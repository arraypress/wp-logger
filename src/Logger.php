<?php
/**
 * WordPress Logger
 *
 * A simple, lean logging library for WordPress plugins and themes.
 *
 * @package ArrayPress\WPLogger
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\Logger;

use Exception;
use Throwable;
use WP_Error;

/**
 * WordPress Logger Class
 *
 * Simple logging for WordPress with automatic file handling.
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
	 * Whether debug mode is enabled
	 *
	 * @var bool
	 */
	private bool $debug_mode;

	/**
	 * Constructor
	 *
	 * @param string $name       Plugin/theme name for file naming.
	 * @param array  $options    Optional configuration.
	 *                           {
	 *
	 * @type string  $log_file   Custom log file path.
	 * @type bool    $enabled    Whether logging is enabled. Default true.
	 * @type bool    $debug_mode Whether debug logging is enabled. Default false.
	 *                           }
	 */
	public function __construct( string $name, array $options = [] ) {
		$this->name       = sanitize_key( $name );
		$this->enabled    = $options['enabled'] ?? true;
		$this->debug_mode = $options['debug_mode'] ?? false;
		$this->log_file   = $options['log_file'] ?? $this->get_default_log_file();

		$this->setup_log_directory();
	}

	/**
	 * Log an error message
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
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
	 */
	public function info( string $message, array $context = [] ): void {
		$this->log( $message, $context, 'INFO' );
	}

	/**
	 * Log debug information
	 *
	 * @param string $message Debug message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	public function debug( string $message, array $context = [] ): void {
		if ( ! $this->debug_mode ) {
			return;
		}

		$this->log( $message, $context, 'DEBUG' );
	}

	/**
	 * Log an exception
	 *
	 * @param Exception|Throwable $exception Exception to log.
	 * @param array               $context   Additional context data.
	 *
	 * @return void
	 */
	public function exception( $exception, array $context = [] ): void {
		if ( ! ( $exception instanceof Throwable ) ) {
			return;
		}

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
	 */
	public function get_file(): string {
		return $this->log_file;
	}

	/**
	 * Check if logging is enabled
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Get default log file path
	 *
	 * @return string Default log file path.
	 */
	private function get_default_log_file(): string {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . $this->name . '-debug.log';
	}

	/**
	 * Setup log directory with security
	 *
	 * @return void
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
	}

}