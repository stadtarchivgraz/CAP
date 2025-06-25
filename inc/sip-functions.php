<?php
function get_sip_form($form) {
	ob_start();
	require dirname( __DIR__ ) . '/' . $form;
	return ob_get_clean();
}

function create_thumbnail($file, $thumbnail_folder): void {
	if(!file_exists($thumbnail_folder)) {
		mkdir( $thumbnail_folder );
	}
	if(wp_get_image_mime($file) === 'image/tiff') {
		$new_file_name = $thumbnail_folder.str_replace(array('.tiff', '.tif'), '.jpg', basename($file));
		$image = new Imagick($file);
		$image->setImageFormat('jpg');
		$image->writeImage($new_file_name);
		$image = wp_get_image_editor( $new_file_name );
		if ( ! is_wp_error( $image ) ) {
			$image->resize( 800, 800 );
			$filename = $image->generate_filename( 'full', $thumbnail_folder );
			$image->save( $filename );
			$image->resize( 300, 300, true );
			$image->save( $new_file_name );
		}
	} else {
		$image = wp_get_image_editor( $file );
		if ( ! is_wp_error( $image ) ) {
			$image->resize( 300, 300, true );
			$image->save( $thumbnail_folder.basename($file) );
		}
	}
}

function create_pdf_thumbnail($file, $thumbnail_folder) {
	if(!file_exists($thumbnail_folder)) {
		mkdir( $thumbnail_folder );
	}
	try {
		$pdf = new Spatie\PdfToImage\Pdf( $file );
		$pdf->saveImage( $thumbnail_folder . basename( $file ) . '.jpg' );
		return true;
	} catch ( ImagickException $e ) {
		return false;
	}
}

function get_archive_tags($archive_id) {
	global $wpdb;
	$all_archive_archivals = $wpdb->get_results($wpdb->prepare("SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $archive_id));
	$all_archival_ids = implode(',', wp_list_pluck($all_archive_archivals, 'object_id'));
	return $wpdb->get_results("SELECT a.term_taxonomy_id, count(a.term_taxonomy_id) as count FROM $wpdb->term_relationships a LEFT JOIN $wpdb->term_taxonomy b ON a.term_taxonomy_id = b.term_taxonomy_id WHERE taxonomy = 'archival_tag' AND a.object_id IN ($all_archival_ids) GROUP BY a.term_taxonomy_id");
}

function get_archival_tag_name($archival_tag_id) {
	global $wpdb;
	return $wpdb->get_var($wpdb->prepare("SELECT name FROM $wpdb->terms WHERE term_id = %d", $archival_tag_id));
}

if(!function_exists('formatBytes')) :
	function formatBytes($size, $precision = 2) {
		$base = log($size, 1024);
		$suffixes = array('', 'K', 'M', 'G', 'T');

		return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	}
endif;

function removeSIP(string $dir): void {
	$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
	$files = new RecursiveIteratorIterator($it,
		RecursiveIteratorIterator::CHILD_FIRST);
	foreach($files as $file) {
		if ($file->isDir()){
			rmdir($file->getPathname());
		} else {
			unlink($file->getPathname());
		}
	}
	rmdir($dir);
}