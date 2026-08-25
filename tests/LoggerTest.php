<?php
/**
 * Logger tests.
 *
 * @package ArrayPress\Logger
 */

declare( strict_types=1 );

namespace ArrayPress\Logger\Tests;

use ArrayPress\Logger\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Covers Logger.
 */
class LoggerTest extends TestCase {

	/**
	 * Remove the throwaway uploads tree between tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->rmdir( $GLOBALS['logger_uploads'] );
		parent::tearDown();
	}

	/**
	 * Build a logger writing into the throwaway tree.
	 *
	 * @param array $options Constructor options.
	 *
	 * @return Logger
	 */
	private function logger( array $options = [] ): Logger {
		return new Logger( 'sugarcart', $options + [ 'enabled' => true ] );
	}

	/**
	 * Delete a directory tree.
	 *
	 * @param string $dir Directory to remove.
	 *
	 * @return void
	 */
	private function rmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( scandir( $dir ) as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$path = $dir . '/' . $entry;

			is_dir( $path ) ? $this->rmdir( $path ) : unlink( $path );
		}

		rmdir( $dir );
	}

	/**
	 * The default log file name carries a salted hash.
	 *
	 * The .htaccess written alongside it only applies on Apache. On nginx the
	 * uploads directory is served as static files, so a predictable name is a
	 * predictable URL for a file holding customer data. The hash is the part
	 * that actually keeps it private.
	 *
	 * @return void
	 */
	public function test_default_file_name_is_not_guessable(): void {
		$file = basename( $this->logger()->get_file() );

		$this->assertNotSame( 'sugarcart.log', $file );
		$this->assertMatchesRegularExpression( '/^sugarcart-[a-f0-9]{32}\.log$/', $file );
		$this->assertStringContainsString( wp_hash( 'sugarcart' ), $file );
	}

	/**
	 * The same name resolves to the same file across instances.
	 *
	 * @return void
	 */
	public function test_file_name_is_stable(): void {
		$this->assertSame( $this->logger()->get_file(), $this->logger()->get_file() );
	}

	/**
	 * Different plugins get different files.
	 *
	 * @return void
	 */
	public function test_different_names_get_different_files(): void {
		$a = ( new Logger( 'sugarcart', [ 'enabled' => true ] ) )->get_file();
		$b = ( new Logger( 'other-plugin', [ 'enabled' => true ] ) )->get_file();

		$this->assertNotSame( $a, $b );

		// Each plugin owns its directory, so clearing one cannot touch another.
		$this->assertSame( $GLOBALS['logger_uploads'] . '/sugarcart', dirname( $a ) );
		$this->assertSame( $GLOBALS['logger_uploads'] . '/other-plugin', dirname( $b ) );
	}

	/**
	 * The directory gets its Apache and directory-listing guards.
	 *
	 * @return void
	 */
	public function test_directory_is_protected(): void {
		$dir = dirname( $this->logger()->get_file() );

		$this->assertFileExists( $dir . '/.htaccess' );
		$this->assertFileExists( $dir . '/index.php' );
		$this->assertStringContainsString( 'Deny from all', file_get_contents( $dir . '/.htaccess' ) );
	}

	/**
	 * A message reaches the file with its level and timestamp.
	 *
	 * @return void
	 */
	public function test_writes_entry(): void {
		$logger = $this->logger();
		$logger->error( 'Gateway refused the charge' );

		$contents = $logger->get_contents();

		$this->assertStringContainsString( 'ERROR: Gateway refused the charge', $contents );
		$this->assertMatchesRegularExpression( '/^\[\d{4}-\d{2}-\d{2}T/', $contents );
	}

	/**
	 * Each level is labelled, and info() defaults to INFO.
	 *
	 * @return void
	 */
	public function test_levels_are_labelled(): void {
		$logger = $this->logger();

		$logger->error( 'e' );
		$logger->warning( 'w' );
		$logger->info( 'i' );
		$logger->debug( 'd' );

		$contents = $logger->get_contents();

		foreach ( [ 'ERROR: e', 'WARNING: w', 'INFO: i', 'DEBUG: d' ] as $expected ) {
			$this->assertStringContainsString( $expected, $contents );
		}
	}

	/**
	 * Context is appended as JSON.
	 *
	 * @return void
	 */
	public function test_context_is_encoded(): void {
		$logger = $this->logger();
		$logger->info( 'Order paid', [ 'order_id' => 42 ] );

		$this->assertStringContainsString( '{"order_id":42}', $logger->get_contents() );
	}

	/**
	 * A disabled logger writes nothing at all.
	 *
	 * @return void
	 */
	public function test_disabled_logger_writes_nothing(): void {
		$logger = new Logger( 'sugarcart', [ 'enabled' => false ] );
		$logger->error( 'ignored' );

		$this->assertFalse( $logger->is_enabled() );
		$this->assertFileDoesNotExist( $logger->get_file() );
		$this->assertSame( '', $logger->get_contents() );
	}

	/**
	 * Entries accumulate rather than replacing each other.
	 *
	 * @return void
	 */
	public function test_entries_append(): void {
		$logger = $this->logger();
		$logger->info( 'first' );
		$logger->info( 'second' );

		$contents = $logger->get_contents();

		$this->assertStringContainsString( 'first', $contents );
		$this->assertStringContainsString( 'second', $contents );
		$this->assertSame( 2, substr_count( $contents, "\n" ) );
	}

	/**
	 * An exception records its class, file, line and trace.
	 *
	 * @return void
	 */
	public function test_exception_records_origin(): void {
		$logger = $this->logger();
		$logger->exception( new RuntimeException( 'Signing failed' ) );

		$contents = $logger->get_contents();

		$this->assertStringContainsString( '[RuntimeException] Signing failed', $contents );
		$this->assertStringContainsString( 'ERROR:', $contents );
		$this->assertStringContainsString( '"line"', $contents );
		$this->assertStringContainsString( '"trace"', $contents );
	}

	/**
	 * A WP_Error records its code and data alongside the message.
	 *
	 * @return void
	 */
	public function test_wp_error_records_code_and_data(): void {
		$logger = $this->logger();
		$logger->wp_error( new \WP_Error( 'http_request_failed', 'Timed out', [ 'status' => 504 ] ) );

		$contents = $logger->get_contents();

		$this->assertStringContainsString( 'ERROR: Timed out', $contents );
		$this->assertStringContainsString( '"error_code":"http_request_failed"', $contents );
		$this->assertStringContainsString( '"status":504', $contents );
	}

	/**
	 * Past the size limit the log rotates instead of growing.
	 *
	 * @return void
	 */
	public function test_rotates_at_the_size_limit(): void {
		$logger = $this->logger( [ 'max_size' => 200 ] );

		for ( $i = 0; $i < 20; $i++ ) {
			$logger->info( str_repeat( 'x', 50 ) );
		}

		$this->assertFileExists( $logger->get_file() . '.1' );
		$this->assertLessThanOrEqual( 200, filesize( $logger->get_file() ) );
	}

	/**
	 * Only one previous generation is kept.
	 *
	 * @return void
	 */
	public function test_rotation_keeps_a_single_generation(): void {
		$logger = $this->logger( [ 'max_size' => 100 ] );

		for ( $i = 0; $i < 40; $i++ ) {
			$logger->info( str_repeat( 'y', 50 ) );
		}

		$dir   = dirname( $logger->get_file() );
		$logs  = glob( $dir . '/*.log*' );

		$this->assertCount( 2, $logs );
		$this->assertFileDoesNotExist( $logger->get_file() . '.2' );
	}

	/**
	 * max_size of zero lets the log grow.
	 *
	 * @return void
	 */
	public function test_zero_max_size_disables_rotation(): void {
		$logger = $this->logger( [ 'max_size' => 0 ] );

		for ( $i = 0; $i < 20; $i++ ) {
			$logger->info( str_repeat( 'z', 50 ) );
		}

		$this->assertFileDoesNotExist( $logger->get_file() . '.1' );
		$this->assertGreaterThan( 1000, filesize( $logger->get_file() ) );
	}

	/**
	 * A negative max_size is treated as unlimited, not as "rotate every write".
	 *
	 * @return void
	 */
	public function test_negative_max_size_is_clamped(): void {
		$logger = $this->logger( [ 'max_size' => -1 ] );

		$logger->info( 'first' );
		$logger->info( 'second' );

		$this->assertFileDoesNotExist( $logger->get_file() . '.1' );
		$this->assertStringContainsString( 'first', $logger->get_contents() );
	}

	/**
	 * clear() removes the rotated generation too.
	 *
	 * Deleting only the live file would leave most of the log on disk, which
	 * is the opposite of what a caller clearing a log is asking for.
	 *
	 * @return void
	 */
	public function test_clear_removes_rotated_generation(): void {
		$logger = $this->logger( [ 'max_size' => 200 ] );

		for ( $i = 0; $i < 20; $i++ ) {
			$logger->info( str_repeat( 'x', 50 ) );
		}

		$this->assertFileExists( $logger->get_file() . '.1' );
		$this->assertTrue( $logger->clear() );
		$this->assertFileDoesNotExist( $logger->get_file() );
		$this->assertFileDoesNotExist( $logger->get_file() . '.1' );
		$this->assertSame( '', $logger->get_contents() );
	}

	/**
	 * clear() succeeds when there is nothing to clear.
	 *
	 * @return void
	 */
	public function test_clear_on_empty_log_succeeds(): void {
		$this->assertTrue( $this->logger()->clear() );
	}

	/**
	 * A custom bare filename lands in the plugin's own directory.
	 *
	 * @return void
	 */
	public function test_custom_filename_stays_in_the_plugin_directory(): void {
		$logger = $this->logger( [ 'log_file' => 'gateway.log' ] );

		$this->assertSame(
			$GLOBALS['logger_uploads'] . '/sugarcart/gateway.log',
			$logger->get_file()
		);
	}

	/**
	 * A custom absolute path is used as given.
	 *
	 * @return void
	 */
	public function test_custom_path_is_used_verbatim(): void {
		$path   = $GLOBALS['logger_uploads'] . '/elsewhere/custom.log';
		$logger = $this->logger( [ 'log_file' => $path ] );

		$this->assertSame( $path, $logger->get_file() );

		$logger->info( 'written' );

		$this->assertStringContainsString( 'written', file_get_contents( $path ) );
	}

	/**
	 * The name is reduced to a key, so it cannot escape the uploads tree.
	 *
	 * @return void
	 */
	public function test_name_is_sanitised(): void {
		$logger = new Logger( '../../Evil Plugin!', [ 'enabled' => true ] );

		$this->assertStringNotContainsString( '..', $logger->get_file() );
		$this->assertStringStartsWith( $GLOBALS['logger_uploads'] . '/', $logger->get_file() );
	}

}
