<?php
/**
 * The front page - Aberdeen Mumbai Homepage
 *
 * Matches the index.html design:
 * - Hero slider with Mumbai skyline
 * - Careers and Employability section
 * - Our Programmes (Undergraduate and Postgraduate cards)
 *
 * @package PROJECT
 */

get_header();

$has_module = project_has_programs_module();
$terms      = $has_module ? Programs_CPT::get_program_types( true ) : array();

// Get featured programmes for cards
$type_posts = array();
if ( $has_module ) {
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

<!-- HERO SLIDER -->
<section class="hero">
	<img
		class="hero-img"
		src="https://images.unsplash.com/photo-1567157577867-05ccb1388e66?w=1800&q=80"
		alt="Mumbai skyline"
	>
	<button class="slider-arrow left" aria-label="Previous slide">
		<svg class="icon" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>
	</button>
	<button class="slider-arrow right" aria-label="Next slide">
		<svg class="icon" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
	</button>
	<div class="hero-overlay">
		<h1 class="hero-title">Join the University of Aberdeen in India</h1>
		<p class="hero-sub">
			Global challenges are waiting for new ways of thinking. Discover your true north and start creating a more inclusive and sustainable world
		</p>
		<div class="hero-dots">
			<div class="dot active"></div>
			<div class="dot"></div>
			<div class="dot"></div>
		</div>
	</div>
</section>

<!-- CAREERS & EMPLOYABILITY -->
<section class="careers">
	<div class="section-inner">
		<div class="section-heading-wrap">
			<h2 class="section-heading">Careers and Employability</h2>
			<div class="motif"></div>
		</div>
		<div class="careers-body">
			<div class="careers-image">
				<img
					src="https://images.unsplash.com/photo-1730173737002-67e5b10d1182?fit=crop&w=1080&q=80"
					alt="Students on campus"
				>
			</div>
			<div class="careers-text">
				<p>
					The University of Aberdeen has been educating students for over 525 years, with a reputation for excellence in teaching and research. Our Mumbai campus brings this legacy to India, offering world-class education with strong industry connections.
					<br><br>
					Our Career Services team works closely with students and graduates to help them achieve their career goals through workshops, internships, and employer connections. We have partnerships with leading companies across various sectors.
				</p>
				<div>
					<a href="<?php echo esc_url( home_url( '/careers' ) ); ?>" class="btn-see-more">See More</a>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- OUR PROGRAMMES -->
<section class="programmes">
	<div class="programmes-heading-wrap">
		<h2 class="programmes-heading">Our Programmes</h2>
		<div class="motif-centered"></div>
	</div>
	<div class="programmes-grid">

		<?php if ( $has_module && ! empty( $terms ) ) : ?>
			<?php foreach ( $terms as $programme_term ) : ?>
				<?php
				$term_slug = $programme_term->slug;
				$term_post = isset( $type_posts[ $term_slug ] ) ? $type_posts[ $term_slug ] : null;
				$term_url  = Programs_Routing::get_archive_url( $term_slug );
				
				// Get image from featured programme or use placeholder
				$image_url = 'https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=700&q=80';
				if ( $term_post && has_post_thumbnail( $term_post->ID ) ) {
					$image_url = get_the_post_thumbnail_url( $term_post->ID, 'large' );
				} elseif ( 'postgraduate' === $term_slug ) {
					$image_url = 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=700&q=80';
				}
				?>

				<!-- <?php echo esc_html( $programme_term->name ); ?> -->
				<div class="prog-card">
					<div class="prog-card-image">
						<img
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $programme_term->name ); ?> Programmes"
						>
					</div>
					<div class="prog-card-body">
						<div class="prog-card-heading-wrap">
							<h3 class="prog-card-heading"><?php echo esc_html( $programme_term->name ); ?> Programmes</h3>
							<div class="motif-gold"></div>
						</div>
						<p class="prog-card-text">
							<?php
							if ( $programme_term->description ) {
								echo esc_html( $programme_term->description );
							} else {
								echo esc_html( sprintf(
									'Explore our %s degree programmes designed to prepare you for success in your chosen field.',
									strtolower( $programme_term->name )
								) );
							}
							?>
						</p>
						<div class="prog-card-footer">
							<a href="<?php echo esc_url( $term_url ); ?>" style="color:inherit;text-decoration:none">
								<span>See More</span>
							</a>
							<svg class="icon" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
						</div>
					</div>
				</div>

			<?php endforeach; ?>
		<?php else : ?>
			
			<!-- Fallback: Default cards if no programmes exist -->
			<div class="prog-card">
				<div class="prog-card-image">
					<img
						src="https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=700&q=80"
						alt="Undergraduate Programmes"
					>
				</div>
				<div class="prog-card-body">
					<div class="prog-card-heading-wrap">
						<h3 class="prog-card-heading">Undergraduate Programmes</h3>
						<div class="motif-gold"></div>
					</div>
					<p class="prog-card-text">
						Explore our undergraduate degree programmes designed to prepare you for success in your chosen field.
					</p>
					<div class="prog-card-footer">
						<a href="<?php echo esc_url( home_url( '/programmes/undergraduate' ) ); ?>" style="color:inherit;text-decoration:none">
							<span>See More</span>
						</a>
						<svg class="icon" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
					</div>
				</div>
			</div>

			<div class="prog-card">
				<div class="prog-card-image">
					<img
						src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=700&q=80"
						alt="Postgraduate Programmes"
					>
				</div>
				<div class="prog-card-body">
					<div class="prog-card-heading-wrap">
						<h3 class="prog-card-heading">Postgraduate Programmes</h3>
						<div class="motif-gold"></div>
					</div>
					<p class="prog-card-text">
						Advance your career with our postgraduate programmes taught by industry experts and leading academics.
					</p>
					<div class="prog-card-footer">
						<a href="<?php echo esc_url( home_url( '/programmes/postgraduate' ) ); ?>" style="color:inherit;text-decoration:none">
							<span>See More</span>
						</a>
						<svg class="icon" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
					</div>
				</div>
			</div>

		<?php endif; ?>

	</div>
</section>

<?php
get_footer();
