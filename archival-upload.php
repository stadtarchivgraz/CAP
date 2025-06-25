<?php
$json_data = array();

require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
require_once( 'vendor/autoload.php' );
use Appwrite\ClamAV\Network;

$supported_mime_types = explode("\r\n", carbon_get_theme_option( 'sip_mime_types') );
$sip_max_size = (carbon_get_theme_option( 'sip_max_size') )?:50000000;

$sip_clamav = carbon_get_theme_option( 'sip_clamav' );
if($sip_clamav) {
	$clam = new Network( carbon_get_theme_option( 'sip_clamav_host' ), carbon_get_theme_option( 'sip_clamav_port' ) );
}

$sip_folder    = carbon_get_theme_option( 'sip_upload_path' ) . $_POST['sipUserID'] . '/' . $_POST['sipFolder'] . '/';
$upload_folder    = $sip_folder . 'content/';
$upload_dir       = '';
$upload_dir_array = explode( '/', $upload_folder );
foreach ( $upload_dir_array as $path ) {
	$upload_dir = $upload_dir . $path . '/';
	if ( ! file_exists( $upload_dir ) ) {
		mkdir( $upload_dir );
	}
}

$fp = fopen($sip_folder . 'names.csv', 'a');

if ( isset( $_POST['fullPath'] ) ) {
	$full_path       = dirname( $_POST['fullPath'] );
	$full_path_array = explode( '/', $full_path );
	foreach ( $full_path_array as $path ) {
		$sanitize_path = sanitize_file_name($path);
		fputcsv($fp, array($sanitize_path,$path));
		$upload_dir = $upload_dir . $sanitize_path . '/';
		if ( ! file_exists( $upload_dir ) ) {
			mkdir( $upload_dir );
		}
	}
}

$sanitize_filename = strtolower(sanitize_file_name(basename( $_FILES['file']['name'])));
fputcsv($fp, array($sanitize_filename,basename( $_FILES['file']['name'])));

$upload_file = $upload_dir . $sanitize_filename;
if ( move_uploaded_file( $_FILES['file']['tmp_name'], $upload_file ) ) {
	$file_type = wp_check_filetype($upload_file);
	if (isset($_COOKIE['sip_file_size'])) {
		$sip_size = $_COOKIE['sip_file_size'];
	} else $sip_size = 0;
	if($sip_size > $sip_max_size) {
		unlink($upload_file);
		$json_data['sip_full'] = basename( $_FILES['file']['name'] );
	} else {
		$file_size = filesize($upload_file);
		$sip_size = $sip_size + $file_size;
		$_COOKIE['sip_file_size'] = $sip_size;
		setcookie("sip_file_size", $sip_size, 0, '/');
		$json_data['sip_size'] = $sip_size;
	}
	if(!in_array($file_type['type'], $supported_mime_types)) {
		unlink($upload_file);
		$json_data['not_supported'] = basename( $_FILES['file']['name'] );
	} elseif ( $sip_clamav && $clam->ping() ) {
		if ( !$clam->fileScan( $upload_file ) ) {
			unlink($upload_file);
			$json_data['infected'] = basename( $_FILES['file']['name'] );
		}
	}
}

fclose($fp);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($json_data);