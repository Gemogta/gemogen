<?php

declare(strict_types=1);

namespace Gemogen\Core;

use Gemogen\Contracts\ScenarioInterface;

class ScenarioManager {

	/** @var array<string, ScenarioInterface> */
	private array $scenarios = [];

	private Logger $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Register a scenario.
	 */
	public function register( ScenarioInterface $scenario ): void {
		$this->scenarios[ $scenario->getId() ] = $scenario;
	}

	/**
	 * Get a scenario by ID.
	 */
	public function get( string $id ): ?ScenarioInterface {
		return $this->scenarios[ $id ] ?? null;
	}

	/**
	 * Get all registered scenarios.
	 *
	 * @return array<string, ScenarioInterface>
	 */
	public function all(): array {
		return $this->scenarios;
	}

	/**
	 * Execute a scenario.
	 *
	 * @param string               $id     Scenario ID.
	 * @param array<string, mixed> $config Configuration for the scenario.
	 * @return array<string, int[]> Map of content type => created IDs.
	 *
	 * @throws \InvalidArgumentException If scenario not found.
	 * @throws \RuntimeException         If validation fails.
	 */
	public function execute( string $id, array $config = [] ): array {
		$scenario = $this->get( $id );

		if ( $scenario === null ) {
			throw new \InvalidArgumentException( "Scenario not found: {$id}" );
		}

		// Validate if the scenario supports it.
		if ( $scenario instanceof AbstractScenario ) {
			$errors = $scenario->validate( $config );
			if ( ! empty( $errors ) ) {
				throw new \RuntimeException(
					sprintf( 'Validation failed for scenario "%s": %s', $id, implode( '; ', $errors ) )
				);
			}
		}

		$this->logger->info( "Executing scenario: {$id}" );

		/**
		 * Fires before a scenario is executed.
		 *
		 * @param ScenarioInterface    $scenario The scenario being executed.
		 * @param array<string, mixed> $config   The configuration.
		 */
		if ( function_exists( 'do_action' ) ) {
			do_action( 'gemogen_before_execute', $scenario, $config );
		}

		$created_ids = $scenario->execute( $config );

		/**
		 * Fires after a scenario is executed.
		 *
		 * @param ScenarioInterface    $scenario   The scenario that was executed.
		 * @param array<string, int[]> $created_ids The created content IDs.
		 * @param array<string, mixed> $config      The configuration used.
		 */
		if ( function_exists( 'do_action' ) ) {
			do_action( 'gemogen_after_execute', $scenario, $created_ids, $config );
		}

		$total = array_sum( array_map( 'count', $created_ids ) );
		$this->logger->info( "Scenario '{$id}' completed: {$total} items created." );

		return $created_ids;
	}

	/**
	 * Rollback a scenario's created content.
	 *
	 * @param string               $id         Scenario ID.
	 * @param array<string, int[]> $createdIds IDs to rollback.
	 */
	public function rollback( string $id, array $createdIds ): void {
		$scenario = $this->get( $id );

		if ( $scenario === null ) {
			throw new \InvalidArgumentException( "Scenario not found: {$id}" );
		}

		$this->logger->info( "Rolling back scenario: {$id}" );

		$scenario->rollback( $createdIds );

		$total = array_sum( array_map( 'count', $createdIds ) );
		$this->logger->info( "Rollback complete: {$total} items removed." );
	}

	/**
	 * Fire the registration hook so third-party code can register scenarios.
	 */
	public function discover(): void {
		/**
		 * Fires when scenarios should be registered.
		 *
		 * @param ScenarioManager $manager The scenario manager to register with.
		 */
		if ( function_exists( 'do_action' ) ) {
			do_action( 'gemogen_register_scenarios', $this );
		}
	}
}
