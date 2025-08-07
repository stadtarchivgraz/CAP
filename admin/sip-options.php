<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Creates the general settings backend page for the CAP-plugin.
 * @todo: add some more help text for the second tab (Information Texts) with ->set_help_text()
 */
function starg_attach_general_plugin_settings() {
	$roles_capability = array(
		'manage_options'    => __( 'Administrator', 'sip' ),
		'edit_others_posts' => __( 'Editor', 'sip' ),
		'publish_posts'     => __( 'Author', 'sip' ),
		'edit_posts'        => __( 'Contributor', 'sip' ),
		'read'              => __( 'Subscriber', 'sip' ),
	);
	$wp_upload_dir      = wp_get_upload_dir();
	$default_path       = $wp_upload_dir['basedir'] . '/archival/';
	$default_mime_types = 'application/pdf
text/plain
image/jpeg
image/png
audio/mp3
audio/mp4
video/mp4';

	$sip_archive_languages = starg_get_enabled_languages();

	$login_fields = array();
	foreach ( $sip_archive_languages as $language ) {
		$language = str_replace( 'sip-', '', $language );
		$login_fields[] = Field::make( 'separator', 'from_language_' . strtolower( $language ), $language );
		$login_fields[] = Field::make( 'rich_text', 'sip_register_text_' . strtolower( $language ), __('Register Text', 'sip') . ' ' . $language );
		$login_fields[] = Field::make( 'rich_text', 'sip_update_profile_text_' . strtolower( $language ), __('Update Profile Text', 'sip') . ' ' . $language );
		$login_fields[] = Field::make( 'rich_text', 'sip_privacy_policy_approval_text_' . strtolower( $language ), __('Privacy Policy Approval Text', 'sip') . ' ' . $language );
		$login_fields[] = Field::make( 'rich_text', 'sip_cron_deleted_text_' . strtolower( $language ), __('SIP Folder deleted Text', 'sip') . ' ' . $language );
	}

	Container::make('theme_options', __('General options', 'sip'))
		->set_page_parent('edit.php?post_type=archival')
		->add_tab(
			__('Application', 'sip'),
			array(
				Field::make('separator', 'sip_archive', 'Archive'),
				Field::make('select', 'sip_archive_role', __('Role', 'sip'))
					->add_options($roles_capability),
				Field::make('separator', 'sip_upload', 'Upload'),
				Field::make('text', 'sip_upload_path', __('Archival Upload Path', 'sip'))
					->set_default_value($default_path),
				Field::make('text', 'sip_max_size', __('Max SIP Size in Byte', 'sip'))
					->set_default_value('50000000')
					->set_width(25),
				Field::make('textarea', 'sip_mime_types', __('Supported file MIME Types', 'sip'))
					->set_default_value($default_mime_types),
				Field::make('checkbox', 'sip_clamav', __('Virus Check with ClamAV', 'sip')),
				Field::make('text', 'sip_clamav_host', 'ClamAV Host')
					->set_conditional_logic(array(
						array(
							'field' => 'sip_clamav',
							'value' => true,
						)
					))
					->set_default_value('localhost')
					->set_width(50),
				Field::make('text', 'sip_clamav_port', 'ClamAV Port')
					->set_conditional_logic(array(
						array(
							'field' => 'sip_clamav',
							'value' => true,
						)
					))
					->set_default_value('3310')
					->set_width(50),
				Field::make('checkbox', 'sip_cron_delete', __('Automatically delete uploaded files', 'sip')),
				Field::make('text', 'sip_cron_delete_days', __('older than days', 'sip'))
					->set_conditional_logic(array(
						array(
							'field' => 'sip_cron_delete',
							'value' => true,
						)
					))
					->set_attribute('type', 'number')
					->set_attribute('min', 1)
					->set_attribute('step', 1)
					->set_default_value('30')
					->set_width(50),
				Field::make('multiselect', 'sip_cron_delete_status',  __('Archival Status', 'sip'))
					->set_conditional_logic(array(
						array(
							'field' => 'sip_cron_delete',
							'value' => true,
						)
					))
					->add_options(array(
						'upload' => __('Uploads'),
						'draft' => __('Draft'),
						'pending' => __('Pending'),
						'publish' => __('Published'),
					))
					->set_default_value('3310')
					->set_width(50),
				Field::make('separator', 'sip_map_options', __('Map', 'sip')),
				Field::make('text', 'sip_map_google_api_key', __('Google API Key for reverse Geocoding', 'sip')),
				Field::make('text', 'sip_map_default_lat', __('Default Lat', 'sip'))
					->set_default_value('47.06745752167981')
					->set_width(33),
				Field::make('text', 'sip_map_default_lng', __('Default Lng', 'sip'))
					->set_default_value('15.441103960661826')
					->set_width(33),
				Field::make('text', 'sip_map_default_zoom', __('Default zoom', 'sip'))
					->set_attribute('type', 'number')
					->set_attribute('min', 1)
					->set_attribute('max', 22)
					->set_attribute('step', 1)
					->set_default_value('10')
					->set_width(33),
				Field::make('separator', 'sip_style_options', 'Style'),
				Field::make('header_scripts', 'sip_custom_style', __('Custom CSS', 'sip'))
			)
		)
		->add_tab(__('Information Texts', 'sip'), $login_fields);
}
add_action( 'carbon_fields_register_fields', 'starg_attach_general_plugin_settings' );

