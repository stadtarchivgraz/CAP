<?php
// Template Name: SIP Upload
if(isset($_POST) && isset($_POST['save-sip'])) {
	$current_locale = strtolower(get_locale());
	$user_id = get_current_user_id();
	$archives = false;
	if($_POST['archival_ID']) {
		$archives = get_the_terms( $_POST['archival_ID'], 'archive');
	}
	$archival_tags = wp_list_pluck(json_decode(stripcslashes($_POST['archival_tags']), true), 'value');

	$post_data = array(
		'post_content'          => $_POST['archival_description'],
		'post_title'            => $_POST['archival_title'],
		'post_type'             => 'archival',
		'tax_input'    => array(
			'archival_tag'     => $archival_tags,
			'archive' => ($archives)?$archives[0]->term_id:get_user_meta($user_id, 'user_archive', true),
		)
	);
	if($_POST['archival_ID']) {
		$post_data['ID'] = $_POST['archival_ID'];
		$post_data['post_author'] = get_post_field( 'post_author', $_POST['archival_ID'] );
		$post_data['post_status'] = get_post_field( 'post_status', $_POST['archival_ID'] );
	}
	if(isset($_POST['archival_originator'])) {
		$post_data['meta_input']['_archival_originator'] = $_POST['archival_originator'];
	}
	if($_POST['archival_single_date']) {
		$post_data['meta_input']['_archival_from'] = $_POST['archival_single_date'];
	} else {
		if($_POST['archival_date_range']) {
			$post_data['meta_input']['_archival_from'] = $_POST['archival_date_range'][0] . '-01-01 00:00:00';
			$post_data['meta_input']['_archival_to'] = $_POST['archival_date_range'][1] . '-12-31 23:59:59';
		}
	}
	if(isset($_POST['archival_address'])) {
		$post_data['meta_input']['_archival_address'] = $_POST['archival_address'];
	}
	if(isset($_POST['archival_lat'])) {
		$post_data['meta_input']['_archival_lat'] = $_POST['archival_lat'];
	}
	if(isset($_POST['archival_lng'])) {
		$post_data['meta_input']['_archival_lng'] = $_POST['archival_lng'];
	}
	if(isset($_POST['archival_area'])) {
		$post_data['meta_input']['_archival_area'] = $_POST['archival_area'];
	}
	if(isset($_POST['archival_upload_purpose'])) {
		$post_data['meta_input']['_archival_upload_purpose'] = $_POST['archival_upload_purpose'];
	}
	if(isset($_POST['archival_blocking_time'])) {
		$post_data['meta_input']['_archival_blocking_time'] = $_POST['archival_blocking_time'];
	}
	if($sip_custom_meta = carbon_get_theme_option('sip_custom_meta' )) {
		foreach($sip_custom_meta as $custom_meta) {
			$meta_name = sanitize_title( $custom_meta['sip_custom_meta_title_' . $current_locale] );
			if(isset($_POST['_archival_' . $meta_name])) {
				$post_data['meta_input']['_archival_' . $meta_name] = $_POST['_archival_' . $meta_name];
			}
		}
		}
	if(isset($_POST['archival_right_transfer'])) {
		$post_data['meta_input']['_archival_right_transfer'] = $_POST['archival_right_transfer'];
	}
	if(isset($_POST['archival_numeration'])) {
		$post_data['meta_input']['_archival_numeration'] = $_POST['archival_numeration'];
	}
	if(isset($_POST['archival_annotation'])) {
		$post_data['meta_input']['_archival_annotation'] = $_POST['archival_annotation'];
	}
	if($sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta' )) {
		foreach($sip_custom_archival_user_meta as $custom_archival_user_meta) {
			$meta_name = sanitize_title( $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale] );
			if(isset($_POST['_archival_' . $meta_name])) {
				$post_data['meta_input']['_archival_' . $meta_name] = $_POST['_archival_' . $meta_name];
			}
		}
	}
	if($_GET['sipFolder']) {
		$post_data['meta_input']['_archival_sip_folder'] = $_GET['sipFolder'];
	}

	$post_id = wp_insert_post($post_data);
	if(!is_wp_error($post_id)){
		$pages = get_pages(array(
			'meta_key' => '_wp_page_template',
			'meta_value' => 'sip-archival.php',
			'hierarchical' => 0
		));
		wp_redirect(get_the_permalink($pages[0]) . $post_id);
	}else{
		//there was an error in the post insertion,
		echo $post_id->get_error_message();
	}
}

if(isset($_GET['sipFolder']) && $_GET['sipFolder'] && isset($_GET['submit']) && $_GET['submit']) {
	global $wpdb;
	$archival_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder' AND meta_value = %s", $_GET['sipFolder']));
	if($archival_id) {
		$post_data = array(
			'ID'          => $archival_id,
			'post_status' => 'pending',
		);
		$post_id   = wp_update_post( $post_data );
		if ( ! is_wp_error( $post_id ) ) {
			unset( $_GET );
		} else {
			//there was an error in the post insertion,
			echo $post_id->get_error_message();
		}
	}
}

if(isset($_GET['sipFolder']) && $_GET['sipFolder'] && isset($_GET['accept']) && $_GET['accept']) {
	global $wpdb;
	$archival_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder' AND meta_value = %s", $_GET['sipFolder']));
	if($archival_id) {
		$post_data = array(
			'ID'          => $archival_id,
			'post_status' => 'publish',
		);
		$post_id   = wp_update_post( $post_data );
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta($post_id, '_archival_archivar_user_id', get_current_user_id());
			unset( $_GET );
		} else {
			//there was an error in the post insertion,
			echo $post_id->get_error_message();
		}
	}
}

if(isset($_GET['sipFolder']) && $_GET['sipFolder'] && isset($_GET['decline']) && $_GET['decline']) {
	global $wpdb;
	$archival_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder' AND meta_value = %s", $_GET['sipFolder']));
	if($archival_id) {
		$author_id = get_post_field('post_author', $archival_id);
		$sip_folder = carbon_get_theme_option( 'sip_upload_path' ) . $author_id . '/' . $_GET['sipFolder'] . '/';
		if(is_dir($sip_folder)) {
			removeSIP( $sip_folder );
		}
		wp_delete_post($archival_id, true);
		$pages = get_pages(array(
		    'meta_key' => '_wp_page_template',
		    'meta_value' => 'sip-archive.php',
		    'hierarchical' => 0
		));
		wp_redirect(get_the_permalink($pages[0]));
	}
}

if(isset($_COOKIE['sip_file_size'])) {
	unset($_COOKIE['sip_file_size']);
	setcookie('sip_file_size', '', -1, '/');
}

get_header();

while ( have_posts() ) :
	the_post();
	/* Start the Loop */
	if(!isset($_GET['sipFolder'])) :
		include( dirname( __DIR__ ) . '/template-parts/content-page.php' );
	else :
		include( dirname( __DIR__ ) . '/template-parts/content-sip-form.php' );
	endif;
endwhile; // End of the loop.

get_footer();

