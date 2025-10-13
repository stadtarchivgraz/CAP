<?php
if (! defined('WPINC')) { die; }

class Starg_Email_Helper {
	/**
	 * Customizations for WordPress emails.
	 * Allows sending emails in HTML format and sets the general sender (both email address and name).
	 * We can also wrap the emails WordPress sends automatically into our html design.
	 */
	public static function init() {
		if ( ! carbon_get_theme_option( 'sip_notifications_enabled' ) ) { return; }

		add_filter('wp_mail_from',      array( 'Starg_Email_Helper', 'set_notification_email_address' ));
		add_filter('wp_mail_from_name', array( 'Starg_Email_Helper', 'set_notification_email_name' ));
		add_action('wp_mail_failed',    array( 'Starg_Email_Helper', 'log_failed_mail_errors' ));

		if ( ! carbon_get_theme_option( 'sip_notifications_as_html' ) ) { return; }

		// WordPress Notifications
		add_filter( 'wp_new_user_notification_email',        array( 'Starg_Email_Helper', 'apply_email_design' ) ); // User register email.
		add_filter( 'wp_new_user_notification_email_admin',  array( 'Starg_Email_Helper', 'apply_email_design' ) ); // Email to admin on user register.
		add_filter( 'retrieve_password_notification_email',  array( 'Starg_Email_Helper', 'apply_email_design' ) ); // Password reset email.
		add_filter( 'password_change_email',                 array( 'Starg_Email_Helper', 'apply_email_design' ) ); // User email after password change.
		add_filter( 'email_change_email',                    array( 'Starg_Email_Helper', 'apply_email_design' ) ); // Confirmation about the new email.
		add_filter( 'wp_password_change_notification_email', array( 'Starg_Email_Helper', 'apply_email_design' ) ); // Admin email after password change.
	}

	/**
	 * Set the content type for emails to html.
	 * This would set every single email sent through wp_mail() to content type HTML. One can call this function in the wp_mail_content_type filter if needed (but not recommended).
	 * To avoid problems with other plugins/theme we add the HTML as header during the wp_mail calls
	 * by adding something like "$email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' )" to the $email array.
	 */
	public static function set_email_content_to_html( $content_type ): string {
		if ( ! carbon_get_theme_option( 'sip_notifications_as_html' ) ) { return $content_type; }

		return 'text/html';
	}

	/**
	 * Changes the email address of sent emails.
	 * @param string $email The email address WordPress usually uses (like: 'wordpress@domain.tld')
	 * @return string
	 */
	public static function set_notification_email_address( string $email ): string {
		$domain        = parse_url( $email, PHP_URL_HOST );
		$email_address = trim( carbon_get_theme_option( 'sip_notification_email_address' ) );
		if ( $email_address && is_email( $email_address ) ) {
			return sanitize_email( $email_address );
		} elseif ( $email_address && is_email( $email_address . '@' . $domain ) ) {
			return sanitize_email( $email_address . '@' . $domain );
		}

		return $email;
	}

	/**
	 * Changes the name of sent emails from WordPress to the name of the Website.
	 */
	public static function set_notification_email_name( $name ) : string {
		if ( ! carbon_get_theme_option( 'sip_notification_email_name' ) ) {
			return $name;
		}

		$email_name = trim( carbon_get_theme_option( 'sip_notification_email_name' ) );
		return sanitize_text_field( $email_name );
	}

	/**
	 * Create an entry in the logs if mailing fails.
	 * @param WP_Error $wp_error
	 * @todo: change the content of the log entry. We might just need: $wp_error['errors']['wp_mail_failed'][0]
	 */
	public static function log_failed_mail_errors( WP_Error $wp_error ): void {
		$logging = apply_filters( 'starg/logging', null );
		if ( $logging instanceof Starg_Logging ) {
			$error_content = esc_html__( 'Error trying to send an email.', 'sip' );
			if ( isset( $wp_error->errors['wp_mail_failed'][0] ) ) {
				$error_content .= ': ' . print_r( $wp_error->errors['wp_mail_failed'], true );
			}
			$logging->create_log_entry( $error_content );
		}
	}

