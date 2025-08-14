<?php
/**
 * Manages the upload of the files from Dropzone.
 * @todo solve this with our form-validation class!
 */

$json_data = array();

// todo: refactor!
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

if ( ! isset( $_POST[ 'starg_form_name' ] ) && 'add_files_to_sip_form' !== $_POST[ 'starg_form_name' ] ) {
	wp_send_json_error( 'wrong form!', 403 );
	exit;
}

if ( ! isset( $_POST[ 'starg_add_archival_files_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'starg_add_archival_files_nonce' ], 'starg_add_archival_files_nonce_action' ) ) {
	wp_send_json_error( 'wrong nonce!', 403 );
	exit;
}

require_once( 'vendor/autoload.php' );
use Appwrite\ClamAV\Network;

$supported_mime_types = explode("\r\n", carbon_get_theme_option( 'sip_mime_types') );
$sip_max_size = (carbon_get_theme_option( 'sip_max_size') ) ?: 50000000;

$sip_clamav = carbon_get_theme_option( 'sip_clamav' );
if ( $sip_clamav ) {
	$clam = new Network( esc_attr( carbon_get_theme_option( 'sip_clamav_host' ) ), (int) esc_attr( carbon_get_theme_option( 'sip_clamav_port' ) ) );
}

// in order to prevent a PHP-Error we ping ClamAV in a try/catch.
$clam_rdy = false;
try {
	$clam_rdy = $clam->ping();
} catch( Exception $e ) {
	error_log( $e->getMessage() );
}

$sip_folder       = esc_attr( carbon_get_theme_option( 'sip_upload_path' ) ) . sanitize_text_field( $_POST['sipUserID'] ) . '/' . sanitize_text_field( $_POST['sipFolder'] ) . '/';
$upload_folder    = $sip_folder . 'content/';
$upload_dir       = '';
$upload_dir_array = explode( '/', $upload_folder );
foreach ( $upload_dir_array as $path ) {
	$upload_dir = $upload_dir . $path . '/';
	if ( ! file_exists( $upload_dir ) ) {
		mkdir( $upload_dir, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS );
	}
}

// the names.csv contains all uploaded filenames.
$fp = fopen($sip_folder . 'names.csv', 'a');

if ( isset( $_POST['fullPath'] ) ) {
	$full_path       = dirname( sanitize_text_field( $_POST['fullPath'] ) );
	$full_path_array = explode( '/', $full_path );
	foreach ( $full_path_array as $path ) {
		$sanitize_path = sanitize_file_name($path);
		fputcsv($fp, array($sanitize_path,$path));
		$upload_dir = $upload_dir . $sanitize_path . '/';
		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS );
		}
	}
}

$sanitize_filename = strtolower(sanitize_file_name(basename( $_FILES['file']['name'])));
fputcsv($fp, array($sanitize_filename,basename( $_FILES['file']['name'])));

// todo: create checksum for each uploaded file and save it as post-meta!
$upload_file = $upload_dir . $sanitize_filename;
if ( move_uploaded_file( $_FILES['file']['tmp_name'], $upload_file ) ) {
	$file_type = wp_check_filetype($upload_file);
	$sip_size  = 0;
	if ( isset( $_COOKIE['sip_file_size'] ) ) {
		$sip_size = sanitize_text_field( $_COOKIE['sip_file_size'] );
	}
	if ( $sip_size > $sip_max_size ) {
		unlink($upload_file);
		$json_data['sip_full'] = sanitize_file_name( basename( $_FILES['file']['name'] ) );
	} else {
		$file_size = filesize($upload_file);
		$sip_size = $sip_size + $file_size;
		$_COOKIE['sip_file_size'] = $sip_size;
		setcookie("sip_file_size", $sip_size, 0, '/');
		$json_data['sip_size'] = $sip_size;
	}
	if ( ! in_array( $file_type['type'], $supported_mime_types ) ) {
		unlink($upload_file);
		$json_data['not_supported'] = sanitize_file_name( basename( $_FILES['file']['name'] ) );
	} elseif ( $sip_clamav && $clam_rdy ) {
		if ( ! $clam->fileScan( $upload_file ) ) {
			unlink($upload_file);
			$json_data['infected'] = sanitize_file_name( basename( $_FILES['file']['name'] ) );
		}
	}
}

fclose($fp);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($json_data);
