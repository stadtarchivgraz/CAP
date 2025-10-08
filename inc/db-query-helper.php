<?php

class DB_Query_Helper {

	/**
	 * Retrieve a list of archive tags based on an archive record ID.
	 * @param int $archive_id
	 * @return object|null|false
	 */
	public static function starg_get_archive_tags(int $archive_id) {
		if (! $archive_id) {
			return false;
		}

		global $wpdb;
		$all_archivals_sql     = "SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d";
		$all_archive_archivals = $wpdb->get_results($wpdb->prepare($all_archivals_sql, $archive_id));
		if (! $all_archive_archivals) {
			return false;
		}

		$all_archival_ids = implode(',', wp_list_pluck($all_archive_archivals, 'object_id'));
		$sql = "SELECT a.term_taxonomy_id, count(a.term_taxonomy_id) as count
		FROM $wpdb->term_relationships a
			LEFT JOIN $wpdb->term_taxonomy b ON a.term_taxonomy_id = b.term_taxonomy_id
		WHERE taxonomy = 'archival_tag'
			AND a.object_id IN (%s)
		GROUP BY a.term_taxonomy_id";

		return $wpdb->get_results($wpdb->prepare($sql, $all_archival_ids));
	}

	/**
	 * Retrieve the name for an existing archival tag.
	 * @param int $archival_tag_id
	 * @return string
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
	 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
	 *                                    Default: false = includes posts with all post_status values.
	 * @return int The number of posts found. 0 if no post was found
	 */
	public static function starg_get_upload_purpose_post_count(string $upload_purpose_option, bool $only_published_posts = false): int {
		if (! $upload_purpose_option) {
			return 0;
		}

		$post_status_filter = '';
		if ($only_published_posts) {
			$post_status_filter = "AND p.post_status = 'publish'";
		}

		// todo: we should change the way we store the upload purposes! Currently, we're saving the translated string from the plugin options.
		// This means we get different values for different user! This means we can't filter ALL entries based on this metadata - we can only filter all german ones, all english ones and so on!
		// maybe bypass this problem by looping through every translation?
		global $wpdb;
		$upload_purpose_sql = "SELECT count(pm.post_id)
		FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->posts p ON pm.post_id = p.ID
		WHERE pm.meta_key = '_archival_upload_purpose'
			AND pm.meta_value = %s
			AND p.post_type = 'archival'
			$post_status_filter";
		$result = $wpdb->get_var($wpdb->prepare($upload_purpose_sql, $upload_purpose_option));

		return (int) $result ?: 0;
	}

	/**
	 * Retrieves the post count of the users archival posts for an specific upload purpose.
	 *
	 * @param int $user_archive
	 * @param string $upload_purpose_option
	 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
	 *                                    Default: false = includes posts with all post_status values.
	 * @return int The number of posts found. 0 if no post was found
	 */
	public static function starg_get_upload_purpose_post_count_for_user(int $user_archive, string $upload_purpose_option, bool $only_published_posts = false): int {
		if (! $user_archive || ! $upload_purpose_option) {
			return 0;
		}
		$post_status_filter = '';
		if ($only_published_posts) {
			$post_status_filter = "AND post_status = 'publish'";
		}

		global $wpdb;
		$sql = "SELECT count(post_id)
		FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts ON post_id = ID
			LEFT JOIN $wpdb->term_relationships ON object_id = ID
		WHERE term_taxonomy_id = %d
			AND meta_key = '_archival_upload_purpose'
			AND meta_value = %s
			$post_status_filter";
		$result = $wpdb->get_var($wpdb->prepare($sql, $user_archive, $upload_purpose_option));

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
		$post_status_filter = '';
		if ($only_published_posts) {
			$post_status_filter = "AND post_status = 'publish'";
		}

		global $wpdb;
		$sql = "SELECT count(post_id) as sip_count, DATE_FORMAT(meta_value, '%Y') as sip_date
		FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts ON post_id = ID
		WHERE meta_key = '_archival_from'
			$post_status_filter
		GROUP BY sip_date
		ORDER BY sip_date DESC";

		$result = $wpdb->get_results($sql);
		return $result ?: array();
	}

	/**
	 * Retrieve the number of an users archival record posts by year.
	 * @param bool $only_published_posts [Optional] Adds an additional filter to the database call to filter by post_status.
	 *                                    Default: false = includes posts with all post_status values.
	 * @return array|object
	 */
	public static function starg_get_upload_year_post_count_for_user(int $user_archive, bool $only_published_posts = false): array|object {
		if (! $user_archive) {
			return array();
		}
		$post_status_filter = '';
		if ($only_published_posts) {
			$post_status_filter = "AND post_status = 'publish'";
		}

		global $wpdb;
		$sql = "SELECT count(post_id) as sip_count, DATE_FORMAT(meta_value, '%Y') as sip_date
		FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts ON post_id = ID
			LEFT JOIN $wpdb->term_relationships ON object_id = ID
		WHERE term_taxonomy_id = %d
			AND meta_key = '_archival_from'
			$post_status_filter
		GROUP BY sip_date
		ORDER BY sip_date DESC";

		$result = $wpdb->get_results($wpdb->prepare($sql, $user_archive));
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
	 * Retrieve all email addresses of all editors.
	 * @param int $user_archive_id [Optional] if set, we only receive the email addresses for a specific archive_id.
	 * @return array
	 */
	public static function get_all_admin_email_addresses( int $user_archive_id = 0 ): array {
		return DB_Query_Helper::get_all_users_email_addresses( 'administrator', $user_archive_id );
	}

	/**
	 * Retrieve all email addresses of all administrators.
	 * @param int $user_archive_id [Optional] if set, we only receive the email addresses for a specific archive_id.
	 * @return array
	 */
	public static function get_all_editor_email_addresses( int $user_archive_id = 0 ): array {
		return DB_Query_Helper::get_all_users_email_addresses( 'editor', $user_archive_id );
	}

}
