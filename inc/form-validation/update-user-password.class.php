<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Starg_Update_User_Password extends Form_Validation {
	public string $nonce_action = 'starg_update_user_password_nonce_action';
	public string $nonce_key    = 'starg_update_user_password_nonce';
	public string $form_name    = 'starg_update_user_password_form';

	/**
	 * Processes the operations needed to update a users password.
	 * The form gets validated and the users input gets sanitized...kinda. it's passwords, we can't pull them through rough sanitizing functions.
	 * A status message will be displayed weather the action was successful or not.
	 *
	 * @return bool|string true on success. false or additional information about the error on failure.
	 */
	public function maybe_process_update_user_password() {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) { return false; }

		$user = wp_get_current_user();
		if ( $user->ID !== (int) $user_input['ID'] ) {
			$this->set_error_message( esc_attr__( 'We encountered a problem updating your password. Please try again.', 'sip' ) );
			// translators: %1$s, %2$s: User ID.
			$this->set_error_log_message( sprintf( esc_attr( 'The user with the ID %1$s has tried to change the password for the user with the ID %2$s.', 'sip' ), $user->ID, $user_input['ID'] ) );
			$this->display_notification();
			return false;
		}

		$password_error = false;
		if ( ! isset($user_input['oldpassword']) || ! $user_input['oldpassword']) {
			$password_error = esc_html__('Enter your old password.', 'sip');
		} elseif (! wp_check_password($user_input['oldpassword'], $user->user_pass, $user->ID)) {
			$password_error = esc_html__('The old password is incorrect.', 'sip');
		} elseif (strlen($user_input['newpassword']) < 12) {
			$password_error = esc_html__('The new password is too short.', 'sip');
		} elseif ($user_input['newpassword'] !== $user_input['repeatpassword']) {
			$password_error = esc_html__('The repeated password does not match the new one.', 'sip');
		} else {
			$user_updated = wp_update_user(array(
				'ID'        => (int) $user_input['ID'],
				'user_pass' => $user_input['newpassword'],
			));

			if ( is_wp_error( $user_updated ) ) {
				$this->set_error_log_message( $user_updated->get_error_message() );
				return esc_html__( 'We encountered a problem updating your password. Please try again.', 'sip' );
			}
		}

		if ( $password_error ) {
			$this->set_error_message( esc_html__( 'The password could not be changed. See the error message in the form.', 'sip') );
			$this->display_notification();
			return $password_error;
		}

		$this->set_success_message( esc_html__( 'Your password was changed successfully. You may have to log in again.', 'sip' ) );
		$this->display_notification();
		return true;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'oldpassword'    => 'trim',// we do not sanitize passwords as we want the user to be able to use special characters like $%&.
			'newpassword'    => 'trim',// we do not sanitize passwords as we want the user to be able to use special characters like $%&.
			'repeatpassword' => 'trim',// we do not sanitize passwords as we want the user to be able to use special characters like $%&.
			'ID'             => 'sanitize_text_field',// todo: maybe change to user_id?
			'password_save'  => '', // not needed! this is a submit-input.
		);
	}

}
