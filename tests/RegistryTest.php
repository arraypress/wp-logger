<?php
/**
 * Registry tests.
 *
 * @package ArrayPress\Logger
 */

declare( strict_types=1 );

namespace ArrayPress\Logger\Tests;

use ArrayPress\Logger\Logger;
use ArrayPress\Logger\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Covers Registry.
 */
class RegistryTest extends TestCase {

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
	 * Leave nothing behind on disk or in the singleton.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Registry::reset();
		$this->rmdir( $GLOBALS['logger_uploads'] );
		parent::tearDown();
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
	 * get_instance() returns the same object every time.
	 *
	 * @return void
	 */
	public function test_is_a_singleton(): void {
		$this->assertSame( Registry::get_instance(), Registry::get_instance() );
	}

	/**
	 * reset() replaces the instance rather than reusing it.
	 *
	 * @return void
	 */
	public function test_reset_discards_the_instance(): void {
		$first = Registry::get_instance();
		Registry::reset();

		$this->assertNotSame( $first, Registry::get_instance() );
	}

	/**
	 * A registered logger comes back by name.
	 *
	 * @return void
	 */
	public function test_register_then_get(): void {
		$registry = Registry::get_instance();
		$logger   = $registry->register( 'sugarcart' );

		$this->assertInstanceOf( Logger::class, $logger );
		$this->assertSame( $logger, $registry->get( 'sugarcart' ) );
		$this->assertTrue( $registry->has( 'sugarcart' ) );
	}

	/**
	 * Registering the same name twice returns the first instance.
	 *
	 * The second call's options are therefore ignored, which is what makes it
	 * safe to call register_logger() from more than one entry point.
	 *
	 * @return void
	 */
	public function test_register_is_idempotent(): void {
		$registry = Registry::get_instance();

		$first  = $registry->register( 'sugarcart', [ 'enabled' => true ] );
		$second = $registry->register( 'sugarcart', [ 'enabled' => false ] );

		$this->assertSame( $first, $second );
		$this->assertTrue( $second->is_enabled() );
		$this->assertSame( 1, $registry->count() );
	}

	/**
	 * The name is reduced to a key, so lookups agree with registration.
	 *
	 * @return void
	 */
	public function test_name_is_sanitised_on_register(): void {
		$registry = Registry::get_instance();
		$registry->register( 'Sugar Cart!' );

		$this->assertTrue( $registry->has( 'sugarcart' ) );
		$this->assertSame( [ 'sugarcart' ], $registry->get_names() );
	}

	/**
	 * An unregistered name yields null, not an error.
	 *
	 * @return void
	 */
	public function test_get_missing_returns_null(): void {
		$registry = Registry::get_instance();

		$this->assertNull( $registry->get( 'nothing' ) );
		$this->assertFalse( $registry->has( 'nothing' ) );
	}

	/**
	 * remove() reports whether anything was removed.
	 *
	 * @return void
	 */
	public function test_remove(): void {
		$registry = Registry::get_instance();
		$registry->register( 'sugarcart' );

		$this->assertTrue( $registry->remove( 'sugarcart' ) );
		$this->assertFalse( $registry->has( 'sugarcart' ) );
		$this->assertFalse( $registry->remove( 'sugarcart' ) );
	}

	/**
	 * get_all() is keyed by name, and count() agrees with it.
	 *
	 * @return void
	 */
	public function test_get_all_is_keyed_by_name(): void {
		$registry = Registry::get_instance();
		$registry->register( 'one' );
		$registry->register( 'two' );

		$all = $registry->get_all();

		$this->assertSame( [ 'one', 'two' ], array_keys( $all ) );
		$this->assertSame( $registry->count(), count( $all ) );
		$this->assertSame( $registry->get_names(), array_keys( $all ) );
	}

	/**
	 * clear() empties the registry and returns itself for chaining.
	 *
	 * @return void
	 */
	public function test_clear_empties_the_registry(): void {
		$registry = Registry::get_instance();
		$registry->register( 'one' );

		$this->assertSame( $registry, $registry->clear() );
		$this->assertSame( 0, $registry->count() );
		$this->assertSame( [], $registry->get_all() );
	}

	/**
	 * An empty registry reports nothing.
	 *
	 * @return void
	 */
	public function test_empty_registry(): void {
		$registry = Registry::get_instance();

		$this->assertSame( 0, $registry->count() );
		$this->assertSame( [], $registry->get_names() );
		$this->assertSame( [], $registry->get_all() );
	}

	/**
	 * The singleton refuses to be rebuilt by unserializing.
	 *
	 * @return void
	 */
	public function test_cannot_be_unserialized(): void {
		$this->expectException( \Exception::class );

		Registry::get_instance()->__wakeup();
	}

	/**
	 * Two names produce two distinct loggers writing to distinct files.
	 *
	 * @return void
	 */
	public function test_loggers_are_independent(): void {
		$registry = Registry::get_instance();

		$a = $registry->register( 'one', [ 'enabled' => true ] );
		$b = $registry->register( 'two', [ 'enabled' => true ] );

		$this->assertNotSame( $a, $b );
		$this->assertNotSame( $a->get_file(), $b->get_file() );

		$a->info( 'only in one' );

		$this->assertStringContainsString( 'only in one', $a->get_contents() );
		$this->assertSame( '', $b->get_contents() );
	}

}
