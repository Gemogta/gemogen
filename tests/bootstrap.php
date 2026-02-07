<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file.
 *
 * When running inside wp-env, the WP test suite is available at:
 * /wordpress-phpunit/includes/
 *
 * For local runs, set WP_TESTS_DIR to point to your WP test suite.
 */

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/wordpress-phpunit';

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	// Fallback: just load Composer autoloader for basic unit tests.
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
	return;
}

// Load WP test suite functions.
require_once $wp_tests_dir . '/includes/functions.php';

// Load the plugin before WP boots.
tests_add_filter(
	'muplugins_loaded',
	function () {
		require_once dirname( __DIR__ ) . '/gemogen.php';
	}
);

// Start the WP test suite.
require_once $wp_tests_dir . '/includes/bootstrap.php';
