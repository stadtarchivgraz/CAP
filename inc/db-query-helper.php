<?php

class DB_Query_Helper {

	/**
	 * Retrieve a list of archive tags based on an archive record ID.
	 * @param int $archive_id
	 * @return object|null|false
	 */
	public static function starg_get_archive_tags( int $archive_id = 0 ) {
		$term_args = array(
			'taxonomy'   => Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG,
			'hide_empty' => false,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		);

		if ( $archive_id ) {
			global $wpdb;
			$all_archivals_sql = "SELECT DISTINCT t.term_id
			FROM $wpdb->term_relationships tr
				INNER JOIN $wpdb->posts p ON tr.object_id = p.ID
				INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN $wpdb->terms t ON tt.term_id = t.term_id
				INNER JOIN $wpdb->term_relationships tr2 ON tr2.object_id = p.ID
				INNER JOIN $wpdb->term_taxonomy tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
			WHERE p.post_type = %s
				AND tt.taxonomy = %s
				AND tt2.taxonomy = %s
				AND tt2.term_taxonomy_id = %d";

			$all_archive_archivals = $wpdb->get_results($wpdb->prepare($all_archivals_sql, Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG, Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG, Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG, $archive_id));

			if ( $all_archive_archivals ) {
				$all_archival_ids = wp_list_pluck($all_archive_archivals, 'term_id');
				$term_args['include'] = $all_archival_ids;
				return get_terms( $term_args );
			}
		}

		return get_terms( $term_args );
	}

	/**
	 * Retrieve the name for an existing archival tag.
	 * @param int $archival_tag_id
	 * @return string
	 * @deprecated we create all needed data within @see DB_Query_Helper::starg_get_archive_tags()
	 */
	public static function starg_get_archival_tag_name(int $archival_tag_id) {
		global $wpdb;
		$result = $wpdb->get_var($wpdb->prepare("SELECT name FROM $wpdb->terms WHERE term_id = %d", $archival_tag_id));
		return esc_attr($result);
	}

	/**
	 * Retrieves the post count of all archival posts from all users for an specific upload purpose.
	 *
	 * @param string $upload_purpose_option
	 * @param array{archive: string, tag: string, purpose: string, year: string, search: string, post_status: string} $args
	 * @return int The number of posts found. 0 if no post was found
	 */
	public static function starg_get_upload_purpose_post_count(string $upload_purpose_option, array $args): int {
		if (! $upload_purpose_option) {
			return 0;
		}

		global $wpdb;
		$defaults = array(
			'archive'     => '',
			'tag'         => '',
			'purpose'     => '',
			'year'        => '',
			'search'      => '',
			'post_status' => 'draft, pending, publish',
			'post_type'   => Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG,
		);
		$args = wp_parse_args( $args, $defaults );

		$where   = array();
		$prepare = array();

		// we don't want to filter the purpose.
		$where[]   = "pm.meta_key = '_archival_upload_purpose'";
		$where[]   = "pm.meta_value = %s";
		$prepare[] = $upload_purpose_option;

		$where[]   = "p.post_type = %s";
		$prepare[] = $args['post_type'];
		$where[]   = "p.post_status NOT IN ('trash', 'auto-draft', 'inherit')";

		if (! empty($args['post_status'])) {
			$where[]   = "p.post_status = %s";
			$prepare[] = $args['post_status'];
		}

		$year_join = '';
		if (! empty($args['year'])) {
			$year_join = "INNER JOIN $wpdb->postmeta pm_year
				ON p.ID = pm_year.post_id
				AND pm_year.meta_key = '_archival_from'";

			$where[]   = "pm_year.meta_value = %d";
			$prepare[] = (int) $args['year'];
		}

		if (! empty($args['search'])) {
			$where[]   = "p.post_title LIKE %s";
			$prepare[] = '%' . $wpdb->esc_like($args['search']) . '%';
		}

		$tag_join = '';
		if ( ! empty( $args['tag'] ) ) {
			$tag_join = "
				INNER JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
				INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN $wpdb->terms t ON tt.term_id = t.term_id
			";

			$where[]   = "tt.taxonomy = %s";
			$where[]   = "t.slug = %s";
			$prepare[] = Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG;
			$prepare[] = $args['tag'];
		}

		$archive_join = '';
		if ( ! empty( $args['archive'] ) ) {
			$archive_join = "
				INNER JOIN $wpdb->term_relationships atr ON p.ID = atr.object_id
				INNER JOIN $wpdb->term_taxonomy att ON atr.term_taxonomy_id = att.term_taxonomy_id
				INNER JOIN $wpdb->terms at ON att.term_id = at.term_id
			";

			$where[]   = "att.taxonomy = %s";
			$where[]   = "at.slug = %s";
			$prepare[] = Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG;
			$prepare[] = $args['archive'];
		}

		// todo: we should change the way we store the upload purposes! Currently, we're saving the translated string from the plugin options.
		// This means we get different values for different user! This means we can't filter ALL entries based on this metadata - we can only filter all german ones, all english ones and so on!
		// maybe bypass this problem by looping through every translation?
		$upload_purpose_sql = "SELECT COUNT(DISTINCT pm.post_id)
			FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->posts p ON pm.post_id = p.ID
			$year_join
			$tag_join
			$archive_join
			WHERE " . implode( ' AND ', $where );

		$result = (int) $wpdb->get_var( $wpdb->prepare( $upload_purpose_sql, $prepare ) );

		return (int) $result ?: 0;
	}

