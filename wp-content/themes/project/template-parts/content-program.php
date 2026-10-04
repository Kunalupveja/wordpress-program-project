<?php
/**
 * Compact program display for generic loops (search results, archives that
 * are not the programmes listing).
 *
 * The full card lives in template-parts/program-card.php. This one is
 * deliberately text-first so it reads well inside mixed results.
 *
 * @package PROJECT
 */

$program_id = get_the_ID();
$type       = project_program_type( $program_id );
$facts      = project_program_facts( $program_id );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'program-result' ); ?>>
	<header class="entry-header">
		<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>

		<?php if ( $type ) : ?>
			<a class="program-result__type" href="<?php echo esc_url( Programs_Routing::get_archive_url( $type->slug ) ); ?>">
				<?php echo esc_html( $type->name ); ?>
			</a>
		<?php endif; ?>
	</header><!-- .entry-header -->

	<?php if ( '' !== $facts['year'] || '' !== $facts['duration'] ) : ?>
		<p class="program-result__facts">
			<?php
			$parts = array();

			if ( '' !== $facts['year'] ) {
				/* translators: %s: academic year, e.g. 2025-26. */
				$parts[] = sprintf( esc_html__( 'Academic year %s', 'project' ), esc_html( $facts['year'] ) );
			}

			if ( '' !== $facts['duration'] ) {
				/* translators: %s: program duration, e.g. 3 Years. */
				$parts[] = sprintf( esc_html__( 'Duration %s', 'project' ), esc_html( $facts['duration'] ) );
			}

			echo esc_html( implode( ' | ', $parts ) );
			?>
		</p>
	<?php endif; ?>

	<div class="entry-summary">
		<?php the_excerpt(); ?>
	</div><!-- .entry-summary -->
</article><!-- #post-<?php the_ID(); ?> -->
