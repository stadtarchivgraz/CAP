<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Sip_Upload_Form_Validation extends Form_Validation {
	public string $nonce_action = 'starg_upload_archival_nonce_action';
	public string $nonce_key    = 'starg_upload_archival_nonce';
	public string $form_name    = 'upload_sip_form';

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

		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) { return false; }

		// if viewing an existing archival/sip.
		$sip_folder = ( isset( $_GET['sipFolder'] ) ) ? sanitize_text_field( $_GET['sipFolder'] ) : '';

		$current_locale = strtolower(get_locale());
		$user_id        = get_current_user_id();
		$archives       = (int) get_user_meta( $user_id, 'user_archive', true );

		// main data for the new post.
		$post_data = array(
			'post_title'   => $user_input[ 'archival_title' ],
			'post_content' => $user_input[ 'archival_description' ],
			'post_type'    => 'archival',
		);

		// if a post_id for an archival is set, we want to update the archival post.
		// todo: maybe check the validity of the post_id.
		if ( $user_input[ 'archival_ID' ] ) {
			$post_data['ID']          = (int) $user_input[ 'archival_ID' ];
			$post_data['post_author'] = get_post_field('post_author', (int) $user_input[ 'archival_ID' ]);
			$post_data['post_status'] = get_post_field('post_status', (int) $user_input[ 'archival_ID' ]);

			// if we're editing the archival record we need to set the post-author.
			$post_author_id = (int) get_post_field( 'post_author', (int) $user_input[ 'archival_ID' ] );
			if ( $post_author_id !== $user_id ) {
				$archives = (int) get_user_meta( $post_author_id, 'user_archive', true );
			}

			// if we still have no archive selected, we may try to use the first one created. OR! We tell them and provide a link to the profile where they can change their archive setting!
			// if ( ! $archives ) {
			// 	$archive_terms = get_the_terms( (int) $user_input[ 'archival_ID' ], 'archive' );
			// 	if ( $archive_terms || ! is_wp_error( $archive_terms ) ) {
			// 		$archives = $archive_terms[0]->term_id;
			// 	}
			// }
		}

		// todo: maybe tell the user about their missing archive setting and provide a link to the profile where they can change their archive setting!
		// if ( ! $archives ) {
		// 	$profile_page_link = '<a href="' . starg_get_the_profile_page_template_url() . '">' . esc_attr__( 'profile page', 'sip' ) . '</a>';
		// 	// translators: %s: a Link to the users profile page.
		// 	$this->set_error_message( sprintf( esc_html__( 'You have not selected an archive. Please visit your %s and select an archive.', 'sip' ), $profile_page_link ) );
		// 	// translators: %d: The User-ID.
		// 	$this->set_error_log_message( sprintf( esc_html__( 'The user with the id %d has not selected an archive!', 'sip' ), $user_id ) );
		// }

		// to be able to set a term we need at least the user role contributor!
		$archival_tags  = wp_list_pluck( json_decode( stripcslashes( $user_input['archival_tags'] ), true ), 'value' );
		$post_data[ 'tax_input' ] = array(
			'archival_tag' => $archival_tags,
			'archive'      => $archives,
		);

		if (isset( $user_input[ 'archival_originator' ] )) {
			$post_data['meta_input']['_archival_originator'] = $user_input[ 'archival_originator' ];
		}
		if ($user_input[ 'archival_single_date' ]) {
			$post_data['meta_input']['_archival_from'] = $user_input[ 'archival_single_date' ];
		} else {
			if ($user_input[ 'archival_date_range' ]) {
				$post_data['meta_input']['_archival_from'] = $user_input[ 'archival_date_range' ][0] . '-01-01 00:00:00';
				$post_data['meta_input']['_archival_to']   = $user_input[ 'archival_date_range' ][1] . '-12-31 23:59:59';
			}
		}
		if (isset($user_input[ 'archival_address' ])) {
			$post_data['meta_input']['_archival_address'] = $user_input[ 'archival_address' ];
		}
		if (isset($user_input[ 'archival_lat' ])) {
			$post_data['meta_input']['_archival_lat'] = $user_input[ 'archival_lat' ];
		}
		if (isset($user_input[ 'archival_lng'])) {
			$post_data['meta_input']['_archival_lng'] = $user_input[ 'archival_lng'];
		}
		if (isset($user_input[ 'archival_area' ])) {
			$post_data['meta_input']['_archival_area'] = $user_input[ 'archival_area' ];
		}
		if (isset($user_input[ 'archival_upload_purpose' ])) {
			$post_data['meta_input']['_archival_upload_purpose'] = $user_input[ 'archival_upload_purpose' ];
		}
		if (isset($user_input[ 'archival_blocking_time' ])) {
			$post_data['meta_input']['_archival_blocking_time'] = $user_input[ 'archival_blocking_time' ];
		}
		if ($sip_custom_meta = carbon_get_theme_option('sip_custom_meta')) {
			foreach ($sip_custom_meta as $custom_meta) {
				$meta_name = sanitize_title($custom_meta['sip_custom_meta_title_' . $current_locale]);
				if (isset($_POST['_archival_' . $meta_name])) {
					$post_data['meta_input']['_archival_' . $meta_name] = sanitize_text_field( $_POST['_archival_' . $meta_name] );
				}
			}
		}
		if (isset($user_input[ 'archival_right_transfer' ])) {
			$post_data['meta_input']['_archival_right_transfer'] = $user_input[ 'archival_right_transfer' ];
		}
		if (isset($user_input[ 'archival_numeration' ])) {
			$post_data['meta_input']['_archival_numeration'] = $user_input[ 'archival_numeration' ];
		}
		if (isset($user_input[ 'archival_annotation' ])) {
			$post_data['meta_input']['_archival_annotation'] = $user_input[ 'archival_annotation' ];
		}
		if ($sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta')) {
			foreach ($sip_custom_archival_user_meta as $custom_archival_user_meta) {
				$meta_name = sanitize_title($custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale]);
				if (isset($_POST['_archival_' . $meta_name])) {
					$post_data['meta_input']['_archival_' . $meta_name] = sanitize_text_field( $_POST['_archival_' . $meta_name] );
				}
			}
		}
		if ( $sip_folder ) {
			$post_data['meta_input']['_archival_sip_folder'] = $sip_folder;
		}

		// create the new post for the uploaded archival. if post was created, redirect to the archival page, else show error message.
		$post_id = wp_insert_post( $post_data );
		if ( is_wp_error( $post_id ) ) {
			$this->set_error_message( sprintf( esc_html__( 'We have a problem creating/updating the post.', 'sip' ), $post_id ) );
			$this->set_error_log_message( __FUNCTION__ . ': ' . $post_id->get_error_message() );
			return false;
		}

		// translators: %d: Post-ID.
		$this->set_success_message(sprintf(esc_html__('Post %d successfully created/updated.', 'sip'), $post_id));

		// used to create the permalink for the edit page for SIP archival records.
		$url = starg_get_the_archival_page_template_url( $post_id );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'archival_ID'             => 'sanitize_key',
			'archival_title'          => 'sanitize_title',
			'archival_description'    => 'sanitize_textarea_field',
			'archival_originator'     => 'sanitize_text_field',
			'archival_address'        => 'sanitize_textarea_field',
			'archival_lat'            => 'sanitize_text_field',
			'archival_lng'            => 'sanitize_text_field',
			'archival_area'           => 'starg_sanitize_json',
			'archival_tags'           => 'trim',
			'archival_upload_purpose' => 'sanitize_text_field',
			'archival_blocking_time'  => 'sanitize_text_field',
			'archival_right_transfer' => 'sanitize_text_field',
			'archival_numeration'     => 'sanitize_text_field',
			'archival_annotation'     => 'sanitize_text_field',
			'archival_single_date'    => 'sanitize_text_field',
			'archival_date_range'     => 'starg_sanitize_array',
		);
	}

}
