<?php
/**
 * Breadcrumb trail for the programme templates.
 *
 * Items come from the plugin's routing helper so the trail always matches
 * the real URL structure.
 *
 * @package PROJECT
 */

if ( ! project_has_programs_module() ) {
	return;
}

$crumbs = Programs_Routing::get_breadcrumbs();

if ( empty( $crumbs ) ) {
	return;
}

$index = 0;
?>
<nav class="programmes-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'project' ); ?>">
	<ol class="programmes-breadcrumbs__list">
		<?php foreach ( $crumbs as $crumb ) : ?>
			<?php
			$index++;
			$is_last = '' === $crumb['url'];
			$track   = function_exists( 'program_track_attr' )
				? program_track_attr( 'link', 'breadcrumb-' . sanitize_title( $crumb['label'] ), 'breadcrumbs', 'componentLink' )
				: '';
			?>
			<li class="programmes-breadcrumbs__item">
				<?php if ( $is_last ) : ?>
					<span class="programmes-breadcrumbs__current" aria-current="page">
						<?php echo esc_html( $crumb['label'] ); ?>
					</span>
				<?php else : ?>
					<a href="<?php echo esc_url( $crumb['url'] ); ?>"
						<?php echo $track ? 'data-track="' . $track . '"' : ''; ?>>
						<?php echo esc_html( $crumb['label'] ); ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
