<?php
/**
 * Registers and renders the program custom fields.
 *
 * @package Programs_Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Programs_Meta
 *
 * Owns the program metadata: registration (with REST exposure), the React
 * admin panel that edits it, and validation/sanitisation on save.
 */
class Programs_Meta {

	/**
	 * Nonce action for the meta panel.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'programs_meta_save';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'programs_meta_nonce';

	/**
	 * Script handle for the admin panel.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'programs-meta-panel';

	/**
	 * Registers the hooks for this class.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . Programs_CPT::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registers the three program meta fields.
	 *
	 * All three are exposed over REST so the values are available to the
	 * admin panel and to any future headless consumer.
	 *
	 * @return void
	 */
	public function register_meta() {
		register_post_meta(
			Programs_CPT::POST_TYPE,
			Programs_CPT::META_ACADEMIC_YEAR,
			array(
				'type'              => 'string',
				'description'       => __( 'Academic year for this program, for example "2025-26".', 'programs-module' ),
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_text' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_program' ),
			)
		);

		register_post_meta(
			Programs_CPT::POST_TYPE,
			Programs_CPT::META_DURATION,
			array(
				'type'              => 'string',
				'description'       => __( 'Duration of this program, for example "3 Years".', 'programs-module' ),
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_text' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_program' ),
			)
		);

		register_post_meta(
			Programs_CPT::POST_TYPE,
			Programs_CPT::META_CONTENT_SECTIONS,
			array(
				'type'              => 'array',
				'description'       => __( 'Repeater content sections: title, description and an optional image.', 'programs-module' ),
				'single'            => true,
				'default'           => array(),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'title'       => array( 'type' => 'string' ),
								'description' => array( 'type' => 'string' ),
								'image'       => array( 'type' => 'integer' ),
							),
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_sections' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_program' ),
			)
		);
	}

	/**
	 * Adds the meta panel to the program edit screen.
	 *
	 * Replaces the default "Custom Fields" box so the React panel is the
	 * single place these values are edited.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'programs-meta-panel',
			__( 'Program details', 'programs-module' ),
			array( $this, 'render_meta_box' ),
			Programs_CPT::POST_TYPE,
			'normal',
			'high'
		);

		remove_meta_box( 'postcustom', Programs_CPT::POST_TYPE, 'normal' );
	}

	/**
	 * Renders the mount point and the hidden inputs the React panel writes to.
	 *
	 * The panel is progressively enhanced: the hidden inputs carry the
	 * current values, so the post still saves correctly even if the
	 * JavaScript bundle fails to load.
	 *
	 * @param WP_Post $post The post being edited.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$year     = get_post_meta( $post->ID, Programs_CPT::META_ACADEMIC_YEAR, true );
		$duration = get_post_meta( $post->ID, Programs_CPT::META_DURATION, true );
		$sections = get_post_meta( $post->ID, Programs_CPT::META_CONTENT_SECTIONS, true );

		if ( ! is_array( $sections ) ) {
			$sections = array();
		}

		$sections = self::sanitize_sections( $sections );
		?>
		<div id="programs-meta-root" class="programs-meta"></div>

		<?php // Without JavaScript these plain fields keep the panel usable. ?>
		<noscript>
			<div class="programs-meta programs-meta--fallback">
				<p class="programs-meta__notice">
					<?php esc_html_e( 'JavaScript is disabled, so the rich panel is unavailable. The values can still be edited below.', 'programs-module' ); ?>
				</p>
				<p>
					<label for="programs-academic-year"><?php esc_html_e( 'Academic year', 'programs-module' ); ?></label>
					<input type="text" id="programs-academic-year"
						name="<?php echo esc_attr( Programs_CPT::META_ACADEMIC_YEAR ); ?>"
						value="<?php echo esc_attr( (string) $year ); ?>" />
				</p>
				<p>
					<label for="programs-duration"><?php esc_html_e( 'Duration', 'programs-module' ); ?></label>
					<input type="text" id="programs-duration"
						name="<?php echo esc_attr( Programs_CPT::META_DURATION ); ?>"
						value="<?php echo esc_attr( (string) $duration ); ?>" />
				</p>
				<p>
					<label for="programs-content-sections"><?php esc_html_e( 'Content sections (JSON)', 'programs-module' ); ?></label>
					<textarea id="programs-content-sections" rows="6"
						name="<?php echo esc_attr( Programs_CPT::META_CONTENT_SECTIONS ); ?>"><?php echo esc_textarea( wp_json_encode( $sections ) ); ?></textarea>
				</p>
			</div>
		</noscript>
		<?php
	}

	/**
	 * Enqueues the compiled React panel.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || Programs_CPT::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// wp.media is used by the panel for the section images.
		wp_enqueue_media();

		$asset_file = PROGRAMS_MODULE_DIR . 'build/admin/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			PROGRAMS_MODULE_URL . 'build/admin/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		$style_path = PROGRAMS_MODULE_DIR . 'build/admin/style-index.css';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				self::SCRIPT_HANDLE,
				PROGRAMS_MODULE_URL . 'build/admin/style-index.css',
				array(),
				$asset['version']
			);
		}

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'ProgramsMeta',
			array(
				'academicYear' => (string) get_post_meta( get_the_ID(), Programs_CPT::META_ACADEMIC_YEAR, true ),
				'duration'     => (string) get_post_meta( get_the_ID(), Programs_CPT::META_DURATION, true ),
				'sections'     => self::sanitize_sections( (array) get_post_meta( get_the_ID(), Programs_CPT::META_CONTENT_SECTIONS, true ) ),
				'fieldYear'    => Programs_CPT::META_ACADEMIC_YEAR,
				'fieldDur'     => Programs_CPT::META_DURATION,
				'fieldSec'     => Programs_CPT::META_CONTENT_SECTIONS,
			)
		);
	}

	/**
	 * Validates and saves the program meta.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$year     = isset( $_POST[ Programs_CPT::META_ACADEMIC_YEAR ] ) ? wp_unslash( $_POST[ Programs_CPT::META_ACADEMIC_YEAR ] ) : '';
		$duration = isset( $_POST[ Programs_CPT::META_DURATION ] ) ? wp_unslash( $_POST[ Programs_CPT::META_DURATION ] ) : '';
		$sections = isset( $_POST[ Programs_CPT::META_CONTENT_SECTIONS ] ) ? wp_unslash( $_POST[ Programs_CPT::META_CONTENT_SECTIONS ] ) : '';

		update_post_meta( $post_id, Programs_CPT::META_ACADEMIC_YEAR, self::sanitize_text( $year ) );
		update_post_meta( $post_id, Programs_CPT::META_DURATION, self::sanitize_text( $duration ) );
		update_post_meta( $post_id, Programs_CPT::META_CONTENT_SECTIONS, self::sanitize_sections( self::decode_sections( $sections ) ) );
	}

	/**
	 * Decodes the sections payload, which arrives as JSON from the panel.
	 *
	 * @param mixed $raw Raw submitted value.
	 * @return array
	 */
	private static function decode_sections( $raw ) {
		if ( is_array( $raw ) ) {
			return $raw;
		}

		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return array();
		}

		$decoded = json_decode( wp_unslash( $raw ), true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Sanitises a single-line text value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_text( $value ) {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitises the content sections repeater.
	 *
	 * Drops anything that is not a well-formed section so malformed
	 * submissions cannot reach the database.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, array{title: string, description: string, image: int}>
	 */
	public static function sanitize_sections( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$title       = isset( $section['title'] ) ? sanitize_text_field( (string) $section['title'] ) : '';
			$description = isset( $section['description'] ) ? sanitize_textarea_field( (string) $section['description'] ) : '';
			$image       = isset( $section['image'] ) ? absint( $section['image'] ) : 0;

			// A section with no title and no body carries no information.
			if ( '' === $title && '' === $description && 0 === $image ) {
				continue;
			}

			$clean[] = array(
				'title'       => $title,
				'description' => $description,
				'image'       => $image,
			);
		}

		return $clean;
	}

	/**
	 * Capability check used by the meta auth callbacks.
	 *
	 * @param bool   $allowed   Whether the user can edit the meta.
	 * @param string $meta_key  The meta key.
	 * @param int    $object_id The post ID.
	 * @return bool
	 */
	public static function can_edit_program( $allowed, $meta_key, $object_id ) {
		return current_user_can( 'edit_post', $object_id );
	}
}
