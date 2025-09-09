# WordPress Logger

A simple, lean logging library for WordPress plugins and themes with smart defaults. Zero-config initialization that automatically follows WordPress debug conventions.

## Features

* 🚀 **Zero Configuration**: Works immediately with WordPress debug settings
* 🎯 **Smart Defaults**: Automatically follows `WP_DEBUG` - no boilerplate needed
* 📝 **Standard Log Levels**: Error, warning, info, and debug logging
* 🔒 **Automatic Security**: Built-in .htaccess and index.php protection
* 🐛 **Exception Handling**: Native support for exceptions and WP_Error objects
* 📂 **Flexible Paths**: Use simple filenames or full paths
* ⚡ **Lightweight**: ~240 lines of focused code
* 🎛️ **Plugin-Specific Control**: Enable debugging per plugin via wp-config.php

## Requirements

* PHP 7.4 or later
* WordPress 5.0 or later

## Installation

```bash
composer require arraypress/wp-logger
```

## Basic Usage

### Zero Configuration

```php
use ArrayPress\Logger\Logger;

// That's it! Automatically follows WP_DEBUG
$logger = new Logger( 'my-plugin' );

// Start logging
$logger->error( 'Payment processing failed' );
$logger->warning( 'Low inventory alert' );
$logger->info( 'Order processed successfully' );
$logger->debug( 'Debug information' );
```

### Custom Configuration

```php
// Just a custom filename (goes to uploads/my-plugin/custom.log)
$logger = new Logger( 'my-plugin', [
    'log_file' => 'custom.log'
] );

// Or specify a full path
$logger = new Logger( 'my-plugin', [
    'log_file' => WP_CONTENT_DIR . '/logs/my-plugin.log'
] );

// Force enable logging regardless of WP_DEBUG
$logger = new Logger( 'my-plugin', [
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

### Plugin Integration

```php
class MyPlugin {
    private Logger $logger;
    
    public function __construct() {
        // Automatically uses MY_PLUGIN_DEBUG or WP_DEBUG
        $this->logger = new Logger( 'my-plugin' );
    }
    
    public function process_order( $order_data ) {
        $this->logger->info( 'Processing order', ['order_id' => $order_data['id']] );
        
        try {
            // Process order logic
            $this->logger->info( 'Order processed successfully' );
        } catch ( Exception $e ) {
            $this->logger->exception( $e, ['order_data' => $order_data] );
            throw $e;
        }
    }
}
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
| `log_file` | string | `uploads/{plugin-name}/debug.log` | Log file path or filename |

## File Locations

Default location:
```
wp-content/uploads/{plugin-name}/debug.log
```

The library automatically:
- Creates directories as needed
- Adds `.htaccess` to deny direct access
- Adds `index.php` for additional security
- Uses proper WordPress file permissions

## Log Format

```
[2025-01-15T10:30:45+00:00] ERROR: Payment processing failed {"user_id":123,"amount":99.99}
[2025-01-15T10:30:46+00:00] INFO: Order processed successfully {"order_id":"12345"}
[2025-01-15T10:30:47+00:00] DEBUG: Cache cleared {"cache_key":"user_123_orders"}
```

## API Reference

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
- **Predictable** - Does exactly what you expect, nothing more

Perfect for plugins and themes that need reliable logging without the overhead of large logging frameworks.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-logger)
- [Issue Tracker](https://github.com/arraypress/wp-logger/issues)