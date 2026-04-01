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
	protected string $author_nickname = '';
	protected string $author_last_name = '';
	protected string $author_first_name = '';

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
			$this->set_error_log_message( esc_attr__( 'User-Input not valid.', 'sip' ) );
			return false;
		}

		$missing_inputs = $this->user_input_required( $user_input );
		if ( ! empty( $missing_inputs ) ) {
			$this->set_notification_for_missing_inputs( $missing_inputs );
			return false;// todo: maybe change to $user_input to be able to fill in the validated data for the user.
		}

		$sip_user_folder_id = $user_input[ 'sipFolder' ];
		$archival_id        = DB_Query_Helper::starg_get_archival_id_by_sip_folder( $sip_user_folder_id );
		if ( ! $archival_id ) {
			// translators: %s: ID/Name of the folder where the sip is stored.
			$this->set_error_message( sprintf( esc_attr__( 'No archival record found with SIP-ID: "%s"', 'sip' ), $sip_user_folder_id ) );
			$this->set_error_log_message( sprintf( esc_attr__( 'No archival record found with SIP-ID: "%s"', 'sip' ), $sip_user_folder_id ) );
			return false;
		}

		// $archive = get_the_terms($archival_id, 'archive');

		// $sip_institution = '';
		// if ( ! is_wp_error( $archive ) ) {
		// 	$sip_institution = strtoupper( esc_attr( carbon_get_term_meta( $archive[0]->term_id, 'sip_institution') ) );
		// 	$sip_referenz    = esc_attr( carbon_get_term_meta( $archive[0]->term_id, 'sip_referenz' ) );
		// }

		$this->archival          = get_post( $archival_id );
		$this->author_nickname   = esc_attr( trim( get_user_meta( $this->archival->post_author, 'nickname', true ) ) );
		$this->author_last_name  = esc_attr( trim( get_user_meta( $this->archival->post_author, 'last_name', true ) ) );
		$this->author_first_name = esc_attr( trim( get_user_meta( $this->archival->post_author, 'first_name', true ) ) );
		$author_name             = $this->author_nickname;
		if ( $this->author_last_name && $this->author_first_name ) {
			$author_name = $this->author_last_name . '_' . $this->author_first_name;
		}

		$this->sip_folder  = starg_get_archival_upload_path() . $this->archival->post_author . '/' . $sip_user_folder_id . '/';
		$this->content_dir = $this->sip_folder . 'content/';
		$this->header_dir  = $this->sip_folder . 'header/';

		if ( ! file_exists($this->header_dir)) {
			mkdir( $this->header_dir, Starg_Security_Settings::STARG_FOLDER_PERMISSIONS );
		}

		$this->create_xml();

		$zip      = new ZipArchive;
		$tmp_file = $this->content_dir . 'sip_' . $this->archival->ID . '.zip';
		if ($zip->open($tmp_file, ZipArchive::CREATE)) {
			$files = new RecursiveTreeIterator(new RecursiveDirectoryIterator($this->content_dir, RecursiveDirectoryIterator::SKIP_DOTS));//todo: change to RecursiveIteratorIterator.

			foreach ($files as $path) {
				$path = trim($path);
				$path_clean = substr($path, strpos($path, '/'));

				if ( is_dir( $path_clean ) ) {
					$zip->addEmptyDir(str_replace($this->content_dir, 'content/', $path_clean . '/'));
				} else if ( is_file( $path_clean ) ) {
					$zip->addFromString(str_replace($this->content_dir, 'content/', $path_clean), file_get_contents($path_clean));
				}
			}

			$zip->addFile($this->header_dir . 'metadata.xml', 'header/metadata.xml');

			$zip->close();
			//header('Content-disposition: attachment; filename=SIP_' . $this->archival->ID . '_' . $sip_institution . '_' . $sip_referenz . '.zip');
			header('Content-disposition: attachment; filename=' . $author_name . '_' . $this->archival->ID . '.zip');
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
	private function create_xml() {
		$current_locale = strtolower(get_locale());
		$files          = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->content_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
		$file_groups    = array();
		$file_mimes     = array();
		[ $file_groups, $file_mimes ] = self::get_file_information( $files );

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

		$author_name = ( $this->author_last_name && $this->author_first_name )
			? $this->author_last_name . ', ' . $this->author_first_name
			: $this->author_nickname;

		// todo: subject to change. We should save the editor who accepts the submission as the original archivist!
		$archivist_id         = get_current_user_id();
		$archivist_last_name  = esc_attr( trim( get_user_meta( $archivist_id, 'last_name', true ) ) );
		$archivist_first_name = esc_attr( trim( get_user_meta( $archivist_id, 'first_name', true ) ) );
		$archivist_name       = ( $archivist_last_name && $archivist_first_name ) ? $archivist_last_name . ', ' . $archivist_first_name : get_userdata( $archivist_id )->data->display_name;

		// todo: name of originator might be in wrong format. Should be "last name, first name".
		$originator = esc_attr( get_post_meta($this->archival->ID, '_archival_originator', true) );
		if ( ! trim( $originator ) ) {
			$originator = $author_name;
		}
		$post_content = wp_kses_data( $this->archival->post_content );

		$archive = get_the_terms($this->archival->ID, 'archive');
		$sip_institution_name = $archive[0]->name;
		$sip_institution_address = array();
		if($archive[0]->description) {
			$sip_institution_address = preg_split("/\r\n|\n|\r/", $archive[0]->description );
		}

		$sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta' );

		$tags = get_the_terms($this->archival->ID, 'archival_tag');

		$archival_area = '';
		if ( $archival_address = esc_attr( get_post_meta($this->archival->ID, '_archival_address', true))) {
			$archival_lat = esc_attr( get_post_meta($this->archival->ID, '_archival_lat', true));
			$archival_lng = esc_attr( get_post_meta($this->archival->ID, '_archival_lng', true));
		} else {
			$archival_area = get_post_meta($this->archival->ID, '_archival_area', true);
		}
		$archival_upload_purpose = esc_attr( get_post_meta($this->archival->ID, '_archival_upload_purpose', true));
		$archival_numeration     = esc_attr( get_post_meta($this->archival->ID, '_archival_numeration', true));
		$archival_annotation     = wp_kses_post( get_post_meta($this->archival->ID, '_archival_annotation', true));
		$archival_blocking_time  = esc_attr( get_post_meta($this->archival->ID, '_archival_blocking_time', true));

		// todo: maybe add plugin option for copyright.
		$copyright_url  = esc_url_raw( 'https://rightsstatements.org/vocab/InC/1.0/' );
		$copyright_text = esc_attr__( 'In Copyright', 'sip' );

		// todo: refactor!
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
				$writer->writeAttribute('ID',  'archival' . $this->archival->ID );
				$writer->writeAttribute('CREATEDATE', self::date_to_dmY($this->archival->post_date));
				$writer->writeAttribute('LASTMODDATE', self::date_to_dmY($this->archival->post_modified));
				$writer->writeAttribute('RECORDSTATUS', 'Complete');
				$writer->startElement('agent');
					$writer->writeAttribute('ROLE', 'CREATOR');
					$writer->writeAttribute('TYPE', 'INDIVIDUAL');
					$writer->writeElement('name', esc_attr( $author_name ) );
				$writer->endElement(); // agent
				$writer->startElement('agent');
					$writer->writeAttribute('ROLE', 'ARCHIVIST');
					$writer->writeAttribute('TYPE', 'INDIVIDUAL');
					$writer->writeElement('name', esc_attr( $archivist_name ));
				$writer->endElement(); // agent
				$writer->startElement('agent');
					$writer->writeAttribute('ROLE', 'PRESERVATION');
					$writer->writeAttribute('TYPE', 'ORGANIZATION');
					$writer->writeElement('name', esc_attr( $sip_institution_name ));
					foreach ($sip_institution_address as $address_line) {
						$single_line = trim( $address_line );
						if ( ! $single_line ) { continue; }
						$writer->writeElement('note', esc_attr( $single_line ));
					}
				$writer->endElement(); // agent
			$writer->endElement(); // metsHdr

			$writer->startElement('dmdSec');
				$writer->writeAttribute('ID', 'DMD' . $this->archival->ID);
				$writer->startElement('mdWrap');
					$writer->writeAttribute('MDTYPE', 'EAD');
					$writer->startElement('xmlData');
						$writer->startElement('ead:ead');
							$writer->startElement('ead:eadheader');
								$writer->writeElement('ead:eadid', 'EAD' . $this->archival->ID);
								$writer->startElement('ead:filedesc');
									$writer->startElement('ead:titlestmt');
										$writer->writeElement('ead:titleproper',  esc_attr( $this->archival->post_title ) );
									$writer->endElement(); // ead:titlestmt
									$writer->startElement('ead:publicationstmt');
										$writer->writeElement('ead:publisher', $sip_institution_name);
									$writer->endElement(); // ead:publicationstmt
								$writer->endElement(); // ead:filedesc
							$writer->endElement(); // ead:eadheader
							$writer->startElement('ead:archdesc');
								$writer->writeAttribute('level', 'file');
								$writer->startElement('ead:did');
									$writer->writeElement( 'ead:unittitle',  esc_attr( $this->archival->post_title ) );
									$writer->writeElement( 'ead:unitid', 'ITEM' . $this->archival->ID );
									if( $originator ) {
										$writer->startElement('ead:origination');
											$writer->writeElement('ead:persname',$originator);
										$writer->endElement(); // ead:origination
									}
									if ( $file_mimes ) {
										$writer->startElement('ead:physdesc');
											$mime_counter = 0;
											$total_files  = 0;
											$max_files    = is_countable( $file_mimes ) ? count( $file_mimes ) : 1;
											$delimiter    = ', ';
											foreach ($file_mimes as $mime => $number) {
												$mime_counter++;
												$total_files += (int) $number;
												// translators: Text in the physical description (ead:physdesc) of the XML. %1$s: Number of files for the MIME type. %2$s: MIME type.
												$writer->text( sprintf( _n( '%1$s %2$s', '%1$s %2$s', $number, 'sip' ), $number, $mime ) );
												if ( $mime_counter < $max_files ) {
													$writer->text( $delimiter );
												}
											}
											$writer->text( $delimiter );
											// translators: Text in the physical description (ead:physdesc) of the XML. %s: Number of files in total.
											$writer->text( sprintf( esc_attr__( '%s total', 'sip' ), $total_files ) );
										$writer->endElement(); // ead:physdesc
									}
									$archival_from = get_post_meta($this->archival->ID, '_archival_from', true);
									$archival_to   = get_post_meta($this->archival->ID, '_archival_to', true);
									if($archival_from) {
										$writer->startElement('ead:unitdate');
											$writer->writeAttribute('type', 'inclusive');
											if( ! $archival_to ) {
												$writer->text( self::date_to_dmY( esc_attr( $archival_from ) ) );
											} else {
												$writer->text( self::date_to_dmY( esc_attr( $archival_from ) )  . ' - ' . self::date_to_dmY( esc_attr( $archival_to ) ) );
											}
										$writer->endElement(); // ead:unitdate
									}
									$writer->writeElement('ead:physloc', get_bloginfo('name'));
								$writer->endElement(); // ead:did

								if( $post_content ) {
									$writer->startElement('ead:scopecontent');
										$writer->startElement('ead:p');
											$writer->startCdata();
												$writer->text($post_content);
											$writer->endCdata();
										$writer->endElement(); // ead:p
									$writer->endElement(); // ead:scopecontent
								}

								if ( $archival_upload_purpose ) {
									$writer->startElement('ead:custodhist');
										$writer->writeElement('ead:p', $archival_upload_purpose);
									$writer->endElement(); // ead:custodhist
								}

								$writer->startElement('ead:accessrestrict');
									$writer->writeElement('ead:head', esc_attr__( 'Access Condition', 'sip' ));

									$writer->startElement( 'ead:legalstatus' );
										$writer->startElement( 'ead:p' );
											$writer->startElement('ead:extref');
												$writer->writeAttribute('xlink:href', $copyright_url);
												$writer->text($copyright_text);
											$writer->endElement(); // ead:extref
										$writer->endElement(); // 'ead:p'
									$writer->endElement(); //ead:legalstatus

									if ( is_numeric( $archival_blocking_time ) ) {
										$writer->startElement('ead:chronlist');
											$writer->startElement('ead:chronitem');
												$writer->startElement('ead:date');
													$writer->startAttribute('type');
														$writer->text('embargoPeriod');
													$writer->endAttribute();
													$writer->text( $archival_blocking_time );
												$writer->endElement(); //ead:date
											$writer->endElement(); //ead:chronitem
											$writer->startElement('ead:chronitem');
												$writer->startElement('ead:date');
													$writer->startAttribute('type');
														$writer->text('restrictedUntil');
													$writer->endAttribute();
													// todo: maybe change date from post_date to _archival_first_submission.
													$writer->text( date('d.m.Y', strtotime($this->archival->post_date) + ($archival_blocking_time * 31536000)) );
												$writer->endElement(); //ead:date
											$writer->endElement(); //ead:chronitem
										$writer->endElement(); // ead:chronlist
									}
								$writer->endElement(); //ead:accessrestrict

								if($tags || $archival_address || ($archival_area && $area = json_decode($archival_area))) {
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
										foreach ( $tags as $tag ) {
											$writer->startElement( 'ead:subject' );
												$writer->text( $tag->name );
											$writer->endElement(); // ead:subject
										}
									$writer->endElement(); //ead:controlaccess
								}

								$writer->startElement( 'ead:dao' );
									$writer->writeAttribute('xlink:href', $copyright_url);
									$writer->writeAttribute('xlink:show', 'new');
								$writer->endElement(); // ead:dao

								if($sip_custom_meta = carbon_get_theme_option('sip_custom_meta' )) {
									foreach ( $sip_custom_meta as $custom_meta ) {
										$meta_name = sanitize_title( $custom_meta['sip_custom_meta_key'] );
										if ( $archival_custom_meta = get_post_meta( $this->archival->ID, '_archival_' . $meta_name, true ) ) {
											$writer->startElement( 'ead:odd' );
												$writer->writeElement( 'ead:head', $custom_meta['sip_custom_meta_title_' . $current_locale]  );
												$writer->startElement( 'ead:p' );
													$writer->writeCdata( esc_attr( $archival_custom_meta ) );
												$writer->endElement(); // ead:p
											$writer->endElement(); // ead:odd
										}
									}
								}

								if($archival_numeration) {
									$writer->startElement( 'ead:odd' );
										$writer->writeElement( 'ead:head', esc_attr__('Numbering', 'sip') );
										$writer->writeElement( 'ead:p', $archival_numeration );
									$writer->endElement(); // ead:odd
								}

								if($archival_annotation) {
									$writer->startElement( 'ead:appraisal' );
										$writer->writeElement( 'ead:head', esc_attr__('Note', 'sip') );
										$writer->startElement( 'ead:p' );
											$writer->writeCdata( $archival_annotation );
										$writer->endElement(); // ead:p
									$writer->endElement(); // ead:appraisal
								}

								if($sip_custom_archival_user_meta) {
									foreach ( $sip_custom_archival_user_meta as $custom_archival_user_meta ) {
										$meta_name = sanitize_title( $custom_archival_user_meta['sip_custom_archival_user_meta_key'] );
										if ( $archival_custom_meta = get_post_meta( $this->archival->ID, '_archival_' . $meta_name, true ) ) {
											$writer->startElement( 'ead:odd' );
												$writer->startElement( 'ead:head' );
													$writer->text( esc_attr( $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale] ) );
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
					$writer->writeAttribute('ID', 'RIGHTS' . $this->archival->ID);
					$writer->startElement('mdWrap');
						// NOTE: This is valid though not the typical METS-way to declare rights. Usually METS suggests PREMIS.
						$writer->writeAttribute('MDTYPE', 'OTHER');
						$writer->writeAttribute('OTHERMDTYPE', 'RIGHTS');
						$writer->startElement('xmlData');
							$writer->startElement('rightsDeclaration');
								$writer->startElement('rightsHolder');
									$writer->writeElement('rightsHolderName', $originator);
								$writer->endElement(); // rightsHolder
								$writer->writeElement('rightsType', $copyright_text);
							$writer->endElement(); // rightsDeclaration
						$writer->endElement(); // xmlData
					$writer->endElement(); // mdWrap
				$writer->endElement(); // rightsMD
			$writer->endElement(); // amdSec

			$writer->startElement('fileSec');
				foreach ($file_groups as $group => $group_files) {
					$writer->startElement('fileGrp');
						$writer->writeAttribute('USE', $group);
						foreach ($group_files as $id => $file) {
							$single_file_name = ( isset( $titles[ basename( $file['Path'] ) ] ) ) ? $titles[ basename( $file['Path'] ) ] : basename( $file['Path'] );
							$writer->startElement('file');
								$writer->writeAttribute('ID', 'FILE' . $id);
								if ( isset( $file['Attribute'] ) ) {
									foreach ($file['Attribute'] as $attr => $text) {
										$writer->startAttribute( esc_attr( $attr ) );
											if ( 'CREATED' === $attr ) {
												$writer->text( self::date_to_Ymd( esc_attr( $text ) ) );
											} else {
												$writer->text( esc_attr( $text ) );
											}
										$writer->endAttribute();
									}
								}
								$writer->startElement('FLocat');
									$writer->writeAttribute('xlink:href', esc_attr( $file['Path'] ));
									$writer->writeAttribute('xlink:title', esc_attr( $single_file_name ));
									$writer->writeAttribute('LOCTYPE', 'OTHER');
									$writer->writeAttribute('OTHERLOCTYPE', 'SYSTEM');
								$writer->endElement(); // FLocat
							$writer->endElement(); // file
						}
					$writer->endElement(); // fileGrp
				}
			$writer->endElement(); // fileSec

			$writer->startElement('structMap');
				$writer->writeAttribute('TYPE', 'PHYSICAL');
				$writer->writeAttribute('LABEL', 'CSIP');

				$writer->startElement('div');
					$writer->writeAttribute('TYPE', 'Directory');
					$writer->writeAttribute('LABEL', 'content');

					$prevDepth = 0;
					foreach ($files as $file_info) {
						$depth = $files->getDepth();

						if ($depth < $prevDepth) {
							for ($i = $prevDepth; $i > $depth; $i--) {
								$writer->endElement(); // close div!
							}
						}

						$fullPath     = $file_info->getPathname();
						$relativePath = str_replace($this->content_dir, 'content/', $fullPath);
						$fileId       = md5($relativePath);

						if ($file_info->isDir()) {

							$writer->startElement('div');
							$writer->writeAttribute('TYPE', 'Directory');
							$writer->writeAttribute(
								'LABEL',
								$titles[basename($fullPath)] ?? basename($fullPath)
							);

						} else {
							$writer->startElement('div');
								$writer->writeAttribute('TYPE', 'Item');
								$writer->startElement('fptr');
									$writer->writeAttribute('FILEID', 'FILE' . $fileId);
								$writer->endElement(); // fptr
							$writer->endElement(); // div
						}

						$prevDepth = $depth;
					}

				// close all open directories.
				for ($i = $prevDepth; $i >= 0; $i--) {
					$writer->endElement();
				}

			$writer->endElement(); // structMap

		$writer->endElement(); // mets

		$writer->endDocument();

		$writer->flush();

	}

	/**
	 * Loop through the uploaded files and get metadata.
	 * @return array
	 */
	private function get_file_information( $files ) {
		$file_groups    = array();
		$file_mimes     = array();

		foreach ( $files as $file_info ) {
			$path_clean = $file_info->getPathname();
			if ( ! $file_info->isFile() ) {
				continue;
			}

			$file_sip_path  = str_replace( $this->content_dir, 'content/', $path_clean );
			$file_extension = strtoupper( pathinfo( $path_clean, PATHINFO_EXTENSION ) );

			if ( ! isset($file_mimes[$file_extension] ) ) {
				$file_mimes[$file_extension] = 0;
			}
			$file_mimes[$file_extension]++;

			$file_type = wp_check_filetype($path_clean);
			$mime_type = $file_type['type'] ?? 'application/octet-stream';
			$file_id   = md5($file_sip_path);

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
					'SIZE'         => filesize($path_clean),
				),
				'Path' => $file_sip_path,
			);

			$created = self::get_file_creation_date( $path_clean, $mime_type, $file_info );

			if ( $created !== null ) {
				$file_groups[$file_group_type][$file_id]['Attribute']['CREATED'] = $created;
			}
		}

		return array( $file_groups, $file_mimes );
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
	 * The format is ISO 8601 and looks like: 2004-02-12T15:19:21+00:00
	 */
	private static function date_to_Ymd($date) {
		return date('Y,md', strtotime($date));
	}

	/**
	 * Returns a converted date.
	 * The format is ISO 8601 and looks like: 2004-02-12T15:19:21+00:00
	 */
	private static function date_to_dmY($date) {
		return date('d.m.Y', strtotime($date));
	}

	/**
	 * Return the creation date for files.
	 * @todo add other mime_types like ms-office files.
	 */
	protected static function get_file_creation_date( $path, $mime_type, $file_info = '' ) {
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

	protected static function parse_pdf_date($pdfDate) {
		// Format: YYYYMMDDHHmmSSOHH'mm'
		if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})?/', $pdfDate, $m)) {
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

	protected static function get_audio_date($path) {
		if ( ! class_exists('\getID3')) { return null; }

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

		// Fallback: allgemeine Metadaten
		if ( ! empty($info['comments']['year'][0])) {
			return self::date_to_iso8601($info['comments']['year'][0]);
		}

		return null;
	}

	/**
	 * Fallback to get the information from the filesystem.
	 * As this isn't the real creation date, this method is not in use atm.
	 */
	protected static function get_fallback_creation_date( $file_info ) {
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

	protected function get_required_input_names() : array {
		return array( 'sipFolder' => true, );
	}
}
