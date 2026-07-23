<?php
if (! defined('WPINC')) { die; }

use Appwrite\ClamAV\Network;

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Create_Sip extends Form_Validation {
	protected string $request_method = 'get';
	public string $url_endpoint      = 'create-sip';
	protected string $sip_user_folder_id;
	protected string $sip_folder;
	protected string $content_dir;
	protected string $header_dir;
	protected string $archival_id;
	protected WP_Post $archival;
	protected string $author_nickname = '';
	protected string $author_last_name = '';
	protected string $author_first_name = '';
	private int $editor_id;
	private array $sip_data = array();
	private array $upload_folder_info = array();
	private bool $run_malware_scan = true;
	private array $mapped_data = array();
	private string $target_os = '';
	private string $csv_delimiter = ',';
	private string $target_path = '';
	private string $skipped_files = '';

	/**
	 * Perform main validation for the form in question.
	 * We do not accept any user-input if one of these checks fails!
	 * @return bool true on success, false on failure.
	 */
	protected function form_validation() : bool {
		if ( ! defined( 'WPINC' ) ) { return false; } // WordPress must be running to continue!
		if ( ! current_user_can('edit_others_posts') ) { return false; } // A valid User must be logged in to continue!
		if ( ! isset( $_REQUEST[ $this->url_endpoint ] ) ) { return false; } // A valid URL-Endpoint must be defined.

		return true; // all good, form is valid!
	}

	/**
	 * Creates the Submission Information Package including all uploaded files, a XML with all metadata and if available, an importable CSV.
	 * This also triggers the download of the created ZIP file with the content.
	 * 
	 * @todo add settings for the name of the created ZIP file.
	 * @todo add user language output in XML.
	 *
	 * @return false|void
	 */
	public function create_sip() {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) {
			$this->set_error_message( esc_attr__( 'User-Input not valid.', 'sip' ) );
			$this->set_error_log_message( esc_attr__( 'User-Input not valid.', 'sip' ), Log_Severity::Warning );
			return false;
		}

		$missing_inputs = $this->user_input_required( $user_input );
		if ( ! empty( $missing_inputs ) ) {
			$this->set_notification_for_missing_inputs( $missing_inputs );
			return false;
		}

		$this->sip_user_folder_id = $user_input[ 'sipFolder' ];
		$this->archival_id = DB_Query_Helper::starg_get_archival_id_by_sip_folder( $this->sip_user_folder_id );
		if ( ! $this->archival_id ) {
			// translators: %s: ID/Name of the folder where the sip is stored.
			$this->set_error_message( sprintf( esc_attr__( 'No archival record found with SIP-ID: "%s"', 'sip' ), $this->sip_user_folder_id ) );
			$this->set_error_log_message( sprintf( esc_attr__( 'No archival record found with SIP-ID: "%s"', 'sip' ), $this->sip_user_folder_id ), Log_Severity::Warning );
			return false;
		}

		$this->archival = get_post( $this->archival_id );
		if ( ! $this->archival ) {
			$this->set_error_message( esc_attr__( 'No post found for the submitted ID.', 'sip' ) );
			// translators: %d: Post-ID
			$this->set_error_log_message( sprintf( esc_attr__( 'No post found for the ID %d during the creation of the SIP.', 'sip' ), (int) $this->archival_id ), Log_Severity::Error );
			return false;
		}

		$this->set_configuration();
		$this->create_sip_data();
		$this->create_xml();
		$this->create_import_csv();
		$this->create_filename_csv();

		$archival_first_submission = get_post_meta($this->archival->ID, '_archival_first_submission', true);
		$archival_submission_date  = ( $archival_first_submission ) ? esc_attr( $archival_first_submission ) : $this->archival->post_date;
		$author                    = strtoupper( str_replace( ', ', '_', $this->sip_data['author_name'] ) );
		$submission_date           = date( 'Ymd', strtotime( $archival_submission_date ) );
		$zip_filename              = $submission_date . '_' . $this->archival->ID . '_' . $author;
		if ( $this->sip_data['selected_institution'] ) {
			$zip_filename = $submission_date . '_' . $this->archival->ID . '_' . $author . '_' . $this->sip_data['selected_institution'];
		}

		$sip_zip_created = $this->create_sip_zip( 'SIP_' . $zip_filename );
		if ( ! $sip_zip_created ) {
			$this->set_error_message( esc_attr__( 'We are facing problems compressing the SIP folder for this submission. Please try again later.', 'sip' ) );
			// translators: %d: Post-ID
			$this->set_error_log_message( sprintf( esc_attr__( 'The SIP folder for ID %d was not created as zip.', 'sip' ), (int) $this->archival_id ), Log_Severity::Error );
			return false;
		}

		$zip      = new ZipArchive;
		$tmp_file = $this->sip_folder . 'sip.zip';
		if ($zip->open( $tmp_file, ZipArchive::CREATE ) ) {
			// add the compressed sip folder to the downloadable zip file.
			if ( file_exists( $this->sip_folder . 'SIP_' . $zip_filename . '.zip' ) ) {
				$zip->addFile( $this->sip_folder . 'SIP_' . $zip_filename . '.zip', sanitize_file_name( 'SIP_' . $zip_filename ) . '.zip' );
			}

			if ( file_exists( $this->sip_folder . 'import.csv' ) ) {
				$zip->addFile( $this->sip_folder . 'import.csv', 'import.csv' );
			}
			if ( file_exists( $this->sip_folder . 'names.csv' ) ) {
				$zip->addFile( $this->sip_folder . 'names.csv', 'names.csv' );
			}
			if ( $this->skipped_files ) {
				$zip->addFromString( 'skipped_files.txt', $this->skipped_files );
			}

			if ( isset( $this->sip_data['files'] ) && ! empty( $this->sip_data['files'] ) ) {
				$file_list_csv = $this->create_file_list_csv();
				if ( $file_list_csv ) {
					$zip->addFile( $this->sip_folder . 'file_list.csv', 'file_list.csv' );
				}
			}

			$zip->close();

			//header('Content-disposition: attachment; filename=SIP_' . $this->archival->ID . '_' . $sip_institution . '_' . $sip_referenz . '.zip');
			header('Content-disposition: attachment; filename=' . esc_attr( 'ZIP_' . $zip_filename ) . '.zip');
			header('Content-type: application/zip');
			header('Content-Length: ' . filesize( $tmp_file ) );
			readfile($tmp_file);
		}

		unlink($tmp_file);
		unlink( $this->sip_folder . 'SIP_' . $zip_filename . '.zip' );

		exit;
	}

	/**
	 * Loads the required configuration values.
	 * Retrieves configuration from the WordPress environment and the plugin settings defined by the site administrator.
	 */
	private function set_configuration() {
		require_once( STARG_SIP_PLUGIN_BASE_DIR . 'admin/create-mapping-for-import.class.php' );

		$this->editor_id   = get_current_user_id();
		$this->sip_folder  = starg_get_archival_upload_path() . $this->archival->post_author . '/' . $this->sip_user_folder_id . '/';
		$this->content_dir = $this->sip_folder . 'content/';
		$this->header_dir  = $this->sip_folder . 'header/';
		if ( ! file_exists($this->header_dir)) {
			mkdir( $this->header_dir, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS );
		}

		$user_archive_id     = (int) get_user_meta( $this->editor_id, 'user_archive', true );
		$configuration       = Create_Mapping_For_Import::get_configuration_by_inst( (int) $user_archive_id );
		$this->target_os     = ( isset( $configuration['target_os'] ) )     ? esc_attr( $configuration['target_os'] )                     : '';
		$this->csv_delimiter = ( isset( $configuration['csv_delimiter'] ) ) ? $this->get_csv_delimiter( $configuration['csv_delimiter'] ) : ',';
		$this->target_path   = ( isset( $configuration['target_path'] ) )   ? esc_attr( $configuration['target_path'] )                   : '';
	}

	/**
	 * Converts a delimiter name into its corresponding character.
	 * Maps supported delimiter names (e.g. `semicolon`, `comma` or `pipe`) to the corresponding delimiter character used during export.
	 * @param string $value
	 * @return string
	 */
	public function get_csv_delimiter( string $value ): string {
		if ( ! $value ) { return ','; }

		switch ( $value ) {
			case 'semicolon':
				return ';';
			case 'comma':
				return ',';
			// case 'tab':
			// 	return 'char(9)';
			case 'tube':
				return '|';
		}

		return ',';
	}

	/**
	 * Creates a ZIP archive containing the uploaded files.
	 * Collects the uploaded files and packages them into a single ZIP archive.
	 */
	private function create_sip_zip( string $zip_filename = '' ): bool {
		if ( ! isset( $this->upload_folder_info['structure'] ) ) { return false; }
		$sips_folder = new ZipArchive;

		$tmp_file = $this->sip_folder . sanitize_file_name( $zip_filename ) . '.zip';
		if ( $sips_folder->open($tmp_file, ZipArchive::CREATE)) {

			$skipped_files = '';
			foreach ( $this->upload_folder_info['structure'] as $folder_name => $folder_items ) {
				$sips_folder->addEmptyDir( $folder_name );
				foreach ( $folder_items as $single_item ) {
					try {
						if ( $single_item['is_dir'] ) {
							continue;
						}

						// If we can't add the single file, we log it's path in skipped_files and try the next one.
						if ( ! is_readable( $single_item['path_clean'] ) ) {
							// translators: %1$s: SIP-Folder-ID. %2$s: Path to file. %3$d: User-ID.
							$this->set_error_log_message( sprintf( esc_html__( 'Error while packing the file %1$s into the SIP file for %2$s. User-ID: %3$d', 'sip' ), $single_item['path_clean'], $this->sip_user_folder_id, $this->archival->post_author ), Log_Severity::Warning );

							// translators: %s: Path to an uploaded file.
							$skipped_files .= sprintf( esc_html__( 'File %s not added in SIP. File not readable.', 'sip' ), $single_item['path_clean'] ) . PHP_EOL;
							continue;
						}

						// If the file is infected, we log it's path in skipped_files and try the next one.
						$scan_result = $this->scan_file_for_viruses( $single_item['path_clean'] );
						if ( ! $scan_result['success'] ) {
							// translators: %1$s: Path to an uploaded file. %2$s: Reason why the file was not added.
							$skipped_files .= sprintf( esc_html__( 'File %1$s not added in SIP. Reason: %2$s', 'sip' ), $single_item['path_clean'], $scan_result['reason'] ) . PHP_EOL;
							continue;
						}

						// File is safe and we add it to the ZIP.
						$sips_folder->addFile( $single_item['path_clean'], $single_item['relative_path'] );
					} catch ( Exception $e ) {
						// translators: %1$s: SIP-Folder-ID. %2$d: User-ID. %3$d: Error message.
						$this->set_error_log_message( sprintf( esc_html__( 'Error while creating the SIP file for %1$s. User-ID: %2$d. Error message: %3$d', 'sip' ), $this->sip_user_folder_id, $this->archival->post_author, $e->getMessage() ), Log_Severity::Error );
					}
				}
			}
			
			// add the skipped_files log if needed.
			if ( $skipped_files ) {
				$this->skipped_files = $skipped_files;
			}

			if ( file_exists( $this->header_dir . 'metadata.xml' ) ) {
				$sips_folder->addFile( $this->header_dir . 'metadata.xml', 'header/metadata.xml' );
			}

			return $sips_folder->close();
		}

		return false;
	}

	/**
	 * Create all needed information for the SIP.
	 * This creates the $this->sip_data array as well as the $this->upload_folder_info array.
	 */
	private function create_sip_data() {
		$archive = get_the_terms($this->archival_id, 'archive');
		$selected_institution  = '';
		$institution_reference = '';
		// retrieve the values from the archive-taxonomy. Only needed when there are multiple institutions stored on the same CAP.
		if ( ! is_wp_error( $archive ) && isset( $archive[1] ) ) {
			$selected_institution  = strtoupper( esc_attr( carbon_get_term_meta( $archive[0]->term_id, 'sip_institution') ) );
			$institution_reference = esc_attr( carbon_get_term_meta( $archive[0]->term_id, 'sip_referenz' ) );
		}

		$this->author_nickname   = esc_attr( trim( get_user_meta( $this->archival->post_author, 'nickname', true ) ) );
		$this->author_last_name  = esc_attr( trim( get_user_meta( $this->archival->post_author, 'last_name', true ) ) );
		$this->author_first_name = esc_attr( trim( get_user_meta( $this->archival->post_author, 'first_name', true ) ) );
		$author_name             = $this->author_nickname;
		if ( $this->author_last_name && $this->author_first_name ) {
			$author_name = $this->author_last_name . ', ' . $this->author_first_name;
		}

		$user_locale = strtolower(get_user_locale( (int) $this->archival->post_author ));
		$language    = starg_get_human_readable_language( $user_locale );

		// todo: subject to change. We should save the editor who accepts the submission as the original archivist!
		$archivist_id         = $this->editor_id;
		$archivist_last_name  = esc_attr( trim( get_user_meta( $archivist_id, 'last_name', true ) ) );
		$archivist_first_name = esc_attr( trim( get_user_meta( $archivist_id, 'first_name', true ) ) );
		$archivist_name       = ( $archivist_last_name && $archivist_first_name ) ? $archivist_last_name . ', ' . $archivist_first_name : get_userdata( $archivist_id )->data->display_name;

		// todo: name of originator might be in wrong format. Should be "last name, first name".
		$originator_name = esc_attr( get_post_meta($this->archival->ID, '_archival_originator', true) );
		if ( ! trim( $originator_name ) ) {
			$originator_name = $author_name;
		}

		$archive = get_the_terms($this->archival->ID, 'archive');
		$institution_name = $archive[0]->name;
		$institution_address = array();
		if($archive[0]->description) {
			$institution_address = preg_split("/\r\n|\n|\r/", $archive[0]->description );
		}

		// $sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta' );

		$tag_list  = '';
		$tag_array = array();
		$tags      = get_the_terms($this->archival->ID, 'archival_tag');
		if ( $tags && ! is_wp_error( $tags )  ) {
			$counter = 0;
			$max     = is_countable( $tags ) ? count( $tags ) : 1;
			$delimiter    = ';';
			foreach ( $tags as $single_tag ) {
				$tag_array[] = esc_attr( $single_tag->name );
				$counter++;
				$tag_list .= esc_attr( ltrim( $single_tag->name, '#' ) );
				if ( $counter < $max ) {
					$tag_list .= $delimiter;
				}
			}
		}

		$archival_area = '';
		$geo_coordinates = '';
		$archival_address = esc_attr( trim( get_post_meta($this->archival->ID, '_archival_address', true) ) );
		$archival_address_str = '';
		if ( $archival_address ) {
			$archival_lat    = esc_attr( get_post_meta($this->archival->ID, '_archival_lat', true));
			$archival_lng    = esc_attr( get_post_meta($this->archival->ID, '_archival_lng', true));
			$geo_coordinates = ( $archival_lat && $archival_lng ) ? $archival_lat . ', ' . $archival_lng : '';

			$split_address = explode( ',', $archival_address );
			$counter = 0;
			$max     = is_countable( $split_address ) ? count( $split_address ) : 1;
			$delimiter = ', ';
			foreach( $split_address as $single_address_part ) {
				$counter++;
				$archival_address_str .= esc_attr( trim( $single_address_part ) );
				if ( $counter < $max ) {
					$archival_address_str .= $delimiter;
				}
			}
		} elseif ( $archival_area = get_post_meta($this->archival->ID, '_archival_area', true) ) {
			$archival_area = json_decode( $archival_area, true );
			$geo_coordinates = esc_attr( wp_json_encode( $archival_area['geometry']['coordinates'][0], JSON_PRETTY_PRINT ) );
		}

		$blocking_time = '';
		$blocked_until = '';
		$archival_blocking_time  = esc_attr( get_post_meta($this->archival->ID, '_archival_blocking_time', true));
		if ( is_numeric( $archival_blocking_time ) ) {
			$blocking_time = $archival_blocking_time; // something like "10" for 10 years.
			$blocked_until = date('d.m.Y', strtotime($this->archival->post_date) + ($archival_blocking_time * 31536000));
		}

		$files          = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->content_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
		$file_groups    = array();
		$file_mimes     = array();
		[ $file_groups, $file_mimes, $structure ] = self::get_upload_folder_information( $files );

		$scope = '';
		if ( $file_mimes ) {
			$mime_counter = 0;
			$total_files  = 0;
			$max_files    = is_countable( $file_mimes ) ? count( $file_mimes ) : 1;
			$delimiter    = ', ';
			foreach ($file_mimes as $mime => $number) {
				$mime_counter++;
				$total_files += (int) $number;
				// translators: Text in the physical description (ead:physdesc) of the XML. %1$s: Number of files for the MIME type. %2$s: MIME type.
				$scope .= sprintf( _n( '%1$s %2$s', '%1$s %2$s', $number, 'sip' ), $number, strtoupper( $mime ) );
				if ( $mime_counter < $max_files ) {
					$scope .= $delimiter;
				}
			}
			$scope .= $delimiter;
			// translators: Text in the physical description (ead:physdesc) of the XML. %s: Number of files in total.
			$scope .= sprintf( esc_attr__( '%s total', 'sip' ), $total_files );
		}


		// fixme: we should not use hardcoded values here! This info should come from the admin/editors.
		$copyright_url  = esc_url_raw( 'https://rightsstatements.org/vocab/InC/1.0/' );
		$copyright_text = 'In Copyright';


		// Extract dates either from the data provided or try to read dates from uploaded files.
		$archival_from = get_post_meta($this->archival->ID, '_archival_from', true);
		$archival_to   = get_post_meta($this->archival->ID, '_archival_to', true);
		$min_creation_time = self::date_to_ymd( esc_attr( $archival_from ) );
		$max_creation_time = $min_creation_time;
		$created       = self::date_to_dmY( esc_attr( $archival_from ) );
		if ( $archival_to ) {
			$max_creation_time = self::date_to_ymd( esc_attr( $archival_to ) );
			$created           = $created  . ' - ' . self::date_to_dmY( esc_attr( $archival_to ) );
		} else {
			$file_creation_dates = array();
			foreach ($file_groups as $id => $file) {
				if ( ! isset( $file['Attribute'] ) ) { continue; }

				foreach ($file['Attribute'] as $attr => $text) {
					if ( 'CREATED' === $attr ) {
						$file_creation_dates[] = $text;
					}
				}
			}
			if ( ! empty( $file_creation_dates ) ) {
				$min_creation_time = $this->date_to_ymd( esc_attr( min( $file_creation_dates ) ) );
				$max_creation_time = $this->date_to_ymd( esc_attr( max( $file_creation_dates ) ) );
			}
		}

		$file_list = $this->create_file_list( $structure );

		$upload_purpose     = esc_html( get_post_meta($this->archival->ID, '_archival_upload_purpose', true) );
		$collection_history = $upload_purpose;
		// fix typo for older entries. todo: remove before shipping!
		if ( $upload_purpose && 'Allgemeine Vor- oder Nachlass' === $upload_purpose ) {
			$collection_history = 'Allgemeiner Vor- oder Nachlass';
		}
		$this->sip_data = array(
			'id'                        => esc_html( $this->archival->ID ),
			'archival_numeration'       => '',
			'title'                     => esc_html( $this->archival->post_title ),
			'min_creation_time'         => $min_creation_time,
			'max_creation_time'         => $max_creation_time,
			'created'                   => $created,
			'blocking_time'             => $blocking_time,
			'blocked_until'             => $blocked_until,
			'scope'                     => $scope,
			'originator_name'           => $originator_name,
			'collection_history'        => $collection_history,
			'author_name'               => $author_name,
			'content'                   => esc_html( $this->archival->post_content ),
			'annotation'                => esc_html( get_post_meta($this->archival->ID, '_archival_annotation', true) ),
			'user_locale'               => $user_locale,
			'language'                  => esc_html( $language ),
			'editor_name'               => $archivist_name,
			'notes'                     => esc_html( get_post_meta($this->archival->ID, '_archival_numeration', true) ),
			'date_cataloguing'          => self::date_to_dmY($this->archival->post_date),
			'geo_coordinates'           => $geo_coordinates,
			'geo_location'              => $archival_address_str,
			'license_uri'               => $copyright_url, // todo: not ideal! these are hardcoded values!
			'license_digital_object'    => $copyright_text, // todo: not ideal! these are hardcoded values!
			'tags'                      => $tag_list,
			'tags_array'                => $tag_array,
			'files'                     => $file_list,
			'institution_name'          => $institution_name,
			'institution_address'       => $institution_address,
			'selected_institution'      => $selected_institution,//only needed when there are multiple institutions stored on the same CAP.
			'institution_reference'     => $institution_reference,//only needed when there are multiple institutions stored on the same CAP.
		);

		$this->upload_folder_info = array(
			'file_groups' => $file_groups,
			'file_mimes'  => $file_mimes,
			'structure'   => $structure,
		);
	}

	/**
	 * Builds a list of uploaded files.
	 * Retrieves the uploaded files and enriches the list with additional metadata required for further processing.
	 */
	private function create_file_list( array $structure ): array {
		$all_files = array();
		foreach ( $structure as $folder => $files_in_folder ) {
			if ( ! $files_in_folder || ! is_array( $files_in_folder ) ) { continue; }
			foreach ( $files_in_folder as $single_file ) {
				if ( ! $single_file || $single_file['is_dir'] ) { continue; }
				$all_files[] = $single_file;
			}
		}
		usort( $all_files, function( $a, $b ) {
			return strcmp($a['mime_type'], $b['mime_type']);
		});
		$filenames = array_column( $all_files, 'relative_path' );
		// $escaped_fn = array_map(function ($name) {
		// 	$name = str_replace('"', '""', $name);
		// 	return '"' . $name . '"';
		// }, $filenames);

		return $filenames;
	}

	/**
	 * Creates the XML file for the Submission Information Package.
	 * @return void
	 */
	private function create_xml() {
		$titles = array();
		// the names.csv contains all uploaded filenames.
		$names_file = fopen($this->sip_folder . 'names.csv', 'r');
		if ( $names_file !== false ) {
			while ( ( $data = fgetcsv($names_file, 100, $this->csv_delimiter) ) !== false) {
				if ( ! isset( $data[1] ) ) { continue; }
				$titles[$data[0]] = $data[1];
			}
			fclose($names_file);
		}

		// todo: use $this->sip_data
		$sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta' );
		$archival_lat  = '';
		$archival_lng  = '';
		$archival_area = '';
		if ( $archival_address = esc_attr( get_post_meta($this->archival->ID, '_archival_address', true))) {
			$archival_lat = esc_attr( get_post_meta($this->archival->ID, '_archival_lat', true));
			$archival_lng = esc_attr( get_post_meta($this->archival->ID, '_archival_lng', true));
		} else {
			$archival_area = get_post_meta($this->archival->ID, '_archival_area', true);
		}

		$writer = new XMLWriter;
		$writer->openURI($this->header_dir . 'metadata.xml');
		$writer->setIndent(1);
		$writer->setIndentString(' ');
		$writer->startDocument('1.0', 'UTF-8', 'yes');

		$writer->startElement('mets');
			$writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			$writer->writeAttribute('xmlns', 'http://www.loc.gov/METS/');
			$writer->writeAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
			$writer->writeAttribute('xmlns:csip', 'https://DILCIS.eu/XML/METS/CSIPExtensionMETS');
			$writer->writeAttribute('xmlns:ead', 'urn:isbn:1-931666-22-9');
			$writer->writeAttribute('xsi:schemaLocation', 'http://www.loc.gov/METS/ http://www.loc.gov/standards/mets/version18/mets.xsd urn:isbn:1-931666-22-9 http://www.loc.gov/ead/ead.xsd');
			$writer->writeAttribute('OBJID', 'Valid_IP_example');
			$writer->writeAttribute('TYPE', 'Databases');
			$writer->writeAttribute('PROFILE', 'https://earkcsip.dilcis.eu/profile/E-ARK-CSIP.xml');
			$writer->startElement('metsHdr');
				$writer->writeAttribute('ID',  'archival' . $this->sip_data['id'] );
				$writer->writeAttribute('CREATEDATE', self::date_to_dmY($this->archival->post_date));
				$writer->writeAttribute('LASTMODDATE', self::date_to_dmY($this->archival->post_modified));
				$writer->writeAttribute('RECORDSTATUS', 'Complete');
				$writer->startElement('agent');
					$writer->writeAttribute('ROLE', 'CREATOR');
					$writer->writeAttribute('TYPE', 'INDIVIDUAL');
					$writer->writeElement('name', $this->sip_data['author_name'] );
				$writer->endElement(); // agent
				$writer->startElement('agent');
					$writer->writeAttribute('ROLE', 'ARCHIVIST');
					$writer->writeAttribute('TYPE', 'INDIVIDUAL');
					$writer->writeElement('name', $this->sip_data['editor_name'] );
				$writer->endElement(); // agent
				$writer->startElement('agent');
					$writer->writeAttribute('ROLE', 'PRESERVATION');
					$writer->writeAttribute('TYPE', 'ORGANIZATION');
					$writer->writeElement('name', $this->sip_data['institution_name']);
					foreach ($this->sip_data['institution_address'] as $address_line) {
						$single_line = trim( $address_line );
						if ( ! $single_line ) { continue; }
						$writer->writeElement('note', esc_attr( $single_line ));
					}
				$writer->endElement(); // agent
			$writer->endElement(); // metsHdr

			$writer->startElement('dmdSec');
				$writer->writeAttribute('ID', 'DMD' . $this->sip_data['id']);
				$writer->startElement('mdWrap');
					$writer->writeAttribute('MDTYPE', 'EAD');
					$writer->startElement('xmlData');
						$writer->startElement('ead:ead');
							$writer->startElement('ead:eadheader');
								$writer->writeElement('ead:eadid', 'EAD' . $this->sip_data['id']);
								$writer->startElement('ead:filedesc');
									$writer->startElement('ead:titlestmt');
										$writer->writeElement('ead:titleproper',  $this->sip_data['title'] );
									$writer->endElement(); // ead:titlestmt
									$writer->startElement('ead:publicationstmt');
										$writer->writeElement('ead:publisher', $this->sip_data['institution_name']);
									$writer->endElement(); // ead:publicationstmt
								$writer->endElement(); // ead:filedesc
							$writer->endElement(); // ead:eadheader
							$writer->startElement('ead:archdesc');
								$writer->writeAttribute('level', 'file');
								$writer->startElement('ead:did');
									$writer->writeElement( 'ead:unittitle',  $this->sip_data['title'] );
									$writer->writeElement( 'ead:unitid', 'ITEM' . $this->sip_data['id'] );
									if ( $this->sip_data['originator_name'] ) {
										$writer->startElement('ead:origination');
											$writer->writeElement('ead:persname', $this->sip_data['originator_name']);
										$writer->endElement(); // ead:origination
									}
									if ( $this->sip_data['scope'] ) {
										$writer->startElement('ead:physdesc');
											$writer->text( $this->sip_data['scope'] );
										$writer->endElement(); // ead:physdesc
									}
									if ( $this->sip_data['created'] ) {
										$writer->startElement('ead:unitdate');
											$writer->writeAttribute('type', 'inclusive');
											$writer->text( $this->sip_data['created'] );
										$writer->endElement(); // ead:unitdate
									}
									$writer->writeElement('ead:physloc', get_bloginfo('name'));
								$writer->endElement(); // ead:did

								if ( $this->sip_data['content'] ) {
									$writer->startElement('ead:scopecontent');
										$writer->startElement('ead:p');
											$writer->startCdata();
												$writer->text($this->sip_data['content']);
											$writer->endCdata();
										$writer->endElement(); // ead:p
									$writer->endElement(); // ead:scopecontent
								}

								if ( $this->sip_data['collection_history'] ) {
									$writer->startElement('ead:custodhist');
										$writer->writeElement('ead:p', $this->sip_data['collection_history']);
									$writer->endElement(); // ead:custodhist
								}

								$writer->startElement('ead:accessrestrict');
									$writer->writeElement('ead:head', esc_attr__( 'Access Condition', 'sip' ));

									$writer->startElement( 'ead:legalstatus' );
										$writer->startElement( 'ead:p' );
											$writer->startElement('ead:extref');
												$writer->writeAttribute('xlink:href', $this->sip_data['license_uri']);
												$writer->text($this->sip_data['license_digital_object']);
											$writer->endElement(); // ead:extref
										$writer->endElement(); // 'ead:p'
									$writer->endElement(); //ead:legalstatus

									if ( is_numeric( $this->sip_data['blocking_time'] ) ) {
										$writer->startElement('ead:chronlist');
											$writer->startElement('ead:chronitem');
												$writer->startElement('ead:date');
													$writer->startAttribute('type');
														$writer->text('embargoPeriod');
													$writer->endAttribute();
													$writer->text( $this->sip_data['blocking_time'] );
												$writer->endElement(); //ead:date
											$writer->endElement(); //ead:chronitem
											$writer->startElement('ead:chronitem');
												$writer->startElement('ead:date');
													$writer->startAttribute('type');
														$writer->text('restrictedUntil');
													$writer->endAttribute();
													// todo: maybe change date from post_date to _archival_first_submission.
													$writer->text( $this->sip_data['blocked_until'] );
												$writer->endElement(); //ead:date
											$writer->endElement(); //ead:chronitem
										$writer->endElement(); // ead:chronlist
									}
								$writer->endElement(); //ead:accessrestrict

								// todo: use $this->sip_data!!
								if($this->sip_data['tags_array'] || $archival_address || ($archival_area && $area = json_decode($archival_area))) {
									$writer->startElement( 'ead:controlaccess' );
										if($archival_address) {
											$split_address = explode( ',', $archival_address );
											$writer->startElement('ead:head');
												$writer->text( 'Geografika:' );
											$writer->endElement();
											foreach( $split_address as $single_address_part ) {
												$writer->startElement('ead:geogname');
													$writer->text( esc_attr( trim( $single_address_part ) ) );
												$writer->endElement(); // ead:geogname
											}
											$writer->startElement('ead:head');
												$writer->text('Coordinates:' );
											$writer->endElement(); // ead:head
											$writer->startElement( 'ead:geogname');
												$writer->text( $archival_lat . ', ' . $archival_lng );
											$writer->endElement(); // ead:geogname
										}
										if ($archival_area && $area = json_decode($archival_area)) {
											$writer->startElement('ead:head');
												$writer->text('Coordinates:' );
											$writer->endElement(); // ead:head
											$writer->startElement('ead:geogname');
												$writer->text( esc_attr( json_encode($area->geometry->coordinates[0]) ) );
											$writer->endElement(); // ead:geogname
										}
										foreach ( $this->sip_data['tags_array'] as $tag_name ) {
											if ( ! $tag_name ) { continue; }
											$writer->writeElement( 'ead:subject', $tag_name );
										}
									$writer->endElement(); //ead:controlaccess
								}

								$writer->startElement( 'ead:dao' );
									$writer->writeAttribute('xlink:href', $this->sip_data['license_uri']);
									$writer->writeAttribute('xlink:show', 'new');
								$writer->endElement(); // ead:dao

								if($sip_custom_meta = carbon_get_theme_option('sip_custom_meta' )) {
									foreach ( $sip_custom_meta as $custom_meta ) {
										$meta_name = sanitize_title( $custom_meta['sip_custom_meta_key'] );
										if ( $archival_custom_meta = get_post_meta( $this->sip_data['id'], '_archival_' . $meta_name, true ) ) {
											$writer->startElement( 'ead:odd' );
												$writer->writeElement( 'ead:head', $custom_meta['sip_custom_meta_title_' . $this->sip_data['user_locale'] ]  );
												$writer->startElement( 'ead:p' );
													$writer->writeCdata( esc_attr( $archival_custom_meta ) );
												$writer->endElement(); // ead:p
											$writer->endElement(); // ead:odd
										}
									}
								}

								if($this->sip_data['archival_numeration']) {
									$writer->startElement( 'ead:odd' );
										$writer->writeElement( 'ead:head', esc_attr__('Numbering', 'sip') );
										$writer->writeElement( 'ead:p', $this->sip_data['archival_numeration'] );
									$writer->endElement(); // ead:odd
								}

								if($this->sip_data['annotation']) {
									$writer->startElement( 'ead:appraisal' );
										$writer->writeElement( 'ead:head', esc_attr__('Note', 'sip') );
										$writer->startElement( 'ead:p' );
											$writer->writeCdata( $this->sip_data['annotation'] );
										$writer->endElement(); // ead:p
									$writer->endElement(); // ead:appraisal
								}

								if($sip_custom_archival_user_meta) {
									foreach ( $sip_custom_archival_user_meta as $custom_archival_user_meta ) {
										$meta_name = sanitize_title( $custom_archival_user_meta['sip_custom_archival_user_meta_key'] );
										if ( $archival_custom_meta = get_post_meta( $this->archival->ID, '_archival_' . $meta_name, true ) ) {
											$writer->startElement( 'ead:odd' );
												$writer->startElement( 'ead:head' );
													$writer->text( esc_attr( $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $this->sip_data['user_locale'] ] ) );
												$writer->endElement(); // ead:head
												$writer->startElement( 'ead:p' );
													$writer->startCdata();
														$writer->text( wp_kses_post( $archival_custom_meta ) );
													$writer->endCdata();
												$writer->endElement(); // ead:p
											$writer->endElement(); // ead:odd
										}
									}
								}

							$writer->endElement(); // ead:archdesc
						$writer->endElement(); // ead:ead
					$writer->endElement(); // xmlData
				$writer->endElement(); // mdWrap
			$writer->endElement(); // dmdSec

			$writer->startElement('amdSec');
				$writer->startElement('rightsMD');
					$writer->writeAttribute('ID', 'RIGHTS' . $this->sip_data['id']);
					$writer->startElement('mdWrap');
						// NOTE: This is valid though not the typical METS-way to declare rights. Usually METS suggests PREMIS.
						$writer->writeAttribute('MDTYPE', 'OTHER');
						$writer->writeAttribute('OTHERMDTYPE', 'RIGHTS');
						$writer->startElement('xmlData');
							$writer->startElement('rightsDeclaration');
								$writer->startElement('rightsHolder');
									$writer->writeElement('rightsHolderName', $this->sip_data['originator_name']);
								$writer->endElement(); // rightsHolder
								$writer->writeElement('rightsType', $this->sip_data['license_digital_object']);
							$writer->endElement(); // rightsDeclaration
						$writer->endElement(); // xmlData
					$writer->endElement(); // mdWrap
				$writer->endElement(); // rightsMD
			$writer->endElement(); // amdSec

			if ( $this->upload_folder_info['file_groups'] ) :
				$writer->startElement('fileSec');
					foreach ($this->upload_folder_info['file_groups'] as $group => $group_files) :
						if ( empty( $group_files ) ) { continue; }

						$writer->startElement('fileGrp');
							$writer->writeAttribute('USE', esc_attr( $group ));
							foreach ($group_files as $id => $file) :
								$single_file_name = ( isset( $titles[ basename( $file['Path'] ) ] ) ) ? $titles[ basename( $file['Path'] ) ] : basename( $file['Path'] );
								$writer->startElement('file');
									$writer->writeAttribute('ID', 'FILE' . esc_attr( $id ));
									if ( isset( $file['Attribute'] ) ) :
										foreach ($file['Attribute'] as $attr => $text) :
											$writer->startAttribute( esc_attr( $attr ) );
												if ( 'CREATED' === $attr ) {
													$writer->text( self::date_to_Ymd( esc_attr( $text ) ) );
												} else {
													$writer->text( esc_attr( $text ) );
												}
											$writer->endAttribute();
										endforeach;
									endif;
									$writer->startElement('FLocat');
										$writer->writeAttribute('xlink:href', esc_attr( $file['Path'] ));
										$writer->writeAttribute('xlink:title', esc_attr( $single_file_name ));
										$writer->writeAttribute('LOCTYPE', 'OTHER');
										$writer->writeAttribute('OTHERLOCTYPE', 'SYSTEM');
									$writer->endElement(); // FLocat
								$writer->endElement(); // file
							endforeach;
						$writer->endElement(); // fileGrp
					endforeach;
				$writer->endElement(); // fileSec
			endif;

			if ( $this->upload_folder_info['structure'] ) :
				$writer->startElement('structMap');
					$writer->writeAttribute('TYPE', 'PHYSICAL');
					$writer->writeAttribute('LABEL', 'CSIP');

					foreach ($this->upload_folder_info['structure'] as $folder_name => $content) :
						if ( empty( $content ) ) { continue; }

						$writer->startElement('div');
							$writer->writeAttribute('TYPE', 'Directory');
							$writer->writeAttribute(
								'LABEL',
								esc_attr( $folder_name )
							);
							foreach ( $content as $single_file ) :
								if ( ! isset( $single_file['file_id'] ) || $single_file['is_dir'] ) { continue; }

								$writer->startElement('div');
									$writer->writeAttribute('TYPE', 'Item');
									$writer->startElement('fptr');
										$writer->writeAttribute('FILEID', 'FILE' . esc_attr( $single_file['file_id'] ));
									$writer->endElement(); // fptr
								$writer->endElement(); // div

							endforeach;
						$writer->endElement(); // div
					endforeach;

				$writer->endElement(); // structMap
			endif;

		$writer->endElement(); // mets

		$writer->endDocument();

		$writer->flush();
	}

	/**
	 * Create an importable file with user defined mapping.
	 */
	private function create_import_csv() {
		// todo: we might need to kick an already existing the import.csv in order to ship the one for the actual user/institute!!
		$import_data = $this->get_import_data();
		if ( ! $import_data ) { return false; }

		$import_data_keys   = array();
		$import_data_values = array();

		foreach ( $import_data as $single_element ) {
			$import_data_keys[]   = $single_element['key'];
			$import_data_values[] = esc_attr( $single_element['value'] );
		}
		$import_file = fopen( $this->sip_folder . 'import.csv', 'w' );
		fwrite( $import_file, "\xEF\xBB\xBF" ); // add UTF-8 BOM for Excel compatibility!
		fputcsv( $import_file, $import_data_keys, $this->csv_delimiter );
		fputcsv( $import_file, $import_data_values, $this->csv_delimiter );
		$file_closed = fclose( $import_file );
		if ( ! $file_closed ) {
			// translators: %s: Path to the file, which was not closed properly.
			$this->set_error_log_message( sprintf( esc_html__( 'The file import.csv was not closed properly. Please check %s', 'sip' ), $this->sip_folder ), Log_Severity::Warning );
		}
	}

	/**
	 * Generates a CSV file containing all filenames.
	 */
	private function create_filename_csv(): void {
		if ( ! isset( $this->upload_folder_info['file_groups'] ) ) { return; }

		// Create a CSV with all the filenames.
		$rows = array();
		foreach ($this->upload_folder_info['file_groups'] as $group => $group_files) {
			foreach ($group_files as $id => $file ) {
				$single_file_path = esc_attr( $file['Path'] );
				$single_file_name = basename( $single_file_path );
				$text = 'none';
				if ( isset( $file['Attribute'] ) ) {
					foreach ($file['Attribute'] as $attr => $text) {
						if ( 'MIMETYPE' === $attr ) {
							$mime = $text;
						}
					}
				}
				$rows[] = array(
					'mime' => $mime,
					'path' => $single_file_path,
					'name' => $single_file_name,
				);
			}
		}
		usort($rows, function ($a, $b) {
			return strcmp($a['mime'], $b['mime']);
		});
		$filenames_csv_file = fopen( $this->sip_folder . 'names.csv', 'w' );
		fputcsv( $filenames_csv_file, array( 'mime', 'name', 'path', ), $this->csv_delimiter );
		foreach ($rows as $row) {
			$single_file_path = ( str_starts_with( $row['path'], 'content/' ) ) ? substr( $row['path'], strlen( 'content/' ) ) : $row['path'];
			fputcsv( $filenames_csv_file, [$row['mime'], $row['name'], $single_file_path], $this->csv_delimiter );
		}
		$file_closed = fclose( $filenames_csv_file );
		if ( ! $file_closed ) {
			// translators: %s: Path to the file, which was not closed properly.
			$this->set_error_log_message( sprintf( esc_html__( 'The file names.csv was not closed properly. Please check %s', 'sip' ), $this->sip_folder ), Log_Severity::Warning );
		}
	}

	/**
	 * Generates a CSV file containing all filenames and adds extra data.
	 * This file allows you to import the paths where the files are located.
	 */
	private function create_file_list_csv(): bool {
		$files = $this->sip_data['files'];
		if ( ! $files ) { return false; }

		$originator_name    = str_replace( ' ', '_', $this->sip_data['originator_name'] );
		$enriched_filenames = array();
		foreach ( $files as $filepath ) {
			// ATTENTION: Unix filepath uses "/" while Windows uses "\"!
			$single_file_path = ( str_starts_with( $filepath, 'content/' ) ) ? substr( $filepath, strlen( 'content/' ) ) : $filepath;
			$path_separator   = '/';
			if ( 'windows' === $this->target_os ) {
				$single_file_path = str_replace( '/', '\\', esc_attr( $single_file_path ) );
				$path_separator   = '\\';
			}
			$enriched_filenames[] = esc_attr( $this->target_path ) . $path_separator . $originator_name . $path_separator . $single_file_path;
		}

		$import_file = fopen( $this->sip_folder . 'file_list.csv', 'w' );
		fwrite( $import_file, "\xEF\xBB\xBF" ); // add UTF-8 BOM for Excel compatibility!
		fputcsv( $import_file, array( esc_attr__( 'Filenames', 'sip' ), ), $this->csv_delimiter );
		foreach( $enriched_filenames as $single_file ) {
			fputcsv( $import_file, array( $single_file ), $this->csv_delimiter );
		}
		$file_closed = fclose( $import_file );
		if ( ! $file_closed ) {
			// translators: %s: Path to the file, which was not closed properly.
			$this->set_error_log_message( sprintf( esc_html__( 'The file file_list.csv was not closed properly. Please check %s', 'sip' ), $this->sip_folder ), Log_Severity::Warning );
		}

		return $file_closed;
	}

	/**
	 * Map the the SIP-data to the columns of the digital long term archive system.
	 * @return array{key:string,value:string,order:int}
	 */
	private function get_import_data(): array {
		$import_data_mapping = $this->get_import_data_mapping();
		if ( empty( $import_data_mapping[ 'fields' ] ) ) { return array(); }

		$mapped_data = array();
		/** @var array{value:string,order:int} $mapped_values */
		foreach ( $import_data_mapping[ 'fields' ] as $mapping_key => $mapped_values ) {
			if ( ! isset( $this->sip_data[ $mapping_key ] ) || empty( $mapped_values ) ) { continue; }

			if ( is_array( $mapped_values ) ) {
				$mapped_data[ $mapping_key ] = array(
					'key'   => esc_attr( $mapped_values['value'] ),
					'value' => $this->sip_data[ $mapping_key ],
					'order' => (isset( $mapped_values['order'] ) ) ? (int) $mapped_values['order'] : 0,
				);
			} else {
				$mapped_data[$mapping_key] = array(
					'key'   => esc_attr( $mapped_values ),
					'value' => $this->sip_data[ $mapping_key ],
					'order' => 0,
				);
			}
		}

		if ( empty( $import_data_mapping[ 'static' ] ) ) {
			uasort( $mapped_data, function ( $a, $b ) {
				return $a['order'] <=> $b['order'];
			});
			$this->mapped_data = $mapped_data;
			return $this->mapped_data;
		}

		/** @var array{column_name:string, column_value:string,order:int} $single_pair */
		foreach ( $import_data_mapping[ 'static' ] as $arr_key => $single_pair ) {
			if ( ! isset( $single_pair['column_name' ] ) ) { continue; }

			$mapped_data[] = array(
				'key'   => esc_attr( $single_pair['column_name' ] ),
				'value' => esc_attr( $single_pair['column_value' ] ),
				'order' => (isset( $single_pair['order'] ) ) ? (int) $single_pair['order'] : 0,
			);
		}

		uasort( $mapped_data, function ( $a, $b ) {
			return $a['order'] <=> $b['order'];
		});

		$this->mapped_data = $mapped_data;
		return $this->mapped_data;
	}

	/**
	 * Returns the active mapping for an user by their institution.
	 * @return array
	 */
	private function get_import_data_mapping(): array {
		require_once( STARG_SIP_PLUGIN_BASE_DIR . 'admin/create-mapping-for-import.class.php' );
		$user_archive_id = (int) get_user_meta( $this->editor_id, 'user_archive', true );
		$active_mapping  = Create_Mapping_For_Import::get_mapping_by_inst( (int) $user_archive_id );
		return $active_mapping;
	}

	/**
	 * Loop through the uploaded files and get the metadata of every single file as well as the structure of the files in the folder.
	 * @return array
	 */
	private function get_upload_folder_information( object $files ) {
		$file_groups    = array();
		$file_mimes     = array();
		$structure      = array();

		foreach ( $files as $file_info ) {
			$depth         = $files->getDepth();
			$path_clean    = $file_info->getPathname();
			$relative_path = str_replace($this->content_dir, 'content/', $path_clean);
			$folder_name   = untrailingslashit( str_replace( $file_info->getFilename(), '', $relative_path ) );
			$file_id       = md5($relative_path);

			$file_type = wp_check_filetype($path_clean);
			$mime_type = $file_type['type'] ?? 'application/octet-stream';

			// create the structure for the content of the folder.
			$structure[ $folder_name ][] = array(
				'depth'         => $depth,// todo: maybe delete. not needed.
				'relative_path' => $relative_path,
				'path_clean'    => $path_clean,
				'filename'      => $file_info->getFilename(),
				'file_id'       => $file_id,
				'is_dir'        => $file_info->isDir(),
				'mime_type'     => esc_attr( $mime_type ),
			);

			if ( ! $file_info->isFile() ) {
				continue;
			}

			// $file_sip_path  = str_replace( $this->content_dir, 'content/', $path_clean );
			// $file_extension = strtoupper( pathinfo( $path_clean, PATHINFO_EXTENSION ) );
			$file_extension = strtoupper( $file_info->getExtension() );

			if ( ! isset($file_mimes[$file_extension] ) ) {
				$file_mimes[$file_extension] = 0;
			}
			$file_mimes[$file_extension]++;

			$file_group_type = 'DEFAULT';
			if (strrpos($mime_type, 'application') === 0) {
				$file_group_type = 'DOWNLOAD';
			} elseif (strrpos($mime_type, 'audio') === 0) {
				$file_group_type = 'AUDIO';
			} elseif (strrpos($mime_type, 'video') === 0) {
				$file_group_type = 'VIDEO';
			}

			$file_groups[$file_group_type][$file_id] = array(
				'Attribute' => array(
					'CHECKSUM'     => hash_file('sha256', $path_clean),
					'CHECKSUMTYPE' => 'SHA-256',
					'MIMETYPE'     => $mime_type,
					// 'SIZE'         => filesize($path_clean),
					'SIZE'         => $file_info->getSize(),
				),
				'Path' => $relative_path,
			);

			$created = self::get_file_creation_date( $path_clean, $mime_type, $file_info );

			if ( $created !== null ) {
				$file_groups[$file_group_type][$file_id]['Attribute']['CREATED'] = $created;
			}
		}

		return array( $file_groups, $file_mimes, $structure );
	}

	/**
	 * Perform a virus check with clamAV.
	 * @param string $upload_file_path Path to the uploaded file.
	 * @return array{success:bool, reason:string} success:false if clamAV is not responding or if a virus was found. success:true on success.
	 */
	private function scan_file_for_viruses( $upload_file_path ) : array {
		if ( ! $this->run_malware_scan ) {
			return array( 'success' => true, 'reason' => esc_attr__( 'No malware scan while SIP creation.', 'sip' ), );
		}

		$clam_rdy = false;
		try {
			$clam     = new Network( esc_attr( carbon_get_theme_option( 'sip_clamav_host' ) ), (int) esc_attr( carbon_get_theme_option( 'sip_clamav_port' ) ) );
			$clam_rdy = $clam->ping();
		} catch( Exception $exception ) {
			// no connection to clamav!
			$this->set_error_log_message( $exception->getMessage(), Log_Severity::Error );
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: not responding', 'sip' ), );
		}

		// maybe connected to clamav but clamav is not ready/responding.
		if ( ! $clam_rdy ) {
			$this->set_error_log_message( esc_attr__( 'ClamAV is not ready/responding', 'sip' ), Log_Severity::Error );
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: not ready', 'sip' ), );
		}

		if ( ! file_exists( $upload_file_path ) ) {
			// translators: %1$s: path to the file. %2$s: user id.
			$this->set_error_log_message(sprintf(esc_attr__('Uploaded File %1$s from user id %2$d was not scanned. File not found', 'sip'), $upload_file_path, $this->editor_id ), Log_Severity::Error );
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: file not found', 'sip' ), );
		}

		$scan_result = $clam->fileScan($upload_file_path);
		if ( ! $scan_result ) {
			// translators: %1$s: File path. %2$d: User ID.
			$this->set_error_log_message(sprintf(esc_attr__('Uploaded File %1$s from user id %2$d is infected', 'sip'), $upload_file_path, $this->editor_id ), Log_Severity::Warning );
			return array( 'success' => false, 'reason' => esc_attr__( 'ClamAV: virus detected', 'sip' ), );
		}

		return array( 'success' => true, 'reason' => esc_attr__( 'ClamAV: file is safe', 'sip' ), );
	}

	/**
	 * Returns a converted date.
	 * The format is ISO 8601 and looks like: 2004-02-12T15:19:21+00:00
	 */
	private static function date_to_iso8601($date) {
		return date('c', strtotime($date));
	}

	/**
	 * Returns a converted date.
	 * The format is 2004,0130
	 */
	private static function date_to_Ymd($date) {
		return date('Y,md', strtotime($date));
	}

	/**
	 * Returns a converted date.
	 * The format looks like: 30.01.2004
	 */
	private static function date_to_dmY($date) {
		return date('d.m.Y', strtotime($date));
	}

	/**
	 * Return the creation date for files.
	 * @todo add other mime_types like ms-office files.
	 */
	protected static function get_file_creation_date( $path, string $mime_type, string $file_info = '' ) {
		if ( strpos($mime_type, 'image') === 0 ) {
			$date = self::get_exif_date($path);
			if ($date) {
				return $date;
			}
		}

		if ( $mime_type === 'application/pdf' ) {
			$date = self::get_pdf_creation_date($path);
			if ($date) {
				return $date;
			}
		}

		if (strpos($mime_type, 'audio') === 0) {
			$date = self::get_audio_date($path);
			if ($date) {
				return $date;
			}
		}

		return null;
	}

	/**
	 * EXIF-Data are only available in some images.
	 */
	protected static function get_exif_date($path) {
		$exif = @exif_read_data( esc_attr( $path ) );

		if ( ! empty($exif['DateTimeOriginal'])) {
			return self::date_to_iso8601( $exif['DateTimeOriginal'] );
		}

		if ( ! empty($exif['DateTime'])) {
			return self::date_to_iso8601( $exif['DateTime'] );
		}

		return null;
	}

	/**
	 * Read the metadata from the PDF file and try to extract the creation date.
	 */
	protected static function get_pdf_creation_date($path) {
		$handle = fopen( esc_attr( $path ), 'r');
		if ( ! $handle) { return null; }

		$chunk_size = 8192;
		$max_bytes  = 131072;
		$read_bytes = 0;

		while ( ! feof($handle) && $read_bytes < $max_bytes) {
			$chunk = fread($handle, $chunk_size);
			$read_bytes += strlen($chunk);

			if (preg_match('/\/CreationDate\s*\(D:(.*?)\)/', $chunk, $matches)) {
				fclose($handle);
				return self::parse_pdf_date($matches[1]);
			}
		}

		fclose($handle);
		return null;
	}

	/**
	 * Extracts a date from a PDF file.
	 */
	protected static function parse_pdf_date($pdf_date) {
		// Format: YYYYMMDDHHmmSSOHH'mm'
		if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})?/', $pdf_date, $m)) {
			$timestamp = strtotime(sprintf(
				'%s-%s-%s %s:%s:%s',
				$m[1], $m[2], $m[3],
				$m[4] ?? '00',
				$m[5] ?? '00',
				$m[6] ?? '00'
			));

			return $timestamp ? date('c', $timestamp) : null;
		}

		return null;
	}

	/**
	 * Extract a date from an audio file.
	 * @param string $path the path to the audio file.
	 */
	protected static function get_audio_date($path) {
		if ( ! class_exists('\getID3')) { return null; }
		if ( ! file_exists( $path ) ) { return null; }

		$getID3 = new \getID3();
		$info   = $getID3->analyze( esc_attr( $path ) );

		$possibleFields = array(
			'year',
			'recording_time',
			'creation_time',
		);

		foreach ($possibleFields as $field) {
			if ( ! empty($info['tags']['id3v2'][$field][0])) {
				return self::date_to_iso8601($info['tags']['id3v2'][$field][0]);
			}
		}

		// Fallback: general metadata
		if ( ! empty($info['comments']['year'][0])) {
			return self::date_to_iso8601($info['comments']['year'][0]);
		}

		return null;
	}

	/**
	 * Fallback to get the information from the filesystem.
	 * As this isn't the real creation date, this method is not in use atm.
	 */
	protected static function get_fallback_creation_date( string $file_info ) {
		$file  = new SplFileInfo( $file_info );
		if ( ! $file->isFile() ) return null;

		// Get the inode change time.
		$ctime = $file->getCTime();
		if ( ! empty($ctime)) {
			return date('c', $ctime);
		}

		// Get the last modified time.
		$mtime = $file->getMTime();
		if ( ! empty($mtime)) {
			return date('c', $mtime);
		}
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'sipFolder'  => 'sanitize_text_field',
		);
	}

	/**
	 * Describes which inputs of the form are required.
	 * If a form has not delivered one of these inputs, we do not trigger any action but display an error message.
	 * For performance reasons we use the input names as keys for the array. This way we can use isset() instead of in_array().
	 * @return array
	 */
	protected function get_required_input_names() : array {
		return array( 'sipFolder' => true, );
	}
}
