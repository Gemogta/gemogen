<?php

declare(strict_types=1);

namespace Gemogen\Tests\Unit\REST;

use Gemogen\Contracts\ScenarioInterface;
use Gemogen\Core\Logger;
use Gemogen\Core\RunHistory;
use Gemogen\Core\ScenarioManager;
use Gemogen\REST\ScenarioController;
use PHPUnit\Framework\TestCase;

// Load WP class stubs for unit testing without WordPress.
require_once __DIR__ . '/wp-stubs.php';

/**
 * Testable subclass that overrides getManager() to avoid Plugin::container() dependency.
 */
class TestableScenarioController extends ScenarioController {

	private ScenarioManager $testManager;

	public function __construct( ScenarioManager $manager ) {
		$this->testManager = $manager;
	}

	protected function getManager(): ScenarioManager {
		return $this->testManager;
	}
}

/**
 * Stub scenario for testing REST controller responses.
 */
class StubScenario implements ScenarioInterface {

	public function getId(): string {
		return 'test-scenario';
	}

	public function getName(): string {
		return 'Test Scenario';
	}

	public function getDescription(): string {
		return 'A test scenario for unit tests.';
	}

	public function getSchema(): array {
		return [
			'posts' => [ 'type' => 'integer', 'default' => 5 ],
		];
	}

	public function execute( array $config = [] ): array {
		return [
			'posts' => [ 101, 102, 103 ],
		];
	}

	public function rollback( array $createdIds ): void {
		// No-op for testing.
	}
}

class ScenarioControllerTest extends TestCase {

	private TestableScenarioController $controller;
	private ScenarioManager $manager;

	protected function setUp(): void {
		$this->manager    = new ScenarioManager( new Logger(), new RunHistory() );
		$this->controller = new TestableScenarioController( $this->manager );
	}

	// -- Class structure tests --

	public function test_extends_base_controller(): void {
		$this->assertInstanceOf( ScenarioController::class, $this->controller );
	}

	public function test_namespace_is_gemogen_v1(): void {
		$reflection = new \ReflectionProperty( $this->controller, 'namespace' );
		$this->assertSame( 'gemogen/v1', $reflection->getValue( $this->controller ) );
	}

	public function test_has_all_callback_methods(): void {
		$expected = [
			'get_scenarios',
			'get_scenario',
			'execute_scenario',
			'rollback_scenario',
			'get_history',
			'reset_content',
			'get_status',
		];

		foreach ( $expected as $method ) {
			$this->assertTrue(
				method_exists( $this->controller, $method ),
				"Missing callback method: {$method}"
			);
		}
	}

	public function test_register_routes_method_exists(): void {
		$this->assertTrue( method_exists( $this->controller, 'register_routes' ) );
	}

	// -- GET /scenarios tests --

	public function test_get_scenarios_returns_empty_array_when_none_registered(): void {
		$request  = new \WP_REST_Request();
		$response = $this->controller->get_scenarios( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [], $response->get_data() );
	}

	public function test_get_scenarios_returns_scenario_list(): void {
		$this->manager->register( new StubScenario() );

		$request  = new \WP_REST_Request();
		$response = $this->controller->get_scenarios( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertSame( 'test-scenario', $data[0]['id'] );
		$this->assertSame( 'Test Scenario', $data[0]['name'] );
		$this->assertSame( 'A test scenario for unit tests.', $data[0]['description'] );
		$this->assertArrayHasKey( 'schema', $data[0] );
	}

	// -- GET /scenarios/<id> tests --

	public function test_get_scenario_returns_404_for_unknown_id(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'nonexistent' );

		$response = $this->controller->get_scenario( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'gemogen_not_found', $response->get_error_code() );
		$this->assertSame( 404, $response->get_error_data()['status'] );
	}

	public function test_get_scenario_returns_scenario_details(): void {
		$this->manager->register( new StubScenario() );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'test-scenario' );

