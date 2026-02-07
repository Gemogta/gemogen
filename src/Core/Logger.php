<?php

declare(strict_types=1);

namespace Gemogen\Core;

class Logger {

	/**
	 * Log an informational message.
	 */
	public function info( string $message ): void {
		$this->log( 'info', $message );
	}

	/**
	 * Log an error message.
	 */
	public function error( string $message ): void {
		$this->log( 'error', $message );
	}

	/**
	 * Log a warning message.
	 */
	public function warning( string $message ): void {
		$this->log( 'warning', $message );
	}

	private function log( string $level, string $message ): void {
		$formatted = sprintf( '[Gemogen][%s] %s', $level, $message );

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			match ( $level ) {
				'error'   => \WP_CLI::error( $message, false ),
				'warning' => \WP_CLI::warning( $message ),
				default   => \WP_CLI::log( $message ),
			};
			return;
		}

		error_log( $formatted );
	}
}