	/**
	 * Retrieves the post count of the users archival posts for an specific upload purpose.
	 *
	 * @param int $user_archive
	 * @param string $upload_purpose_option
	 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
	 *                                    Default: false = includes posts with all post_status values.
	 * @deprecated use @see DB_Query_Helper::starg_get_upload_purpose_post_count instead!
	 * @return int The number of posts found. 0 if no post was found
	 */
	public static function starg_get_upload_purpose_post_count_for_user(int $user_archive, string $upload_purpose_option, bool $only_published_posts = false): int {
		if (! $user_archive || ! $upload_purpose_option) {
			return 0;
		}
		$post_status_filter = 'AND p.post_status != "trash"';
		if ($only_published_posts) {
			$post_status_filter = "AND p.post_status = 'publish'";
		}

		global $wpdb;
		$sql = "SELECT count(pm.post_id)
		FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->posts p ON pm.post_id = p.ID
			LEFT JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID
		WHERE tr.term_taxonomy_id = %d
			AND pm.meta_key = '_archival_upload_purpose'
			AND pm.meta_value = %s
			AND p.post_type = %s
			$post_status_filter";
		$result = $wpdb->get_var($wpdb->prepare($sql, $user_archive, $upload_purpose_option, Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG));

		return (int) $result ?: 0;
	}

	/**
	 * Retrieve the number of all archival record posts by year.
	 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
	 *                                    Default: false = includes posts with all post_status values.
	 * @return array|object
	 *         array(
	 *             0 => {
	 *                 'sip_count' => '1',
	 *                 'sip_date'  => '2024',
	 *             },
	 *         )
	 */
	public static function starg_get_upload_year_post_count(bool $only_published_posts = false): array|object {
		$post_status_filter = 'AND p.post_status != "trash"';
		if ($only_published_posts) {
			$post_status_filter = "AND p.post_status = 'publish'";
		}

		global $wpdb;
		$sql = "SELECT count(pm.post_id) as sip_count, DATE_FORMAT(pm.meta_value, '%Y') as sip_date
		FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->posts p ON pm.post_id = p.ID
		WHERE pm.meta_key = '_archival_from'
			$post_status_filter
		GROUP BY sip_date
		ORDER BY sip_date DESC";

		$result = $wpdb->get_results($sql);
		return $result ?: array();
	}

	/**
	 * Retrieve the number of an users archival record posts by year.
	 * @param int $archive_id
	 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
	 *                                    Default: false = includes posts with all post_status values.
	 * @return array|object
	 */
	public static function starg_get_upload_year_post_count_for_user(int $archive_id, bool $only_published_posts = false): array|object {
		if (! $archive_id) {
			return array();
		}
		$post_status_filter = 'AND p.post_status != "trash"';
		if ($only_published_posts) {
			$post_status_filter = "AND p.post_status = 'publish'";
		}

		global $wpdb;
		$sql = "SELECT count(pm.post_id) as sip_count, DATE_FORMAT(pm.meta_value, '%Y') as sip_date
		FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->posts p ON pm.post_id = p.ID
			LEFT JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID
		WHERE tr.term_taxonomy_id = %d
			AND pm.meta_key = '_archival_from'
			$post_status_filter
		GROUP BY sip_date
		ORDER BY sip_date DESC";

		$result = $wpdb->get_results($wpdb->prepare($sql, $archive_id));
		return $result ?: array();
	}

	/**
	 * Receive all archival sip folders from a user by their id.
	 * @param int $user_id
	 * @return array Array with all sip folders or empty array on failure.
	 */
	public static function starg_get_archival_sip_folders_by_user_id(int $user_id = 0): array {
		if (! $user_id) {
			return array();
		}

		global $wpdb;
		$archival_sip_folders_sql = "SELECT pm.meta_value
			FROM {$wpdb->postmeta} AS pm
				LEFT JOIN {$wpdb->posts} AS p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_archival_sip_folder'
				AND p.post_author = %d";
		$archival_sip_folders = $wpdb->get_results($wpdb->prepare($archival_sip_folders_sql, $user_id));
		if (! $archival_sip_folders) {
			return array();
		}
		return wp_list_pluck($archival_sip_folders, 'meta_value');
	}

	/**
	 * Retrieve the post-id of an archival record based on a sip_folder.
	 * @param string $sip_folder
	 * @return int|false
	 */
	public static function starg_get_archival_id_by_sip_folder(string $sip_folder) {
		if (! $sip_folder) {
			return false;
		}

		global $wpdb;
		$archival_post_id_sql = "SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_archival_sip_folder'
				AND meta_value = %s";
		$archival_id = $wpdb->get_var($wpdb->prepare($archival_post_id_sql, $sip_folder));
		return (int) $archival_id ?? false;
	}

