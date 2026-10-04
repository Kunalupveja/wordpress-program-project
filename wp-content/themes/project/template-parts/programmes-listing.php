<?php
/**
 * The programme listing: filter, grid, loading and empty states.
 *
 * Shared by archive-program.php and taxonomy-program-type.php so both
 * URLs render the same component and cannot drift apart.
 *
 * The whole set of programs is always rendered. Cards outside the active
 * filter carry the `hidden` attribute in the HTML, so with JavaScript
 * disabled each URL already shows exactly the right subset; with
 * JavaScript the Interactivity store re-evaluates visibility per card and
 * additionally asks the REST API which ids belong to the filter.
 *
 * @package PROJECT
 */

if ( ! project_has_programs_module() ) {
	return;
}

$terms  = Programs_CPT::get_program_types( true );
$active = 'all';

if ( is_tax( Programs_CPT::TAXONOMY ) ) {
	$queried = get_queried_object();

	if ( $queried instanceof WP_Term ) {
		$active = $queried->slug;
	}
}

$counts = array(
	'all' => 0,
);

foreach ( $terms as $term ) {
	$counts[ $term->slug ] = (int) $term->count;
	$counts['all']        += (int) $term->count;
}

// Filters are derived from the terms themselves, never hardcoded.
$names = array();

foreach ( $terms as $term ) {
	$names[ $term->slug ] = $term->name;
}

$filters = array_merge( array( 'all' ), array_keys( $names ) );

$programs = new WP_Query(
	array(
		'post_type'      => Programs_CPT::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
		'ignore_sticky_posts' => true,
	)
);
?>
<div class="programmes"
	data-wp-interactive="programs/filter"
	data-wp-context='<?php echo esc_attr( wp_json_encode( array( 'counts' => $counts ) ) ); ?>'
	data-wp-class--is-loading="state.isLoading">

	<?php get_template_part( 'template-parts/programmes-breadcrumbs' ); ?>

	<header class="programmes__intro">
		<?php if ( is_tax( Programs_CPT::TAXONOMY ) ) : ?>
			<h1 class="programmes__title">
				<?php
				$queried = get_queried_object();
				/* translators: %s: program type name, e.g. Undergraduate. */
				printf( esc_html__( '%s programmes', 'project' ), esc_html( $queried instanceof WP_Term ? $queried->name : '' ) );
				?>
			</h1>
			<p class="programmes__lede">
				<?php echo esc_html( $queried instanceof WP_Term ? $queried->description : '' ); ?>
			</p>
		<?php else : ?>
			<h1 class="programmes__title"><?php esc_html_e( 'Programmes', 'project' ); ?></h1>
			<p class="programmes__lede">
				<?php esc_html_e( 'Browse undergraduate and postgraduate programmes, then enquire in a couple of clicks.', 'project' ); ?>
			</p>
		<?php endif; ?>
	</header>

	<ul class="programmes__filter">
		<?php foreach ( $filters as $value ) : ?>
			<?php
			$label = 'all' === $value
				? __( 'All programmes', 'project' )
				: ( isset( $names[ $value ] ) ? $names[ $value ] : $value );

			$context = array(
				'value'   => $value,
				'restUrl' => Programs_Routing::get_rest_url( $value ),
			);

			$track = function_exists( 'program_track_attr' )
				? program_track_attr( 'link', 'filter-' . $value, 'programme-filter', 'componentLink' )
				: '';
			?>
			<li class="programmes__filter-item">
				<a class="programmes__filter-link"
					href="<?php echo esc_url( Programs_Routing::get_archive_url( $value ) ); ?>"
					data-wp-context='<?php echo esc_attr( wp_json_encode( $context ) ); ?>'
					data-wp-on--click="actions.selectFilter"
					data-wp-bind--aria-current="state.ariaCurrent"
					<?php echo $track ? 'data-track="' . $track . '"' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<p class="programmes__status" role="status" aria-live="polite">
		<span class="programmes__loading" data-wp-bind--hidden="!state.isLoading">
			<?php esc_html_e( 'Loading programmes…', 'project' ); ?>
		</span>
	</p>

	<?php if ( $programs->have_posts() ) : ?>
		<ul class="programmes__grid">
			<?php
			while ( $programs->have_posts() ) :
				$programs->the_post();

				get_template_part(
					'template-parts/program-card',
					'',
					array(
						'post'    => get_post(),
						'active'  => $active,
						'section' => 'programme-listing',
					)
				);
			endwhile;
			?>
		</ul>
	<?php endif; ?>

	<div class="programmes__empty" data-wp-bind--hidden="!state.isEmpty">
		<p><?php esc_html_e( 'No programmes match this filter yet.', 'project' ); ?></p>
	</div>

	<noscript>
		<p class="programmes__status">
			<?php esc_html_e( 'Filtering without JavaScript opens the matching programmes page instead.', 'project' ); ?>
		</p>
	</noscript>
</div>
<?php
wp_reset_postdata();
