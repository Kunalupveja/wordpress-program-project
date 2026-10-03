<?php
/**
 * Programs Module
 *
 * Custom post type, taxonomy, metadata, React admin UI, frontend routing,
 * reusable program card, Interactivity API filter and click tracking.
 *
 * @package Programs_Module
 *
 * @wordpress-plugin
 * Plugin Name:       Programs Module
 * Plugin URI:        https://github.com/kunal-upveja/wordpress-programs-module
 * Description:       Provides the Programs content model and frontend for the university site: CPT, taxonomy, custom React admin panel, custom routing, reusable card, Interactivity API filter and click tracking.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Kunal
 * Author URI:        https://github.com/kunal-upveja
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       programs-module
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'PROGRAMS_MODULE_VERSION', '0.1.0' );
define( 'PROGRAMS_MODULE_FILE', __FILE__ );
define( 'PROGRAMS_MODULE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PROGRAMS_MODULE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Loads the plugin text domain.
 *
 * @return void
 */
function programs_module_load_textdomain() {
	load_plugin_textdomain( 'programs-module', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'programs_module_load_textdomain' );

/**
 * Runs on plugin activation.
 *
 * Rewrites are flushed so any rules the plugin registers are available
 * immediately instead of on the next incidental rewrite flush.
 *
 * @return void
 */
function programs_module_activate() {
	update_option( 'programs_module_version', PROGRAMS_MODULE_VERSION );

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'programs_module_activate' );

/**
 * Runs on plugin deactivation.
 *
 * Flushes so the plugin's rewrite rules stop being served.
 *
 * @return void
 */
function programs_module_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'programs_module_deactivate' );
