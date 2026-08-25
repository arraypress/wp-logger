# WordPress Logger

A simple, lean logging library for WordPress plugins and themes with smart defaults and registry pattern support. Zero-config initialization that automatically follows WordPress debug conventions.

## Features

* 🚀 **Zero Configuration**: Works immediately with WordPress debug settings
* 📦 **Registry Pattern**: Centralized logger management across plugins
* 🎯 **Smart Defaults**: Automatically follows `WP_DEBUG` - no boilerplate needed
* 📝 **Standard Log Levels**: Error, warning, info, and debug logging
* 🔒 **Automatic Security**: Built-in .htaccess and index.php protection
* 🐛 **Exception Handling**: Native support for exceptions and WP_Error objects
* 📂 **Flexible Paths**: Use simple filenames or full paths
* ⚡ **Lightweight**: Minimal, focused code
* 🎛️ **Plugin-Specific Control**: Enable debugging per plugin via wp-config.php

## Requirements

* PHP 8.3 or later
* WordPress 5.0 or later

## Installation
```bash
composer require arraypress/wp-logger
```

## Basic Usage

### Using the Registry (Recommended)
```php
// Register once in your main plugin file
// Creates: wp-content/uploads/my-plugin/my-plugin.log
register_logger( 'my-plugin' );

// Get and use anywhere in your plugin
$logger = get_logger( 'my-plugin' );
$logger->error( 'Payment processing failed' );
$logger->warning( 'Low inventory alert' );
$logger->info( 'Order processed successfully' );
$logger->debug( 'Debug information' );
```

### Direct Instantiation
```php
use ArrayPress\Logger\Logger;

// Create directly if you prefer
// Creates: wp-content/uploads/my-plugin/my-plugin.log
$logger = new Logger( 'my-plugin' );

// Start logging
$logger->error( 'Payment processing failed' );
$logger->info( 'Order processed successfully' );
```

### Custom Configuration
```php
// Custom filename within the plugin directory
// Creates: wp-content/uploads/my-plugin/errors.log
register_logger( 'my-plugin', [
    'log_file' => 'errors.log'
] );

// Multiple loggers for different purposes. A bare filename always lands in
// the directory belonging to the logger's name.
register_logger( 'my-plugin' );            // → uploads/my-plugin/my-plugin-{hash}.log
register_logger( 'my-plugin-api', [
    'log_file' => 'api.log'                // → uploads/my-plugin-api/api.log
] );
register_logger( 'my-plugin-payments', [
    'log_file' => 'payments.log'           // → uploads/my-plugin-payments/payments.log
] );

// Rotate sooner than the 5 MB default, or not at all.
register_logger( 'my-plugin', [ 'max_size' => 1048576 ] );
register_logger( 'my-plugin', [ 'max_size' => 0 ] );

// Full path override
register_logger( 'my-plugin', [
    'log_file' => WP_CONTENT_DIR . '/logs/custom.log'
] );

// Force enable logging regardless of WP_DEBUG
register_logger( 'my-plugin', [
    'enabled' => true
] );
```

## Smart Debug Control

The logger automatically detects debug settings in this order:

1. **Plugin-specific constant** (if defined)
2. **WP_DEBUG constant** (WordPress standard)

### Via wp-config.php
```php
// Enable debugging for specific plugin only
define( 'MY_PLUGIN_DEBUG', true );

// Or use WordPress debug (affects all loggers using defaults)
define( 'WP_DEBUG', true );
```

## Plugin Integration Pattern
```php
namespace MyPlugin;

use function ArrayPress\Logger\register_logger;
use function ArrayPress\Logger\get_logger;

class Plugin {
    
    public function __construct() {
        // Register logger once
        // Creates: wp-content/uploads/my-plugin/my-plugin.log
        register_logger( 'my-plugin' );
    }
    
    public function process_order( $order_data ) {
        $logger = get_logger( 'my-plugin' );
        $logger->info( 'Processing order', ['order_id' => $order_data['id']] );
        
        try {
            // Process order logic
            $logger->info( 'Order processed successfully' );
        } catch ( Exception $e ) {
            $logger->exception( $e, ['order_data' => $order_data] );
            throw $e;
        }
    }
}
```

### Creating Plugin Wrapper Functions (Optional)

For convenience, you can create wrapper functions in your plugin:
```php
namespace MyPlugin;

use ArrayPress\Logger\Logger;
use function ArrayPress\Logger\get_logger;

function logger(): ?Logger {
    return get_logger( 'my-plugin' );
}

function log_error( string $message, array $context = [] ): void {
    logger()?->error( $message, $context );
}

function log_info( string $message, array $context = [] ): void {
    logger()?->info( $message, $context );
}

// Usage anywhere in your plugin
\MyPlugin\log_error( 'Database connection failed' );
\MyPlugin\log_info( 'Cache cleared successfully' );
```

