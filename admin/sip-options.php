<?php
if (! defined('WPINC')) { die; }

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Appwrite\ClamAV\Network;

/**
 * Returns an URL to the options page of the plugin.
 */
function starg_get_plugin_options_admin_url() {
	if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) { return; }

	return add_query_arg( array( 'post_type' => 'archival', 'page' => 'crb_carbon_fields_container_allgemeine_optionen.php', ), esc_url( admin_url( 'edit.php' ) ) );
}

/**
 * Creates the general settings backend page for the CAP-plugin.
 */
function starg_attach_general_plugin_settings() {
	$application_fields   = starg_get_application_setting_fields();
	$login_fields         = starg_get_login_setting_fields();
	$email_fields         = starg_get_email_setting_fields();
	$debug_logging_fields = starg_get_debug_logging_setting_fields();

	// todo: this might be a problem! first of all we're in a plugin. but more important: what if the theme also uses "carbon fields" and adds 'theme_options'...
	Container::make('theme_options', esc_html__('General options', 'sip'))
		->set_page_parent('edit.php?post_type=archival')
		->add_tab( esc_html__( 'Application', 'sip' ), $application_fields )
		->add_tab( esc_html__( 'Information Texts', 'sip' ), $login_fields )
		->add_tab( esc_html__( 'Email settings', 'sip' ), $email_fields )
		->add_tab( esc_html__( 'Logging settings', 'sip' ), $debug_logging_fields );
}
add_action( 'carbon_fields_register_fields', 'starg_attach_general_plugin_settings' );

