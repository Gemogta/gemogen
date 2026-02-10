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
 *
 *     # Rollback the last run
 *     wp gemogen rollback --last
 *
 *     # Show run history
 *     wp gemogen history
 *
 *     # Remove all Gemogen-generated content
 *     wp gemogen reset
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

		WP_CLI::line( '' );
		WP_CLI::line( 'To undo this run:' );
		WP_CLI::line( '  wp gemogen rollback --last' );
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
	 * [<scenario-id>]
	 * : The scenario ID to rollback. Not needed with --last.
	 *
	 * [--ids=<json>]
	 * : JSON map of content type => IDs to delete.
	 *
	 * [--last]
	 * : Rollback the most recent run from history.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gemogen rollback core-content --ids='{"posts":[1,2,3],"users":[4,5]}'
	 *     wp gemogen rollback --last
	 */
	public function rollback( array $args, array $assoc_args ): void {
		$manager = $this->getManager();

		// Handle --last flag.
		if ( isset( $assoc_args['last'] ) ) {
			$history = $manager->getHistory();
			$last    = $history->getLast();

			if ( $last === null ) {
				WP_CLI::error( 'No runs in history.' );
			}

			$scenario_id = $last['scenario_id'];
			$ids         = $last['created_ids'];
			$total       = array_sum( array_map( 'count', $ids ) );

			WP_CLI::line( "Rolling back last run ({$scenario_id}, {$total} items)..." );

			try {
				$manager->rollback( $scenario_id, $ids );
			} catch ( \Throwable $e ) {
				WP_CLI::error( $e->getMessage() );
			}

			$history->removeLast();
			WP_CLI::success( "Rollback complete. {$total} items removed." );
			return;
		}

		// Standard rollback with scenario-id + --ids.
		if ( empty( $args ) ) {
			WP_CLI::error( 'Provide a scenario ID or use --last.' );
		}

		$scenario_id = $args[0];
		$scenario    = $manager->get( $scenario_id );

		if ( $scenario === null ) {
			WP_CLI::error( "Scenario not found: {$scenario_id}" );
		}

		if ( ! isset( $assoc_args['ids'] ) ) {
			WP_CLI::error( '--ids is required. Pass the JSON output from the run command, or use --last.' );
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

	/**
	 * Show run history.
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
	 *     wp gemogen history
	 *     wp gemogen history --format=json
	 */
	public function history( array $args, array $assoc_args ): void {
		$history = $this->getManager()->getHistory();
		$runs    = $history->getAll();

		if ( empty( $runs ) ) {
			WP_CLI::warning( 'No runs recorded.' );
			return;
		}

		$rows = [];

		foreach ( array_reverse( $runs ) as $index => $run ) {
			$rows[] = [
				'#'        => count( $runs ) - $index,
				'Scenario' => $run['scenario_id'],
				'Items'    => $run['total'],
				'Date'     => date( 'Y-m-d H:i:s', $run['timestamp'] ),
			];
		}

		$format = $assoc_args['format'] ?? 'table';
		WP_CLI\Utils\format_items( $format, $rows, [ '#', 'Scenario', 'Items', 'Date' ] );
	}

	/**
	 * Remove ALL Gemogen-generated content.
	 *
	 * Deletes every post, user, term, and comment that has the
	 * `_gemogen_generated` meta tag. Also clears run history.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gemogen reset
	 *     wp gemogen reset --yes
	 */
	public function reset( array $args, array $assoc_args ): void {
		if ( ! isset( $assoc_args['yes'] ) ) {
			WP_CLI::confirm( 'This will delete ALL content generated by Gemogen. Continue?' );
		}

		$counts = [
			'posts'    => 0,
			'users'    => 0,
			'terms'    => 0,
			'comments' => 0,
		];

		// Delete posts (including pages, attachments, CPTs).
		global $wpdb;

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

		if ( $total === 0 ) {
			WP_CLI::success( 'No Gemogen-generated content found.' );
			return;
		}

		$parts = [];
		foreach ( $counts as $type => $count ) {
			if ( $count > 0 ) {
				$parts[] = "{$count} {$type}";
			}
		}

		WP_CLI::success( sprintf( 'Reset complete. Removed %d items: %s.', $total, implode( ', ', $parts ) ) );
	}
}
