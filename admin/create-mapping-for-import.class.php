<?php
defined( 'ABSPATH' ) || exit;

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'admin/backend-form-validation.class.php' );
require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/db/import-mapping.php' );

/**
 * Todo:
 *   * add multiple mapping profiles
 *   * choose between mapping profiles
 *   * set mapping profiles active
 *   * transform data based on mapping profiles (like date/time)
 */
class Create_Mapping_For_Import extends Backend_Form_Validation {
	private array $active_mapping;
	private array $active_config;
	private int $user_archive_id      = 0;
	public static int $schema_version = 1;
	protected array $invalid_input    = array();

	public function __construct() {
		$this->nonce_action        = 'starg_mapping_nonce_action';
		$this->nonce_key           = 'starg_mapping_nonce_key';
		$this->form_name           = 'starg_mapping_name';
		$this->form_name_key       = 'starg_mapping_key';
		$this->backend_slug        = strtolower( sanitize_title( STARG_SIP_PLUGIN_NAME . '-create-mapping' ) );
		$this->submit_button_class = 'button-primary';

		$this->user_archive_id = (int) get_user_meta( get_current_user_id(), 'user_archive', true );
		$this->active_mapping  = $this->get_mapping_by_inst( (int) $this->user_archive_id );
		$this->active_config   = $this->get_configuration_by_inst( (int) $this->user_archive_id );
	}

	public function init(): bool {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		$this->user_input = $this->user_input_sanitization();
		if ( ! $this->user_input ) { return false; }

		$this->missing_inputs = $this->user_input_required( $this->user_input );
		if ( ! empty( $this->missing_inputs ) ) {
			$this->set_error_message( esc_attr_x( 'There are inputs missing', 'Error message if a form was not filled properly.', 'sip' ) );
			$this->set_notification_for_missing_inputs( $this->missing_inputs );
			return false;
		}

		$user_input_valid = $this->validate_user_input();
		if ( ! $user_input_valid ) {
			$this->set_error_message( esc_attr_x( 'There is a problem in the configuration. Please check the "Order" column and verify that there are no duplicate entries.', 'Error message if a form was not filled properly.', 'sip' ) );
			$this->set_notification_for_invalid_inputs( $this->invalid_input );
			return false;
		}

		$mapping_saved = $this->save_mapping();
		if ( ! $mapping_saved ) {
			$this->set_error_message( esc_attr_x( 'Error saving the mapping data. Please try again.', 'Error message.', 'sip' ) );
			// translators: %s: ID of the current user.
			$this->set_error_log_message( sprintf( esc_attr_x( 'Error saving the mapping data for %s', '', 'sip' ), get_current_user_id() ), Log_Severity::Error );
			return false;
		}

		$this->set_success_message( esc_html__( 'Your entries have been saved', 'sip' ) );
		return true;
	}

	/**
	 * Defines and returns the main mapping schema for the import files.
	 * @return array{version:int, fields:array, static:array}
	 */
	public static function get_mapping_schema(): array {
		return array(
			'version' => self::$schema_version,
			'fields'  => array(
				'id' => array(
					'label'    => esc_attr__( 'Identification', 'sip' ),
				),
				'title' => array(
					'label'    => esc_attr__( 'Title', 'sip' ),
				),
				'min_creation_time' => array(
					'label'     => esc_attr__( 'Created at', 'sip' ),
					// 'transform' => 'date:m,Y',
				),
				'max_creation_time' => array(
					'label'     => esc_attr__( 'Created until', 'sip' ),
					// 'transform' => 'date:m,Y',
				),
				'created' => array(
					'label'     => esc_attr__( 'Created', 'sip' ),
					// 'transform' => 'date:Y-m-d',
				),
				'blocking_time' => array(
					'label'    => esc_attr__( 'Blocking time', 'sip' ),
				),
				'blocked_until' => array(
					'label'    => esc_attr__( 'Blocked until', 'sip' ),
				),
				'scope' => array(
					'label'    => esc_attr__( 'Scope', 'sip' ),
				),
				'originator_name' => array(
					'label'    => esc_attr__( 'Originator', 'sip' ),
				),
				'collection_history' => array(
					'label'    => esc_attr__( 'Collection history', 'sip' ),
				),
				'author_name' => array(
					'label'    => esc_attr__( 'Author', 'sip' ),
				),
				'content' => array(
					'label'    => esc_attr__( 'Content', 'sip' ),
				),
				'annotation' => array(
					'label'    => esc_attr__( 'Annotation', 'sip' ),
				),
				'language' => array(
					'label'    => esc_attr__( 'Language', 'sip' ),
				),
				'editor_name' => array(
					'label'    => esc_attr__( 'Editor', 'sip' ),
				),
				'notes' => array(
					'label'    => esc_attr__( 'Notes', 'sip' ),
				),
				'date_cataloguing' => array(
					'label'    => esc_attr__( 'Cataloguing date', 'sip' ),
				),
				'geo_coordinates' => array(
					'label'    => esc_attr__( 'Coordinates', 'sip' ),
				),
				'geo_location' => array(
					'label'    => esc_attr__( 'Location', 'sip' ),
				),
				'license_uri' => array(
					'label'    => esc_attr__( 'License URI', 'sip' ),
				),
				'license_digital_object' => array(
					'label'    => esc_attr__( 'License', 'sip' ),
				),
				'tags' => array(
					'label'    => esc_attr__( 'Tag', 'sip' ),
				),
			),
			'static' => array(
				'column_name'  => '',
				'column_value' => '',
				'order'        => '',
			),
		);
	}

