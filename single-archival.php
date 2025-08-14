<?php
/**
 * Template to display the single post for the CPT archival.
 */

get_header();

while (have_posts()) : the_post();

	// only admin/editor should be able to view the single page for the archival records CPT.
	if ( current_user_can( 'edit_others_pages' ) ) {
		include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-archival.php' );
	} else {
		echo starg_get_notification_message( esc_html__('You are not allowed to view this page!', 'sip'), 'is-warning is-light' );
	}


	// If comments are open or there is at least one comment, load up the comment template.
	if (comments_open() || get_comments_number()) {
		comments_template();
	}

	// Previous/next post navigation might be added later. But only for admin/editors!
	// if ( current_user_can('edit_others_pages') ) :
		// $sip_next_label     = esc_html__( 'Next archival >', 'sip' );
		// $sip_previous_label = esc_html__( '< Previous archival', 'sip' );

		// the_post_navigation(
		// 	array(
		// 		'next_text' => '<span class="meta-nav">' . $sip_next_label . '</span> <span class="post-title">%title</span>',
		// 		'prev_text' => '<span class="meta-nav">' . $sip_previous_label . '</span> <span class="post-title">%title</span>'
		// 	)
		// );
	// endif;

endwhile; // End of the loop.

get_footer();
