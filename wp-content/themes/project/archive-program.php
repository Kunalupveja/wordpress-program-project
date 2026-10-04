<?php
/**
 * The template for the programmes archive: /programmes
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package PROJECT
 */

get_header();
?>

	<main id="primary" class="site-main">
		<?php get_template_part( 'template-parts/programmes-listing' ); ?>
	</main><!-- #main -->

<?php
get_footer();
