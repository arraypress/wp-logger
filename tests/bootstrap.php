<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ArrayPress\Logger
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

/**
 * Root of the throwaway uploads tree the suite writes into.
 *
 * Logger is a file writer, so the tests exercise real files. Anything else
 * would be testing a mock of the filesystem rather than the library.
 *
 * @var string
 */
$GLOBALS['logger_uploads'] = sys_get_temp_dir() . '/wp-logger-tests-' . getmypid();

if ( ! function_exists( 'wp_upload_dir' ) ) {
	/**
	 * Point the library at the throwaway uploads tree.
	 *
	 * @return array<string, mixed>
	 */
	function wp_upload_dir(): array {
		return [
			'basedir' => $GLOBALS['logger_uploads'],
			'baseurl' => 'https://example.test/wp-content/uploads',
			'error'   => false,
		];
	}
}

if ( ! function_exists( 'wp_hash' ) ) {
	/**
	 * Salted hash.
	 *
	 * Core keys this on the site's AUTH_SALT. The suite uses a fixed key: what
	 * the tests care about is that the value is derived and stable, not the
	 * particular digest.
	 *
	 * @param string $data   Value to hash.
	 * @param string $scheme Unused.
	 *
	 * @return string
	 */
	function wp_hash( string $data, string $scheme = 'auth' ): string {
		return hash_hmac( 'md5', $data, 'test-salt-not-a-real-secret' );
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	/**
	 * Recursively create a directory.
	 *
	 * @param string $target Directory to create.
	 *
	 * @return bool
	 */
	function wp_mkdir_p( string $target ): bool {
		return is_dir( $target ) || mkdir( $target, 0777, true );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Formatted current time.
	 *
	 * @param string $type Format, or 'c' for ISO 8601.
	 *
	 * @return string
	 */
	function current_time( string $type ): string {
		return 'c' === $type ? gmdate( 'c' ) : gmdate( $type );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * JSON-encode a value.
	 *
	 * @param mixed $data Value to encode.
	 *
	 * @return string|false
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Pass a value straight through; nothing here registers filters.
	 *
	 * @param string $hook  Unused.
	 * @param mixed  $value Value to return.
	 *
	 * @return mixed
	 */
	function apply_filters( string $hook, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Escape for HTML output.
	 *
	 * @param string $text Text to escape.
	 *
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal stand-in for core's error object.
	 */
	class WP_Error {

		/**
		 * @var string
		 */
		private string $code;

		/**
		 * @var string
		 */
		private string $message;

		/**
		 * @var mixed
		 */
		private $data;

		/**
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Error data.
		 */
		public function __construct( string $code = '', string $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * @return string
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

/*
 * sanitize_key(), sanitize_file_name() and trailingslashit() below are copied
 * verbatim from WordPress core. Logger builds a filename out of all three, and
 * the tests assert what that filename is; a hand-written approximation would
 * only prove the approximation agrees with itself.
 */
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$sanitized_key = '';

		if ( is_scalar( $key ) ) {
			$sanitized_key = strtolower( $key );
			$sanitized_key = preg_replace( '/[^a-z0-9_\-]/', '', $sanitized_key );
		}

		/**
		 * Filters a sanitized key string.
		 *
		 * @since 3.0.0
		 *
		 * @param string $sanitized_key Sanitized key.
		 * @param string $key           The key prior to sanitization.
		 */
		return apply_filters( 'sanitize_key', $sanitized_key, $key );
	}
}


if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return untrailingslashit( $value ) . '/';
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $value ) {
		return rtrim( $value, '/\\' );
	}
}



require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/*
 * And src/Functions.php again: it is a Composer `files` entry, so it already
 * ran when PHPUnit loaded the autoloader -- before ABSPATH was defined, so it
 * returned without declaring anything. `require`, not `require_once`, because
 * Composer already included this exact path.
 */
require dirname( __DIR__ ) . '/src/Functions.php';
