<?php
/*
 Plugin Name: SIP
 Description: Plugin for creating Submission Information Packages (SIPs) from archival records. The archival records are provided by users. The archivist can choose whether to create a SIP or reject it.
 Author: Stadtarchiv Graz, Guido Handrick
 Version: 3.3.0
 Author URI: https://www.grazmuseum.at/stadtarchiv/
 Text Domain: sip
 Domain Path: /languages/
 Requires PHP: 8.1
 Requires at least: 6.0
 Used Libraries: php-clamav (https://github.com/appwrite/php-clamav), composer, dropzone (https://github.com/dropzone/dropzone/), carbon-fields (https://github.com/htmlburger/carbon-fields), getID3 (https://github.com/JamesHeinrich/getID3), pdf-to-image (https://github.com/spatie/pdf-to-image), htmltopdf (https://github.com/spipu/html2pdf), TCPDF (https://github.com/tecnickcom/TCPDF), Notification (https://bracketspace.com)
 */

if (! defined('WPINC')) { die; }

define( 'STARG_SIP_PLUGIN_VERSION', '3.3.0' );
define( 'STARG_SIP_PLUGIN_NAME',    'SIP' );
define( 'STARG_SIP_PLUGIN_BASE_DIR', trailingslashit( dirname( __FILE__ ) ) );
define( 'STARG_SIP_PLUGIN_BASE_URL', plugin_dir_url( __FILE__ ) );

class Starg_Sip_Plugin {

