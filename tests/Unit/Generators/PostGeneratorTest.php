<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Generators;

use Gemogen\Contracts\GeneratorInterface;
use Gemogen\Core\ContentPool;
use Gemogen\Generators\PostGenerator;
use PHPUnit\Framework\TestCase;

class PostGeneratorTest extends TestCase {

	public function test_implements_generator_interface(): void {
		$pool      = new ContentPool();
		$generator = new PostGenerator( $pool );

		$this->assertInstanceOf( GeneratorInterface::class, $generator );
	}

	public function test_has_generate_method(): void {
		$this->assertTrue( method_exists( PostGenerator::class, 'generate' ) );
	}

	public function test_has_delete_method(): void {
		$this->assertTrue( method_exists( PostGenerator::class, 'delete' ) );
	}

	public function test_generate_accepts_empty_params(): void {
		$reflection = new \ReflectionMethod( PostGenerator::class, 'generate' );
		$params     = $reflection->getParameters();

		$this->assertCount( 1, $params );
		$this->assertTrue( $params[0]->isDefaultValueAvailable() );
		$this->assertSame( [], $params[0]->getDefaultValue() );
	}

	public function test_generate_returns_int(): void {
		$reflection = new \ReflectionMethod( PostGenerator::class, 'generate' );
		$returnType = $reflection->getReturnType();

		$this->assertNotNull( $returnType );
		$this->assertSame( 'int', $returnType->getName() );
	}

	public function test_delete_accepts_int_parameter(): void {
		$reflection = new \ReflectionMethod( PostGenerator::class, 'delete' );
		$params     = $reflection->getParameters();

		$this->assertCount( 1, $params );
		$this->assertSame( 'int', $params[0]->getType()->getName() );
	}

	public function test_delete_returns_void(): void {
		$reflection = new \ReflectionMethod( PostGenerator::class, 'delete' );
		$returnType = $reflection->getReturnType();

		$this->assertNotNull( $returnType );
		$this->assertSame( 'void', $returnType->getName() );
	}

	public function test_constructor_requires_content_pool(): void {
		$reflection = new \ReflectionClass( PostGenerator::class );
		$constructor = $reflection->getConstructor();
		$params     = $constructor->getParameters();

		$this->assertCount( 1, $params );
		$this->assertSame( ContentPool::class, $params[0]->getType()->getName() );
	}

	public function test_stores_pool_as_private_property(): void {
		$reflection = new \ReflectionClass( PostGenerator::class );
		$property   = $reflection->getProperty( 'pool' );

		$this->assertTrue( $property->isPrivate() );
	}
}
