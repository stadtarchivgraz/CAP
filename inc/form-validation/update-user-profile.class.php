<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Starg_Update_User_Profile extends Form_Validation {
	public string $nonce_action = 'starg_update_user_data_nonce_action';
	public string $nonce_key    = 'starg_update_user_data_nonce';
	public string $form_name    = 'starg_update_user_data_form';

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

		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) { return false; }

		$user_data[ 'ID' ]           = $user_input[ 'ID' ];
		$user_data[ 'display_name' ] = $user_input[ 'first_name' ] . ' ' . $user_input[ 'last_name' ];
		$user_data[ 'first_name' ]   = $user_input[ 'first_name' ];
		$user_data[ 'last_name' ]    = $user_input[ 'last_name' ];
		$user_data[ 'user_email' ]   = $user_input[ 'user_email' ];

		$updated_user = wp_update_user( $user_data );
		if ( is_wp_error( $updated_user ) ) {
			$this->set_error_message( esc_html__( 'Your personal information could not be saved. Please try again.', 'sip' ) );
			$this->set_error_log_message( $updated_user->get_error_message() );
			$this->display_notification();
			return false;
		}

		// todo: implement user_meta error handling!
		if ( $user_input[ 'user_archive' ] ) {
			update_user_meta( (int) $user_input['ID'], 'user_archive', $user_input['user_archive'] );
			update_user_meta( (int) $user_input['ID'], 'user_archive_profile', 1 );
		}

		update_user_meta( (int) $user_input['ID'], 'user_address', $user_input['user_address'] );
		update_user_meta( (int) $user_input['ID'], 'user_birthday', $user_input['user_birthday'] );
		update_user_meta( (int) $user_input['ID'], 'user_privacy_policy_approval', $user_input['user_privacy_policy_approval'] );

		$this->set_success_message( esc_html__( 'Your personal information has been successfully updated.', 'sip' ) );
		$this->display_notification();

		return true;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
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
			'ID'                             => 'sanitize_text_field',// todo: maybe change to user_id?
			// 'user_save'                    => '', // not needed! this is a submit-input.
		);
	}

}