	function __construct() {
		register_activation_hook( __FILE__,   array( 'Starg_Sip_Plugin', 'starg_sip_activate' ) );
		register_deactivation_hook( __FILE__, array( 'Starg_Sip_Plugin', 'starg_sip_deactivate' ) );

		// Load CPT and Taxonomies.
		require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/archival-cpt.php" );
		Archival_Custom_Posts::init();

		add_action( 'after_setup_theme',  array( 'Starg_Sip_Plugin', 'starg_load_additional_packages' ) );
		add_action( 'init',               array( 'Starg_Sip_Plugin', 'starg_load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( 'Starg_Sip_Plugin', 'starg_load_assets' ) );
		add_action( 'wp',                 array( 'Starg_Sip_Plugin', 'starg_delete_sip_cron_schedule' ) );

		add_action( 'archival_delete_cron_event', array( 'Starg_Sip_Plugin', 'starg_archival_delete_cron_job_function' ) );

		require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/email-helper.php" );
		add_action( 'init', array( 'Starg_Email_Helper', 'init' ) );

		// load the notification plugin instead.
		// todo: remove notification plugin after testing.
		// require_once( STARG_SIP_PLUGIN_BASE_DIR . "assets/notification/load.php" );
		// require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/sip-notifications.php" );


		require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/template-handling.php' );
		Starg_Template_Handling::init();

		require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/security-settings.php' );
		Starg_Security_Settings::init();

		require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/services.php" );
		Starg_Services::init();

		require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/db-query-helper.php" );
		require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/sip-functions.php" );
		require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/sip-template-functions.php" );
		require_once( STARG_SIP_PLUGIN_BASE_DIR . "admin/sip-options.php" );
		require_once( STARG_SIP_PLUGIN_BASE_DIR . "admin/sip-meta.php" );

		if ( is_admin() ) {
			require_once( STARG_SIP_PLUGIN_BASE_DIR . 'admin/admin-notification.php' );
			Starg_Admin_Notification::init();
			require_once( STARG_SIP_PLUGIN_BASE_DIR . 'admin/admin-pages.php' );
			Starg_Admin_Pages::init();
		}
	}

	/**
	 * Load the translations for the plugin.
	 */
	public static function starg_load_textdomain() {
		load_plugin_textdomain( 'sip', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// These translations are used to be able to display a translated post-status in the "template-parts/content-archivals-list.php" template.
		esc_attr__( 'draft', 'sip' );
		esc_attr__( 'pending', 'sip' );
		esc_attr__( 'publish', 'sip' );
	}

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
	public static function starg_load_assets() {
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
			}
			body .bp-wrap{ z-index:1001;}';
		wp_add_inline_style( 'sip-full', $style_fixes );
	}

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
	public static function starg_load_additional_packages() {
		require_once( 'vendor/autoload.php' );
		\Carbon_Fields\Carbon_Fields::boot();
	}

	/**
	 * Checks every day for archival resources (SIPs) to delete.
	 */
	public static function starg_delete_sip_cron_schedule() {
		if ( ! wp_next_scheduled( 'archival_delete_cron_event' ) && carbon_get_theme_option( 'sip_cron_delete' ) ) {
			$archival_delete_cron = wp_schedule_event( time(), 'daily', 'archival_delete_cron_event', array(), true );
			if ( is_wp_error( $archival_delete_cron ) ) {
				$logging = apply_filters( 'starg/logging', null );
				if ($logging instanceof Starg_Logging) {
					// translators: %s: Errormessage.
					$logging->create_log_entry(sprintf(esc_html__( 'Archival delete cron not scheduled. see: %s', 'sip'), $archival_delete_cron->get_error_message() ));
				}
			}
		}
	}

	/**
	 * Determine which folders to delete after a set time.
	 * The time can be set in the backend.
	 */
	public static function starg_archival_delete_cron_job_function() {
		if ( ! carbon_get_theme_option( 'sip_cron_delete' ) || ! carbon_get_theme_option( 'sip_cron_delete_status' ) ) { return; }

		$days          = (int) esc_attr( carbon_get_theme_option( 'sip_cron_delete_days' ) ); // type:number
		$status        = array_map( 'esc_html', carbon_get_theme_option( 'sip_cron_delete_status' ) ); // type:multiselect
		$status        = array_flip( $status );
		$upload_folder = starg_get_archival_upload_path();
		$sip_folders   = array();
		$logging       = apply_filters( 'starg/logging', null );

		// get all uploaded folders by each user and check if a post exists. if not, this entry is considered abandoned.
		if ( isset( $status['upload'] ) ) {
			$user_ids             = get_users( array( 'fields' => 'ID' ) );
			$archival_sip_folders = DB_Query_Helper::starg_get_all_archival_sip_folders_from_posts();
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
			unset( $status['upload'] );
		}

		// Check each user-defined status (except 'upload') for archival posts that should be removed.
		if ( $status ) {
			$status_str           = "'" . implode( "','", array_flip( $status ) ) . "'";
			$post_date_filter     = date( 'Y-m-d 23:59:59', strtotime( "-$days days" ) );
			$archival_sip_folders = DB_Query_Helper::starg_get_posts_with_archival_sip_meta( $status_str, $post_date_filter );

			if ( $archival_sip_folders ) {
				foreach ( $archival_sip_folders as $archival_sip_folder ) {
					$single_sip_folder = $upload_folder . $archival_sip_folder->post_author . '/' . $archival_sip_folder->meta_value . '/';
					$sip_folders[]     = $single_sip_folder;
					$post_meta_deleted = delete_post_meta( $archival_sip_folder->ID, '_archival_sip_folder' );
					$post_deleted      = wp_trash_post( $archival_sip_folder->ID );

					if ( ! $post_meta_deleted ) {
						if ( $logging instanceof Starg_Logging ) {
							// translators: %1$s: Post-ID. %2$d: SIP folder.
							$logging->create_log_entry( sprintf( esc_html__( 'Post meta for post %1$d and SIP folder %2$s not deleted.', 'sip' ), $archival_sip_folder->ID, $single_sip_folder ) );
						}
					}
					if ( ! $post_deleted ) {
						if ( $logging instanceof Starg_Logging ) {
							// translators: %1$s: Post-ID. %2$d: SIP folder.
							$logging->create_log_entry( sprintf( esc_html__( 'Post with ID %1$d and SIP folder %2$s not deleted.', 'sip' ), $archival_sip_folder->ID, $single_sip_folder ) );
						}
					}
				}
			}
		}

		if ( empty( $sip_folders) ) { return; }

		foreach ( $sip_folders as $sip_folder ) {
			if ( ! is_dir( $sip_folder ) ) { continue; }

			starg_remove_SIP( $sip_folder );
		}
	}

	/**
	 * Activate the plugin.
	 */
	public static function starg_sip_activate() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "activate-plugin_{$plugin}" );

		// Create our custom post type and its custom taxonomies.
		Archival_Custom_Posts::starg_custom_post_type_archival();
		Archival_Custom_Posts::starg_create_custom_taxonomies();

		// create the needed capabilities for users to be able to work with our custom post type and custom capabilities.
		require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/user-helper.php" );
		Starg_User_Helper::starg_create_user_role();
		Starg_User_Helper::starg_set_capabilities_for_archival_records();

		Starg_Template_Handling::starg_tab_rewrites();

		// Clear the permalinks after the post type has been registered.
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook.
	 */
	public static function starg_sip_deactivate() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );

		// Unregister the custom post type and its custom taxonomies, so the rules are no longer in memory.
		unregister_post_type( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG );
		unregister_taxonomy( Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG );
		unregister_taxonomy( Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG );

		// remove the capabilities for our custom post type and custom taxonomies.
		require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/user-helper.php" );
		Starg_User_Helper::starg_remove_user_role();
		Starg_User_Helper::starg_remove_capabilities_for_archival_records();

		// Clear the permalinks to remove our post type's rules from the database.
		flush_rewrite_rules();

		$timestamp = wp_next_scheduled('archival_delete_cron_event');
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'archival_delete_cron_event');
		}
	}

}

$starg_sip_plugin = new Starg_Sip_Plugin;

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
