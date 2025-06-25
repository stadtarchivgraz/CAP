<?php
// Template Name: SIP Profile

if(isset($_GET['sipFolder']) && $_GET['sipFolder'] && isset($_GET['delete']) && $_GET['delete']) {
	global $wpdb;
	$author_id = get_current_user_id();
	$sip_folder = carbon_get_theme_option( 'sip_upload_path' ) . $author_id . '/' . $_GET['sipFolder'] . '/';
	if(is_dir($sip_folder)) {
		removeSIP($sip_folder);
	}
	if(isset($_GET['archival_id']) && $_GET['archival_id']) {
		wp_delete_post($_GET['archival_id'], true);
	}
}

get_header();

/* Start the Loop */
while ( have_posts() ) :
	the_post();
	include( dirname( __DIR__ ) . '/template-parts/content-page.php' );

endwhile; // End of the loop.

get_footer();