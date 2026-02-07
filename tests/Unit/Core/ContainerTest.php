<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Core;

use Gemogen\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {

	private Container $container;

	protected function setUp(): void {
		$this->container = new Container();
	}

	public function test_set_and_get(): void {
		$this->container->set( 'greeting', fn() => 'hello' );

		$this->assertSame( 'hello', $this->container->get( 'greeting' ) );
	}

	public function test_has_returns_true_for_registered_service(): void {
		$this->container->set( 'foo', fn() => 'bar' );

		$this->assertTrue( $this->container->has( 'foo' ) );
	}

	public function test_has_returns_false_for_unknown_service(): void {
		$this->assertFalse( $this->container->has( 'unknown' ) );
	}

	public function test_get_returns_singleton(): void {
		$this->container->set( 'counter', fn() => new \stdClass() );

		$first = $this->container->get( 'counter' );
		$second = $this->container->get( 'counter' );

		$this->assertSame( $first, $second );
	}

	public function test_get_throws_on_unknown_service(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Service not found: missing' );

		$this->container->get( 'missing' );
	}

	public function test_set_resets_cached_instance(): void {
		$this->container->set( 'value', fn() => 'first' );
		$this->assertSame( 'first', $this->container->get( 'value' ) );

		$this->container->set( 'value', fn() => 'second' );
		$this->assertSame( 'second', $this->container->get( 'value' ) );
	}

	public function test_factory_receives_container(): void {
		$this->container->set( 'dep', fn() => 42 );
		$this->container->set( 'service', fn( Container $c ) => 'value-' . $c->get( 'dep' ) );

		$this->assertSame( 'value-42', $this->container->get( 'service' ) );
	}
}
