<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Scenarios;

use Gemogen\Contracts\GeneratorInterface;
use Gemogen\Contracts\ScenarioInterface;
use Gemogen\Core\AbstractScenario;
use Gemogen\Core\ContentPool;
use Gemogen\Core\Logger;
use Gemogen\Generators\CommentGenerator;
use Gemogen\Generators\MediaGenerator;
use Gemogen\Generators\PostGenerator;
use Gemogen\Generators\TaxonomyGenerator;
use Gemogen\Generators\UserGenerator;
use Gemogen\Scenarios\CoreContentScenario;
use PHPUnit\Framework\TestCase;

class CoreContentScenarioTest extends TestCase {

	private CoreContentScenario $scenario;

	protected function setUp(): void {
		$pool = new ContentPool();

		$this->scenario = new CoreContentScenario(
			new Logger(),
			new PostGenerator( $pool ),
			new UserGenerator( $pool ),
			new TaxonomyGenerator( $pool ),
			new CommentGenerator( $pool ),
			new MediaGenerator(),
		);
	}

	public function test_implements_scenario_interface(): void {
		$this->assertInstanceOf( ScenarioInterface::class, $this->scenario );
	}

	public function test_extends_abstract_scenario(): void {
		$this->assertInstanceOf( AbstractScenario::class, $this->scenario );
	}

	public function test_id_is_core_content(): void {
		$this->assertSame( 'core-content', $this->scenario->getId() );
	}

	public function test_name_is_wordpress_core_content(): void {
		$this->assertSame( 'WordPress Core Content', $this->scenario->getName() );
	}

	public function test_description_is_not_empty(): void {
		$this->assertNotEmpty( $this->scenario->getDescription() );
	}

	public function test_description_mentions_content_types(): void {
		$description = $this->scenario->getDescription();

		$this->assertStringContainsString( 'posts', $description );
		$this->assertStringContainsString( 'pages', $description );
		$this->assertStringContainsString( 'categories', $description );
		$this->assertStringContainsString( 'tags', $description );
		$this->assertStringContainsString( 'users', $description );
		$this->assertStringContainsString( 'comments', $description );
		$this->assertStringContainsString( 'media', $description );
	}

	public function test_schema_has_properties(): void {
		$schema = $this->scenario->getSchema();

		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertIsArray( $schema['properties'] );
	}

	public function test_schema_has_all_expected_fields(): void {
		$schema     = $this->scenario->getSchema();
		$properties = $schema['properties'];

		$expectedFields = [ 'posts', 'pages', 'users', 'categories', 'tags', 'comments', 'media' ];

		foreach ( $expectedFields as $field ) {
			$this->assertArrayHasKey( $field, $properties, "Missing schema field: {$field}" );
		}
	}

	public function test_schema_fields_are_all_integers(): void {
		$schema     = $this->scenario->getSchema();
		$properties = $schema['properties'];

		foreach ( $properties as $key => $definition ) {
			$this->assertSame( 'integer', $definition['type'], "Field '{$key}' should be integer type" );
		}
	}

	public function test_schema_fields_have_defaults(): void {
		$schema     = $this->scenario->getSchema();
		$properties = $schema['properties'];

		foreach ( $properties as $key => $definition ) {
			$this->assertArrayHasKey( 'default', $definition, "Field '{$key}' should have a default value" );
			$this->assertIsInt( $definition['default'], "Field '{$key}' default should be an integer" );
			$this->assertGreaterThan( 0, $definition['default'], "Field '{$key}' default should be positive" );
		}
	}

	public function test_schema_fields_have_descriptions(): void {
		$schema     = $this->scenario->getSchema();
		$properties = $schema['properties'];

		foreach ( $properties as $key => $definition ) {
			$this->assertArrayHasKey( 'description', $definition, "Field '{$key}' should have a description" );
			$this->assertNotEmpty( $definition['description'], "Field '{$key}' description should not be empty" );
		}
	}

	public function test_schema_default_values(): void {
		$schema   = $this->scenario->getSchema();
		$expected = [
			'posts'      => 10,
			'pages'      => 5,
			'users'      => 3,
			'categories' => 5,
			'tags'       => 10,
			'comments'   => 20,
			'media'      => 5,
		];

		foreach ( $expected as $key => $value ) {
			$this->assertSame(
				$value,
				$schema['properties'][ $key ]['default'],
				"Default for '{$key}' should be {$value}"
			);
		}
	}

	public function test_validate_accepts_valid_config(): void {
		$errors = $this->scenario->validate( [ 'posts' => 5, 'pages' => 2 ] );

		$this->assertEmpty( $errors );
	}

	public function test_validate_rejects_negative_values(): void {
		$errors = $this->scenario->validate( [ 'posts' => -1 ] );

		$this->assertNotEmpty( $errors );
	}

	public function test_validate_rejects_wrong_type(): void {
		$errors = $this->scenario->validate( [ 'posts' => 'five' ] );

		$this->assertNotEmpty( $errors );
	}

	public function test_validate_accepts_empty_config(): void {
		$errors = $this->scenario->validate( [] );

		$this->assertEmpty( $errors );
	}

	public function test_validate_accepts_zero_values(): void {
		$errors = $this->scenario->validate( [ 'posts' => 0, 'pages' => 0 ] );

		$this->assertEmpty( $errors );
	}

	public function test_has_execute_method(): void {
		$this->assertTrue( method_exists( $this->scenario, 'execute' ) );
	}

	public function test_has_rollback_method(): void {
		$this->assertTrue( method_exists( $this->scenario, 'rollback' ) );
	}

	public function test_constructor_signature(): void {
		$reflection  = new \ReflectionClass( CoreContentScenario::class );
		$constructor = $reflection->getConstructor();
		$params      = $constructor->getParameters();

		$this->assertCount( 6, $params );
		$this->assertSame( 'logger', $params[0]->getName() );
		$this->assertSame( 'posts', $params[1]->getName() );
		$this->assertSame( 'users', $params[2]->getName() );
		$this->assertSame( 'taxonomies', $params[3]->getName() );
		$this->assertSame( 'comments', $params[4]->getName() );
		$this->assertSame( 'media', $params[5]->getName() );
	}
}
