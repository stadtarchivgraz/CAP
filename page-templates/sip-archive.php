<?php
// Template Name: Upload
get_header();

/* Start the Loop */
while ( have_posts() ) :
	the_post();
	include( dirname( __DIR__ ) . '/template-parts/content-page.php' );

endwhile; // End of the loop.

get_footer();