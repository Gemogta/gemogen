<?php

declare(strict_types=1);

namespace Gemogen;

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
		// Services will be registered here in Milestone 1+.
	}

	/**
	 * Register WordPress hooks.
	 */
	private function register_hooks(): void {
		// Hooks will be registered here in Milestone 1+.
	}

	/**
	 * Reset the singleton (for testing only).
	 */
	public static function reset(): void {
		self::$instance = null;
	}
}
