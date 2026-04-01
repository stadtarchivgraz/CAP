<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Starg_Update_User_Profile extends Form_Validation {
	public string $nonce_action   = 'starg_update_user_data_nonce_action';
	public string $nonce_key      = 'starg_update_user_data_nonce';
	public string $form_name      = 'starg_update_user_data_form';
	public array $user_input      = array();
	private array $missing_inputs = array();

	/**
	 * Processes the operations needed to update a users profile.
	 * The form gets validated and the users input gets sanitized.
	 * The user profile and additional user metadata gets updated.
	 * A status message will be displayed weather the action was successful or not.
	 *
	 * @return bool
	 */
	public function maybe_process_update_user_profile() : bool {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		$this->user_input = $this->user_input_sanitization();
		if ( ! $this->user_input ) { return false; }

		$this->missing_inputs = $this->user_input_required( $this->user_input );
		if ( ! empty( $this->missing_inputs ) ) {
			$this->set_notification_for_missing_inputs( $this->missing_inputs );
			$this->display_notification();
			return false;// todo: maybe change to $user_input to be able to fill in the validated data for the user.
		}

		$user_data[ 'ID' ]           = $this->user_input[ 'ID' ];
		$user_data[ 'display_name' ] = $this->user_input[ 'first_name' ] . ' ' . $this->user_input[ 'last_name' ];
		$user_data[ 'first_name' ]   = $this->user_input[ 'first_name' ];
		$user_data[ 'last_name' ]    = $this->user_input[ 'last_name' ];
		$user_data[ 'user_email' ]   = $this->user_input[ 'user_email' ];

		$updated_user = wp_update_user( $user_data );
		if ( is_wp_error( $updated_user ) ) {
			$this->set_error_message( esc_html__( 'Your personal information could not be saved. Please try again.', 'sip' ) );
			$this->set_error_log_message( $updated_user->get_error_message() );
			$this->display_notification();
			return false;
		}

		// if the website has only one institution registered, we set it by default.
		$single_archive_id = DB_Query_Helper::maybe_get_single_archive_id();
		if ( $single_archive_id ) {
			update_user_meta( (int) $this->user_input['ID'], 'user_archive', (int) $single_archive_id );
			update_user_meta( (int) $this->user_input['ID'], 'user_archive_profile', 1 );
		} elseif ( $this->user_input[ 'user_archive' ] ) {
			update_user_meta( (int) $this->user_input['ID'], 'user_archive', (int) $this->user_input['user_archive'] );
			update_user_meta( (int) $this->user_input['ID'], 'user_archive_profile', 1 );
		}

		update_user_meta( (int) $this->user_input['ID'], 'user_address', $this->user_input['user_address'] );
		update_user_meta( (int) $this->user_input['ID'], 'user_birthday', $this->user_input['user_birthday'] );
		update_user_meta( (int) $this->user_input['ID'], 'user_privacy_policy_approval', $this->user_input['user_privacy_policy_approval'] );

		$this->set_success_message( esc_html__( 'Your personal information has been successfully updated.', 'sip' ) );
		$this->display_notification();

		// todo: remove potential ID-Anchor for missing input fields.
		// wp_safe_redirect( $_SERVER[ 'REQUEST_URI' ] );
		// exit;
		return true;
	}

	/**
	 * Retrieve the value for the form input field.
	 * The value differs based on the state of the archival submission status.
	 * @param string $field The form field for which we want to retrieve the data.
	 * @param mixed $default [Optional] A default value for the input field if we don't want to use an empty string. Default: empty string.
	 * @return mixed May be a string or an array.
	 */
	public function get_form_value( string $field, $default = '' ) {
		if ( ! $field || ! is_user_logged_in() ) { return ''; }
		$field = sanitize_key( $field );

		// if the form was submitted and required form fields were not filled, we pass the previously filled user_input if possible.
		if ( $this->missing_inputs ) {
			return (isset( $this->user_input[ $field ] )) ? $this->user_input[ $field ] : '';
		}

		$user = wp_get_current_user();
		switch ( $field ) {
			case ( 'ID' ) :
				return (int) $user->ID;
			case ( 'display_name' ) :
				return esc_html( $user->display_name );
			case ( 'first_name' ) :
				return esc_html( $user->first_name );
			case ( 'last_name' ) :
				return esc_html( $user->last_name );
			case ( 'user_email' ) :
				return sanitize_email( $user->user_email );
		}

		$user_meta = get_user_meta( $user->ID, $field, true ); // can be string or array!
		return $user_meta ?? esc_html( $default );
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'ID'                             => 'sanitize_text_field',// todo: maybe change to user_id?
			'display_name'                   => 'sanitize_text_field',
			'user_archive'                   => 'sanitize_text_field',
			'first_name'                     => 'sanitize_text_field',
			'last_name'                      => 'sanitize_text_field',
			'user_email'                     => 'sanitize_email',
			'user_birthday'                  => 'sanitize_text_field',
			'user_address'                   => array(
				'street_number' => 'sanitize_text_field',
				'zip'           => 'sanitize_text_field',
				'city'          => 'sanitize_text_field',
			),
			'user_privacy_policy_approval'   => 'true_false',
		);
	}

	/**
	 * Describes which inputs of the form are required.
	 * @return array
	 */
	protected function get_required_input_names() : array {
		return array(
			// 'user_archive' => true,// todo: user_archive becomes readonly! which means the browser doesn't send it to the server!
			'ID'                           => true,
			'first_name'                   => true,
			'last_name'                    => true,
			'user_email'                   => true,
			'user_birthday'                => true,
			'user_privacy_policy_approval' => true,
			'user_address'                 => array(
				'street_number' => true,
				'zip'           => true,
				'city'          => true,
			),
		);
	}
}
