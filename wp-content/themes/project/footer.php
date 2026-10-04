<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package PROJECT
 */

?>

	<!-- FOOTER - Aberdeen Mumbai Design -->
	<footer class="footer">
		<div class="footer-top">
			<div class="footer-left">
				<div class="footer-logo-wrap">
					<div class="footer-logo-name">ABERDEEN</div>
					<div class="footer-logo-sub">Mumbai</div>
				</div>
				<div class="footer-address">
					The University of Aberdeen<br>
					India Location,<br>
					Mumbai
				</div>
				<div class="footer-tel">Tel: <span>+91-1212121212</span></div>
				<div class="footer-links">
					<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact Us</a>
					<a href="<?php echo esc_url( home_url( '/student-policies' ) ); ?>">Student Policies</a>
				</div>
			</div>
			<div class="footer-right">
				<div class="footer-connect-label">Connect With Us</div>
				<div class="footer-social">
					<a href="#" aria-label="X (Twitter)">
						<svg class="icon" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" fill="currentColor"><path d="M18.9 2H22l-7.6 8.7L23.3 22h-7l-5.5-7.2L4.5 22H1.4l8.1-9.3L1 2h7.2l5 6.6zm-1.1 18h1.7L6.3 3.9H4.5z"/></svg>
					</a>
					<a href="#" aria-label="Instagram">
						<svg class="icon" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
					</a>
					<a href="#" aria-label="LinkedIn">
						<svg class="icon" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" fill="currentColor"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9.5h4V21H3zM9.5 9.5h3.8v1.6h.1c.5-1 1.8-2 3.8-2 4 0 4.8 2.6 4.8 6V21h-4v-5.2c0-1.3 0-2.9-1.8-2.9s-2 1.4-2 2.8V21h-4z"/></svg>
					</a>
				</div>
			</div>
		</div>
		<div class="footer-bottom">
			<div class="footer-bottom-links">
				<a href="<?php echo esc_url( home_url( '/privacy' ) ); ?>">Privacy</a>
				<a href="<?php echo esc_url( home_url( '/accessibility' ) ); ?>">Accessibility</a>
				<a href="<?php echo esc_url( home_url( '/cookies' ) ); ?>">Cookies</a>
			</div>
			<div class="footer-copyright">© University of Aberdeen</div>
		</div>
	</footer><!-- .footer -->
</div><!-- #page -->

<?php
/*
 * The enquiry pop-up is rendered outside #page so the dialog can sit above
 * the whole document. It is rendered on every page because the header
 * "Apply Now" CTA opens it anywhere on the site. The mobile sticky bar is
 * only meaningful on the programme screens, so it stays scoped to them.
 */
if ( function_exists( 'project_has_programs_module' ) && project_has_programs_module() ) {
	if ( project_is_programmes_context() ) {
		get_template_part( 'template-parts/sticky-enquire' );
	}

	get_template_part( 'template-parts/program-enquiry' );
}
?>

<?php wp_footer(); ?>

</body>
</html>
