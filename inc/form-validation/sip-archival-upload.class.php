<?php
if (! defined('WPINC')) { die; }

use Appwrite\ClamAV\Network;

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Sip_Archival_Upload extends Form_Validation {
	public string $nonce_action      = 'starg_add_archival_files_nonce_action';
	public string $nonce_key         = 'starg_add_archival_files_nonce';
	public string $form_name         = 'add_files_to_sip_form';
	public string $url_endpoint      = 'upload_file';//todo: subject to change. Actually we use it as query-arg.

	/**
	 * Process the uploaded files.
	 */
	public function process_archival_upload() : void {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid || ! isset( $_REQUEST[ $this->url_endpoint ] ) ) { return; }

		// save the uploaded binary file for later use. We can't sanitize a binary file with our sanitization functions.
		$uploaded_file = $_FILES['file'];
		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) {
			// translators: %d: Current user id.
			$this->set_error_log_message( sprintf( esc_attr__( 'Wrong user input while uploading an archival record from user %d.', 'sip' ), get_current_user_id() ) );
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode( array( 'archival_upload' => false, ) );
			exit;
		}

		$supported_mime_types = explode("\r\n", carbon_get_theme_option( 'sip_mime_types') );
		$sip_max_size = (carbon_get_theme_option( 'sip_max_size') ) ?: 50000000;

		$sip_folder       = starg_get_archival_upload_path() . $user_input['sipUserID'] . '/' . $user_input['sipFolder'] . '/';
		$upload_folder    = $sip_folder . 'content/';
		$upload_dir       = '';
		$upload_dir_array = explode( '/', $upload_folder );
		foreach ( $upload_dir_array as $path ) {
			$upload_dir = $upload_dir . $path . '/';
			if ( ! file_exists( $upload_dir ) ) {
				mkdir( $upload_dir, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS );
			}
		}

		// todo: move to own function! $this->add_uploaded_file_to_csv()
		// the names.csv contains all uploaded filenames.
		$fp = fopen($sip_folder . 'names.csv', 'a');

		if ( $user_input['fullPath'] ) {
			$full_path       = dirname( sanitize_text_field( $user_input['fullPath'] ) );
			$full_path_array = explode( '/', $full_path );
			foreach ( $full_path_array as $path ) {
				$sanitize_path = sanitize_file_name($path);
				fputcsv( $fp, array( strtolower( $sanitize_path ), $path ) );
				$upload_dir = $upload_dir . $sanitize_path . '/';
				if ( ! file_exists( $upload_dir ) ) {
					mkdir( $upload_dir, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS );
				}
			}
		}

		$sanitize_filename = sanitize_file_name( basename( $uploaded_file['name'] ) );
		fputcsv( $fp, array( strtolower( $sanitize_filename ), $sanitize_filename ) );
		fclose( $fp );
		// End $this->add_uploaded_file_to_csv()

		// todo: check for for duplicate filenames in the folder. Currently files with the same name are silently overwritten when uploaded.
		// todo: create checksum for each uploaded file and save it as post-meta!
		$upload_file_path = trailingslashit( $upload_dir ) . $sanitize_filename;
		$json_data        = array(
			'success' => true,
		);
		$file_deleted     = false;
		// todo: simplify!
		if ( move_uploaded_file( $uploaded_file['tmp_name'], $upload_file_path ) ) {
			$file_type = wp_check_filetype($upload_file_path);
			$sip_size  = 0;
			if ( isset( $_COOKIE['sip_file_size'] ) ) {
				$sip_size = sanitize_text_field( $_COOKIE['sip_file_size'] );
			}
			if ( $sip_size > $sip_max_size ) {
				$file_deleted          = unlink($upload_file_path);
				$json_data['sip_full'] = $sanitize_filename;
				$json_data['success']  = false;
			} else {
				$file_size                = filesize($upload_file_path);
				$sip_size                 = $sip_size + $file_size;
				$_COOKIE['sip_file_size'] = $sip_size;
				$json_data['sip_size']    = $sip_size;

				setcookie("sip_file_size", $sip_size, 0, '/');
			}
			if ( ! in_array( $file_type['type'], $supported_mime_types ) ) {
				$file_deleted               = unlink($upload_file_path);
				$json_data['success']       = false;
				$json_data['not_supported'] = $sanitize_filename;
			} elseif ( (bool) carbon_get_theme_option( 'sip_clamav' ) ) {
				$scan_result = $this->perform_virus_scan( $upload_file_path );
				if ( ! $scan_result ) {
					$file_deleted          = unlink($upload_file_path);
					$json_data['success']  = false;
					$file_deleted_msg      = ( $file_deleted ) ? esc_attr__( 'deleted', 'sip' ) : esc_attr__( 'not deleted', 'sip' );
					$json_data['infected'] = $sanitize_filename;

					// translators: %1$d: User Id. %2$s: Filename. %3$s: either "deleted" or "not deleted".
					$this->set_error_log_message( sprintf( esc_attr__('Infected file uploaded by %1$d in %2$s. File %3$s.', 'sip'), get_current_user_id(), $upload_file_path, $file_deleted_msg ) );
				}
			}
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_data);
		exit;
	}

	private function add_uploaded_file_to_csv() {}

	/**
	 * Perform a virus check with clamAV.
	 * @param string $upload_file_path Path to the uploaded file.
	 * @return bool false if clamAV is not responding or if a virus was found. true on success.
	 */
	private function perform_virus_scan( $upload_file_path ) : bool {
		$clam_rdy = false;
		try {
			$clam     = new Network( esc_attr( carbon_get_theme_option( 'sip_clamav_host' ) ), (int) esc_attr( carbon_get_theme_option( 'sip_clamav_port' ) ) );
			$clam_rdy = $clam->ping();
		} catch( Exception $exception ) {
			// no connection to clamav!
			$this->set_error_log_message( $exception->getMessage() );
			return false;
		}

		// maybe connected to clamav but clamav is not ready/responding.
		if ( ! $clam_rdy ) {
			$this->set_error_log_message( esc_attr__( 'clamav is not ready/responding', 'sip' ) );
			return false;
		}

		// todo: check if the file is scannable. there is also a possibility that the file can not be found.
		$scan_result = $clam->fileScan($upload_file_path);
		if ( ! $scan_result ) {
		// translators: %1$s: File path. %2$d: User ID.
			$this->set_error_log_message(sprintf(esc_attr__('Uploaded File %1$s from user id %2$d is infected', 'sip'), $upload_file_path, get_current_user_id() ) );
			return false;
		}

		return true;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'sipUserID'    => 'sanitize_key',
			'sipFolder'    => 'sanitize_text_field',
			// 'file'         => '', // we can't sanitize a binary file with our sanitization functions yet!
			'fullPath'     => 'sanitize_text_field',
		);
	}

}
