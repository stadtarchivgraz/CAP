<?php
if (! defined('WPINC')) { die; }

/**
 * Class for validation user input in forms.
 * It checks whether the data comes from one of our forms, whether the user is allowed to fill out the form at all,
 * whether all required inputs are valid, and sanitizes them.
 * Additionally, a simple error/success message will be created and can be used to inform the user about the operation.
 *
 * @since: 3.0.0
 * @author: Hannes Z.
 */
abstract class Form_Validation {
	protected string $request_method = 'post';
	public string $nonce_action;
	public string $nonce_key;
	public string $form_name;
	public string $form_name_key = 'starg_form_name';
	public string $success_msg   = '';
	public string $error_msg     = '';
	public string $error_log_msg = '';
	public string $url_endpoint  = '';

	/**
	 * Perform main validation for the form in question.
	 * We do not accept any user-input if one of these checks fails!
	 * @return bool true on success, false on failure.
	 */
	protected function form_validation() : bool {
		if ( ! defined( 'WPINC' ) ) { return false; } // WordPress must be running to continue!
		if ( ! is_user_logged_in() ) { return false; } // A valid User must be logged in to continue!

		// if we use the form in a loop, we use a suffix for the nonce to differentiate between the elements in the loop.
		$form_suffix = isset( $_REQUEST[ 'starg_form_suffix' ] ) ? '_' . sanitize_text_field( $_REQUEST[ 'starg_form_suffix' ] ) : '';
		if ( ! isset( $_REQUEST[ $this->form_name_key ] ) || $this->form_name . $form_suffix !== $_REQUEST[ $this->form_name_key ] ) { return false; } // The data must be from the expected form to continue!
		if ( ! isset( $_REQUEST[ $this->nonce_key . $form_suffix ] ) ) { return false; } // There must be a nonce-input to continue!
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST[ $this->nonce_key . $form_suffix ] ), $this->nonce_action ) ) { return false; } // The nonce must be valid to continue!
	
		return true; // all good, form is valid!
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	abstract protected function get_valid_input_names() : array;

	/**
	 * Sanitize user input and only return needed values from the $_REQUEST.
	 * @return array
	 */
	protected function user_input_sanitization() : array {
		$request_method = ( 'post' === $this->request_method ) ? $_POST : $_GET;
		$sanitized_user_input = array();
		$valid_input_names    = $this->get_valid_input_names(); // we're only processing inputs from our form!
		if ( empty( $valid_input_names ) || ! is_array( $valid_input_names ) ) { return $sanitized_user_input; }

		foreach( $valid_input_names as $input_name => $sanitizing_function ) {
			// the usual way to sanitize the users input.
			if ( ! is_array( $sanitizing_function ) ) {
				if ( ! function_exists( $sanitizing_function ) || ! is_callable( $sanitizing_function ) ) {
					$sanitizing_function = 'sanitize_text_field';
				}

				$sanitized_user_input[ $input_name ] = ( isset( $request_method[ $input_name ] ) && $request_method[ $input_name ] ) ? call_user_func( $sanitizing_function, $request_method[ $input_name ] ) : '';
				continue;
			}

			// if we got an array as input_name:
			foreach ( $sanitizing_function as $arr_input_name => $arr_sanitizing_function ) {
				if ( ! function_exists( $arr_sanitizing_function ) || ! is_callable( $arr_sanitizing_function ) ) {
					$arr_sanitizing_function = 'sanitize_text_field';
				}

				$sanitized_user_input[ $input_name ][ $arr_input_name ] = ( isset( $request_method[ $input_name ][ $arr_input_name ] ) && $request_method[ $input_name ][ $arr_input_name ] ) ? call_user_func( $arr_sanitizing_function, $request_method[ $input_name ][ $arr_input_name ] ) : '';
			}
		}

		// clear the request.
		unset( $request_method );

		return $sanitized_user_input;
	}

	/**
	 * Sets a message which can be used to inform the user about a successful operation on the website.
	 * @param string $success_msg
	 * @return void
	 */
	protected function set_success_message( string $success_msg = '' ) : void {
		if ( $this->success_msg ) {
			$this->success_msg .= '<br>' . $success_msg;
			return;
		}
		$this->success_msg = $success_msg;
	}
	/**
	 * Sets a message which can be used to inform the user about a failed operation on the website.
	 * @param string $error_msg
	 * @return void
	 */
	protected function set_error_message( string $error_msg = '' ) : void {
		if ( $this->error_msg ) {
			$this->error_msg .= '<br>' . $error_msg;
			return;
		}
		$this->error_msg = $error_msg;
	}
	/**
	 * Creates an entry in the error log which gives a deeper insight in a failed operation on the website.
	 * @param string $error_log_msg
	 * @return void
	 */
	protected function set_error_log_message( string $error_log_msg = '' ) : void {
		if ( $this->error_log_msg ) {
			$this->error_log_msg .= '<br>' . $error_log_msg;
			error_log( $this->error_log_msg );
			return;
		}
		$this->error_log_msg = $error_log_msg;
		error_log( $this->error_log_msg );
	}

	/**
	 * Displays either an error or success message about an user interaction.
	 * @return void
	 */
	public function display_notification() : void {
		if ( ! $this->success_msg && ! $this->error_msg ) { return; }

		$notification_msg   = $this->success_msg;
		$notification_style = 'is-success is-light';
		if ( $this->error_msg ) {
			$notification_msg   = $this->error_msg;
			$notification_style = 'is-danger is-light';
		}
		echo starg_get_notification_message( $notification_msg, $notification_style, true );
	}

}
