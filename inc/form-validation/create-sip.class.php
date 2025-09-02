<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Create_Sip extends Form_Validation {
	protected string $request_method = 'get';
	public string $url_endpoint      = 'create-sip';
	protected string $sip_folder;
	protected string $content_dir;
	protected string $header_dir;
	protected $archival;

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
	 * Creates all the needed files for a Submission Information Package ready to be ingested in an OAIS like archive.
	 * This also triggers the download of the created ZIP file with the content.
	 * @return false|void
	 */
	public function create_sip() {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) {
			$this->set_error_message( esc_attr__( 'User-Input not valid.', 'sip' ) );
			$this->set_error_log_message( esc_attr__( 'User-Input not valid.', 'sip' ) );
			return false;
		}

		$sip_user_folder_id = $user_input[ 'sipFolder' ];
		if ( ! $sip_user_folder_id ) {
			$this->set_error_message( esc_attr__( 'No SIP Folder provided.', 'sip' ) );
			$this->set_error_log_message( esc_attr__( 'No SIP Folder provided.', 'sip' ) );
			return false;
		}

		global $wpdb;
		$archival_sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder' AND meta_value = %s";
		$archival_id  = $wpdb->get_var($wpdb->prepare( $archival_sql, $sip_user_folder_id ));
		if ( ! $archival_id ) {
			// translators: %s: ID/Name of the folder where the sip is stored.
			$this->set_error_message( sprintf( esc_attr__( 'No archival record found with SIP-ID: "%s"', 'sip' ), $sip_user_folder_id ) );
			$this->set_error_log_message( sprintf( esc_attr__( 'No archival record found with SIP-ID: "%s"', 'sip' ), $sip_user_folder_id ) );
			return false;
		}

		$archive = get_the_terms($archival_id, 'archive');

		$sip_institution = '';
		if ( ! is_wp_error( $archive ) ) {
			$sip_institution = strtoupper( esc_attr( carbon_get_term_meta( $archive[0]->term_id, 'sip_institution') ) );
			$sip_referenz    = esc_attr( carbon_get_term_meta( $archive[0]->term_id, 'sip_referenz' ) );
		}

		$this->archival = get_post( $archival_id );
		$archival_user  = get_user_by('id', $this->archival->post_author);
		$sip_referenz   = ($sip_referenz) ? strtoupper($sip_referenz) : strtoupper($archival_user->user_login);

		$this->sip_folder  = esc_attr( carbon_get_theme_option('sip_upload_path') ) . $this->archival->post_author . '/' . $sip_user_folder_id . '/';
		$this->content_dir = $this->sip_folder . 'content/';
		$this->header_dir  = $this->sip_folder . 'header/';

		if (! file_exists($this->header_dir)) {
			mkdir( $this->header_dir, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS );
		}

		$this->create_xml();

		$zip      = new ZipArchive;
		$tmp_file = $this->content_dir . '/sip.zip';
		if ($zip->open($tmp_file, ZipArchive::CREATE)) {
			$files = new RecursiveTreeIterator(new RecursiveDirectoryIterator($this->content_dir, RecursiveDirectoryIterator::SKIP_DOTS));

			foreach ($files as $path) {
				$path = trim($path);
				$path_clean = substr($path, strpos($path, '/'));

				if (is_dir($path_clean) === true) {
					$zip->addEmptyDir(str_replace($this->content_dir, 'content/', $path_clean . '/'));
				} else if (is_file($path_clean) === true) {
					$zip->addFromString(str_replace($this->content_dir, 'content/', $path_clean), file_get_contents($path_clean));
				}
			}

			$zip->addFile($this->header_dir . 'metadata.xml', 'header/metadata.xml');

			$zip->close();
			header('Content-disposition: attachment; filename=SIP_' . date('Ymd-Hi') . '_' . $sip_institution . '_' . $sip_referenz . '.zip');
			header('Content-type: application/zip');
			header('Content-Length: ' . filesize( $tmp_file ) );
			readfile($tmp_file);
			unlink($tmp_file);
		}

		exit;
	}

	/**
	 * Creates the XML file for the Submission Information Package.
	 * @return void
	 */
	function create_xml() {
		$current_locale = strtolower(get_locale());
		$files          = new RecursiveTreeIterator(new RecursiveDirectoryIterator($this->content_dir, RecursiveDirectoryIterator::SKIP_DOTS));
		$file_groups    = array();
		$file_mimes     = array();
		$exif           = false;

		$titles = array();
		// the names.csv contains all uploaded filenames.
		$names_file = fopen($this->sip_folder . 'names.csv', 'r');
		if ( $names_file !== false ) {
			// todo: our maximum of uploaded files is 100 for one archival record (=SIP).
			while ( ( $data = fgetcsv($names_file, 100, ',') ) !== false) {
				$titles[$data[0]] = $data[1];
			}
			fclose($names_file);
		}

		foreach ($files as $path) {
			$path = trim($path);
			$path_clean = substr($path, strpos($path, '/'));
			if (is_file($path_clean) === true) {
				$file_sip_path  = str_replace($this->content_dir, 'content/', $path_clean);
				$file_extension = strtoupper(pathinfo($path_clean, PATHINFO_EXTENSION));
				if ( array_key_exists( $file_extension, $file_mimes ) ){
					$file_mimes[$file_extension]++;
				} else {
					$file_mimes[$file_extension] = 1;
				}
				$file_type = wp_check_filetype($path_clean);
				$file_id   = md5($file_sip_path);
				$file_group_type = 'DEFAULT';
				if (strrpos($file_type['type'], 'application') === 0) {
					$file_group_type = 'DOWNLOAD';
				} elseif (strrpos($file_type['type'], 'audio') === 0) {
					$file_group_type = 'AUDIO';
				} elseif (strrpos($file_type['type'], 'video') === 0) {
					$file_group_type = 'VIDEO';
				} else {
					$exif = @exif_read_data($path_clean);
				}
				$file_groups[$file_group_type][$file_id]['Attribute']['CHECKSUM']     = hash_file('sha256', $path_clean);
				$file_groups[$file_group_type][$file_id]['Attribute']['CHECKSUMTYPE'] = 'SHA-256';
				$file_groups[$file_group_type][$file_id]['Attribute']['MIMETYPE']     = $file_type['type'];
				$file_groups[$file_group_type][$file_id]['Attribute']['SIZE']         = filesize($path_clean);
				$file_groups[$file_group_type][$file_id]['Path']                      = $file_sip_path;
				if($exif && (isset($exif['DateTime']) && $exif['DateTime'])) {
					$file_groups[$file_group_type][$file_id]['Attribute']['CREATED'] = self::date_to_iso8601($exif['DateTime']);
				}
			}
		}

		$creator = get_userdata($this->archival->post_author);
		$author = get_userdata($this->archival->post_author);
		$originator = get_post_meta($this->archival->ID, '_archival_originator', true);
		$archivist = get_userdata(get_current_user_id());

		$archive = get_the_terms($this->archival->ID, 'archive');
		$sip_institution_name = $archive[0]->name;
		$sip_institution_address = array();
		if($archive[0]->description) {
			$sip_institution_address = preg_split("/\r\n|\n|\r/", $archive[0]->description );
		}

		$sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta' );

		$tags = get_the_terms($this->archival->ID, 'archival_tag');

		$archival_area = '';
		if ( $archival_address = get_post_meta($this->archival->ID, '_archival_address', true)) {
			$archival_lat = get_post_meta($this->archival->ID, '_archival_lat', true);
			$archival_lng = get_post_meta($this->archival->ID, '_archival_lng', true);
		} else {
			$archival_area = get_post_meta($this->archival->ID, '_archival_area', true); // todo: escape it!
		}
		$archival_upload_purpose = get_post_meta($this->archival->ID, '_archival_upload_purpose', true);
		$archival_numeration = get_post_meta($this->archival->ID, '_archival_numeration', true);
		$archival_annotation = get_post_meta($this->archival->ID, '_archival_annotation', true);
		$archival_blocking_time = get_post_meta($this->archival->ID, '_archival_blocking_time', true);

		$writer = new XMLWriter;
		$writer->openURI($this->header_dir . 'metadata.xml');
		$writer->setIndent(1);
		$writer->setIndentString(' ');
		$writer->startDocument('1.0', 'UTF-8', 'yes');

		$writer->startElement('mets');
			$writer->startAttribute('xmlns:xsi');
				$writer->text('http://www.w3.org/2001/XMLSchema-instance');
			$writer->endAttribute();
			$writer->startAttribute('xmlns');
				$writer->text('http://www.loc.gov/METS/');
			$writer->endAttribute();
			$writer->startAttribute('xmlns:xlink');
				$writer->text('http://www.w3.org/1999/xlink');
			$writer->endAttribute();
			$writer->startAttribute('xmlns:csip');
				$writer->text('https://DILCIS.eu/XML/METS/CSIPExtensionMETS');
			$writer->endAttribute();
			$writer->startAttribute('xmlns:ead');
				$writer->text('urn:isbn:1-931666-22-9');
			$writer->endAttribute();
			$writer->startAttribute('xsi:schemaLocation');
				$writer->text('http://www.loc.gov/METS/ http://www.loc.gov/standards/mets/version18/mets.xsd urn:isbn:1-931666-22-9 http://www.loc.gov/ead/ead.xsd');
			$writer->endAttribute();
			$writer->startAttribute('OBJID');
				$writer->text('Valid_IP_example');
			$writer->endAttribute();
			$writer->startAttribute('TYPE');
				$writer->text('Databases');
			$writer->endAttribute();
			$writer->startAttribute('PROFILE');
				$writer->text('https://earkcsip.dilcis.eu/profile/E-ARK-CSIP.xml');
			$writer->endAttribute();
			$writer->startElement('metsHdr');
				$writer->startAttribute('ID');
				$writer->text('archival' . $this->archival->ID);
				$writer->endAttribute();
				$writer->startAttribute('CREATEDATE');
				$writer->text(self::date_to_iso8601($this->archival->post_date));
				$writer->endAttribute();
				$writer->startAttribute('LASTMODDATE');
				$writer->text(self::date_to_iso8601($this->archival->post_modified));
				$writer->endAttribute();
				$writer->startAttribute('RECORDSTATUS');
				$writer->text('Complete');
				$writer->endAttribute();
				$writer->startElement('agent');
					$writer->startAttribute('ROLE');
					$writer->text('CREATOR');
					$writer->endAttribute();
					$writer->startAttribute('TYPE');
					$writer->text('INDIVIDUAL');
					$writer->endAttribute();
					$writer->startElement('name');
						$writer->text($author->data->display_name);
					$writer->endElement(); // name
				$writer->endElement(); // agent
				$writer->startElement('agent');
					$writer->startAttribute('ROLE');
					$writer->text('ARCHIVIST');
					$writer->endAttribute();
					$writer->startAttribute('TYPE');
					$writer->text('INDIVIDUAL');
					$writer->endAttribute();
					$writer->startElement('name');
						$writer->text($archivist->data->display_name);
					$writer->endElement(); // name
				$writer->endElement(); // agent
				$writer->startElement('agent');
					$writer->startAttribute('ROLE');
					$writer->text('PRESERVATION');
					$writer->endAttribute();
					$writer->startAttribute('TYPE');
					$writer->text('ORGANIZATION');
					$writer->endAttribute();
					$writer->startElement('name');
						$writer->text($sip_institution_name);
					$writer->endElement(); // name
					foreach ($sip_institution_address as $address_line) {
						$writer->startElement('note');
							$writer->text($address_line);
						$writer->endElement(); // note
					}
				$writer->endElement(); // agent
			$writer->endElement(); // metsHdr

			$writer->startElement('dmdSec');
				$writer->startAttribute('ID');
				$writer->text('DMD' . $this->archival->ID);
				$writer->endAttribute();
				$writer->startElement('mdWrap');
					$writer->startAttribute('MDTYPE');
					$writer->text('EAD');
					$writer->endAttribute();
					$writer->startElement('xmlData');
						$writer->startElement('ead:ead');
							$writer->startElement('ead:eadheader');
								$writer->startElement('ead:eadid');
									$writer->text('EAD' . $this->archival->ID);
								$writer->endElement(); // ead:eadid
								$writer->startElement('ead:filedesc');
									$writer->startElement('ead:titlestmt');
										$writer->startElement('ead:titleproper');
											$writer->text($this->archival->post_title);
										$writer->endElement(); // ead:titleproper
									$writer->endElement(); // ead:titlestmt
									$writer->startElement('ead:publicationstmt');
										$writer->startElement('ead:publisher');
											$writer->text($sip_institution_name);
										$writer->endElement(); // ead:publisher
									$writer->endElement(); // ead:publicationstmt
								$writer->endElement(); // ead:filedesc
							$writer->endElement(); // ead:eadheader
							$writer->startElement('ead:archdesc');
								$writer->startAttribute('level');
									$writer->text('item');
								$writer->endAttribute();
								$writer->startElement('ead:did');
									$writer->startElement('ead:unittitle');
										$writer->text($this->archival->post_title);
									$writer->endElement(); // ead:unittitle
									$writer->startElement('ead:unitid');
										$writer->text('ITEM' . $this->archival->ID);
									$writer->endElement(); // ead:unitid
									if($archival_originator = get_post_meta($this->archival->ID, '_archival_originator', true)) {
										$writer->startElement('ead:origination');
											$writer->startElement('ead:persname');
												$writer->text($archival_originator);
											$writer->endElement(); // ead:persname
										$writer->endElement(); // ead:origination
									}
									foreach ($file_mimes as $mime => $number) {
										$mime_numbers[] = $mime . ', ' . $number;
									}
									$writer->startElement('ead:physdesc');
										$writer->startElement('ead:extent');
											$writer->text(implode('; ', $mime_numbers));
										$writer->endElement(); // ead:extent
									$writer->endElement(); // ead:physdesc
									if($this->archival->post_content) {
										$writer->startElement('ead:abstract');
											$writer->startCdata();
												$writer->text($this->archival->post_content);
											$writer->endCdata();
										$writer->endElement(); // ead:abstract
									}
									$archival_from = get_post_meta($this->archival->ID, '_archival_from', true);
									$archival_to = get_post_meta($this->archival->ID, '_archival_to', true);
									if($archival_from) {
										$writer->startElement('ead:unitdate');
											$writer->startAttribute('type');
												$writer->text('inclusive');
											$writer->endAttribute();
											if(!$archival_to) {
												$writer->text($archival_from);
											} else {
												$writer->text($archival_from . ' - ' . $archival_to);
											}
										$writer->endElement(); // ead:unitdate
									}
									$writer->startElement('ead:physloc');
										$writer->text(get_bloginfo('name'));
									$writer->endElement(); //ead:physloc
								$writer->endElement(); // ead:did
								$writer->startElement('ead:accessrestrict');
									$writer->startElement('ead:head');
										$writer->text('Access Condition');
									$writer->endElement(); //ead:head
									if(is_numeric($archival_blocking_time)) {
										$writer->startElement('ead:p');
											$writer->text('Restricted Access');
											$writer->startElement('ead:extref');
												$writer->startAttribute('xlink:href');
													$writer->text('https://creativecommons.org/licenses/by/4.0/');
												$writer->endAttribute();
												$writer->text('Creative Commons Lizenz (CC BY 4.0)');
											$writer->endElement(); // ead:extref
											$writer->text('Blocking Time ends ');
											$writer->text(date('d. F Y', strtotime($this->archival->post_date) + ($archival_blocking_time * 31536000)));
										$writer->endElement(); // ead:p
									} else {
										$writer->startElement('ead:p');
											$writer->text('Open Access');
											$writer->startElement('ead:extref');
												$writer->startAttribute('xlink:href');
													$writer->text('https://creativecommons.org/licenses/by/4.0/');
												$writer->endAttribute();
												$writer->text('Creative Commons Lizenz (CC BY 4.0)');
											$writer->endElement(); // ead:extref
										$writer->endElement(); // ead:p
									}
								$writer->endElement(); //ead:accessrestrict

								if($tags || $archival_address || ($archival_area && $area = json_decode($archival_area))) {
									$writer->startElement( 'ead:controlaccess' );
										if($archival_address) {
											$writer->startElement('ead:geogname');
												$writer->text($archival_address);
											$writer->endElement(); // ead:geogname
											$writer->startElement('ead:note');
												$writer->startElement('ead:p');
												$writer->text('Coordinates: ' . $archival_lat . ', ' . $archival_lng);
												$writer->endElement(); // ead:p
											$writer->endElement(); // ead:note
										}
										if ($archival_area && $area = json_decode($archival_area)) {
											$writer->startElement('ead:note');
												$writer->startElement('ead:p');
													$writer->text('Coordinates: ' . json_encode($area->geometry->coordinates[0]));
												$writer->endElement(); // ead:p
											$writer->endElement(); // ead:note
										}
										foreach ( $tags as $tag ) {
											$writer->startElement( 'ead:subject' );
											$writer->text( $tag->name );
											$writer->endElement(); // ead:subject
										}
									$writer->endElement(); //ead:controlaccess
								}
								$writer->startElement( 'ead:dao' );
									$writer->startAttribute('xlink:href');
										$writer->text('https://creativecommons.org/licenses/by/4.0/');
									$writer->endAttribute();
									$writer->startAttribute('xlink:show');
										$writer->text('new');
									$writer->endAttribute();
								$writer->endElement(); // ead:dao

								if($sip_custom_meta = carbon_get_theme_option('sip_custom_meta' )) {
									foreach ( $sip_custom_meta as $custom_meta ) {
										$meta_name = sanitize_title( $custom_meta['sip_custom_meta_key'] );
										if ( $archival_custom_meta = get_post_meta( $this->archival->ID, '_archival_' . $meta_name, true ) ) {
											$writer->startElement( 'ead:odd' );
												$writer->startElement( 'ead:head' );
													$writer->text( $custom_meta['sip_custom_meta_title_' . $current_locale] );
												$writer->endElement(); // ead:head
												$writer->startElement( 'ead:p' );
													$writer->startCdata();
														$writer->text( $archival_custom_meta );
													$writer->endCdata();
												$writer->endElement(); // ead:p
											$writer->endElement(); // ead:odd
										}
									}
								}

								if($archival_numeration) {
									$writer->startElement( 'ead:odd' );
										$writer->startElement( 'ead:head' );
											$writer->text(__('Numbering', 'sip'));
										$writer->endElement(); // ead:head
										$writer->startElement( 'ead:p' );
											$writer->startCdata();
												$writer->text($archival_numeration);
											$writer->endCdata();
										$writer->endElement(); // ead:p
									$writer->endElement(); // ead:odd
								}

								if($archival_annotation) {
									$writer->startElement( 'ead:odd' );
										$writer->startElement( 'ead:head' );
											$writer->text(__('Note', 'sip'));
										$writer->endElement(); // ead:head
										$writer->startElement( 'ead:p' );
											$writer->startCdata();
												$writer->text($archival_annotation);
											$writer->endCdata();
										$writer->endElement(); // ead:p
									$writer->endElement(); // ead:odd
								}

								if($sip_custom_archival_user_meta) {
									foreach ( $sip_custom_archival_user_meta as $custom_archival_user_meta ) {
										$meta_name = sanitize_title( $custom_archival_user_meta['sip_custom_archival_user_meta_key'] );
										if ( $archival_custom_meta = get_post_meta( $this->archival->ID, '_archival_' . $meta_name, true ) ) {
											$writer->startElement( 'ead:odd' );
												$writer->startElement( 'ead:head' );
													$writer->text( $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale] );
												$writer->endElement(); // ead:head
												$writer->startElement( 'ead:p' );
													$writer->startCdata();
														$writer->text( $archival_custom_meta );
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
					$writer->startAttribute('ID');
					$writer->text('RIGHTS' . $this->archival->ID);
					$writer->endAttribute();
					$writer->startElement('mdWrap');
						$writer->startAttribute('MDTYPE');
						$writer->text('OTHER');
						$writer->endAttribute();
						$writer->startAttribute('OTHERMDTYPE');
						$writer->text('RIGHTS');
						$writer->endAttribute();
						$writer->startElement('xmlData');
							$writer->startElement('rightsDeclaration');
								$writer->startElement('rightsHolder');
									$writer->startElement('name');
										$writer->text($archival_originator);
									$writer->endElement(); // name
								$writer->endElement(); // rightsHolder
								$writer->startElement('rightsType');
									$writer->text('Copyright');
								$writer->endElement(); // rightsType
							$writer->endElement(); // rightsDeclaration
						$writer->endElement(); // xmlData
					$writer->endElement(); // mdWrap
				$writer->endElement(); // rightsMD
			$writer->endElement(); // amdSec

			$writer->startElement('fileSec');
				foreach ($file_groups as $group => $group_files) {
					$writer->startElement('fileGrp');
						$writer->startAttribute('USE');
						$writer->text($group);
						$writer->endAttribute();
						foreach ($group_files as $id => $file) {
							$writer->startElement('file');
								$writer->startAttribute('ID');
								$writer->text('FILE'.$id);
								$writer->endAttribute();
								foreach ($file['Attribute'] as $attr => $text) {
									$writer->startAttribute($attr);
									$writer->text($text);
									$writer->endAttribute();
								}
								$writer->startElement('FLocat');
									$writer->startAttribute('xlink:href');
									$writer->text($file['Path']);
									$writer->endAttribute();
									$writer->startAttribute('xlink:title');
									$writer->text(($titles[basename($file['Path'])])?:basename($file['Path']));
									$writer->endAttribute();
									$writer->startAttribute('LOCTYPE');
									$writer->text('OTHER');
									$writer->endAttribute();
									$writer->startAttribute('OTHERLOCTYPE');
									$writer->text('SYSTEM');
									$writer->endAttribute();
								$writer->endElement(); // FLocat
							$writer->endElement(); // file
						}
					$writer->endElement(); // fileGrp
				}
			$writer->endElement(); // fileSec

			$writer->startElement('structMap');
				$writer->startAttribute('TYPE');
				$writer->text('PHYSICAL');
				$writer->endAttribute();
				$writer->startAttribute('LABEL');
				$writer->text('CSIP');
				$writer->endAttribute();
				$writer->startElement('div');
					$writer->startAttribute('TYPE');
					$writer->text('Directory');
					$writer->endAttribute();
					$writer->startAttribute('LABEL');
					$writer->text('content');
					$writer->endAttribute();
				$dir_close = true;
				$dir_open = false;
				foreach($files as $path) {
					$path = trim($path);
					$path_clean = substr($path, strpos($path, '/'));
					if( in_array(substr($path_clean, strrpos($path_clean, '/')+1), array('.', '..')) )
						continue;
					$file_sip_path = str_replace($this->content_dir, 'content/', $path_clean);
					$file_id = md5($file_sip_path);
					if(!is_dir($path_clean)) {
						$writer->startElement('div');
							$writer->startAttribute('TYPE');
							$writer->text('Item');
							$writer->endAttribute();
							$writer->startElement('fptr');
								$writer->startAttribute('FILEID');
								$writer->text('FILE'.$file_id);
								$writer->endAttribute();
							$writer->endElement(); // fptr
						$writer->endElement(); // div
						if(strrpos($path, '\\') !== false) {
							$writer->endElement(); // div
						}
					} else {
						if(strrpos($path, '\\') !== false && isset( $ol_open ) && $ol_open === true) {// todo: check for $ol_open. not sure where this var comes from!!!
							$writer->endElement(); // div
							$ol_close = true;
							$ol_open = false;
						}
						$writer->startElement('div');
							$writer->startAttribute('TYPE');
							$writer->text('Directory');
							$writer->endAttribute();
							$writer->startAttribute('LABEL');
							$writer->text(($titles[basename($path_clean)])?:basename($path_clean));
							$writer->endAttribute();
						$dir_close = false;
						$dir_open = true;
					}
				}
				if(!$dir_close) {
					$writer->endElement(); // div
				}
				$writer->endElement(); // div

			$writer->endElement(); // structMap

		$writer->endElement(); // mets

		$writer->endDocument();

		$writer->flush();

	}

	/**
	 * Returns a converted date.
	 * The format is ISO 8601 and looks like: 2004-02-12T15:19:21+00:00
	 */
	private static function date_to_iso8601($date) {
		return date('c', strtotime($date));
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
}
