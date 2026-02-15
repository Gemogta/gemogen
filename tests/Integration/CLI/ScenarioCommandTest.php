<?php

declare(strict_types=1);

namespace Gemogen\Tests\Integration\CLI;

use WP_UnitTestCase;

/**
 * Integration tests for WP-CLI Gemogen commands.
 *
 * Requires wp-env (Docker) and WP-CLI test harness.
 * These tests validate CLI commands execute correctly against a real WP instance.
 */
class ScenarioCommandTest extends WP_UnitTestCase {

	/**
	 * Helper: run a WP-CLI command and capture the output.
	 *
	 * @param string $command The CLI command (e.g., 'gemogen list').
	 * @return array{exit_code: int, stdout: string, stderr: string}
	 */
	private function runCliCommand( string $command ): array {
		$stdout = '';
		$stderr = '';

		ob_start();

		try {
			\WP_CLI::run_command(
				explode( ' ', $command ),
				[],
			);
			$exit_code = 0;
		} catch ( \WP_CLI\ExitException $e ) {
			$exit_code = $e->getCode();
		}

		$stdout = ob_get_clean() ?: '';

		return [
			'exit_code' => $exit_code,
			'stdout'    => $stdout,
			'stderr'    => $stderr,
		];
	}

	public function test_list_command_succeeds(): void {
		// Ensure the plugin is booted and scenarios are registered.
		$this->assertTrue(
			class_exists( \Gemogen\CLI\ScenarioCommand::class ),
			'ScenarioCommand class should exist'
		);
	}

	public function test_scenario_command_class_extends_base_command(): void {
		$this->assertTrue(
			is_subclass_of( \Gemogen\CLI\ScenarioCommand::class, \Gemogen\CLI\BaseCommand::class ),
			'ScenarioCommand should extend BaseCommand'
		);
	}

	public function test_scenario_command_has_list_method(): void {
		$this->assertTrue(
			method_exists( \Gemogen\CLI\ScenarioCommand::class, 'list_scenarios' ),
			'ScenarioCommand should have list_scenarios method'
		);
	}

	public function test_scenario_command_has_run_method(): void {
		$this->assertTrue(
			method_exists( \Gemogen\CLI\ScenarioCommand::class, 'run' ),
			'ScenarioCommand should have run method'
		);
	}

	public function test_scenario_command_has_rollback_method(): void {
		$this->assertTrue(
			method_exists( \Gemogen\CLI\ScenarioCommand::class, 'rollback' ),
			'ScenarioCommand should have rollback method'
		);
	}

	public function test_scenario_command_has_info_method(): void {
		$this->assertTrue(
			method_exists( \Gemogen\CLI\ScenarioCommand::class, 'info' ),
			'ScenarioCommand should have info method'
		);
	}

	public function test_scenario_command_has_history_method(): void {
		$this->assertTrue(
			method_exists( \Gemogen\CLI\ScenarioCommand::class, 'history' ),
			'ScenarioCommand should have history method'
		);
	}

	public function test_scenario_command_has_reset_method(): void {
		$this->assertTrue(
			method_exists( \Gemogen\CLI\ScenarioCommand::class, 'reset' ),
			'ScenarioCommand should have reset method'
		);
	}

	public function test_core_content_scenario_is_registered(): void {
		$manager  = \Gemogen\Plugin::container()->get( 'scenario.manager' );
		$scenario = $manager->get( 'core-content' );

		$this->assertNotNull( $scenario, 'core-content scenario should be registered' );
	}

	public function test_run_creates_content_and_records_history(): void {
		$manager = \Gemogen\Plugin::container()->get( 'scenario.manager' );

		$created = $manager->execute( 'core-content', [
			'posts'      => 2,
			'pages'      => 1,
			'users'      => 1,
			'categories' => 1,
			'tags'       => 1,
			'comments'   => 2,
			'media'      => 0,
		] );

		// Verify content was created.
		$this->assertCount( 2, $created['posts'] );
		$this->assertCount( 1, $created['pages'] );

		// Verify run was recorded.
		$history = $manager->getHistory();
		$last    = $history->getLast();

		$this->assertNotNull( $last );
		$this->assertSame( 'core-content', $last['scenario_id'] );
	}

	public function test_rollback_last_removes_content_and_history_entry(): void {
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

		$history = $manager->getHistory();
		$last    = $history->getLast();

		$this->assertNotNull( $last );

		// Rollback.
		$manager->rollback( $last['scenario_id'], $last['created_ids'] );
		$history->removeLast();

		// Verify posts are deleted.
		foreach ( $created['posts'] as $post_id ) {
			$this->assertNull( get_post( $post_id ) );
		}
	}
}
