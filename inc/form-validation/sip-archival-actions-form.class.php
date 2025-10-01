<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Sip_Archival_Actions extends Form_Validation {
	public string $nonce_action = 'starg_archival_actions_nonce_action';
	public string $nonce_key    = 'starg_archival_actions_nonce';
	public string $form_name    = 'archival_actions_form';

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

		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) { return false; }

		$missing_inputs = $this->user_input_required( $user_input );
		if ( ! empty( $missing_inputs ) ) {
			$this->set_notification_for_missing_inputs( $missing_inputs );
			$this->display_notification();
			return false;// todo: maybe change to $user_input to be able to fill in the validated data for the user.
		}

		$sip_folder = $user_input[ 'sipFolder' ];
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
			$this->set_error_message( sprintf( esc_html__( 'No archival record found for the provided SIP folder: %s. This entry can not be deleted.', 'sip' ), $sip_folder ) );
			// translators: %s: identifier for an archival record.
			$this->set_error_log_message( sprintf( esc_html__( 'Problems removing an archival record. Please check sip folder %s', 'sip' ), $sip_folder ) );
			$this->display_notification();
			return false;
		}

		$archival_status         = get_post_status($archival_id);
		$archival_user_id        = get_post_field( 'post_author', $archival_id );
		$is_users_archival_post  = ( (int) $archival_user_id === get_current_user_id() ); // the author of the archival record post is allowed to remove it.
		$user_can_accept_decline = ( current_user_can( 'edit_others_posts' ) && $archival_status !== 'publish' );
		$user_can_submit         = ( current_user_can( 'read_archival', $archival_id ) && $archival_status !== 'publish' ); // todo: maybe change capability to "edit_archival"

		// check if the user is allowed to perform an action here.
		if ( ! $user_can_accept_decline && ( ! $user_can_submit && ! $is_users_archival_post ) ) {
			$this->set_error_message( esc_html__( 'You are not allowed to perform this action.', 'sip' ) );
			$this->display_notification();
			return false;
		}

		$action_result = false;
		// perform the action. this can be an approval, rejection (=deletion) or a submission.
		if ( $user_can_accept_decline && $user_input[ 'accept_archival' ] ) {
			$action_result = $this->_process_action_accept( $archival_id );
		}

		if ( ( $user_can_accept_decline || $is_users_archival_post ) && $user_input[ 'decline_archival' ] ) {
			$action_result = $this->_process_action_decline( $sip_folder, $archival_id );
		}

		if ( $is_users_archival_post && $user_can_submit && $user_input[ 'submit_archival' ] ) {
			$action_result = $this->_process_action_submit( $archival_id );
		}
		
		$this->display_notification();
		return $action_result;
	}

	/**
	 * Accept an archival record. This updates the post_status from draft to publish.
	 * @param int $archival_id
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

		$archivar_user_set = update_post_meta($maybe_new_archival_post_id, '_archival_archivar_user_id', get_current_user_id());
		if ( ! $archivar_user_set ) {
			// translators: %d: Post-ID of an archival record.
			$this->set_error_log_message( sprintf( esc_attr__( 'Archivist user ID not set for archival record post: %d', 'sip' ), $maybe_new_archival_post_id ) );
			// translators: %d: Post-ID of an archival record.
			$this->set_error_message( sprintf( esc_attr__( 'Archivist user ID not set for archival record post: %d', 'sip' ), $maybe_new_archival_post_id ) );
			return false;
		}

		$this->set_success_message( esc_attr__( 'Archival record accepted.', 'sip' ) );
		return true;
	}

	/**
	 * Decline an archival record. This deletes both! The uploaded items and the archival post!
	 * @param string $sip_folder
	 * @param int $archival_id
	 * @return bool
	 */
	private function _process_action_decline( string $sip_folder, int $archival_post_id ) : bool {
		$author_id  = get_post_field('post_author', $archival_post_id);
		$sip_folder = starg_get_archival_upload_path() . $author_id . '/' . $sip_folder . '/';
		if (is_dir($sip_folder)) {
			$sip_deleted = starg_remove_SIP($sip_folder);
			if ( ! $sip_deleted ) {
				// translators: %s: ID/Name of the folder where the archival record (sip) is stored.
				$this->set_error_log_message( sprintf( esc_attr__( 'Failed to delete the folder for archival record %s.', 'sip' ), $sip_folder ) );
				// translators: %s: ID/Name of the folder where the archival record (sip) is stored.
				$this->set_error_message( sprintf( esc_attr__( 'Failed to delete the folder for archival record %s.', 'sip' ), $sip_folder ) );
				return false;
			}
		}

		$post_deleted_id = wp_delete_post($archival_post_id, true);

		if ( ! $post_deleted_id ) {
			// translators: %d: Post-ID of the archival record.
			$this->set_error_message( sprintf( esc_attr__( 'Failed to delete the archival record with the ID %d.', 'sip' ), $archival_post_id ) );
			return false;
		}

		$this->set_success_message( esc_attr__( 'Archival record deleted.', 'sip' ) );
		return true;
	}

	/**
	 * Submits an archival record. The post status changes from draft to pending.
	 * @param string $sip_folder
	 * @param int $archival_id
	 * @return bool
	 */
	private function _process_action_submit(int $archival_post_id): bool {
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

		$this->set_success_message(esc_attr__('Archival record submitted.', 'sip'));
		return true;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'accept_archival'    => 'sanitize_key',
			'decline_archival'   => 'sanitize_key',
			'submit_archival'    => 'sanitize_key',
			'sipFolder'          => 'sanitize_text_field',
		);
	}

	protected function get_required_input_names() : array {
		return array( 'sipFolder' => true, );
	}

}