/**
 * Creates additional settings backend page for the sip upload form.
 * @todo: add some more help text for the second tab (Information Texts) with ->set_help_text()
 * @todo: add a help text for the users meta data. One can add different entries with name, title (with translations) and a type (where u can only select between text and textarea)
 */
function starg_attach_user_form_field_settings() {
	$sip_archive_languages = starg_get_enabled_languages();

	$form_fields = array();
	$sub_form_fields = array();
	$sub_user_form_fields = array();

	$sub_form_fields[] = Field::make( 'text', 'sip_custom_meta_key', __('Name','sip') )
		->set_required( true );

	$sub_user_form_fields[] = Field::make( 'text', 'sip_custom_archival_user_meta_key', __('Name','sip') )
		->set_required( true );

	foreach ( $sip_archive_languages as $language ) {
		$language = str_replace( 'sip-', '', $language );
		$form_fields[] = Field::make( 'separator', 'from_language_' . strtolower( $language ), $language );
		$form_fields[] = Field::make('textarea', 'sip_upload_purpose_options_' . strtolower($language), __('Upload Purpose Options', 'sip')  . ' ' . $language)
			->set_width(50);
		$form_fields[] = Field::make('textarea', 'sip_blocking_time_options_' . strtolower($language), __('Blocking Time Options', 'sip') .  ' ' . $language)
			->set_width(50);
		$form_fields[] = Field::make( 'text', 'sip_blocking_time_upload_purpose_' . strtolower( $language ), __('Blocking Time Upload Purpose', 'sip') .  ' ' . $language );
		$form_fields[] = Field::make( 'text', 'sip_blocking_time_calculate_' . strtolower( $language ), __('Blocking Time Calculate', 'sip') .  ' ' . $language );
		$form_fields[] = Field::make( 'rich_text', 'sip_right_transfer_text_' . strtolower( $language ), __('Right Transfer Text', 'sip') .  ' ' . $language );

		$sub_form_fields[] = Field::make( 'text', 'sip_custom_meta_title_' . strtolower( $language ), __('Title','sip') .  ' ' . $language );

		$sub_user_form_fields[] = Field::make( 'text', 'sip_custom_archival_user_meta_title_' . strtolower( $language ), __('Title','sip') .  ' ' . $language );
	}

	$sub_form_fields[] = Field::make('select', 'sip_custom_meta_type', __('Type', 'sip'))
		->add_options(array(
			'text' => 'text',
			'textarea' => 'textarea',
		));

	$sub_user_form_fields[] = Field::make('select', 'sip_custom_archival_user_meta_type', __('Type', 'sip'))
		->add_options(array(
			'text' => 'text',
			'textarea' => 'textarea',
		));

	$form_fields[] = Field::make('complex', 'sip_custom_meta', __('Custom Meta Data', 'sip'))
		->set_layout('grid')
		->add_fields($sub_form_fields);

	Container::make('theme_options', __('Form fields', 'sip'))
		->set_page_parent('edit.php?post_type=archival')
		->add_tab(__('Users', 'sip'), $form_fields)
		->add_tab(
			__('Archival Users', 'sip'),
			array(
				Field::make('complex', 'sip_custom_archival_user_meta', __('Custom Meta Data', 'sip'))
					->set_layout('grid')
					->add_fields($sub_user_form_fields),
			)
		);
}
add_action( 'carbon_fields_register_fields', 'starg_attach_user_form_field_settings' );

/**
 * Creates additional settings for the archive taxonomy of the archival post type.
 * These fields are used to connect the user to a specific archival institution.
 */
function starg_attach_settings_to_the_archive_taxonomy() {
	Container::make('term_meta', __('Term Options', 'sip'))
		->where('term_taxonomy', '=', 'archive') // only show our new field for categories
		->add_fields(array(
			Field::make('text', 'sip_institution', __('Institution Name abbreviation', 'sip'))
				->set_required(true)
				->set_help_text( __('Used to generate the SIP name.', 'sip' ) ),
			Field::make('text', 'sip_referenz', __('SIP Referenz', 'sip'))
				->set_help_text( __('If empty - Originator (user) login will be used.', 'sip' ) ),
			Field::make('image', 'sip_institution_logo', __('Institution Logo', 'sip')),
		));
}
add_action( 'carbon_fields_register_fields', 'starg_attach_settings_to_the_archive_taxonomy' );
