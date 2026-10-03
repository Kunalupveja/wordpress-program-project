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
 * Every seeding method is idempotent: running it twice never duplicates
 * content, and only posts flagged with SAMPLE_MARKER are ever removed.
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
	 * Registers the admin hooks that expose seeding from wp-admin.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_post_programs_module_seed', array( __CLASS__, 'handle_seed_request' ) );
		add_action( 'admin_post_programs_module_remove_sample', array( __CLASS__, 'handle_remove_request' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
	}

	/**
	 * Seeds terms and programs in one call.
	 *
	 * @param bool $force Whether to refresh programs that already exist.
	 * @return array{terms: int, programs: int} Counts of what was created.
	 */
	public static function seed_all( $force = false ) {
		$term_ids = self::seed_terms();
		$programs = self::seed_programs( $force );

		update_option( self::SEEDED_OPTION, PROGRAMS_MODULE_VERSION );

		return array(
			'terms'    => count( $term_ids ),
			'programs' => $programs,
		);
	}

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

	/**
	 * Creates the sample programs.
	 *
	 * Idempotent: a program whose slug already exists is skipped unless
	 * `$force` is true, in which case it is refreshed in place.
	 *
	 * @param bool $force Whether to refresh existing sample programs.
	 * @return int Number of programs created or updated.
	 */
	public static function seed_programs( $force = false ) {
		$count = 0;

		foreach ( self::get_sample_programs() as $program ) {
			$slug        = sanitize_title( $program['title'] );
			$existing_id = self::find_program_by_slug( $slug );

			if ( $existing_id && ! $force ) {
				continue;
			}

			$post_data = array(
				'post_type'    => Programs_CPT::POST_TYPE,
				'post_title'   => $program['title'],
				'post_name'    => $slug,
				'post_excerpt' => $program['excerpt'],
				'post_content' => $program['content'],
				'post_status'  => 'publish',
				'meta_input'   => array(
					Programs_CPT::META_ACADEMIC_YEAR   => $program['academic_year'],
					Programs_CPT::META_DURATION        => $program['duration'],
					Programs_CPT::META_CONTENT_SECTIONS => $program['sections'],
					self::SAMPLE_MARKER                => 1,
				),
			);

			if ( $existing_id ) {
				$post_data['ID'] = $existing_id;
				$post_id         = wp_update_post( wp_slash( $post_data ), true );
			} else {
				$post_id = wp_insert_post( wp_slash( $post_data ), true );
			}

			if ( is_wp_error( $post_id ) || 0 === $post_id ) {
				continue;
			}

			self::assign_program_type( $post_id, $program['type'] );

			$needs_image = $force || ! has_post_thumbnail( $post_id );
			if ( $needs_image ) {
				$attachment_id = self::generate_featured_image( $program, $slug, $post_id );

				if ( $attachment_id ) {
					set_post_thumbnail( $post_id, $attachment_id );
				}
			}

			++$count;
		}

		return $count;
	}

	/**
	 * Deletes only the programs this plugin created.
	 *
	 * @return int Number of programs removed.
	 */
	public static function remove_sample_programs() {
		$query = new WP_Query(
			array(
				'post_type'      => Programs_CPT::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::SAMPLE_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery -- seeded data only, runs on demand.
				'no_found_rows'  => true,
			)
		);

		$removed = 0;

		foreach ( $query->posts as $post_id ) {
			$thumbnail_id = get_post_thumbnail_id( $post_id );

			if ( $thumbnail_id ) {
				wp_delete_attachment( $thumbnail_id, true );
			}

			wp_delete_post( $post_id, true );
			++$removed;
		}

		delete_option( self::SEEDED_OPTION );

		return $removed;
	}

	/**
	 * Counts how many sample programs currently exist.
	 *
	 * @return int
	 */
	public static function count_sample_programs() {
		$query = new WP_Query(
			array(
				'post_type'      => Programs_CPT::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::SAMPLE_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery -- count only.
				'no_found_rows'  => false,
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Handles the "seed sample data" request from wp-admin.
	 *
	 * @return void
	 */
	public static function handle_seed_request() {
		self::assert_can_manage_sample_data( 'programs_module_seed' );

		$result  = self::seed_all( ! empty( $_POST['force'] ) );
		$redirect = add_query_arg(
			array(
				'post_type'         => Programs_CPT::POST_TYPE,
				'programs-seeded'   => $result['programs'],
				'programs-terms'    => $result['terms'],
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handles the "remove sample data" request from wp-admin.
	 *
	 * @return void
	 */
	public static function handle_remove_request() {
		self::assert_can_manage_sample_data( 'programs_module_remove_sample' );

		$removed  = self::remove_sample_programs();
		$redirect = add_query_arg(
			array(
				'post_type'       => Programs_CPT::POST_TYPE,
				'programs-removed' => $removed,
			),
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Renders the sample-data controls on the Programs and Plugins screens.
	 *
	 * @return void
	 */
	public static function render_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, array( 'edit-program', 'plugins' ), true ) ) {
			return;
		}

		$count = self::count_sample_programs();
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Programs sample data', 'programs-module' ); ?></strong>
				<?php
				printf(
					/* translators: %d: number of seeded sample programs. */
					esc_html__( '&mdash; %d sample program(s) present. Seeding is idempotent and only ever removes content it created.', 'programs-module' ),
					(int) $count
				);
				?>
			</p>
			<p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
					<input type="hidden" name="action" value="programs_module_seed" />
					<input type="hidden" name="force" value="1" />
					<?php wp_nonce_field( 'programs_module_seed' ); ?>
					<?php submit_button( __( 'Re-seed sample programs', 'programs-module' ), 'secondary', 'submit', false ); ?>
				</form>
				<?php if ( $count > 0 ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<input type="hidden" name="action" value="programs_module_remove_sample" />
						<?php wp_nonce_field( 'programs_module_remove_sample' ); ?>
						<?php submit_button( __( 'Remove sample programs', 'programs-module' ), 'delete', 'submit', false ); ?>
					</form>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Guards an admin-post request with a capability check and a nonce.
	 *
	 * @param string $nonce_action The nonce action to verify.
	 * @return void
	 */
	private static function assert_can_manage_sample_data( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You are not allowed to manage Programs sample data.', 'programs-module' ),
				esc_html__( 'Insufficient permissions', 'programs-module' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * Finds a program by its slug.
	 *
	 * @param string $slug The post slug.
	 * @return int Post ID, or 0 when not found.
	 */
	private static function find_program_by_slug( $slug ) {
		$query = new WP_Query(
			array(
				'post_type'      => Programs_CPT::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'name'           => $slug,
				'no_found_rows'  => true,
			)
		);

		if ( empty( $query->posts ) ) {
			return 0;
		}

		return (int) $query->posts[0];
	}

	/**
	 * Assigns a program type term to a program.
	 *
	 * @param int    $post_id The program ID.
	 * @param string $slug    The program-type term slug.
	 * @return void
	 */
	private static function assign_program_type( $post_id, $slug ) {
		$term = get_term_by( 'slug', $slug, Programs_CPT::TAXONOMY );

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		wp_set_object_terms( $post_id, array( (int) $term->term_id ), Programs_CPT::TAXONOMY, false );
	}

	/**
	 * Builds and attaches a generated featured image for a program.
	 *
	 * Uses GD, which is present in the target environment. If GD is missing
	 * the program is still created, just without a featured image.
	 *
	 * @param array  $program  The program definition.
	 * @param string $slug     The program slug, used for the filename.
	 * @param int    $post_id  The program the image belongs to.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private static function generate_featured_image( $program, $slug, $post_id ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$width  = 1200;
		$height = 800;
		$image  = imagecreatetruecolor( $width, $height );

		if ( false === $image ) {
			return 0;
		}

		$from  = self::hex_to_rgb( $program['accent'][0] );
		$to    = self::hex_to_rgb( $program['accent'][1] );
		$steps = max( 1, $height - 1 );

		for ( $y = 0; $y < $height; $y++ ) {
			$ratio  = $y / $steps;
			$colour = imagecolorallocate(
				$image,
				(int) round( $from[0] + ( $to[0] - $from[0] ) * $ratio ),
				(int) round( $from[1] + ( $to[1] - $from[1] ) * $ratio ),
				(int) round( $from[2] + ( $to[2] - $from[2] ) * $ratio )
			);

			imageline( $image, 0, $y, $width, $y, $colour );
		}

		// A translucent band at the bottom keeps the caption legible.
		$band = imagecolorallocatealpha( $image, 0, 0, 0, 70 );
		imagefilledrectangle( $image, 0, (int) ( $height * 0.68 ), $width, $height, $band );

		$white = imagecolorallocate( $image, 255, 255, 255 );
		$font  = self::get_font_path();

		if ( $font ) {
			// Level of study, e.g. UNDERGRADUATE.
			imagettftext( $image, 22, 0, 72, 110, $white, $font, strtoupper( $program['type_label'] ) );

			$line_height = 68;
			$y           = 210;

			foreach ( self::wrap_text( $program['title'], $font, 54, $width - 144 ) as $line ) {
				imagettftext( $image, 54, 0, 72, $y, $white, $font, $line );
				$y += $line_height;
			}

			// Academic year and duration.
			imagettftext(
				$image,
				24,
				0,
				72,
				$height - 72,
				$white,
				$font,
				$program['academic_year'] . '   |   ' . $program['duration']
			);
		} else {
			// No TrueType font available: fall back to GD's built-in font.
			imagestring( $image, 5, 72, 100, strtoupper( $program['type_label'] ), $white );
			imagestring( $image, 5, 72, 160, $program['title'], $white );
			imagestring( $image, 4, 72, $height - 90, $program['academic_year'] . ' | ' . $program['duration'], $white );
		}

		ob_start();
		imagepng( $image );
		$png = ob_get_clean();
		imagedestroy( $image );

		if ( ! is_string( $png ) || '' === $png ) {
			return 0;
		}

		$filename = sanitize_file_name( 'program-' . $slug . '.png' );
		$upload   = wp_upload_bits( $filename, null, $png );

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $upload['url'],
				'post_mime_type' => 'image/png',
				'post_title'     => $program['title'],
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$post_id
		);

		if ( 0 === $attachment_id || is_wp_error( $attachment_id ) ) {
			return 0;
		}

		// Alt text: the reusable card (Task 5) relies on it for accessibility.
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $program['title'] );
		update_post_meta( $attachment_id, self::SAMPLE_MARKER, 1 );

		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return (int) $attachment_id;
	}

	/**
	 * Converts a hex colour to an RGB triplet.
	 *
	 * @param string $hex Hex colour, with or without a leading "#".
	 * @return int[] Array of red, green, blue.
	 */
	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Locates a readable TrueType font for the generated images.
	 *
	 * Falls back to GD's built-in font when none is available, so seeding
	 * never fails just because a font is missing.
	 *
	 * @return string Absolute path to a font, or an empty string.
	 */
	private static function get_font_path() {
		$candidates = array(
			'C:/Windows/Fonts/arialbd.ttf',
			'C:/Windows/Fonts/arial.ttf',
			'/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
			'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
			'/Library/Fonts/Arial Bold.ttf',
			'/System/Library/Fonts/Supplemental/Arial Bold.ttf',
		);

		foreach ( $candidates as $candidate ) {
			if ( is_readable( $candidate ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Greedily wraps text to a pixel width using a TrueType font.
	 *
	 * @param string $text      The text to wrap.
	 * @param string $font      Path to the TrueType font.
	 * @param int    $size      Font size in points.
	 * @param int    $max_width Maximum line width in pixels.
	 * @return string[] The wrapped lines.
	 */
	private static function wrap_text( $text, $font, $size, $max_width ) {
		$lines  = array();
		$current = '';

		foreach ( preg_split( '/\s+/', trim( $text ) ) as $word ) {
			$candidate = '' === $current ? $word : $current . ' ' . $word;
			$box       = imagettfbbox( $size, 0, $font, $candidate );

			if ( $box && ( $box[2] - $box[0] ) > $max_width && '' !== $current ) {
				$lines[] = $current;
				$current = $word;
			} else {
				$current = $candidate;
			}
		}

		if ( '' !== $current ) {
			$lines[] = $current;
		}

		return $lines;
	}

	/**
	 * Returns the ten sample program definitions.
	 *
	 * Five undergraduate and five postgraduate. Each entry carries the
	 * title, level, academic year, duration, copy, content sections and the
	 * accent colours used to generate its featured image.
	 *
	 * @return array<int, array>
	 */
	private static function get_sample_programs() {
		return array(
			array(
				'title'         => 'Computer Science BSc',
				'type'          => 'undergraduate',
				'type_label'    => 'Undergraduate',
				'academic_year' => '2025-26',
				'duration'      => '3 Years',
				'accent'        => array( '1e3a8a', '3b82f6' ),
				'excerpt'       => 'Build the software, systems and algorithms behind modern computing.',
				'content'       => 'Our Computer Science BSc gives you a deep grounding in programming, algorithms, data structures and computer architecture, then lets you specialise in the areas that interest you most. You will work on real projects from your first year, learn to write software that scales, and graduate with a portfolio that shows employers what you can actually do.',
				'sections'      => array(
					array(
						'title'       => 'Year 1: Foundations',
						'description' => 'Programming fundamentals, discrete mathematics, computer architecture and an introduction to how the web works.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 2: Core practice',
						'description' => 'Data structures and algorithms, databases, operating systems and software engineering delivered as team projects.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 3: Specialisation',
						'description' => 'Choose from artificial intelligence, distributed systems, security or graphics, and finish with an individual capstone project.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Business Management BA',
				'type'          => 'undergraduate',
				'type_label'    => 'Undergraduate',
				'academic_year' => '2025-26',
				'duration'      => '3 Years',
				'accent'        => array( '78350f', 'f59e0b' ),
				'excerpt'       => 'Understand how organisations grow, compete and create value.',
				'content'       => 'The Business Management BA develops the analytical and leadership skills employers look for in graduate management roles. You will study strategy, marketing, operations and finance together rather than in isolation, and apply them to live case studies supplied by partner organisations.',
				'sections'      => array(
					array(
						'title'       => 'Year 1: Business fundamentals',
						'description' => 'Accounting, microeconomics, organisational behaviour and the principles of marketing.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 2: Functional depth',
						'description' => 'Operations management, human resource strategy, business analytics and financial decision making.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 3: Strategy in practice',
						'description' => 'A consultancy-style group project with a real organisation, plus an optional year-long industry placement.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Biomedical Sciences BSc',
				'type'          => 'undergraduate',
				'type_label'    => 'Undergraduate',
				'academic_year' => '2025-26',
				'duration'      => '3 Years',
				'accent'        => array( '134e4a', '14b8a6' ),
				'excerpt'       => 'Study the science that underpins modern medicine and healthcare.',
				'content'       => 'Biomedical Sciences BSc explores how the human body works and what happens when it does not. You will spend significant time in the laboratory, developing the practical techniques used in diagnostics, pharmacology and medical research.',
				'sections'      => array(
					array(
						'title'       => 'Year 1: Core biology',
						'description' => 'Cell biology, genetics, human anatomy and physiology, with weekly laboratory practicals.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 2: Disease and immunity',
						'description' => 'Immunology, microbiology, pathology and the molecular basis of disease.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 3: Research project',
						'description' => 'An extended laboratory research project, often published or presented at a student research conference.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Computer Science with Software Development BSc',
				'type'          => 'undergraduate',
				'type_label'    => 'Undergraduate',
				'academic_year' => '2025-26',
				'duration'      => '4 Years',
				'accent'        => array( '312e81', '6366f1' ),
				'excerpt'       => 'A four-year route with a full industrial placement year.',
				'content'       => 'This four-year variant of our Computer Science degree adds a structured industrial placement between your second and final year. You graduate with the same rigorous technical grounding plus a year of commercial experience and an industry reference.',
				'sections'      => array(
					array(
						'title'       => 'Years 1-2: Technical core',
						'description' => 'Programming, algorithms, databases and software engineering, taught alongside professional practice.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 3: Industrial placement',
						'description' => 'A paid placement with one of our partner employers, supported by the university placement team.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 4: Advanced practice',
						'description' => 'Architecture and design at scale, plus a substantial team project delivered with an external client.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Accounting and Finance BSc',
				'type'          => 'undergraduate',
				'type_label'    => 'Undergraduate',
				'academic_year' => '2026-27',
				'duration'      => '3 Years',
				'accent'        => array( '064e3b', '10b981' ),
				'excerpt'       => 'A professional-facing degree with significant exemptions from accountancy bodies.',
				'content'       => 'Accredited by the major professional bodies, this degree offers substantial exemptions from their professional examinations. You will master financial reporting, management accounting, audit and corporate finance in a course designed around real reporting standards.',
				'sections'      => array(
					array(
						'title'       => 'Year 1: Recording and reporting',
						'description' => 'Financial accounting, management accounting and the regulatory environment.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 2: Analysis and assurance',
						'description' => 'Audit, taxation, corporate finance and financial statement analysis.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 3: Professional practice',
						'description' => 'Advanced financial reporting, governance and a dissertation in an applied finance topic.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Accounting and Finance MSc',
				'type'          => 'postgraduate',
				'type_label'    => 'Postgraduate',
				'academic_year' => '2026-27',
				'duration'      => '1 Year',
				'accent'        => array( '065f46', '34d399' ),
				'excerpt'       => 'Advance into senior finance roles with a masters-level professional qualification.',
				'content'       => 'Designed for graduates who want to accelerate towards chartered status, this MSc deepens your technical accounting and finance expertise while building the strategic judgement senior roles demand. Teaching is delivered by academics and practising finance professionals.',
				'sections'      => array(
					array(
						'title'       => 'Term 1: Advanced reporting',
						'description' => 'International financial reporting standards, consolidation and complex group structures.',
						'image'       => 0,
					),
					array(
						'title'       => 'Term 2: Strategy and risk',
						'description' => 'Corporate finance, risk management, governance and strategic management accounting.',
						'image'       => 0,
					),
					array(
						'title'       => 'Term 3: Dissertation',
						'description' => 'An independent research dissertation, often based on a live problem supplied by an employer.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Computer Science MSc',
				'type'          => 'postgraduate',
				'type_label'    => 'Postgraduate',
				'academic_year' => '2026-27',
				'duration'      => '1 Year',
				'accent'        => array( '1e40af', '60a5fa' ),
				'excerpt'       => 'A conversion masters for graduates moving into computing.',
				'content'       => 'Whether you are deepening an existing computing background or converting from another discipline, this MSc takes you to an advanced level in software engineering, systems and research method, and finishes with a substantial individual project.',
				'sections'      => array(
					array(
						'title'       => 'Term 1: Advanced computing',
						'description' => 'Advanced algorithms, software architecture and research methods in computing.',
						'image'       => 0,
					),
					array(
						'title'       => 'Term 2: Specialist modules',
						'description' => 'Choose from machine learning, cloud and distributed systems, security or human-centred computing.',
						'image'       => 0,
					),
					array(
						'title'       => 'Term 3: Masters project',
						'description' => 'A substantial supervised project, frequently undertaken with an industrial partner.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Data Science MSc',
				'type'          => 'postgraduate',
				'type_label'    => 'Postgraduate',
				'academic_year' => '2026-27',
				'duration'      => '1 Year',
				'accent'        => array( '4c1d95', 'a78bfa' ),
				'excerpt'       => 'Turn large, messy datasets into decisions organisations can act on.',
				'content'       => 'Data Science MSc combines statistics, machine learning and engineering practice. You will work with real datasets at scale, learn to communicate findings to non-technical audiences, and consider the ethics of data-driven decision making throughout.',
				'sections'      => array(
					array(
						'title'       => 'Term 1: Foundations',
						'description' => 'Statistical modelling, programming for data and database systems at scale.',
						'image'       => 0,
					),
					array(
						'title'       => 'Term 2: Machine learning',
						'description' => 'Supervised and unsupervised learning, deep learning, and responsible data practice.',
						'image'       => 0,
					),
					array(
						'title'       => 'Term 3: Applied project',
						'description' => 'A capstone project solving a real analytical problem with an external partner.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Cyber Security MSc',
				'type'          => 'postgraduate',
				'type_label'    => 'Postgraduate',
				'academic_year' => '2026-27',
				'duration'      => '2 Years',
				'accent'        => array( '7f1d1d', 'f87171' ),
				'excerpt'       => 'Defend systems against the threats organisations actually face.',
				'content'       => 'Cyber Security MSc covers offensive and defensive security in equal measure, so you understand how attacks work before you are asked to prevent them. Laboratory exercises run in an isolated environment designed for safe, realistic practice.',
				'sections'      => array(
					array(
						'title'       => 'Year 1, term 1: Security foundations',
						'description' => 'Cryptography, network security, secure software development and threat modelling.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 1, term 2: Offensive and defensive',
						'description' => 'Penetration testing, digital forensics, incident response and security operations.',
						'image'       => 0,
					),
					array(
						'title'       => 'Year 2: Research dissertation',
						'description' => 'An extended dissertation in an area such as malware analysis, privacy or secure systems.',
						'image'       => 0,
					),
				),
			),
			array(
				'title'         => 'Business Analytics MSc',
				'type'          => 'postgraduate',
				'type_label'    => 'Postgraduate',
				'academic_year' => '2026-27',
				'duration'      => '1 Year',
				'accent'        => array( '7c2d12', 'fb923c' ),
				'excerpt'       => 'Bridge the gap between data teams and business decision makers.',
				'content'       => 'Business Analytics MSc is for graduates who want to lead data-informed decisions rather than build models alone. The course pairs analytical technique with the commercial and communication skills needed to make analysis stick.',
				'sections'      => array(
					array(
						'title'       => 'Term 1: Analytical toolkit',
						'description' => 'Statistics, optimisation, data visualisation and SQL for analysts.',
						'image'       => 0,
					),
					array(
						'title'       => 'Term 2: Decision making',
						'description' => 'Predictive analytics, simulation, and communicating analysis to senior stakeholders.',
						'image'       => 0,
					),
					array(
						'title'       => 'Term 3: Consulting project',
						'description' => 'A team consulting engagement delivering an analytical solution for a partner organisation.',
						'image'       => 0,
					),
				),
			),
		);
	}
}
