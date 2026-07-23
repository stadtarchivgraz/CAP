<?php
defined( 'ABSPATH' ) || exit;

class Import_Mapping_DB_Table {
	private const STARG_DB_TABLE_NAME           = 'starg_mapping_for_import';
	private const STARG_DB_TABLE_VERSION        = '1.0.1';
	private const STARG_TABLE_COL_ID            = 'id';
	private const STARG_TABLE_COL_INST_ID       = 'institution_id';
	private const STARG_TABLE_COL_MAPPING       = 'mapping';
	private const STARG_TABLE_COL_CONFIGURATION = 'configuration';
	private const STARG_TABLE_COL_VERSION       = 'version';
	private const STARG_TABLE_COL_CREATED_AT    = 'created_at';
	private const STARG_TABLE_COL_UPDATED_AT    = 'updated_at';

	/**
	 * Create the database table for the import mapping data.
	 * Also creates an option in the db with the name of the table + '_version' with the version number as its value.
	 */
	public static function create_db_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . self::STARG_DB_TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$create_table_query = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			" . self::STARG_TABLE_COL_ID . " BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
			" . self::STARG_TABLE_COL_INST_ID . " INT unsigned NOT NULL,
			" . self::STARG_TABLE_COL_MAPPING . " JSON,
			" . self::STARG_TABLE_COL_CONFIGURATION . " JSON,
			" . self::STARG_TABLE_COL_VERSION . " INT unsigned NOT NULL,
			" . self::STARG_TABLE_COL_CREATED_AT . " DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			" . self::STARG_TABLE_COL_UPDATED_AT . " DATETIME NOT NULL,
			PRIMARY KEY (" . self::STARG_TABLE_COL_ID . ") ) ENGINE=InnoDB $charset_collate;
		";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $create_table_query );

		add_option( self::STARG_DB_TABLE_NAME . '_version', self::STARG_DB_TABLE_VERSION );
	}

	/**
	 * If the version of the database table stored as option differs from the one set here, we need to update the table columns.
	 * This is needed if we add more columns to the table for example.
	 */
	public static function maybe_update_db_table() {
		$installed_version = get_option( self::STARG_DB_TABLE_NAME . '_version' );
		if ( $installed_version === self::STARG_DB_TABLE_VERSION ) { return; }

		$db_update = new self();
		$db_update->update_self();
	}

	/**
	 * Update the database table for the import mapping data.
	 */
	private function update_self() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::STARG_DB_TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE `{$table_name}` (
			" . self::STARG_TABLE_COL_ID . " BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
			" . self::STARG_TABLE_COL_INST_ID . " INT unsigned NOT NULL,
			" . self::STARG_TABLE_COL_MAPPING . " JSON,
			" . self::STARG_TABLE_COL_CONFIGURATION . " JSON,
			" . self::STARG_TABLE_COL_VERSION . " INT unsigned NOT NULL,
			" . self::STARG_TABLE_COL_CREATED_AT . " DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			" . self::STARG_TABLE_COL_UPDATED_AT . " DATETIME NOT NULL,
			PRIMARY KEY (" . self::STARG_TABLE_COL_ID . ") ) ENGINE=InnoDB $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		update_option( $this::STARG_DB_TABLE_NAME . '_version', $this::STARG_DB_TABLE_VERSION );
	}

	/**
	 * Returns the saved import mapping data based on the provided institution id and the schema version.
	 * @param int $inst_id The term-ID of the institution as set for @see Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG
	 * @param int $version The schema version of the data.
	 * @return array|null
	 */
	public static function get_input_by_institution_id( int $inst_id, int $version = 1 ) {
		if ( ! $inst_id ) { return NULL; }
		global $wpdb;

		$table_name  = $wpdb->prefix . self::STARG_DB_TABLE_NAME;
		$col_inst_id = self::STARG_TABLE_COL_INST_ID;
		$col_version = self::STARG_TABLE_COL_VERSION;

		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE $col_inst_id = %d AND $col_version = %d", $inst_id, $version );
		$result = $wpdb->get_row( $sql, ARRAY_A );

		return ( $result ) ? $result : NULL;
	}


	//////////
	// Get the values of the private constants for use in other files.
	//////////

	public static function get_table_name() {
		return self::STARG_DB_TABLE_NAME;
	}
	public static function get_table_version() {
		return self::STARG_DB_TABLE_VERSION;
	}
	public static function get_col_slug_id() {
		return self::STARG_TABLE_COL_ID;
	}
	public static function get_col_slug_institution_id() {
		return self::STARG_TABLE_COL_INST_ID;
	}
	public static function get_col_slug_mapping() {
		return self::STARG_TABLE_COL_MAPPING;
	}
	public static function get_col_slug_configuration() {
		return self::STARG_TABLE_COL_CONFIGURATION;
	}
	public static function get_col_slug_version() {
		return self::STARG_TABLE_COL_VERSION;
	}
	public static function get_col_slug_created_at() {
		return self::STARG_TABLE_COL_CREATED_AT;
	}
	public static function get_col_slug_updated_at() {
		return self::STARG_TABLE_COL_UPDATED_AT;
	}

}
