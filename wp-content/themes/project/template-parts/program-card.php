<?php
/**
 * Reusable program card.
 *
 * Matches the Figma programme card: image, then a navy body with a gold
 * heading, a gold motif bar, the programme description and the academic
 * year / duration meta, followed by the Apply and Enquire actions.
 *
 * Rendered with:
 *   get_template_part( 'template-parts/program-card', '', array( ... ) );
 *
 * @package PROJECT
 *
 * @var array $args {
 *     Optional arguments.
 *
 *     @type WP_Post $post   The program to render. Defaults to the global post.
 *     @type string  $active The filter currently applied: "all", a term slug,
 *                           or an empty string for "always visible".
 *     @type string  $section Tracking section name for the card link.
 * }
 */

$program = isset( $args['post'] ) && $args['post'] instanceof WP_Post ? $args['post'] : get_post();

if ( ! $program instanceof WP_Post ) {
	return;
}

$program_id = (int) $program->ID;
$active     = isset( $args['active'] ) ? (string) $args['active'] : 'all';
$section    = isset( $args['section'] ) ? (string) $args['section'] : 'programme-listing';

$type      = project_program_type( $program_id );
$type_slug = $type ? $type->slug : '';
$facts     = project_program_facts( $program_id );

/*
 * Rendered hidden whenever the card is not part of the active filter, so the
 * correct subset is on screen before any JavaScript runs. The Interactivity
 * binding takes over from here and re-evaluates it on every filter change.
 */
$hidden = '' !== $active && 'all' !== $active && $type_slug !== $active;

$track_card = function_exists( 'program_track_attr' )
	? program_track_attr( 'link', 'program-card', $section, 'componentLink' )
	: '';

$track_apply = function_exists( 'program_track_attr' )
	? program_track_attr( 'link', 'apply-now', $section, 'componentLink' )
	: '';

$track_enquire = function_exists( 'program_track_attr' )
	? program_track_attr( 'button', 'enquire-now', $section, 'componentButton' )
	: '';

$has_facts = '' !== $facts['year'] || '' !== $facts['duration'];
?>
<li class="program-card"
	data-wp-context='<?php echo esc_attr( wp_json_encode( array( 'id' => $program_id, 'type' => $type_slug ) ) ); ?>'
	data-wp-bind--hidden="state.cardHidden"
	<?php echo $hidden ? 'hidden' : ''; ?>>

	<a class="program-card__link"
		href="<?php echo esc_url( get_permalink( $program ) ); ?>"
		<?php echo $track_card ? 'data-track="' . $track_card . '"' : ''; ?>>

		<span class="program-card__media">
			<?php
			if ( has_post_thumbnail( $program_id ) ) {
				echo wp_get_attachment_image(
					get_post_thumbnail_id( $program_id ),
					'program-card',
					false,
					array(
						// Falls back to the attachment alt text, then the
						// program title, so the image is never unnamed.
						'alt'     => trim( strip_tags( get_post_meta( get_post_thumbnail_id( $program_id ), '_wp_attachment_image_alt', true ) ) ) ?: get_the_title( $program ),
						'loading' => 'lazy',
					)
				);
			} else {
				echo '<span class="program-card__media-empty" aria-hidden="true"></span>';
			}

			if ( $type ) :
				?>
				<span class="program-card__type"><?php echo esc_html( $type->name ); ?></span>
				<?php
			endif;
			?>
		</span>

		<span class="program-card__body">
			<span class="program-card__heading-wrap">
				<span class="program-card__heading"><?php echo esc_html( get_the_title( $program ) ); ?></span>
				<span class="program-card__motif" aria-hidden="true"></span>
			</span>

			<?php if ( has_excerpt( $program ) ) : ?>
				<span class="program-card__text"><?php echo esc_html( get_the_excerpt( $program ) ); ?></span>
			<?php else : ?>
				<span class="program-card__text"><?php echo esc_html( wp_trim_words( get_the_content( null, false, $program ), 22, '…' ) ); ?></span>
			<?php endif; ?>

			<?php if ( $has_facts ) : ?>
				<span class="program-card__meta">
					<?php if ( '' !== $facts['year'] ) : ?>
						<span>
							<?php
							/* translators: %s: academic year, e.g. 2025-26. */
							printf( esc_html__( 'Academic year %s', 'project' ), esc_html( $facts['year'] ) );
							?>
						</span>
					<?php endif; ?>

					<?php if ( '' !== $facts['duration'] ) : ?>
						<span>
							<?php
							/* translators: %s: program duration, e.g. 3 Years. */
							printf( esc_html__( 'Duration %s', 'project' ), esc_html( $facts['duration'] ) );
							?>
						</span>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</span>
	</a>

	<?php // Outside the card link: two separately focusable actions. ?>
	<div class="program-card__actions" data-wp-interactive="programs/enquiry">
		<a class="pm-button pm-button--secondary"
			href="<?php echo esc_url( Programs_Routing::get_apply_url( $program_id ) ); ?>"
			data-wp-context='<?php echo esc_attr( wp_json_encode( array( 'programId' => $program_id, 'programTitle' => get_the_title( $program ) ) ) ); ?>'
			data-wp-on--click="actions.open"
			<?php echo $track_apply ? 'data-track="' . $track_apply . '"' : ''; ?>>
			<?php esc_html_e( 'Apply now', 'project' ); ?>
		</a>

		<a class="pm-button pm-button--primary"
			href="#programmes-enquiry"
			data-wp-context='<?php echo esc_attr( wp_json_encode( array( 'programId' => $program_id, 'programTitle' => get_the_title( $program ) ) ) ); ?>'
			data-wp-on--click="actions.open"
			<?php echo $track_enquire ? 'data-track="' . $track_enquire . '"' : ''; ?>>
			<?php esc_html_e( 'Enquire now', 'project' ); ?>
		</a>
	</div>
</li>
