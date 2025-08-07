<?php
/**
 * Template Name: SIP Archive
 * This is an archive page for all the submitted archival records.
 */

// Redirect everybody to the start if one is not allowed to see all archival records.
$sip_archive_role = ( carbon_get_theme_option( 'sip_archive_role' ) ) ? esc_attr( carbon_get_theme_option( 'sip_archive_role' ) ) : 'edit_others_posts';
if ( ! current_user_can( $sip_archive_role ) ) {
	wp_safe_redirect( home_url() );
	exit;
}

get_header();

/* Start the Loop */
while ( have_posts() ) :
	the_post();

	include_once( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-page.php' );

endwhile; // End of the loop.

get_footer();
