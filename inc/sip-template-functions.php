<?php
/**
 * Add additional content to some pages.
 * This added content might be a login form, an upload form, a profile form or some text.
 * This is totally unexpected and should not be solved this way!
 * @param string $content
 * @return string
 * @todo: remove!
 */
function starg_add_sip_forms_to_content( string $content ) {
	if ( ! is_page() ) { return $content; }

	// if a user is not logged in we only display the login form.
	if ( ! is_user_logged_in() ) {
		return $content . '<div class="container sip">' . wp_login_form( array( 'echo' => false, 'form_id' => 'archival_loginform', ) ) . '</div>';
	}

	$current_locale   = strtolower( get_locale() );
	$sip_archive_role = ( carbon_get_theme_option( 'sip_archive_role' ) ) ? carbon_get_theme_option( 'sip_archive_role' ) : 'edit_others_posts';
	if ( is_page_template('sip-upload.php') ) {
		if ( ! get_user_meta( get_current_user_id(), 'user_privacy_policy_approval', true ) ) {
			return $content . '<div class="container sip">' . wpautop( get_option( '_sip_update_profile_text_' . $current_locale ) ) . '</div>';
		} else {
			return $content . starg_get_sip_form( 'uploadform.php' );
		}
	} elseif ( is_page_template('sip-profile.php') ) {
		 return $content . starg_get_sip_form( 'profileform.php' );
	} elseif ( is_page_template( 'sip-archive.php' ) && current_user_can( $sip_archive_role ) ) {
		return $content . starg_get_sip_form( 'filterform.php' );
	}
	
	return $content;
}
// add_filter( 'the_content', 'starg_add_sip_forms_to_content' );

function starg_archival_loginform_top( $content, $args ) {
	$current_locale = strtolower( get_locale() );
	if ( $args['form_id'] !== 'archival_loginform' ) {
		return $content;
	}

	return wpautop( wp_kses_post( get_option( '_sip_register_text_' . $current_locale ) ) );
}
add_filter( 'login_form_top', 'starg_archival_loginform_top', 10, 2 );

/**
 * Overwrites the original single-template for the archival CPT.
 */
function starg_load_archival_template( $template ) {
	global $post;

	if ( 'archival' !== $post->post_type ) {
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
	// todo: add default for the users archive.
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
						'selected'      => $user_archive
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

function starg_save_sip_user_archive_field( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST[ 'user_archive' ] ) ) {
		return false;
	}

	update_user_meta( $user_id, 'user_archive', sanitize_text_field( $_POST[ 'user_archive' ] ) );
}
add_action( 'user_register', 'starg_save_sip_user_archive_field' );
add_action( 'profile_update', 'starg_save_sip_user_archive_field' );

/**
 * Change the main query based on the current user.
 * @param WP_Query $query
 */
function starg_sip_admin_archivals( WP_Query $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return $query;
	}

	if ( 'archival' !== $query->query_vars[ 'post_type' ] ) {
		return $query;
	}

	$user_id = get_current_user_id();
	// only display archival posts from the current user if not admin!
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		$query->set( 'author', $user_id );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		$user_archive = get_user_meta( $user_id, 'user_archive', true );
		$tax_query = array(
			array(
				'taxonomy' => 'archive',
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

/*
add_action('pre_get_posts', function($query) {
	print_r($query);
	if (!is_admin() && $query->is_main_query() && is_singular('archival')) {
		print_r($query->query_vars);
		$post_id = get_queried_object_id();
		$current_language = pll_current_language();

		// Fallback auf Originalsprache, wenn keine Übersetzung existiert
		if (!pll_get_post($post_id, $current_language)) {
			$default_language = pll_default_language();
			$fallback_post_id = pll_get_post($post_id, $default_language);

			if ($fallback_post_id) {
				// Setze den Beitrag für die Anfrage
				$query->set('p', $fallback_post_id);
				$query->set('lang', $current_language);
			} else {
				// Keine Übersetzung oder Fallback vorhanden - verhindere 404
				$query->set_404(false);
			}
		}
	}
});
*/

/**
 * Change the return value for the WP-Function "the_permalink" for the archival single page.
 * This is totally unexpected and should not be solved this way!
 * This function is not in use anymore!
 * @todo: remove!
 */
function starg_adjust_the_permalink_for_archivals( $url, $post ) {
	if ( ! $post || 'archival' !== $post->post_type ) {
		return $url;
	}

	$pages = get_pages( array(
		'meta_key'     => '_wp_page_template',
		'meta_value'   => 'sip-archival.php',
		'hierarchical' => 0,
	) );
	if ( ! $pages ) { return $url; }

	$slug = ( $post->post_name ) ? $post->post_name : $post->ID;
	$url  = get_the_permalink( $pages[0] ) . $slug;

	return $url;
}
// add_filter( 'the_permalink', 'starg_adjust_the_permalink_for_archivals', 10, 2 );
