<?php
/**
 * Template for displaying program type archives (Undergraduate/Postgraduate)
 *
 * Matches the undergraduate.html / postgraduate.html design
 *
 * @package PROJECT
 */

get_header();

$current_term = get_queried_object();
$term_name    = $current_term->name;
$term_desc    = $current_term->description;
?>

<!-- TITLE BAND -->
<section class="title-band">
	<h1><?php echo esc_html( $term_name ); ?> Programmes</h1>
	<div class="motif"></div>
</section>

<!-- PROGRAMMES LISTING -->
<section class="programmes container">
	<?php if ( $term_desc ) : ?>
		<p class="intro"><?php echo esc_html( $term_desc ); ?></p>
	<?php else : ?>
		<p class="intro">Discover a campus that blends Scottish heritage with Indian innovation. A global education awaits!</p>
	<?php endif; ?>
	
	<?php if ( have_posts() ) : ?>
		<div class="grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php
				$program_id = get_the_ID();
				$facts      = project_program_facts( $program_id );
				$image_url  = has_post_thumbnail() ? get_the_post_thumbnail_url( $program_id, 'large' ) : '';
				
				// Fallback images if no featured image
				if ( ! $image_url ) {
					$image_url = ( 'undergraduate' === $current_term->slug ) 
						? get_template_directory_uri() . '/assets/images/bca.png'
						: get_template_directory_uri() . '/assets/images/engineering.png';
				}
				?>
				
				<a href="<?php the_permalink(); ?>" class="card">
					<div class="card-image">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
					</div>
					<div class="card-body">
						<div class="card-heading">
							<h2><?php the_title(); ?></h2>
							<div class="motif"></div>
						</div>
						<p>
							<?php
							if ( has_excerpt() ) {
								echo esc_html( get_the_excerpt() );
							} else {
								echo esc_html( wp_trim_words( get_the_content(), 20, '...' ) );
							}
							?>
						</p>
					</div>
				</a>

			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<p class="intro">No programmes found in this category.</p>
	<?php endif; ?>
</section>

<?php
get_footer();
