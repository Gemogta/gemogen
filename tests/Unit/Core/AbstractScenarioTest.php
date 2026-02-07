<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Core;

use Gemogen\Core\AbstractScenario;
use Gemogen\Core\Logger;
use PHPUnit\Framework\TestCase;

class AbstractScenarioTest extends TestCase {

	private AbstractScenario $scenario;

	protected function setUp(): void {
		$this->scenario = new class( new Logger() ) extends AbstractScenario {
			public function getId(): string {
				return 'test';
			}

			public function getName(): string {
				return 'Test Scenario';
			}

			public function getDescription(): string {
				return 'A test scenario.';
			}

			public function getSchema(): array {
				return [
					'properties' => [
						'count' => [ 'type' => 'integer', 'default' => 10 ],
						'name'  => [ 'type' => 'string', 'default' => 'hello' ],
						'flag'  => [ 'type' => 'boolean', 'default' => false ],
					],
				];
			}

			public function execute( array $config = [] ): array {
				return [];
			}

			public function rollback( array $createdIds ): void {
			}

			// Expose protected method for testing.
			public function publicMergeDefaults( array $config ): array {
				return $this->mergeDefaults( $config );
			}
		};
	}

	public function test_merge_defaults_fills_missing_values(): void {
		$result = $this->scenario->publicMergeDefaults( [] );

		$this->assertSame( 10, $result['count'] );
		$this->assertSame( 'hello', $result['name'] );
		$this->assertSame( false, $result['flag'] );
	}

	public function test_merge_defaults_preserves_provided_values(): void {
		$result = $this->scenario->publicMergeDefaults( [ 'count' => 5 ] );

		$this->assertSame( 5, $result['count'] );
		$this->assertSame( 'hello', $result['name'] );
	}

	public function test_validate_passes_for_correct_types(): void {
		$errors = $this->scenario->validate( [ 'count' => 5, 'name' => 'world', 'flag' => true ] );

		$this->assertEmpty( $errors );
	}

	public function test_validate_catches_wrong_type(): void {
		$errors = $this->scenario->validate( [ 'count' => 'not-an-int' ] );

		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'count', $errors[0] );
		$this->assertStringContainsString( 'integer', $errors[0] );
	}

	public function test_validate_catches_negative_integer(): void {
		$errors = $this->scenario->validate( [ 'count' => -5 ] );

		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'non-negative', $errors[0] );
	}

	public function test_validate_ignores_unknown_keys(): void {
		$errors = $this->scenario->validate( [ 'unknown_key' => 'whatever' ] );

		$this->assertEmpty( $errors );
	}
}
