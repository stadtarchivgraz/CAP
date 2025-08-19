<?php
/*
 Plugin Name: SIP
 Description: Plugin for creating Submission Information Packages (SIPs) from archival records. The archival records are provided by users. The archivist can choose whether to create a SIP or reject it.
 Author: Stadtarchiv Graz, Guido Handrick
 Version: 3.0.2
 Author URI: http://guido-handrick.info
 Text Domain: sip
 Domain Path: /languages/
 Used Libraries: php-clamav (https://github.com/appwrite/php-clamav), composer, dropzone (https://github.com/dropzone/dropzone/), carbon-fields (https://github.com/htmlburger/carbon-fields), getID3 (https://github.com/JamesHeinrich/getID3), pdf-to-image (https://github.com/spatie/pdf-to-image), htmltopdf (https://github.com/spipu/html2pdf), TCPDF (https://github.com/tecnickcom/TCPDF), Notification (https://bracketspace.com)
 */

if (! defined('WPINC')) { die; }

define( 'STARG_SIP_PLUGIN_VERSION', '3.0.2' );
define( 'STARG_SIP_PLUGIN_NAME',    'SIP' );
define( 'STARG_SIP_PLUGIN_BASE_DIR', trailingslashit( dirname( __FILE__ ) ) );
define( 'STARG_SIP_PLUGIN_BASE_URL', plugin_dir_url( __FILE__ ) );

// Load CPT and Taxonomies.
require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/archival-cpt.php" );
Archival_Custom_Posts::init();

/**
 * Load the translations for the plugin.
 */
