<?php
/**
 * PROJECT functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package PROJECT
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function project_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on PROJECT, use a find and replace
		* to change 'project' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'project', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'project' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'project_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'project_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function project_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'project_content_width', 640 );
}
add_action( 'after_setup_theme', 'project_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function project_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'project' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'project' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'project_widgets_init' );

/**
 * Renders the site logo, falling back to the bundled brand mark, then a
 * wordmark.
 *
 * Priority: the customiser logo, the theme's own assets/images/logo.png
 * (the same brand mark the reference build ships), and finally a wordmark
 * built from the site name so the header never looks unfinished.
 *
 * @return void
 */
function project_site_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}

	$bundled = get_template_directory() . '/assets/images/logo.png';

	if ( file_exists( $bundled ) ) {
		?>
		<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img class="site-logo__image"
				src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>"
				alt="<?php echo esc_attr( get_bloginfo( 'name' ) . ' Mumbai' ); ?>"
				width="180" height="63" />
		</a>
		<?php
		return;
	}

	$name = get_bloginfo( 'name' );
	?>
	<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<span class="site-logo__mark" aria-hidden="true"><?php echo esc_html( mb_substr( $name, 0, 1 ) ); ?></span>
		<span class="site-logo__text">
			<span class="site-logo__name"><?php echo esc_html( $name ); ?></span>
			<span class="site-logo__place"><?php esc_html_e( 'Mumbai', 'project' ); ?></span>
		</span>
	</a>
	<?php
}

/**
 * Enqueue scripts and styles.
 */
function project_scripts() {
	// Base theme stylesheet
	wp_enqueue_style( 'project-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'project-style', 'rtl', 'replace' );
	
	// Aberdeen Mumbai design system (from HTML/CSS build) - MAIN STYLES
	wp_enqueue_style( 'aberdeen-design', get_template_directory_uri() . '/assets/css/aberdeen-design.css', array( 'project-style' ), _S_VERSION );

	// Programs module styles: listing, filter bar, cards, single programme,
	// enquiry popup and mobile sticky CTA. Shares the Aberdeen design tokens
	// so it cannot drift from the base system.
	if ( project_has_programs_module() ) {
		wp_enqueue_style( 'programs-css', get_template_directory_uri() . '/assets/css/programs.css', array( 'aberdeen-design' ), _S_VERSION );
	}

	wp_enqueue_script( 'project-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'project_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Programme presentation helpers.
 *
 * Loaded last so it can rely on the plugin's classes being registered
 * (plugins load before themes).
 */
require get_template_directory() . '/inc/programs.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

