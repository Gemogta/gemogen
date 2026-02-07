<?php

declare(strict_types=1);

namespace Gemogen;

use Gemogen\Core\ContentPool;
use Gemogen\Core\Logger;
use Gemogen\Core\ScenarioManager;
use Gemogen\Generators\CommentGenerator;
use Gemogen\Generators\MediaGenerator;
use Gemogen\Generators\PostGenerator;
use Gemogen\Generators\TaxonomyGenerator;
use Gemogen\Generators\UserGenerator;
use Gemogen\Scenarios\CoreContentScenario;
use Gemogen\Sources\BuiltInSource;
use Gemogen\Sources\UserTemplateSource;

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

		// Content sources and pool.
		$this->container->set( 'source.builtin', fn() => new BuiltInSource() );
		$this->container->set( 'source.user_template', fn() => new UserTemplateSource() );

		$this->container->set(
			'content.pool',
			function ( Container $c ): ContentPool {
				$pool = new ContentPool();
				$pool->addSource( $c->get( 'source.builtin' ) );
				$pool->addSource( $c->get( 'source.user_template' ) );

				/**
				 * Allows adding custom content sources to the pool.
				 *
				 * @param ContentPool $pool The content pool to add sources to.
				 */
				if ( function_exists( 'do_action' ) ) {
					do_action( 'gemogen_content_sources', $pool );
				}

				return $pool;
			}
		);

		// Generators (all receive the ContentPool).
		$this->container->set( 'generator.post', fn( Container $c ) => new PostGenerator( $c->get( 'content.pool' ) ) );
		$this->container->set( 'generator.user', fn( Container $c ) => new UserGenerator( $c->get( 'content.pool' ) ) );
		$this->container->set( 'generator.taxonomy', fn( Container $c ) => new TaxonomyGenerator( $c->get( 'content.pool' ) ) );
		$this->container->set( 'generator.comment', fn( Container $c ) => new CommentGenerator( $c->get( 'content.pool' ) ) );
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
