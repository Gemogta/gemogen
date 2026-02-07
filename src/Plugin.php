<?php

declare(strict_types=1);

namespace Gemogen;

use Gemogen\Core\Logger;
use Gemogen\Core\ScenarioManager;
use Gemogen\Generators\CommentGenerator;
use Gemogen\Generators\MediaGenerator;
use Gemogen\Generators\PostGenerator;
use Gemogen\Generators\TaxonomyGenerator;
use Gemogen\Generators\UserGenerator;
use Gemogen\Scenarios\CoreContentScenario;

/**
 * Main plugin class — handles boot sequence and service wiring.
 */
class Plugin {

	private static ?self $instance = null;
	private Container $container;

	private function __construct() {
		$this->container = new Container();
	}

	/**
	 * Boot the plugin (called on plugins_loaded).
	 */
	public static function boot(): void {
		if ( self::$instance !== null ) {
			return;
		}

		self::$instance = new self();
		self::$instance->register_services();
		self::$instance->register_hooks();

		/**
		 * Fires after Gemogen has fully loaded.
		 *
		 * @param Container $container The plugin's DI container.
		 */
		do_action( 'gemogen_loaded', self::$instance->container );
	}

	/**
	 * Get the plugin container.
	 */
	public static function container(): Container {
		if ( self::$instance === null ) {
			throw new \RuntimeException( 'Gemogen has not been booted yet.' );
		}

		return self::$instance->container;
	}

	/**
	 * Register services into the container.
	 */
	private function register_services(): void {
		$this->container->set( 'logger', fn() => new Logger() );

		$this->container->set( 'generator.post', fn() => new PostGenerator() );
		$this->container->set( 'generator.user', fn() => new UserGenerator() );
		$this->container->set( 'generator.taxonomy', fn() => new TaxonomyGenerator() );
		$this->container->set( 'generator.comment', fn() => new CommentGenerator() );
		$this->container->set( 'generator.media', fn() => new MediaGenerator() );

		$this->container->set(
			'scenario.core-content',
			fn( Container $c ) => new CoreContentScenario(
				$c->get( 'logger' ),
				$c->get( 'generator.post' ),
				$c->get( 'generator.user' ),
				$c->get( 'generator.taxonomy' ),
				$c->get( 'generator.comment' ),
				$c->get( 'generator.media' ),
			)
		);

		$this->container->set(
			'scenario.manager',
			function ( Container $c ): ScenarioManager {
				$manager = new ScenarioManager( $c->get( 'logger' ) );
				$manager->register( $c->get( 'scenario.core-content' ) );
				return $manager;
			}
		);
	}

	/**
	 * Register WordPress hooks.
	 */
	private function register_hooks(): void {
		add_action( 'init', fn() => $this->container->get( 'scenario.manager' )->discover() );
	}

	/**
	 * Reset the singleton (for testing only).
	 */
	public static function reset(): void {
		self::$instance = null;
	}
}