	/**
	 * Defines and returns the schema for the configuration.
	 * The array value represents the allowed values.
	 * @return array{target_os:array, csv_delimiter:array, target_path:string}
	 */
	public static function get_configuration_schema(): array {
		return array(
			'target_os'     => array( 'windows', 'unix', ),
			'csv_delimiter' => array( ',', ';', '|', 'comma', 'semicolon', 'tube', ),
			'target_path'   => '',
		);
	}

	/**
	 * Returns the saved data for a specific institution/archive.
	 * @param int $id the Term-ID of the institution as set for @see Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG
	 * @return array|null
	 */
	private static function get_data_by_inst( int $id ) {
		return Import_Mapping_DB_Table::get_input_by_institution_id( $id, self::$schema_version );
	}

	/**
	 * Returns the saved mapping data for a specific institution/archive.
	 * @param int $id the Term-ID of the institution as set for @see Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG
	 * @return array normalized mapping or empty array if no mapping is available.
	 */
	public static function get_mapping_by_inst( int $id ): array {
		$row = self::get_data_by_inst( $id );
		if ( ! $row || ! isset( $row[ Import_Mapping_DB_Table::get_col_slug_mapping() ] ) ) { return array(); }

		$mapping = json_decode( $row[ Import_Mapping_DB_Table::get_col_slug_mapping() ], true ) ?: array();
		return self::normalize_mapping( $mapping );
	}

	/**
	 * Returns the saved configuration data for a specific institution/archive.
	 * @param int $id the Term-ID of the institution as set for @see Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG
	 * @return array
	 */
	public static function get_configuration_by_inst( int $id ) {
		$row = self::get_data_by_inst( $id );
		if ( ! $row || ! isset( $row[ Import_Mapping_DB_Table::get_col_slug_configuration() ] ) ) { return array(); }

		return json_decode( $row[ Import_Mapping_DB_Table::get_col_slug_configuration() ], true ) ?: array();
	}

	/**
	 * Make sure the mapping is consistent and sane.
	 * @param array $mapping
	 * @return array{fields:array, static:array}
	 */
	private static function normalize_mapping( array $mapping ): array {
		$schema     = self::get_mapping_schema();
		$normalized = array(
			'fields' => array(),
			'static' => array(),
		);

		foreach ( $schema['fields'] as $key => $field ) {
			$normalized['fields'][$key] = array(
				'value' => isset( $mapping[$key]['value'] ) ? sanitize_text_field( $mapping[$key]['value'] ) : '',
				'order' => isset( $mapping[$key]['order'] ) ? (int) sanitize_key( $mapping[$key]['order'] ) : 0,
			);
		}

		if ( empty( $mapping['static'] ) ) {
			return $normalized;
		}

		// static fields with predefined values.
		if ( is_array( $mapping['static'] ) ) {
			foreach ( $mapping['static'] as $key => $row ) {
				if ( empty( $row['column_name'] ) ) { continue; }

				$normalized['static'][ $key ] = array(
					'column_name'  => sanitize_text_field( $row[ 'column_name' ] ),
					'column_value' => sanitize_text_field( $row[ 'column_value' ] ),
					'order'        => (isset( $row[ 'order' ] ) ) ? (int) sanitize_key( $row[ 'order' ] ) : 0,
				);
			}
		}

		return $normalized;
	}

