<?php
/**
 * Custom frontend routing for programmes.
 *
 * @package Programs_Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Programs_Routing
 *
 * Owns the public URL structure:
 *
 *   /programmes                    all programs, grouped by type
 *   /programmes/undergraduate      undergraduate only
 *   /programmes/postgraduate       postgraduate only
 *   /programmes/{program-slug}     a single program
 *
 * The taxonomy itself has no rewrite base (see Programs_CPT), so there is
 * exactly one canonical URL per program type and it can never be shadowed
 * by a program whose slug happens to match.
 */
class Programs_Routing {

	/**
	 * Registers the hooks for this class.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'term_link', array( $this, 'filter_term_link' ), 10, 3 );
		add_filter( 'document_title_parts', array( $this, 'filter_document_title' ) );
		add_filter( 'body_class', array( $this, 'filter_body_class' ) );
	}

	/**
	 * Adds the programme rewrite rules.
	 *
	 * Registered at the top of the stack so the two fixed type URLs are
	 * matched before the generic single-program permastruct.
	 *
	 * @return void
	 */
	public function add_rewrite_rules() {
		$types = implode( '|', array_map( 'preg_quote', Programs_CPT::DEFAULT_TERMS ) );

		add_rewrite_rule(
			'^' . Programs_CPT::REWRITE_SLUG . '/(' . $types . ')/?$',
			'index.php?' . Programs_CPT::TAXONOMY . '=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^' . Programs_CPT::REWRITE_SLUG . '/(' . $types . ')/page/([0-9]{1,})/?$',
			'index.php?' . Programs_CPT::TAXONOMY . '=$matches[1]&paged=$matches[2]',
			'top'
		);
	}

	/**
	 * Points program-type term links at the pretty archive URLs.
	 *
	 * The taxonomy has no rewrite base of its own, so without this filter
	 * WordPress would fall back to a query-string URL.
	 *
	 * @param string $term_link The term permalink.
	 * @param WP_Term $term    The term object.
	 * @param string $taxonomy The taxonomy name.
	 * @return string
	 */
	public function filter_term_link( $term_link, $term, $taxonomy ) {
		if ( Programs_CPT::TAXONOMY !== $taxonomy || ! $term instanceof WP_Term ) {
			return $term_link;
		}

		return self::get_archive_url( $term->slug );
	}

	/**
	 * Makes the document title reflect the current programme context.
	 *
	 * @param array $parts The title parts.
	 * @return array
	 */
	public function filter_document_title( $parts ) {
		if ( is_singular( Programs_CPT::POST_TYPE ) ) {
			$parts['title'] = get_the_title();
			return $parts;
		}

		if ( is_post_type_archive( Programs_CPT::POST_TYPE ) ) {
			$parts['title'] = __( 'Programmes', 'programs-module' );
			return $parts;
		}

		if ( is_tax( Programs_CPT::TAXONOMY ) ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				/* translators: %s: program type name, e.g. Undergraduate. */
				$parts['title'] = sprintf( __( '%s programmes', 'programs-module' ), $term->name );
			}

			return $parts;
		}

