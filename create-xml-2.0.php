<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

function date_to_iso8601($date) {
	return date('c', strtotime($date));
}

$current_locale = strtolower(get_locale());
$files = new RecursiveTreeIterator(new RecursiveDirectoryIterator($content_dir, RecursiveDirectoryIterator::SKIP_DOTS));
$file_groups = array();
$exif = false;

$titles = array();
$names_file = fopen($sip_folder . 'names.csv', 'r');
if($names_file !== FALSE){
	while(($data = fgetcsv($names_file, 100, ',')) !== FALSE){
		$titles[$data[0]] = $data[1];
	}
	fclose($names_file);
}

foreach ($files as $path) {
	$path = trim($path);
	$path_clean = substr($path, strpos($path, '/'));
	if (is_file($path_clean) === true) {
		$file_sip_path = str_replace($content_dir, 'content/', $path_clean);
		$file_type = wp_check_filetype($path_clean);
		$file_id = md5($file_sip_path);
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
		$file_groups[$file_group_type][$file_id]['Attribute']['CHECKSUM'] = hash_file('sha256', $path_clean);
		$file_groups[$file_group_type][$file_id]['Attribute']['CHECKSUMTYPE'] = 'SHA-256';
		$file_groups[$file_group_type][$file_id]['Attribute']['MIMETYPE'] = $file_type['type'];
		$file_groups[$file_group_type][$file_id]['Attribute']['SIZE'] = filesize($path_clean);
		$file_groups[$file_group_type][$file_id]['Path'] = $file_sip_path;
		if($exif && (isset($exif['DateTime']) && $exif['DateTime'])) {
			$file_groups[$file_group_type][$file_id]['Attribute']['CREATED'] = date_to_iso8601($exif['DateTime']);
		}
	}
}

$creator = get_userdata($archival->post_author);
$author = get_userdata($archival->post_author);
$originator = get_post_meta($archival->ID, '_archival_originator', true);
$archivist = get_userdata(get_current_user_id());

$archive = get_the_terms($archival->ID, 'archive');
$sip_institution_name = $archive[0]->name;
$sip_institution_address = array();
if($archive[0]->description) {
	$sip_institution_address = preg_split("/\r\n|\n|\r/", $archive[0]->description );
}

$sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta' );

$tags = get_the_terms($archival->ID, 'archival_tag');

if($archival_address = get_post_meta($archival->ID, '_archival_address', true)) {
	$archival_lat = get_post_meta($archival->ID, '_archival_lat', true);
	$archival_lng = get_post_meta($archival->ID, '_archival_lng', true);
} else {
	$archival_area = get_post_meta($archival->ID, '_archival_area', true);
}
$archival_upload_purpose = get_post_meta($archival->ID, '_archival_upload_purpose', true);
$archival_numeration = get_post_meta($archival->ID, '_archival_numeration', true);
$archival_annotation = get_post_meta($archival->ID, '_archival_annotation', true);
$archival_blocking_time = get_post_meta($archival->ID, '_archival_blocking_time', true);

