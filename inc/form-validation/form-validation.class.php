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
	public string $form_name_key  = 'starg_form_name';
	public string $success_msg    = '';
	public string $error_msg      = '';
	public string $error_log_msg  = '';
	public string $url_endpoint   = '';

	/**
	 * Perform main validation for the form in question.
	 * We do not accept any user-input if one of these checks fails!
	 * @return bool true on success, false on failure.
	 */
	protected function form_validation() : bool {
		// todo: maybe add logging for invalid forms?
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
	 * Loops through every user_input we got and checks if they are required in our form.
	 * @param array $user_input
	 * @return array empty array if all required input fields have data, otherwise array with missing input field keys.
	 */
	protected function user_input_required( array $user_input = array() ) : array {
		if ( ! $user_input ) { return array(); }

		$all_valid_inputs = $this->get_valid_input_names();
		$required_inputs  = $this->get_required_input_names();
		$missing_inputs   = array();

		foreach ( $user_input as $input_key => $input_value ) {
			if ( ! isset( $all_valid_inputs[ $input_key ] ) ) { continue; } // not our input. no need to check if required.
			if ( ! isset( $required_inputs[ $input_key ] ) ) { continue; } // input is not required. continue with operation.

			if ( is_array( $input_value ) ) {
				foreach( $input_value as $other_input_key => $other_input_field ) {
					if ( ! trim( $other_input_field ) ) {
						$missing_inputs[ $input_key ] = $other_input_key;
					}
				}
				continue;
			}

			// required input has no value!
			if ( ! trim( $input_value ) ) {
				$missing_inputs[] = $input_key;
			}
		}

		return $missing_inputs;
	}

	/**
	 * Trigger the sending of the notification emails.
	 * @param string $message_content The main email content.
	 * @param string $subject The subject of the sent email.
	 * @param string[] $send_to One ore more recipients of the email.
	 * @return bool
	 */
	protected function send_email_notification( string $message_content, string $subject = '', $send_to = '' ) : bool {
		if ( ! carbon_get_theme_option( 'sip_notifications_enabled' ) ) { return false; }

		if ( ! $subject ) {
			// translators: %s: Title of the website.
			$subject = sprintf( esc_attr__( 'Notification from %s', 'sip' ), esc_attr( get_bloginfo() ) );
		}

		$logging = apply_filters( 'starg/logging', null );
		if ( ! $send_to ) {
			if ( $logging instanceof Starg_Logging ) {
				// translators: %s: The class which called the function.
				$logging->create_log_entry( sprintf( esc_html__( 'Email notification for %s not sent. Missing recipient!', 'sip' ), get_called_class() ), Log_Severity::Info );
			}
			// sending the mail to the admin, so we know there is something wrong here!
			$send_to = sanitize_email( get_bloginfo( 'admin_email' ) );
		}

		$headers = '';
		$message = sanitize_textarea_field( $message_content );
		if ( carbon_get_theme_option( 'sip_notifications_as_html' ) ) {
			// build the html for the email.
			$message = Starg_Email_Helper::email_notification_wrapper( $message_content, $subject );
			// set the content type of this email to HTML.
			$headers = array( 'Content-Type: text/html; charset=UTF-8', );
		}

		// send the mail.
		$mail_sent = wp_mail( $send_to, $subject, $message, $headers );

		if ( ! $mail_sent ) {
			if ( $logging instanceof Starg_Logging ) {
				if ( is_array( $send_to ) ) {
					$send_to = implode( ',', $send_to );
				}
				// translators: %1$s: The class which called the function. %2$s: Email address of the user to whom the email should have been sent.
				$logging->create_log_entry( sprintf( esc_html__( 'Email notification for %1$s to %2$s not sent.', 'sip' ), get_called_class(), $send_to ), Log_Severity::Error );
			}
		}
		return $mail_sent;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array the array should be formed as [ 'input_name' => 'sanitizing_function', ]
	 */
	abstract protected function get_valid_input_names() : array;

	/**
	 * Describes which inputs of the form are required.
	 * If a form has not delivered one of these inputs, we do not trigger any action but display an error message.
	 * For performance reasons we use the input names as keys for the array. This way we can use isset() instead of in_array().
	 * @return array
	 */
	abstract protected function get_required_input_names() : array;

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
		$logging = apply_filters( 'starg/logging', null );
		if ( ! $logging instanceof Starg_Logging || ! $logging->error_logging_enabled ) { return; }
	
		if ( $this->error_log_msg ) {
			$this->error_log_msg .= ' | ' . $error_log_msg;
			$logging->create_log_entry( $this->error_log_msg );
			return;
		}

		$this->error_log_msg = $error_log_msg;
		$logging->create_log_entry( $error_log_msg );
	}

	/**
	 * Create a notification about missing inputs in forms.
	 * @param array $missing_inputs
	 * @return void
	 */
	protected function set_notification_for_missing_inputs( array $missing_inputs ) : void {
		if ( ! $missing_inputs ) { return; }
		$missing_input_links = '';
		foreach( $missing_inputs as $single_input_field ) {
			$missing_input_links .= '<a href="#' . esc_attr( str_replace( '_', '-', $single_input_field ) ) . '">' . esc_html( $single_input_field ) . '</a> ';
		}
		// translators: %s: one or more hyperlinks to the missing required inputs.
		$this->set_error_message( sprintf( esc_html__( 'Missing inputs. Please check %s', 'sip' ), $missing_input_links ) );
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
