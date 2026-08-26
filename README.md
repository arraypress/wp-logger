# WordPress Logger

A debug log per plugin, in a file nobody can find by guessing.

## What it does

`error_log()` puts everything from every plugin in one file, which is useless
the moment two things are misbehaving. The usual fix is a per-plugin log in
uploads — but a predictable path means a public URL, and that file holds
customer emails, IP addresses and gateway responses.

The filename carries a hash of the plugin name derived from the site's own
salts, so it cannot be guessed from outside. That matters because the
`.htaccess` written alongside it does nothing on nginx.

## Features

- One log per plugin, at a path that cannot be guessed
- Levels for error, warning, info and debug
- Log a `Throwable` with its file, line and trace, or a `WP_Error` with its code
- Attach structured context to any entry, stored as JSON
- Rotates at a size limit, keeping one previous generation, so a forgotten
  debug flag cannot fill the disk
- Off unless `WP_DEBUG` or your own `{PLUGIN}_DEBUG` constant says otherwise
- Registry, so anything in the plugin can reach the same logger

## Installation

```bash
composer require arraypress/wp-logger
```

## Quick start

```php
// Once, during bootstrap.
register_logger( 'sugarcart' );

// Anywhere after that.
$log = get_logger( 'sugarcart' );

$log->info( 'Order completed', [ 'order_id' => 1001, 'total' => 4999 ] );

try {
    $gateway->charge( $order );
} catch ( GatewayException $e ) {
    $log->exception( $e, [ 'order_id' => $order->id ] );
}

// WP_Error keeps its code and data.
$response = wp_remote_post( $url, $args );

if ( is_wp_error( $response ) ) {
    $log->wp_error( $response );
}
```

Logging is off unless `WP_DEBUG` is on, or you define `SUGARCART_DEBUG`.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-2.0-or-later
