<?php
/**
 * Lead form ("Enquire now") endpoint.
 *
 * The popup itself is a Figma screen that the theme implements with the
 * Interactivity API. This class is the other half: a validated endpoint
 * that receives the lead.
 *
 * @package Programs_Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Programs_Enquiry
 *
 * Registers POST /wp-json/programs/v1/enquire.
 *
 * Deliberately not part of Programs_Tracking: the click log must never
 * contain personal data, while a lead is personal data by definition. Leads
 * go to their own file in the same protected directory so the two can be
 * governed (and deleted) separately.
 */
class Programs_Enquiry {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'programs/v1';

	/**
	 * REST route.
	 *
	 * @var string
	 */
	const REST_ROUTE = '/enquire';

	/**
	 * Maximum accepted body size in bytes.
	 *
	 * @var int
	 */
	const MAX_BODY_BYTES = 8192;

	/**
	 * Maximum submissions accepted per client per minute.
	 *
	 * @var int
	 */
	const RATE_LIMIT = 5;

	/**
	 * Number of days lead files are kept.
	 *
	 * @var int
	 */
	const RETENTION_DAYS = 90;

	/**
	 * Cron hook for lead file rotation.
	 *
	 * @var int
	 */
	const CRON_HOOK = 'programs_enquiry_cleanup';

	/**
	 * Maximum lengths for each text field.
	 *
	 * @var array<string, int>
	 */
	const MAX_LENGTHS = array(
		'name'        => 100,
		'email'       => 190,
		'phone'       => 40,
		'message'     => 2000,
		'program'     => 200,
		'countryCode' => 8,
	);

	/**
	 * Accepted country dialling codes.
	 *
	 * Anything not on the list is dropped, so the field cannot be used to
	 * smuggle arbitrary content into the lead file.
	 *
	 * @var string[]
	 */
	const DIAL_CODES = array( '+971', '+44', '+91', '+92', '+20', '+966', '+63', '+1' );

	/**
	 * Registers the hooks for this class.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( self::CRON_HOOK, array( $this, 'cleanup_old_leads' ) );
		add_action( 'init', array( $this, 'schedule_cleanup' ) );
	}

	/**
	 * Enqueues the enquiry pop-up module.
	 *
	 * The endpoint and nonce travel as data attributes on the form rather
	 * than as an inline script, so there is nothing to keep in sync and no
	 * extra global.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! is_singular( Programs_CPT::POST_TYPE ) && ! is_post_type_archive( Programs_CPT::POST_TYPE ) && ! is_tax( Programs_CPT::TAXONOMY ) ) {
			return;
		}

		$module = PROGRAMS_MODULE_DIR . 'build/frontend/enquiry.js';

		if ( ! file_exists( $module ) ) {
			return;
		}

		wp_enqueue_script_module(
			'programs-enquiry',
			PROGRAMS_MODULE_URL . 'build/frontend/enquiry.js',
			array( '@wordpress/interactivity' ),
			(string) PROGRAMS_MODULE_VERSION
		);
	}

	/**
	 * Registers POST /wp-json/programs/v1/enquire.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_enquiry' ),
				// Explicit callback. The route is public (visitors are not
				// logged in) and is protected by body size, field
				// validation and a rate limit instead. See README.
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Permission callback: public, but rate limited.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return true|WP_Error
	 */
	public function permissions_check( $request ) {
		if ( $this->is_rate_limited() ) {
			return new WP_Error(
				'programs_enquiry_rate_limited',
				__( 'Too many enquiries. Please try again later.', 'programs-module' ),
				array( 'status' => 429 )
			);
		}

		return true;
	}

	/**
	 * Whether the current client has exceeded the rate limit.
	 *
	 * The IP is hashed and never stored.
	 *
	 * @return bool
	 */
	private function is_rate_limited() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( '' === $ip ) {
			return false;
		}

		$key   = 'programs_enquiry_' . wp_hash( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_LIMIT ) {
			return true;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return false;
	}