function starg_get_application_setting_fields() {
	$roles_capability = array(
		'manage_options'    => esc_html__( 'Administrator', 'sip' ),
		'edit_others_posts' => esc_html__( 'Editor', 'sip' ),
		'publish_posts'     => esc_html__( 'Author', 'sip' ),
		// 'edit_posts'        => esc_html__( 'Contributor', 'sip' ),
		// 'read'              => esc_html__( 'Subscriber', 'sip' ),
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
	$maptiler_api_link    = '<a href="https://cloud.maptiler.com/account/keys/" target="_blank">https://cloud.maptiler.com/account/keys/</a>';
	$google_maps_api_link = '<a href="hhttps://developers.google.com/maps/third-party-platforms/wordpress/generate-api-key" target="_blank">hhttps://developers.google.com/maps/third-party-platforms/wordpress/generate-api-key</a>';

	// Check if ClamAV is up to date.
	$clamav_date = '';
	if ( get_option('_sip_clamav_host') && get_option('_sip_clamav_port') ) {
		try {
			$clamav              = new Network(esc_attr(get_option('_sip_clamav_host')), (int) esc_attr(get_option('_sip_clamav_port')));
			$clamav_version      = esc_attr( $clamav->version() );
			$version_date_st     = substr(strrchr($clamav_version, '/'), 1);
			$clamav_version_date = DateTime::createFromFormat('D M d H:i:s Y', $version_date_st);
			$clamav_date         = date_i18n( 'd. F Y', $clamav_version_date->getTimestamp() );
		} catch (Exception $exception) {
			$logging = apply_filters( 'starg/logging', null );
			if ( $logging instanceof Starg_Logging ) {
				$logging->create_log_entry( $exception->getMessage() );
			}
		}
	}

	return array(
		Field::make('separator', 'sip_archive', esc_html__( 'Editorial Options', 'sip' ) ),
		Field::make('select', 'sip_archive_role', esc_html__('User role for editorial members', 'sip'))
			->add_options($roles_capability)
			->set_help_text( esc_html__( 'This role or higher is required to approve or reject submitted archival records.', 'sip' ) ),
		Field::make('separator', 'sip_upload', esc_html__( 'Upload', 'sip' ) ),
		Field::make('text', 'sip_upload_path', esc_html__('Archival Upload Path', 'sip'))
			->set_default_value($default_path)
			->set_width(20)
			->set_help_text( esc_html__( 'Specify the folder where the digital archival records should be stored. By default, all files are saved in the /wp-content/uploads/archival/ directory.', 'sip' ) ),
		Field::make('text', 'sip_max_size', esc_html__('Max SIP Size in Bytes', 'sip'))
			->set_default_value('50000000')
			->set_width(20)
			->set_help_text( esc_html__( 'This is the maximum size of files a user is allowed to upload for one submission.', 'sip' ) ),
		Field::make('textarea', 'sip_mime_types', esc_html__('Supported file MIME Types', 'sip'))
			->set_default_value($default_mime_types)
			->set_width(20)
			->set_help_text( esc_html__( 'Defines which file types are allowed to be uploaded to the application.
The value is specified as a list of MIME types (e.g. application/pdf, image/png). Files with unsupported MIME types will be rejected during upload.', 'sip' ) ),
		Field::make('checkbox', 'sip_display_mime_types_hint', esc_html__('Display a help text on the upload page that lists the supported file types', 'sip'))
			->set_default_value( 'yes' )
			->set_width(20)
			->set_help_text( esc_html__( 'If checked, a readable version of the supported mime types will be displayed as a help text on the upload page.', 'sip' ) ),
		Field::make('separator', 'sip_virus_check', esc_html__( 'Virus Check', 'sip' ) ),
		Field::make('checkbox', 'sip_clamav', esc_html__('Virus Check with ClamAV', 'sip')),
		Field::make('text', 'sip_clamav_host', 'ClamAV Host')
			->set_conditional_logic(array(
				array(
					'field' => 'sip_clamav',
					'value' => true,
				)
			))
			->set_default_value('localhost')
			->set_width(37.5)
			->set_help_text( esc_html__( 'The IP address or hostname where the ClamAV service is reachable.
This setting defines which server the application connects to in order to scan files for malware.', 'sip' ) ),
		Field::make('text', 'sip_clamav_port', 'ClamAV Port')
			->set_conditional_logic(array(
				array(
					'field' => 'sip_clamav',
					'value' => true,
				)
			))
			->set_default_value('3310')
			->set_width(37.5)
			->set_help_text( esc_html__( 'The network port on which the ClamAV service is listening on the specified host.
Ensure that the port is correctly configured and reachable from the application.', 'sip' ) ),
		Field::make( 'text', 'sip_clamav_version', esc_attr__( 'ClamAV signature date', 'sip' ) )
			->set_conditional_logic(array(
				array(
					'field' => 'sip_clamav',
					'value' => true,
				)
			))
			->set_default_value( esc_attr( $clamav_date ) )
			->set_attribute( 'readOnly', 'readonly' )
			->set_width(25)
			->set_help_text( esc_html__( 'The ClamAV signature date. If the date is over a week old, please contact your administrator.', 'sip' ) ),
		Field::make('separator', 'sip_housekeeping', esc_html__( 'Housekeeping', 'sip' ) ),
		Field::make('checkbox', 'sip_cron_delete', esc_html__('Automatically delete uploaded files', 'sip'))
			->set_help_text( esc_html__( 'Enables the automatic deletion of uploaded files.
When enabled, files are automatically removed from the system based on the configured retention rules.', 'sip' ) ),
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
			->set_width(50)
			->set_help_text( esc_html__( 'Defines the number of days after which uploaded files are automatically deleted.
All files with an upload date older than the specified number of days will be removed during automatic cleanup.', 'sip' ) ),
		Field::make('multiselect', 'sip_cron_delete_status',  esc_html__('Archival Status', 'sip'))
			->set_conditional_logic(array(
				array(
					'field' => 'sip_cron_delete',
					'value' => true,
				)
			))
			->add_options(array(
				'upload'  => esc_html__( 'Uploads', 'sip' ),
				'draft'   => esc_html__( 'Draft', 'sip' ),
				'pending' => esc_html__( 'Pending', 'sip' ),
				'publish' => esc_html__( 'Accepted', 'sip' ),
			))
			->set_default_value('upload')
			->set_width(50)
			->set_help_text( esc_html__( 'Allows selection of the statuses that are subject to automatic deletion.
Multiple statuses can be selected at the same time (e.g. upload, draft, pending, accepted).', 'sip' ) ),
		Field::make('separator', 'sip_map_options', esc_html__('Map', 'sip')),
		// we either use google maps for the coordinates of a place on the map, or openstreetmap. Fallback is openstreetmap as it does not need a API-Key.
		Field::make('text', 'sip_map_google_api_key', esc_html__('Google API Key for reverse Geocoding', 'sip'))
			// translators: %s: External link to create an API Key.
			->set_help_text( sprintf( esc_html__( 'Follow the steps at %s to create the API Key.', 'sip' ), $google_maps_api_link )),
		Field::make('text', 'sip_map_maptiler_api_key', esc_html__('Maptile API Key', 'sip'))
			// translators: %s: External link to maptiler homepage.
			->set_help_text( sprintf( esc_html__( 'To create an API Key, you need to create an account at %s first. Then you can create the API-Key.', 'sip' ), $maptiler_api_link ) ),
		Field::make('text', 'sip_map_default_lat', esc_html__('Default Lat', 'sip'))
			->set_default_value('47.06745752167981')
			->set_width(33)
			->set_help_text( esc_html__( 'Defines the latitude for the initial map position.
The value is specified in decimal degrees (e.g. 48.137154) and determines the vertical position on the map.', 'sip' ) ),
		Field::make('text', 'sip_map_default_lng', esc_html__('Default Lng', 'sip'))
			->set_default_value('15.441103960661826')
			->set_width(33)
			->set_help_text( esc_html__( 'Defines the longitude for the initial map position.
The value is specified in decimal degrees (e.g. 11.576124) and determines the horizontal position on the map.', 'sip' ) ),
		Field::make('text', 'sip_map_default_zoom', esc_html__('Default zoom', 'sip'))
			->set_attribute('type', 'number')
			->set_attribute('min', 1)
			->set_attribute('max', 22)
			->set_attribute('step', 1)
			->set_default_value('10')
			->set_width(33)
			->set_help_text( esc_html__( 'Defines the initial zoom level of the map.
Higher values result in a more zoomed-in view, while lower values display a larger map area.', 'sip' ) ),
		Field::make('separator', 'sip_style_options', 'Style'),
		Field::make('header_scripts', 'sip_custom_style', esc_html__('Custom CSS', 'sip'))
			// translators: %1$s: placeholder for the <style> tag. %2$s: placeholder for the <script> tag.
			->set_help_text( sprintf( esc_html__( 'Add custom CSS, including the %1$s start and end tags. %2$s tags get removed.', 'sip' ), '&lt;style&gt;', '&lt;script&gt;' ) ),
	);
}

function starg_get_login_setting_fields() {
	$login_fields          = array();
	$sip_archive_languages = starg_get_enabled_languages();

	foreach ( $sip_archive_languages as $language ) {
		$language = str_replace( 'sip-', '', $language );
		$login_fields[] = Field::make( 'separator', 'from_language_' . strtolower( $language ), starg_get_human_readable_language( $language ) );
		$login_fields[] = Field::make( 'rich_text', 'sip_register_text_' . strtolower( $language ), esc_html__('Register Text', 'sip') . ' ' . starg_get_human_readable_language( $language ) )
			->set_help_text( esc_html__( 'This text is displayed when a user accesses a page that requires authentication.
The configured content replaces the default frontend message.', 'sip' ) );
		$login_fields[] = Field::make( 'rich_text', 'sip_update_profile_text_' . strtolower( $language ), esc_html__('Update Profile Text', 'sip') . ' ' . starg_get_human_readable_language( $language ) )
			->set_help_text( esc_html__( 'This text is displayed when a user is required to complete their profile before using the page.
The configured content replaces the default frontend message.', 'sip' ) );
		$login_fields[] = Field::make( 'rich_text', 'sip_privacy_policy_approval_text_' . strtolower( $language ), esc_html__('Privacy Policy Approval Text', 'sip') . ' ' . starg_get_human_readable_language( $language ) )
			->set_help_text( esc_html__( 'Defines the text displayed in the frontend along with a link to the privacy policy or terms of use page.', 'sip' ) );
		$login_fields[] = Field::make( 'rich_text', 'sip_cron_deleted_text_' . strtolower( $language ), esc_html__('SIP Folder deleted Text', 'sip') . ' ' . starg_get_human_readable_language( $language ) );
	}

	return $login_fields;
}

/**
 * Defines the input fields for the plugins email settings.
 * @todo: maybe add Field::make( 'color', 'sip_notifications_bg_color', __('','sip') ) or Field::make( 'image', 'sip_notifications_header_image', __('Logo','sip') ) for customization.
 * @return array
 */
function starg_get_email_setting_fields() : array {
	$admin_emails      = DB_Query_Helper::get_all_admin_email_addresses();
	$domain            = parse_url( home_url(), PHP_URL_HOST );
	$domain_parts      = explode( '.', $domain );
	if (count($domain_parts) > 2 && $domain_parts[0] === 'www') {
		array_shift($domain_parts);
		$domain = implode( '.', $domain_parts );
	}
	$conditional_logic = array(
		array(
			'field' => 'sip_notifications_enabled',
			'value' => true,
		),
	);
	$html_email_cl = array_merge( $conditional_logic, array( array( 'field' => 'sip_notifications_as_html', 'value' => true, ) ) );
	$email_preview = starg_get_preview_notification_email();


	$email_setting_fields = array(
		Field::make( 'checkbox', 'sip_notifications_enabled', esc_html__( 'Enable email notifications', 'sip' ) )
			->set_default_value( 'no' )
			->set_help_text( esc_html__( 'This option enables the integrated notification system. When enabled, the system automatically sends emails to the user and their editor for every submission. It also sends an email to the user if their submission is accepted.', 'sip' ) ),
		Field::make( 'text', 'sip_notification_email_address', esc_html__( 'Username of the email address', 'sip' ) )
			->set_attribute( 'type', 'text' )
			->set_attribute( 'placeholder', 'wordpress' )
			->set_attribute( 'data-host', '@' . $domain )
			->set_width(33.3331)
			->set_help_text( esc_html__( 'This changes the username of the email address used to send the emails. Both the "@"-sign and the domain of your website are added automatically.', 'sip' ) )
			->set_conditional_logic( $conditional_logic ),
		Field::make( 'text', 'sip_notification_email_url', esc_html__( 'Domain', 'sip' ) )
			->set_default_value( '@' . $domain )
			->set_attribute( 'placeholder', '@' . $domain )
			->set_attribute( 'type', 'text' )
			->set_attribute( 'readOnly', true )
			->set_width(33.3331)
			->set_help_text( esc_html__( 'This will be automatically added to the username of the email address.', 'sip' ) )
			->set_conditional_logic( $conditional_logic ),
		Field::make( 'text', 'sip_notification_email_name', esc_html__( 'Email name', 'sip' ) )
			->set_attribute( 'placeholder', 'WordPress' )
			->set_width(33.3331)
			->set_help_text( esc_html__( 'This changes the name of the sent emails from "WordPress" to something of your choice.', 'sip' ) )
			->set_conditional_logic( $conditional_logic ),
		Field::make( 'text', 'sip_notification_reply_email_address', esc_html__( 'Reply email address', 'sip' ) )
			->set_attribute( 'type', 'email' )
			->set_attribute( 'placeholder', 'support@' . $domain )
			->set_width(50)
			->set_help_text( esc_html__( 'Add an email address for replies so that users can easily contact you at a specific address.', 'sip' ) )
			->set_conditional_logic( $conditional_logic ),
		Field::make( 'multiselect', 'sip_notification_additional_recipients', esc_html__( 'Additional recipients to notify about user submissions', 'sip' ) )
			->add_options( $admin_emails )
			->set_width(50)
			->set_help_text( esc_html__( 'Usually, only users with the "editor" role are notified about submissions. Use this field to select administrators who should also be notified.', 'sip' ) )
			->set_conditional_logic( $conditional_logic ),
		Field::make( 'checkbox', 'sip_notifications_as_html', esc_html__( 'Send emails as HTML', 'sip' ) )
			->set_default_value( 'no' )
			->set_width(25)
			->set_help_text( esc_html__( 'Sending emails as HTML allows for a more complex structure and design. This also wraps other WordPress emails (like registration confirmations or password resets) in the same design.', 'sip' ) )
			->set_conditional_logic( $conditional_logic ),
		Field::make( 'html', 'sip_notification_email_preview', esc_html__( 'Preview', 'sip' ) )
			->set_html( $email_preview )
			->set_help_text( esc_html__( 'This is a preview of the email design.', 'sip' ) )
			->set_conditional_logic( $html_email_cl ),
		// todo: Customization for email notifications. Like background-color, text-color, logo, text in footer.
	);

	return $email_setting_fields;
}

/**
 * Defines the input fields for the plugins logging settings.
 * @return array
 */
function starg_get_debug_logging_setting_fields() : array {
	$conditional_logic = array(
		array(
			'field' => 'sip_logging_enabled',
			'value' => true,
		),
	);

	$debug_logging_fields = array(
		Field::make( 'checkbox', 'sip_logging_enabled', esc_html__( 'Enable debug logging', 'sip' ) )
			->set_default_value( 'yes' ),
	);

	return $debug_logging_fields;
}

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
		$form_fields[] = Field::make( 'separator', 'from_language_' . strtolower( $language ), starg_get_human_readable_language( $language ) );
		$form_fields[] = Field::make( 'textarea', 'sip_upload_purpose_options_' . strtolower($language), esc_html__('Upload Purpose Options', 'sip')  . ' - ' . starg_get_human_readable_language( $language ))
			->set_width(50)
			->set_help_text( esc_html__( 'Defines the predefined reasons for which users can upload files.
The entries configured here are available for selection in the upload form. Each entry represents a possible upload purpose (e.g. archiving, publication, private storage).', 'sip' ) );
		$form_fields[] = Field::make( 'textarea', 'sip_blocking_time_options_' . strtolower($language), esc_html__('Blocking Time Options', 'sip') .  ' - ' . starg_get_human_readable_language( $language ))
			->set_width(50)
			->set_help_text( esc_html__( 'Defines the available blocking periods users can choose from during upload.
The blocking time determines how long uploaded files remain inaccessible to the public. The configured values are presented to users as selectable options.', 'sip' ) );
		$form_fields[] = Field::make( 'text', 'sip_blocking_time_upload_purpose_' . strtolower( $language ), esc_html__('Blocking Time Upload Purpose', 'sip') .  ' - ' . starg_get_human_readable_language( $language ) )
			->set_help_text( esc_html__( 'Specifies which upload purposes allow a blocking time to be selected or applied.
Only the upload purposes defined here will enable users to set a blocking time during upload. Make sure to use a comma separated list of one or more defined upload purposes from the previous selection.', 'sip' ) );
		$form_fields[] = Field::make( 'text', 'sip_blocking_time_calculate_' . strtolower( $language ), esc_html__('Blocking Time Calculate', 'sip') .  ' - ' . starg_get_human_readable_language( $language ) )
			->set_help_text( esc_html__( 'Defines how the blocking time is calculated by default.
This setting allows you to specify a rule used to automatically determine the end of the blocking period, e.g. year of birth + 100 years.', 'sip' ) );
		$form_fields[] = Field::make( 'rich_text', 'sip_right_transfer_text_' . strtolower( $language ), esc_html__('Right Transfer Text', 'sip') .  ' - ' . starg_get_human_readable_language( $language ) )
			->set_help_text( esc_html__( 'Defines the text displayed to users regarding the transfer of rights.
The content should provide information about the donation agreement between the users and the website and clearly explain the legal terms of the transfer.', 'sip' ) );

		$sub_form_fields[] = Field::make( 'text', 'sip_custom_meta_title_' . strtolower( $language ), esc_html__('Title','sip') .  ' - ' . starg_get_human_readable_language( $language ) );

		$sub_user_form_fields[] = Field::make( 'text', 'sip_custom_archival_user_meta_title_' . strtolower( $language ), esc_html__('Title','sip') .  ' - ' . starg_get_human_readable_language( $language ) );
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

/**
 * Creates the HTML for the preview of HTML email notifications.
 * @return string
 */
function starg_get_preview_notification_email(): string {
	$home_link = '<a href="' . get_home_url() . '">' . get_home_url() . '</a>';
	ob_start();
		?>
		<div class="notification_email_preview notification_html">
			<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ececec;">
				<tr>
					<td align="center" style="padding:24px 16px;">

					<?php // Outer container ?>
					<table width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;font-family:sans-serif;">

						<?php // Header / Logo ?>
						<tr>
							<td align="center" style="padding:16px 0 24px 0;max-height:100px;">
								<?php
								if ( has_custom_logo() ) :
									the_custom_logo();
								else :
								?>
								<h1 style="margin:0;font-size:24px;line-height:1.3;color:#222;">
									<?php echo esc_html( get_bloginfo() ); ?>
								</h1>
								<?php endif; ?>
							</td>
						</tr>

						<?php // Main content ?>
						<tr>
							<td style="background-color:#ffffff;padding:32px;color:#222222;font-size:16px;line-height:1.5;border-radius:4px;box-shadow:1px 1px 5px 3px #cfcfcf;">
								<?php
								// translators: %s: Link to the website.
								echo wpautop( sprintf( esc_attr_x( "You've got a new notification!\n\nWe wanted to let you know that a new notification has been sent to you. Head over to %s and check the status of your submission.\n\nThank you for your contribution.", 'Preview text for the email notifications', 'sip' ), $home_link ) );
								?>
							</td>
						</tr>

						<?php // Footer ?>
						<tr>
							<td align="center" style="padding:16px 0 0 0;font-size:13px;line-height:1.4;color:#000000;">
								<p style="margin:0;">
									<?php echo esc_html__( 'This is an automated notification email. Please do not reply directly.', 'sip' ); ?>
								</p>
							</td>
						</tr>

					</table>
					<?php // /Outer container ?>

					</td>
				</tr>
			</table>
		</div>
	<?php
	return ob_get_clean();
}
