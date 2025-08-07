<?php
/**
 * Manages the removal of the files uploaded with Dropzone.
 * @todo solve this with our form-validation class!
 */

$json_data = array();

// todo: refactor!
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

$upload_dir        = carbon_get_theme_option( 'sip_upload_path' ) . sanitize_text_field( $_POST['sipUserID'] ) . '/' . sanitize_text_field( $_POST['sipFolder'] ) . '/content/';
$upload_file       = $upload_dir . sanitize_text_field( $_POST['deletePath'] );
$filename          = basename($upload_file);
$sanitize_filename = strtolower(sanitize_file_name($filename));
$upload_file       = str_replace($filename, $sanitize_filename, $upload_file);
$json_data['upload_file'] = $upload_file;

if( file_exists( $upload_file ) ) {
	$file_size = filesize($upload_file);
	if (isset($_COOKIE['sip_file_size'])) {
		$sip_size = sanitize_text_field( $_COOKIE['sip_file_size'] );
		$sip_size =  $sip_size - $file_size;
		$_COOKIE['sip_file_size'] = $sip_size;
		setcookie("sip_file_size", $sip_size, 0, '/');
		$json_data['sip_size'] = $sip_size;
	}
	unlink($upload_file);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($json_data);
