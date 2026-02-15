<?php

declare(strict_types=1);

namespace Gemogen\Tests\Integration\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * Integration tests for REST API endpoints.
 *
 * Requires wp-env (Docker). Tests the actual REST API responses
 * with a real WordPress instance and database.
 */
class ScenarioControllerTest extends WP_UnitTestCase {

	private int $admin_id;

	protected function setUp(): void {
		parent::setUp();

		// Create an admin user for permission checks.
		$this->admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->admin_id );

		// Ensure REST API routes are registered.
		do_action( 'rest_api_init' );
	}

	protected function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	public function test_get_scenarios_returns_200(): void {
		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/scenarios' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_get_scenarios_returns_array(): void {
		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/scenarios' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
	}

	public function test_get_scenarios_contains_core_content(): void {
		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/scenarios' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$ids = array_column( $data, 'id' );
		$this->assertContains( 'core-content', $ids );
	}

	public function test_get_single_scenario_returns_details(): void {
		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/scenarios/core-content' );
		$request->set_url_params( [ 'id' => 'core-content' ] );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'core-content', $data['id'] );
		$this->assertArrayHasKey( 'schema', $data );
	}

	public function test_get_nonexistent_scenario_returns_404(): void {
		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/scenarios/nonexistent' );
		$request->set_url_params( [ 'id' => 'nonexistent' ] );
		$response = rest_do_request( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_execute_scenario_creates_content(): void {
		$request = new WP_REST_Request( 'POST', '/gemogen/v1/scenarios/core-content/execute' );
		$request->set_url_params( [ 'id' => 'core-content' ] );
		$request->set_body_params( [
			'config' => [
				'posts'      => 2,
				'pages'      => 0,
				'users'      => 0,
				'categories' => 0,
				'tags'       => 0,
				'comments'   => 0,
				'media'      => 0,
			],
		] );

		$response = rest_do_request( $request );

		$this->assertSame( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( 2, $data['total'] );
		$this->assertArrayHasKey( 'created_ids', $data );
	}

	public function test_rollback_scenario_removes_content(): void {
		// First create content.
		$manager = \Gemogen\Plugin::container()->get( 'scenario.manager' );
		$created = $manager->execute( 'core-content', [
			'posts'      => 2,
			'pages'      => 0,
			'users'      => 0,
			'categories' => 0,
			'tags'       => 0,
			'comments'   => 0,
			'media'      => 0,
		] );

		$request = new WP_REST_Request( 'POST', '/gemogen/v1/scenarios/core-content/rollback' );
		$request->set_url_params( [ 'id' => 'core-content' ] );
		$request->set_body_params( [ 'created_ids' => $created ] );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
	}

	public function test_get_history_returns_200(): void {
		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/history' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
	}

	public function test_get_status_returns_plugin_info(): void {
		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/status' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'version', $data );
		$this->assertArrayHasKey( 'scenario_count', $data );
		$this->assertArrayHasKey( 'total_runs', $data );
		$this->assertArrayHasKey( 'last_run', $data );
	}

	public function test_reset_clears_all_generated_content(): void {
		// Create some content first.
		$manager = \Gemogen\Plugin::container()->get( 'scenario.manager' );
		$created = $manager->execute( 'core-content', [
			'posts'      => 2,
			'pages'      => 0,
			'users'      => 0,
			'categories' => 0,
			'tags'       => 0,
			'comments'   => 0,
			'media'      => 0,
		] );

		$request  = new WP_REST_Request( 'POST', '/gemogen/v1/reset' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertGreaterThanOrEqual( 2, $data['total'] );

		// Verify posts are actually deleted.
		foreach ( $created['posts'] as $post_id ) {
			$this->assertNull( get_post( $post_id ) );
		}
	}

	public function test_unauthenticated_user_gets_403(): void {
		wp_set_current_user( 0 ); // Log out.

		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/scenarios' );
		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	public function test_non_admin_user_gets_403(): void {
		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );

		$request  = new WP_REST_Request( 'GET', '/gemogen/v1/scenarios' );
		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}
}