$writer = new XMLWriter;
$writer->openURI($header_dir . 'metadata.xml');
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
		$writer->text('archival' . $archival->ID);
		$writer->endAttribute();
		$writer->startAttribute('CREATEDATE');
		$writer->text(date_to_iso8601($archival->post_date));
		$writer->endAttribute();
		$writer->startAttribute('LASTMODDATE');
		$writer->text(date_to_iso8601($archival->post_modified));
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
		$writer->text('DMD' . $archival->ID);
		$writer->endAttribute();
		$writer->startElement('mdWrap');
			$writer->startAttribute('MDTYPE');
			$writer->text('EAD');
			$writer->endAttribute();
			$writer->startElement('xmlData');
				$writer->startElement('titleInfo');
					$writer->startElement('title');
						$writer->startCdata();
							$writer->text($archival->post_title);
						$writer->endCdata();
					$writer->endElement(); // title
				$writer->endElement(); // titleInfo
				if($archival_originator = get_post_meta($archival->ID, '_archival_originator', true)) {
					$writer->startElement('name');
						$writer->startAttribute('type');
						$writer->text('personal');
						$writer->endAttribute();
						$writer->startElement('displayForm');
							$writer->text($archival_originator);
						$writer->endElement(); // displayForm
						$writer->startElement('role');
							$writer->startElement('roleTerm');
								$writer->startAttribute('authority');
								$writer->text('marcrelator');
								$writer->endAttribute();
								$writer->startAttribute('type');
								$writer->text('code');
								$writer->endAttribute();
								$writer->text('org');
							$writer->endElement(); // roleTerm
						$writer->endElement(); // role
					$writer->endElement(); // name
				}
				if($archival->post_content) {
					$writer->startElement('abstract');
						$writer->startCdata();
							$writer->text($archival->post_content);
						$writer->endCdata();
					$writer->endElement();
				}
				$archival_from = get_post_meta($archival->ID, '_archival_from', true);
				$archival_to = get_post_meta($archival->ID, '_archival_to', true);
				if($archival_from) {
					$writer->startElement('originInfo');
						$writer->startAttribute('eventType');
						$writer->text('production');
						$writer->endAttribute();
						if(!$archival_to) {
							$writer->startElement('dateCreated');
								$writer->startAttribute('encoding');
								$writer->text('iso8601');
								$writer->endAttribute();
								$writer->text($archival_from);
							$writer->endElement(); // dateCreated
						} else {
							$writer->startElement('dateCreated');
								$writer->startAttribute('encoding');
								$writer->text('iso8601');
								$writer->endAttribute();
								$writer->startAttribute('point');
								$writer->text('start');
								$writer->endAttribute();
								$writer->text(date_to_iso8601($archival_from));
							$writer->endElement(); // dateCreated
							$writer->startElement('dateCreated');
								$writer->startAttribute('encoding');
								$writer->text('iso8601');
								$writer->endAttribute();
								$writer->startAttribute('point');
								$writer->text('end');
								$writer->endAttribute();
								$writer->text(date_to_iso8601($archival_to));
							$writer->endElement(); // dateCreated
							$writer->startElement('displayDate');
								$year_from = date('Y', strtotime($archival_from));
								$year_to = date('Y', strtotime($archival_to));
								if($year_from != $year_to) {
									$writer->text($year_from . ' - ' . $year_to);
								} else {
									$writer->text($year_from);
								}
							$writer->endElement(); // displayDate
						}
					$writer->endElement(); // originInfo
				}
				if($tags) {
					$writer->startElement('subject');
						foreach ($tags as $tag) {
							$writer->startElement('topic');
								$writer->text($tag->name);
							$writer->endElement(); // topic
						}
					$writer->endElement(); // subject
				}
				if($archival_address) {
					$writer->startElement('subject');
						$writer->startElement('geographic');
							$writer->text($archival_address);
						$writer->endElement(); // geographic
						$writer->startElement('cartographics');
							$writer->startElement('coordinates');
								$writer->text($archival_lat . ', ' . $archival_lng);
							$writer->endElement(); // coordinates
						$writer->endElement(); // cartographics
					$writer->endElement(); // subject
				} elseif ($archival_area && $area = json_decode($archival_area)) {
					$writer->startElement('subject');
						$writer->startElement('cartographics');
							$writer->startElement('coordinates');
								$writer->text(json_encode($area->geometry->coordinates[0]));
							$writer->endElement(); // coordinates
						$writer->endElement(); // cartographics
					$writer->endElement(); // subject
				}
				if(is_numeric($archival_blocking_time)) {
					$writer->startElement('accessCondition');
						$writer->startAttribute('type');
						$writer->text('restriction on access');
						$writer->endAttribute();
						$writer->startAttribute('xlink:href');
						$writer->text('https://creativecommons.org/licenses/by/4.0/');
						$writer->endAttribute();
						$writer->startAttribute('displayLabel');
						$writer->text('Access Status');
						$writer->endAttribute();
						$writer->text('Restricted Access');
					$writer->endElement(); // accessCondition
					$writer->startElement('originInfo');
						$writer->startAttribute('eventType');
						$writer->text('publication');
						$writer->endAttribute();
						$writer->startElement('dateOther');
							$writer->startAttribute('encoding');
							$writer->text('iso8601');
							$writer->endAttribute();
							$writer->startAttribute('type');
							$writer->text('Blocking Time');
							$writer->endAttribute();
							$writer->startAttribute('point');
							$writer->text('end');
							$writer->endAttribute();
						$writer->text(date('c', strtotime($archival->post_date) + ($archival_blocking_time * 31536000)));
						$writer->endElement(); // dateCreated
					$writer->endElement(); // originInfo
				} else {
					$writer->startElement('accessCondition');
						$writer->startAttribute('type');
						$writer->text('restriction on access');
						$writer->endAttribute();
						$writer->startAttribute('xlink:href');
						$writer->text('https://creativecommons.org/licenses/by/4.0/');
						$writer->endAttribute();
						$writer->startAttribute('displayLabel');
						$writer->text('Access Status');
						$writer->endAttribute();
						$writer->text('Open Access');
					$writer->endElement(); // accessCondition
				}
				if($sip_custom_meta = carbon_get_theme_option('sip_custom_meta' )) {
					foreach ( $sip_custom_meta as $custom_meta ) {
						$meta_name = sanitize_title( $custom_meta['sip_custom_meta_key'] );
						if ( $archival_custom_meta = get_post_meta( $archival->ID, '_archival_' . $meta_name, true ) ) {
							$writer->startElement( 'node' );
							$writer->startAttribute( 'type' );
							$writer->text( 'source note' );
							$writer->endAttribute();
							$writer->startAttribute( 'displayLabel' );
							$writer->text( $custom_meta['sip_custom_meta_title_' . $current_locale] );
							$writer->endAttribute();
							$writer->startCdata();
							$writer->text( $archival_custom_meta );
							$writer->endCdata();
							$writer->endElement(); // node
						}
					}
				}
				if($archival_numeration) {
					$writer->startElement('node');
						$writer->startAttribute('type');
						$writer->text('numbering');
						$writer->endAttribute();
						$writer->startAttribute('displayLabel');
						$writer->text(__('Numbering', 'sip'));
						$writer->endAttribute();
						$writer->text($archival_numeration);
					$writer->endElement(); // node
				}
				if($archival_annotation) {
					$writer->startElement('node');
						$writer->startAttribute('type');
						$writer->text('source note');
						$writer->endAttribute();
						$writer->startAttribute('displayLabel');
						$writer->text(__('Note', 'sip'));
						$writer->endAttribute();
						$writer->startCdata();
							$writer->text($archival_annotation);
						$writer->endCdata();
					$writer->endElement(); // node
				}
				if($sip_custom_archival_user_meta) {
					foreach ( $sip_custom_archival_user_meta as $custom_archival_user_meta ) {
						$meta_name = sanitize_title( $custom_archival_user_meta['sip_custom_archival_user_meta_key'] );
						if ( $archival_custom_meta = get_post_meta( $archival->ID, '_archival_' . $meta_name, true ) ) {
							$writer->startElement( 'node' );
							$writer->startAttribute( 'type' );
							$writer->text( 'source note' );
							$writer->endAttribute();
							$writer->startAttribute( 'displayLabel' );
							$writer->text( $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale] );
							$writer->endAttribute();
							$writer->startCdata();
							$writer->text( $archival_custom_meta );
							$writer->endCdata();
							$writer->endElement(); // node
						}
					}
				}
			$writer->endElement(); // xmlData
		$writer->endElement(); // mdWrap
	$writer->endElement(); // dmdSec

	$writer->startElement('amdSec');
		$writer->startElement('rightsMD');
			$writer->startAttribute('ID');
			$writer->text('RIGHTS' . $archival->ID);
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
						$writer->text($id);
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
			$file_sip_path = str_replace($content_dir, 'content/', $path_clean);
			$file_id = md5($file_sip_path);
			if(!is_dir($path_clean)) {
				$writer->startElement('div');
					$writer->startAttribute('TYPE');
					$writer->text('Item');
					$writer->endAttribute();
					$writer->startElement('fptr');
						$writer->startAttribute('FILEID');
						$writer->text($file_id);
						$writer->endAttribute();
					$writer->endElement(); // fptr
				$writer->endElement(); // div
				if(strrpos($path, '\\') !== false) {
					$writer->endElement(); // div
				}
			} else {
				if(strrpos($path, '\\') !== false && $ol_open === true) {
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