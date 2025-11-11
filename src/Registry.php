<?php
/**
 * Logger Registry
 *
 * Centralized registry for managing logger instances across plugins and themes.
 *
 * @package     ArrayPress\Logger
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Logger;

use Exception;

/**
 * Registry Class
 *
 * Singleton registry for managing logger instances across the application.
 * Provides centralized access to Logger instances without using global variables.
 *
 * @since 1.0.0
 */
class Registry {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered logger instances
	 *
	 * @var array<string, Logger>
	 */
	private array $loggers = [];

	/**
	 * Private constructor
	 *
	 * Prevents direct instantiation to enforce singleton pattern.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
	}

	/**
	 * Prevent cloning
	 *
	 * Ensures singleton instance cannot be cloned.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function __clone() {
	}

	/**
	 * Prevent unserialization
	 *
	 * Ensures singleton instance cannot be unserialized.
	 *
	 * @return void
	 * @throws Exception When attempting to unserialize.
	 * @since 1.0.0
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Get registry instance
	 *
	 * Returns the singleton instance of the registry, creating it if necessary.
	 *
	 * @return self Registry instance.
	 * @since 1.0.0
	 */
	public static function get_instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a logger
	 *
	 * Creates and stores a new Logger instance or returns existing one.
	 *
	 * @param string $name     Plugin/theme name for the logger.
	 * @param array  $options  {
	 *                         Optional configuration arguments.
	 *
	 * @type bool    $enabled  Whether logging is enabled. Default: follows {NAME}_DEBUG or WP_DEBUG.
	 * @type string  $log_file Custom log file path or filename. Default: uploads/{name}/debug.log.
	 *                         }
	 *
	 * @return Logger The logger instance.
	 * @since 1.0.0
	 */
	public function register( string $name, array $options = [] ): Logger {
		$name = sanitize_key( $name );

		// Return existing instance if already registered
		if ( isset( $this->loggers[ $name ] ) ) {
			return $this->loggers[ $name ];
		}

		// Create and store new logger
		$this->loggers[ $name ] = new Logger( $name, $options );

		return $this->loggers[ $name ];
	}

	/**
	 * Get a logger instance
	 *
	 * Retrieves a registered logger instance by name.
	 *
	 * @param string $name Plugin/theme name.
	 *
	 * @return Logger|null Logger instance or null if not found.
	 * @since 1.0.0
	 */
	public function get( string $name ): ?Logger {
		$name = sanitize_key( $name );

		return $this->loggers[ $name ] ?? null;
	}

	/**
	 * Check if a logger exists
	 *
	 * Determines whether a logger has been registered for the given name.
	 *
	 * @param string $name Plugin/theme name.
	 *
	 * @return bool True if logger exists, false otherwise.
	 * @since 1.0.0
	 */
	public function has( string $name ): bool {
		$name = sanitize_key( $name );

		return isset( $this->loggers[ $name ] );
	}

	/**
	 * Remove a logger
	 *
	 * Unregisters a logger from the registry.
	 *
	 * @param string $name Plugin/theme name.
	 *
	 * @return bool True if logger was removed, false if not found.
	 * @since 1.0.0
	 */
	public function remove( string $name ): bool {
		$name = sanitize_key( $name );

		if ( isset( $this->loggers[ $name ] ) ) {
			unset( $this->loggers[ $name ] );

			return true;
		}

		return false;
	}

	/**
	 * Get all registered loggers
	 *
	 * Returns all currently registered logger instances.
	 *
	 * @return array<string, Logger> Array of name => Logger instance.
	 * @since 1.0.0
	 */
	public function get_all(): array {
		return $this->loggers;
	}

	/**
	 * Get all registered names
	 *
	 * Returns an array of all registered logger names.
	 *
	 * @return string[] Array of registered names.
	 * @since 1.0.0
	 */
	public function get_names(): array {
		return array_keys( $this->loggers );
	}

	/**
	 * Count registered loggers
	 *
	 * Returns the total number of registered loggers.
	 *
	 * @return int Number of registered loggers.
	 * @since 1.0.0
	 */
	public function count(): int {
		return count( $this->loggers );
	}

	/**
	 * Clear all loggers
	 *
	 * Removes all registered loggers from the registry.
	 * Primarily useful for testing or complete reinitialization.
	 *
	 * @return self Returns self for method chaining.
	 * @since 1.0.0
	 */
	public function clear(): self {
		$this->loggers = [];

		return $this;
	}

	/**
	 * Reset the singleton instance
	 *
	 * Clears the singleton instance, forcing creation of a new one on next access.
	 * Should only be used for testing purposes.
	 *
	 * @return void
	 * @internal
	 * @since 1.0.0
	 */
	public static function reset(): void {
		self::$instance = null;
	}

}