	/**
	 * Make sure the provided $data fit in our $schema.
	 * @param array $data
	 * @param array $schema
	 * @return array
	 */
	public function apply_mapping( array $data, array $schema ): array {
		$result = array();

		// dynamic inputs
		// $config = 'label', 'required', 'transform'
		foreach ( $schema['fields'] as $internal_key => $config ) {
			if ( ! $config || ! isset ( $data[ $internal_key ] ) || ! $data[ $internal_key ] ) { continue; }

			$single_data = sanitize_text_field( $data[ $internal_key ] );
			$single_order = (int) sanitize_key( $data[ $internal_key . '_order' ] );
			if ( isset( $config['transform'] ) ) {
				$single_data = $this->transform_data( $single_data, $config['transform'] );
			}

			$result[$internal_key] = array(
				'value' => $single_data,
				'order' => $single_order,
			);
		}

		if ( ! isset( $data['static'] ) ) {
			return $result;
		}

		// static inputs.
		foreach ( $data['static'] as $static_key => $row ) {
			if ( empty( $row ) ) { continue; }

			foreach( $row as $key => $value ) {
				$result['static'][ $static_key ][ $key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Make sure the provided $data fit in our $schema.
	 * @param array $data
	 * @param array $schema
	 * @return array
	 */
	public function apply_configuration( array $data, array $schema ): array {
		$result = array();
		foreach ( $schema as $internal_key => $expected_values ) {
			if (
				! isset ( $data[ $internal_key ] ) || ! $data[ $internal_key ] ||
				( is_array( $expected_values ) && ! in_array( $data[ $internal_key ], $expected_values, true ) )
			) { continue; }

			$single_data = sanitize_text_field( $data[ $internal_key ] );
			$result[$internal_key] = $single_data;
		}

		return $result;
	}

	/**
	 * Checks if every provided number for the _order fields is unique.
	 * This should prevent incorrect orders for the created CSV.
	 * @todo: check for duplicated column names.
	 * @return bool
	 */
	private function validate_user_input(): bool {
		$invalid_input = array();
		$seen_order    = array();
		$dyn_input     = array();
		if ( isset( $this->user_input['static'] ) ) {
			foreach( $this->user_input['static'] as $dyn_key => $dyn_val ) {
				if ( isset( $dyn_input[ $dyn_val['order'] ] ) ) {
					$invalid_input[ $dyn_val['order'] ] = 'static[' . $dyn_key . '][order]';
				}
				$dyn_input[ $dyn_val['order'] ]  = $dyn_key;
				$seen_order[ $dyn_val['order'] ] = $dyn_key;
			}
		}

		foreach ( $this->user_input as $input_key => $input_value ) {
			if ( ! str_ends_with( $input_key, '_order' ) ) { continue; }

			if ( isset( $seen_order[ $input_value ] ) ) {
				$invalid_input[ $input_value ] = $input_key;
			}
			$seen_order[ $input_value ] = $input_key;
		}

		if ( $invalid_input ) {
			$this->invalid_input = $invalid_input;
			return false;
		}

		return true;
	}

	/**
	 * Saves the mapping from the user to the database.
	 * @return int|false
	 */
	private function save_mapping() {
		global $wpdb;
		$schema      = $this->get_mapping_schema();
		$mapped_data = $this->apply_mapping( $this->user_input, $schema );


		$configuration_schema = $this->get_configuration_schema();
		$mapped_configuration = $this->apply_configuration( $this->user_input, $configuration_schema );

		$table_name         = $wpdb->prefix . Import_Mapping_DB_Table::get_table_name();
		$col_id             = Import_Mapping_DB_Table::get_col_slug_id();
		$col_institution_id = Import_Mapping_DB_Table::get_col_slug_institution_id();
		$col_mapping        = Import_Mapping_DB_Table::get_col_slug_mapping();
		$col_config         = Import_Mapping_DB_Table::get_col_slug_configuration();
		$col_version        = Import_Mapping_DB_Table::get_col_slug_version();
		$col_created_at     = Import_Mapping_DB_Table::get_col_slug_created_at();
		$col_updated_at     = Import_Mapping_DB_Table::get_col_slug_updated_at();

		$data = array(
			$col_institution_id => $this->user_archive_id,
			$col_mapping        => wp_json_encode( $mapped_data ),
			$col_config         => wp_json_encode( $mapped_configuration ),
			$col_version        => $schema['version'],
			$col_updated_at     => current_time('mysql'),
		);

		// check for an existing mapping and update it if needed.
		$existing_mapping = Import_Mapping_DB_Table::get_input_by_institution_id( $this->user_archive_id, $schema['version'] );
		if ( $existing_mapping && (int) $schema['version'] === (int) $existing_mapping[ $col_version ] ) {
			$updated = $wpdb->update(
				$table_name,
				$data,
				array(
					$col_id  => (int) $existing_mapping[ $col_id ],
				),
				array(
					$col_institution_id => '%d',
					$col_mapping        => '%s',
					$col_config         => '%s',
					$col_version        => '%d',
					$col_updated_at     => '%s',
				),
				array(
					$col_id  => '%d',
				)
			);
			return $updated;
		}

		// otherwise create a new entry.
		$data[ $col_created_at ] = current_time('mysql');
		$result = $wpdb->insert(
			$table_name,
			$data,
			array(
				$col_institution_id => '%s',
				$col_mapping        => '%s',
				$col_config         => '%s',
				$col_version        => '%d',
				$col_created_at     => '%s',
				$col_updated_at     => '%s',
			)
		);

		return $result;
	}

	/**
	 * Retrieve the value for the form input field.
	 * The value differs based on the state of the archival submission status.
	 * @param string $field The form field for which we want to retrieve the data.
	 * @param mixed $default [Optional] A default value for the input field if we don't want to use an empty string. Default: empty string.
	 *
	 * Overrides @see Form_Input_Helper::get_form_value()
	 */
	public function get_form_value( string $field, $default = '' ) {
		if ( ! $field ) { return esc_attr( $default ); }

		// load the saved values for the dynamic form field.
		if ( isset( $this->active_mapping[ 'static' ] ) && $this->is_valid_array_path( $field ) ) {
			$dynamic_val = $this->get_value_by_path( $this->active_mapping, $field );
			if ( $dynamic_val ) {
				return esc_attr( $dynamic_val );
			}
		}

		if ( ! isset( $this->get_valid_input_names()[ $field ] ) ) { return esc_attr( $default ); }
		$field = sanitize_key( $field );

		// if the form was submitted and required form fields were not filled, we pass the previously filled user_input if possible.
		if ( $this->missing_inputs || $this->invalid_input ) {
			return (isset( $this->user_input[ $field ] )) ? $this->user_input[ $field ] : esc_attr( $default );
		}

		$field_key = 'value';
		$field_name = $field;
		if ( str_ends_with( $field, '_order' ) ) {
			$field_key = 'order';
			$field_name = str_replace( '_order', '', $field );
		}
		// load the saved values if available.
		if ( isset( $this->active_mapping['fields'][ $field_name ][$field_key] ) && $this->active_mapping['fields'][ $field_name ][$field_key] ) {
			return esc_attr( $this->active_mapping['fields'][ $field_name ][$field_key] );
		}

		if (isset( $this->active_config[ $field ] )) {
			return esc_attr( $this->active_config[ $field ] );
		}

		// if not specified otherwise we use an empty string.
		return ( $default ) ? esc_attr( $default ) : '';
	}

	private static function is_valid_array_path( string $path ) {
		// first match: characters from a-z, A-z, 0-9, '_' and '-'.
		// second match: characters from a-z, A-z, 0-9, '_' and '-' inside [] braces.
		return preg_match('/^[a-zA-Z0-9_\-]+(\[[a-zA-Z0-9_\-]+\])*$/', $path);
	}

	private static function get_value_by_path(array $array, string $path) {
		if ( ! is_array( $array ) ) { return null; }
		// "static[0][column_name]" → ["static", "0", "column_name"]
		preg_match_all('/\[?([^\[\]]+)\]?/', sanitize_text_field( $path ), $matches);
		$keys = $matches[1] ?? array();

		$value = '';
		foreach ($keys as $key) {
			if ( array_key_exists($key, $array) ) {
				$value = $array[$key];
				continue;
			}

			$found = false;
			foreach ($array as $row) {
				if ( is_array($row) && isset($row['column_name']) && $row['column_name'] === $key ) {
					$value = $row['column_value'] ?? null;
					$found = true;
					break;
				}
			}

			if ( ! $found) {
				return false;
			}
		}

		return esc_attr( $value );
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array the array should be formed as [ 'input_name' => 'sanitizing_function', ]
	 */
	protected function get_valid_input_names(): array {
		return array(
			'id'                           => 'sanitize_text_field',
			'id_order'                     => 'sanitize_key',
			'title'                        => 'sanitize_text_field',
			'title_order'                  => 'sanitize_key',
			'min_creation_time'            => 'sanitize_text_field',
			'min_creation_time_order'      => 'sanitize_key',
			'max_creation_time'            => 'sanitize_text_field',
			'max_creation_time_order'      => 'sanitize_key',
			'created'                      => 'sanitize_text_field',
			'created_order'                => 'sanitize_key',
			'blocking_time'                => 'sanitize_text_field',
			'blocking_time_order'          => 'sanitize_key',
			'blocked_until'                => 'sanitize_text_field',
			'blocked_until_order'          => 'sanitize_key',
			'scope'                        => 'sanitize_text_field',
			'scope_order'                  => 'sanitize_key',
			'originator_name'              => 'sanitize_text_field',
			'originator_name_order'        => 'sanitize_key',
			'collection_history'           => 'sanitize_text_field',
			'collection_history_order'     => 'sanitize_key',
			'author_name'                  => 'sanitize_text_field',
			'author_name_order'            => 'sanitize_key',
			'content'                      => 'sanitize_text_field',
			'content_order'                => 'sanitize_key',
			'annotation'                   => 'sanitize_text_field',
			'annotation_order'             => 'sanitize_key',
			'language'                     => 'sanitize_text_field',
			'language_order'               => 'sanitize_key',
			'editor_name'                  => 'sanitize_text_field',
			'editor_name_order'            => 'sanitize_key',
			'notes'                        => 'sanitize_text_field',
			'notes_order'                  => 'sanitize_key',
			'date_cataloguing'             => 'sanitize_text_field',
			'date_cataloguing_order'       => 'sanitize_key',
			'geo_coordinates'              => 'sanitize_text_field',
			'geo_coordinates_order'        => 'sanitize_key',
			'geo_location'                 => 'sanitize_text_field',
			'geo_location_order'           => 'sanitize_key',
			'license_uri'                  => 'sanitize_text_field',
			'license_uri_order'            => 'sanitize_key',
			'license_digital_object'       => 'sanitize_text_field',
			'license_digital_object_order' => 'sanitize_key',
			'tags'                         => 'sanitize_text_field',
			'tags_order'                   => 'sanitize_key',
			'target_os'                    => 'sanitize_text_field',
			'csv_delimiter'                => 'sanitize_text_field',
			'target_path'                  => 'starg_sanitize_file_path',
		);
	}

	/**
	 * Describes which structure we need for dynamic generated form inputs.
	 */
	protected function get_dynamic_input_names(): array {
		return array(
			'static' => array(
				'column_name'  => 'txt',
				'column_value' => 'value',
				'order'        => 'int',
			),
		);
	}

	/**
	 * Describes which inputs of the form are required.
	 * If a form has not delivered one of these inputs, we do not trigger any action but display an error message.
	 * For performance reasons we use the input names as keys for the array. This way we can use isset() instead of in_array().
	 * @return array
	 */
	protected function get_required_input_names(): array {
		return array();
	}

	public function get_label_for_static_fields( string $field_name ): string {
		switch( $field_name ) {
			case ( 'column_name' ) :
				return esc_attr__( 'Column name', 'sip' );
			case ( 'column_value' ) :
				return esc_attr__( 'Column value', 'sip' );
			case ( 'order' ) :
				return esc_attr__( 'Order', 'sip' );
		}

		return '';
	}

	/* -------------------------------------------------
	* Transform a value into a different format.
	* ------------------------------------------------- */
	protected function transform_data( string $value, string $config) {
		if ( empty( $config ) ) { return $value; }

		// Example: "date:Y-m-d"
		if (str_starts_with($config, 'date:')) {
			$format = str_replace('date:', '', $config);

			$timestamp = strtotime($value);
			return $timestamp ? date($format, $timestamp) : $value;
		}

		return $value;
	}

}

/**
 * Sanitizes a user input and allows formats like windows paths.
 */
function starg_sanitize_file_path(string $path): string {
	$path = wp_unslash( $path );
	$path = str_replace("\0", '', $path);
	$path = trim($path);

	// if the file path looks suspicious, we don't save anything!
	if ( ! preg_match('#^[a-zA-Z0-9_\-\/\\\\:\.\s]+$#', $path) ) {
		return '';
	}

	return $path;
}
