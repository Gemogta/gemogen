<?php

declare(strict_types=1);

namespace Gemogen\Core;

/**
 * Stores run history in a WP option for easy rollback and tracking.
 */
class RunHistory {

	private const OPTION_KEY = 'gemogen_run_history';
	private const MAX_RUNS   = 20;

	/**
	 * Record a completed run.
	 *
	 * @param string               $scenarioId The scenario that was executed.
	 * @param array<string, mixed> $config     The configuration used.
	 * @param array<string, int[]> $createdIds Map of content type => created IDs.
	 */
	public function record( string $scenarioId, array $config, array $createdIds ): void {
		$history = $this->getAll();

		$history[] = [
			'scenario_id' => $scenarioId,
			'config'      => $config,
			'created_ids' => $createdIds,
			'total'       => array_sum( array_map( 'count', $createdIds ) ),
			'timestamp'   => time(),
		];

		// Keep only the last N runs (FIFO).
		if ( count( $history ) > self::MAX_RUNS ) {
			$history = array_slice( $history, -self::MAX_RUNS );
		}

		if ( function_exists( 'update_option' ) ) {
			update_option( self::OPTION_KEY, $history, false );
		}
	}

	/**
	 * Get the most recent run.
	 *
	 * @return array{scenario_id: string, config: array, created_ids: array, total: int, timestamp: int}|null
	 */
	public function getLast(): ?array {
		$history = $this->getAll();

		if ( empty( $history ) ) {
			return null;
		}

		return end( $history ) ?: null;
	}

	/**
	 * Get all recorded runs.
	 *
	 * @return array<int, array{scenario_id: string, config: array, created_ids: array, total: int, timestamp: int}>
	 */
	public function getAll(): array {
		if ( ! function_exists( 'get_option' ) ) {
			return [];
		}

		$history = get_option( self::OPTION_KEY, [] );

		return is_array( $history ) ? $history : [];
	}

	/**
	 * Remove the most recent run from history.
	 */
	public function removeLast(): void {
		$history = $this->getAll();

		if ( ! empty( $history ) ) {
			array_pop( $history );

			if ( function_exists( 'update_option' ) ) {
				update_option( self::OPTION_KEY, $history, false );
			}
		}
	}

	/**
	 * Clear all run history.
	 */
	public function clear(): void {
		if ( function_exists( 'delete_option' ) ) {
			delete_option( self::OPTION_KEY );
		}
	}
}
