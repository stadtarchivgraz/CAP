<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Export_Statistics extends Form_Validation {
	protected string $request_method = 'get';
	public string $url_endpoint      = 'export_statistics';
	public string $nonce_action      = 'starg_export_statistics_nonce_action';
	public string $nonce_key         = 'starg_export_statistics_nonce';
	public string $form_name         = 'export_statistics_form';

	/**
	 * Perform main validation for the form in question.
	 * We do not accept any user-input if one of these checks fails!
	 * @return bool true on success, false on failure.
	 */
	protected function form_validation() : bool {
		if ( ! defined( 'WPINC' ) ) { return false; } // WordPress must be running to continue!
		if ( ! current_user_can('publish_archival_records') ) { return false; } // A valid User must be logged in to continue!
		if ( ! isset( $_REQUEST[ $this->url_endpoint ] ) ) { return false; } // A valid URL-Endpoint must be defined.

		// if we use the form in a loop, we use a suffix for the nonce to differentiate between the elements in the loop.
		$form_suffix = isset( $_REQUEST[ 'starg_form_suffix' ] ) ? '_' . sanitize_text_field( $_REQUEST[ 'starg_form_suffix' ] ) : '';
		if ( ! isset( $_REQUEST[ $this->form_name_key ] ) || $this->form_name . $form_suffix !== $_REQUEST[ $this->form_name_key ] ) { return false; } // The data must be from the expected form to continue!
		if ( ! isset( $_REQUEST[ $this->nonce_key . $form_suffix ] ) ) { return false; } // There must be a nonce-input to continue!
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST[ $this->nonce_key . $form_suffix ] ), $this->nonce_action ) ) { return false; } // The nonce must be valid to continue!
	
		return true; // all good, form is valid!
	}

	/**
	 * Creates all the needed files for a Submission Information Package ready to be ingested in an OAIS like archive.
	 * This also triggers the download of the created ZIP file with the content.
	 * @return false|void
	 */
	public function maybe_export_statistics() {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		// todo: not really needed here as we only have one button! maybe remove?
		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) {
			$this->set_error_message( esc_attr__( 'User-Input not valid.', 'sip' ) );
			$this->set_error_log_message( esc_attr__( 'User-Input not valid.', 'sip' ) );
			return false;
		}

		$sip_folder         = starg_get_archival_upload_path();
		$all_uploaded_files = Starg_Admin_Pages::get_user_statistics( $sip_folder );
		if ( empty( $all_uploaded_files ) ) {
			$this->set_error_message( esc_html__( 'No data found to export.', 'sip' ) );
			return false;
		}

		$creation_date = date( 'd_m_Y' );
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cap_statistics_export_' . $creation_date . '.csv');

		$output = fopen('php://output', 'w');

		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

		// START main statistics //

		// create headings for the csv.
		$csv_headings = array(
			esc_html__( 'Name', 'sip' ),
			esc_html__( 'Username', 'sip' ),
			esc_html__( 'Archive', 'sip' ),
			esc_html__( 'Submissions', 'sip' ),
			esc_html__( 'Files submitted', 'sip' ),
			esc_html__( 'Extra*', 'sip' ),
		);
		fputcsv($output, $csv_headings);

		foreach ( $all_uploaded_files[ 'valid_users' ]['data'] as $single_user ) {
			fputcsv($output, array_map( 'esc_html', $single_user ) );
		}
		$calculated_stats = array(
			esc_html__( 'Total User', 'sip' ),
			$all_uploaded_files[ 'valid_users' ]['number_users'],
			esc_html__( 'Number of submissions', 'sip' ),
			$all_uploaded_files[ 'valid_users' ]['number_submissions'],
			esc_html__( 'Number of submitted files', 'sip' ),
			$all_uploaded_files[ 'valid_users' ]['number_submitted_files'],
		);
		fputcsv($output, array_map( 'esc_html', $calculated_stats ) );

		// END main statistics //


		// START archive related statistics //

		fputcsv( $output, array( esc_html__( 'Archive', 'sip' ), esc_html__( 'Users', 'sip' ), esc_html__( 'Submitted', 'sip' ), esc_html__( 'Files', 'sip' ), ) );
		foreach ($all_uploaded_files['valid_users']['statistics_by_archive']['data'] as $archive => $values) {
			fputcsv($output, array(
				$archive,
				esc_html( $values['user_by_archive']) ?? 0,
				esc_html( $values['submitted_by_archive']) ?? 0,
				esc_html( $values['files_by_archive']) ?? 0,
			));
		}

		// todo: maybe add the table footer for calculated numbers?
		// fputcsv($output, array(
		// 	esc_html( $all_uploaded_files['valid_users']['statistics_by_archive']['number_users'] ),
		// 	esc_html( $all_uploaded_files['valid_users']['statistics_by_archive']['number_submissions'] ),
		// 	esc_html( $all_uploaded_files['valid_users']['statistics_by_archive']['number_submitted_files'] ),
		// ) );

		// END archive related statistics //


		// START statistics for admin/test user //

		fputcsv($output, array( esc_html__( 'Data from administrators or test users', 'sip' ) ) );
		foreach ( $all_uploaded_files[ 'skipped_users' ]['data'] as $single_skipped_user ) {
			fputcsv($output, array_map( 'esc_html', $single_skipped_user ) );
		}

		$calculated_stats_skipped = array(
			esc_html__( 'Total User', 'sip' ),
			$all_uploaded_files[ 'skipped_users' ]['number_users'],
			esc_html__( 'Number of submissions', 'sip' ),
			$all_uploaded_files[ 'skipped_users' ]['number_submissions'],
			esc_html__( 'Number of submitted files', 'sip' ),
			$all_uploaded_files[ 'skipped_users' ]['number_submitted_files'],
		);
		fputcsv($output, array_map( 'esc_html', $calculated_stats_skipped ) );

		fputcsv( $output, array( esc_html__( 'Archive', 'sip' ), esc_html__( 'Users', 'sip' ), esc_html__( 'Submitted', 'sip' ), esc_html__( 'Files', 'sip' ), ) );
		foreach ($all_uploaded_files['skipped_users']['statistics_by_archive']['data'] as $archive => $values) {
			fputcsv($output, array(
				$archive,
				esc_html( $values['user_by_archive'] ) ?? 0,
				esc_html( $values['submitted_by_archive'] ) ?? 0,
				esc_html( $values['files_by_archive'] ) ?? 0,
			));
		}

		// END statistics for admin/test user //

		$export_created = fclose($output);
		if ( ! $export_created ) {
			$this->set_error_message( esc_html__( 'Problems creating statistics export.', 'sip' ) );
			$this->set_error_log_message( esc_html__( 'Problems creating statistics export.', 'sip' ) );
			exit;
		}

		$this->set_success_message( esc_html__( 'Data successfully exported.', 'sip' ) );
		exit;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'export_csv'  => 'sanitize_text_field',
		);
	}

	protected function get_required_input_names() : array {
		return array();
	}
}
