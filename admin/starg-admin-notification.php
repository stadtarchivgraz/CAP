<?php
if (! defined('WPINC')) { die; }

/**
 * Adds some notifications for the admin-area.
 *
 * @since: 3.0.0
 * @author: Hannes Z.
 */
class Admin_Notification {

	public static function init() {
		add_action( 'admin_notices', array( 'Admin_Notification', 'starg_check_dependencies' ) );
	}

	/**
	 * Display a warning or note in the WordPress Backend if some dependencies are not installed/active.
	 */
	public static function starg_check_dependencies() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		$notification_message = array();
		if ( ! extension_loaded( 'imagick' ) ) {
			$notification_message[] = esc_attr__( 'Warning: The PHP-Extension "imagick" is not loaded!', 'sip' );
		}
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			$notification_message[] = esc_attr__( 'Warning: The PHP-Setting "allow_url_fopen" is not activated! In order to create PDF-Files this setting must be activated.', 'sip' );
		}

		$ghostscript = shell_exec( 'which gs 2>&1' );
		if ( ! $ghostscript ) {
			$notification_message[] = esc_attr__( 'Warning: Ghostscript might not be installed or could not been found on the system! In order to create PDF-Files you need to install ghostscript.', 'sip' );
		}

		// todo: add option to disable weak notices. We don't want to nag people into using a specific plugin such as polylang.
		if ( ! is_plugin_active( 'polylang/polylang.php' ) ) {
			$notification_message[] = esc_attr__( 'Note: We recomend using the plugin polylang if you want to translate the site.', 'sip' );
		}

		if ( ! function_exists( 'socket_create' ) ) {
			$notification_message[] = esc_attr__( 'Note: In order to be able to communicate with ClamAV you need to activate the sockets-Extension in PHP.', 'sip' );
		}

		// todo: add notification about clamav.

		Admin_Notification::starg_display_admin_notice( $notification_message );
	}

	/**
	 * Creates the output for the admin notifications.
	 */
	public static function starg_display_admin_notice( array $notice_msg = array() ) : void {
		if ( ! $notice_msg ) { return; }
		?>
		<div class="notice notice-warning">
			<h2>SIP</h2><?php // todo: change Title! ?>
			<?php foreach( $notice_msg as $single_msg ) : ?>
				<p><?php echo esc_attr( $single_msg ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php
	}

}
