<?php
/**
 * The "Enquire now" pop-up: lead form screen and thank-you screen.
 *
 * Rendered once, at the end of the page. Without JavaScript the dialog stays
 * hidden and the noscript block below gives visitors a working alternative,
 * so the Enquire action never dead-ends.
 *
 * The endpoint and nonce travel as data attributes on the form, so there is
 * no inline script and no global to keep in sync.
 *
 * @package PROJECT
 */

if ( ! project_has_programs_module() || ! class_exists( 'Programs_Enquiry' ) ) {
	return;
}

$endpoint = rest_url( Programs_Enquiry::REST_NAMESPACE . Programs_Enquiry::REST_ROUTE );
$nonce    = wp_create_nonce( 'wp_rest' );

$programme_options = new WP_Query(
	array(
		'post_type'           => Programs_CPT::POST_TYPE,
		'post_status'         => 'publish',
		'posts_per_page'      => 100,
		'orderby'             => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	)
);

$dial_codes = array(
	'+971' => __( 'UAE (+971)', 'project' ),
	'+44'  => __( 'UK (+44)', 'project' ),
	'+91'  => __( 'India (+91)', 'project' ),
	'+92'  => __( 'Pakistan (+92)', 'project' ),
	'+20'  => __( 'Egypt (+20)', 'project' ),
	'+966' => __( 'Saudi Arabia (+966)', 'project' ),
	'+63'  => __( 'Philippines (+63)', 'project' ),
	'+1'   => __( 'USA / Canada (+1)', 'project' ),
);
?>
<div id="programmes-enquiry"
	class="enquiry"
	data-wp-interactive="programs/enquiry"
	hidden
	data-wp-bind--hidden="!state.isOpen"
	data-wp-on--keydown="actions.onKeydown"
	data-wp-watch="effects.focusDialog"
	data-wp-effect="effects.focusDialog">

	<button class="enquiry__overlay" type="button"
		data-wp-on--click="actions.close"
		aria-label="<?php esc_attr_e( 'Close the enquiry form', 'project' ); ?>"></button>

	<div class="enquiry__dialog" role="dialog" aria-modal="true" aria-labelledby="programmes-enquiry-title">
		<button class="enquiry__close" type="button"
			data-wp-on--click="actions.close"
			aria-label="<?php esc_attr_e( 'Close the enquiry form', 'project' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>

		<h2 class="enquiry__title" id="programmes-enquiry-title"><?php esc_html_e( 'Enquire now', 'project' ); ?></h2>

		<p class="enquiry__subtitle">
			<?php esc_html_e( 'Tell us what you are interested in and we will be in touch within two working days.', 'project' ); ?>
		</p>

		<p class="enquiry__form-error"
			data-wp-text="state.generalError"
			data-wp-bind--hidden="!state.generalError"></p>

		<form class="enquiry__form"
			data-wp-on--submit="actions.submit"
			data-wp-bind--hidden="state.isSent"
			data-endpoint="<?php echo esc_url( $endpoint ); ?>"
			data-nonce="<?php echo esc_attr( $nonce ); ?>">

			<div class="enquiry__field">
				<label class="enquiry__label" for="enquiry-programme">
					<?php esc_html_e( 'Select programme', 'project' ); ?>
					<span class="enquiry__required" aria-hidden="true">*</span>
				</label>
				<select class="enquiry__select" id="enquiry-programme" name="programme" required
					data-enquiry-autofocus
					data-wp-bind--value="state.programId">
					<option value="0"><?php esc_html_e( 'Please choose…', 'project' ); ?></option>
					<?php
					while ( $programme_options->have_posts() ) :
						$programme_options->the_post();
						?>
						<option value="<?php echo esc_attr( (string) get_the_ID() ); ?>">
							<?php echo esc_html( get_the_title() ); ?>
						</option>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</select>
			</div>

			<div class="enquiry__field">
				<label class="enquiry__label" for="enquiry-name">
					<?php esc_html_e( 'Full name', 'project' ); ?>
					<span class="enquiry__required" aria-hidden="true">*</span>
				</label>
				<input class="enquiry__input" id="enquiry-name" name="name" type="text"
					autocomplete="name" required
					aria-describedby="enquiry-name-error"
					data-wp-bind--aria-invalid="state.errorName" />
				<span class="enquiry__error" id="enquiry-name-error"
					data-wp-text="state.errorName"
					data-wp-bind--hidden="!state.errorName"></span>
			</div>

			<div class="enquiry__field">
				<label class="enquiry__label" for="enquiry-email">
					<?php esc_html_e( 'Email address', 'project' ); ?>
					<span class="enquiry__required" aria-hidden="true">*</span>
				</label>
				<input class="enquiry__input" id="enquiry-email" name="email" type="email"
					autocomplete="email" required
					aria-describedby="enquiry-email-error"
					data-wp-bind--aria-invalid="state.errorEmail" />
				<span class="enquiry__error" id="enquiry-email-error"
					data-wp-text="state.errorEmail"
					data-wp-bind--hidden="!state.errorEmail"></span>
			</div>

			<div class="enquiry__field">
				<label class="enquiry__label" for="enquiry-phone">
					<?php esc_html_e( 'Phone number', 'project' ); ?>
				</label>
				<div class="enquiry__phone">
					<select class="enquiry__select" id="enquiry-dial-code" name="countryCode"
						aria-label="<?php esc_attr_e( 'Country dialling code', 'project' ); ?>">
						<?php foreach ( $dial_codes as $code => $label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( '+971', $code ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input class="enquiry__input" id="enquiry-phone" name="phone" type="tel"
						autocomplete="tel"
						aria-describedby="enquiry-phone-error"
						data-wp-bind--aria-invalid="state.errorPhone" />
				</div>
				<span class="enquiry__error" id="enquiry-phone-error"
					data-wp-text="state.errorPhone"
					data-wp-bind--hidden="!state.errorPhone"></span>
			</div>

			<div class="enquiry__field">
				<label class="enquiry__label" for="enquiry-message">
					<?php esc_html_e( 'Your message', 'project' ); ?>
				</label>
				<textarea class="enquiry__textarea" id="enquiry-message" name="message" rows="4"></textarea>
			</div>

			<div class="enquiry__actions">
				<button class="pm-button pm-button--primary" type="submit"
					data-wp-bind--disabled="state.isSubmitting">
					<?php esc_html_e( 'Send', 'project' ); ?>
				</button>
				<button class="pm-button pm-button--secondary" type="button"
					data-wp-on--click="actions.close">
					<?php esc_html_e( 'Cancel', 'project' ); ?>
				</button>
			</div>
		</form>

		<div class="enquiry__thanks" hidden data-wp-bind--hidden="!state.isSent">
			<div class="enquiry__thanks-mark" aria-hidden="true">&#10003;</div>
			<h3 class="enquiry__thanks-title"><?php esc_html_e( 'Enquiry Submitted', 'project' ); ?></h3>
			<p class="enquiry__thanks-text">
				<?php esc_html_e( 'Thank you for contacting us. Your enquiry has been received and a member of our admissions team will be in touch within two working days.', 'project' ); ?>
			</p>
			<button class="pm-button pm-button--secondary" type="button" data-wp-on--click="actions.close">
				<?php esc_html_e( 'Close', 'project' ); ?>
			</button>
		</div>
	</div>
</div>

<noscript>
	<div class="programmes__empty">
		<p>
			<?php esc_html_e( 'JavaScript is disabled, so the enquiry form cannot open here. Please email the admissions team and we will get back to you.', 'project' ); ?>
		</p>
	</div>
</noscript>
