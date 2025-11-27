<?php
/**
 * Logger Helper Functions
 *
 * Core functionality helpers for logger registration and management.
 * These global functions provide a simplified API for logger usage.
 *
 * @package     ArrayPress\Logger
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

use ArrayPress\Logger\Logger;
use ArrayPress\Logger\Registry;

if ( ! function_exists( 'register_logger' ) ) {
	/**
	 * Register a logger
	 *
	 * Creates and registers a new logger instance for a plugin or theme.
	 * If a logger with the same name already exists, returns the existing instance.
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
	function register_logger( string $name, array $options = [] ): Logger {
		$registry = Registry::get_instance();

		return $registry->register( $name, $options );
	}
}

if ( ! function_exists( 'get_logger' ) ) {
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
	function get_logger( string $name ): ?Logger {
		$registry = Registry::get_instance();

		return $registry->get( $name );
	}
}



if ( ! function_exists( 'has_logger' ) ) {
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
	function has_logger( string $name ): bool {
		$registry = Registry::get_instance();

		return $registry->has( $name );
	}
}

if ( ! function_exists( 'remove_logger' ) ) {
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
	function remove_logger( string $name ): bool {
		$registry = Registry::get_instance();

		return $registry->remove( $name );
	}
}

if ( ! function_exists( 'get_all_loggers' ) ) {
	/**
	 * Get all registered loggers
	 *
	 * Returns all currently registered logger instances.
	 *
	 * @return array<string, Logger> Array of name => Logger instance.
	 * @since 1.0.0
	 */
	function get_all_loggers(): array {
		$registry = Registry::get_instance();

		return $registry->get_all();
	}
}