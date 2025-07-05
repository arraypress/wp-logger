# WordPress Logger

A simple, lean logging library for WordPress plugins and themes. Provides clean APIs for error logging, debugging, and exception handling with automatic file management and security.

## Features

* 🚀 **Simple Setup**: One-line initialization with sensible defaults
* 📝 **Standard Log Levels**: Error, warning, info, and debug logging
* 🔒 **Automatic Security**: Built-in .htaccess protection for log files
* 🐛 **Exception Handling**: Native support for exceptions and WP_Error objects
* 📂 **Smart File Management**: Automatic directory creation and path resolution
* ⚡ **Lightweight**: Minimal overhead with maximum functionality
* 🎛️ **Configurable**: Debug mode and custom file paths supported

## Requirements

* PHP 7.4 or later
* WordPress 5.0 or later

## Installation

```bash
composer require arraypress/wp-logger
```

## Basic Usage

### Simple Setup

```php
use ArrayPress\WPLogger\Logger;

// Basic logger for your plugin
$logger = new Logger( 'my-plugin' );

// Log messages
$logger->error( 'Payment processing failed' );
$logger->warning( 'Low inventory alert' );
$logger->info( 'Order processed successfully' );
$logger->debug( 'Debug information' );
```

### With Configuration

```php
// Enable debug mode and custom path
$logger = new Logger( 'my-plugin', [
    'debug_mode' => true,
    'log_file'   => WP_CONTENT_DIR . '/debug/my-plugin.log'
]);
```

### Exception Logging

```php
try {
    // Your code here
    process_payment( $data );
} catch ( Exception $e ) {
    $logger->exception( $e, ['user_id' => 123] );
}
```

### WordPress Error Handling

```php
$result = wp_remote_get( $url );

if ( is_wp_error( $result ) ) {
    $logger->wp_error( $result, ['url' => $url] );
}
```

### Context Data

```php
$logger->error( 'Database connection failed', [
    'host'     => DB_HOST,
    'database' => DB_NAME,
    'user_id'  => get_current_user_id()
]);
```

## Advanced Usage

### Plugin Integration

```php
class MyPlugin {
    private Logger $logger;
    
    public function __construct() {
        $this->logger = new Logger( 'my-plugin', [
            'debug_mode' => defined( 'MY_PLUGIN_DEBUG' ) && MY_PLUGIN_DEBUG
        ]);
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

### Helper Functions

```php
// Create global helper functions
function log_error( string $message, array $context = [] ): void {
    MyPlugin()->logger->error( $message, $context );
}

function log_debug( string $message, array $context = [] ): void {
    MyPlugin()->logger->debug( $message, $context );
}
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `log_file` | string | `uploads/{name}-debug.log` | Custom log file path |
| `enabled` | bool | `true` | Whether logging is enabled |
| `debug_mode` | bool | `false` | Whether debug logging is enabled |

## File Locations

By default, logs are stored in:
```
wp-content/uploads/{plugin-name}-debug.log
```

The library automatically:
- Creates the directory if it doesn't exist
- Adds `.htaccess` protection to prevent direct access
- Uses WordPress-standard file permissions

## Log Format

```
[2025-01-15T10:30:45+00:00] ERROR: Payment processing failed {"user_id":123,"amount":99.99}
[2025-01-15T10:30:46+00:00] INFO: Order processed successfully {"order_id":"12345"}
[2025-01-15T10:30:47+00:00] DEBUG: Cache cleared {"cache_key":"user_123_orders"}
```

## Available Methods

### Logging Methods
- `error( string $message, array $context = [] )` - Log error messages
- `warning( string $message, array $context = [] )` - Log warnings
- `info( string $message, array $context = [] )` - Log informational messages
- `debug( string $message, array $context = [] )` - Log debug information (requires debug_mode)

### Specialized Methods
- `exception( Exception $exception, array $context = [] )` - Log exceptions with stack trace
- `wp_error( WP_Error $wp_error, array $context = [] )` - Log WordPress errors

### Utility Methods
- `clear()` - Clear the log file
- `get_contents()` - Get log file contents
- `get_file()` - Get log file path
- `is_enabled()` - Check if logging is enabled

## Security

The library automatically protects log files by:
- Creating `.htaccess` files to deny direct access
- Storing logs outside the web root when possible
- Using WordPress-standard file permissions

## Requirements

- PHP 7.4+
- WordPress 5.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-logger)
- [Issue Tracker](https://github.com/arraypress/wp-logger/issues)