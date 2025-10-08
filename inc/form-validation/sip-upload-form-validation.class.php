<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Sip_Upload_Form_Validation extends Form_Validation {
	public string $nonce_action   = 'starg_upload_archival_nonce_action';
	public string $nonce_key      = 'starg_upload_archival_nonce';
	public string $form_name      = 'upload_sip_form';
	private array $user_input     = array();
	private array $missing_inputs = array();
	private string $sip_folder_id;
	private int $archival_id;

	public function __construct() {
		// if viewing an existing archival record/sip.
		$this->sip_folder_id = ( isset( $_GET['sipFolder'] ) ) ? sanitize_text_field( $_GET['sipFolder'] ) : '';
		$this->archival_id   = DB_Query_Helper::starg_get_archival_id_by_sip_folder( $this->sip_folder_id ) ?? 0;
	}

	/**
	 * After the user uploaded their files, they are redirected to a form where they can add additional data for the files.
	 * This function validates and sanitizes these user inputs.
	 * The post for this archival record gets created or updated if it already exists.
	 * Besides that some additional metadata is set.
	 * @return false|void false on error. On success, the user gets redirected to a page where they can preview their entry.
	 */
	public function process_upload_form() {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		$this->user_input = $this->user_input_sanitization();
		if ( ! $this->user_input ) { return false; }

		$this->missing_inputs = $this->user_input_required( $this->user_input );
		if ( ! empty( $this->missing_inputs ) ) {
			$this->set_notification_for_missing_inputs( $this->missing_inputs );
			return false;
		}

		$current_locale  = strtolower(get_locale());
		$current_user_id = get_current_user_id();
		$orig_author_id  = $current_user_id;
		$user_archive    = (int) get_user_meta( $current_user_id, 'user_archive', true );

		// main data for the new post.
		$post_data = array(
			'post_author'  => $current_user_id,
			'post_title'   => $this->user_input[ 'archival_title' ],
			'post_content' => $this->user_input[ 'archival_description' ],
			'post_type'    => Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG,
			'post_status'  => ( 'save_draft' === $this->user_input['save_sip'] ) ? 'draft' : 'pending',
		);

		// if a post_id for an archival is set, we want to update the archival post.
		// todo: maybe check the validity of the post_id.
		if ( $this->user_input[ 'archival_ID' ] ) {
			$orig_author_id  = (int) get_post_field( 'post_author', (int) $this->user_input[ 'archival_ID' ] );
			$post_data['ID'] = (int) $this->user_input[ 'archival_ID' ];

			// if we're editing the archival record from a different user (admin/editor) we need to reset the post_author to its original value.
			if ( $orig_author_id !== $current_user_id ) {
				$user_archive = (int) get_user_meta( $orig_author_id, 'user_archive', true );
				$post_data['post_author'] = $orig_author_id;
				// to keep the original post_status we need to set it again. otherwise we would overwrite it as draft if an archiver edits the post.
				$post_data['post_status'] = get_post_field( 'post_status', (int) $this->user_input[ 'archival_ID' ] );
			}
		}

		// tell the user about their missing archive setting and provide a link to the profile where they can change their archive setting!
		if ( ! $user_archive ) {
			$profile_page_link = '<a href="' . starg_get_the_profile_page_template_url() . '">' . esc_attr__( 'profile page', 'sip' ) . '</a>';
			// translators: %s: a Link to the users profile page.
			$this->set_error_message( sprintf( esc_html__( 'We could not find an archive for your account. Please visit the %s, select an archive, and save your settings.', 'sip' ), $profile_page_link ) );
			// translators: %d: The User-ID.
			$this->set_error_log_message( sprintf( esc_html__( 'The user with the id %d has not selected an archive!', 'sip' ), $current_user_id ) );
			return false;
		}

		// set the custom taxonomies for the archival record:
		$archival_tags  = wp_list_pluck( json_decode( stripcslashes( $this->user_input['archival_tags'] ), true ), 'value' );
		$archival_tags  = array_map( 'sanitize_text_field', $archival_tags );
		$post_data[ 'tax_input' ] = array(
			Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG => $archival_tags,
			Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG      => $user_archive,
		);

		if ( $this->user_input[ 'archival_originator' ] ) {
			$post_data['meta_input']['_archival_originator'] = $this->user_input[ 'archival_originator' ];
		}
		if ($this->user_input[ 'archival_single_date' ]) {
			$post_data['meta_input']['_archival_from'] = $this->user_input[ 'archival_single_date' ];
		} else {
			if ($this->user_input[ 'archival_date_range' ]) {
				$post_data['meta_input']['_archival_from'] = $this->user_input[ 'archival_date_range' ][0] . '-01-01 00:00:00';
				$post_data['meta_input']['_archival_to']   = $this->user_input[ 'archival_date_range' ][1] . '-12-31 23:59:59';
			}
		}
		if ($this->user_input[ 'archival_address' ]) {
			$post_data['meta_input']['_archival_address'] = $this->user_input[ 'archival_address' ];
		}
		if ($this->user_input[ 'archival_lat' ]) {
			$post_data['meta_input']['_archival_lat'] = $this->user_input[ 'archival_lat' ];
		}
		if ($this->user_input[ 'archival_lng']) {
			$post_data['meta_input']['_archival_lng'] = $this->user_input[ 'archival_lng'];
		}
		if ($this->user_input[ 'archival_area' ]) {
			$post_data['meta_input']['_archival_area'] = $this->user_input[ 'archival_area' ];
		}
		if ($this->user_input[ 'archival_upload_purpose' ]) {
			$post_data['meta_input']['_archival_upload_purpose'] = $this->user_input[ 'archival_upload_purpose' ];
		}
		if ($this->user_input[ 'archival_blocking_time' ]) {
			$post_data['meta_input']['_archival_blocking_time'] = $this->user_input[ 'archival_blocking_time' ];
		}
		if ($sip_custom_meta = carbon_get_theme_option('sip_custom_meta')) {
			foreach ($sip_custom_meta as $custom_meta) {
				$meta_name = sanitize_title($custom_meta['sip_custom_meta_title_' . $current_locale]);
				if (isset($_POST['_archival_' . $meta_name])) {
					$post_data['meta_input']['_archival_' . $meta_name] = sanitize_text_field( $_POST['_archival_' . $meta_name] );
				}
			}
		}
		if ($this->user_input[ 'archival_right_transfer' ]) {
			$post_data['meta_input']['_archival_right_transfer'] = $this->user_input[ 'archival_right_transfer' ];
		}
		if ($this->user_input[ 'archival_numeration' ]) {
			$post_data['meta_input']['_archival_numeration'] = $this->user_input[ 'archival_numeration' ];
		}
		if ($this->user_input[ 'archival_annotation' ]) {
			$post_data['meta_input']['_archival_annotation'] = $this->user_input[ 'archival_annotation' ];
		}
		if ($sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta')) {
			foreach ($sip_custom_archival_user_meta as $custom_archival_user_meta) {
				$meta_name = sanitize_title($custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale]);
				if (isset($_POST['_archival_' . $meta_name])) {
					$post_data['meta_input']['_archival_' . $meta_name] = sanitize_text_field( $_POST['_archival_' . $meta_name] );
				}
			}
		}
		if ( $this->sip_folder_id ) {
			$post_data['meta_input']['_archival_sip_folder'] = $this->sip_folder_id;
		}

		// create the new post for the uploaded archival. if post was created, redirect to the archival page, else show error message.
		$post_id = wp_insert_post( $post_data );
		if ( ! $post_id || is_wp_error( $post_id ) ) {
			$this->set_error_message( esc_html__( 'We encountered a problem creating/updating your entry.', 'sip' ) );
			$this->set_error_log_message( __FUNCTION__ . ': ' . $post_id->get_error_message() );
			return false;
		}

		// todo: As we redirect the user to another page, this message will not be displayed. We might add a query arg to the redirect_url with this message.
		// translators: %d: Post-ID.
		$this->set_success_message(sprintf(esc_html__('Entry %s successfully created/updated.', 'sip'), get_the_title( $post_id ) ));

		$this->notify_user( $current_user_id, $orig_author_id, $user_archive );

		// used to create the permalink for the edit page for SIP archival records.
		$url = starg_get_the_archival_page_template_url( $post_id );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Create the content of the notification and trigger sending.
	 * @param int $current_user_id
	 * @param int $orig_author_id
	 * @param int $user_archive_id
	 * @return void
	 */
	private function notify_user( int $current_user_id, int $orig_author_id, int $user_archive_id ): void {
		if ( ! carbon_get_theme_option( 'sip_notifications_enabled' ) ) { return; }

		$user_archive      = '';
		$user_archive_term = get_term( $user_archive_id, Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG );
		if ( $user_archive_term ) {
			$user_archive = $user_archive_term->name;
		}
		if ( $orig_author_id !== $current_user_id ) {
			$author = get_user_by( 'ID', $orig_author_id );
		} else {
			$author = get_user_by( 'ID', $current_user_id );
		}
		$author_name  = $author->display_name;
		$author_email = $author->user_email;
		$editor_email = DB_Query_Helper::get_all_editor_email_addresses( $user_archive_id );
		if ( carbon_get_theme_option( 'sip_notification_additional_recipients' ) ) {
			$other_email_user_ids = array_map( 'esc_attr', carbon_get_theme_option( 'sip_notification_additional_recipients' ) );
			$other_email          = array();
			foreach( $other_email_user_ids as $single_user_id ) {
				$other_email[ $single_user_id ] = get_userdata( $single_user_id )->user_email;
			}
			$editor_email = array_merge( $editor_email, $other_email );
		}

		// linebreaks for better reading.
		// $break = ( carbon_get_theme_option( 'sip_notifications_as_html' ) ) ? '<br>' : PHP_EOL . PHP_EOL;

		// send a notification to the editors.
		// translators: %s: Title of the submission.
		// $message = sprintf( esc_html__( 'Title: %1$s', 'sip' ), $this->user_input[ 'archival_title' ] );
		// $message .= $break;
		// // translators: %s: Name of the user.
		// $message .= sprintf( esc_html__( 'Author: %s', 'sip' ), $author_name );
		// $message .= $break;
		// // translators: %s: Name of the institution.
		// $message .= sprintf( esc_html__( 'New files have been uploaded to the following archive: %s', 'sip' ), $user_archive );
		// $message .= $break;
		// $message .= esc_html__( 'Please log in to the archive for review and approval.', 'sip' );

		// translators: %1$s: Title of the submission. translators: %2$s: Name of the user. translators: %3$s: Name of the institution.
		$message_to_editors = sprintf( esc_html__( 'Title: %1$s

Author: %2$s

New files have been uploaded to the following archive: %3$s

Please log in to the archive for review and approval.', 'sip' ), $this->user_input[ 'archival_title' ], $author_name, $user_archive );
		// translators: %s: Name of the institution.
		$subject = sprintf( esc_attr__( 'New archival record submitted to %s.', 'sip' ), $user_archive );
		$this->send_email_notification( $message_to_editors, $subject, $editor_email );


		// send a notification to the user.
		// translators: %s: Name of the user.
		// $message = sprintf( esc_html_x( 'Dear %s,', 'Greeting for people in emails', 'sip' ), $author_name );
		// $message .= $break;
		// $message .= esc_html__( 'congratulations, your submission has been successfully received!', 'sip' );
		// $message .= $break;
		// $message .= esc_html__( 'You will be separately informed via email about the acceptance or rejection of your submission by the archive. The review of a submission takes approximately 10 business days.', 'sip' );
		// $message .= $break;
		// $message .= esc_html__( 'Thank you for your contribution.', 'sip' );

		// translators: %s: Name of the user.
		$message_to_author = sprintf( esc_html__( 'Dear %s,

congratulations, your submission has been successfully received!

You will be separately informed via email about the acceptance or rejection of your submission by the archive. The review of a submission takes approximately 10 business days.

Thank you for your contribution.', 'sip' ), $author_name );
		// translators: %s: Name of the institution.
		$subject = sprintf( esc_attr__( 'Your submission to the archive %s has been received.', 'sip' ), $user_archive );
		$this->send_email_notification( $message_to_author, $subject, $author_email );
	}

	/**
	 * Retrieves the archival ID of the current SIP.
	 */
	public function get_archival_id() {
		return (int) $this->archival_id;
	}

	/**
	 * Retrieves the SIP folder ID of the current SIP.
	 */
	public function get_sip_folder_id() {
		return esc_attr( $this->sip_folder_id );
	}

	/**
	 * Retrieve the value for the form input field.
	 * The value differs based on the state of the archival submission status.
	 * @param string $field The form field for which we want to retrieve the data.
	 * @param mixed $default [Optional] A default value for the input field if we don't want to use an empty string. Default: empty string.
	 * @return mixed May be a string or an array.
	 */
	public function get_form_value( string $field, $default = '' ) {
		if ( ! $field || ! isset( $this->get_valid_input_names()[ $field ]) ) { return ''; }
		$field = sanitize_key( $field );

		// if the form was submitted and required form fields were not filled, we pass the previously filled user_input if possible.
		if ( $this->missing_inputs ) {
			return (isset( $this->user_input[ $field ] )) ? $this->user_input[ $field ] : '';
		}

		// if we have an existing post, we want to use the values from the post.
		if ( $this->archival_id ) {
			$archival_post = get_post( $this->archival_id );
			if ( $archival_post ) {

				switch ( $field ) {
					case ( 'archival_title' ) :
						return esc_html( $archival_post->post_title );

					case ( 'archival_description' ) :
						return wp_kses_post( $archival_post->post_content );

					case ( 'archival_tags' ) :
						$archival_tags_list = get_the_terms($this->archival_id, Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG);
						if ( $archival_tags_list && ! is_wp_error( $archival_tags_list ) ) {
							return array_map('esc_attr', wp_list_pluck($archival_tags_list, 'name'));
						} else {
							return array();
						}

					case ( 'archival_single_date' ) :
						return get_post_meta( $this->archival_id, '_archival_from', true );

					case ( 'archival_date_range' ) :
						return array(
							get_post_meta( $this->archival_id, '_archival_from', true ),
							get_post_meta( $this->archival_id, '_archival_to', true ),
						);

				}

				$archival_meta = get_post_meta( $this->archival_id, '_' . $field, true ); // can be string or array!
				return $archival_meta ?? '';
			}
		}

		// if not specified otherwise we use an empty string.
		return ( $default ) ? esc_html( $default ) : '';
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'archival_ID'             => 'sanitize_key',
			'archival_title'          => 'sanitize_text_field',
			'archival_description'    => 'sanitize_textarea_field',
			'archival_originator'     => 'sanitize_text_field',
			'archival_address'        => 'sanitize_text_field',
			'archival_lat'            => 'sanitize_text_field',
			'archival_lng'            => 'sanitize_text_field',
			'archival_area'           => 'starg_sanitize_json',
			'archival_tags'           => 'trim',// todo: this is an array/json! We might change "trim" to "starg_sanitize_json"
			'archival_upload_purpose' => 'sanitize_text_field',
			'archival_blocking_time'  => 'sanitize_text_field',
			'archival_right_transfer' => 'sanitize_text_field',
			'archival_numeration'     => 'sanitize_text_field',
			'archival_annotation'     => 'sanitize_text_field',
			'archival_single_date'    => 'sanitize_text_field',
			'archival_date_range'     => 'starg_sanitize_array',
			'save_sip'                => 'sanitize_text_field',
		);
	}

	/**
	 * Describes which inputs of the form are required.
	 * @return array
	 */
	protected function get_required_input_names() : array {
		return array(
			'archival_title'          => true,
			'archival_description'    => true,
			'archival_originator'     => true,
			'archival_tags'           => true,
			'archival_upload_purpose' => true,
			'archival_blocking_time'  => true,
			'archival_right_transfer' => true,
			'save_sip'                => true,
		);
	}

}
