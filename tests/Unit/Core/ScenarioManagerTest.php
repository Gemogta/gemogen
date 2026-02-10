<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\Core;

use Gemogen\Contracts\ScenarioInterface;
use Gemogen\Core\Logger;
use Gemogen\Core\RunHistory;
use Gemogen\Core\ScenarioManager;
use PHPUnit\Framework\TestCase;

class ScenarioManagerTest extends TestCase {

	private ScenarioManager $manager;

	protected function setUp(): void {
		$this->manager = new ScenarioManager( new Logger(), new RunHistory() );
	}

	public function test_register_and_get(): void {
		$scenario = $this->createScenario( 'test-scenario' );

		$this->manager->register( $scenario );

		$this->assertSame( $scenario, $this->manager->get( 'test-scenario' ) );
	}

	public function test_get_returns_null_for_unknown(): void {
		$this->assertNull( $this->manager->get( 'nonexistent' ) );
	}

	public function test_all_returns_registered_scenarios(): void {
		$s1 = $this->createScenario( 'first' );
		$s2 = $this->createScenario( 'second' );

		$this->manager->register( $s1 );
		$this->manager->register( $s2 );

		$all = $this->manager->all();
		$this->assertCount( 2, $all );
		$this->assertArrayHasKey( 'first', $all );
		$this->assertArrayHasKey( 'second', $all );
	}

	public function test_execute_calls_scenario_execute(): void {
		$scenario = $this->createMock( ScenarioInterface::class );
		$scenario->method( 'getId' )->willReturn( 'mock' );
		$scenario->method( 'execute' )
			->with( [ 'count' => 5 ] )
			->willReturn( [ 'posts' => [ 1, 2, 3, 4, 5 ] ] );

		$this->manager->register( $scenario );

		$result = $this->manager->execute( 'mock', [ 'count' => 5 ] );

		$this->assertSame( [ 'posts' => [ 1, 2, 3, 4, 5 ] ], $result );
	}

	public function test_execute_throws_on_unknown_scenario(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Scenario not found: unknown' );

		$this->manager->execute( 'unknown' );
	}

	public function test_rollback_calls_scenario_rollback(): void {
		$ids = [ 'posts' => [ 1, 2 ] ];

		$scenario = $this->createMock( ScenarioInterface::class );
		$scenario->method( 'getId' )->willReturn( 'rollbackable' );
		$scenario->expects( $this->once() )
			->method( 'rollback' )
			->with( $ids );

		$this->manager->register( $scenario );
		$this->manager->rollback( 'rollbackable', $ids );
	}

	public function test_rollback_throws_on_unknown_scenario(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Scenario not found: unknown' );

		$this->manager->rollback( 'unknown', [] );
	}

	public function test_register_overwrites_same_id(): void {
		$first  = $this->createScenario( 'same-id' );
		$second = $this->createScenario( 'same-id' );

		$this->manager->register( $first );
		$this->manager->register( $second );

		$this->assertSame( $second, $this->manager->get( 'same-id' ) );
		$this->assertCount( 1, $this->manager->all() );
	}

	private function createScenario( string $id ): ScenarioInterface {
		$scenario = $this->createMock( ScenarioInterface::class );
		$scenario->method( 'getId' )->willReturn( $id );
		return $scenario;
	}
}
