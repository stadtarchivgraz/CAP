<?php

class Render_Sip_Content_Folder {
	public static array $exif_dates = array();
	public static bool $is_pdf = false;

	/**
	 * Display the content of the users sip folder.
	 * We try to display thumbnails for images and PDFs and icons based on the filetype for other resources.
	 * The thumbnails are wrapped in a link to open them in a gallery.
	 * We also use this view to render the content in the PDF.
	 *
	 * @param bool $is_pdf
	 *
	 * @return void
	 */
	public static function render_sip_folder_content( bool $is_pdf = false ) : void {
		$logging   = apply_filters( 'starg/logging', null );
		$sip_id    = false;
		$file_data = array();
		$author_id = get_current_user_id();
		self::$is_pdf = $is_pdf;

		if (isset($_GET['sipFolder']) && $_GET['sipFolder']) {
			// $author_id = get_current_user_id();
			$sip_id      = sanitize_text_field($_GET['sipFolder']);
			$archival_id = DB_Query_Helper::starg_get_archival_id_by_sip_folder( $sip_id );
			if ( $archival_id ) {
				$author_id = (int) get_post_field( 'post_author', $archival_id );
			}
		} elseif ( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG === get_post_type() ) {
			$archival_id = get_the_ID();
			$sip_id      = esc_attr(get_post_meta($archival_id, '_archival_sip_folder', true));
			$author_id   = (int) get_post_field( 'post_author', $archival_id );
		} elseif ( get_query_var( 'archival_name' ) ) {
			$archival_id = (int) get_query_var( 'archival_name' );
			$sip_id      = esc_attr(get_post_meta($archival_id, '_archival_sip_folder', true));
			$author_id   = (int) get_post_field( 'post_author', $archival_id );
		}

		if ( ! $sip_id || ! $author_id ) {
			echo starg_get_notification_message( esc_html__( 'No files found.', 'sip' ), 'is-warning is-light' );
			if ( $logging instanceof Starg_Logging ) {
				// translators: %1$s: Post-ID.  %2$s ID/Name of the folder where the sip is stored. %3$d: User-ID.
				$logging->create_log_entry( sprintf( esc_html__( 'No SIP data found for post %1$s for SIP folder %2$s requested by %3$d.', 'sip' ), $archival_id, $sip_id, $author_id ) );
			}
			return;
		}

		// create the path to the users files.
		$sip_folder       = starg_get_archival_upload_path() . $author_id . '/' . $sip_id . '/';
		$upload_folder    = $sip_folder . 'content/';
		$thumbnail_folder = $sip_folder . 'thumb/';

		// if we have more files in this single sip, we need to display each item.
		try {
			$it = new RecursiveTreeIterator(new RecursiveDirectoryIterator($upload_folder, RecursiveDirectoryIterator::SKIP_DOTS));
		} catch ( Exception $exception ) {
			// translators: %1$s: ID/Name of the folder where the sip is stored. %2$d: User-ID.
			echo starg_get_notification_message( sprintf( esc_html__( 'The folder "%1$s" for user "%2$d" is empty.', 'sip' ), $sip_id, $author_id ), 'is-warning is-light' );
			if ( $logging instanceof Starg_Logging ) {
				$logging->create_log_entry( $exception->getMessage() );
			}
			return;
		}


		foreach ($it as $path) {
			$path       = trim($path);
			$path_clean = substr($path, strpos($path, '/'));

			// todo: something wrong with the <ol>?
			if (is_dir($path_clean)) {
				echo '<li class="subfolder">' . basename($path) . '</li><ol>';
				continue;
			}

			[$file_data_entry, $file_type]    = Render_Sip_Content_Folder::get_file_metadata($path_clean);
			$file_data[basename($path_clean)] = $file_data_entry;

			$file_url   = get_bloginfo('url') . substr($path, strrpos($path, '/wp-content'));
			$thumb_link = '';
			$image_size = false;

			// create view based on filetype
			if (strrpos($file_type['type'], 'image') === 0) {
				$image_size = getimagesize($path_clean);
			}
			$thumb_link = Render_Sip_Content_Folder::get_file_link($file_type, $file_url, $path_clean, $thumbnail_folder, $image_size);

			// render data
			if ($thumb_link) {
				echo ( ! $is_pdf ) ? '<li>' : '<table style="width: 100%"><tr><td style="width:40%;">';
				echo $thumb_link;
				echo ( ! $is_pdf ) ? '<p>' : '</td><td style="width: 60%">';
				echo Render_Sip_Content_Folder::get_file_info($file_data_entry, $path_clean);
				echo ( ! $is_pdf ) ? '</p></li>' : '</td></tr></table><br>';
			}
		}
	}