		return $parts;
	}

	/**
	 * Adds context classes to the body element.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function filter_body_class( $classes ) {
		if ( is_tax( Programs_CPT::TAXONOMY ) ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				$classes[] = 'programmes-archive';
				$classes[] = 'programmes-type-' . sanitize_html_class( $term->slug );
			}
		}

		if ( is_post_type_archive( Programs_CPT::POST_TYPE ) ) {
			$classes[] = 'programmes-archive';
		}

		if ( is_singular( Programs_CPT::POST_TYPE ) ) {
			$classes[] = 'programmes-single';
		}

		return $classes;
	}

	/**
	 * Returns the URL of a programmes archive, optionally filtered by type.
	 *
	 * Always build URLs through this helper so nothing hardcodes a path.
	 *
	 * @param string $type_slug Optional program-type term slug.
	 * @return string
	 */
	public static function get_archive_url( $type_slug = '' ) {
		$base = home_url( '/' . Programs_CPT::REWRITE_SLUG . '/' );

		if ( '' === $type_slug || 'all' === $type_slug ) {
			return $base;
		}

		return $base . sanitize_title( $type_slug ) . '/';
	}

	/**
	 * Returns the REST URL the filter uses to ask the API which programs
	 * belong to a type.
	 *
	 * Uses the core REST collection for the post type rather than a custom
	 * route: the post type and taxonomy are already exposed with
	 * `show_in_rest`, and `_fields=id` keeps the response to the ids the
	 * store actually needs.
	 *
	 * @param string $type_slug Optional program-type term slug. Empty for all.
	 * @return string
	 */
	public static function get_rest_url( $type_slug = '' ) {
		$post_type = get_post_type_object( Programs_CPT::POST_TYPE );
		$rest_base = $post_type && ! empty( $post_type->rest_base )
			? $post_type->rest_base
			: Programs_CPT::POST_TYPE;

		$taxonomy = get_taxonomy( Programs_CPT::TAXONOMY );
		$tax_base = $taxonomy && ! empty( $taxonomy->rest_base )
			? $taxonomy->rest_base
			: Programs_CPT::TAXONOMY;

		$args = array(
			'per_page' => 100,
			'_fields'  => 'id',
		);

		if ( '' !== $type_slug && 'all' !== $type_slug ) {
			// The core posts controller validates this parameter as a term
			// ID list, so the slug has to be resolved to an id first.
			$term = get_term_by( 'slug', sanitize_title( $type_slug ), Programs_CPT::TAXONOMY );

			if ( $term instanceof WP_Term ) {
				$args[ $tax_base ] = (int) $term->term_id;
			}
		}

		return add_query_arg( $args, rest_url( 'wp/v2/' . $rest_base ) );
	}

	/**
	 * Returns the "Apply now" URL for a program.
	 *
	 * Never hardcoded: a host can point the whole site at a different apply
	 * flow with one filter. The default resolves the page whose slug is
	 * `apply` when it exists, so nothing 404s on a stock install once that
	 * page has been added.
	 *
	 * @param int $post_id Optional program ID.
	 * @return string
	 */
	public static function get_apply_url( $post_id = 0 ) {
		$url = '';

		$apply_page = get_page_by_path( 'apply' );

		if ( $apply_page ) {
			$url = get_permalink( $apply_page );
		}

		if ( ! $url ) {
			$url = home_url( '/apply/' );
		}

		/**
		 * Filters the Apply now URL.
		 *
		 * @param string $url     The apply URL.
		 * @param int    $post_id The program ID.
		 */
		return apply_filters( 'programs_apply_url', $url, $post_id );
	}

	/**
	 * Builds the breadcrumb trail for the current programmes context.
	 *
	 * @return array<int, array{label: string, url: string}> Breadcrumb items.
	 *                                                      The last item has
	 *                                                      an empty url.
	 */
	public static function get_breadcrumbs() {
		$items = array(
			array(
				'label' => __( 'Home', 'programs-module' ),
				'url'   => home_url( '/' ),
			),
		);

		if ( is_singular( Programs_CPT::POST_TYPE ) ) {
			$items[] = array(
				'label' => __( 'Programmes', 'programs-module' ),
				'url'   => self::get_archive_url(),
			);

			$terms = wp_get_object_terms( get_the_ID(), Programs_CPT::TAXONOMY );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$term = $terms[0];

				$items[] = array(
					'label' => $term->name,
					'url'   => self::get_archive_url( $term->slug ),
				);
			}

			$items[] = array(
				'label' => get_the_title(),
				'url'   => '',
			);

			return $items;
		}

		if ( is_tax( Programs_CPT::TAXONOMY ) ) {
			$term = get_queried_object();

			$items[] = array(
				'label' => __( 'Programmes', 'programs-module' ),
				'url'   => self::get_archive_url(),
			);

			if ( $term instanceof WP_Term ) {
				$items[] = array(
					'label' => $term->name,
					'url'   => '',
				);
			}

			return $items;
		}

		if ( is_post_type_archive( Programs_CPT::POST_TYPE ) ) {
			$items[] = array(
				'label' => __( 'Programmes', 'programs-module' ),
				'url'   => '',
			);
		}

		return $items;
	}
}
