<?php
if (! defined('WPINC')) { die; }

/**
 * This class adds some additional security options for the WordPress site and the plugin itself.
 * The WordPress Version will be removed from the <head> of the site and xmlrpc will be deactivated.
 * The access to the backend (/wp-admin) is restricted and the admin-bar is only showing for admins/editors.
 * The access to the REST-API is restricted and we disable editing theme or plugin files in the backend.
 * The last 3 digits of the IP-Address for comments are random.
 * We also added some sanitizing for the carbon-fields plugin (which is pretty mind-blowing that they don't sanitize any user-input!).
 *   But be aware, we only sanitize some of the fields, that we use!
 *
 * @since: 3.0.0
 * @author: Hannes Z.
 */
Class Starg_Security_Settings {

	// When creating new folders on the server, we want to avoid making them writable by everyone.
	// 0755: Owner can read, write and execute. Group and others can read and execute.
	// Standard is 0777: Everyone can read, <strong>write</strong> and execute.
	const STARG_FOLDER_PERMISSIONS = 0755;

	public static function init() {
		remove_action('wp_head', array( 'Starg_Security_Settings', 'wp_generator') );
		add_filter( 'xmlrpc_methods', array( 'Starg_Security_Settings', 'starg_disable_xmlrpc' ) );

		add_action( 'init', array( 'Starg_Security_Settings', 'starg_maybe_disable_admin_bar' ) );
		add_action( 'admin_init', array( 'Starg_Security_Settings', 'restrict_access_to_wp_admin' ) );

		add_filter( 'rest_authentication_errors', array( 'Starg_Security_Settings', 'starg_maybe_disable_rest_api' ) );
		add_action( 'init', array( 'Starg_Security_Settings', 'starg_disable_theme_and_plugin_edit' ) );
		add_filter( 'pre_comment_user_ip', array( 'Starg_Security_Settings', 'starg_alter_comment_author_ip' ) );

		add_filter( 'carbon_fields_before_field_save', array( 'Starg_Security_Settings', 'starg_sanitize_user_input_for_carbon_fields' ) );
	}

	/**
	 * Deactivates the admin bar for specific users. Only admins and editors can see the admin bar.
	 */
	public static function starg_maybe_disable_admin_bar() {
		if ( current_user_can( 'edit_others_posts' ) ) { return; }

		add_filter( 'show_admin_bar', '__return_false' );
	}

	/**
	 * Deactivates xmlrpc pingback.
	 * @param string[] $methods
	 *
	 * @see wp_xmlrpc_server::__construct
	 */
	public static function starg_disable_xmlrpc( $methods ) {
		unset($methods['pingback.ping']);
		return $methods;
	}

	/**
	 * Restrict REST-API. only logged in user or predefined routes are allowed.
	 */
	public static function starg_maybe_disable_rest_api( $results ) {
		// If an error occurred (by an other plugin or so), return it.
		if ( ! empty( $results ) ) { return $results; }

		// open the rest api for logged in users.
		if ( is_user_logged_in() ) { return $results; }

		$request_uri = $_SERVER[ 'REQUEST_URI' ] ?? '';

		// whitelisted routes.
		$allowed_routes = array(
			'notification/v1/',
		);

		foreach ( $allowed_routes as $single_route ) {
			if ( strpos( $request_uri, $single_route ) !== false ) {
				return true;
			}
		}

		// every guest or other route gets blocked.
		return new WP_Error( 'REST_API_restricted', array( 'status' => rest_authorization_required_code(), ) );
	}

	/**
	 * Restrict the access to the WordPress-Backend.
	 * Only administrators, editors and AJAX-Calls can access the Backend (/wp-admin)
	 * @todo maybe create an option in the plugin so that other site owners can override this setting.
	 */
	public static function restrict_access_to_wp_admin() {
		if ( is_user_logged_in() && ! current_user_can( 'edit_pages' ) && ! wp_doing_ajax() ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Deactivates theme and plugin file edit.
	 */
	public static function starg_disable_theme_and_plugin_edit() {
		if (! defined('DISALLOW_FILE_EDIT') || false === defined('DISALLOW_FILE_EDIT')) {
			define('DISALLOW_FILE_EDIT', true);
		}
	}

	/**
	 * Alter authors IP-Address.
	 *
	 * @param string $comment_author_ip The IP-Address of the commenting user. can be IPv4 or IPv6.
	 *
	 * @return string
	 *
	 * @see wp_filter_comment()
	 */
	public static function starg_alter_comment_author_ip( string $comment_author_ip ) : string {
		$comment_author_ip_version = WP_Http::is_ip_address( $comment_author_ip );

		if (4 === $comment_author_ip_version) {
			$ip_parts = explode('.', $comment_author_ip);
			return $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2] . '.' . '***';
		} else {
			return '';
		}
	}

	/**
	 * Adds sanitizing for user-input in carbon-fields.
	 * As of Version 3.x carbon fields support following inputs: 'association, checkbox, color, complex, date, date_time, file, footer_scripts, gravity_form (needs extra Plugin), header_scripts, hidden, html, image, map, media_gallery, multiselect, oembed, radio, radio_image, rich_text, select, separator, set, sidebar, text, textarea, time,'
	 * @link https://docs.carbonfields.net/learn/fields/text.html
	 *
	 * @link https://github.com/htmlburger/carbon-fields/issues/504#issuecomment-2098070487
	 *
	 * @return \Carbon_Fields\Field\Field
	 */
	public static function starg_sanitize_user_input_for_carbon_fields( \Carbon_Fields\Field\Field $field ) {
		// todo: find a way to only target the fields from our plugin!
		$input_type = $field->get_type();
		$user_input = $field->get_value();

		if ( empty( $user_input ) ) {
			return $field;
		}

		$sanitized_value = $user_input;
		switch( $input_type ) {
			case 'text':
			case 'select':
				$sanitized_value = sanitize_text_field( $user_input );
				break;
			case 'textarea':
				$sanitized_value = sanitize_textarea_field( $user_input );
				break;
			case 'rich_text': // aka wysiwyg-editor.
			case 'html':
				$sanitized_value = wp_kses_post( $user_input );
				break;
			case 'multiselect':
				$sanitized_value = starg_sanitize_array( $user_input );
				break;

			// subject to change! At the moment we only allow <style> tags. A <script> tag will be removed!
			case 'header_scripts':
			case 'footer_scripts':
				$sanitized_value = wp_kses( $user_input, array( 'style' => array(), ) );
				break;
		}

		$field->set_value( $sanitized_value );

		return $field;
	}

}


/**
 * Sanitizes the keys and values of an array.
 * @param array $arr the array to sanitize
 * @return array
 */
function starg_sanitize_array(array $arr): array {
	if (! $arr || ! is_array($arr)) {
		return array();
	}

	$sanitized_arr = array();
	foreach ($arr as $key => $value) {
		if (empty($value)) {
			continue;
		}

		$sanitized_arr[sanitize_key($key)] = sanitize_text_field($value);
	}

	return $sanitized_arr;
}

/**
 * Sanitizes a json string.
 * Use with caution! Using this function may invalidate the JSON!
 */
function starg_sanitize_json(string $json_string = ''): string {
	if (! $json_string) {
		return '';
	}

	$data = json_decode(wp_unslash($json_string), true);
	if (! $data || ! is_array($data)) {
		return '';
	}

	$sanitized_data = starg_sanitize_json_array($data);
	return ($sanitized_data) ? json_encode($sanitized_data) : '';
}

/**
 * Sanitizes an array inside a json string.
 * Use with caution! Using this function may invalidate the JSON!
 */
function starg_sanitize_json_array($data = array()) {
	$sanitized_data = array();
	if (is_array($data)) {
		foreach ($data as $key => $value) {
			$sanitized_data[htmlspecialchars($key, ENT_QUOTES, 'UTF-8')] = starg_sanitize_json_array($value);
		}
	} else {
		$sanitized_data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	}

	return $sanitized_data;
}
