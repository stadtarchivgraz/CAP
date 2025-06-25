<?php
function sip_forms( $content ) {
	$current_locale = strtolower(get_locale());
	$sip_archive_role = (carbon_get_theme_option( 'sip_archive_role' ))?carbon_get_theme_option( 'sip_archive_role' ):'edit_others_posts';
	if ( is_page() && is_page_template('sip-upload.php') ) {
		if(!is_user_logged_in()) {
			return $content.'<div class="container sip">' . wp_login_form(array('echo' => false, 'form_id' => 'archival_loginform' )) . '</div>';
		} elseif (!get_user_meta(get_current_user_id(), 'user_privacy_policy_approval', true)) {
			return $content.'<div class="container sip">' . wpautop(get_option('_sip_update_profile_text_' . $current_locale)) . '</div>';
		} else return $content.get_sip_form('uploadform.php');
	} elseif ( is_page() && is_page_template('sip-profile.php') ) {
		if(!is_user_logged_in()) {
			return $content. '<div class="container sip">' . wp_login_form(array('echo' => false, 'form_id' => 'archival_loginform' )) . '</div>';
		} else return $content.get_sip_form('profileform.php');
	} elseif ( is_page() && is_page_template('sip-archive.php') && current_user_can($sip_archive_role) ) {
		return $content.get_sip_form('filterform.php');
	} else {
		return $content;
	}
}
add_filter( 'the_content', 'sip_forms' );

function archival_loginform_top( $content, $args ) {
	$current_locale = strtolower(get_locale());
	if($args['form_id'] == 'archival_loginform') {
		return wpautop(get_option('_sip_register_text_' . $current_locale));
	}

}
add_filter( 'login_form_top', 'archival_loginform_top', 10, 2 );

function load_archival_template( $template ) {
	global $post;

	if ( 'archival' === $post->post_type ) {
		return plugin_dir_path( __DIR__ ) . 'single-archival.php';
	}

	return $template;
}
add_filter( 'single_template', 'load_archival_template' );

function sip_user_archive_field($user){
	$user_archive = get_user_meta($user->ID, 'user_archive', true);
	?>
	<table class="form-table">
		<tr>
			<th><label for="company"><?php _e('Archive', 'sip'); ?></label></th>
			<td>
				<?php
				if(current_user_can('manage_options')) {
					$args = array(
						'name'          => 'user_archive',
						'id'            => 'archive',
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
					$archive = get_term($user_archive, 'archive');
					echo $archive->name;
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'sip_user_archive_field' );
add_action( 'edit_user_profile', 'sip_user_archive_field' );
add_action( "user_new_form", "sip_user_archive_field" );

function save_sip_user_archive_field($user_id){
	# again do this only if you can
	if(!current_user_can('manage_options'))
		return false;
	update_user_meta($user_id, 'user_archive', $_POST['user_archive']);
}
add_action('user_register', 'save_sip_user_archive_field');
add_action('profile_update', 'save_sip_user_archive_field');

function sip_admin_archivals($query) {
	if ( is_admin() && $query->is_main_query() ) {
		if ( 'archival' === $query->query_vars['post_type']) {
			$user_id = get_current_user_id();
			if(!current_user_can('edit_others_posts')) {
				$query->set( 'author', $user_id );
			}
			if(!current_user_can('manage_options')) {
				$user_archive = get_user_meta($user_id, 'user_archive', true);
				$tax_query = array(
					array(
						'taxonomy' => 'archive',
						'field' => 'term_id',
						'terms' => $user_archive
					)
				);
				$query->set( 'tax_query', $tax_query );
			}
		}
	}
	return $query;
}
add_filter('pre_get_posts', 'sip_admin_archivals' );

function sip_upload_types($existing_mimes = array()) {
	// allow .woff
	$existing_mimes['svg'] = 'image/svg+xml';
	$existing_mimes['xml-text'] = 'text/xml';
	$existing_mimes['xml'] = 'application/xml';

	return $existing_mimes;
}
add_filter('upload_mimes', 'sip_upload_types');
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
add_filter('the_permalink', function($url, $post) {
	if (is_object($post) && $post->post_type === 'archival') { // Ersetze 'archival' durch deinen Custom Post Type
		$pages = get_pages(array(
			'meta_key' => '_wp_page_template',
			'meta_value' => 'sip-archival.php',
			'hierarchical' => 0
		));
        $slug = ($post->post_name)?$post->post_name:$post->ID;
        $url = get_the_permalink($pages[0]) . $slug;
	}
	return $url;
}, 10, 2);