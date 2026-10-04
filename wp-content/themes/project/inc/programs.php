<?php
/**
 * Theme-side support for the Programs module.
 *
 * The plugin owns the content model; this file owns everything presentational
 * that is not a template: the stylesheet, the image sizes the cards ask for,
 * and small helpers the templates share.
 *
 * Every helper is guarded so the theme still works if the plugin is
 * deactivated.
 *
 * @package PROJECT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Whether the Programs module plugin is active.
 *
 * @return bool
 */
function project_has_programs_module() {
	return class_exists( 'Programs_CPT' );
}

/**
 * Registers the image sizes used by the programme templates.
 *
 * Declared here rather than in a template so the sizes exist even for
 * images uploaded before a programme page was first viewed.
 *
 * @return void
 */
function project_programs_image_sizes() {
	add_image_size( 'program-card', 640, 428, true );
	add_image_size( 'program-hero', 1600, 640, true );
}
add_action( 'after_setup_theme', 'project_programs_image_sizes' );

/**
 * Enqueues the programme stylesheet.
 *
 * Registered in functions.php against the Aberdeen design system so the two
 * stylesheets share one set of design tokens and cannot drift apart.
 *
 * @return void
 */
function project_programs_scripts() {
	if ( ! project_has_programs_module() ) {
		return;
	}

	wp_enqueue_style( 'programs-css' );
}
add_action( 'wp_enqueue_scripts', 'project_programs_scripts' );

/**
 * Appends a "Programmes" link to the primary menu.
 *
 * Keeps the header usable on a fresh install, before any menu has been
 * assigned in the customiser. The URL comes from the plugin's own routing
 * helper rather than being written into the theme.
 *
 * @param string   $items The menu HTML.
 * @param stdClass $args  The menu args.
 * @return string
 */
function project_programs_menu_item( $items, $args ) {
	if ( ! project_has_programs_module() ) {
		return $items;
	}

	if ( empty( $args->theme_location ) || 'menu-1' !== $args->theme_location ) {
		return $items;
	}

	return $items . sprintf(
		'<li class="menu-item menu-item--programmes"><a href="%1$s">%2$s</a></li>',
		esc_url( Programs_Routing::get_archive_url() ),
		esc_html__( 'Programmes', 'project' )
	);
}
add_filter( 'wp_nav_menu_items', 'project_programs_menu_item', 10, 2 );

/**
 * Whether the current request is one of the programme screens.
 *
 * Used to decide whether the enquiry pop-up and the mobile sticky bar are
 * rendered at all.
 *
 * @return bool
 */
function project_is_programmes_context() {
	if ( ! project_has_programs_module() ) {
		return false;
	}

	return is_singular( Programs_CPT::POST_TYPE )
		|| is_post_type_archive( Programs_CPT::POST_TYPE )
		|| is_tax( Programs_CPT::TAXONOMY );
}

/**
 * Flags programme screens on the body element.
 *
 * Lets the stylesheet reserve space for the mobile sticky bar only where
 * that bar actually exists.
 *
 * @param array $classes Body classes.
 * @return array
 */
function project_programs_body_class( $classes ) {
	if ( project_is_programmes_context() ) {
		$classes[] = 'has-sticky-enquire';
	}

	return $classes;
}
add_filter( 'body_class', 'project_programs_body_class' );

/**
 * Returns the academic year and duration for a program, already escaped.
 *
 * @param int $program_id The program post ID.
 * @return array{year: string, duration: string}
 */
function project_program_facts( $program_id ) {
	if ( ! project_has_programs_module() ) {
		return array(
			'year'     => '',
			'duration' => '',
		);
	}

	return array(
		'year'     => (string) get_post_meta( $program_id, Programs_CPT::META_ACADEMIC_YEAR, true ),
		'duration' => (string) get_post_meta( $program_id, Programs_CPT::META_DURATION, true ),
	);
}

/**
 * Returns the primary program type term for a program.
 *
 * @param int $program_id The program post ID.
 * @return WP_Term|null
 */
function project_program_type( $program_id ) {
	if ( ! project_has_programs_module() ) {
		return null;
	}

	$terms = wp_get_object_terms( $program_id, Programs_CPT::TAXONOMY );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return null;
	}

	return $terms[0];
}