	/**
	 * Create a link to the file with thumbnail.
	 *
	 * @param array $file_type
	 * @param string $file_url         URL of the file.
	 * @param string $path_to_file     Absolute path to the file on the hard drive.
	 * @param string $thumbnail_folder [Optional]
	 * @param mixed $image_size        [Optional]
	 *
	 * @return string
	 */
	private static function get_file_link(array $file_type, string $file_url, string $path_to_file, string $thumbnail_folder = '', $image_size = array() ) : string {
		$file = $file_url;
		// We need to use the absolute path to the images if we're rendering a PDF.
		// The PDF-Library we're using (spipu/html2pdf) transforms uppercase letters in URLs into lowercase.
		// This is fine for windows servers as their filesystem is not case sensitive. But on linux image.png is not the same as Image.png!
		if ( self::$is_pdf ) {
			$file = $path_to_file;
		}

		$ext       = strtolower($file_type['ext']);
		$type      = explode('/', $file_type['type'])[0];
		$file_info = pathinfo( $file );

		$thumbnail_dir_url  = dirname($file, 2);
		$thumbnail_filename = ( 'pdf' === $ext ) ? $file_info['basename'] : $file_info['filename'];

		// if no thumbnail exists, try to create one.
		if ( ! file_exists( $thumbnail_folder . $thumbnail_filename . '.' . $ext ) ) {
			if ( 'pdf' === $ext ) {
				starg_create_pdf_thumbnail( $path_to_file, $thumbnail_folder );
			} elseif ( 'image' === $type ) {
				starg_create_thumbnail( $path_to_file, $thumbnail_folder );
			}
		}

		if (file_exists($thumbnail_folder . $thumbnail_filename . '.' . $ext)) {
			$thumbnail_url = trailingslashit($thumbnail_dir_url) . 'thumb/' . $thumbnail_filename . '.' . $ext;
		} else {
			$thumbnail_url = Render_Sip_Content_Folder::get_fallback_thumbnail($file_type);
		}

		// no links for PDFs. Wouldn't work anyway as we use the absolute path to the files!
		if ( self::$is_pdf ) {
			return '<img src="' . $thumbnail_url . '" alt="">';
		}

		switch ($type) {
			case 'application':
				return '<a data-iframe="' . $file_url . '" href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt=""></a>';

			case 'image':
				$img_data_width  = ( isset( $image_size[0] ) ) ? ' data-width="' . $image_size[0] . '"' : '';
				$img_data_height = ( isset( $image_size[1] ) ) ? ' data-height="' . $image_size[1] . '"' : '';
				return '<a' . $img_data_width . $img_data_height . ' data-thumb="' . $thumbnail_url . '" data-img="' . $file_url .
					'" href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt=""></a>';

			case 'audio':
			case 'video':
				$sources = [["src" => $file_url, "type" => $file_type['type']]];
				return '<a data-sources=\'' . json_encode($sources, JSON_UNESCAPED_SLASHES) . '\' href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt=""></a>';

			case 'text':
				return '<a data-iframe="' . $file_url . '" href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt=""></a>';
		}

		return '';
	}

