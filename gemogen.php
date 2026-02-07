<?php
/**
 * Plugin Name: Gemogen
 * Plugin URI:  https://github.com/gemogen/gemogen
 * Description: Scenario-based content generator for WordPress development.
 * Version:     0.1.0
 * Author:      Gemogen
 * License:     GPL-2.0-or-later
 * Text Domain: gemogen
 * Requires PHP: 8.1
 * Requires at least: 6.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GEMOGEN_VERSION', '0.1.0' );
define( 'GEMOGEN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEMOGEN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GEMOGEN_PLUGIN_FILE', __FILE__ );

// Load Composer autoloader.
$autoloader = GEMOGEN_PLUGIN_DIR . 'vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Gemogen: Please run "composer install" in the plugin directory.', 'gemogen' );
			echo '</p></div>';
		}
	);
	return;
}
require_once $autoloader;

// Boot the plugin on plugins_loaded.
add_action( 'plugins_loaded', [ Gemogen\Plugin::class, 'boot' ] );
