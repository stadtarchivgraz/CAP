<?php
defined( 'ABSPATH' ) || exit;

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
abstract class Backend_Form_Validation extends Form_Validation {
	protected string $request_method = 'post';
	protected string $backend_slug;
	protected array $user_input           = array();
	protected array $missing_inputs       = array();
	protected string $submit_button_class = '';

	/***********************************/
	/*** validation and sanitization ***/
	/***********************************/

	/**
	 * Perform main validation for the form in question.
	 * We do not accept any user-input if one of these checks fails!
	 * @return bool true on success, false on failure.
	 */
	protected function form_validation(): bool {
		if ( ! defined( 'WPINC' ) || ! is_admin() ) { return false; } // WordPress must be running and we must be in the backend to continue!
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_others_pages' ) ) { return false; } // A valid User (at least with editor capabilities) must be logged in to continue!
		if ( ! isset( $_REQUEST[ $this->form_name_key ] ) || $this->form_name !== $_REQUEST[ $this->form_name_key ] ) { return false; } // The data must be from the expected form to continue!
		if ( ! isset( $_REQUEST[ $this->nonce_key ] ) ) { return false; } // There must be a nonce-input to continue!
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST[ $this->nonce_key ] ), sanitize_key( $this->nonce_action ) ) ) { return false; } // The nonce must be valid to continue!
		if ( ! isset( $_REQUEST[ '_wp_http_referer' ] ) ) { return false; }
		if ( ! check_admin_referer( sanitize_key( $this->nonce_action ), sanitize_key( $this->nonce_key ) ) ); // this also verifies the nonce, so the call above is actually not needed...maybe delete.
	
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
		if ( empty( $valid_input_names ) || ! is_array( $valid_input_names ) ) {
			unset( $request_method );
			return array();
		}

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

		if ( $this->get_dynamic_input_names() ) {
			$sanitized_user_input = array_merge( $sanitized_user_input, $this->process_dynamic_inputs() );
		}

		// clear the request.
		unset( $request_method );

		return $sanitized_user_input;
	}

	private function process_dynamic_inputs(): array {
		$request_method = ( 'post' === $this->request_method ) ? $_POST : $_GET;
		$schemas = $this->get_dynamic_input_names();

		if ( empty( $schemas ) || ! is_array( $schemas ) ) { return array(); }

		$result = array();
		foreach ( $schemas as $input_name_key => $field_schema ) {
			if ( ! isset( $request_method[ $input_name_key ] ) || ! is_array( $request_method[ $input_name_key ] ) ) {
				continue;
			}

			foreach ( $request_method[ $input_name_key ] as $row_key => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$normalized_row = array();
				foreach ( $row as $field => $value ) {
					$sanitized_field_name = sanitize_key( $field );

					if ( ! isset( $field_schema[ $sanitized_field_name ] ) ) {
						// translators: %s: Name of an expected input filed name.
						$this->set_error_log_message( sprintf( esc_attr__( 'Invalid key for %s', 'sip' ), sanitize_key( $sanitized_field_name ) ), Log_Severity::Warning );
						continue;
					}

					$type = $field_schema[ $sanitized_field_name ];
					switch ( $type ) {
						case 'key':
							$normalized_row[ $sanitized_field_name ] = sanitize_key( $value );
							break;
						case 'int':
							$normalized_row[ $sanitized_field_name ] = (int) $value;
							break;
						case 'value':
						case 'txt':
						default:
							$normalized_row[ $sanitized_field_name ] = sanitize_text_field( $value );
							break;
					}
				}

				$index = is_numeric( $row_key ) ? (int) $row_key : sanitize_key( $row_key );

				$result[ $input_name_key ][ $index ] = $normalized_row;
			}
		}

		return $result;
	}

	public function get_request_method(): string {
		return $this->request_method;
	}
	public function get_user_input() {
		return $this->user_input;
	}

	/**
	 * Describes which structure we need for dynamic generated form inputs.
	 * @return array the array should be formed as [ 'input_name' => txt,value,int,key ]
	 */
	abstract protected function get_dynamic_input_names() : array;

	/**************/
	/*** inputs ***/
	/**************/

	public function the_form_name_field(): void {
		?>
		<input type="hidden" name="<?php echo esc_attr( $this->form_name_key ); ?>" value="<?php echo esc_attr( $this->form_name ); ?>" aria-hidden="true" />
	<?php
	}
	public function the_nonce_field(): void {
		wp_nonce_field( $this->nonce_action, $this->nonce_key );
	}
	public function the_submit_button( string $field_name = '', string $button_value = '' ): void {
		$value = ( $button_value ) ? esc_attr( $button_value ) : esc_attr__( 'Submit', 'sip' )
		?>
		<input type="submit" name="<?php echo esc_attr( $field_name ); ?>" id="submit" class="<?php echo esc_attr( $this->submit_button_class ); ?>" value="<?php echo $value; ?>">
	<?php
	}

	public function the_label( string $field_name, string $label, bool $is_required = false ): void {
		?>
		<label class="<?php echo ( $is_required ) ? 'is-required' : ''; ?>" for="<?php echo esc_attr( $field_name ); ?>"><?php echo esc_html( $label ); ?><?php echo ( $is_required ) ? '*' : ''; ?></label>
	<?php
	}

	public function the_help_text( string $text, string $field_name = '' ): void {
		?>
		<span <?php echo ( $field_name ) ? 'id="' . esc_attr( $field_name ) . '_desc"' : ''; ?> class=""><?php echo wp_kses_post( $text ); ?></span>
	<?php
	}

	public function the_text_field( string $field_name, string $placeholder = '', bool $is_required = false, bool $has_help_text = false, $default = '' ): void {
		?>
		<input id="<?php echo esc_attr( $field_name ); ?>" class=""
			type="text" name="<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"
			value="<?php echo esc_attr( $this->get_form_value( $field_name, $default ) ); ?>"
			<?php echo ( $is_required ) ? 'required' : ''; ?>
			<?php echo ( $has_help_text ) ? 'aria-describedby="' . esc_attr( $field_name ) . '_desc"' : ''; ?>>
	<?php
	}

	public function the_number_field( string $field_name, string $placeholder = '', bool $is_required = false, bool $has_help_text = false, $default = 0 ): void {
		?>
		<input id="<?php echo esc_attr( $field_name ); ?>" class=""
			type="number" name="<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>"
			value="<?php echo esc_attr( $this->get_form_value( $field_name, $default ) ); ?>"
			<?php echo ( $is_required ) ? 'required' : ''; ?>
			<?php echo ( $has_help_text ) ? 'aria-describedby="' . esc_attr( $field_name ) . '_desc"' : ''; ?>>
	<?php
	}

	public function the_select_field( string $field_name, array $options, bool $has_help_text = false ): void {
		$required = ( $this->get_required_input_names() && isset( $this->get_required_input_names()[ $field_name ] ) ) ? true : false;
		$selected = $this->get_form_value( $field_name );
		?>
		<select id="<?php echo esc_attr( $field_name ); ?>" class=""
			name="<?php echo esc_attr( $field_name ); ?>" <?php echo ( $has_help_text ) ? 'aria-describedby="' . esc_attr( $field_name ) . '_desc"' : ''; ?>>
			<option value="0" <?php selected( $selected, 0 ); ?>><?php esc_html_e( 'none', 'sip' ); ?></option>
			<?php foreach ( $options as $single_option => $label ) : ?>
				<option value="<?php echo esc_attr( $single_option ); ?>" <?php selected( $selected, esc_attr( $single_option ) ); ?>>
					<?php echo esc_attr( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php
	}

	public function the_checkbox_field( string $field_name, bool $is_required = false, bool $has_help_text = false ): void {
		?>
		<input id="<?php echo esc_attr( $field_name ); ?>" class=""
			type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="true"
			<?php checked( $this->get_form_value( esc_attr( $field_name ) ) ); ?> <?php echo ( $is_required ) ? 'required' : ''; ?>
			<?php echo ( $has_help_text ) ? 'aria-describedby="' . esc_attr( $field_name ) . '_desc"' : ''; ?>>
	<?php
	}

	/**
	 * Retrieve the value for the form input field.
	 * The value differs based on the state of the archival submission status.
	 * @param string $field The form field for which we want to retrieve the data.
	 * @param mixed $default [Optional] A default value for the input field if we don't want to use an empty string. Default: empty string.
	 *
	 * Overrides @see Form_Input_Helper::get_form_value()
	 *
	 * @return mixed May be a string or an array.
	 */
	public function get_form_value( string $field, $default = '' ) {
		if ( ! $field || ! isset( $this->get_valid_input_names()[ $field ] ) ) { return ''; }
		$field = sanitize_key( $field );

		// if the form was submitted and required form fields were not filled, we pass the previously filled user_input if possible.
		if ( $this->missing_inputs ) {
			return (isset( $this->user_input[ $field ] )) ? $this->user_input[ $field ] : '';
		}

		// if not specified otherwise we use an empty string.
		return ( $default ) ? esc_attr( $default ) : '';
	}

	/**
	 * Displays either an error or success message about an user interaction.
	 * @return void
	 */
	public function display_notification() : void {
		if ( ! $this->success_msg && ! $this->error_msg ) { return; }

		$notification_msg   = $this->success_msg;
		$notification_style = 'notice-success';
		if ( $this->error_msg ) {
			$notification_msg   = $this->error_msg;
			$notification_style = 'notice-error';
		}
		?>
		
		<div class="notice <?php echo $notification_style; ?>">
			<p><?php echo wp_kses_post( $notification_msg ); ?></p>
		</div>
	<?php
	}

}
