<?php
/**
 * Registers the "Program" custom post type and the "Program Type" taxonomy.
 *
 * @package Programs_Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Programs_CPT
 *
 * Owns the Programs content model: the post type itself and the taxonomy
 * used to group programmes by level (Undergraduate / Postgraduate).
 */
class Programs_CPT {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'program';

	/**
	 * Taxonomy key.
	 *
	 * @var string
	 */
	const TAXONOMY = 'program-type';

	/**
	 * Public base for the archive and for single programs.
	 *
	 * /programmes            -> all programs
	 * /programmes/{slug}     -> a single program
	 *
	 * @var string
	 */
	const REWRITE_SLUG = 'programmes';

	/**
	 * Public base for the taxonomy.
	 *
	 * Deliberately namespaced under /programmes/type rather than sharing
	 * REWRITE_SLUG. If the taxonomy also claimed /programmes/{slug} it would
	 * race with single programs, and whichever rule was registered first
	 * would win. Task 4 adds explicit rules for /programmes/undergraduate
	 * and /programmes/postgraduate, so the canonical term archive keeps its
	 * own base and the two structures can never collide.
	 *
	 * @var string
	 */
	const TAXONOMY_REWRITE_SLUG = 'programmes/type';

	/**
	 * Term slugs seeded by this plugin, in display order.
	 *
	 * @var string[]
	 */
	const DEFAULT_TERMS = array( 'undergraduate', 'postgraduate' );

	/**
	 * Meta key for the academic year, e.g. "2025-26".
	 *
	 * @var string
	 */
	const META_ACADEMIC_YEAR = 'program_academic_year';

	/**
	 * Meta key for the duration, e.g. "3 Years".
	 *
	 * @var string
	 */
	const META_DURATION = 'program_duration';

	/**
	 * Meta key for the repeater content sections.
	 *
	 * Stored as an array of arrays: title, description, optional image ID.
	 *
	 * @var string
	 */
	const META_CONTENT_SECTIONS = 'program_content_sections';

	/**
	 * Registers the hooks for this class.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Registers the `program` post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'                     => _x( 'Programs', 'Post type general name', 'programs-module' ),
			'singular_name'            => _x( 'Program', 'Post type singular name', 'programs-module' ),
			'menu_name'                => _x( 'Programs', 'Admin menu text', 'programs-module' ),
			'name_admin_bar'           => _x( 'Program', 'Add new on toolbar', 'programs-module' ),
			'add_new'                  => __( 'Add New', 'programs-module' ),
			'add_new_item'             => __( 'Add New Program', 'programs-module' ),
			'new_item'                 => __( 'New Program', 'programs-module' ),
			'edit_item'                => __( 'Edit Program', 'programs-module' ),
			'view_item'                => __( 'View Program', 'programs-module' ),
			'view_items'               => __( 'View Programs', 'programs-module' ),
			'all_items'                => __( 'All Programs', 'programs-module' ),
			'search_items'             => __( 'Search Programs', 'programs-module' ),
			'parent_item_colon'        => __( 'Parent Programs:', 'programs-module' ),
			'not_found'                => __( 'No programs found.', 'programs-module' ),
			'not_found_in_trash'       => __( 'No programs found in Trash.', 'programs-module' ),
			'featured_image'           => __( 'Program image', 'programs-module' ),
			'set_featured_image'       => __( 'Set program image', 'programs-module' ),
			'remove_featured_image'    => __( 'Remove program image', 'programs-module' ),
			'use_featured_image'       => __( 'Use as program image', 'programs-module' ),
			'archives'                 => __( 'Program archives', 'programs-module' ),
			'insert_into_item'         => __( 'Insert into program', 'programs-module' ),
			'uploaded_to_this_item'    => __( 'Uploaded to this program', 'programs-module' ),
			'filter_items_list'        => __( 'Filter programs list', 'programs-module' ),
			'items_list_navigation'    => __( 'Programs list navigation', 'programs-module' ),
			'items_list'               => __( 'Programs list', 'programs-module' ),
			'item_published'           => __( 'Program published.', 'programs-module' ),
			'item_published_privately' => __( 'Program published privately.', 'programs-module' ),
			'item_reverted_to_draft'   => __( 'Program reverted to draft.', 'programs-module' ),
			'item_scheduled'           => __( 'Program scheduled.', 'programs-module' ),
			'item_updated'             => __( 'Program updated.', 'programs-module' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'University programmes offered at undergraduate and postgraduate level.', 'programs-module' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'programs',
			'rest_namespace'      => 'wp/v2',
			'menu_position'       => 21,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'has_archive'         => self::REWRITE_SLUG,
			'rewrite'             => array(
				'slug'       => self::REWRITE_SLUG,
				'with_front' => false,
				'feeds'      => false,
				'pages'      => false,
			),
			'query_var'           => self::POST_TYPE,
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'can_export'          => true,
			'delete_with_user'    => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Registers the hierarchical `program-type` taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'                       => _x( 'Program Types', 'Taxonomy general name', 'programs-module' ),
			'singular_name'              => _x( 'Program Type', 'Taxonomy singular name', 'programs-module' ),
			'search_items'               => __( 'Search Program Types', 'programs-module' ),
			'popular_items'              => __( 'Popular Program Types', 'programs-module' ),
			'all_items'                  => __( 'All Program Types', 'programs-module' ),
			'parent_item'                => __( 'Parent Program Type', 'programs-module' ),
			'parent_item_colon'          => __( 'Parent Program Type:', 'programs-module' ),
			'edit_item'                  => __( 'Edit Program Type', 'programs-module' ),
			'view_item'                  => __( 'View Program Type', 'programs-module' ),
			'update_item'                => __( 'Update Program Type', 'programs-module' ),
			'add_new_item'               => __( 'Add New Program Type', 'programs-module' ),
			'new_item_name'              => __( 'New Program Type Name', 'programs-module' ),
			'separate_items_with_commas' => __( 'Separate program types with commas', 'programs-module' ),
			'add_or_remove_items'        => __( 'Add or remove program types', 'programs-module' ),
			'choose_from_most_used'      => __( 'Choose from the most used program types', 'programs-module' ),
			'not_found'                  => __( 'No program types found.', 'programs-module' ),
			'no_terms'                   => __( 'No program types', 'programs-module' ),
			'items_list_navigation'      => __( 'Program Types list navigation', 'programs-module' ),
			'items_list'                 => __( 'Program Types list', 'programs-module' ),
			'menu_name'                  => _x( 'Program Types', 'Admin menu text', 'programs-module' ),
		);

		$args = array(
			'labels'            => $labels,
			'description'       => __( 'Level of study, for example Undergraduate or Postgraduate.', 'programs-module' ),
			'public'            => true,
			'publicly_queryable' => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'program-types',
			'rest_namespace'    => 'wp/v2',
			'hierarchical'      => true,
			'query_var'         => true,
			'sort'              => false,
			'rewrite'           => array(
				'slug'         => self::TAXONOMY_REWRITE_SLUG,
				'with_front'   => false,
				'hierarchical' => false,
			),
		);

		register_taxonomy( self::TAXONOMY, array( self::POST_TYPE ), $args );
	}

	/**
	 * Returns the program type terms, ordered as they are displayed.
	 *
	 * Used instead of hardcoding term IDs anywhere in the codebase.
	 *
	 * @param bool $hide_empty Whether to skip terms with no programs. Default false.
	 * @return WP_Term[] Array of term objects, empty array on failure.
	 */
	public static function get_program_types( $hide_empty = false ) {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => (bool) $hide_empty,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		return $terms;
	}
}
