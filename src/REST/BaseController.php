<?php

declare(strict_types=1);

namespace Gemogen\REST;

use Gemogen\Core\ScenarioManager;
use Gemogen\Plugin;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;

/**
 * Base class for Gemogen REST controllers.
 */
abstract class BaseController extends WP_REST_Controller {

	protected string $namespace = 'gemogen/v1';

	/**
	 * Get the scenario manager from the container.
	 */
	protected function getManager(): ScenarioManager {
		return Plugin::container()->get( 'scenario.manager' );
	}

	/**
	 * Permission check: require manage_options capability.
	 */
	public function check_permissions( WP_REST_Request $request ): bool|WP_Error {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'gemogen_rest_forbidden',
			'You do not have permission to access Gemogen.',
			[ 'status' => 403 ]
		);
	}
}
