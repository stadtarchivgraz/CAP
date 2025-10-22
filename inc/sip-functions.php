<?php
/**
 * Includes a template from the plugin folder.
 * @param string $form the name/path to the form to include.
 * @return false|string
 */
function starg_get_sip_form( string $form ) {
	if ( ! $form || ! file_exists( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/forms/' . $form ) ) {
		$logging = apply_filters( 'starg/logging', null );
		if ( $logging instanceof Starg_Logging ) {
			// translators: %s: Name of the required form template.
			$logging->create_log_entry( sprintf( esc_attr__( 'The form %s does not exist', 'sip' ), esc_attr( $form ) ) );
		}
		return false;
	}

	ob_start();
	require STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/forms/' . esc_attr( $form );
	return ob_get_clean();
}

/**
 * Tries to create a thumbnail of an uploaded image.
 * @param string $file_path
 * @param string $thumbnail_folder
 * @return void|array
 */
function starg_create_thumbnail($file_path, $thumbnail_folder) {
	$logging = apply_filters( 'starg/logging', null );
	if ( ! extension_loaded( 'imagick' ) ) {
		if ( $logging instanceof Starg_Logging ) {
			$logging->create_log_entry( esc_html__( 'Imagick-Extension not loaded.', 'sip' ) );
		}
		return;
	}

	if ( ! file_exists( $thumbnail_folder ) ) {
		mkdir( $thumbnail_folder, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS, true );
	}

	$new_thumbnail = array();
	if( wp_get_image_mime($file_path) === 'image/tiff' ) {
		$new_file_name = $thumbnail_folder.str_replace(array('.tiff', '.tif'), '.jpg', basename($file_path));
		$image = new Imagick($file_path);
		$image->setImageFormat('jpg');
		$image->writeImage($new_file_name);
		$image = wp_get_image_editor( $new_file_name );

		if ( is_wp_error( $image ) ) {
			if ( $logging instanceof Starg_Logging ) {
				// translators: %s: Name of the uploaded file.
				$logging->create_log_entry( sprintf( esc_html__( 'Problems creating thumbnail for file %s.', 'sip' ) ), $file_path );
			}
			return;
		}

		$image->resize( 800, 800 );
		$filename = $image->generate_filename( 'full', $thumbnail_folder );
		$image->save( $filename );
		$image->resize( 300, 300, true );
		$new_thumbnail = $image->save( $new_file_name );
	} else {
		$image = wp_get_image_editor( $file_path );

		if ( is_wp_error( $image ) ) {
			if ( $logging instanceof Starg_Logging ) {
				// translators: %s: Name of the uploaded file.
				$logging->create_log_entry( sprintf( esc_html__( 'Problems creating thumbnail for file %s.', 'sip' ) ), $file_path );
			}
			return;
		}

		$image->resize( 300, 300, true );
		$new_thumbnail = $image->save( $thumbnail_folder.basename( $file_path ) );
	}

	return $new_thumbnail;
}

/**
 * Tries to create a thumbnail from an uploaded pdf.
 * Note that ImageMagick disabled the option to process PDFs on default since version 7.
 * One might need to update the /policy.xml file for ImageMagick from <policy domain="coder" rights="none" pattern="PDF" /> to <policy domain="coder" rights="read|write" pattern="PDF" />
 *
 * @param string $file
 * @param string $thumbnail_folder
 * @return bool
 */
function starg_create_pdf_thumbnail( $file, $thumbnail_folder ) : bool {
	if ( ! $file || ! $thumbnail_folder ) { return false; }

	if ( ! file_exists( $thumbnail_folder ) ) {
		mkdir( $thumbnail_folder, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS, true );
	}

	try {
		$pdf = new Spatie\PdfToImage\Pdf( $file );
		$file_written = $pdf->saveImage( $thumbnail_folder . basename( $file ) . '.jpg' );
		return ( $file_written ) ? true : false;
	} catch ( Exception $e ) {
		$logging = apply_filters( 'starg/logging', null );
		if ( $logging instanceof Starg_Logging ) {
			// translators: %s: Error message.
			$logging->create_log_entry( sprintf( esc_attr__( 'Error while creating a thumbnail for a PDF. See: %s', 'sip' ), $e->getMessage() ) );
		}
		return false;
	}
}


/**
 * Converts the filesize.
 */
function starg_format_bytes($size, $precision = 2) {
	$base = log($size, 1024);
	$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

	return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}



/**
 * Remove an existing SIP from the uploads-Folder.
 * @param string $dir The directory where the SIP is stored.
 * @return bool
 */
function starg_remove_SIP(string $dir) : bool {
	$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
	$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

	try {
		$dir_deleted  = array();
		$file_deleted = array();
		foreach ($files as $file) {
			if ($file->isDir()) {
				$dir_deleted[ $file->getBasename() ] = rmdir($file->getPathname());
			} else {
				$file_deleted[ $file->getBasename() ] = unlink($file->getPathname());
			}
		}
		$dir_deleted[] = rmdir($dir);
		if ( in_array( false, $dir_deleted ) || in_array( false, $file_deleted ) ) {
			return false;
		}
		return true;
	} catch ( UnexpectedValueException $e ) {
		// translators: %1$s: Directory of the archival record in question. %2$s: Error message.
		$error_log_msg = sprintf( esc_html__( 'sip %1$s was not removed! Error message: %2$s', 'sip' ), $dir, $e->getMessage() );
		$logging       = apply_filters( 'starg/logging', null );
		if ( $logging instanceof Starg_Logging ) {
			$logging->create_log_entry( $error_log_msg, Log_Severity::Error );
		}
		return false;
	}
}

/**
 * Generates a list of available languages.
 */
function starg_get_enabled_languages() {
	if (function_exists('pll_languages_list')) {
		$sip_archive_languages = wp_list_pluck(pll_languages_list(array('fields' => array())), 'locale');
	} else {
		$sip_archive_languages = get_available_languages(plugin_dir_path( __DIR__ ) . 'languages/');
		array_unshift($sip_archive_languages, 'en_US');
	}

	return $sip_archive_languages;
}

/**
 * Converts bytes to a more human readable format.
 */
function starg_human_filesize($bytes, $decimals = 2) {
	$sz = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1000, $factor)) . $sz[$factor];
}

