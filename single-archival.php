<?php
/**
 * Template to display the single post for the CPT archival.
 */

get_header();

while (have_posts()) : the_post();

	$author_id = (int) get_post_field( 'post_author' );
	// only admin/editor should be able to view the single page for the archival records CPT.
	if ( current_user_can( 'edit_others_pages' ) || ( is_user_logged_in() && $author_id === get_current_user_id() ) ) :
		include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-archival.php' );
	elseif( ! is_user_logged_in() ) :
		?>
		<article <?php post_class('sip'); ?>>
			<div class="container">
				<?php wp_login_form( array( 'echo' => true, ) ); ?>
			</div>
		</article>
	<?php
	else :
		echo starg_get_notification_message( esc_html__('You are not allowed to view this page!', 'sip'), 'is-warning is-light' );
	endif;


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
