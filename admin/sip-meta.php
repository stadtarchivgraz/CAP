<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 *  Returns an URL to the translation page for the form fields of the plugin.
 */
function starg_get_plugin_form_fields_admin_url() {
	if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) { return; }

	return add_query_arg( array( 'post_type' => 'archival', 'page' => 'crb_carbon_fields_container_formularfelder.php', ), esc_url( admin_url( 'edit.php' ) ) );
}

/**
 * Creates the form input fields for submitting an archival in the frontend.
 */
function starg_add_sip_meta_fields() {
	$current_locale = strtolower(get_locale());

	// todo: refactor!
	$upload_purpose_options = array();
	if ( carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale ) ) {
		$upload_purpose_options = carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale );
		$upload_purpose_options = explode( "\r\n", $upload_purpose_options );
	}
	$upload_purpose_options = array_combine( $upload_purpose_options, $upload_purpose_options );

	// todo: refactor!
	$blocking_time_options = array();
	if ( carbon_get_theme_option( 'sip_blocking_time_options_' . $current_locale ) ) {
		$blocking_time_options = carbon_get_theme_option( 'sip_blocking_time_options_' . $current_locale );
		$blocking_time_options = explode( "\r\n",  $blocking_time_options );
	}
	$blocking_time_options = array_combine( $blocking_time_options, $blocking_time_options );

	$blocking_time_upload_purpose = carbon_get_theme_option( 'sip_blocking_time_upload_purpose_' . $current_locale );

	$custom_meta = array();
	if ( $sip_custom_meta = carbon_get_theme_option( 'sip_custom_meta' ) ) {
		$custom_meta[] = Field::make( 'separator', 'sip_custom_options', esc_html__( 'Custom Meta', 'sip' ) );
		foreach ($sip_custom_meta as $key => $meta) {
			$custom_meta[] = Field::make( $meta['sip_custom_meta_type'], 'archival_' . sanitize_title( $meta['sip_custom_meta_key'] ), $meta['sip_custom_meta_title_' . $current_locale] );
		}
	}

	$default_meta = array(
		Field::make('separator', 'sip_folder_options', esc_html__('SIP', 'sip')),
		Field::make('text', 'archival_sip_folder', esc_html__('SIP', 'sip')),
		Field::make('separator', 'sip_date_options', esc_html__('Date/Time', 'sip')),
		Field::make('date_time', 'archival_from', esc_html__('from', 'sip'))
			->set_width(50),
		Field::make('date_time', 'archival_to', esc_html__('to', 'sip'))
			->set_width(50),
		Field::make('separator', 'sip_place_options', esc_html__('Creation Place', 'sip')),
		Field::make('text', 'archival_address', esc_html__('Address', 'sip')),
		Field::make('text', 'archival_lat', esc_html__('Latitude', 'sip'))
			->set_width(50),
		Field::make('text', 'archival_lng', esc_html__('Longitude', 'sip'))
			->set_width(50),
		Field::make('text', 'archival_area', esc_html__('Area', 'sip')),
		Field::make('separator', 'sip_media_options', esc_html__('Media', 'sip')),
		Field::make('text', 'archival_originator', esc_html__('Originator', 'sip')),
		Field::make('select', 'archival_upload_purpose', esc_html__('Upload Purpose', 'sip'))
			->set_width(50)
			->add_options($upload_purpose_options),
		Field::make('select', 'archival_blocking_time', esc_html__('Blocking Time', 'sip'))
			->set_width(50)
			->add_options($blocking_time_options)
			->set_conditional_logic(array(
				array(
					'field' => 'archival_upload_purpose',
					'value' => $blocking_time_upload_purpose,
					'compare' => '=',
				)
			)),
		Field::make('checkbox', 'archival_right_transfer', esc_html__('Right Transfer', 'sip'))
			->set_option_value('yes'),
	);

	$custom_archival_user_meta = array();
	$sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta');
	if ($sip_custom_archival_user_meta) {
		$custom_archival_user_meta[] = Field::make('separator', 'sip_custom_archival_user_options', esc_html__('Custom Meta', 'sip'));
		foreach ($sip_custom_archival_user_meta as $meta) {
			$custom_archival_user_meta[] = Field::make($meta['sip_custom_archival_user_meta_type'], 'archival_' . sanitize_title($meta['sip_custom_archival_user_meta_key']), $meta['sip_custom_archival_user_meta_title_' . $current_locale]);
		}
	}

	$default_archival_user_meta = array(
		Field::make('textarea', 'archival_annotation', esc_html__('Note', 'sip'))
			->set_attribute('maxLength', 3000),
		Field::make('text', 'archival_numeration', esc_html__('Numbering', 'sip')),
	);

	Container::make('post_meta', esc_html__('Meta Fields', 'sip'))
		->where('post_type', '=', 'archival')
		->add_fields(array_merge($default_meta, $custom_meta));

	Container::make('post_meta', esc_html__('Archive information', 'sip'))
		->where('post_type', '=', 'archival')
		->add_fields(array_merge($default_archival_user_meta, $custom_archival_user_meta));
}
add_action( 'carbon_fields_register_fields', 'starg_add_sip_meta_fields' );