/**
 * Get the creation date of the oldest file in a specific folder.
 * @param string $folder_path
 * @param $timestamp
 * @return int|float|false
 */
function starg_get_folder_creation_days_ago( string $folder_path, $timestamp = false ) {
	$files = glob( $folder_path . '/*' );
	if ( empty( $files ) ) {
		return false; // Folder is empty, no date available.
	}

	$current_time = time();
	$oldest_file  = min( array_map( 'filemtime', $files ) );
	if ( ! $timestamp ) {
		return floor(($current_time - $oldest_file) / (60 * 60 * 24));
	} else {
		return $oldest_file;
	}
	//return date('Y-m-d H:i:s', $oldest_file);
}

/**
 * Return the permalink for the edit page for SIP archival records.
 * @return string
 */
function starg_get_the_edit_archival_page_url() : string {
	$pages = get_pages(array(
		'meta_key'     => '_wp_page_template',
		'meta_value'   => 'sip-upload.php',
		'hierarchical' => 0,
		'number'       => 1,
	));
	if ( ! $pages || ! get_the_permalink( $pages[0] ) ) {
		$logging       = apply_filters( 'starg/logging', null );
		if ( $logging instanceof Starg_Logging ) {
			$logging->create_log_entry( esc_html__( 'No page associated with page-template sip-upload found.', 'sip' ) );
		}
		return get_home_url();
	}

	// We need to add query_args to the url to make sure the user is allowed to view the page!
	// After the upload of an archival record one get redirected to the same page where they can add additional information to it.
	// Problem is, the post for this archival record might not exist!
	return add_query_arg( array( 'starg' => '1', 'starg_amd' => wp_create_nonce( 'starg_add_archival_meta_data_nonce_action' ), ), esc_url( get_the_permalink( $pages[0] ) ) );
}

/**
 * Return the permalink for the edit page for SIP archival records.
 * @param int|string $archival_id
 * @return string
 */
function starg_get_the_archival_page_template_url( $archival_id = 0 ) : string {
	$pages = get_pages(array(
		'meta_key'     => '_wp_page_template',
		'meta_value'   => 'sip-archival.php',
		'hierarchical' => 0,
		'number'       => 1,
	));
	if ( ! $pages || ! get_the_permalink( $pages[0] ) ) {
		$logging = apply_filters( 'starg/logging', null );
		if ( $logging instanceof Starg_Logging ) {
			$logging->create_log_entry( esc_html__( 'No page associated with page-template sip-archival found.', 'sip' ) );
		}
		return get_home_url();
	}

	$url = get_the_permalink( $pages[0] );
	if ( $archival_id ) {
		$url = add_query_arg( array( 'archival_name' => $archival_id, ), esc_url( $url ) );
	}
	return $url;
}

/**
 * Return the permalink for the users profile page.
 * @param int|string $archival_id
 * @return string
 */
