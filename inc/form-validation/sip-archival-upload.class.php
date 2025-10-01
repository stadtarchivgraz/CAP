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
		$sip_max_size = (carbon_get_theme_option( 'sip_max_size') ) ? (int) carbon_get_theme_option( 'sip_max_size') : 50000000;

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
					/** we could also use something like @see wp_mkdir_p(), but it creates permission with 0777. */
					mkdir( $upload_dir, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS, true );
				}
			}
		}

		$sanitize_filename = sanitize_file_name( basename( $uploaded_file['name'] ) );
		$upload_file_path  = trailingslashit( $upload_dir ) . $sanitize_filename; // todo: create checksum for each uploaded file and save it as post-meta!

		// don't overwrite existing files.
		if ( file_exists( $upload_file_path ) ) {
			$sanitize_filename = $this->get_unique_filename( $upload_dir, $sanitize_filename );
			$upload_file_path  = trailingslashit( $upload_dir ) . $sanitize_filename;
		}

		fputcsv( $fp, array( strtolower( $sanitize_filename ), $sanitize_filename ) );
		fclose( $fp );
		// End $this->add_uploaded_file_to_csv()

		$json_data        = array(
			'success' => true,
		);
		$file_deleted     = false;

		/** we can not use something like @see wp_upload_dir(), because we need to specify the upload directory only for archival data! */
		$file_moved = move_uploaded_file( $uploaded_file['tmp_name'], $upload_file_path );
		if ( ! $file_moved ) {
			$file_deleted         = unlink($upload_file_path);
			$json_data['success'] = false;
			// translators: %1$s: Filename. %2$s: Path to folder.
			$this->set_error_log_message( sprintf( esc_attr__( 'Uploaded file %1$s not moved to uploads folder %2$s', 'sip' ), $sanitize_filename, $upload_file_path ) );

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($json_data);
			exit;
		}

		$file_type = wp_check_filetype($upload_file_path);
		$sip_size  = 0;
		if ( isset( $_COOKIE['sip_file_size'] ) ) {
			$sip_size = sanitize_text_field( $_COOKIE['sip_file_size'] );
		}
		$json_data['sip_size'] = $sip_size;

		// check max sip size.
		if ( $sip_size > $sip_max_size ) {
			$file_deleted          = unlink($upload_file_path);
			$json_data['sip_full'] = $sanitize_filename;
			$json_data['success']  = false;

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($json_data);
			exit;
		}

		// check if file type is supported
		if ( ! in_array( $file_type['type'], $supported_mime_types ) ) {
			$file_deleted               = unlink($upload_file_path);
			$json_data['success']       = false;
			$json_data['not_supported'] = $sanitize_filename;
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($json_data);
			exit;
		}

		$file_scan_result = $this->scan_file( $upload_file_path );
		if ( true !== $file_scan_result ) {
			// removal of the uploaded file happens during scan_file here.
			$json_data['success']  = false;
			$json_data['infected'] = $sanitize_filename;
			if ( isset( $file_scan_result['reason'] ) ) {
				$json_data['reason'] = $file_scan_result['reason'];
			}
		}

		$file_size                = filesize($upload_file_path);
		$sip_size                 = $sip_size + $file_size;
		$_COOKIE['sip_file_size'] = $sip_size;
		$json_data['sip_size']    = $sip_size;

		setcookie("sip_file_size", $sip_size, 0, '/');

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_data);
		exit;
	}

	/**
	 * Add a counter to a filename to prevent overwriting files with the same filename.
	 */
	private function get_unique_filename( $directory, $filename ) {
		$fileinfo     = pathinfo($filename);
		$basename     = sanitize_file_name($fileinfo['filename']);
		$extension    = isset($fileinfo['extension']) ? '.' . $fileinfo['extension'] : '';
		$new_filename = $basename . $extension;
		$counter      = 1;

		while ( file_exists($directory . '/' . $new_filename) ) {
			$new_filename = $basename . '-' . $counter . $extension;
			$counter++;
		}

		return $new_filename;
	}

	private function add_uploaded_file_to_csv() {}

	/**
	 * Perform scans for malware on the uploaded file.
	 * @param string $upload_file_path
	 * @return void|bool|array{ success: bool, reason: string } NULL if no scan was performed. true if everything is ok, array{ success: bool, reason: string } if file was not found, antivirus software is not ready, file is infected.
	 */
	private function scan_file( string $upload_file_path ) {
		if ( ! (bool) carbon_get_theme_option( 'sip_clamav' ) ) { return NULL; }
		if ( ! function_exists('socket_create') ) {
			$this->set_error_log_message(esc_attr__('ClamAV: can not connect because module socket_create is missing.', 'sip'));
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: file not scanned.', 'sip' ), );
		}

		$scan_result = $this->scan_file_for_viruses( $upload_file_path );
		if ( ! $scan_result['success'] ) {
			$file_deleted     = unlink($upload_file_path);
			$file_deleted_msg = ( $file_deleted ) ? esc_attr__( 'deleted', 'sip' ) : esc_attr__( 'not deleted', 'sip' );

			// translators: %1$d: Filename. %2$s: User Id. %3$s: either "deleted" or "not deleted". %4$s: Virus scan result.
			$this->set_error_log_message( sprintf( esc_attr__('Problem with file %1$s from user %2$d. File %3$s. Virus scan result: %4$s', 'sip'), $upload_file_path, get_current_user_id(), $file_deleted_msg, $scan_result['reason'] ) );

			return $scan_result;
		}

		return true;
	}

	/**
	 * Perform a virus check with clamAV.
	 * @param string $upload_file_path Path to the uploaded file.
	 * @return array{success:bool, reason:string} success:false if clamAV is not responding or if a virus was found. success:true on success.
	 */
	private function scan_file_for_viruses( $upload_file_path ) : array {
		$clam_rdy = false;
		try {
			$clam     = new Network( esc_attr( carbon_get_theme_option( 'sip_clamav_host' ) ), (int) esc_attr( carbon_get_theme_option( 'sip_clamav_port' ) ) );
			$clam_rdy = $clam->ping();
		} catch( Exception $exception ) {
			// no connection to clamav!
			$this->set_error_log_message( $exception->getMessage() );
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: not responding', 'sip' ), );
		}

		// maybe connected to clamav but clamav is not ready/responding.
		if ( ! $clam_rdy ) {
			$this->set_error_log_message( esc_attr__( 'ClamAV is not ready/responding', 'sip' ) );
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: not ready', 'sip' ), );
		}

		if ( ! file_exists( $upload_file_path ) ) {
			// translators: %1$s: path to the file. %2$s: user id.
			$this->set_error_log_message(sprintf(esc_attr__('Uploaded File %1$s from user id %2$d was not scanned. File not found', 'sip'), $upload_file_path, get_current_user_id() ) );
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: file not found', 'sip' ), );
		}

		$scan_result = $clam->fileScan($upload_file_path);
		if ( ! $scan_result ) {
		// translators: %1$s: File path. %2$d: User ID.
			$this->set_error_log_message(sprintf(esc_attr__('Uploaded File %1$s from user id %2$d is infected', 'sip'), $upload_file_path, get_current_user_id() ) );
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: virus detected', 'sip' ), );
		}

		return array( 'success' => true, 'reason' => esc_attr__( 'ClamAV: file is safe', 'sip' ), );
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

	
	protected function get_required_input_names() : array {
		return array();
	}

}
