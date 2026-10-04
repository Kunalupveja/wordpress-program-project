<?php
/**
 * The template for a single program: /programmes/{slug}
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package PROJECT
 */

get_header();

$program_id = get_the_ID();
$type       = project_program_type( $program_id );
$facts      = project_program_facts( $program_id );
$sections   = get_post_meta( $program_id, Programs_CPT::META_CONTENT_SECTIONS, true );
$sections   = is_array( $sections ) ? $sections : array();

$track_type = function_exists( 'program_track_attr' )
	? program_track_attr( 'link', 'programme-type', 'programme-overview', 'componentLink' )
	: '';

$track_apply = function_exists( 'program_track_attr' )
	? program_track_attr( 'link', 'apply-now', 'programme-actions', 'componentLink' )
	: '';

$track_enquire = function_exists( 'program_track_attr' )
	? program_track_attr( 'button', 'enquire-now', 'programme-actions', 'componentButton' )
	: '';

$track_back = function_exists( 'program_track_attr' )
	? program_track_attr( 'link', 'back-to-programmes', 'programme-footer', 'componentLink' )
	: '';
?>

	<main id="primary" class="site-main">
		<article class="program-single">

			<?php get_template_part( 'template-parts/programmes-breadcrumbs' ); ?>

			<?php if ( has_post_thumbnail( $program_id ) ) : ?>
				<figure class="program-single__hero">
					<?php
					echo wp_get_attachment_image(
						get_post_thumbnail_id( $program_id ),
						'program-hero',
						false,
						array(
							'alt'     => trim( strip_tags( get_post_meta( get_post_thumbnail_id( $program_id ), '_wp_attachment_image_alt', true ) ) ) ?: get_the_title(),
							'loading' => 'eager',
						)
					);
					?>
				</figure>
			<?php endif; ?>

			<header class="program-single__header">
				<?php if ( $type ) : ?>
					<a class="program-single__type"
						href="<?php echo esc_url( Programs_Routing::get_archive_url( $type->slug ) ); ?>"
						<?php echo $track_type ? 'data-track="' . $track_type . '"' : ''; ?>>
						<?php echo esc_html( $type->name ); ?>
					</a>
				<?php endif; ?>

				<h1 class="program-single__title"><?php the_title(); ?></h1>

				<ul class="program-single__facts">
					<?php if ( '' !== $facts['year'] ) : ?>
						<li class="program-single__fact">
							<span class="program-single__fact-label"><?php esc_html_e( 'Academic year', 'project' ); ?></span>
							<span class="program-single__fact-value"><?php echo esc_html( $facts['year'] ); ?></span>
						</li>
					<?php endif; ?>

					<?php if ( '' !== $facts['duration'] ) : ?>
						<li class="program-single__fact">
							<span class="program-single__fact-label"><?php esc_html_e( 'Duration', 'project' ); ?></span>
							<span class="program-single__fact-value"><?php echo esc_html( $facts['duration'] ); ?></span>
						</li>
					<?php endif; ?>
				</ul>
			</header>

			<div class="program-single__content">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'project' ),
						'after'  => '</div>',
					)
				);
				?>
			</div>

			<div class="program-single__actions" data-wp-interactive="programs/enquiry">
				<a class="pm-button pm-button--secondary"
					href="<?php echo esc_url( Programs_Routing::get_apply_url( $program_id ) ); ?>"
					<?php echo $track_apply ? 'data-track="' . $track_apply . '"' : ''; ?>>
					<?php esc_html_e( 'Apply now', 'project' ); ?>
				</a>

				<a class="pm-button pm-button--primary"
					href="#programmes-enquiry"
					data-wp-context='<?php echo esc_attr( wp_json_encode( array( 'programId' => $program_id, 'programTitle' => get_the_title() ) ) ); ?>'
					data-wp-on--click="actions.open"
					<?php echo $track_enquire ? 'data-track="' . $track_enquire . '"' : ''; ?>>
					<?php esc_html_e( 'Enquire now', 'project' ); ?>
				</a>
			</div>

			<?php if ( ! empty( $sections ) ) : ?>
				<section class="program-single__section" aria-labelledby="program-sections-title">
					<h2 class="program-single__section-title" id="program-sections-title">
						<?php esc_html_e( 'What you will study', 'project' ); ?>
					</h2>

					<ul class="program-sections">
						<?php foreach ( $sections as $index => $section ) : ?>
							<?php
							$title       = isset( $section['title'] ) ? $section['title'] : '';
							$description = isset( $section['description'] ) ? $section['description'] : '';
							$image_id    = isset( $section['image'] ) ? absint( $section['image'] ) : 0;
							?>
							<li class="program-section">
								<?php if ( $image_id ) : ?>
									<div class="program-section__media">
										<?php
										echo wp_get_attachment_image(
											$image_id,
											'program-card',
											false,
											array(
												'alt'     => $title,
												'loading' => 0 === $index ? 'eager' : 'lazy',
											)
										);
										?>
									</div>
								<?php endif; ?>

								<div class="program-section__text">
									<?php if ( '' !== $title ) : ?>
										<h3 class="program-section__title"><?php echo esc_html( $title ); ?></h3>
									<?php endif; ?>

									<?php if ( '' !== $description ) : ?>
										<p class="program-section__description"><?php echo esc_html( $description ); ?></p>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php
			/*
			 * Related programmes: the rest of the same program type. Rendered
			 * with the same card component as the listing, so there is one
			 * card implementation in the theme.
			 */
			$related = new WP_Query(
				array(
					'post_type'           => Programs_CPT::POST_TYPE,
					'post_status'         => 'publish',
					'posts_per_page'      => 3,
					'post__not_in'        => array( $program_id ),
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'tax_query'           => $type ? array(
						array(
							'taxonomy' => Programs_CPT::TAXONOMY,
							'field'    => 'term_id',
							'terms'    => (int) $type->term_id,
						),
					) : array(),
				)
			);

			if ( $related->have_posts() ) :
				?>
				<section class="program-single__section" aria-labelledby="program-related-title">
					<h2 class="program-single__section-title" id="program-related-title">
						<?php esc_html_e( 'Related programmes', 'project' ); ?>
					</h2>

					<ul class="programmes__grid">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();

							get_template_part(
								'template-parts/program-card',
								'',
								array(
									'post'    => get_post(),
									'active'  => '', // Always visible here.
									'section' => 'programme-related',
								)
							);
						endwhile;
						?>
					</ul>
				</section>
				<?php
				wp_reset_postdata();
			endif;
			?>

			<a class="program-single__back"
				href="<?php echo esc_url( Programs_Routing::get_archive_url() ); ?>"
				<?php echo $track_back ? 'data-track="' . $track_back . '"' : ''; ?>>
				<?php esc_html_e( 'Back to programmes', 'project' ); ?>
			</a>
		</article>
	</main><!-- #main -->

<?php
get_footer();
