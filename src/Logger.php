<?php
/**
 * WordPress Logger
 *
 * A simple, lean logging library for WordPress plugins and themes.
 *
 * @package     ArrayPress\Logger
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @version     1.1.0
 * @author      ArrayPress
 */

declare( strict_types=1 );

namespace ArrayPress\Logger;

use Exception;
use Throwable;
use WP_Error;

/**
 * WordPress Logger Class
 *
 * Simple logging for WordPress with automatic file handling and smart defaults.
 *
 * @since 1.0.0
 */
class Logger {

	/**
	 * Plugin/theme identifier
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private string $log_file;

	/**
	 * Whether logging is enabled
	 *
	 * @var bool
	 */
	private bool $enabled;

	/**
	 * Constructor
	 *
	 * @param string $name     Plugin/theme name for file naming.
	 * @param array  $options  Optional configuration.
	 *                         {
	 *                         Optional configuration arguments.
	 *
	 * @type bool    $enabled  Whether logging is enabled. Default: follows WP_DEBUG.
	 * @type string  $log_file Custom log file path or filename. Default: uploads/{name}/debug.log.
	 *                         }
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $name, array $options = [] ) {
		$this->name     = sanitize_key( $name );
		$this->enabled  = $options['enabled'] ?? $this->should_enable_logging();
		$this->log_file = $this->determine_log_file( $options['log_file'] ?? null );

		$this->setup_log_directory();
	}

	/**
	 * Log an error message
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function error( string $message, array $context = [] ): void {
		$this->log( $message, $context, 'ERROR' );
	}

	/**
	 * Log a warning message
	 *
	 * @param string $message Warning message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function warning( string $message, array $context = [] ): void {
		$this->log( $message, $context, 'WARNING' );
	}

	/**
	 * Log an info message
	 *
	 * @param string $message Info message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function info( string $message, array $context = [] ): void {
		$this->log( $message, $context );
	}

	/**
	 * Log debug information
	 *
	 * @param string $message Debug message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function debug( string $message, array $context = [] ): void {
		$this->log( $message, $context, 'DEBUG' );
	}

	/**
	 * Log an exception
	 *
	 * @param Throwable $exception Exception to log.
	 * @param array     $context   Additional context data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function exception( Throwable $exception, array $context = [] ): void {
		$context = array_merge( $context, [
			'file'  => $exception->getFile(),
			'line'  => $exception->getLine(),
			'trace' => $exception->getTraceAsString(),
		] );

		$message = sprintf( '[%s] %s', get_class( $exception ), $exception->getMessage() );

		$this->error( $message, $context );
	}

	/**
	 * Log a WP_Error
	 *
	 * @param WP_Error $wp_error WP_Error object.
	 * @param array    $context  Additional context data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function wp_error( WP_Error $wp_error, array $context = [] ): void {
		$context['error_code'] = $wp_error->get_error_code();
		$context['error_data'] = $wp_error->get_error_data();

		$this->error( $wp_error->get_error_message(), $context );
	}

	/**
	 * Log a message
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context data.
	 * @param string $level   Log level.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function log( string $message, array $context = [], string $level = 'INFO' ): void {
		if ( ! $this->enabled ) {
			return;
		}

		$timestamp   = current_time( 'c' );
		$context_str = empty( $context ) ? '' : ' ' . wp_json_encode( $context );

		$entry = sprintf(
			"[%s] %s: %s%s\n",
			$timestamp,
			$level,
			$message,
			$context_str
		);

		error_log( $entry, 3, $this->log_file );
	}

	/**
	 * Clear the log file
	 *
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	public function clear(): bool {
		if ( file_exists( $this->log_file ) ) {
			return unlink( $this->log_file );
		}

		return true;
	}

	/**
	 * Get log contents
	 *
	 * @return string Log file contents.
	 * @since 1.0.0
	 */
	public function get_contents(): string {
		if ( ! file_exists( $this->log_file ) ) {
			return '';
		}

		return file_get_contents( $this->log_file ) ?: '';
	}

	/**
	 * Get log file path
	 *
	 * @return string Log file path.
	 * @since 1.0.0
	 */
	public function get_file(): string {
		return $this->log_file;
	}

	/**
	 * Check if logging is enabled
	 *
	 * @return bool True if enabled, false otherwise.
	 * @since 1.0.0
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Determine if logging should be enabled by default
	 *
	 * Checks plugin-specific constant first, then WP_DEBUG.
	 *
	 * @return bool True if logging should be enabled.
	 * @since 1.1.0
	 */
	private function should_enable_logging(): bool {
		// Check for plugin-specific debug constant (e.g., SUGARCART_DEBUG)
		$constant = strtoupper( str_replace( '-', '_', $this->name ) ) . '_DEBUG';

		if ( defined( $constant ) ) {
			return (bool) constant( $constant );
		}

		// Fall back to WP_DEBUG
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Determine log file path with smart defaults
	 *
	 * @param string|null $custom_path Custom log file path or filename.
	 *
	 * @return string Determined log file path.
	 * @since 1.1.0
	 */
	private function determine_log_file( ?string $custom_path ): string {
		if ( $custom_path ) {
			// If just a filename (no slashes), put it in default location
			if ( ! strpos( $custom_path, '/' ) && ! strpos( $custom_path, '\\' ) ) {
				$upload_dir = wp_upload_dir();

				return trailingslashit( $upload_dir['basedir'] ) . $this->name . '/' . $custom_path;
			}

			// Use the provided path as-is
			return $custom_path;
		}

		// Default: uploads/{plugin-name}/debug.log
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . $this->name . '/debug.log';
	}

	/**
	 * Setup log directory with security
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function setup_log_directory(): void {
		$dir = dirname( $this->log_file );

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Add .htaccess protection
		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Deny from all\n" );
		}

		// Add index.php for additional protection
		$index = trailingslashit( $dir ) . 'index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

}