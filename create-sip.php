<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

global $wpdb;
if($archival_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder' AND meta_value = %s", $_GET['sipFolder']))) {

	$archive = get_the_terms($archival_id, 'archive');

	$sip_institution = strtoupper( carbon_get_term_meta( $archive[0]->term_id, 'sip_institution' ) );
	$sip_referenz    = carbon_get_term_meta( $archive[0]->term_id,'sip_referenz' );

	$archival    = get_post( $archival_id );
	$archival_user = get_user_by('id', $archival->post_author);

	$sip_referenz = ( $sip_referenz ) ? strtoupper( $sip_referenz ) : strtoupper( $archival_user->user_login);

	$sip_folder = carbon_get_theme_option( 'sip_upload_path' ) . $archival->post_author . '/' . $_GET['sipFolder'] . '/';
	$content_dir = $sip_folder . 'content/';
	$header_dir  = $sip_folder . 'header/';


	if ( ! file_exists( $header_dir ) ) {
		mkdir( $header_dir );
	}

	require_once( 'create-xml.php' );

	$zip      = new ZipArchive;
	$tmp_file = $content_dir . '/sip.zip';
	if ( $zip->open( $tmp_file, ZipArchive::CREATE ) ) {
		$files = new RecursiveTreeIterator(new RecursiveDirectoryIterator($content_dir, RecursiveDirectoryIterator::SKIP_DOTS));

		foreach ( $files as $path ) {
			$path = trim($path);
			$path_clean = substr($path, strpos($path, '/'));

			if ( is_dir( $path_clean ) === true ) {
				$zip->addEmptyDir( str_replace( $content_dir, 'content/', $path_clean . '/' ) );
			} else if ( is_file( $path_clean ) === true ) {
				$zip->addFromString( str_replace( $content_dir, 'content/', $path_clean ), file_get_contents( $path_clean ) );
			}
		}

		$zip->addFile( $header_dir . 'metadata.xml', 'header/metadata.xml' );

		$zip->close();
		header( 'Content-disposition: attachment; filename=SIP_' . date( 'Ymd-Hi' ) . '_' . $sip_institution . '_' . $sip_referenz . '.zip' );
		header( 'Content-type: application/zip' );
		readfile( $tmp_file );
		unlink( $tmp_file );
	}
}