<?php

declare(strict_types=1);

namespace Gemogen\CLI;

use Gemogen\Container;
use Gemogen\Core\ScenarioManager;
use Gemogen\Plugin;
use WP_CLI;
use WP_CLI_Command;

/**
 * Base class for Gemogen CLI commands.
 */
abstract class BaseCommand extends WP_CLI_Command {

	protected function getManager(): ScenarioManager {
		return Plugin::container()->get( 'scenario.manager' );
	}

	protected function getContainer(): Container {
		return Plugin::container();
	}

	/**
	 * Parse a JSON string from a CLI argument, with error handling.
	 *
	 * @return array<string, mixed>
	 */
	protected function parseJson( string $json, string $argName ): array {
		$data = json_decode( $json, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			WP_CLI::error( sprintf( 'Invalid JSON in --%s: %s', $argName, json_last_error_msg() ) );
		}

		if ( ! is_array( $data ) ) {
			WP_CLI::error( sprintf( '--%s must be a JSON object or array.', $argName ) );
		}

		return $data;
	}

	/**
	 * Output a summary table of created items.
	 *
	 * @param array<string, int[]> $createdIds
	 */
	protected function printSummary( array $createdIds ): void {
		$rows = [];

		foreach ( $createdIds as $type => $ids ) {
			if ( empty( $ids ) ) {
				continue;
			}

			$rows[] = [
				'Type'  => $type,
				'Count' => count( $ids ),
				'IDs'   => implode( ', ', array_slice( $ids, 0, 10 ) ) . ( count( $ids ) > 10 ? '...' : '' ),
			];
		}

		if ( empty( $rows ) ) {
			WP_CLI::warning( 'No items were created.' );
			return;
		}

		WP_CLI\Utils\format_items( 'table', $rows, [ 'Type', 'Count', 'IDs' ] );

		$total = array_sum( array_column( $rows, 'Count' ) );
		WP_CLI::success( sprintf( '%d items created.', $total ) );
	}
}
