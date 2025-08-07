<?php
if (! defined('WPINC')) { die; }

/**
 * Manages and registers services via a custom WordPress filter,
 * allowing centralized access in templates and other parts of the plugin.
 *
 * @since: 3.0.0
 * @author: Hannes Z.
 */
class Starg_Services {

	public static function init() {
		/**
		 * Registers a class as service for updating the users password.
		 * Intended to be anonymous.
		 */
		add_action( 'wp', function() {
			if ( ! is_page_template( array( 'sip-profile.php', ) ) ) { return; }

			require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/update-user-password.class.php' );
			$update_user_password = new Starg_Update_User_Password;
			add_filter( 'starg/update_user_password', function() use ( $update_user_password ) {
				return $update_user_password;
			});
		} );

		/**
		 * Registers a class as service for creating the xml, zip-file and start the download.
		 * Intended to be anonymous.
		 */
		add_action( 'wp', function() {
			if ( ! current_user_can('edit_others_posts') ) { return; }
			if ( ! is_singular( array( 'archival', ) ) && ! is_page_template( array( 'sip-archive.php', 'sip-archival.php', 'sip-profile.php', ) ) ) { return; }

			require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/create-sip.class.php' );
			$create_sip = new Create_Sip;
			$create_sip->create_sip();
			add_filter( 'starg/create_sip', function() use ( $create_sip ) {
				return $create_sip;
			});
		});

		/**
		 * Registers a class as service for creating the xml, zip-file and start the download.
		 * Intended to be anonymous.
		 */
		add_action( 'wp', function() {
			if ( ! current_user_can('edit_others_posts') ) { return; }
			if ( ! is_singular( array( 'archival', ) ) && ! is_page_template( array( 'sip-archival.php', ) ) ) { return; }

			require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/create-sip-pdf.class.php' );
			$create_sip_pdf = new Create_Sip_Pdf;
			$create_sip_pdf->create_sip_pdf();
			add_filter( 'starg/create_sip_pdf', function() use ( $create_sip_pdf ) {
				return $create_sip_pdf;
			});
		});
	}
}
