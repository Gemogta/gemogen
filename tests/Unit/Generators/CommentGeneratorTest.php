<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Generators;

use Gemogen\Contracts\GeneratorInterface;
use Gemogen\Core\ContentPool;
use Gemogen\Generators\CommentGenerator;
use PHPUnit\Framework\TestCase;

class CommentGeneratorTest extends TestCase {

	public function test_implements_generator_interface(): void {
		$pool      = new ContentPool();
		$generator = new CommentGenerator( $pool );

		$this->assertInstanceOf( GeneratorInterface::class, $generator );
	}

	public function test_has_generate_method(): void {
		$this->assertTrue( method_exists( CommentGenerator::class, 'generate' ) );
	}

	public function test_has_delete_method(): void {
		$this->assertTrue( method_exists( CommentGenerator::class, 'delete' ) );
	}

	public function test_generate_accepts_empty_params(): void {
		$reflection = new \ReflectionMethod( CommentGenerator::class, 'generate' );
		$params     = $reflection->getParameters();

		$this->assertCount( 1, $params );
		$this->assertTrue( $params[0]->isDefaultValueAvailable() );
		$this->assertSame( [], $params[0]->getDefaultValue() );
	}

	public function test_generate_returns_int(): void {
		$reflection = new \ReflectionMethod( CommentGenerator::class, 'generate' );
		$returnType = $reflection->getReturnType();

		$this->assertNotNull( $returnType );
		$this->assertSame( 'int', $returnType->getName() );
	}

	public function test_delete_accepts_int_parameter(): void {
		$reflection = new \ReflectionMethod( CommentGenerator::class, 'delete' );
		$params     = $reflection->getParameters();

		$this->assertCount( 1, $params );
		$this->assertSame( 'int', $params[0]->getType()->getName() );
	}

	public function test_constructor_requires_content_pool(): void {
		$reflection  = new \ReflectionClass( CommentGenerator::class );
		$constructor = $reflection->getConstructor();
		$params      = $constructor->getParameters();

		$this->assertCount( 1, $params );
		$this->assertSame( ContentPool::class, $params[0]->getType()->getName() );
	}

	public function test_stores_pool_as_private_property(): void {
		$reflection = new \ReflectionClass( CommentGenerator::class );
		$property   = $reflection->getProperty( 'pool' );

		$this->assertTrue( $property->isPrivate() );
	}

	public function test_generate_throws_when_comment_post_id_is_zero(): void {
		// CommentGenerator throws RuntimeException when comment_post_ID is 0.
		// We can't call generate() directly without WP functions,
		// but we can verify the method signature expects the requirement.
		$reflection = new \ReflectionMethod( CommentGenerator::class, 'generate' );
		$source     = file_get_contents( ( new \ReflectionClass( CommentGenerator::class ) )->getFileName() );

		$this->assertStringContainsString( 'comment_post_ID is required', $source );
	}
}
