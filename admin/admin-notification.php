<?php
if (! defined('WPINC')) { die; }

use Appwrite\ClamAV\Network;

/**
 * Adds some notifications for the admin-area.
 *
 * @since: 3.0.0
 * @author: Hannes Z.
 */
class Starg_Admin_Notification {

	public static function init() {
		add_action( 'admin_notices', array( 'Starg_Admin_Notification', 'starg_check_dependencies' ) );
	}

	/**
	 * Display a warning or note in the WordPress Backend if some dependencies are not installed/active.
	 */
	public static function starg_check_dependencies() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		$error_sign   = '<strong>' . esc_attr__( 'Error:', 'sip' ) . '</strong>';
		$warning_sign = '<strong>' . esc_attr__( 'Warning:', 'sip' ) . '</strong>';
		$note_sign    = '<strong>' . esc_attr__( 'Note:', 'sip' ) . '</strong>';

		$notification_message = array();
		if ( ! extension_loaded( 'imagick' ) ) {
			// translators: %s: Notification level like "Error", "Warning", "Note".
			$notification_message[] = sprintf( esc_attr__( '%s The PHP extension "imagick" is not loaded!', 'sip' ), $warning_sign );
		}
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			// translators: %s: Notification level like "Error", "Warning", "Note".
			$notification_message[] = sprintf( esc_attr__( '%s The PHP setting "allow_url_fopen" is not enabled! This setting must be enabled to create PDF files.', 'sip' ), $warning_sign );
		}

		$ghostscript = shell_exec( 'which gs 2>&1' );
		if ( ! $ghostscript ) {
			// translators: %s: Notification level like "Error", "Warning", "Note".
			$notification_message[] = sprintf( esc_attr__( '%s Ghostscript might not be installed or could not be found on the system! You need to install Ghostscript to create PDF files.', 'sip' ), $warning_sign );
		}

		// todo: add option to disable weak notices. We don't want to nag people into using a specific plugin such as polylang.
		if ( ! is_plugin_active( 'polylang/polylang.php' ) ) {
			$link_to_polylang = '<a href="https://wordpress.org/plugins/polylang/">Polylang</a>';
			// translators: %1$s: Notification level like "Error", "Warning", "Note". %2$s: Link to the Plugin in the WordPress repository.
			$notification_message[] = sprintf( esc_attr__( '%1$s We recommend using the plugin %2$s if you want to translate the site.', 'sip' ), $note_sign, $link_to_polylang );
		}

		if ( carbon_get_theme_option( 'sip_clamav' ) ) {
			if ( ! function_exists( 'socket_create' ) ) {
				// translators: %s: Notification level like "Error", "Warning", "Note".
				$notification_message[] = sprintf( esc_attr__( '%s To communicate with ClamAV, you need to enable the sockets extension in PHP.', 'sip' ), $note_sign );
			}

			$clamav = new Network( esc_attr( carbon_get_theme_option( 'sip_clamav_host' ) ), (int) esc_attr( carbon_get_theme_option( 'sip_clamav_port' ) ) );
			$clamav_rdy = false;
			try {
				$clamav_rdy = $clamav->ping();
			} catch( Exception $e ) {
				error_log( $e->getMessage() );
			}
			if ( ! $clamav_rdy ) {
				$plugin_settings_url    = starg_get_plugin_options_admin_url();
				$clamav_settings_link   = '<a href="' . $plugin_settings_url . '">' . esc_attr__( 'Check your configuration.', 'sip' ) . '</a>';
				// translators: %1$s: Notification level like "Error", "Warning", "Note". %2$s: Link to the settings page for clamAV Host/Port.
				$notification_message[] = sprintf( esc_attr__( '%1$s There is an error in your configuration for ClamAV. We can not connect to ClamAV server. %2$s.', 'sip' ), $error_sign, $clamav_settings_link );
			}
		}
		
		Starg_Admin_Notification::starg_display_admin_notice( $notification_message );
	}

	/**
	 * Creates the output for the admin notifications.
	 */
	public static function starg_display_admin_notice( array $notice_msg = array() ) : void {
		if ( ! $notice_msg ) { return; }
		?>
		<div class="notice notice-warning">
			<h2><?php echo STARG_SIP_PLUGIN_NAME; ?></h2>
			<?php foreach( $notice_msg as $single_msg ) : ?>
				<p><?php echo wp_kses_post( $single_msg ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php
	}

}