function starg_load_textdomain() {
	load_plugin_textdomain( 'sip', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'starg_load_textdomain' );

/**
 * Load additional assets for the plugin.
 *
 * currently used assets:
 *   - Bigger Picture: https://github.com/henrygd/bigger-picture
 *   - Leaflet: https://github.com/Leaflet/Leaflet
 *   - Leaflet Awesome Markers: https://github.com/lennardv2/Leaflet.awesome-markers
 *   - Leaflet Control Geocoder: https://github.com/perliedman/leaflet-control-geocoder
 *   - Mapbox: https://github.com/mapbox/mapbox-gl-js
 *   - Leaflet Mapbox: https://github.com/mapbox/mapbox-gl-leaflet
 *   - Leaflet Markercluster: https://github.com/Leaflet/Leaflet.markercluster
 *   - Leaflet Area Selection: https://github.com/bopen/leaflet-area-selection
 *   - tagify: https://github.com/yairEO/tagify
 *   - noUiSlider: https://github.com/leongersen/noUiSlider
 *   - dropzone: https://github.com/dropzone/dropzone
 */
function starg_load_assets() {
	wp_enqueue_style( 'sip-full', STARG_SIP_PLUGIN_BASE_URL . 'css/sip-full.css', array(), STARG_SIP_PLUGIN_VERSION, 'all' );

	$bigger_picture_version         = '1.1.19';
	$leaflet_version                = '1.9.3';
	$geocoder_version               = '1.13.0';
	$leaflet_markers_version        = '2.0.2';
	$mapbox_version                 = '...';//todo.
	$leaflet_mapbox_version         = '...';//todo.
	$leaflet_markercluster_version  = '...';//todo.
	$leaflet_area_selection_version = '...';//todo.
	$tagify_version                 = '4.17.9';
	$nouislider_version             = '15.8.1';
	$dropzone_version               = '5.9.3';

	$script_strategy = array( 'in_footer' => true, 'strategy' => 'defer', );

	// only load bigger-pictrue if we're on the single-template for the archival CPT.
	if ( is_singular( array( 'archival', ) ) || is_page_template( array( 'sip-archival.php', 'sip-upload.php', ) ) ) {
		wp_enqueue_style( 'bigger_picture', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/bigger-picture.css', array(), $bigger_picture_version );
		wp_enqueue_script( 'bigger_picture', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/bigger-picture.min.js', array(), $bigger_picture_version, $script_strategy );
	}

	if ( is_singular( array( 'archival', ) ) || is_page_template( array( 'sip-archival.php', 'sip-upload.php', ) ) ) {
		wp_enqueue_style( 'leaflet_main', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/leaflet.css', array(), $leaflet_version );
		wp_enqueue_style( 'leaflet_markers', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/leaflet.awesome-markers.css', array(), $leaflet_markers_version );
		wp_enqueue_style( 'geocoder', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/Control.Geocoder.css', array(), $geocoder_version );
		wp_enqueue_style( 'mapbox', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/mapbox-gl.css', array(), $mapbox_version );
		wp_enqueue_style( 'leaflet_mapbox', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/MarkerCluster.css', array(), $leaflet_mapbox_version );
		wp_enqueue_style( 'leaflet_markercluster', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/MarkerCluster.Default.css', array(), $leaflet_markercluster_version );
		wp_enqueue_style( 'leaflet_area_selection', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/leaflet.area-selection.css', array(), $leaflet_area_selection_version );

		wp_enqueue_script( 'leaflet_main', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/leaflet.js', array(), $leaflet_version, $script_strategy );
		wp_enqueue_script( 'leaflet_markers', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/leaflet.awesome-markers.min.js', array(), $leaflet_markers_version, $script_strategy );
		wp_enqueue_script( 'geocoder', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/Control.Geocoder.js', array(), $geocoder_version, $script_strategy );
		wp_enqueue_script( 'mapbox', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/mapbox-gl.js', array(), $mapbox_version, $script_strategy );
		wp_enqueue_script( 'leaflet_mapbox', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/leaflet-mapbox-gl.js', array(), $leaflet_mapbox_version, $script_strategy );
		wp_enqueue_script( 'leaflet_markercluster', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/leaflet.markercluster.js', array(), $leaflet_markercluster_version, $script_strategy );
		wp_enqueue_script( 'leaflet_area_selection', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/leaflet.area-selection.js', array(), $leaflet_area_selection_version, $script_strategy );
	}

	if ( is_page_template( array( 'sip-upload.php' ) ) ) {
		wp_enqueue_script( 'tagify', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/tagify.js', array(), $tagify_version, $script_strategy );
		wp_enqueue_script( 'tagify_polifills', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/tagify.polyfills.min.js', array(), $tagify_version, $script_strategy );
		wp_enqueue_style( 'tagify', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/tagify.css', array(), $tagify_version );

		wp_enqueue_script( 'nouislider', STARG_SIP_PLUGIN_BASE_URL . 'assets/js/nouislider.min.js', array(), $nouislider_version, $script_strategy );
		wp_enqueue_style( 'nouislider', STARG_SIP_PLUGIN_BASE_URL . 'assets/css/nouislider.min.css', array(), $nouislider_version );
	}

	if ( is_page_template( array( 'sip-upload.php' ) ) ) {
		wp_enqueue_style( 'dropzone', STARG_SIP_PLUGIN_BASE_URL . 'vendor/enyo/dropzone/dist/min/dropzone.min.css', array(), $dropzone_version );
		// wp_enqueue_style( 'dropzone_basic', STARG_SIP_PLUGIN_BASE_URL . 'vendor/enyo/dropzone/dist/min/basic.min.css', array(), $dropzone_version );
		wp_enqueue_script( 'dropzone', STARG_SIP_PLUGIN_BASE_URL . 'vendor/enyo/dropzone/dist/min/dropzone.min.js', array(), $dropzone_version, $script_strategy );
	}

	if ( is_user_logged_in() && is_page_template( array( 'sip-profile.php', ) ) ) {
		wp_enqueue_script('utils');// WordPress Script!
		wp_enqueue_script('user-profile');// WordPress Script!
	}

	$style_fixes = '.sip .archival-pagination {
			margin-top: 21px;
		}
		.sip .archival-pagination .page-numbers {
			padding: 12px;
			display: inline-block;
			font-size: 16px;
			text-align: center;
			line-height: 1;
			background-color: transparent;
			border: 1px solid #e6e8ea;/* #ecf8ff */
			border-radius: 4px;
			vertical-align: middle;
			box-shadow: none;
			margin-right: .5rem;
		}
		.sip .archival-pagination a.page-numbers:hover,
		.sip .archival-pagination .page-numbers.current,
		.sip .archival-pagination .page-numbers:hover {
			background-color: #f8f8f8;
			color: #333;
		}';
	wp_add_inline_style( 'sip-full', $style_fixes );
}
add_action( 'wp_enqueue_scripts', 'starg_load_assets' );

/**
 * Load the additional packages.
 * Currently used packages:
 *   - carbon-fields (https://github.com/htmlburger/carbon-fields)
 *   - composer
 *   - dropzone (https://github.com/dropzone/dropzone/)
 *   - getID3 (https://github.com/JamesHeinrich/getID3)
 *   - htmltopdf (https://github.com/spipu/html2pdf)
 *   - pdf-to-image (https://github.com/spatie/pdf-to-image)
 *   - php-clamav (https://github.com/appwrite/php-clamav)
 *   - TCPDF (https://github.com/tecnickcom/TCPDF)
 */
function starg_load_additional_packages() {
	require_once( 'vendor/autoload.php' );
	\Carbon_Fields\Carbon_Fields::boot();
}
add_action( 'after_setup_theme', 'starg_load_additional_packages' );

// todo: maybe install the plugin https://wordpress.org/plugins/notification/ instead of including it as an asset in the plugin?
require_once( STARG_SIP_PLUGIN_BASE_DIR . "assets/notification/load.php" );


require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/template-handling.php' );
Starg_Template_Handling::init();

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/security-settings.php' );
Starg_Security_Settings::init();

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'admin/admin-notification.php' );
Starg_Admin_Notification::init();

require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/services.php" );
Starg_Services::init();

require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/sip-functions.php" );
require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/sip-template-functions.php" );
require_once( STARG_SIP_PLUGIN_BASE_DIR . "admin/sip-options.php" );
require_once( STARG_SIP_PLUGIN_BASE_DIR . "admin/sip-meta.php" );

/**
 * Checks every day for archival resources (SIPs) to delete.
 */
function starg_delete_sip_cron_schedule() {
	if ( ! wp_next_scheduled( 'archival_delete_cron_event' ) && carbon_get_theme_option( 'sip_cron_delete' ) ) {
		wp_schedule_event( time(), 'daily', 'archival_delete_cron_event' );
	}
}
add_action( 'wp', 'starg_delete_sip_cron_schedule' );


/**
 * Determine which folders to delete after a set time.
 * The time can be set in the backend.
 */
function starg_archival_delete_cron_job_function() {
	if ( ! carbon_get_theme_option( 'sip_cron_delete' ) ) { return; }

	global $wpdb;
	$days          = (int) esc_attr( carbon_get_theme_option( 'sip_cron_delete_days' ) ); // type:number
	$status        = carbon_get_theme_option( 'sip_cron_delete_status' ); // type:multiselect
	$upload_folder = esc_attr( carbon_get_theme_option( 'sip_upload_path' ) ); // type:text
	$sip_folders   = array();

	if ( in_array( 'upload', $status ) ) {
		$user_ids             = get_users( array( 'fields' => 'ID' ) );
		$archival_sip_folders = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder'" );
		$archival_sip_folders = wp_list_pluck( $archival_sip_folders, 'meta_value' );
		foreach ( $user_ids as $single_user_id ) {
			$user_upload_folder = $upload_folder . $single_user_id . '/';
			if ( ! file_exists( $user_upload_folder ) ) {
				continue;
			}

			$user_sip_folders = glob( $user_upload_folder . '*', GLOB_ONLYDIR );
			foreach ( $user_sip_folders as $single_user_sip_folder ) {
				$user_sip_folder_sip = basename( $single_user_sip_folder );
				if ( ! in_array( $user_sip_folder_sip, $archival_sip_folders ) && $days <= starg_get_folder_creation_days_ago( $single_user_sip_folder ) ) {
					$sip_folders[] = $single_user_sip_folder;
				}
			}
		}
	}

	$status_str        = "'" . implode( "','", $status ) . "'";
	$archival_sips_sql = "SELECT ID, meta_value, post_author
		FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id = ID
		WHERE meta_key = '_archival_sip_folder'
			AND post_status IN (%s)
			AND post_date <= %s";
	$post_date_filter     = date( 'Y-m-d 23:59:59', strtotime( "-$days days" ) );
	$archival_sip_folders = $wpdb->get_results( $wpdb->prepare( $archival_sips_sql, $status_str, $post_date_filter ) );

	if ( ! $archival_sip_folders ) { return; }

	foreach ( $archival_sip_folders as $archival_sip_folder ) {
		$sip_folders[] = $upload_folder . $archival_sip_folder->post_author . '/' . $archival_sip_folder->meta_value . '/';
		delete_post_meta( $archival_sip_folder->ID, '_archival_sip_folder' );
	}
	foreach ( $sip_folders as $sip_folder ) {
		if ( is_dir( $sip_folder ) ) {
			starg_remove_SIP( $sip_folder );
		}
	}
}
add_action( 'archival_delete_cron_event', 'starg_archival_delete_cron_job_function' );

// These translations are used to be able to display a translated post-status in the "template-parts/content-archivals-list.php" template.
__('draft', 'sip');
__('pending', 'sip');
__('publish', 'sip');

/**
 * Activate the plugin.
 */
function starg_sip_activate() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "activate-plugin_{$plugin}" );

	// Create our custom post type and its custom taxonomies.
	Archival_Custom_Posts::starg_custom_post_type_archival();
	Archival_Custom_Posts::starg_create_custom_taxonomies();

	// create the needed capabilities for users to be able to work with our custom post type and custom capabilities.
	require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/user-helper.php" );
	Starg_User_Helper::starg_set_capabilities_for_archival_records();

	Starg_Template_Handling::starg_tab_rewrites();

	// Clear the permalinks after the post type has been registered.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'starg_sip_activate' );

/**
 * Deactivation hook.
 */
function starg_sip_deactivate() {
	 if ( ! current_user_can( 'manage_options' ) ) { return; }

	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "deactivate-plugin_{$plugin}" );

	// Unregister the custom post type and its custom taxonomies, so the rules are no longer in memory.
	unregister_post_type( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG );
	unregister_taxonomy( Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG );
	unregister_taxonomy( Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG );

	// remove the capabilities for our custom post type and custom taxonomies.
	require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/user-helper.php" );
	Starg_User_Helper::starg_remove_capabilities_for_archival_records();

	// Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules();

	$timestamp = wp_next_scheduled('archival_delete_cron_event');
	if ($timestamp) {
		wp_unschedule_event($timestamp, 'archival_delete_cron_event');
	}
}
register_deactivation_hook( __FILE__, 'starg_sip_deactivate' );


///////////////////////
///////// DEV /////////
///////////////////////

/**
 * Displays a nice Debug-Notification.
 */
function _starg_debug_var( $var = '' ) : void {
	if ( ! current_user_can('manage_options') ) { return; }

	echo '<div class="sip"><pre class="notification is-warning">';
	var_dump( $var );
	echo '</pre></div>';
}