	/**
	 * Validates and records a lead.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_enquiry( $request ) {
		$body = $request->get_body();

		if ( strlen( (string) $body ) > self::MAX_BODY_BYTES ) {
			return new WP_Error(
				'programs_enquiry_too_large',
				__( 'Enquiry payload too large.', 'programs-module' ),
				array( 'status' => 413 )
			);
		}

		$params = $request->get_json_params();

		if ( empty( $params ) || ! is_array( $params ) ) {
			return new WP_Error(
				'programs_enquiry_malformed',
				__( 'Enquiry payload is missing or malformed.', 'programs-module' ),
				array( 'status' => 400 )
			);
		}

		$name    = $this->read_field( $params, 'name' );
		$email   = $this->read_field( $params, 'email' );
		$phone   = $this->read_field( $params, 'phone' );
		$message = $this->read_field( $params, 'message' );
		$program = $this->read_field( $params, 'program' );

		$program_id    = isset( $params['programId'] ) ? absint( $params['programId'] ) : 0;
		$country_code  = $this->read_field( $params, 'countryCode' );

		if ( '' !== $country_code && ! in_array( $country_code, self::DIAL_CODES, true ) ) {
			$country_code = '';
		}

		$errors = array();

		// The pop-up asks which programme the enquiry is about; a lead with
		// no programme is not actionable.
		if ( $program_id <= 0 || Programs_CPT::POST_TYPE !== get_post_type( $program_id ) ) {
			$errors['programme'] = __( 'Please choose a programme.', 'programs-module' );
		}

		if ( '' === $name ) {
			$errors['name'] = __( 'Please enter your name.', 'programs-module' );
		}

		if ( '' === $email ) {
			$errors['email'] = __( 'Please enter your email address.', 'programs-module' );
		} elseif ( ! is_email( $email ) ) {
			$errors['email'] = __( 'Please enter a valid email address.', 'programs-module' );
		}

		if ( '' !== $phone && ! preg_match( '/^[0-9+()\s.-]{6,40}$/', $phone ) ) {
			$errors['phone'] = __( 'Please enter a valid phone number.', 'programs-module' );
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'programs_enquiry_invalid',
				__( 'Please check the highlighted fields.', 'programs-module' ),
				array(
					'status' => 400,
					'fields' => $errors,
				)
			);
		}

		$entry = array(
			'timestamp'     => gmdate( 'c' ),
			'program_id'    => $program_id,
			'program_title' => $program,
			'name'          => $name,
			'email'         => $email,
			'phone'         => '' === $phone ? '' : trim( $country_code . ' ' . $phone ),
			'message'       => $message,
		);

		/**
		 * Fires after a valid enquiry has been accepted.
		 *
		 * Hook here to hand the lead to a CRM. The lead is logged either
		 * way, so a failure downstream cannot silently drop it.
		 *
		 * @param array $entry The sanitized lead.
		 */
		do_action( 'programs_enquiry_accepted', $entry );

		$written = self::write_lead( $entry );

		if ( ! $written ) {
			return new WP_Error(
				'programs_enquiry_write_failed',
				__( 'We could not record your enquiry. Please try again.', 'programs-module' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'received' => true,
				'message'  => __( 'Thank you. We will be in touch shortly.', 'programs-module' ),
			),
			200
		);
	}

	/**
	 * Reads and sanitizes one text field from the payload.
	 *
	 * @param array  $params The decoded payload.
	 * @param string $key    The field name.
	 * @return string
	 */
	private function read_field( $params, $key ) {
		if ( ! isset( $params[ $key ] ) || ! is_scalar( $params[ $key ] ) ) {
			return '';
		}

		$value = sanitize_text_field( wp_unslash( (string) $params[ $key ] ) );
		$max   = isset( self::MAX_LENGTHS[ $key ] ) ? self::MAX_LENGTHS[ $key ] : 200;

		return substr( $value, 0, $max );
	}

	/**
	 * Appends one JSON line to today's lead file.
	 *
	 * @param array $entry The lead.
	 * @return bool True on success.
	 */
	public static function write_lead( $entry ) {
		$dir = Programs_Tracking::get_log_dir();

		if ( '' === $dir ) {
			return false;
		}

		$file = $dir . 'leads-' . gmdate( 'Y-m-d' ) . '.log';
		$line = wp_json_encode( $entry );

		if ( false === $line ) {
			return false;
		}

		$result = file_put_contents( $file, $line . "\n", FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return false !== $result;
	}

	/**
	 * Schedules the daily lead file cleanup.
	 *
	 * @return void
	 */
	public function schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Deletes lead files older than the retention period.
	 *
	 * @return int Number of files deleted.
	 */
	public function cleanup_old_leads() {
		$dir = Programs_Tracking::get_log_dir();

		if ( '' === $dir ) {
			return 0;
		}

		$deleted = 0;
		$cutoff  = time() - ( self::RETENTION_DAYS * DAY_IN_SECONDS );
		$files   = glob( $dir . 'leads-*.log' );

		if ( ! is_array( $files ) ) {
			return 0;
		}

		foreach ( $files as $file ) {
			if ( filemtime( $file ) < $cutoff ) {
				wp_delete_file( $file );
				++$deleted;
			}
		}

		return $deleted;
	}
}
