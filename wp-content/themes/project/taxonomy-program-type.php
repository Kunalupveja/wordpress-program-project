<?php
/**
 * The template for a program type archive: /programmes/undergraduate
 *
 * Renders the same listing component as the programmes archive so the two
 * URLs can never drift apart. The component reads the queried term and
 * pre-selects it.
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
