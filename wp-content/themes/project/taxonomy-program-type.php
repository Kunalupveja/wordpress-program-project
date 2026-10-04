<?php
/**
 * Template for displaying program type archives (Undergraduate/Postgraduate)
 *
 * Matches the undergraduate.html / postgraduate.html design: a navy title
 * band, then the same reusable programme cards used everywhere else.
 *
 * @package PROJECT
 */

get_header();

$current_term = get_queried_object();
$term_name    = $current_term instanceof WP_Term ? $current_term->name : '';
$term_desc    = $current_term instanceof WP_Term ? $current_term->description : '';
?>

<!-- TITLE BAND -->
<section class="title-band">
	<h1><?php echo esc_html( $term_name ); ?> Programmes</h1>
	<div class="motif"></div>
</section>

<!-- PROGRAMMES LISTING -->
<section class="programmes-page">
	<?php if ( $term_desc ) : ?>
		<p class="intro"><?php echo esc_html( $term_desc ); ?></p>
	<?php else : ?>
		<p class="intro">Discover a campus that blends Scottish heritage with Indian innovation. A global education awaits!</p>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<ul class="programmes__grid">
			<?php
			while ( have_posts() ) :
				the_post();

				get_template_part(
					'template-parts/program-card',
					'',
					array(
						'post'    => get_post(),
						'active'  => '', // Already filtered by the archive.
						'section' => 'programme-listing',
					)
				);
			endwhile;
			?>
		</ul>
	<?php else : ?>
		<p class="intro">No programmes found in this category.</p>
	<?php endif; ?>
</section>

<?php
get_footer();
