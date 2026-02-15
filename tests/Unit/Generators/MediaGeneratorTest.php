<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Generators;

use Gemogen\Contracts\GeneratorInterface;
use Gemogen\Generators\MediaGenerator;
use PHPUnit\Framework\TestCase;

class MediaGeneratorTest extends TestCase {

	public function test_implements_generator_interface(): void {
		$generator = new MediaGenerator();

		$this->assertInstanceOf( GeneratorInterface::class, $generator );
	}

	public function test_has_generate_method(): void {
		$this->assertTrue( method_exists( MediaGenerator::class, 'generate' ) );
	}

	public function test_has_delete_method(): void {
		$this->assertTrue( method_exists( MediaGenerator::class, 'delete' ) );
	}

	public function test_generate_accepts_empty_params(): void {
		$reflection = new \ReflectionMethod( MediaGenerator::class, 'generate' );
		$params     = $reflection->getParameters();

		$this->assertCount( 1, $params );
		$this->assertTrue( $params[0]->isDefaultValueAvailable() );
		$this->assertSame( [], $params[0]->getDefaultValue() );
	}

	public function test_generate_returns_int(): void {
		$reflection = new \ReflectionMethod( MediaGenerator::class, 'generate' );
		$returnType = $reflection->getReturnType();

		$this->assertNotNull( $returnType );
		$this->assertSame( 'int', $returnType->getName() );
	}

	public function test_delete_accepts_int_parameter(): void {
		$reflection = new \ReflectionMethod( MediaGenerator::class, 'delete' );
		$params     = $reflection->getParameters();

		$this->assertCount( 1, $params );
		$this->assertSame( 'int', $params[0]->getType()->getName() );
	}

	public function test_does_not_require_content_pool(): void {
		// MediaGenerator has no constructor parameters (unlike other generators).
		$reflection  = new \ReflectionClass( MediaGenerator::class );
		$constructor = $reflection->getConstructor();

		$this->assertNull( $constructor );
	}

	public function test_has_private_random_color_method(): void {
		$reflection = new \ReflectionClass( MediaGenerator::class );
		$method     = $reflection->getMethod( 'randomColor' );

		$this->assertTrue( $method->isPrivate() );
	}

	public function test_random_color_returns_valid_hex(): void {
		$reflection = new \ReflectionClass( MediaGenerator::class );
		$method     = $reflection->getMethod( 'randomColor' );
		$method->setAccessible( true );

		$generator = new MediaGenerator();
		$color     = $method->invoke( $generator );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{6}$/', $color );
	}

	public function test_random_color_returns_from_known_set(): void {
		$knownColors = [ '3498db', 'e74c3c', '2ecc71', '9b59b6', 'f39c12', '1abc9c', 'e67e22', '34495e' ];

		$reflection = new \ReflectionClass( MediaGenerator::class );
		$method     = $reflection->getMethod( 'randomColor' );
		$method->setAccessible( true );

		$generator = new MediaGenerator();

		// Call multiple times to increase confidence.
		for ( $i = 0; $i < 20; $i++ ) {
			$color = $method->invoke( $generator );
			$this->assertContains( $color, $knownColors );
		}
	}
}
