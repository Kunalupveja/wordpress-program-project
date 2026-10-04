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

$enquirer_types = array(
	'prospective-student' => __( 'Prospective student', 'project' ),
	'parent'              => __( 'Parent or guardian', 'project' ),
	'counsellor'          => __( 'School counsellor', 'project' ),
	'agent'               => __( 'Agent', 'project' ),
);

$countries = array(
	'india'    => __( 'India', 'project' ),
	'bangladesh' => __( 'Bangladesh', 'project' ),
	'nepal'    => __( 'Nepal', 'project' ),
	'sri-lanka' => __( 'Sri Lanka', 'project' ),
	'uae'      => __( 'United Arab Emirates', 'project' ),
	'other'    => __( 'Other', 'project' ),
);

$cities = array(
	'mumbai'       => __( 'Mumbai', 'project' ),
	'delhi'        => __( 'Delhi', 'project' ),
	'bengaluru'    => __( 'Bengaluru', 'project' ),
	'chennai'      => __( 'Chennai', 'project' ),
	'hyderabad'    => __( 'Hyderabad', 'project' ),
	'pune'         => __( 'Pune', 'project' ),
	'kolkata'      => __( 'Kolkata', 'project' ),
	'other'        => __( 'Other', 'project' ),
);
?>
<div id="programmes-enquiry"
	class="enquiry"
	data-wp-interactive="programs/enquiry"
	hidden
	data-wp-bind--hidden="!state.isOpen"
	data-wp-on--keydown="actions.onKeydown"
	data-wp-effect="effects.lockScroll">

	<button class="enquiry__overlay" type="button"
		data-wp-on--click="actions.close"
		aria-label="<?php esc_attr_e( 'Close the enquiry form', 'project' ); ?>"></button>

	<div class="enquiry__dialog" role="dialog" aria-modal="true" aria-labelledby="programmes-enquiry-title"
		data-wp-effect="effects.focusDialog">
		<div class="enquiry__head">
			<h2 class="enquiry__title" id="programmes-enquiry-title"><?php esc_html_e( 'Register your interest', 'project' ); ?></h2>
			<button class="enquiry__close" type="button"
				data-wp-on--click="actions.close"
				aria-label="<?php esc_attr_e( 'Close the enquiry form', 'project' ); ?>"></button>
		</div>

		<p class="enquiry__form-error"
			data-wp-text="state.generalError"
			data-wp-bind--hidden="!state.generalError"></p>

		<form class="enquiry__form"
			data-wp-on--submit="actions.submit"
			data-wp-bind--hidden="state.isSent"
			data-endpoint="<?php echo esc_url( $endpoint ); ?>"
			data-nonce="<?php echo esc_attr( $nonce ); ?>">

			<div class="enquiry__field">
				<label class="enquiry__label" for="enquiry-enquirer">
					<?php esc_html_e( 'I am enquiring as', 'project' ); ?>
					<span class="enquiry__required" aria-hidden="true">*</span>
				</label>
				<select class="enquiry__select" id="enquiry-enquirer" name="enquirer" required
					data-enquiry-autofocus
					data-wp-bind--aria-invalid="state.errorEnquirer">
					<option value=""><?php esc_html_e( 'Select an option', 'project' ); ?></option>
					<?php foreach ( $enquirer_types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="enquiry__error"
					data-wp-text="state.errorEnquirer"
					data-wp-bind--hidden="!state.errorEnquirer"></span>
			</div>

			<div class="enquiry__row">
				<div class="enquiry__field">
					<label class="enquiry__label" for="enquiry-level">
						<?php esc_html_e( 'Level of interest', 'project' ); ?>
						<span class="enquiry__required" aria-hidden="true">*</span>
					</label>
					<select class="enquiry__select" id="enquiry-level" name="level" required
						data-wp-bind--aria-invalid="state.errorLevel"
						data-wp-on--change="actions.syncProgrammes">
						<option value=""><?php esc_html_e( 'Select an option', 'project' ); ?></option>
						<?php
						foreach ( Programs_CPT::get_program_types() as $level_term ) :
							?>
							<option value="<?php echo esc_attr( $level_term->slug ); ?>"><?php echo esc_html( $level_term->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="enquiry__error"
						data-wp-text="state.errorLevel"
						data-wp-bind--hidden="!state.errorLevel"></span>
				</div>

				<div class="enquiry__field">
					<label class="enquiry__label" for="enquiry-programme">
						<?php esc_html_e( 'Programme of interest', 'project' ); ?>
						<span class="enquiry__required" aria-hidden="true">*</span>
					</label>
					<select class="enquiry__select" id="enquiry-programme" name="programme" required
						data-wp-bind--value="state.programId"
						data-wp-bind--aria-invalid="state.errorProgramme">
						<option value="0"><?php esc_html_e( 'Select an option', 'project' ); ?></option>
						<?php
						while ( $programme_options->have_posts() ) :
							$programme_options->the_post();
							$programme_type = project_program_type( get_the_ID() );
							?>
							<option value="<?php echo esc_attr( (string) get_the_ID() ); ?>"
								<?php echo $programme_type ? 'data-type="' . esc_attr( $programme_type->slug ) . '"' : ''; ?>>
								<?php echo esc_html( get_the_title() ); ?>
							</option>
						<?php endwhile; ?>
						<?php wp_reset_postdata(); ?>
					</select>
					<span class="enquiry__error"
						data-wp-text="state.errorProgramme"
						data-wp-bind--hidden="!state.errorProgramme"></span>
				</div>
			</div>

			<div class="enquiry__row">
				<div class="enquiry__field">
					<label class="enquiry__label" for="enquiry-first-name">
						<?php esc_html_e( 'First name', 'project' ); ?>
						<span class="enquiry__required" aria-hidden="true">*</span>
					</label>
					<input class="enquiry__input" id="enquiry-first-name" name="firstName" type="text"
						autocomplete="given-name" required
						aria-describedby="enquiry-first-name-error"
						data-wp-bind--aria-invalid="state.errorFirstName" />
					<span class="enquiry__error" id="enquiry-first-name-error"
						data-wp-text="state.errorFirstName"
						data-wp-bind--hidden="!state.errorFirstName"></span>
				</div>

				<div class="enquiry__field">
					<label class="enquiry__label" for="enquiry-last-name">
						<?php esc_html_e( 'Last name', 'project' ); ?>
						<span class="enquiry__required" aria-hidden="true">*</span>
					</label>
					<input class="enquiry__input" id="enquiry-last-name" name="lastName" type="text"
						autocomplete="family-name" required
						aria-describedby="enquiry-last-name-error"
						data-wp-bind--aria-invalid="state.errorLastName" />
					<span class="enquiry__error" id="enquiry-last-name-error"
						data-wp-text="state.errorLastName"
						data-wp-bind--hidden="!state.errorLastName"></span>
				</div>
			</div>

			<div class="enquiry__row">
				<div class="enquiry__field">
					<label class="enquiry__label" for="enquiry-country">
						<?php esc_html_e( 'Country', 'project' ); ?>
						<span class="enquiry__required" aria-hidden="true">*</span>
					</label>
					<select class="enquiry__select" id="enquiry-country" name="country" required
						data-wp-bind--aria-invalid="state.errorCountry">
						<option value=""><?php esc_html_e( 'Select an option', 'project' ); ?></option>
						<?php foreach ( $countries as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="enquiry__error"
						data-wp-text="state.errorCountry"
						data-wp-bind--hidden="!state.errorCountry"></span>
				</div>

				<div class="enquiry__field">
					<label class="enquiry__label" for="enquiry-city">
						<?php esc_html_e( 'City', 'project' ); ?>
						<span class="enquiry__required" aria-hidden="true">*</span>
					</label>
					<select class="enquiry__select" id="enquiry-city" name="city" required
						data-wp-bind--aria-invalid="state.errorCity">
						<option value=""><?php esc_html_e( 'Select an option', 'project' ); ?></option>
						<?php foreach ( $cities as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="enquiry__error"
						data-wp-text="state.errorCity"
						data-wp-bind--hidden="!state.errorCity"></span>
				</div>
			</div>

			<div class="enquiry__row">
				<div class="enquiry__field">
					<label class="enquiry__label" for="enquiry-phone">
						<?php esc_html_e( 'Phone number', 'project' ); ?>
						<span class="enquiry__required" aria-hidden="true">*</span>
					</label>
					<div class="enquiry__phone">
						<span class="enquiry__phone-prefix" aria-hidden="true">+91</span>
						<input class="enquiry__input" id="enquiry-phone" name="phone" type="tel"
							inputmode="numeric" pattern="[0-9]{10}" autocomplete="tel-national"
							aria-describedby="enquiry-phone-error" required
							data-wp-bind--aria-invalid="state.errorPhone" />
					</div>
					<span class="enquiry__error" id="enquiry-phone-error"
						data-wp-text="state.errorPhone"
						data-wp-bind--hidden="!state.errorPhone"></span>
				</div>

				<div class="enquiry__field">
					<label class="enquiry__label" for="enquiry-email">
						<?php esc_html_e( 'Email', 'project' ); ?>
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
			</div>

			<div class="enquiry__foot">
				<p class="enquiry__consent">
					<?php
					esc_html_e( 'By clicking the button below, you agree to receive communications via Email/Call/WhatsApp/SMS about this academic program and other academic programs from University of Aberdeen or its trusted third-party partners and service providers.', 'project' );
					?>
					<a href="https://emeritus.org/privacy-notice/prospective-students" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy Policy', 'project' ); ?></a>
				</p>
				<button class="enquiry__submit" type="submit"
					data-wp-bind--disabled="state.isSubmitting">
					<?php esc_html_e( 'Submit', 'project' ); ?>
				</button>
			</div>
		</form>

		<div class="enquiry__thanks" hidden data-wp-bind--hidden="!state.isSent">
			<div class="enquiry__thanks-toast" role="status">
				<svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
				<p>
					<strong><?php esc_html_e( 'Thank you for registering your interest', 'project' ); ?></strong>
					<?php esc_html_e( "We'll send you helpful information and updates about this programme, the application process, and more.", 'project' ); ?>
				</p>
			</div>
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
