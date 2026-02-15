<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Sources;

use Gemogen\Contracts\ContentSourceInterface;
use Gemogen\Sources\UserTemplateSource;
use PHPUnit\Framework\TestCase;

class UserTemplateSourceTest extends TestCase {

	public function test_implements_content_source_interface(): void {
		$source = new UserTemplateSource();

		$this->assertInstanceOf( ContentSourceInterface::class, $source );
	}

	public function test_priority_is_fifty(): void {
		$source = new UserTemplateSource();

		$this->assertSame( 50, $source->getPriority() );
	}

	public function test_get_title_returns_null_without_wp(): void {
		// Without WP's get_option, the source returns null.
		$source = new UserTemplateSource();

		$this->assertNull( $source->getTitle() );
	}

	public function test_get_content_returns_null_without_wp(): void {
		$source = new UserTemplateSource();

		$this->assertNull( $source->getContent() );
	}

	public function test_get_field_returns_null_without_wp(): void {
		$source = new UserTemplateSource();

		$this->assertNull( $source->getField( 'first_name' ) );
	}

	public function test_supports_returns_false_without_wp(): void {
		$source = new UserTemplateSource();

		$this->assertFalse( $source->supports( 'first_name' ) );
	}

	public function test_save_does_not_throw_without_wp(): void {
		// save() uses function_exists guard — should not throw.
		UserTemplateSource::save( [ 'titles' => [ 'Test' ] ] );

		$this->assertTrue( true ); // No exception.
	}

	public function test_has_save_static_method(): void {
		$this->assertTrue( method_exists( UserTemplateSource::class, 'save' ) );

		$reflection = new \ReflectionMethod( UserTemplateSource::class, 'save' );
		$this->assertTrue( $reflection->isStatic() );
	}

	public function test_priority_is_between_builtin_and_file_import(): void {
		// BuiltInSource = 0, UserTemplateSource = 50, FileImportSource = 100.
		$source = new UserTemplateSource();
		$priority = $source->getPriority();

		$this->assertGreaterThan( 0, $priority );
		$this->assertLessThan( 100, $priority );
	}

	public function test_templates_cache_is_initially_null(): void {
		$reflection = new \ReflectionClass( UserTemplateSource::class );
		$property   = $reflection->getProperty( 'templates' );
		$property->setAccessible( true );

		$source = new UserTemplateSource();

		$this->assertNull( $property->getValue( $source ) );
	}

	public function test_get_title_populates_templates_cache(): void {
		$reflection = new \ReflectionClass( UserTemplateSource::class );
		$property   = $reflection->getProperty( 'templates' );
		$property->setAccessible( true );

		$source = new UserTemplateSource();
		$source->getTitle(); // Triggers cache population.

		// Without WP, templates will be empty array (not null).
		$this->assertIsArray( $property->getValue( $source ) );
	}
}
