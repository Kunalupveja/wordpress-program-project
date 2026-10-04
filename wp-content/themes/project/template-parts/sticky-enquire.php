<?php
/**
 * Mobile sticky Enquire bar (Task 1.1).
 *
 * Fixed to the bottom of the viewport below 768px so the Enquire action is
 * always one tap away on a phone. It is hidden while the pop-up is open,
 * and it disappears on tablet and desktop where the in-page buttons are
 * already visible.
 *
 * @package PROJECT
 */

if ( ! project_has_programs_module() || ! class_exists( 'Programs_Enquiry' ) ) {
	return;
}

$program_id    = 0;
$program_title = '';

if ( is_singular( Programs_CPT::POST_TYPE ) ) {
	$program_id    = (int) get_the_ID();
	$program_title = get_the_title();
}

$context = array(
	'programId'    => $program_id,
	'programTitle' => $program_title,
);

$track = function_exists( 'program_track_attr' )
	? program_track_attr( 'button', 'enquire-now-sticky', 'sticky-cta', 'componentButton' )
	: '';
?>
<div class="sticky-enquire" data-wp-interactive="programs/enquiry">
	<span class="sticky-enquire__label">
		<?php esc_html_e( 'Interested in this programme?', 'project' ); ?>
		<?php if ( '' !== $program_title ) : ?>
			<span><?php echo esc_html( $program_title ); ?></span>
		<?php endif; ?>
	</span>

	<a class="pm-button pm-button--primary"
		href="#programmes-enquiry"
		data-wp-context='<?php echo esc_attr( wp_json_encode( $context ) ); ?>'
		data-wp-on--click="actions.open"
		<?php echo $track ? 'data-track="' . $track . '"' : ''; ?>>
		<?php esc_html_e( 'Enquire now', 'project' ); ?>
	</a>
</div>
