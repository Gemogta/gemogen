<?php
/**
 * Minimal WordPress REST API stubs for unit testing.
 *
 * Only loaded when running outside wp-env (i.e., WP classes are not available).
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_REST_Controller' ) ) {
	// @phpcs:ignore
	class WP_REST_Controller {
		protected string $namespace = '';
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	// @phpcs:ignore
	class WP_REST_Server {
		public const READABLE  = 'GET';
		public const CREATABLE = 'POST';
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	// @phpcs:ignore
	class WP_REST_Request {
		private array $params = [];

		public function set_param( string $key, mixed $value ): void {
			$this->params[ $key ] = $value;
		}

		public function get_param( string $key ): mixed {
			return $this->params[ $key ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	// @phpcs:ignore
	class WP_REST_Response {
		private mixed $data;
		private int $status;

		public function __construct( mixed $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		public function get_data(): mixed {
			return $this->data;
		}

		public function get_status(): int {
			return $this->status;
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	// @phpcs:ignore
	class WP_Error {
		private string $code;
		private string $message;
		private array $data;

		public function __construct( string $code = '', string $message = '', mixed $data = [] ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = is_array( $data ) ? $data : [];
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		public function get_error_data(): array {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( string $namespace, string $route, array $args ): void {
		// No-op stub for unit tests.
	}
}
