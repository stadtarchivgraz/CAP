<?php
// Template Name: SIP Upload

$sip_folder = ( isset( $_GET['sipFolder'] ) ) ? sanitize_text_field( $_GET['sipFolder'] ) : '';

if ( isset( $_POST ) && isset( $_POST['save-sip'] ) ) {
	require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/sip-upload-form-validation.class.php' );
	$upload_form = new Sip_Upload_Form_Validation;
	$upload_form->process_upload_form();
}

if (isset($_COOKIE['sip_file_size'])) {
	unset($_COOKIE['sip_file_size']);
	setcookie('sip_file_size', '', -1, '/');
}

get_header();

while (have_posts()) :
	the_post();
	
	// display a notification
	if ( isset( $upload_form ) ) {
		$upload_form->display_notification();
	}

	if ( ! isset( $_GET['sipFolder'] ) ) :
		include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-page.php' );
	else :
		// Only admin/editor and the author should be able to view this page.
		// But we need to define a route for the second phase! Otherwise we can't create the post and add additional meta data!
		// The creation of the post happens in phase two and not at uploading files through Dropzone!
		$author_id = (int) get_post_field( 'post_author', starg_get_archival_id_by_sip_folder( $sip_folder ) );
		$is_second_phase = isset( $_GET['starg'] ) && isset( $_GET['starg_amd'] ) && wp_verify_nonce( sanitize_key( $_GET['starg_amd'] ), 'starg_add_archival_meta_data_nonce_action' );

		if ( ( current_user_can('edit_others_posts') || $author_id === get_current_user_id() ) || $is_second_phase ) {
			include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-sip-form.php' );
		} else {
			echo starg_get_notification_message(esc_html__('You are not allowed to view this page!', 'sip'), 'is-warning is-light');
		}
	endif;
endwhile; // End of the loop.

get_footer();
