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

require_once PROGRAMS_MODULE_DIR . 'includes/class-programs-cpt.php';
require_once PROGRAMS_MODULE_DIR . 'includes/class-programs-seeder.php';
require_once PROGRAMS_MODULE_DIR . 'includes/class-programs-meta.php';
require_once PROGRAMS_MODULE_DIR . 'includes/class-programs-routing.php';
require_once PROGRAMS_MODULE_DIR . 'includes/class-programs-tracking.php';
require_once PROGRAMS_MODULE_DIR . 'includes/class-programs-enquiry.php';

/**
 * Boots the plugin components.
 *
 * Components register their own hooks; nothing is instantiated before
 * `plugins_loaded` so that the whole plugin can be disabled cleanly.
 *
 * @return void
 */
function programs_module_bootstrap() {
	$programs_cpt = new Programs_CPT();
	$programs_cpt->register_hooks();

	$programs_seeder = new Programs_Seeder();
	$programs_seeder->register_hooks();

	$programs_meta = new Programs_Meta();
	$programs_meta->register_hooks();

	$programs_routing = new Programs_Routing();
	$programs_routing->register_hooks();

	$programs_tracking = new Programs_Tracking();
	$programs_tracking->register_hooks();

	$programs_enquiry = new Programs_Enquiry();
	$programs_enquiry->register_hooks();
}
add_action( 'plugins_loaded', 'programs_module_bootstrap' );

/**
 * Loads the plugin text domain.
 *
 * @return void
 */
function programs_module_load_textdomain() {
	load_plugin_textdomain( 'programs-module', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'programs_module_load_textdomain' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once PROGRAMS_MODULE_DIR . 'includes/class-programs-cli.php';

	WP_CLI::add_command( 'programs', 'Programs_CLI' );
}

/**
 * Runs on plugin activation.
 *
 * Rewrites are flushed so any rules the plugin registers are available
 * immediately instead of on the next incidental rewrite flush.
 *
 * @return void
 */
function programs_module_activate() {
	// Register the content model first so the rules we are about to flush
	// actually include the program post type and the program-type taxonomy.
	$programs_cpt = new Programs_CPT();
	$programs_cpt->register_post_type();
	$programs_cpt->register_taxonomy();

	// The taxonomy has to exist before terms can be assigned to it.
	Programs_Seeder::seed_all();

	// The pretty /programmes/undergraduate style rules are added on init,
	// which has already run at this point, so add them here too or the
	// flush below would write a rule set without them.
	$programs_routing = new Programs_Routing();
	$programs_routing->add_rewrite_rules();

	// Make sure the tracking log directory exists and is protected from the
	// moment the plugin goes live, not on the first tracked click.
	Programs_Tracking::get_log_dir();

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
