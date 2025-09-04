<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Sip_Archival_Remove extends Form_Validation {
	public string $nonce_action      = 'starg_remove_archival_files_nonce_action';
	public string $nonce_key         = 'starg_remove_archival_files_nonce';
	public string $form_name         = 'remove_uploaded_file_form';
	public string $url_endpoint      = 'remove_uploaded_file';//todo: subject to change. Actually we use it as query-arg.

	/**
	 * Delete the uploaded File on the server.
	 * This is called after the file has been deleted from the Dropzone.
	 */
	public function process_archival_remove() : void {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid || ! isset( $_REQUEST[ $this->url_endpoint ] ) ) { return; }

		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) {
			$this->set_error_log_message( esc_attr__( 'Wrong user input while removing an uploaded archival record', 'sip' ) );
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode( array( 'success' => false, ) );
			exit;
		}

		$json_data = array(
			'success' => true,
		);

		$sip_folder               = starg_get_archival_upload_path() . $user_input['sipUserID'] . '/' . $user_input['sipFolder'] . '/';
		$upload_folder            = $sip_folder . 'content/';
		$upload_file              = $upload_folder . $user_input['deletePath'];
		$filename                 = sanitize_file_name( basename( $upload_file ) );
		$lc_filename              = strtolower( $filename );
		$lc_upload_file           = str_replace( $filename, $lc_filename, $upload_file );
		$json_data['upload_file'] = $lc_upload_file; // todo: do we want to return the full path to the file? maybe it's enough to return the $lc_filename?

		if ( ! file_exists( $upload_file ) ) {
			$this->set_error_log_message( esc_attr__( 'Trying to delete a file that does not exist.', 'sip' ) );
			header('Content-Type: application/json; charset=utf-8');
			$json_data['success'] = false;
			echo json_encode( $json_data );
			exit;
		}

		$this->remove_deleted_filename_from_csv( $sip_folder, $lc_filename, $user_input['sipUserID'] );

		$file_size = filesize($upload_file);
		if (isset($_COOKIE['sip_file_size'])) {
			$sip_size                 = sanitize_text_field( $_COOKIE['sip_file_size'] );
			$sip_size                 =  $sip_size - $file_size;
			$_COOKIE['sip_file_size'] = $sip_size;
			setcookie("sip_file_size", $sip_size, 0, '/');

			$json_data['sip_size']    = $sip_size;
		}

		$file_deleted = unlink($upload_file);
		if ( ! $file_deleted ) {
			$json_data['success'] = false;

			// translators: %s: Name of an uploaded file.
			$this->set_error_log_message( sprintf( esc_attr__( 'File %s not deleted', 'sip' ), $upload_file ) );
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_data);
		exit;
	}

	/**
	 * Remove the filename from the csv file in the sip folder.
	 * @param string $sip_folder
	 * @param string $lc_filename
	 * @param int    $sipUserID
	 * @return bool
	 */
	private function remove_deleted_filename_from_csv( string $sip_folder, string $lc_filename, int $sipUserID = 0 ) : bool {
		// the names.csv contains all uploaded filenames. if we remove an uploaded file, we need to kick it from the names file as well!
		$names_csv = $sip_folder . 'names.csv';
		$csv_rows  = array_map( 'str_getcsv', file( $names_csv ) );

		if ( empty( $csv_rows ) ) { return false; }

		$new_names_csv = [];
		foreach ( $csv_rows as $single_row ) {
			if ( ! in_array( $lc_filename, $single_row ) ) {
				$new_names_csv[] = $single_row;
			}
		}

		if ( empty( $new_names_csv ) ) { return false; }

		// overwrite the csv.
		$lines_written = array();
		$csv_file      = fopen( $names_csv, 'w' );
		foreach ( $new_names_csv as $single_csv_row ) {
			$lines_written[] = fputcsv( $csv_file, $single_csv_row );
		}

		if ( in_array( false, $lines_written ) ) {
			// translators: %1$s: Name of the uploaded file. %2$s: the name of the sip folder. %3$s: User ID
			$this->set_error_log_message( sprintf( esc_attr__( 'Problems updating the csv for upload %1$s in sip folder %2$s from user id %3$d.', 'sip' ), $lc_filename, $sip_folder, $sipUserID ) );
			return false;
		}

		$file_written = fclose($csv_file);
		if ( ! $file_written ) {
			// translators: %1$s: Name of the uploaded file. %2$s: the name of the sip folder. %3$s: User ID
			$this->set_error_log_message( sprintf( esc_attr__( 'Problems updating the csv for upload %1$s in sip folder %2$s from user id %3$d.', 'sip' ), $lc_filename, $sip_folder, $sipUserID ) );
		}

		return $file_written;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'sipUserID'    => 'sanitize_key',
			'sipFolder'    => 'sanitize_text_field',
			'deletePath'   => 'sanitize_text_field',
		);
	}

}
