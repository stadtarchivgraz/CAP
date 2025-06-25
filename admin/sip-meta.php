<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

function sip_meta_field() {
	$current_locale = strtolower(get_locale());
	if($upload_purpose_options = carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale )) {
		$upload_purpose_options = explode("\r\n", $upload_purpose_options );
	} else $upload_purpose_options = array();
	$upload_purpose_options = array_combine($upload_purpose_options, $upload_purpose_options);

	if($blocking_time_options = carbon_get_theme_option( 'sip_blocking_time_options_' . $current_locale )) {
		$blocking_time_options = explode("\r\n",  $blocking_time_options);
	} else $blocking_time_options = array();
	$blocking_time_options = array_combine($blocking_time_options, $blocking_time_options);

	$blocking_time_upload_purpose = carbon_get_theme_option( 'sip_blocking_time_upload_purpose_' . $current_locale );

	$custom_meta = array();
	if($sip_custom_meta = carbon_get_theme_option('sip_custom_meta')) {
		$custom_meta[] = Field::make( 'separator', 'sip_custom_options', __('Custom Meta', 'sip') );
		foreach ($sip_custom_meta as $key => $meta) {
			$custom_meta[] = Field::make( $meta['sip_custom_meta_type'], 'archival_' . sanitize_title($meta['sip_custom_meta_key']), $meta['sip_custom_meta_title_' . $current_locale] );
		}
	}

	$default_meta = array(
		Field::make( 'separator', 'sip_folder_options', __('SIP', 'sip') ),
		Field::make( 'text', 'archival_sip_folder', __('SIP', 'sip') ),
		Field::make( 'separator', 'sip_date_options', __('Date/Time', 'sip') ),
		Field::make( 'date_time', 'archival_from', __('from', 'sip') )
		     ->set_width(50 ),
		Field::make( 'date_time', 'archival_to', __('to', 'sip') )
			->set_width(50),
		Field::make( 'separator', 'sip_place_options', __('Creation Place', 'sip') ),
		Field::make( 'text', 'archival_address', __('Address', 'sip') ),
		Field::make( 'text', 'archival_lat', __('Latitude', 'sip') )
			->set_width(50),
		Field::make( 'text', 'archival_lng', __('Longitude', 'sip') )
			->set_width(50),
		Field::make( 'text', 'archival_area', __('Area', 'sip') ),
		Field::make( 'separator', 'sip_media_options', __('Media', 'sip') ),
		Field::make( 'text', 'archival_originator', __('Originator', 'sip') ),
		Field::make( 'select', 'archival_upload_purpose', __('Upload Purpose', 'sip') )
			->set_width(50)
			->add_options( $upload_purpose_options ),
		Field::make( 'select', 'archival_blocking_time', __('Blocking Time', 'sip') )
		     ->set_width(50)
		     ->add_options( $blocking_time_options )
			 ->set_conditional_logic( array(
				array(
					'field' => 'archival_upload_purpose',
					'value' => $blocking_time_upload_purpose,
					'compare' => '=',
				)
			) ),
		Field::make( 'checkbox', 'archival_right_transfer', __('Right Transfer', 'sip') )
		     ->set_option_value( 'yes' ),
	);

	$custom_archival_user_meta = array();
	$sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta' )   ;
	if($sip_custom_archival_user_meta) {
		$custom_archival_user_meta[] = Field::make( 'separator', 'sip_custom_archival_user_options', __('Custom Meta', 'sip') );
		foreach ($sip_custom_archival_user_meta as $meta) {
			$custom_archival_user_meta[] = Field::make( $meta['sip_custom_archival_user_meta_type'], 'archival_' . sanitize_title($meta['sip_custom_archival_user_meta_key']), $meta['sip_custom_archival_user_meta_title_' . $current_locale] );
		}
	}

	$default_archival_user_meta = array(
		Field::make( 'textarea', 'archival_annotation', __('Note', 'sip') )
			->set_attribute( 'maxLength', 3000 ),
		Field::make( 'text', 'archival_numeration', __('Numbering', 'sip') ),
	);

	Container::make( 'post_meta', __( 'Meta Fields', 'sip' ) )
			->where( 'post_type', '=', 'archival' )
	         ->add_fields( array_merge( $default_meta, $custom_meta ) );

	Container::make( 'post_meta', __( 'Archive information', 'sip' ) )
	         ->where( 'post_type', '=', 'archival' )
	         ->add_fields( array_merge( $default_archival_user_meta, $custom_archival_user_meta ) );
}
add_action( 'carbon_fields_register_fields', 'sip_meta_field' );