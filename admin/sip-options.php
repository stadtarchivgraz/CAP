<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Returns an URL to the options page of the plugin.
 */
function starg_get_plugin_options_admin_url() {
	if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) { return; }

	return add_query_arg( array( 'post_type' => 'archival', 'page' => 'crb_carbon_fields_container_allgemeine_optionen.php', ), esc_url( admin_url( 'edit.php' ) ) );
}

/**
 * Creates the general settings backend page for the CAP-plugin.
 * @todo: add some more help text for the second tab (Information Texts) with ->set_help_text()
 */
function starg_attach_general_plugin_settings() {
	$roles_capability = array(
		'manage_options'    => esc_html__( 'Administrator', 'sip' ),
		'edit_others_posts' => esc_html__( 'Editor', 'sip' ),
		'publish_posts'     => esc_html__( 'Author', 'sip' ),
		'edit_posts'        => esc_html__( 'Contributor', 'sip' ),
		'read'              => esc_html__( 'Subscriber', 'sip' ),
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
		$login_fields[] = Field::make( 'rich_text', 'sip_register_text_' . strtolower( $language ), esc_html__('Register Text', 'sip') . ' ' . $language );
		$login_fields[] = Field::make( 'rich_text', 'sip_update_profile_text_' . strtolower( $language ), esc_html__('Update Profile Text', 'sip') . ' ' . $language );
		$login_fields[] = Field::make( 'rich_text', 'sip_privacy_policy_approval_text_' . strtolower( $language ), esc_html__('Privacy Policy Approval Text', 'sip') . ' ' . $language );
		$login_fields[] = Field::make( 'rich_text', 'sip_cron_deleted_text_' . strtolower( $language ), esc_html__('SIP Folder deleted Text', 'sip') . ' ' . $language );
	}

	Container::make('theme_options', esc_html__('General options', 'sip'))
		->set_page_parent('edit.php?post_type=archival')
		->add_tab(
			esc_html__('Application', 'sip'),
			array(
				Field::make('separator', 'sip_archive', 'Archive'),
				Field::make('select', 'sip_archive_role', esc_html__('Role', 'sip'))
					->add_options($roles_capability),
				Field::make('separator', 'sip_upload', 'Upload'),
				Field::make('text', 'sip_upload_path', esc_html__('Archival Upload Path', 'sip'))
					->set_default_value($default_path),
				Field::make('text', 'sip_max_size', esc_html__('Max SIP Size in Bytes', 'sip'))
					->set_default_value('50000000')
					->set_width(25),
				Field::make('textarea', 'sip_mime_types', esc_html__('Supported file MIME Types', 'sip'))
					->set_default_value($default_mime_types),
				Field::make('checkbox', 'sip_clamav', esc_html__('Virus Check with ClamAV', 'sip')),
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
				Field::make('checkbox', 'sip_cron_delete', esc_html__('Automatically delete uploaded files', 'sip')),
				Field::make('text', 'sip_cron_delete_days', esc_html__('older than days', 'sip'))
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
				Field::make('multiselect', 'sip_cron_delete_status',  esc_html__('Archival Status', 'sip'))
					->set_conditional_logic(array(
						array(
							'field' => 'sip_cron_delete',
							'value' => true,
						)
					))
					->add_options(array(
						'upload' => esc_html__('Uploads'),
						'draft' => esc_html__('Draft'),
						'pending' => esc_html__('Pending'),
						'publish' => esc_html__('Published'),
					))
					->set_default_value('3310')
					->set_width(50),
				Field::make('separator', 'sip_map_options', esc_html__('Map', 'sip')),
				Field::make('text', 'sip_map_google_api_key', esc_html__('Google API Key for reverse Geocoding', 'sip')),
				Field::make('text', 'sip_map_default_lat', esc_html__('Default Lat', 'sip'))
					->set_default_value('47.06745752167981')
					->set_width(33),
				Field::make('text', 'sip_map_default_lng', esc_html__('Default Lng', 'sip'))
					->set_default_value('15.441103960661826')
					->set_width(33),
				Field::make('text', 'sip_map_default_zoom', esc_html__('Default zoom', 'sip'))
					->set_attribute('type', 'number')
					->set_attribute('min', 1)
					->set_attribute('max', 22)
					->set_attribute('step', 1)
					->set_default_value('10')
					->set_width(33),
				Field::make('separator', 'sip_style_options', 'Style'),
				Field::make('header_scripts', 'sip_custom_style', esc_html__('Custom CSS', 'sip'))
			)
		)
		->add_tab(esc_html__('Information Texts', 'sip'), $login_fields);
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

	$sub_form_fields[] = Field::make( 'text', 'sip_custom_meta_key', esc_html__('Name','sip') )
		->set_required( true );

	$sub_user_form_fields[] = Field::make( 'text', 'sip_custom_archival_user_meta_key', esc_html__('Name','sip') )
		->set_required( true );

	foreach ( $sip_archive_languages as $language ) {
		$language = str_replace( 'sip-', '', $language );
		$form_fields[] = Field::make( 'separator', 'from_language_' . strtolower( $language ), $language );
		$form_fields[] = Field::make('textarea', 'sip_upload_purpose_options_' . strtolower($language), esc_html__('Upload Purpose Options', 'sip')  . ' ' . $language)
			->set_width(50);
		$form_fields[] = Field::make('textarea', 'sip_blocking_time_options_' . strtolower($language), esc_html__('Blocking Time Options', 'sip') .  ' ' . $language)
			->set_width(50);
		$form_fields[] = Field::make( 'text', 'sip_blocking_time_upload_purpose_' . strtolower( $language ), esc_html__('Blocking Time Upload Purpose', 'sip') .  ' ' . $language );
		$form_fields[] = Field::make( 'text', 'sip_blocking_time_calculate_' . strtolower( $language ), esc_html__('Blocking Time Calculate', 'sip') .  ' ' . $language );
		$form_fields[] = Field::make( 'rich_text', 'sip_right_transfer_text_' . strtolower( $language ), esc_html__('Right Transfer Text', 'sip') .  ' ' . $language );

		$sub_form_fields[] = Field::make( 'text', 'sip_custom_meta_title_' . strtolower( $language ), esc_html__('Title','sip') .  ' ' . $language );

		$sub_user_form_fields[] = Field::make( 'text', 'sip_custom_archival_user_meta_title_' . strtolower( $language ), esc_html__('Title','sip') .  ' ' . $language );
	}

	$sub_form_fields[] = Field::make('select', 'sip_custom_meta_type', esc_html__('Type', 'sip'))
		->add_options(array(
			'text' => 'text',
			'textarea' => 'textarea',
		));

	$sub_user_form_fields[] = Field::make('select', 'sip_custom_archival_user_meta_type', esc_html__('Type', 'sip'))
		->add_options(array(
			'text' => 'text',
			'textarea' => 'textarea',
		));

	$form_fields[] = Field::make('complex', 'sip_custom_meta', esc_html__('Custom Meta Data', 'sip'))
		->set_layout('grid')
		->add_fields($sub_form_fields);

	Container::make('theme_options', esc_html__('Form fields', 'sip'))
		->set_page_parent('edit.php?post_type=archival')
		->add_tab(esc_html__('Users', 'sip'), $form_fields)
		->add_tab(
			esc_html__('Archival Users', 'sip'),
			array(
				Field::make('complex', 'sip_custom_archival_user_meta', esc_html__('Custom Meta Data', 'sip'))
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
	Container::make('term_meta', esc_html__('Term Options', 'sip'))
		->where('term_taxonomy', '=', 'archive') // only show our new field for categories
		->add_fields(array(
			Field::make('text', 'sip_institution', esc_html__('Institution Name abbreviation', 'sip'))
				->set_required(true)
				->set_help_text( esc_html__('Used to generate the SIP name.', 'sip' ) ),
			Field::make('text', 'sip_referenz', esc_html__('SIP Referenz', 'sip'))
				->set_help_text( esc_html__("If empty - the originator's (user's) login will be used.", 'sip' ) ),
			Field::make('image', 'sip_institution_logo', esc_html__('Institution Logo', 'sip')),
		));
}
add_action( 'carbon_fields_register_fields', 'starg_attach_settings_to_the_archive_taxonomy' );
