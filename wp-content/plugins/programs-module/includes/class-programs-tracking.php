<?php
/**
 * Click tracking: data layer, REST endpoint and daily log files.
 *
 * @package Programs_Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Builds and escapes a click-tracking payload.
 *
 * Templates must not hand-write data-track; they call this helper.
 *
 * @param string $event     Either "link" or "button".
 * @param string $label     Short slug label, e.g. "apply-now".
 * @param string $section   Section name, e.g. "programme-overview".
 * @param string $component Component name, e.g. "componentLink".
 * @return string Escaped attribute value, ready to echo inside data-track.
 */
function program_track_attr( $event, $label, $section, $component ) {
	$event = in_array( $event, array( 'link', 'button' ), true ) ? $event : 'link';
	$key   = 'link' === $event ? 'link' : 'button';

	$payload = array(
		'type'         => 'click',
		'event'        => $event,
		$key           => array(
			'label' => Programs_Tracking::clean_token( $label ),
		),
		'content_data' => array(
			'sectionName' => Programs_Tracking::clean_token( $section ),
			'uiComponent' => Programs_Tracking::clean_token( $component ),
		),
	);

	return esc_attr( wp_json_encode( $payload ) );
}

/**
 * Class Programs_Tracking
 *
 * Registers the tracking REST route, validates payloads strictly, and
 * appends each accepted event to a daily JSON Lines log file.
 */
class Programs_Tracking {

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
	const REST_ROUTE = '/track';

	/**
	 * Maximum accepted body size in bytes.
	 *
	 * @var int
	 */
	const MAX_BODY_BYTES = 4096;

	/**
	 * Maximum events accepted per client per minute.
	 *
	 * @var int
	 */
	const RATE_LIMIT = 120;

	/**
	 * Number of days daily log files are kept.
	 *
	 * @var int
	 */
	const RETENTION_DAYS = 30;

	/**
	 * Cron hook for log rotation.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'programs_tracking_cleanup';

	/**
	 * Allowed event types.
	 *
	 * @var string[]
	 */
	const ALLOWED_EVENTS = array( 'link', 'button' );

	/**
	 * Allowed keys inside content_data. Anything else is dropped.
	 *
	 * @var string[]
	 */
	const ALLOWED_CONTENT_KEYS = array( 'sectionName', 'uiComponent' );

	/**
	 * Registers the hooks for this class.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( self::CRON_HOOK, array( $this, 'cleanup_old_logs' ) );
		add_action( 'init', array( $this, 'schedule_cleanup' ) );
	}

	/**
	 * Registers POST /wp-json/programs/v1/track.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_track' ),
				// Explicit callback, see the README for why this route is
				// public and what limits it.
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);
	}

	/**
	 * Enqueues the tracking script and the filter module data.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$asset_file = PROGRAMS_MODULE_DIR . 'build/frontend/tracking.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'programs-tracking',
			PROGRAMS_MODULE_URL . 'build/frontend/tracking.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_localize_script(
			'programs-tracking',
			'ProgramsTracking',
			array(
				'endpoint' => rest_url( self::REST_NAMESPACE . self::REST_ROUTE ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			)
		);

		if ( ! self::is_programmes_context() ) {
			return;
		}

		// The filter module reads its initial state from this global.
		wp_add_inline_script(
			'programs-tracking',
			'window.ProgramsFilter = ' . wp_json_encode(
				array(
					'filter' => self::current_filter(),
					'values' => array_merge( array( 'all' ), Programs_CPT::DEFAULT_TERMS ),
				)
			) . ';',
			'before'
		);

		wp_enqueue_script_module(
			'programs-filter',
			PROGRAMS_MODULE_URL . 'build/frontend/filter.js',
			array( '@wordpress/interactivity' ),
			(string) $asset['version']
		);
	}

	/**
	 * Whether the current request is a programmes listing.
	 *
	 * @return bool
	 */
	private static function is_programmes_context() {
		return is_post_type_archive( Programs_CPT::POST_TYPE ) || is_tax( Programs_CPT::TAXONOMY );
	}

