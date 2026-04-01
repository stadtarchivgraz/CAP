<?php
if (! defined('WPINC')) { die; }

/**
 * Adds additional Text above of the login form.
 * The filter "login_form_top" only exists in the function @see wp_login_form() and therefore does not do anything on wp-login.php
 * If one wants to add Text on top of wp-login.php one needs to use the filter login_message.
 * @param string $content  Content to display. Default empty.
 * @param array $args      Array of login form arguments.
 * @return string
 */
function starg_archival_loginform_top( $content, $args ) {
	if ( $args['form_id'] !== 'archival_loginform' ) {
		return $content;
	}

	$current_locale = strtolower( get_locale() );
	$content_to_add = wpautop( wp_kses_post( get_option( '_sip_register_text_' . $current_locale ) ) );
	return '<section class="notification is-info is-light content">' . $content_to_add . '</section>';
}
add_filter( 'login_form_top', 'starg_archival_loginform_top', 10, 2 );

/**
 * Overwrites the original single-template for the archival CPT.
 */
function starg_load_archival_template( $template ) {
	global $post;

	if ( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG !== $post->post_type ) {
		return $template;
	}

	return STARG_SIP_PLUGIN_BASE_DIR . 'single-archival.php';
}
add_filter( 'single_template', 'starg_load_archival_template' );

/**
 * Adds a new setting for the users profile in the backend where one can set the preferred archival institution.
 * @param string|WP_User $user
 */
function starg_add_sip_user_archive_field( $user ) {
	$user_archive = '';
	if ( 'add-new-user' !== $user ) {
		$user_archive = get_user_meta( $user->ID, 'user_archive', true );
	}
	?>
	<table class="form-table">
		<tr>
			<th><label for="select_archive"><?php _e('Archive', 'sip'); ?></label></th>
			<td>
				<?php
				if ( current_user_can( 'manage_options' ) ) {
					$args = array(
						'name'          => 'user_archive',
						'id'            => 'select_archive',
						'class'         => 'postform',
						'taxonomy'      => 'archive',
						'hide_empty'    => false,
						'hide_if_empty' => false,
						'value_field'   => 'term_id',
						'required'      => true,
						'selected'      => $user_archive,
					);
					wp_dropdown_categories( $args );
				} else {
					$archive = get_term( $user_archive, 'archive' );
					echo $archive->name;
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'starg_add_sip_user_archive_field' );
add_action( 'edit_user_profile', 'starg_add_sip_user_archive_field' );
add_action( "user_new_form", "starg_add_sip_user_archive_field" );

/**
 * If a user is added in the backend, we save the user's archive option as user meta.
 * @param int $user_id
 * @param array $userdata
 * @return void
 */
function starg_save_user_archive_on_register( int $user_id, array $userdata ): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! isset( $_POST[ 'user_archive' ] ) ) {
		return;
	}

	update_user_meta( $user_id, 'user_archive', (int) sanitize_text_field( $_POST[ 'user_archive' ] ) );
	update_user_meta( $user_id, 'user_archive_profile', 1 );
}
add_action( 'user_register', 'starg_save_user_archive_on_register', 10, 2 );

/**
 * If a user is updated in the backend, we save the user's archive option as user meta.
 * @param int $user_id
 * @param WP_User $old_user_data
 * @param array $userdata
 * @return void
 */
function starg_save_user_archive_on_update( $user_id, $old_user_data, array $userdata ): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! isset( $_POST[ 'user_archive' ] ) ) {
		return;
	}

	update_user_meta( $user_id, 'user_archive', (int) sanitize_text_field( $_POST[ 'user_archive' ] ) );
	update_user_meta( $user_id, 'user_archive_profile', 1 );
}
add_action( 'profile_update', 'starg_save_user_archive_on_update', 10, 3 );

/**
 * Change the main query based on the current user.
 * todo: might be deprecated. A regular user can only access their own archival records. And admin/editors should be able to see every entry.
 * @param WP_Query $query
 */
function starg_sip_admin_archivals( WP_Query $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return $query;
	}

	if ( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG !== $query->query_vars[ 'post_type' ] ) {
		return $query;
	}

	$user_id = get_current_user_id();
	// only display archival posts from the current user if not admin!
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		$query->set( 'author', $user_id );
	// }
	// if ( ! current_user_can( 'manage_options' ) ) {
		$user_archive = get_user_meta( $user_id, 'user_archive', true );
		$tax_query = array(
			array(
				'taxonomy' => Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG,
				'field'    => 'term_id',
				'terms'    => $user_archive
			)
		);
		$query->set( 'tax_query', $tax_query );
	}

	return $query;
}
add_filter( 'pre_get_posts', 'starg_sip_admin_archivals' );

/**
 * Allow the upload for additional file types.
 * SVG and XML files are allowed.
 * @param array $existing_mimes contains all the file types currently allowed for upload.
 */
function starg_sip_upload_types( array $existing_mimes = array() ) {
	// allow .woff
	$existing_mimes['svg']      = 'image/svg+xml';
	$existing_mimes['xml-text'] = 'text/xml';
	$existing_mimes['xml']      = 'application/xml';

	return $existing_mimes;
}
add_filter( 'upload_mimes', 'starg_sip_upload_types' );

/**
 * Save the date of the first submission.
 * The user can only save the submission as draft or in pending state. Only editors can publish posts.
 * @param string $new_status
 * @param string $old_status
 * @param WP_Post $post
 * @return void
 */
function starg_save_first_submission_date( string $new_status, string $old_status, WP_Post $post ) {
	if ( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG !== $post->post_type ) { return; }

	if ( $new_status === 'pending' && $old_status !== 'pending' ) {
		if ( ! get_post_meta($post->ID, '_archival_first_submission', true)) {
			update_post_meta($post->ID, '_archival_first_submission', current_time('mysql'));
		}
	}
}
add_action( 'transition_post_status', 'starg_save_first_submission_date', 10, 3 );

/**
 * Delete the uploaded files if a post is about to be deleted.
 * This function fires before the post is actually deleted, because we need to check the post meta for the correct name of the sip folder.
 * @param int $post_id
 * @param WP_Post $post
 * @return void
 */
function starg_remove_uploads_on_backend_removal( int $post_id, WP_Post $post ): void {
	if ( ! is_admin() ) { return; }
	if ( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG !== $post->post_type ) { return; }

	$sip_folder_id = get_post_meta( $post_id, '_archival_sip_folder', true );
	if ( ! $sip_folder_id ) {
		$logging = apply_filters( 'starg/logging', null );
		if ( $logging instanceof Starg_Logging ) {
			// translators: %1$s: Post-ID.
			$logging->create_log_entry( sprintf( esc_html__( 'Trying to delete the uploads of post %s. But this post has no sip_folder post_meta.', 'sip' ), $post_id ) );
		}
		return;
	}

	$upload_path = starg_get_archival_upload_path() . $post->post_author . '/' . esc_attr( $sip_folder_id );
	starg_remove_SIP( $upload_path );
}
add_action( 'before_delete_post', 'starg_remove_uploads_on_backend_removal', 20, 2 );
