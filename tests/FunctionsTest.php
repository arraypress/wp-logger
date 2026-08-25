<?php
/**
 * Global function tests.
 *
 * @package ArrayPress\Logger
 */

declare( strict_types=1 );

namespace ArrayPress\Logger\Tests;

use ArrayPress\Logger\Logger;
use ArrayPress\Logger\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Covers src/Functions.php.
 */
class FunctionsTest extends TestCase {

	/**
	 * Start every test with an empty registry.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Registry::reset();
	}

	/**
	 * Leave nothing behind.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Registry::reset();

		$dir = $GLOBALS['logger_uploads'];

		if ( is_dir( $dir ) ) {
			exec( 'rm -rf ' . escapeshellarg( $dir ) );
		}

		parent::tearDown();
	}

	/**
	 * Every documented global is declared.
	 *
	 * src/Functions.php returns early unless ABSPATH is defined, so this also
	 * catches a bootstrap that never loads it -- in which case the rest of this
	 * class would fail with "undefined function" rather than a useful message.
	 *
	 * @return void
	 */
	public function test_globals_are_declared(): void {
		foreach ( [ 'register_logger', 'get_logger', 'has_logger', 'remove_logger', 'get_all_loggers' ] as $fn ) {
			$this->assertTrue( function_exists( $fn ), $fn );
		}
	}

	/**
	 * register_logger() returns a Logger and registers it.
	 *
	 * @return void
	 */
	public function test_register_logger(): void {
		$logger = register_logger( 'sugarcart', [ 'enabled' => true ] );

		$this->assertInstanceOf( Logger::class, $logger );
		$this->assertTrue( has_logger( 'sugarcart' ) );
		$this->assertSame( $logger, get_logger( 'sugarcart' ) );
	}

	/**
	 * The globals share the registry singleton rather than each holding their own.
	 *
	 * @return void
	 */
	public function test_globals_share_one_registry(): void {
		$logger = register_logger( 'sugarcart', [ 'enabled' => true ] );

		$this->assertSame( $logger, Registry::get_instance()->get( 'sugarcart' ) );
		$this->assertSame( [ 'sugarcart' => $logger ], get_all_loggers() );
	}

	/**
	 * get_logger() on an unregistered name yields null.
	 *
	 * @return void
	 */
	public function test_get_logger_missing(): void {
		$this->assertNull( get_logger( 'nothing' ) );
		$this->assertFalse( has_logger( 'nothing' ) );
	}

	/**
	 * remove_logger() reports whether anything was removed.
	 *
	 * @return void
	 */
	public function test_remove_logger(): void {
		register_logger( 'sugarcart', [ 'enabled' => true ] );

		$this->assertTrue( remove_logger( 'sugarcart' ) );
		$this->assertFalse( remove_logger( 'sugarcart' ) );
		$this->assertSame( [], get_all_loggers() );
	}

	/**
	 * Registering twice hands back the first logger.
	 *
	 * @return void
	 */
	public function test_register_logger_is_idempotent(): void {
		$first  = register_logger( 'sugarcart', [ 'enabled' => true ] );
		$second = register_logger( 'sugarcart', [ 'enabled' => false ] );

		$this->assertSame( $first, $second );
	}

}