	/**
	 * The program type currently being viewed, or "all".
	 *
	 * @return string
	 */
	private static function current_filter() {
		if ( is_tax( Programs_CPT::TAXONOMY ) ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				return $term->slug;
			}
		}

		return 'all';
	}

	/**
	 * Permission callback: public, but rate limited.
	 *
	 * The endpoint has to be callable by anonymous visitors, otherwise
	 * tracking would never fire. It is protected instead by strict payload
	 * validation, a body size cap and a per-client rate limit. See README.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return true|WP_Error
	 */
	public function permissions_check( $request ) {
		if ( $this->is_rate_limited() ) {
			return new WP_Error(
				'programs_track_rate_limited',
				__( 'Too many tracking events.', 'programs-module' ),
				array( 'status' => 429 )
			);
		}

		return true;
	}

	/**
	 * Whether the current client has exceeded the rate limit.
	 *
	 * The IP is hashed and never stored, so no personal data is retained.
	 *
	 * @return bool
	 */
	private function is_rate_limited() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( '' === $ip ) {
			return false;
		}

		$key   = 'programs_track_' . wp_hash( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_LIMIT ) {
			return true;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return false;
	}

	/**
	 * Validates and logs a click event.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_track( $request ) {
		$body = $request->get_body();

		if ( strlen( (string) $body ) > self::MAX_BODY_BYTES ) {
			return new WP_Error(
				'programs_track_too_large',
				__( 'Tracking payload too large.', 'programs-module' ),
				array( 'status' => 413 )
			);
		}

		$params = $request->get_json_params();

		if ( empty( $params ) || ! is_array( $params ) ) {
			return new WP_Error(
				'programs_track_malformed',
				__( 'Tracking payload is missing or malformed.', 'programs-module' ),
				array( 'status' => 400 )
			);
		}

		// type must be exactly "click".
		if ( ! isset( $params['type'] ) || 'click' !== $params['type'] ) {
			return new WP_Error(
				'programs_track_bad_type',
				__( 'Invalid tracking type.', 'programs-module' ),
				array( 'status' => 400 )
			);
		}

		// event must be one of the allowed values.
		if ( empty( $params['event'] ) || ! in_array( $params['event'], self::ALLOWED_EVENTS, true ) ) {
			return new WP_Error(
				'programs_track_bad_event',
				__( 'Invalid tracking event.', 'programs-module' ),
				array( 'status' => 400 )
			);
		}

		$event = $params['event'];
		$key   = 'link' === $event ? 'link' : 'button';

		// label: short sanitized slug.
		$label_source = isset( $params[ $key ]['label'] ) ? $params[ $key ]['label'] : '';

		if ( ! is_string( $label_source ) ) {
			return new WP_Error(
				'programs_track_bad_label',
				__( 'Invalid tracking label.', 'programs-module' ),
				array( 'status' => 400 )
			);
		}

		$label = substr( self::clean_token( $label_source ), 0, 64 );

		if ( '' === $label ) {
			return new WP_Error(
				'programs_track_bad_label',
				__( 'Invalid tracking label.', 'programs-module' ),
				array( 'status' => 400 )
			);
		}

		// content_data: sanitized strings, unknown keys dropped.
		$content_data = array();
		$incoming     = isset( $params['content_data'] ) && is_array( $params['content_data'] )
			? $params['content_data']
			: array();

		foreach ( self::ALLOWED_CONTENT_KEYS as $allowed_key ) {
			if ( ! isset( $incoming[ $allowed_key ] ) ) {
				continue;
			}

			if ( ! is_scalar( $incoming[ $allowed_key ] ) ) {
				continue;
			}

			$content_data[ $allowed_key ] = substr(
				sanitize_text_field( (string) $incoming[ $allowed_key ] ),
				0,
				200
			);
		}

		$payload = array(
			'type'         => 'click',
			'event'        => $event,
			$key           => array( 'label' => $label ),
			'content_data' => $content_data,
		);

		$entry = array(
			'timestamp' => gmdate( 'c' ),
			'page_url'  => self::current_page_path(),
			'payload'   => $payload,
		);

		$written = self::write_log_line( $entry );

		if ( ! $written ) {
			return new WP_Error(
				'programs_track_write_failed',
				__( 'Could not write the tracking log.', 'programs-module' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response( array( 'logged' => true ), 200 );
	}

	/**
	 * Reduces a tracking token to a safe, readable slug.
	 *
	 * Unlike `sanitize_key()` this preserves case, so the analytics
	 * vocabulary stays readable: "componentLink" is not flattened to
	 * "componentlink", which would silently break downstream grouping.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function clean_token( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', sanitize_text_field( (string) $value ) );
	}

	/**
	 * Derives the current page path from the Referer header.
	 *
	 * Only the path is kept, so no query strings (which may carry personal
	 * data) reach the log.
	 *
	 * @return string
	 */
	private static function current_page_path() {
		if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
			return '';
		}

		$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		$path    = wp_parse_url( $referer, PHP_URL_PATH );

		return is_string( $path ) ? $path : '';
	}

	/**
	 * Returns the directory log files are written to, creating it if needed.
	 *
	 * Filterable so a host can move it outside the web root entirely.
	 *
	 * @return string Absolute path, or an empty string on failure.
	 */
	public static function get_log_dir() {
		$upload_dir = wp_upload_dir();
		$base       = ! empty( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : WP_CONTENT_DIR . '/uploads';
		$dir        = trailingslashit( $base ) . 'programs-tracking';

		/**
		 * Filters the directory tracking logs are written to.
		 *
		 * @param string $dir Absolute path to the log directory.
		 */
		$dir = apply_filters( 'programs_tracking_log_dir', $dir );

		if ( ! is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return '';
			}

			self::protect_log_dir( $dir );
		}

		return trailingslashit( $dir );
	}

	/**
	 * Writes the deny rules that keep the log directory private.
	 *
	 * @param string $dir The log directory.
	 * @return void
	 */
	private static function protect_log_dir( $dir ) {
		$htaccess = trailingslashit( $dir ) . '.htaccess';

		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "# Deny access to tracking logs.\n<Files \"*\">\nRequire all denied\n</Files>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		$index = trailingslashit( $dir ) . 'index.php';

		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		$web_config = trailingslashit( $dir ) . 'web.config';

		if ( ! file_exists( $web_config ) ) {
			file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions
				$web_config,
				"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<configuration>\n  <system.webServer>\n    <authorization>\n      <deny users=\"*\" />\n    </authorization>\n  </system.webServer>\n</configuration>\n"
			);
		}
	}

	/**
	 * Appends one JSON line to today's log file.
	 *
	 * @param array $entry The log entry.
	 * @return bool True on success.
	 */
	public static function write_log_line( $entry ) {
		$dir = self::get_log_dir();

		if ( '' === $dir ) {
			return false;
		}

		$file = $dir . 'tracking-' . gmdate( 'Y-m-d' ) . '.log';
		$line = wp_json_encode( $entry );

		if ( false === $line ) {
			return false;
		}

		$result = file_put_contents( $file, $line . "\n", FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( false === $result ) {
			error_log( 'Programs tracking: failed to write to ' . $file ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			return false;
		}

		return true;
	}

	/**
	 * Schedules the daily log cleanup.
	 *
	 * @return void
	 */
	public function schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Deletes log files older than the retention period.
	 *
	 * @return int Number of files deleted.
	 */
	public function cleanup_old_logs() {
		$dir = self::get_log_dir();

		if ( '' === $dir ) {
			return 0;
		}

		$deleted  = 0;
		$cutoff   = time() - ( self::RETENTION_DAYS * DAY_IN_SECONDS );
		$patterns = glob( $dir . 'tracking-*.log' );

		if ( ! is_array( $patterns ) ) {
			return 0;
		}

		foreach ( $patterns as $file ) {
			if ( filemtime( $file ) < $cutoff ) {
				wp_delete_file( $file );
				++$deleted;
			}
		}

		return $deleted;
	}
}
