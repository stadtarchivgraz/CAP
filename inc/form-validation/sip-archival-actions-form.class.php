<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Sip_Archival_Actions extends Form_Validation {
	public string $nonce_action = 'starg_archival_actions_nonce_action';
	public string $nonce_key    = 'starg_archival_actions_nonce';
	public string $form_name    = 'archival_actions_form';
	private array $user_input;

	/***************/
	/* Main action */
	/***************/

	/**
	 * Process actions for saved archival records.
	 * This function validates and sanitizes the users inputs.
	 * Based on a users sip folder we look for an matching archival post.
	 * At the moment there are three actions a user might trigger. An approval, a rejection or a submission.
	 *     - The approval can only be triggered by an admin/editor. This action sets the post status from "pending" to "publish".
	 *     - The rejection can also only be triggered by an admin/editor. This action deletes the sip folder and all it's content as well as the archival post itself!
	 *     - The submission can only be triggered by the author of the archival post. This action sets the post status from "draft" to "pending". This means the archival is ready for the archivist to be verified.
	 * 
	 * @return bool true on success. false on failure.
	 */
	public function process_sip_archival_actions() : bool {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		$this->user_input = $this->user_input_sanitization();
		if ( ! $this->user_input ) { return false; }

		$missing_inputs = $this->user_input_required( $this->user_input );
		if ( ! empty( $missing_inputs ) ) {
			$this->set_notification_for_missing_inputs( $missing_inputs );
			$this->display_notification();
			return false;// todo: maybe change to $this->user_input to be able to fill in the validated data for the user.
		}

		$sip_folder = $this->user_input[ 'sipFolder' ];
		// we check for the $sip_folder with the method user_input_required. So this check should be deprecated.
		// if ( ! $sip_folder ) {
		// 	$this->set_error_message( esc_html__( 'No SIP folder provided.', 'sip' ) );
		// 	$this->display_notification();
		// 	return false;
		// }

		// todo: we have a problem here! a successful upload creates the upload folder but not the post! the post gets created on meta-data input!
		$archival_id     = DB_Query_Helper::starg_get_archival_id_by_sip_folder( $sip_folder );
		if ( ! $archival_id ) {
			// translators: %s: identifier for an archival record.
			$this->set_error_message( sprintf( esc_html__( 'No archival record found for the provided SIP folder: %s. This entry cannot be deleted.', 'sip' ), $sip_folder ) );
			// translators: %s: identifier for an archival record.
			$this->set_error_log_message( sprintf( esc_html__( 'Problems removing an archival record. Please check the SIP folder %s.', 'sip' ), $sip_folder ) );
			$this->display_notification();
			return false;
		}

		$archival_status         = get_post_status($archival_id);
		$archival_author_id      = get_post_field( 'post_author', $archival_id );
		$is_users_archival_post  = ( (int) $archival_author_id === get_current_user_id() ); // the author of the archival record post is allowed to remove it.
		$user_can_accept_decline = ( current_user_can( 'edit_others_archival_records' ) && $archival_status !== 'publish' );
		$user_can_submit         = ( current_user_can( 'read_archival', $archival_id ) && $archival_status !== 'publish' );

		// check if the user is allowed to perform an action here.
		if ( ! $user_can_accept_decline && ( ! $user_can_submit && ! $is_users_archival_post ) ) {
			$this->set_error_message( esc_html__( 'You are not allowed to perform this action.', 'sip' ) );
			$this->display_notification();
			return false;
		}

		$action_result = false;
		// perform the action. this can be an approval, rejection (=deletion) or a submission.
		if ( $user_can_accept_decline && 'accept' === $this->user_input[ 'accept_archival' ] ) {
			$action_result = $this->_process_action_accept( $archival_id );
		}

		if ( $user_can_accept_decline && ( 'decline' === $this->user_input[ 'decline_archival' ] || 'decline_with_response' === $this->user_input['decline_archival'] ) ) {
			$action_result = $this->_process_action_decline( $sip_folder, $archival_id );
		}

		// todo: check if it is better to use the expected value for the user_input here.
		if ( ( $user_can_accept_decline || $is_users_archival_post ) && 'delete' === $this->user_input[ 'delete_archival' ] ) {
			$action_result = $this->_process_action_delete( $sip_folder, $archival_id );
		}

		if ( $is_users_archival_post && $user_can_submit && 'submit' === $this->user_input[ 'submit_archival' ] ) {
			$action_result = $this->_process_action_submit( $archival_id, $archival_author_id );
		}
		
		$this->display_notification();
		return $action_result;
	}

	/***********/
	/* Actions */
	/***********/

	/**
	 * Accept an archival record. This updates the post_status from draft to publish.
	 * @param int $archival_post_id
	 * @return bool
	 */
	private function _process_action_accept( int $archival_post_id ) : bool {
		$post_data = array(
			'ID'          => $archival_post_id,
			'post_status' => 'publish',
		);
		$maybe_new_archival_post_id = wp_update_post($post_data);
		if ( is_wp_error($maybe_new_archival_post_id)) {
			$this->set_error_log_message( $maybe_new_archival_post_id->get_error_message() );
			// translators: %d: Post-ID of an archival record.
			$this->set_error_message(  sprintf( esc_attr__( 'The post with the ID %d could not be updated.', 'sip' ), $archival_post_id )  );
			return false;
		}

		$archivist_user_set = update_post_meta($maybe_new_archival_post_id, '_archival_archivar_user_id', get_current_user_id());
		if ( ! $archivist_user_set ) {
			// translators: %d: Post-ID of an archival record.
			$this->set_error_log_message( sprintf( esc_attr__( 'Archivist user ID not set for archival record post: %d', 'sip' ), $maybe_new_archival_post_id ) );
			// translators: %d: Post-ID of an archival record.
			$this->set_error_message( sprintf( esc_attr__( 'Archivist user ID not set for archival record post: %d', 'sip' ), $maybe_new_archival_post_id ) );
		}

		$author_id = get_post_field( 'post_author', $maybe_new_archival_post_id, true );
		$this->notify_user_accept( $author_id, $maybe_new_archival_post_id );

		$this->set_success_message( esc_attr__( 'Archival record accepted.', 'sip' ) );
		return true;
	}

	/**
	 * Decline an archival record. This (soft) deletes both! The uploaded items and the archival post!
	 * @param string $sip_folder
	 * @param int $archival_post_id
	 * @return bool
	 *
	 * @todo maybe create an option if the data (uploaded items + post) should be deleted or just trashed?
	 */
	private function _process_action_decline( string $sip_folder, int $archival_post_id ) : bool {
		if ( ! $sip_folder && ! $archival_post_id ) { return false; }

		$author_id    = get_post_field( 'post_author', $archival_post_id );
		$archivist_id = (int) get_post_meta( $archival_post_id, '_archival_archivar_user_id', true );

		// todo: do not delete posts yet. Reactivate later.
		// $post_deleted_id = wp_delete_post($archival_post_id, true);
		$post_deleted_id = wp_trash_post($archival_post_id);
		if ( ! $post_deleted_id ) {
			// translators: %d: Post-ID of the archival record.
			$this->set_error_message( sprintf( esc_attr__( 'Failed to delete the archival record with the ID %d.', 'sip' ), $archival_post_id ) );
			return false;
		}

		// todo: reactivate the removal of uploaded Files. At the moment we only remove uploaded files if the corresponding post is deleted in the backend.
		// $sip_folder = starg_get_archival_upload_path() . $author_id . '/' . $sip_folder . '/';
		// if (is_dir($sip_folder)) {
		// 	$sip_deleted = starg_remove_SIP($sip_folder);
		// 	if ( ! $sip_deleted ) {
		// 		// translators: %s: ID/Name of the folder where the archival record (sip) is stored.
		// 		$this->set_error_log_message( sprintf( esc_attr__( 'Failed to delete the folder for archival record %s.', 'sip' ), $sip_folder ) );
		// 		// translators: %s: ID/Name of the folder where the archival record (sip) is stored.
		// 		$this->set_error_message( sprintf( esc_attr__( 'Failed to delete the folder for archival record %s.', 'sip' ), $sip_folder ) );
		// 		return false;
		// 	}
		// }

		// maybe notify the user about the rejection of a submission.
		if ( 'decline_with_response' === $this->user_input['decline_archival'] ) {
			$this->notify_user_decline( $author_id, $archivist_id );
		}

		$this->set_success_message( esc_attr__( 'Archival record deleted.', 'sip' ) );
		return true;
	}

	/**
	 * Delete an archival record. This deletes both! The uploaded items and the archival post!
	 * @param string $sip_folder
	 * @param int $archival_post_id
	 * @return bool
	 */
	private function _process_action_delete( string $sip_folder, int $archival_post_id ) : bool {
		// todo: reactivate the removal of uploaded Files. At the moment we only remove uploaded files if the corresponding post is deleted in the backend.
		// $author_id  = get_post_field('post_author', $archival_post_id);
		// $sip_folder = starg_get_archival_upload_path() . $author_id . '/' . $sip_folder . '/';
		// if (is_dir($sip_folder)) {
		// 	$sip_deleted = starg_remove_SIP($sip_folder);
		// 	if ( ! $sip_deleted ) {
		// 		// translators: %s: ID/Name of the folder where the archival record (sip) is stored.
		// 		$this->set_error_log_message( sprintf( esc_attr__( 'Failed to delete the folder for archival record %s.', 'sip' ), $sip_folder ) );
		// 		// translators: %s: ID/Name of the folder where the archival record (sip) is stored.
		// 		$this->set_error_message( sprintf( esc_attr__( 'Failed to delete the folder for archival record %s.', 'sip' ), $sip_folder ) );
		// 		return false;
		// 	}
		// }

		// todo: do not delete posts yet. Reactivate later.
		// $post_deleted_id = wp_delete_post($archival_post_id, true);
		$post_deleted_id = wp_trash_post($archival_post_id);

		if ( ! $post_deleted_id ) {
			// translators: %d: Post-ID of the archival record.
			$this->set_error_message( sprintf( esc_attr__( 'Failed to delete the archival record with the ID %d.', 'sip' ), $archival_post_id ) );
			return false;
		}

		// delete the post_meta.
		// delete_post_meta( $post_deleted_id, '_archival_sip_folder' );

		$this->set_success_message( esc_attr__( 'Archival record deleted.', 'sip' ) );
		return true;
	}

	/**
	 * Submits an archival record. The post status changes from draft to pending.
	 * @param int $archival_post_id
	 * @return bool
	 */
	private function _process_action_submit( int $archival_post_id, $archival_author_id ): bool {
		$post_data = array(
			'ID'          => $archival_post_id,
			'post_status' => 'pending',
		);
		$maybe_updated_post_id = wp_update_post($post_data);
		if (is_wp_error($maybe_updated_post_id)) {
			$this->set_error_log_message($maybe_updated_post_id->get_error_message());
			// translators: %d: Post-ID of an archival record.
			$this->set_error_message( sprintf( esc_attr__( 'The post with the ID %d could not be updated.', 'sip' ), $archival_post_id ) );
			return false;
		}
		$user_archive_id = (int) get_user_meta( $archival_author_id, 'user_archive', true );

		$this->notify_user_submit( get_current_user_id(), (int) $archival_author_id, $user_archive_id, $archival_post_id );

		$this->set_success_message(esc_attr__('Archival record submitted.', 'sip'));
		return true;
	}

	/*****************/
	/* Notifications */
	/*****************/

	/**
	 * Create the content of the notification if a submission was accepted and trigger sending.
	 * @param int $author_id
	 * @param int $archival_post_id
	 * @return void
	 */
	private function notify_user_accept( int $author_id, int $archival_post_id ): void {
		if ( ! carbon_get_theme_option( 'sip_notifications_enabled' ) ) { return; }

		$author_name  = '';
		$author_mail  = '';
		$user_archive = '';
		$permalink    = esc_url( get_permalink( $archival_post_id ) );
		$author       = get_user_by( 'ID', $author_id );
		if ( $author ) {
			$author_name       = $author->display_name;
			$author_mail       = $author->user_email;
			$user_archive_id   = (int) get_user_meta( $author_id, 'user_archive', true );
			$user_archive_term = get_term( $user_archive_id, Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG );
			if ( $user_archive_term ) {
				$user_archive = $user_archive_term->name;
			}
		}

		$post_title   = get_the_title( $archival_post_id );
		$originator   = get_post_meta( $archival_post_id, '_archival_originator', true );
		$link_to_post = '<a href="' . $permalink . '">' . $permalink . '</a>';

		// translators: %1$s: Name of the user. %2$s: Title of the submission. %3$s: Name of the originator. %4$s: Link to the post.
		$message = sprintf( esc_html__( 'Dear %1$s,

Title: %2$s

Author: %3$s

%4$s

has been accepted and will be taken over by the archive.

Thank you for your contribution.', 'sip' ), $author_name, $post_title, $originator, $link_to_post );
		// translators: %s: Name of the institution.
		$subject = sprintf( esc_attr__( 'Your submission to %s', 'sip' ), $user_archive );
		$this->send_email_notification( $message, $subject, $author_mail );
	}

	/**
	 * Create the content of the notification if a submission was declined and trigger sending.
	 * @param int $author_id
	 * @param int $archival_post_id
	 * @return void
	 */
	private function notify_user_decline( int $author_id, int $archivist_id = 0 ): void {
		// the editor who handles the submission.
		if ( ! $archivist_id || $archivist_id !== get_current_user_id() ) {
			$archivist_id = get_current_user_id();
		}

		$author_name  = '';
		$author_email = '';
		$author       = get_user_by( 'ID', $author_id );
		if ( $author ) {
			$author_name  = $author->display_name;
			$author_email = sanitize_email( $author->user_email );
		}

		$subject = esc_html__( 'Your submission was rejected', 'sip' );
		// translators: %1$s: Name of the user. %2$s: Name of the Website.
		$message = sprintf( esc_html__( 'Dear %1$s,

thank you very much for your submission to %2$s! Unfortunately, we cannot accept your submission into the archive and must reject it.', 'sip' ), $author_name, esc_attr( get_bloginfo( 'name' ) ) );

		if ( $this->user_input['notification_content'] ) {
			// translators: %s: Reason for the rejection.
			$message .= PHP_EOL . PHP_EOL . sprintf( esc_html__( 'Reason for the rejection: %s', 'sip' ), $this->user_input['notification_content'] );
		}

		// maybe we want to tell the user, who was reviewing their submission, so they can contact them directly.
		if ( $this->user_input[ 'notification_contact_data' ] && $archivist_id ) {
			$archivist = get_user_by( 'ID', $archivist_id );
			if ( $archivist ) {
				$archivist_name   = $archivist->display_name;
				$archivist_email  = sanitize_email( $archivist->user_email );
				$archivist_mailto = '<a href="mailto:' . $archivist_email . '">' . $archivist_email . '</a>';
				// translators: %1$s: Name of the responsible archivist. %2$s: Email Link.
				$message .= PHP_EOL . PHP_EOL . sprintf( esc_html__( 'If you have any questions regarding the rejection, please contact %1$s at %2$s.', 'sip' ), $archivist_name, $archivist_mailto );
			}
		}

		$this->send_email_notification( $message, $subject, $author_email );
	}

	/**
	 * Create the content of the notification if a submission was submitted and trigger sending.
	 * @param int $current_user_id
	 * @param int $orig_author_id
	 * @param int $user_archive_id
	 * @param int $post_id
	 * @return void
	 */
	private function notify_user_submit( int $current_user_id, int $orig_author_id, int $user_archive_id, int $post_id = 0 ): void {
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
				// add only users with the same archive.
				$selected_archive_id = get_user_meta( $single_user_id, 'user_archive', true );
				if ( $user_archive_id === $selected_archive_id ) {
					$other_email[ $single_user_id ] = get_userdata( $single_user_id )->user_email;
				}
			}
			$editor_email = array_merge( $editor_email, $other_email );
		}

		$link_to_archival = get_home_url();
		if ( $post_id ) {
			// todo: maybe use the permalink instead of the title as text?
			$link_to_archival = '<a href="' . get_permalink( $post_id ) . '">' . $this->user_input[ 'archival_title' ] . '</a>';
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

		// translators: %1$s: Title of the submission. %2$s: Name of the user. %3$s: Name of the institution. %4$s: Link to the post.
		$message_to_editors = sprintf( esc_html__( 'Title: %1$s

Author: %2$s

New files have been uploaded to the following archive: %3$s

Please log in to the archive for review and approval.

%4$s', 'sip' ), $this->user_input[ 'archival_title' ], $author_name, $user_archive, $link_to_archival );
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

	/*****************/
	/*** UI Elements */
	/*****************/

	/**
	 * Renders a button with a modal to safely trigger the decline action for a user submission.
	 * @param string $user_sip_id
	 * @param string $user_sip_title
	 * @return void
	 */
	public function archival_decline_button( string $user_sip_id, string $user_sip_title = '' ): void {
		if ( ! $user_sip_id ) { return; }
		ob_start();
		?>
		<button class="button is-danger is-light is-outlined js-modal-trigger" type="button" data-modal-id="<?php echo $user_sip_id; ?>">
			<?php esc_html_e('Decline', 'sip'); ?>
		</button>
		<?php
		$modal_title    = esc_html__('Confirm rejection', 'sip');
		$user_sip_title = ( $user_sip_title ) ? esc_html($user_sip_title) : esc_html__( 'this entry', 'sip' );
		// translators: %s: Title of the post.
		$modal_content  = sprintf( esc_html__( 'Are you sure to decline %s? All files will be deleted.', 'sip' ), '<strong>' . $user_sip_title . '</strong>' );
		$modal_action_d = array(
			array(
				'name'  => 'decline_archival',
				'type'  => 'submit',
				'value' => 'decline',
				'text'  => esc_html__( 'Decline', 'sip' ),
				'class' => 'is-danger',
			),
		);
		echo $this->get_action_modal( $user_sip_id, $modal_title, $modal_content, $modal_action_d );

		echo ob_get_clean();
	}

	/**
	 * Renders a button with a modal to safely trigger the decline action for a user submission.
	 * Within the modal the editor may add a subject and/or a content which will be sent as notification email to the user.
	 * @param string $user_sip_id
	 * @param string $user_sip_title
	 * @return void
	 */
	public function archival_decline_button_with_response_form( string $user_sip_id, string $user_sip_title = '' ): void {
		if ( ! $user_sip_id ) { return; }
		ob_start();
		?>
		<button class="button is-danger is-light is-outlined js-modal-trigger" type="button" data-modal-id="<?php echo $user_sip_id; ?>">
			<?php esc_html_e('Decline with response', 'sip'); ?>
		</button>
		<?php
		$modal_title    = esc_html__('Confirm rejection', 'sip');
		$user_sip_title = ( $user_sip_title ) ? esc_html($user_sip_title) : esc_html__( 'this entry', 'sip' );
		$modal_content  = sprintf( esc_html__( 'Are you sure to decline %s? All files will be deleted.', 'sip' ), '<strong>' . $user_sip_title . '</strong>' );
		$modal_action_d = array(
			array(
				'name'  => 'decline_archival',
				'type'  => 'submit',
				'value' => 'decline_with_response',
				'text'  => esc_html__( 'Decline', 'sip' ),
				'class' => 'is-danger',
			),
		);
		$form_elements = array(
			'notification_content' => array(
				'label'       => esc_html__( 'Reason for the rejection:', 'sip' ),
				'type'        => 'textarea',
				'name'        => 'notification_content',
				'id'          => 'notification_content',
				'class'       => 'textarea',
				'placeholder' => esc_html__( 'Please use this text area if you want to add a reason for the rejection in the email notification.', 'sip' ),
			),
			'notification_contact_data' => array(
				'label'     => esc_html__('Add the name and email address of the responsible archivist (you) in the notification email.', 'sip'),
				'type'      => 'checkbox',
				'name'      => 'notification_contact_data',
				'id'        => 'notification_contact_data',
				'class'     => 'checkbox',
				'checked'   => true,
				'help_text' => esc_html__( 'This might be useful if a contributor wants to directly contact you.', 'sip' ),
			),
		);
		echo $this->get_action_modal( $user_sip_id, $modal_title, $modal_content, $modal_action_d, $form_elements );

		echo ob_get_clean();
	}

	/**
	 * Renders a button with a modal to safely trigger the delete action for a user submission.
	 * @param string $user_sip_id
	 * @param string $user_sip_title
	 * @return void
	 */
	public function archival_delete_button( string $user_sip_id, string $user_sip_title = '' ): void {
		if ( ! $user_sip_id ) { return; }
		ob_start();
		?>
		<button class="button is-danger is-light is-outlined js-modal-trigger" type="button" data-modal-id="<?php echo $user_sip_id; ?>">
			<?php esc_html_e('Delete', 'sip'); ?>
		</button>
		<?php
		$modal_title    = esc_html__('Confirm deletion', 'sip');
		$user_sip_title = ( $user_sip_title ) ? esc_html($user_sip_title) : esc_html__( 'this entry', 'sip' );
		// translators: %s: Title of the post.
		$modal_content  = sprintf( esc_html__( 'Are you sure you want to delete %s? All files will be deleted.', 'sip' ), '<strong>' . $user_sip_title . '</strong>' );
		$modal_action   = array(
			array(
				'name'  => 'delete_archival',
				'type'  => 'submit',
				'value' => 'delete',
				'text'  => esc_html__( 'Delete', 'sip' ),
				'class' => 'is-danger',
			),
		);
		echo $this->get_action_modal( $user_sip_id, $modal_title, $modal_content, $modal_action );

		echo ob_get_clean();
	}

	/****************/
	/** Form inputs */
	/****************/

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'accept_archival'           => 'sanitize_key',
			'decline_archival'          => 'sanitize_key',
			'delete_archival'           => 'sanitize_key',
			'submit_archival'           => 'sanitize_key',
			'notification_content'      => 'wp_kses_post',
			'notification_contact_data' => 'sanitize_text_field',
			'sipFolder'                 => 'sanitize_text_field',
		);
	}

	/**
	 * Describes which inputs of the form are required.
	 * If a form has not delivered one of these inputs, we do not trigger any action but display an error message.
	 * For performance reasons we use the input names as keys for the array. This way we can use isset() instead of in_array().
	 * @return array
	 */
	protected function get_required_input_names() : array {
		return array( 'sipFolder' => true, );
	}

}
