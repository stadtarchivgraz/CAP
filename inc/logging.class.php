<?php
if (! defined('ABSPATH')) { die; }

/**
 * This class provides a simple way to log activities of this plugin on the website.
 * It creates a log file in wp-content/starg_logs/ containing information about each activity.
 * The log includes the time (as timestamp) the activity was recorded, the user ID, the page where the activity occurred, and the user's browser.
 *
 * @since: 3.1.0
 * @author: Hannes Z.
 */
class Starg_Logging {

	private string $debug_log_destination;
	private string $debug_log_filename    = 'starg_debug.log';
	public bool    $error_logging_enabled = false;

	function __construct() {
		// todo: add Plugin-Option to turn logging off/on.
		// todo: add Plugin-Option to change destination and name of the log file.

		$this->debug_log_destination = ( defined( WP_CONTENT_DIR ) ) ? WP_CONTENT_DIR . '/starg_logs/' : ABSPATH . 'wp-content/starg_logs/';

		if ( ! file_exists( $this->debug_log_destination ) ) {
			mkdir( $this->debug_log_destination, 0700, true );
			$this->protect_log_files();
		}
		if ( ! is_writeable( $this->debug_log_destination ) ) {
			// translators: %s: Path to the folder for the plugins log files.
			error_log( STARG_SIP_PLUGIN_NAME . ': ' . sprintf( esc_attr__( 'Error log was not created: no write permission for log folder %s', 'sip' ), $this->debug_log_destination ) );
			$this->error_logging_enabled = false;
			return;
		}

		$this->error_logging_enabled = true;
		$this->create_log_file();
	}

	/**
	 * Protect the log files from direct access. For Apache Servers only!
	 * @todo: create protecting for other server software as well (nginx)
	 */
	private function protect_log_files() {
		if ( ! self::is_apache() ) {
			// todo: add notification about the nginx configs located at /etc/nginx/sites-available/ or /etc/nginx/nginx.conf
			// one should add a rule like >> location ~* $this->debug_log_destination/.log$ { deny all; return 403; } << and restart nginx.
			return;
		}

		if ( file_exists( $this->debug_log_destination . '.htaccess' ) ) { return; }

		$htaccess_content = '<Files "' . $this->debug_log_filename . '">' . PHP_EOL . 'Order allow,deny' . PHP_EOL . 'Deny from all' . PHP_EOL . '</Files>' . PHP_EOL;
		$htaccess_created = file_put_contents( $this->debug_log_destination . '.htaccess', $htaccess_content, FILE_APPEND | LOCK_EX );
		if ( ! $htaccess_created ) {
			error_log( STARG_SIP_PLUGIN_NAME . ': ' . esc_attr__( 'htaccess for log folder was not created.', 'sip' ) );
		}
	}

	/**
	 * Initialize the log file.
	 * It also includes a heading in the first line of the file to clarify what each column means.
	 */
	private function create_log_file() {
		if ( self::is_apache() && ! file_exists( $this->debug_log_destination . '.htaccess' ) ) {
			$this->protect_log_files();
		}

		if ( file_exists( $this->debug_log_destination . $this->debug_log_filename ) ) { return; }

		$log_created = file_put_contents( $this->debug_log_destination . $this->debug_log_filename, 'Timestamp|User ID|Request URI|Browser|Message' . PHP_EOL, FILE_APPEND | LOCK_EX );
		if ( ! $log_created ) {
			error_log( STARG_SIP_PLUGIN_NAME . ': ' . esc_attr__( 'Error log was not created.', 'sip' ) );
		}
	}

	/**
	 * Write the entry into the log file.
	 * @param string $log_msg
	 * @return bool
	 */
	public function create_log_entry( string $log_msg ) : bool {
		if ( ! $this-> error_logging_enabled || ! $log_msg ) { return false; }

		$timestamp = time();
		$user_id   = get_current_user_id();
		$page      = sanitize_url( $_SERVER[ 'REQUEST_URI' ] );
		$browser   = self::get_user_browser();

		$debug_log_message = $timestamp . '|' . $user_id . '|' . $page . '|' . $browser . '|' . esc_attr( $log_msg ) . PHP_EOL;

		return error_log( $debug_log_message, 3, $this->debug_log_destination . $this->debug_log_filename );
	}

	/**
	 * Parses the provided user agent to identify the user's browser.
	 * @return string
	 */
	private static function get_user_browser() : string {
		$agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );

		if (strpos($agent, 'Firefox') !== false) {
			return 'Firefox';
		} elseif (strpos($agent, 'Chrome') !== false && strpos($agent, 'Chromium') === false && strpos($agent, 'Edg') === false) {
			return 'Chrome';
		} elseif (strpos($agent, 'Edg') !== false) {
			return 'Edge';
		} elseif (strpos($agent, 'Safari') !== false && strpos($agent, 'Chrome') === false) {
			return 'Safari';
		} elseif (strpos($agent, 'Opera') !== false || strpos($agent, 'OPR') !== false) {
			return 'Opera';
		} elseif (strpos($agent, 'MSIE') !== false || strpos($agent, 'Trident') !== false) {
			return 'Internet Explorer';
		}

		return esc_attr__( 'Unknown browser', 'sip' );
	}

	private static function is_apache() {
		return ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false );
	}
}
