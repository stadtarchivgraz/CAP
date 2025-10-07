<?php
// Template Name: SIP Profile

$update_user_password = apply_filters( 'starg/update_user_password', null );
if ( $update_user_password instanceof Starg_Update_User_Password ) {
	$update_user_password->maybe_process_update_user_password();
}

get_header();

/* Start the Loop */
while ( have_posts() ) :
	the_post();
	include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-page.php' );

endwhile; // End of the loop.

get_footer();