		$response = $this->controller->get_scenario( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'test-scenario', $data['id'] );
		$this->assertSame( 'Test Scenario', $data['name'] );
		$this->assertArrayHasKey( 'schema', $data );
	}

	// -- POST /scenarios/<id>/execute tests --

	public function test_execute_returns_404_for_unknown_scenario(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'nonexistent' );

		$response = $this->controller->execute_scenario( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'gemogen_not_found', $response->get_error_code() );
	}

	public function test_execute_returns_201_on_success(): void {
		$this->manager->register( new StubScenario() );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'test-scenario' );
		$request->set_param( 'config', [] );

		$response = $this->controller->execute_scenario( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( 3, $data['total'] );
		$this->assertArrayHasKey( 'created_ids', $data );
		$this->assertArrayHasKey( 'message', $data );
	}

	public function test_execute_response_contains_created_ids(): void {
		$this->manager->register( new StubScenario() );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'test-scenario' );

		$response = $this->controller->execute_scenario( $request );
		$data     = $response->get_data();

		$this->assertSame( [ 101, 102, 103 ], $data['created_ids']['posts'] );
	}

	// -- POST /scenarios/<id>/rollback tests --

	public function test_rollback_returns_404_for_unknown_scenario(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'nonexistent' );
		$request->set_param( 'created_ids', [ 'posts' => [ 1 ] ] );

		$response = $this->controller->rollback_scenario( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'gemogen_not_found', $response->get_error_code() );
	}

	public function test_rollback_returns_400_for_empty_created_ids(): void {
		$this->manager->register( new StubScenario() );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'test-scenario' );
		$request->set_param( 'created_ids', [] );

		$response = $this->controller->rollback_scenario( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'gemogen_invalid_ids', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
	}

	public function test_rollback_returns_400_for_null_created_ids(): void {
		$this->manager->register( new StubScenario() );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'test-scenario' );
		// created_ids not set — will be null from get_param

		$response = $this->controller->rollback_scenario( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'gemogen_invalid_ids', $response->get_error_code() );
	}

	public function test_rollback_returns_200_on_success(): void {
		$this->manager->register( new StubScenario() );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 'test-scenario' );
		$request->set_param( 'created_ids', [ 'posts' => [ 101, 102 ] ] );

		$response = $this->controller->rollback_scenario( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( 2, $data['total'] );
		$this->assertStringContainsString( '2 items removed', $data['message'] );
	}

	// -- GET /history tests --

	public function test_get_history_returns_empty_array_without_wp(): void {
		$request  = new \WP_REST_Request();
		$response = $this->controller->get_history( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [], $response->get_data() );
	}

	// -- GET /status tests --

	public function test_get_status_returns_correct_structure(): void {
		$request  = new \WP_REST_Request();
		$response = $this->controller->get_status( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'version', $data );
		$this->assertArrayHasKey( 'scenario_count', $data );
		$this->assertArrayHasKey( 'total_runs', $data );
		$this->assertArrayHasKey( 'last_run', $data );
	}

	public function test_get_status_scenario_count_matches_registered(): void {
		$this->manager->register( new StubScenario() );

		$request  = new \WP_REST_Request();
		$response = $this->controller->get_status( $request );
		$data     = $response->get_data();

		$this->assertSame( 1, $data['scenario_count'] );
	}

	public function test_get_status_returns_null_last_run_without_history(): void {
		$request  = new \WP_REST_Request();
		$response = $this->controller->get_status( $request );
		$data     = $response->get_data();

		$this->assertNull( $data['last_run'] );
	}

	public function test_get_status_returns_version_fallback(): void {
		$request  = new \WP_REST_Request();
		$response = $this->controller->get_status( $request );
		$data     = $response->get_data();

		// GEMOGEN_VERSION not defined in unit tests — should fallback.
		$this->assertSame( '0.0.0', $data['version'] );
	}
}
