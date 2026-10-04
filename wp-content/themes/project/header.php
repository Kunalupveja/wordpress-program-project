<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package PROJECT
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'project' ); ?></a>

	<!-- HEADER - Aberdeen Mumbai Design -->
	<header class="header">
		<div class="name-bar">
			<div class="container">
				<a href="https://www.abdn.ac.uk/" target="_blank" rel="noopener">University of Aberdeen UK</a>
			</div>
		</div>
		<div class="top-bar">
			<div class="logo">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<div class="logo-crest">
						<div class="logo-crest-icon">
							<span style="font-size:9px; line-height:1.1;">UNIV<br>ABDN</span>
						</div>
						<div class="logo-text-block">
							<span class="logo-text-top">University of</span>
							<span class="logo-name">ABERDEEN</span>
							<span class="logo-sub">Mumbai</span>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<nav class="nav" aria-label="<?php esc_attr_e( 'Primary menu', 'project' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-1',
						'menu_id'        => 'primary-menu',
						'container'      => false,
						'fallback_cb'    => function() {
							// Default navigation if menu not set
							echo '<a href="' . esc_url( home_url( '/' ) ) . '">Study</a>';
							echo '<a href="' . esc_url( home_url( '/programmes' ) ) . '">Programmes</a>';
							echo '<a href="#">Admission</a>';
							echo '<a href="#">About Us</a>';
							echo '<a href="#">Alumni</a>';
							echo '<a href="#">Contact Us</a>';
						},
					)
				);
				?>
			</nav>
			<a href="<?php echo esc_url( home_url( '/apply' ) ); ?>" class="btn-apply">Apply Now</a>
		</div>
	</header><!-- .header -->
