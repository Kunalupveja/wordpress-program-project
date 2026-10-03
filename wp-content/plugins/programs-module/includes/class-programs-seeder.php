<?php
/**
 * Seeds the default Program Type terms and the sample programs.
 *
 * @package Programs_Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Programs_Seeder
 *
 * Creates the content required by Task 2: the two program types and ten
 * sample programs (five undergraduate, five postgraduate), each with a
 * featured image, a title and its meta values.
 *
 * Every method is idempotent: running it twice never duplicates content.
 */
class Programs_Seeder {

	/**
	 * Option recording which data version has already been seeded.
	 *
	 * @var string
	 */
	const SEEDED_OPTION = 'programs_module_seeded_version';

	/**
	 * Post meta key marking a program as sample data.
	 *
	 * Only posts carrying this flag are removed by `remove_sample_programs()`,
	 * so real content is never touched.
	 *
	 * @var string
	 */
	const SAMPLE_MARKER = '_programs_module_sample';

	/**
	 * The default terms, keyed by slug.
	 *
	 * @var array<string, array{name: string, description: string}>
	 */
	const DEFAULT_TERMS = array(
		'undergraduate' => array(
			'name'        => 'Undergraduate',
			'description' => 'Bachelors level programmes, typically three or four years of full-time study.',
		),
		'postgraduate'  => array(
			'name'        => 'Postgraduate',
			'description' => 'Masters level programmes for students who already hold an undergraduate degree.',
		),
	);

	/**
	 * Creates the default program type terms if they do not exist yet.
	 *
	 * Idempotent: an existing term is left untouched and its ID returned.
	 *
	 * @return array<string, int> Term IDs keyed by slug. Terms that could not
	 *                            be created are simply absent.
	 */
	public static function seed_terms() {
		$term_ids = array();

		foreach ( self::DEFAULT_TERMS as $slug => $term ) {
			$existing = get_term_by( 'slug', $slug, Programs_CPT::TAXONOMY );

			if ( $existing instanceof WP_Term ) {
				$term_ids[ $slug ] = (int) $existing->term_id;
				continue;
			}

			$inserted = wp_insert_term(
				$term['name'],
				Programs_CPT::TAXONOMY,
				array(
					'slug'        => $slug,
					'description' => $term['description'],
				)
			);

			if ( ! is_wp_error( $inserted ) ) {
				$term_ids[ $slug ] = (int) $inserted['term_id'];
			}
		}

		return $term_ids;
	}
}
