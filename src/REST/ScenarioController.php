<?php

declare(strict_types=1);

namespace Gemogen\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for scenario operations.
 */
class ScenarioController extends BaseController {

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {

		// GET /gemogen/v1/scenarios
		register_rest_route(
			$this->namespace,
			'/scenarios',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_scenarios' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// GET /gemogen/v1/scenarios/<id>
		register_rest_route(
			$this->namespace,
			'/scenarios/(?P<id>[a-zA-Z0-9_-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_scenario' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// POST /gemogen/v1/scenarios/<id>/execute
		register_rest_route(
			$this->namespace,
			'/scenarios/(?P<id>[a-zA-Z0-9_-]+)/execute',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'execute_scenario' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'config' => [
						'type'              => 'object',
						'default'           => [],
						'validate_callback' => function ( $value ): bool {
							return is_array( $value ) || is_object( $value );
						},
					],
				],
			]
		);

		// POST /gemogen/v1/scenarios/<id>/rollback
		register_rest_route(
			$this->namespace,
			'/scenarios/(?P<id>[a-zA-Z0-9_-]+)/rollback',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rollback_scenario' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'created_ids' => [
						'required'          => true,
						'type'              => 'object',
						'validate_callback' => function ( $value ): bool {
							return ( is_array( $value ) || is_object( $value ) ) && ! empty( $value );
						},
					],
				],
			]
		);

		// GET /gemogen/v1/history
		register_rest_route(
			$this->namespace,
			'/history',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_history' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// POST /gemogen/v1/reset
		register_rest_route(
			$this->namespace,
			'/reset',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'reset_content' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		// GET /gemogen/v1/status
		register_rest_route(
			$this->namespace,
			'/status',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);
	}

	/**
	 * GET /scenarios — List all scenarios with schemas.
	 */
	public function get_scenarios( WP_REST_Request $request ): WP_REST_Response {
		$manager   = $this->getManager();
		$scenarios = [];

		foreach ( $manager->all() as $scenario ) {
			$scenarios[] = [
				'id'          => $scenario->getId(),
				'name'        => $scenario->getName(),
				'description' => $scenario->getDescription(),
				'schema'      => $scenario->getSchema(),
			];
		}

		return new WP_REST_Response( $scenarios, 200 );
	}

	/**
	 * GET /scenarios/<id> — Single scenario details.
	 */
	public function get_scenario( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$manager  = $this->getManager();
		$scenario = $manager->get( $request->get_param( 'id' ) );

		if ( $scenario === null ) {
			return new WP_Error(
				'gemogen_not_found',
				'Scenario not found.',
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response(
			[
				'id'          => $scenario->getId(),
				'name'        => $scenario->getName(),
				'description' => $scenario->getDescription(),
				'schema'      => $scenario->getSchema(),
			],
			200
		);
	}

	/**
	 * POST /scenarios/<id>/execute — Run a scenario.
	 */
	public function execute_scenario( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$manager  = $this->getManager();
		$id       = $request->get_param( 'id' );
		$config   = $request->get_param( 'config' ) ?? [];
		$scenario = $manager->get( $id );

		if ( $scenario === null ) {
			return new WP_Error(
				'gemogen_not_found',
				'Scenario not found.',
				[ 'status' => 404 ]
			);
		}

		try {
			$created_ids = $manager->execute( $id, (array) $config );
		} catch ( \RuntimeException $e ) {
			return new WP_Error(
				'gemogen_validation_error',
				$e->getMessage(),
				[ 'status' => 400 ]
			);
		} catch ( \Throwable $e ) {
			return new WP_Error(
				'gemogen_execution_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}

		$total = array_sum( array_map( 'count', $created_ids ) );

		return new WP_REST_Response(
			[
				'success'     => true,
				'created_ids' => $created_ids,
				'total'       => $total,
				'message'     => sprintf( '%d items created.', $total ),
			],
			201
		);
	}

	/**
	 * POST /scenarios/<id>/rollback — Rollback created content.
	 */
	public function rollback_scenario( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$manager     = $this->getManager();
		$id          = $request->get_param( 'id' );
		$created_ids = $request->get_param( 'created_ids' );
		$scenario    = $manager->get( $id );

		if ( $scenario === null ) {
			return new WP_Error(
				'gemogen_not_found',
				'Scenario not found.',
				[ 'status' => 404 ]
			);
		}

		if ( ! is_array( $created_ids ) || empty( $created_ids ) ) {
			return new WP_Error(
				'gemogen_invalid_ids',
				'created_ids must be a non-empty object.',
				[ 'status' => 400 ]
			);
		}

		try {
			$manager->rollback( $id, $created_ids );
		} catch ( \Throwable $e ) {
			return new WP_Error(
				'gemogen_rollback_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}

		$total = array_sum( array_map( 'count', $created_ids ) );

		return new WP_REST_Response(
			[
				'success' => true,
				'total'   => $total,
				'message' => sprintf( '%d items removed.', $total ),
			],
			200
		);
	}

	/**
	 * GET /history — Recent run history.
	 */
	public function get_history( WP_REST_Request $request ): WP_REST_Response {
		$history = $this->getManager()->getHistory();
		$runs    = $history->getAll();

		$items = [];

		foreach ( array_reverse( $runs ) as $run ) {
			$items[] = [
				'scenario_id' => $run['scenario_id'],
				'config'      => $run['config'],
				'created_ids' => $run['created_ids'],
				'total'       => $run['total'],
				'timestamp'   => $run['timestamp'],
				'date'        => wp_date( 'Y-m-d H:i:s', $run['timestamp'] ),
			];
		}

		return new WP_REST_Response( $items, 200 );
	}

	/**
	 * POST /reset — Remove all Gemogen-generated content.
	 */
	public function reset_content( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$counts = [
			'posts'    => 0,
			'users'    => 0,
			'terms'    => 0,
			'comments' => 0,
		];

		// Delete posts (including pages, attachments, CPTs).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$post_ids = $wpdb->get_col(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_gemogen_generated' AND meta_value = '1'"
		);

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
			$counts['posts']++;
		}

		// Delete users.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$user_ids = $wpdb->get_col(
			"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_gemogen_generated' AND meta_value = '1'"
		);

		if ( ! empty( $user_ids ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';

			foreach ( $user_ids as $user_id ) {
				wp_delete_user( (int) $user_id );
				$counts['users']++;
			}
		}

		// Delete terms.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$term_rows = $wpdb->get_results(
			"SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = '_gemogen_generated' AND meta_value = '1'"
		);

		foreach ( $term_rows as $row ) {
			$term = get_term( (int) $row->term_id );

			if ( $term && ! is_wp_error( $term ) ) {
				wp_delete_term( (int) $row->term_id, $term->taxonomy );
				$counts['terms']++;
			}
		}

		// Delete comments.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$comment_ids = $wpdb->get_col(
			"SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key = '_gemogen_generated' AND meta_value = '1'"
		);

		foreach ( $comment_ids as $comment_id ) {
			wp_delete_comment( (int) $comment_id, true );
			$counts['comments']++;
		}

		// Clear run history.
		$this->getManager()->getHistory()->clear();

		$total = array_sum( $counts );

		return new WP_REST_Response(
			[
				'success' => true,
				'counts'  => $counts,
				'total'   => $total,
				'message' => $total > 0
					? sprintf( 'Reset complete. Removed %d items.', $total )
					: 'No Gemogen-generated content found.',
			],
			200
		);
	}

	/**
	 * GET /status — Plugin status.
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		$manager = $this->getManager();
		$history = $manager->getHistory();
		$last    = $history->getLast();

		return new WP_REST_Response(
			[
				'version'        => defined( 'GEMOGEN_VERSION' ) ? GEMOGEN_VERSION : '0.0.0',
				'scenario_count' => count( $manager->all() ),
				'total_runs'     => count( $history->getAll() ),
				'last_run'       => $last ? [
					'scenario_id' => $last['scenario_id'],
					'total'       => $last['total'],
					'date'        => wp_date( 'Y-m-d H:i:s', $last['timestamp'] ),
				] : null,
			],
			200
		);
	}
}