	/**
	 * Extracts metadata from files.
	 * Uses the external Library getID3 by james-heinrich.
	 *
	 * @param string $file_path
	 *
	 * @return array
	 */
	private static function get_file_metadata( string $file_path ) : array {
		$sanitized_file_path = sanitize_text_field( $file_path );
		$file_basename       = sanitize_text_field( basename($file_path) );
		$getID3              = new getID3;
		$data                = $getID3->analyze($sanitized_file_path);

		Render_Sip_Content_Folder::$exif_dates = array_merge(
			Render_Sip_Content_Folder::$exif_dates,
			Render_Sip_Content_Folder::extract_exif_dates( $data, $file_basename )
		);

		$getID3->CopyTagsToComments($data);

		$file_type = wp_check_filetype($sanitized_file_path);
		if ( ! isset($data['fileformat'])) {
			$data['fileformat'] = $file_type['ext'];
		}

		return array( $data, $file_type );
	}

	/**
	 * Creates a string with additional information about a file.
	 *
	 *  @param array $data
	 * @param string $path_clean
	 *
	 * @return string
	 */
	private static function get_file_info( array $data, string $path_clean ) : string {
		$exif_dates = Render_Sip_Content_Folder::$exif_dates;
		$info  = (isset($data['filename']) ? esc_html( $data['filename'] ) . '<br>' : '');
		$info .= (isset($data['filesize']) ? starg_format_bytes( (float) esc_html( $data['filesize'] )) . '<br>' : '');
		$info .= (isset($data['playtime_string']) ? esc_html( $data['playtime_string'] ) . '<br>' : '');
		$info .= (isset($exif_dates[basename($path_clean)])
			? date('c', (int) esc_attr( $exif_dates[basename($path_clean)] )) . '<br>'
			: '');
		return $info;
	}

	/**
	 * Checks the filetype and retrieves a suitable thumbnail.
	 *
	 * @param array{ext: string, type: string } $file_type
	 *
	 * @return string
	 */
	private static function get_fallback_thumbnail( array $file_type ) : string {
		$base_path    = STARG_SIP_PLUGIN_BASE_URL;
		$fallback_ext = '.svg';
		// We need to use the absolute path to the images if we're rendering a PDF. We also need to use PNGs to avoid conflict with possibly missing php packages.
		// The PDF-Library we're using (spipu/html2pdf) does not support SVGs.
		if ( self::$is_pdf ) {
			$base_path = STARG_SIP_PLUGIN_BASE_DIR;
			$fallback_ext = '.png';
		}

		$ext       = esc_attr( $file_type['ext'] );
		$type      = explode('/', $file_type['type'])[0];
		$base_url  = $base_path . 'assets/img/';
		$fallbacks = array(
			'application' => 'file' . $fallback_ext,
			'image'       => 'image-file' . $fallback_ext,
			'audio'       => 'audio-file' . $fallback_ext,
			'video'       => 'video-file' . $fallback_ext,
			'text'        => 'file' . $fallback_ext,
		);

		$custom = $base_url . strtolower($ext) . '' . $fallback_ext;
		if (file_exists(STARG_SIP_PLUGIN_BASE_DIR . 'assets/img/' . strtolower($ext) . '' . $fallback_ext)) {
			return esc_url( $custom );
		}

		$fallback_url = ($fallbacks[esc_attr( $type )] ?? $fallbacks['application']);
		return esc_url( $base_url . $fallback_url );
	}

	/**
	 * Extracts EXIF creation dates from a nested array structure.
	 *
	 * Iterates recursively through the given array and searches for the field "DateTimeOriginal" (a typical EXIF metadata field in images).
	 * If found, the value is converted into a UNIX timestamp (using strtotime) and stored in the resulting array, keyed by the provided file identifier.
	 *
	 * @param array  $data      The nested array (e.g., metadata array) to search through.
	 * @param string $file_key  The file identifier (e.g., filename) used as the key in the result array.
	 *
	 * @return array An associative array containing the extracted EXIF date as a UNIX timestamp,
	 *               keyed by the given file identifier. Returns an empty array if no date is found.
	 */
	private static function extract_exif_dates(array $data, string $file_key) : array {
		$exif_dates = array();

		$iterator = new RecursiveIteratorIterator(
			new RecursiveArrayIterator($data),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $key => $value) {
			if ($key === 'DateTimeOriginal') {
				$exif_dates[$file_key] = strtotime( sanitize_text_field( $value ) );
			}
		}

		return $exif_dates;
	}

}
