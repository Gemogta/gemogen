<?php

declare(strict_types=1);

namespace Gemogen\CLI;

use WP_CLI;

/**
 * Manage Gemogen content generation scenarios.
 *
 * ## EXAMPLES
 *
 *     # List all scenarios
 *     wp gemogen list
 *
 *     # Run a scenario
 *     wp gemogen run core-content --config='{"posts":5}'
 *
 *     # Show scenario details
 *     wp gemogen info core-content
 *
 *     # Rollback created content
 *     wp gemogen rollback core-content --ids='{"posts":[1,2,3]}'
 */
class ScenarioCommand extends BaseCommand {

	/**
	 * List all registered scenarios.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp gemogen list
	 *     wp gemogen list --format=json
	 *
	 * @subcommand list
	 */
	public function list_scenarios( array $args, array $assoc_args ): void {
		$manager   = $this->getManager();
		$scenarios = $manager->all();

		if ( empty( $scenarios ) ) {
			WP_CLI::warning( 'No scenarios registered.' );
			return;
		}

		$rows = [];

		foreach ( $scenarios as $scenario ) {
			$schema     = $scenario->getSchema();
			$properties = $schema['properties'] ?? [];
			$fields     = array_keys( $properties );

			$rows[] = [
				'ID'          => $scenario->getId(),
				'Name'        => $scenario->getName(),
				'Description' => $scenario->getDescription(),
				'Fields'      => implode( ', ', $fields ),
			];
		}

		$format = $assoc_args['format'] ?? 'table';
		WP_CLI\Utils\format_items( $format, $rows, [ 'ID', 'Name', 'Description', 'Fields' ] );
	}

	/**
	 * Run a scenario.
	 *
	 * ## OPTIONS
	 *
	 * <scenario-id>
	 * : The scenario ID to execute.
	 *
	 * [--config=<json>]
	 * : JSON configuration for the scenario.
	 *
	 * [--dry-run]
	 * : Show what would be created without actually creating anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gemogen run core-content
	 *     wp gemogen run core-content --config='{"posts":5,"pages":2}'
	 *     wp gemogen run core-content --dry-run
	 */
	public function run( array $args, array $assoc_args ): void {
		$scenario_id = $args[0];
		$manager     = $this->getManager();

		$scenario = $manager->get( $scenario_id );

		if ( $scenario === null ) {
			WP_CLI::error( "Scenario not found: {$scenario_id}" );
		}

		$config = [];

		if ( isset( $assoc_args['config'] ) ) {
			$config = $this->parseJson( $assoc_args['config'], 'config' );
		}

		// Dry run: show what would be created.
		if ( isset( $assoc_args['dry-run'] ) ) {
			WP_CLI::line( "Dry run for scenario: {$scenario_id}" );
			WP_CLI::line( '' );

			$schema   = $scenario->getSchema();
			$defaults = [];

			foreach ( $schema['properties'] ?? [] as $key => $def ) {
				$value = $config[ $key ] ?? $def['default'] ?? '—';
				$defaults[] = [
					'Field'       => $key,
					'Value'       => (string) $value,
					'Description' => $def['description'] ?? '',
				];
			}

			WP_CLI\Utils\format_items( 'table', $defaults, [ 'Field', 'Value', 'Description' ] );
			WP_CLI::success( 'Dry run complete. No content was created.' );
			return;
		}

		WP_CLI::line( "Running scenario: {$scenario_id}..." );
		WP_CLI::line( '' );

		try {
			$created_ids = $manager->execute( $scenario_id, $config );
		} catch ( \Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		$this->printSummary( $created_ids );

		// Output the IDs as JSON for potential piping to rollback.
		WP_CLI::line( '' );
		WP_CLI::line( 'Rollback command:' );
		WP_CLI::line( sprintf(
			"wp gemogen rollback %s --ids='%s'",
			$scenario_id,
			json_encode( $created_ids, JSON_UNESCAPED_SLASHES )
		) );
	}

	/**
	 * Show details about a scenario.
	 *
	 * ## OPTIONS
	 *
	 * <scenario-id>
	 * : The scenario ID to inspect.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gemogen info core-content
	 */
	public function info( array $args, array $assoc_args ): void {
		$scenario_id = $args[0];
		$manager     = $this->getManager();
		$scenario    = $manager->get( $scenario_id );

		if ( $scenario === null ) {
			WP_CLI::error( "Scenario not found: {$scenario_id}" );
		}

		WP_CLI::line( "Scenario: {$scenario->getName()}" );
		WP_CLI::line( "ID: {$scenario->getId()}" );
		WP_CLI::line( "Description: {$scenario->getDescription()}" );
		WP_CLI::line( '' );
		WP_CLI::line( 'Configuration schema:' );

		$schema = $scenario->getSchema();
		$rows   = [];

		foreach ( $schema['properties'] ?? [] as $key => $def ) {
			$rows[] = [
				'Field'       => $key,
				'Type'        => $def['type'] ?? 'string',
				'Default'     => isset( $def['default'] ) ? (string) $def['default'] : '—',
				'Description' => $def['description'] ?? '',
			];
		}

		WP_CLI\Utils\format_items( 'table', $rows, [ 'Field', 'Type', 'Default', 'Description' ] );
	}

	/**
	 * Rollback content created by a scenario.
	 *
	 * ## OPTIONS
	 *
	 * <scenario-id>
	 * : The scenario ID to rollback.
	 *
	 * --ids=<json>
	 * : JSON map of content type => IDs to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gemogen rollback core-content --ids='{"posts":[1,2,3],"users":[4,5]}'
	 */
	public function rollback( array $args, array $assoc_args ): void {
		$scenario_id = $args[0];
		$manager     = $this->getManager();

		$scenario = $manager->get( $scenario_id );

		if ( $scenario === null ) {
			WP_CLI::error( "Scenario not found: {$scenario_id}" );
		}

		if ( ! isset( $assoc_args['ids'] ) ) {
			WP_CLI::error( '--ids is required. Pass the JSON output from the run command.' );
		}

		$ids = $this->parseJson( $assoc_args['ids'], 'ids' );

		$total = array_sum( array_map( 'count', $ids ) );
		WP_CLI::line( "Rolling back {$total} items for scenario: {$scenario_id}..." );

		try {
			$manager->rollback( $scenario_id, $ids );
		} catch ( \Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::success( "Rollback complete. {$total} items removed." );
	}
}