	/**
	 * Retrieve all sip folder ids for which we have an existing archival post.
	 */
	public static function starg_get_all_archival_sip_folders_from_posts(): array {
		global $wpdb;
		$archival_sip_folders = $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_archival_sip_folder'");
		if (! $archival_sip_folders) {
			return array();
		}

		return wp_list_pluck($archival_sip_folders, 'meta_value');
	}

	/**
	 * Retrieve posts with a specific archival SIP folder meta value,
	 * filtered by post status and publish date.
	 *
	 * The query joins the postmeta and posts tables to fetch:
	 * - Post ID
	 * - Meta value of '_archival_sip_folder'
	 * - Post author
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string|string[] $post_status One or multiple post statuses to filter by.
	 * @param string          $date        Upper limit for post_date (format: 'Y-m-d H:i:s').
	 *
	 * @return array List of matching posts with ID, meta_value, and post_author.
	 */
	public static function starg_get_posts_with_archival_sip_meta(string $filter_status = '', string $filter_date = ''): array {
		global $wpdb;

		$archival_sips_sql = "SELECT p.ID, pm.meta_value, p.post_author
			FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_archival_sip_folder'
				AND p.post_type = %s
				AND p.post_status IN (%s)
				AND p.post_date <= %s";
		$archival_sip_folders = $wpdb->get_results($wpdb->prepare($archival_sips_sql, Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG, $filter_status, $filter_date));
		if (! $archival_sip_folders) {
			return array();
		}

		return $archival_sip_folders;
	}

	/**
	 * Retrieve email addresses of all users.
	 * @param string|string[] $user_role       [Optional] If specified, only users with a specific role will be returned.
	 * @param int             $user_archive_id [Optional] If specified, only users associated with a certain institution will be returned.
	 * @return array          Array with all the email addresses, or empty array if nothing was found.
	 */
	private static function get_all_users_email_addresses( $user_role = '', int $user_archive_id = 0 ): array {
		$args = array(
			'role'       => $user_role,
			'fields'     => array( 'ID', 'user_email', ),
			'orderby'    => 'user_login',
			'order'      => 'ASC',
		);

		// only select editors which are related to a specific archive.
		if ( $user_archive_id ) {
			$args[ 'meta_key' ]   = 'user_archive';
			$args[ 'meta_value' ] = $user_archive_id;
		}

		$user_query = new WP_User_Query($args);

		if ( empty($user_query->results) ) { return array(); }

		$emails = array();
		foreach ($user_query->results as $user) {
			$emails[ $user->ID ] = $user->user_email;
		}
		return $emails;
	}

	/**
	 * Retrieve all email addresses of all administrators.
	 * @param int $user_archive_id [Optional] if set, we only receive the email addresses for a specific archive_id.
	 * @return array
	 */
	public static function get_all_admin_email_addresses( int $user_archive_id = 0 ): array {
		return DB_Query_Helper::get_all_users_email_addresses( 'administrator', $user_archive_id );
	}

	/**
	 * Retrieve all email addresses of all editors.
	 * @param int $user_archive_id [Optional] if set, we only receive the email addresses for a specific archive_id.
	 * @return array
	 */
	public static function get_all_editor_email_addresses( int $user_archive_id = 0 ): array {
		return DB_Query_Helper::get_all_users_email_addresses( 'editor', $user_archive_id );
	}

	/**
	 * Checks if the plugin was set up correctly by adding at least one institution as archive in the custom taxonomy Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG.
	 *
	 * @return int|false|null
	 *    If the website has only one institution to upload files to, we return their term_id.
	 *    If the website has more than one institution, we return false.
	 *    If the website does not have set any institution, we return null and set an Error.
	 */
	public static function maybe_get_single_archive_id() {
		// do we have more than one entires in Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG?
		$min_registered_archives = get_terms( array( 'taxonomy' => Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG, 'fields' => 'ids', 'number' => 2, 'orderby' => 'term_id', 'hide_empty' => false, ));
		if ( ! $min_registered_archives || is_wp_error( $min_registered_archives ) ) {
			$logging = apply_filters( 'starg/logging', null );
			if ( $logging instanceof Starg_Logging && $logging->error_logging_enabled ) {
				// translators: %s: Name of the custom taxonomy.
				$logging->create_log_entry( sprintf( esc_attr__( 'No institution set as %s.', 'sip' ), Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG ), Log_Severity::Error );
			}
			// translators: %s: Name of the custom taxonomy.
			_doing_it_wrong( __FUNCTION__, sprintf( esc_attr__( 'No institution set as %s.', 'sip' ), Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG ), '3.4.8' );
			return null;
		}

		if ( 1 === count( $min_registered_archives ) ) {
			return (int) $min_registered_archives[0];
		}

		return false;
	}

}