## Exception and Error Handling

### Exceptions
```php
try {
    process_payment( $data );
} catch ( Exception $e ) {
    $logger->exception( $e, ['user_id' => 123] );
    // Automatically logs message, file, line, and stack trace
}
```

### WordPress Errors
```php
$result = wp_remote_get( $url );

if ( is_wp_error( $result ) ) {
    $logger->wp_error( $result, ['url' => $url] );
    // Automatically logs error code, message, and data
}
```

### Context Data
```php
$logger->error( 'Database connection failed', [
    'host'     => DB_HOST,
    'database' => DB_NAME,
    'user_id'  => get_current_user_id(),
    'memory'   => memory_get_usage()
] );
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | Follows `{PLUGIN}_DEBUG` or `WP_DEBUG` | Whether logging is enabled |
| `log_file` | string | `uploads/{plugin-name}/{plugin-name}-{hash}.log` | Log file path or filename |
| `max_size` | int | `5242880` (5 MB) | Bytes after which the log rotates. `0` disables rotation |

## File Locations

Default location pattern:
```
wp-content/uploads/{plugin-name}/{plugin-name}-{hash}.log
```

Examples:
- `sugarcart` → `wp-content/uploads/sugarcart/sugarcart-3f2a…c91.log`
- `my-plugin` → `wp-content/uploads/my-plugin/my-plugin-8b41…2de.log`

The library automatically:
- Creates directories as needed
- Adds `.htaccess` to deny direct access
- Adds `index.php` for additional security
- Rotates the log once it passes `max_size`, keeping one previous generation
  as `{file}.log.1`

### Why the filename carries a hash

`.htaccess` is an Apache file. On nginx — which a great many WordPress hosts
run — it is ignored entirely and the uploads directory is served as ordinary
static files. A predictable log name is then a predictable URL for a file
holding email addresses, IP addresses and gateway responses.

The suffix is `wp_hash()` of the logger name, so it is derived from the site's
own salts and cannot be guessed from outside. It is stable, so the path does
not change between requests. This is the approach WooCommerce takes for the
same reason.

Passing an explicit `log_file` opts out of this, so put such a file somewhere
that is not web-served.

## Rotation

Once the log would pass `max_size` (5 MB by default) it is renamed to
`{file}.log.1` and a fresh file started. Only one previous generation is kept,
so a site left in debug mode cannot fill its disk. `clear()` removes both.

## Log Format
```
[2025-01-15T10:30:45+00:00] ERROR: Payment processing failed {"user_id":123,"amount":99.99}
[2025-01-15T10:30:46+00:00] INFO: Order processed successfully {"order_id":"12345"}
[2025-01-15T10:30:47+00:00] DEBUG: Cache cleared {"cache_key":"user_123_orders"}
```

## API Reference

### Registry Functions

- `register_logger( string $name, array $options = [] ): Logger` - Register a new logger
- `get_logger( string $name ): ?Logger` - Get a registered logger
- `has_logger( string $name ): bool` - Check if a logger exists
- `remove_logger( string $name ): bool` - Remove a logger

### Logging Methods

- `error( string $message, array $context = [] )` - Log error messages
- `warning( string $message, array $context = [] )` - Log warnings
- `info( string $message, array $context = [] )` - Log informational messages
- `debug( string $message, array $context = [] )` - Log debug information
- `log( string $message, array $context = [], string $level = 'INFO' )` - Generic logging

### Specialized Methods

- `exception( Throwable $exception, array $context = [] )` - Log exceptions with trace
- `wp_error( WP_Error $wp_error, array $context = [] )` - Log WordPress errors

### Utility Methods

- `clear()` - Clear the log file
- `get_contents()` - Get log file contents
- `get_file()` - Get log file path
- `is_enabled()` - Check if logging is enabled

## Why This Logger?

Unlike complex logging libraries, this logger is designed specifically for WordPress with just enough features:

- **No configuration required** - Uses WordPress conventions by default
- **No dependencies** - Just one simple class
- **WordPress-native** - Uses WordPress functions and follows WordPress patterns
- **Registry pattern** - Centralized management without globals
- **Smart naming** - Each plugin gets its own named log file automatically
- **Predictable** - Does exactly what you expect, nothing more

Perfect for plugins and themes that need reliable logging without the overhead of large logging frameworks.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-logger)
- [Issue Tracker](https://github.com/arraypress/wp-logger/issues)