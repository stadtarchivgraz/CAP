<?php
// Template Name: SIP Profile

get_header();

/* Start the Loop */
while ( have_posts() ) :
	the_post();
	include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-page.php' );

endwhile; // End of the loop.

get_footer();
