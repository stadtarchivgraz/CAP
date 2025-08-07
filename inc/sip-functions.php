<?php
/**
 * Includes a template from the plugin folder.
 * @param string $form the name/path to the form to include.
 * @return false|string
 */
function starg_get_sip_form( string $form ) {
	if ( ! $form || ! file_exists( STARG_SIP_PLUGIN_BASE_DIR . $form ) ) { return false; }

	ob_start();
	require STARG_SIP_PLUGIN_BASE_DIR . $form;
	return ob_get_clean();
}

function starg_create_thumbnail($file, $thumbnail_folder): void {
	if ( ! extension_loaded( 'imagick' ) ) {
		error_log( 'Imagick-Extension not loaded.' );
		return;
	}

	if ( ! file_exists( $thumbnail_folder ) ) {
		mkdir( $thumbnail_folder );
	}
	if( wp_get_image_mime($file) === 'image/tiff' ) {
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

function starg_create_pdf_thumbnail($file, $thumbnail_folder) {
	if ( ! file_exists( $thumbnail_folder ) ) {
		mkdir( $thumbnail_folder );
	}
	try {
		$pdf = new Spatie\PdfToImage\Pdf( $file );
		$file_written = $pdf->saveImage( $thumbnail_folder . basename( $file ) . '.jpg' );
		return ( $file_written ) ? true : false;
	} catch ( Exception $e ) {
		error_log( 'Error while creating PDF thumbnail: ' . $e );
		return false;
	}
}

/**
 * 
 * @return object|null|false
 */
function starg_get_archive_tags( int $archive_id ) {
	if ( ! $archive_id ) { return false; }

	global $wpdb;
	$all_archivals_sql     = "SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d";
	$all_archive_archivals = $wpdb->get_results( $wpdb->prepare( $all_archivals_sql, $archive_id ) );
	if ( ! $all_archive_archivals ) { return false; }

	$all_archival_ids = implode(',', wp_list_pluck($all_archive_archivals, 'object_id'));
	$sql = "SELECT a.term_taxonomy_id, count(a.term_taxonomy_id) as count
		FROM $wpdb->term_relationships a
			LEFT JOIN $wpdb->term_taxonomy b ON a.term_taxonomy_id = b.term_taxonomy_id
		WHERE taxonomy = 'archival_tag'
			AND a.object_id IN (%s)
		GROUP BY a.term_taxonomy_id";

	return $wpdb->get_results( $wpdb->prepare( $sql, $all_archival_ids ) );
}

/**
 * Retrieve the name for an existing archival tag.
 * @param int $archival_tag_id
 * @return string
 */
function starg_get_archival_tag_name( int $archival_tag_id ) {
	global $wpdb;
	$result = $wpdb->get_var($wpdb->prepare("SELECT name FROM $wpdb->terms WHERE term_id = %d", $archival_tag_id));
	return esc_attr( $result );
}

function starg_format_bytes($size, $precision = 2) {
	$base = log($size, 1024);
	$suffixes = array('', 'K', 'M', 'G', 'T');

	return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

/**
 * Retrieves the post count of all archival posts from all users for an specific upload purpose.
 *
 * @param string $upload_purpose_option
 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
 *                                    Default: false = includes posts with all post_status values.
 * @return int The number of posts found. 0 if no post was found
 */
function starg_get_upload_purpose_post_count( string $upload_purpose_option, bool $only_published_posts = false ) : int {
	if ( ! $upload_purpose_option ) { return 0; }
	$post_status_filter = '';
	if ( $only_published_posts ) {
		$post_status_filter = "AND post_status = 'publish'";
	}

	global $wpdb;
	$upload_purpose_sql = "SELECT count(post_id)
		FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts ON post_id = ID
		WHERE meta_key = '_archival_upload_purpose'
			AND meta_value = %s
			$post_status_filter";
	$result = $wpdb->get_var( $wpdb->prepare( $upload_purpose_sql, $upload_purpose_option ) );

	return (int) $result ?: 0;
}

/**
 * Retrieves the post count of the users archival posts for an specific upload purpose.
 *
 * @param int $user_archive
 * @param string $upload_purpose_option
 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
 *                                    Default: false = includes posts with all post_status values.
 * @return int The number of posts found. 0 if no post was found
 */
function starg_get_upload_purpose_post_count_for_user( int $user_archive, string $upload_purpose_option, bool $only_published_posts = false ) : int {
	if ( ! $user_archive || ! $upload_purpose_option ) { return 0; }
	$post_status_filter = '';
	if ( $only_published_posts ) {
		$post_status_filter = "AND post_status = 'publish'";
	}

	global $wpdb;
	$sql = "SELECT count(post_id)
		FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts ON post_id = ID
			LEFT JOIN $wpdb->term_relationships ON object_id = ID
		WHERE term_taxonomy_id = %d
			AND meta_key = '_archival_upload_purpose'
			AND meta_value = %s
			$post_status_filter";
	$result = $wpdb->get_var( $wpdb->prepare( $sql, $user_archive, $upload_purpose_option ) );

	return (int) $result ?: 0;
}

/**
 * todo: neeeds docblock
 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
 *                                    Default: false = includes posts with all post_status values.
 * @return array|object
 */
function starg_get_upload_year_post_count( bool $only_published_posts = false ) : array|object {
	$post_status_filter = '';
	if ( $only_published_posts ) {
		$post_status_filter = "AND post_status = 'publish'";
	}

	global $wpdb;
	$sql = "SELECT count(post_id) as sip_count, DATE_FORMAT(meta_value, '%Y') as sip_date
		FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts ON post_id = ID
		WHERE meta_key = '_archival_from'
			$post_status_filter
		GROUP BY sip_date
		ORDER BY sip_date DESC";

	$result = $wpdb->get_results( $sql );
	return $result ?: array();
}

/**
 * todo: neeeds docblock
 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
 *                                    Default: false = includes posts with all post_status values.
 * @return array|object
 */
function starg_get_upload_year_post_count_for_user( int $user_archive, bool $only_published_posts = false ) : array|object {
	if ( ! $user_archive ) { return array(); }
	$post_status_filter = '';
	if ( $only_published_posts ) {
		$post_status_filter = "AND post_status = 'publish'";
	}

	global $wpdb;
	$sql = "SELECT count(post_id) as sip_count, DATE_FORMAT(meta_value, '%Y') as sip_date
		FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts ON post_id = ID
			LEFT JOIN $wpdb->term_relationships ON object_id = ID
		WHERE term_taxonomy_id = %d
			AND meta_key = '_archival_from'
			$post_status_filter
		GROUP BY sip_date
		ORDER BY sip_date DESC";

	$result = $wpdb->get_results( $wpdb->prepare( $sql, $user_archive ) );
	return $result ?: array();
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
		error_log( 'sip ' . $dir . ' was not removed! check: ' . $e->getMessage() );
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
		error_log( 'No Page-Template found: sip-upload.php' );
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
		error_log( 'No Page-Template found: sip-archival.php' );
		return get_home_url();
	}

	$url = get_the_permalink( $pages[0] );
	if ( $archival_id ) {
		$url = add_query_arg( array( 'archival_name' => $archival_id, ), $url );
	}
	return esc_url( $url );
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
		error_log( 'No Page-Template found: sip-profile.php' );
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
 * Receive all archival sip folders from a user by their id.
 * @param int $user_id
 * @return array Array with all sip folders or empty array on failure.
 */
function starg_get_archival_sip_folders_by_user_id( int $user_id = 0 ) : array {
	global $wpdb;
	$archival_sip_folders_sql = "SELECT meta_value
		FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts ON post_id = ID
		WHERE meta_key = '_archival_sip_folder'
			AND post_author = %d";
	$archival_sip_folders = $wpdb->get_results( $wpdb->prepare( $archival_sip_folders_sql, $user_id ) );
	if ( ! $archival_sip_folders ) {
		return array();
	}
	return wp_list_pluck( $archival_sip_folders, 'meta_value' );
}

/**
 * Retrieve the post-id of an archival record based on a sip_folder.
 * @param string $sip_folder
 * @return int|false
 */
function starg_get_archival_id_by_sip_folder( string $sip_folder ) {
	global $wpdb;
	$archival_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder' AND meta_value = %s", $sip_folder ) );
	return (int) $archival_id ?? false;
}