	/**
	 * Create a new email array to wrap the message up in a HTML.
	 * @param array $email The original email data from WordPress.
	 * @return array{to:string,subject:string,message:string,headers:string[]}
	 * 
	 * @todo maybe add 'Reply-To: ' . Starg_Email_Helper::get_notification_reply_address() or 'X-Mailer: WordPress/PHPMailer'
	 */
	public static function apply_email_design( array $email ): array {
		$new_email = array(
			'to'      => sanitize_email( $email[ 'to' ] ),
			'subject' => esc_attr( $email[ 'subject' ] ),
			'message' => Starg_Email_Helper::email_notification_wrapper( $email['message'] ),
			'headers' => array( 'Content-Type: text/html; charset=UTF-8', ),
		);

		return $new_email;
	}

	/**
	 * Create the main output for the notification emails.
	 * @param string $message The main content of the email.
	 * @param string $title The title of the mail.
	 * @return string The html of the email.
	 */
	public static function email_notification_wrapper( string $message, string $title = '' ): string {
		if ( ! $title ) {
			// translators: %s: Name of the website.
			$title = sprintf( esc_attr__( '[%s] Notification', 'sip' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
		}
		$sanitized_reply_to = sanitize_email( Starg_Email_Helper::get_notification_reply_address() );

		$custom_logo_id = get_theme_mod('custom_logo');
		if ( $custom_logo_id ) {
			$logo_path   = get_attached_file($custom_logo_id);
			$logo_data   = file_get_contents($logo_path);
			$logo_base64 = base64_encode($logo_data);
			$logo_type   = mime_content_type($logo_path);
		}
		ob_start();
		?>
		<html>
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title><?php echo esc_attr( $title ); ?></title>
				<meta name="robots" content="noindex, nofollow">
				<meta name="x-apple-disable-message-reformatting">
				<meta http-equiv="X-UA-Compatible" content="IE=edge">

				<style>
					html{background-color: #ececec;font-family:sans-serif;}
					body{margin:2rem auto;max-width: 40%;}
					header{margin:1rem auto 2rem;text-align:center;}
					header img{max-width: 300px;height: auto;}
					main{padding:2rem;background-color: #fff;color:#222;border-radius:.25rem;box-shadow:1px 1px 5px 3px #cfcfcf;}
					main h1{margin:0 0 1.5rem;padding:0;font-size:1.5rem;}
					main p {margin:0;padding:0 0 1rem;font-size:1rem;}
					footer{margin-top:2rem;color:#000;text-align: center;font-size:.85rem;}
					footer p{margin:0;padding:0 0 .5rem;}
					@media (max-width: 768px) {body {margin-left: 1.5rem;margin-right: 1.5rem;max-width: 100%;}}
				</style>
			</head>
			<body>
				<header>
					<a href="<?php echo sanitize_url( get_home_url() ); ?>">
						<?php if ( $custom_logo_id ) : ?>
							<img src="data:<?php echo $logo_type; ?>;base64,<?php echo $logo_base64; ?>" alt="<?php esc_attr_e( 'Logo', 'sip' ); ?>">
						<?php else : ?>
							<h1><?php echo esc_html( get_bloginfo() ); ?></h1>
						<?php endif; ?>
					</a>
				</header>
				<main>
					<?php echo wpautop( make_clickable( wp_kses_post( $message ) ) ); ?>
				</main>
				<footer>
					<?php
					echo '<p>' . esc_html__( 'This is an automated notification email. Please do not reply directly.', 'sip' ) . '</p>';

					if ( $sanitized_reply_to ) {
						$reply_to_link = '<a href="mailto:' . $sanitized_reply_to . '">' . $sanitized_reply_to . '</a>';
						// translators: %s: Email address of support as hyperlink.
						echo '<p>' . sprintf( esc_html__( 'If you need assistance, contact us at %s', 'sip' ), $reply_to_link ) . '</p>';
					}
					?>
				</footer>
			</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Checks the option if our notification emails should add an email address for support purposes.
	 * @return string
	 */
	public static function get_notification_reply_address(): string {
		$reply_email_address = carbon_get_theme_option( 'sip_notification_reply_email_address' );
		if ( $reply_email_address && is_email( $reply_email_address ) ) {
			return sanitize_email( $reply_email_address );
		}

		return '';
	}

}
