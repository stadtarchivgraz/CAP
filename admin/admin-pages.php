<?php

require_once( STARG_SIP_PLUGIN_BASE_DIR . "inc/user-helper.php" );
class Starg_Admin_Pages {

	private static array $skipped_user_roles = array(
		'administrator',
		Starg_User_Helper::STARG_TEST_USER_ROLE,
	);

	public static function init() {
		add_action( 'admin_menu', array( 'Starg_Admin_Pages', 'starg_add_statistics_page' ) );
	}

	/**
	 * Adds a page in the WordPress backend to display some statistics.
	 */
	public static function starg_add_statistics_page() {
		add_submenu_page(
			'edit.php?post_type=archival',
			esc_html__( 'Statistics', 'sip' ),
			esc_html__( 'Statistics', 'sip' ),
			'edit_others_pages',
			'starg-statisitcs',
			array( 'Starg_Admin_Pages', 'starg_render_statistics_page' ),
		);
	}

	/**
	 * Renders the statistics in the WordPress backend.
	 */
	public static function starg_render_statistics_page() : void {
		$export_statistics = apply_filters('starg/export_statistics', null);
		if ($export_statistics instanceof Export_Statistics) {
			$export_statistics->display_notification();
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline" style="margin-bottom:16px;"><?php esc_html_e( 'Statistics', 'sip' ); ?></h1>
			<hr class="wp-header-end">
			<?php
			$sip_folder         = starg_get_archival_upload_path();
			$all_uploaded_files = self::_get_user_statistics( $sip_folder );
			if ( empty( $all_uploaded_files ) ) :
				?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'There are no elements to display.', 'sip' ); ?></p>
				</div>
			</div><?php // end .wrap ?>
				<?php
				return;
			endif;
			?>

			<h2><?php esc_html_e( 'Archive-related statistics', 'sip' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Archive', 'sip' ); ?></th>
						<th><?php esc_html_e( 'User', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Submissions', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Files submitted', 'sip' ); ?></th>
					</tr>
				</thead>
				<?php self::display_archive_statistics_table_rows( $all_uploaded_files[ 'valid_users' ][ 'statistics_by_archive' ] ); ?>
			</table>

			<h2><?php esc_html_e( 'Statistics by user', 'sip' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Username', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Archive', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Submissions', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Files submitted', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Extra*', 'sip' ); ?></th>
					</tr>
				</thead>
				<?php self::display_user_statistics_table_rows( $all_uploaded_files[ 'valid_users' ] ); ?>
			</table>

			<h2><?php esc_html_e( 'User', 'sip' ); ?></h2>
			<?php self::render_user_role_table(); ?>

			<hr style="margin-top: 2em;margin-bottom:2em;">

			<h2><?php esc_html_e( 'Data from administrators or test users', 'sip' ); ?></h2>
			<h3><?php esc_html_e( 'Archive-related statistics', 'sip' ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Archive', 'sip' ); ?></th>
						<th><?php esc_html_e( 'User', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Submissions', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Files submitted', 'sip' ); ?></th>
					</tr>
				</thead>
				<?php self::display_archive_statistics_table_rows( $all_uploaded_files[ 'skipped_users' ][ 'statistics_by_archive' ] ); ?>
			</table>

			<h3><?php esc_html_e( 'Statistics by user', 'sip' ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Username', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Archive', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Submissions', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Files submitted', 'sip' ); ?></th>
						<th><?php esc_html_e( 'Extra*', 'sip' ); ?></th>
					</tr>
				</thead>
				<?php self::display_user_statistics_table_rows( $all_uploaded_files[ 'skipped_users' ] ); ?>
			</table>

			<?php if ( $all_uploaded_files[ 'drafts' ] ) : ?>
				<hr style="margin-top: 2em;margin-bottom:2em;">

				<h3><?php esc_html_e( 'Drafts', 'sip' ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Username', 'sip' ); ?></th>
							<th><?php esc_html_e( 'Uploads', 'sip' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php self::display_drafts_or_uploads_table_rows( $all_uploaded_files[ 'drafts' ] ); ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( $all_uploaded_files[ 'uploads' ] ) : ?>
				<hr style="margin-top: 2em;margin-bottom:2em;">

				<h3><?php esc_html_e( 'Uploads without post or metadata', 'sip' ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Username', 'sip' ); ?></th>
							<th><?php esc_html_e( 'Uploads', 'sip' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php self::display_drafts_or_uploads_table_rows( $all_uploaded_files[ 'uploads' ] ); ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( $export_statistics ) : ?>
				<form class="form" action="" method="get" style="margin-top: 2rem;">
					<?php
					$export_statistics_url = '#';
					if ( isset( $export_statistics->url_endpoint ) ) {
						$export_statistics_url = add_query_arg( array(
							$export_statistics->url_endpoint  => true,
							$export_statistics->form_name_key => $export_statistics->form_name,
							$export_statistics->nonce_key     => wp_create_nonce( $export_statistics->nonce_action ),
						) );
					}
					?>
					<a class="button" href="<?php echo $export_statistics_url; ?>" name="export_csv" type="submit">
						<?php esc_html_e( 'Export as CSV', 'sip' ); ?>
					</a>
				</form>
			<?php endif; ?>
		</div>
	<?php
	}


	/***************/
	/* HTML Tables */
	/***************/

	/**
	 * Renders the table rows for the statistics data.
	 * @param array $data
	 * @return void
	 */
	protected static function display_user_statistics_table_rows( array $data ) : void {
		if ( empty( $data ) || ! isset( $data['data'] ) ) :
			?>
			<tbody>
				<tr>
					<td colspan="6">
						<div>
							<p><?php esc_html_e( 'There are no elements to display.', 'sip' ); ?></p>
						</div>
					</td>
				<tr>
			</tbody>
			<?php
			return;
		endif;
		?>

		<tbody>
			<?php foreach ($data['data'] as $user_id => $single_submission) : ?>
				<tr data-user_id="<?php echo esc_attr($user_id); ?>">
					<td><?php echo esc_html( $single_submission['display_name'] ); ?></td>
					<td><?php echo esc_html( $single_submission['user_login'] ); ?></td>
					<td><?php echo esc_html( $single_submission['archive_name'] ); ?></td>
					<td><?php echo esc_html( $single_submission['users_submission'] ); ?></td>
					<td><?php echo esc_html( $single_submission['users_files'] ); ?></td>
					<td><?php echo esc_html( $single_submission['extra'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th><?php esc_html_e('Total', 'sip'); ?></th>
				<th colspan="2"><?php echo esc_html($data['number_users']); ?></th>
				<th><?php echo esc_html($data['number_submissions']); ?></th>
				<th><?php echo esc_html($data['number_submitted_files']); ?></th>
				<?php // translators: %s: formatted filesize. ?>
				<th><?php printf( esc_html__( 'Total filesize: %s', 'sip' ), starg_format_bytes( (int) $data['filesize'] ) ); ?></th>
			</tr>
		</tfoot>
	<?php
	}

	/**
	 * Renders the table rows for the statistics data.
	 * @param array $data
	 * @return void
	 */
	protected static function display_archive_statistics_table_rows( array $data ) : void {
		if ( empty( $data ) || ! isset( $data['data'] ) ) :
			?>
			<tbody>
				<tr>
					<td colspan="4">
						<div>
							<p><?php esc_html_e( 'There are no elements to display.', 'sip' ); ?></p>
						</div>
					</td>
				<tr>
			</tbody>
			<?php
			return;
		endif;
		?>

		<tbody>
			<?php foreach ( $data['data'] as $archive => $archive_data ) : ?>
				<tr>
					<th><?php echo esc_html( $archive ); ?></th>
					<th><?php echo esc_html( $archive_data['user_by_archive'] ); ?></th>
					<th><?php echo esc_html( $archive_data['submitted_by_archive'] ); ?></th>
					<th><?php echo esc_html( $archive_data['files_by_archive'] ); ?></th>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th><?php echo is_countable( $data['data'] ) ? count( $data['data'] ) : 0; ?></th>
				<th><?php echo esc_html( $data['number_users'] ); ?></th>
				<th><?php echo esc_html( $data['number_submissions'] ); ?></th>
				<th><?php echo esc_html( $data['number_submitted_files'] ); ?></th>
			</tr>
		</tfoot>
	<?php
	}

	protected static function display_drafts_or_uploads_table_rows( array $data ): void {
		?>
		<tbody>
			<?php foreach ( $data as $user_id => $user_data ) : ?>
				<tr data-user_id="<?php echo esc_attr($user_id); ?>">
					<td><?php echo esc_attr( get_user_by( 'ID', $user_id )->display_name ); ?></td>
					<td>
						<ul style="margin: 0;">
							<?php
							foreach ( $user_data as $key => $value ) :
								if ( 'filesize' === $key ) { continue; }
								?>
								<li>
									<?php
									// translators: %1$s: Number of files. %2$s: Name of the folder.
									printf( _n( 'There is %1$s file in folder %2$s.', 'There are %1$s files in folder %2$s.', (int) $value, 'sip' ), esc_attr( $value ), esc_attr( $key ) );
									?>
								</li>
							<?php endforeach; ?>
						</ul>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	<?php
	}

	/**
	 * Renders a HTML-Table with all user roles of the website and the number of users assigned to each role.
	 */
	private static function render_user_role_table() : void {
		$roles_count = self::get_user_role_stats();
		?>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'User role', 'sip' ); ?></th>
					<th><?php esc_html_e( 'Number of users', 'sip' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$total_user = 0;
				foreach ($roles_count as $role => $count) :
					$total_user += (int) $count;
					?>
					<tr>
						<td><?php echo esc_html( ucfirst(translate_user_role($role)) ); ?></td>
						<td><?php echo esc_html( $count ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td><?php esc_html_e( 'Total', 'sip' ); ?></td>
					<td><?php echo esc_html( $total_user ); ?></td>
				</tr>
			</tfoot>
		</table>
	<?php
	}


	/****************/
	/* prepare data */
	/****************/

	/**
	 * The main function to create the statistics.
	 * @param string $sip_folder
	 * @param bool $skip_test_user [Optional] whether include all users (admins and test user as well) in the statistics. default: true (skip them)
	 * @return array{valid_users:array, skipped_user:array, uploads_onl:array }
	 */
	private static function _get_user_statistics( string $sip_folder, bool $skip_test_user = true ) : array {
		$all_uploaded_files = Starg_Admin_Pages::_get_all_uploaded_files( $sip_folder );
		if ( empty( $all_uploaded_files ) ) { return array(); }

		$number_valid_users           = 0;
		$number_valid_submissions     = 0;
		$number_valid_submitted_files = 0;
		$filesize_valid               = 0;
		$drafts                       = array();
		$uploads                      = array();

		if ( $skip_test_user ) {
			$number_skipped_users           = 0;
			$number_skipped_submissions     = 0;
			$number_skipped_submitted_files = 0;
			$filesize_skipped               = 0;
		}

		$archive_name                  = esc_html__('unknown', 'sip');
		$statistics_by_archive         = array();
		$skipped_statistics_by_archive = array();
		$valid_user_data               = array();
		$skipped_user_data             = array();
		foreach ( $all_uploaded_files as $user_id => $single_submission ) {
			if ( empty( $single_submission ) ) {
				continue;
			}

			// todo: we might want to keep track of the deleted user!
			$user = get_user_by( 'id', (int) $user_id );
			if ( ! $user ) { continue; }// maybe a deleted user.

			$user_archive = esc_html( get_user_meta( $user_id, 'user_archive', true ) );
			$archive      = get_term( $user_archive, 'archive' );
			if ( ! $archive || is_wp_error( $archive ) ) {
				$logging = apply_filters( 'starg/logging', null );
				if ( $logging instanceof Starg_Logging ) {
					// translators: %d: User-ID.
					$logging->create_log_entry( sprintf( esc_html__( 'User with the ID %d has not selected an archive.', 'sip' ), (int) $user_id ) );
				}
			} else {
				$archive_name = $archive->name;
			}

			$user_files             = 0;
			$single_user_submission = 0;

			$archival_status = array();
			// loop through each submission and count the uploaded files.
			foreach ( $single_submission as $submission_id => $uploaded_files) {
				$archival_status[ $submission_id ] = 'upload';

				// we need to check the status of the submission. If it is an upload without post we can not count it as submission!
				$archival_id = DB_Query_Helper::starg_get_archival_id_by_sip_folder( $submission_id );
				if ( ! $archival_id ) {
					// todo: also include the filesize for uploads without posts.
					if ( 'filesize' !== $submission_id ) {
						$uploads[ $user_id ][ $submission_id ] = $uploaded_files;
					}
					continue;
				}

				$single_archival_status = get_post_status( $archival_id );
				// filter all drafts from statistics.
				if ( 'draft' === $single_archival_status ) {
					if ( 'filesize' !== $submission_id ) {
						$drafts[ $user_id ][ $submission_id ] = $uploaded_files;
					}
				} else {
					$user_files += (int) $uploaded_files;
					$single_user_submission++;
				}
				$archival_status[ $submission_id ] = array( $archival_id => $single_archival_status, );
			}

			// don't count uploads.
			if ( ! $user_files || ! $single_user_submission ) {
				continue;
			}

			// skipping user which are not relevant for statistics.
			if ( $skip_test_user ) {
				foreach ( $user->roles as $single_user_role ) {
					if (in_array($single_user_role, Starg_Admin_Pages::$skipped_user_roles)) {
						$number_skipped_users++;
						$number_skipped_submissions     += $single_user_submission;
						$number_skipped_submitted_files += $user_files;
						$filesize_skipped               += $single_submission['filesize'];

						$skipped_user_data['data'][$user_id] = array(
							'display_name'      => esc_html($user->display_name),
							'user_login'        => esc_html($user->user_login),
							'archive_name'      => esc_html($archive_name),
							'users_submission'  => $single_user_submission,
							'users_files'       => $user_files,
							'extra'             => esc_html($user->roles[0]),
							// 'archival_status'   => $archival_status, // todo: maybe include the post_status for each archival record?
						);

						// calculate the data by archive.
						$skipped_statistics_by_archive['data'][$archive_name]['user_by_archive']      = ($skipped_statistics_by_archive['data'][$archive_name]['user_by_archive'] ?? 0) + 1;
						$skipped_statistics_by_archive['data'][$archive_name]['submitted_by_archive'] = ($skipped_statistics_by_archive['data'][$archive_name]['submitted_by_archive'] ?? 0) + $single_user_submission;
						$skipped_statistics_by_archive['data'][$archive_name]['files_by_archive']     = ($skipped_statistics_by_archive['data'][$archive_name]['files_by_archive'] ?? 0) + $user_files;
						continue 2;
					}
				}
			}

			$number_valid_users++;
			$number_valid_submissions     += $single_user_submission;
			$number_valid_submitted_files += $user_files;
			$filesize_valid               += $single_submission['filesize'];

			// calculate the data by archive.
			$statistics_by_archive['data'][ $archive_name ][ 'user_by_archive' ]      = ($statistics_by_archive['data'][ $archive_name ][ 'user_by_archive' ] ?? 0) + 1;
			$statistics_by_archive['data'][ $archive_name ][ 'submitted_by_archive' ] = ($statistics_by_archive['data'][ $archive_name ][ 'submitted_by_archive' ] ?? 0) + $single_user_submission;
			$statistics_by_archive['data'][ $archive_name ][ 'files_by_archive' ]     = ($statistics_by_archive['data'][ $archive_name ][ 'files_by_archive' ] ?? 0) + $user_files;

			$valid_user_data['data'][ $user_id ] = array(
				'display_name'      => esc_html( $user->display_name ),
				'user_login'        => esc_html( $user->user_login ),
				'archive_name'      => esc_html( $archive_name ),
				'users_submission'  => $single_user_submission,
				'users_files'       => $user_files,
				'extra'             => esc_html( $user->roles[0] ),
				// 'archival_status'   => $archival_status, // todo: maybe include the post_status for each archival record?
			);
		}

		$statistics_by_archive['number_users']           = (int) $number_valid_users;
		$statistics_by_archive['number_submissions']     = (int) $number_valid_submissions;
		$statistics_by_archive['number_submitted_files'] = (int) $number_valid_submitted_files;
		$valid_user_data['number_users']                 = (int) $number_valid_users;
		$valid_user_data['number_submissions']           = (int) $number_valid_submissions;
		$valid_user_data['number_submitted_files']       = (int) $number_valid_submitted_files;
		$valid_user_data['statistics_by_archive']        = $statistics_by_archive;
		$valid_user_data['filesize']                     = (int) $filesize_valid;

		if ( $skip_test_user ) {
			$skipped_statistics_by_archive['number_users']           = (int) $number_skipped_users;
			$skipped_statistics_by_archive['number_submissions']     = (int) $number_skipped_submissions;
			$skipped_statistics_by_archive['number_submitted_files'] = (int) $number_skipped_submitted_files;
			$skipped_user_data['number_users']                       = (int) $number_skipped_users;
			$skipped_user_data['number_submissions']                 = (int) $number_skipped_submissions;
			$skipped_user_data['number_submitted_files']             = (int) $number_skipped_submitted_files;
			$skipped_user_data['statistics_by_archive']              = $skipped_statistics_by_archive;
			$skipped_user_data['filesize']                           = (int) $filesize_skipped;
		}

		return array( 'valid_users' => $valid_user_data, 'skipped_users' => $skipped_user_data, 'drafts' => $drafts, 'uploads' => $uploads, );
	}

	/**
	 * Wrapper function for @see Starg_Admin_Pages::_get_user_statistics
	 * @param string $sip_folder
	 * @param bool $skip_test_user [Optional] whether include all users (admins and test user as well) in the statistics. default: true (skip them)
	 * @return array{valid_users:array, skipped_user:array, uploads_onl:array }
	 */
	public static function get_user_statistics( string $sip_folder, bool $skip_test_user = true ) : array {
		return self::_get_user_statistics( $sip_folder, $skip_test_user );
	}

	/**
	 * Calculate the number of files inside the directories.
	 * @param string $base_dir Starting directory to look for uploads.
	 * @param string $sub_dir [Optional] Subdirectory inside the $base_dir.
	 * @return array
	 */
	private static function _get_all_uploaded_files( string $base_dir, string $sub_dir = 'content' ) : array {
		$stats     = array();
		$user_dirs = glob($base_dir . '/*', GLOB_ONLYDIR);

		if ( empty( $user_dirs ) ) { return array(); }

		foreach ($user_dirs as $user_dir) {
			$file_size  = 0;
			$user_id    = basename($user_dir);

			// these are the folders for the single submission.
			$element_dirs = glob($user_dir . '/*', GLOB_ONLYDIR);

			foreach ($element_dirs as $element_dir) {
				$element_id  = basename($element_dir);
				// if specified, we only want to count the files inside of a specific folder (like content).
				$content_dir = $element_dir . '/' . ltrim($sub_dir, '/');

				if (! is_dir($content_dir)) {
					continue;
				}

				$files = glob($content_dir . '/*');
				if (! $files) {
					continue;
				}

				$file_count = count($files);
				foreach ($files as $single_file) {
					$file_size += filesize($single_file);
				}

				if ( ! isset($stats[$user_id])) {
					$stats[$user_id] = [];
				}

				$stats[$user_id][$element_id] = $file_count;
			}

			$stats[$user_id]['filesize']  = $file_size;
		}

		return $stats;
	}

	/**
	 * Create an array that contains user roles along with the number of users assigned to each role.
	 * @return array
	 */
	private static function get_user_role_stats() : array {
		$roles_count = array();
		$users       = get_users();
		foreach ($users as $user) {
			foreach ($user->roles as $role) {
				if ( ! isset($roles_count[$role])) {
					$roles_count[$role] = 0;
				}
				$roles_count[$role]++;
			}
		}
		return $roles_count;
	}

}
