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

	<footer id="colophon" class="site-footer">
		<div class="site-footer__inner">
			<div class="site-footer__top">
				<div class="site-footer__brand footer-widget">
					<?php project_site_logo(); ?>
					<p class="site-footer__blurb">
						<?php esc_html_e( 'A branch campus of the University of Sunderland, offering undergraduate and postgraduate degrees in the heart of Dubai.', 'project' ); ?>
					</p>
				</div>

				<div class="footer-widget">
					<h2 class="footer-widget__title"><?php esc_html_e( 'Programmes', 'project' ); ?></h2>
					<ul>
						<?php if ( function_exists( 'project_has_programs_module' ) && project_has_programs_module() ) : ?>
							<li><a href="<?php echo esc_url( Programs_Routing::get_archive_url() ); ?>"><?php esc_html_e( 'All programmes', 'project' ); ?></a></li>
							<?php foreach ( Programs_CPT::get_program_types( true ) as $footer_term ) : ?>
								<li>
									<a href="<?php echo esc_url( Programs_Routing::get_archive_url( $footer_term->slug ) ); ?>">
										<?php echo esc_html( $footer_term->name ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</div>

				<div class="footer-widget">
					<h2 class="footer-widget__title"><?php esc_html_e( 'Quick links', 'project' ); ?></h2>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'project' ); ?></a></li>
						<?php if ( function_exists( 'project_has_programs_module' ) && project_has_programs_module() ) : ?>
							<li><a href="<?php echo esc_url( Programs_Routing::get_archive_url() ); ?>"><?php esc_html_e( 'Courses and qualifications', 'project' ); ?></a></li>
						<?php endif; ?>
						<li><a href="<?php echo esc_url( function_exists( 'project_has_programs_module' ) && project_has_programs_module() ? Programs_Routing::get_apply_url() : home_url( '/' ) ); ?>"><?php esc_html_e( 'Apply now', 'project' ); ?></a></li>
					</ul>
				</div>

				<div class="footer-widget">
					<h2 class="footer-widget__title"><?php esc_html_e( 'Contact', 'project' ); ?></h2>
					<ul>
						<li><?php esc_html_e( 'Dubai Knowledge Park', 'project' ); ?></li>
						<li><?php esc_html_e( 'Dubai, United Arab Emirates', 'project' ); ?></li>
						<li>
							<a href="mailto:<?php echo esc_attr( 'admissions@example.education' ); ?>"><?php echo esc_html( 'admissions@example.education' ); ?></a>
						</li>
					</ul>
				</div>
			</div>

			<div class="site-footer__bottom">
				<p class="site-footer__copy">
					<?php
					/* translators: %s: current year. */
					printf( esc_html__( 'Copyright all right reserved %s', 'project' ), esc_html( gmdate( 'Y' ) ) );
					?>
				</p>

				<ul class="site-footer__social">
					<li><a href="#" aria-label="<?php esc_attr_e( 'Facebook', 'project' ); ?>">FB</a></li>
					<li><a href="#" aria-label="<?php esc_attr_e( 'X', 'project' ); ?>">X</a></li>
					<li><a href="#" aria-label="<?php esc_attr_e( 'LinkedIn', 'project' ); ?>">IN</a></li>
					<li><a href="#" aria-label="<?php esc_attr_e( 'YouTube', 'project' ); ?>">YT</a></li>
				</ul>
			</div>
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php
/*
 * The enquiry pop-up and the mobile sticky bar are rendered outside #page so
 * the sticky bar is never trapped by a transformed ancestor and the dialog
 * can sit above the whole document.
 */
if ( function_exists( 'project_is_programmes_context' ) && project_is_programmes_context() ) {
	get_template_part( 'template-parts/sticky-enquire' );
	get_template_part( 'template-parts/program-enquiry' );
}
?>

<?php wp_footer(); ?>

</body>
</html>
