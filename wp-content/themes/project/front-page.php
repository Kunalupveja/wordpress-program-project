<?php
/**
 * The front page: the "Home" screen from the Figma file.
 *
 * Flow: hero (cityscape) -> Courses and Qualifications -> Our Programmes
 * (one card per program type) -> footer.
 *
 * Images are pulled from the programmes themselves, so the page is never
 * empty and never ships a stock asset.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package PROJECT
 */

get_header();

$has_module = project_has_programs_module();
$terms      = $has_module ? Programs_CPT::get_program_types( true ) : array();

$intro_post = null;
$type_posts = array();

if ( $has_module ) {
	$intro_query = new WP_Query(
		array(
			'post_type'           => Programs_CPT::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);

	if ( $intro_query->have_posts() ) {
		$intro_post = $intro_query->posts[0];
	}

	// One representative image per program type for the Our Programmes cards.
	foreach ( $terms as $home_term ) {
		$type_query = new WP_Query(
			array(
				'post_type'           => Programs_CPT::POST_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => 1,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'tax_query'           => array(
					array(
						'taxonomy' => Programs_CPT::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => (int) $home_term->term_id,
					),
				),
			)
		);

		if ( $type_query->have_posts() ) {
			$type_posts[ $home_term->slug ] = $type_query->posts[0];
		}
	}
}
?>

	<main id="primary" class="site-main">

		<section class="home-hero">
			<?php // Decorative skyline: the city the campus sits in. ?>
			<svg class="home-hero__skyline" viewBox="0 0 1200 240" preserveAspectRatio="none" aria-hidden="true" focusable="false">
				<g fill="#0c2340">
					<rect x="40" y="120" width="70" height="120" />
					<rect x="125" y="70" width="58" height="170" />
					<rect x="196" y="140" width="46" height="100" />
					<rect x="255" y="40" width="64" height="200" />
					<rect x="332" y="110" width="52" height="130" />
					<rect x="398" y="160" width="74" height="80" />
					<rect x="486" y="20" width="56" height="220" />
					<rect x="556" y="100" width="60" height="140" />
					<rect x="630" y="150" width="48" height="90" />
					<rect x="692" y="60" width="68" height="180" />
					<rect x="774" y="130" width="54" height="110" />
					<rect x="842" y="90" width="62" height="150" />
					<rect x="918" y="155" width="44" height="85" />
					<rect x="976" y="50" width="70" height="190" />
					<rect x="1060" y="120" width="58" height="120" />
				</g>
				<g fill="#f4b942" opacity="0.5">
					<rect x="60" y="140" width="8" height="10" /><rect x="80" y="140" width="8" height="10" />
					<rect x="140" y="90" width="8" height="10" /><rect x="160" y="90" width="8" height="10" />
					<rect x="270" y="60" width="8" height="10" /><rect x="295" y="60" width="8" height="10" />
					<rect x="500" y="45" width="8" height="10" /><rect x="520" y="45" width="8" height="10" />
					<rect x="710" y="80" width="8" height="10" /><rect x="735" y="80" width="8" height="10" />
					<rect x="990" y="70" width="8" height="10" /><rect x="1015" y="70" width="8" height="10" />
				</g>
			</svg>

			<div class="home-hero__inner">
				<p class="home-hero__eyebrow"><?php esc_html_e( 'University of Sunderland', 'project' ); ?></p>
				<h1 class="home-hero__title"><?php esc_html_e( 'Join the University of Sunderland in Dubai', 'project' ); ?></h1>
				<p class="home-hero__text">
					<?php esc_html_e( 'Study a British degree in one of the world’s most connected cities. Choose from undergraduate and postgraduate programmes built around the industries that hire here.', 'project' ); ?>
				</p>
				<div class="home-hero__actions">
					<?php if ( $has_module ) : ?>
						<a class="pm-button pm-button--primary" href="<?php echo esc_url( Programs_Routing::get_archive_url() ); ?>">
							<?php esc_html_e( 'Explore programmes', 'project' ); ?>
						</a>
					<?php endif; ?>
					<a class="pm-button pm-button--secondary" href="#courses-and-qualifications">
						<?php esc_html_e( 'Courses and qualifications', 'project' ); ?>
					</a>
				</div>
			</div>
		</section>

		<section class="home-intro" id="courses-and-qualifications" aria-labelledby="courses-and-qualifications-title">
			<div class="home-intro__media">
				<?php
				if ( $intro_post && has_post_thumbnail( $intro_post->ID ) ) {
					echo wp_get_attachment_image(
						get_post_thumbnail_id( $intro_post->ID ),
						'large',
						false,
						array(
							'alt'     => '',
							'loading' => 'eager',
						)
					);
				}
				?>
			</div>

			<div class="home-intro__text-block">
				<p class="home-intro__eyebrow"><?php esc_html_e( 'Study with us', 'project' ); ?></p>
				<h2 class="home-intro__title" id="courses-and-qualifications-title">
					<?php esc_html_e( 'Courses and Qualifications', 'project' ); ?>
				</h2>
				<p class="home-intro__text">
					<?php esc_html_e( 'Every programme is taught to the same academic standard as our UK campus and validated by the University of Sunderland. You study in small groups, with lecturers who still work in the industries they teach, and you graduate with a degree recognised across the Gulf and beyond.', 'project' ); ?>
				</p>
				<?php if ( $has_module ) : ?>
					<a class="pm-button pm-button--primary" href="<?php echo esc_url( Programs_Routing::get_archive_url() ); ?>">
						<?php esc_html_e( 'Browse all courses', 'project' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( ! empty( $terms ) ) : ?>
			<section class="home-programmes" aria-labelledby="our-programmes-title">
				<div class="home-programmes__inner">
					<div class="home-section__head">
						<h2 class="home-section__title" id="our-programmes-title">
							<?php esc_html_e( 'Our Programmes', 'project' ); ?>
						</h2>
						<a class="home-section__link" href="<?php echo esc_url( Programs_Routing::get_archive_url() ); ?>">
							<?php esc_html_e( 'View all programmes', 'project' ); ?>
						</a>
					</div>

					<ul class="home-types">
						<?php foreach ( $terms as $home_term ) : ?>
							<?php
							$card_post = isset( $type_posts[ $home_term->slug ] ) ? $type_posts[ $home_term->slug ] : null;
							?>
							<li class="home-type">
								<?php if ( $card_post && has_post_thumbnail( $card_post->ID ) ) : ?>
									<div class="home-type__media">
										<?php
										echo wp_get_attachment_image(
											get_post_thumbnail_id( $card_post->ID ),
											'program-card',
											false,
											array(
												'alt'     => '',
												'loading' => 'lazy',
											)
										);
										?>
									</div>
								<?php endif; ?>

								<div class="home-type__body">
									<h3 class="home-type__title"><?php echo esc_html( $home_term->name ); ?></h3>
									<p class="home-type__text"><?php echo esc_html( $home_term->description ); ?></p>
									<a class="home-type__link"
										href="<?php echo esc_url( Programs_Routing::get_archive_url( $home_term->slug ) ); ?>"
										data-track="<?php echo esc_attr( program_track_attr( 'link', 'home-type-' . $home_term->slug, 'home-types', 'componentLink' ) ); ?>">
										<?php
										/* translators: %s: program type name, e.g. Undergraduate. */
										printf( esc_html__( 'View %s programmes', 'project' ), esc_html( $home_term->name ) );
										?>
									</a>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

	</main><!-- #main -->

<?php
get_footer();