function starg_get_the_profile_page_template_url() : string {
	$pages = get_pages(array(
		'meta_key'     => '_wp_page_template',
		'meta_value'   => 'sip-profile.php',
		'hierarchical' => 0,
		'number'       => 1,
	));
	if ( ! $pages || ! get_the_permalink( $pages[0] ) ) {
		$logging = apply_filters( 'starg/logging', null );
		if ( $logging instanceof Starg_Logging ) {
			$logging->create_log_entry( esc_html__( 'No page associated with page-template sip-profile found.', 'sip' ) );
		}
		return get_home_url();
	}

	return esc_url( get_the_permalink( $pages[0] ) );
}

/**
 * Creates a notification element for the frontend.
 * @param string $message the message to display.
 * @param string $notification_style [Optional] the bulma css class used to create additional styling for the notification. choose something like "is-error", "is-warning".
 * @param bool $display_close_button [Optional] weather a button should be displayed, with which the user can dismiss the notification.
 * @return void|string
 */
function starg_get_notification_message( string $message, string $notification_style = 'is-success is-light', $display_close_button = false ) {
	if ( ! $message ) { return; }
	ob_start();
	?>
	<div class="sip">
		<div class="container my-4">
			<div class="notification <?php echo $notification_style; ?>">
				<?php if ( $display_close_button ) : ?>
					<button class="delete"></button>
				<?php endif; ?>
				<?php echo wpautop( wp_kses_post( $message ) ); ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Attempt to generate and store latitude and longitude coordinates for the address associated with an archival record.
 * We therefore ask either google (if an api key exists) or openstreetmap.
 * @param int $archival_post_id
 * @return array
 */
function starg_get_map_coordinates_by_post_id( int $archival_post_id ) : array {
	if ( ! $archival_post_id || ! get_post_meta( $archival_post_id, '_archival_address', true ) ) {
		return array();
	}

	$markers['title']         = get_the_title( $archival_post_id );
	$markers['permalink']     = get_the_permalink($archival_post_id);
	$markers['place_address'] = esc_attr(get_post_meta($archival_post_id, '_archival_address', true));
	$markers['lat']           = esc_attr(get_post_meta($archival_post_id, '_archival_lat', true));
	$markers['lng']           = esc_attr(get_post_meta($archival_post_id, '_archival_lng', true));

	if ( $markers['lat'] || $markers['lng'] ) {
		return $markers;
	}

	$google_api_key = esc_attr( carbon_get_theme_option('sip_map_default_google_api_key') );
	if ( ! empty( $google_api_key ) ) {
		$json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode( str_replace(' ', '+', $markers['place_address']) ) . '&key=' . $google_api_key);
		$obj  = json_decode($json);
		if ( ! $obj->results[0]->geometry->location->lat ) {
			return $markers;
		}

		// save the lat and long values to the post meta data.
		update_post_meta($archival_post_id, '_archival_lat', sanitize_text_field($obj->results[0]->geometry->location->lat));
		update_post_meta($archival_post_id, '_archival_lng', sanitize_text_field($obj->results[0]->geometry->location->lng));

		$markers['lat'] = sanitize_text_field($obj->results[0]->geometry->location->lat);
		$markers['lng'] = sanitize_text_field($obj->results[0]->geometry->location->lng);

		return $markers;
	}

	// for the openstreetmap api to work properly we need to provide an user-agent and/or an email address.
	$options = array(
		"http" => array(
			"header" => "User-Agent: " . STARG_SIP_PLUGIN_NAME . '/' . STARG_SIP_PLUGIN_VERSION . "\r\n",
		),
	);
	$context = stream_context_create($options);
	$json    = file_get_contents( 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode( str_replace(' ', '+', $markers['place_address']) ), false, $context );
	// possible values from the request are: "place_id","licence","osm_type","osm_id","lat","lon","class","type","place_rank","importance","addresstype","name","display_name","boundingbox"
	if ($obj = json_decode($json)) {
		// save the lat and long values to the post meta data.
		update_post_meta($archival_post_id, '_archival_lat', sanitize_text_field($obj[0]->lat));
		update_post_meta($archival_post_id, '_archival_lng', sanitize_text_field($obj[0]->lon));

		$markers['lat'] = sanitize_text_field($obj[0]->lat);
		$markers['lng'] = sanitize_text_field($obj[0]->lon);
	}

	return $markers;
}

/**
 * Retrieve the path to the uploaded archival records.
 * @return string
 */
function starg_get_archival_upload_path() : string {
	$upload_path = esc_attr( carbon_get_theme_option( 'sip_upload_path' ) );
	if ( empty( $upload_path ) ) {
		$upload_path = trailingslashit( wp_get_upload_dir()['basedir'] ) . 'archival';
	}

	return trailingslashit( $upload_path );
}
