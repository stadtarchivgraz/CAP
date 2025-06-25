<?php
/*
 Plugin Name: SIP
 Description: Plugin for Archival SIP
 Author: Guido Handrick
 Version: 2.0
 Author URI: http://guido-handrick.info
 Text Domain: sip
 Domain Path: /languages/
 */

include(dirname(__FILE__)."/archival.php");

/**
 * Activate the plugin.
 */
function sip_activate() {
	//dbi_load_carbon_fields();
	// Trigger our function that registers the custom post type plugin.
	custom_post_type_archival();
	// Clear the permalinks after the post type has been registered.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sip_activate' );

/**
 * Deactivation hook.
 */
function sip_deactivate() {
	// Unregister the post type, so the rules are no longer in memory.
	unregister_post_type( 'archival' );
	// Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules();
	$timestamp = wp_next_scheduled('archival_delete_cron_event');
	if ($timestamp) {
		wp_unschedule_event($timestamp, 'archival_delete_cron_event');
	}

}
register_deactivation_hook( __FILE__, 'sip_deactivate' );

function load_sip_textdomain() {
	load_plugin_textdomain( 'sip', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'load_sip_textdomain' );

function sip_load_plugin_css() {
	$plugin_url = plugin_dir_url( __FILE__ );
	wp_enqueue_style( 'sip-full', $plugin_url . 'css/sip-full.css', array(), '2.0', 'all' );

}
add_action( 'wp_enqueue_scripts', 'sip_load_plugin_css' );

function sip_load() {
	require_once( 'vendor/autoload.php' );
	\Carbon_Fields\Carbon_Fields::boot();

	if ( ! current_user_can( 'edit_others_posts' ) ) {
		add_filter('show_admin_bar', '__return_false');
	}
}
add_action( 'after_setup_theme', 'sip_load' );
function sip_page_template( $page_template ){
	if ( get_page_template_slug() == 'sip-upload.php' ) {
		$page_template = dirname( __FILE__ ) . '/page-templates/sip-upload.php';
	}
	if ( get_page_template_slug() == 'sip-profile.php' ) {
		$page_template = dirname( __FILE__ ) . '/page-templates/sip-profile.php';
	}
	if ( get_page_template_slug() == 'sip-archive.php' ) {
		$page_template = dirname( __FILE__ ) . '/page-templates/sip-archive.php';
	}
	if ( get_page_template_slug() == 'sip-archival.php' ) {
		$page_template = dirname( __FILE__ ) . '/page-templates/sip-archival.php';
	}
	return $page_template;
}
add_filter( 'page_template', 'sip_page_template' );
function sip_add_template_to_select( $post_templates ) {
	$post_templates['sip-archival.php'] = __('SIP Archival', 'sip');
	$post_templates['sip-upload.php'] = __('SIP Upload', 'sip');
	$post_templates['sip-profile.php'] = __('SIP Profile', 'sip');
	$post_templates['sip-archive.php'] = __('SIP Archive', 'sip');
	return $post_templates;
}
add_filter( 'theme_page_templates', 'sip_add_template_to_select', 10, 1 );
function tab_rewrites() {
	add_rewrite_tag('%tab%', '([^&]+)');
	add_rewrite_rule('([^/]*)/tab/([^/]*)/?','index.php?pagename=$matches[1]&tab=$matches[2]','top');
	add_rewrite_rule('([^/]*)/([^/]*)/tab/([^/]*)/?','index.php?pagename=$matches[2]&tab=$matches[3]','top');
}
add_action('init', 'tab_rewrites');

function my_custom_cron_schedule() {
	if (!wp_next_scheduled('archival_delete_cron_event') && carbon_get_theme_option('sip_cron_delete')) {
		wp_schedule_event(time(), 'daily', 'archival_delete_cron_event');
	}
}
add_action('wp', 'my_custom_cron_schedule');

require_once(dirname(__FILE__)."/assets/notification/load.php");

include(dirname(__FILE__)."/inc/sip-functions.php");
include(dirname(__FILE__)."/inc/sip-template-functions.php");
include(dirname(__FILE__)."/admin/sip-options.php");
include(dirname(__FILE__)."/admin/sip-meta.php");
include(dirname(__FILE__)."/inc/sip-notifications.php");

function get_folder_creation_days_ago($folder_path, $timestamp = false) {
	$files = glob($folder_path . '/*');
	if (empty($files)) {
		return false; // Ordner ist leer, kein Datum verfügbar
	}
	$current_time = time();
	$oldest_file = min(array_map('filemtime', $files));
	if(!$timestamp) {
		return floor(($current_time - $oldest_file) / (60 * 60 * 24));
	} else return $oldest_file;
	//return date('Y-m-d H:i:s', $oldest_file);
}


function archival_delete_cron_job_function() {
	if(carbon_get_theme_option( 'sip_cron_delete' )) {
		global $wpdb;
		$days          = carbon_get_theme_option( 'sip_cron_delete_days' );
		$status        = carbon_get_theme_option( 'sip_cron_delete_status' );
		$upload_folder = carbon_get_theme_option( 'sip_upload_path' );
		$sip_folders   = array();
		if ( in_array( 'upload', $status ) ) {
			$user_ids             = get_users( array( 'fields' => 'ID' ) );
			$archival_sip_folders = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder'" );
			$archival_sip_folders = wp_list_pluck( $archival_sip_folders, 'meta_value' );
			foreach ( $user_ids as $user_id ) {
				$user_upload_folder = $upload_folder . $user_id . '/';
				if ( file_exists( $user_upload_folder ) ) {
					$user_sip_folders = glob( $user_upload_folder . '*', GLOB_ONLYDIR );
					foreach ( $user_sip_folders as $user_sip_folder ) {
						$user_sip_folder_sip = basename( $user_sip_folder );
						if ( ! in_array( $user_sip_folder_sip, $archival_sip_folders ) && $days <= get_folder_creation_days_ago( $user_sip_folder ) ) {
							$sip_folders[] = $user_sip_folder;
						}
					}
				}
			}
		}
		$status_str           = "'" . implode( "','", $status ) . "'";
		$archival_sip_folders = $wpdb->get_results( $wpdb->prepare( "SELECT ID, meta_value, post_author FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id = ID WHERE meta_key = '_archival_sip_folder' AND post_status IN ($status_str) AND post_date <= %s", date( 'Y-m-d 23:59:59', strtotime( "-$days days" ) ) ) );
		foreach ( $archival_sip_folders as $archival_sip_folder ) {
			$sip_folders[] = $upload_folder . $archival_sip_folder->post_author . '/' . $archival_sip_folder->meta_value . '/';
			delete_post_meta($archival_sip_folder->ID, '_archival_sip_folder' );
		}
		foreach ( $sip_folders as $sip_folder ) {
			if(is_dir($sip_folder)) {
				removeSIP($sip_folder);
			}
		}
	}
}
add_action('archival_delete_cron_event', 'archival_delete_cron_job_function');

add_filter('query_vars', function($vars) {
	$vars[] = 'archival_name';
	return $vars;
});

add_action('init', function() {
	// Überprüfen, ob Polylang aktiv ist
	if (function_exists('pll_get_post') && function_exists('pll_languages_list')) {
		$languages = wp_list_pluck(pll_languages_list(array('fields' => array())), 'slug'); // Sprach-Slugs erhalten
		$default_language = pll_default_language('slug');
		$pages = get_pages(array(
			'meta_key' => '_wp_page_template',
			'meta_value' => 'sip-archival.php',
			'hierarchical' => 0
		));
		if ($pages) {
			foreach ($languages as $lang) {
				$translated_page_slug = get_page_uri(pll_get_post($pages[0]->ID, $lang)); // Übersetzter Slug
				if ($translated_page_slug) {
					// Rewrite-Regel für jede Sprache und den übersetzten Slug hinzufügen
					add_rewrite_rule(
						"^$lang/$translated_page_slug/?$", // Nur der übersetzte Slug
						"index.php?pagename=$translated_page_slug&lang=$lang",
						'top'
					);
					add_rewrite_rule(
						"^$lang/$translated_page_slug/([^/]*)/?$", // Übersetzter Slug in der Sprache
						"index.php?pagename=$translated_page_slug&archival_name=\$matches[1]&lang=$lang",
						'top'
					);
					if($lang == $default_language) {
						add_rewrite_rule(
							"^$translated_page_slug/?$", // Nur der übersetzte Slug
							"index.php?pagename=$translated_page_slug&lang=$lang",
							'top'
						);
						add_rewrite_rule(
							"^$translated_page_slug/([^/]*)/?$", // Übersetzter Slug in der Sprache
							"index.php?pagename=$translated_page_slug&archival_name=\$matches[1]&lang=$lang",
							'top'
						);
					}
				}
			}
		}
	}
});

__('draft', 'sip');
__('pending', 'sip');
__('publish', 'sip');