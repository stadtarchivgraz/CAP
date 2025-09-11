<?php
if (! defined('WPINC')) { die; }

use Spipu\Html2Pdf\Html2Pdf;

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/form-validation.class.php' );
class Create_Sip_Pdf extends Form_Validation {
	protected string $request_method = 'get';
	public string $url_endpoint      = 'create-sip-pdf';

	/**
	 * Perform main validation for the form in question.
	 * We do not accept any user-input if one of these checks fails!
	 * @return bool true on success, false on failure.
	 */
	protected function form_validation() : bool {
		if ( ! defined( 'WPINC' ) ) { return false; } // WordPress must be running to continue!
		if ( ! current_user_can('edit_others_posts') ) { return false; } // A valid User must be logged in to continue!
		if ( ! isset( $_REQUEST[ $this->url_endpoint ] ) ) { return false; } // A valid URL-Endpoint must be defined.

		return true; // all good, form is valid!
	}

	/**
	 * Triggers the creation of a PDF based on the information of an archival post.
	 * @return false|void
	 */
	public function create_sip_pdf() {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		$user_input = $this->user_input_sanitization();
		if ( ! $user_input ) {
			$this->set_error_message( esc_attr__( 'User-Input not valid.', 'sip' ) );
			$this->set_error_log_message( esc_attr__( 'User-Input not valid.', 'sip' ) );
			return false;
		}

		$sip_user_folder_id = $user_input[ 'sipFolder' ];
		if ( ! $sip_user_folder_id ) {
			$this->set_error_message( esc_attr__( 'No SIP Folder provided.', 'sip' ) );
			$this->set_error_log_message( esc_attr__( 'No SIP Folder provided.', 'sip' ) );
			return false;
		}

		$this->create_pdf( $user_input['sipFolder'] );
		exit;
	}

	/**
	 * Starts the creation process of the PDF file for a specific archival record.
	 * @return false|void
	 */
	private function create_pdf( string $sip_folder ) {
		$current_locale   = strtolower(get_locale());
		$date_time_format = get_option('date_format') . ' ' . get_option('time_format');
		$date_format      = get_option('date_format');
		$archival_id      = starg_get_archival_id_by_sip_folder( $sip_folder );
		if ( ! $archival_id ) {
			// translators: %d: Post-ID of an archival record.
			$this->set_error_message( sprintf( esc_attr__( 'No archival record found with SIP Post-ID: "%d"', 'sip' ), $archival_id ) );
			$this->set_error_log_message( sprintf( esc_attr__( 'No archival record found with SIP Post-ID: "%d"', 'sip' ), $archival_id ) );
			return false;
		}

		$sip_institution_logo_html    = '';
		$sip_institution_logo_abspath = '';
		$sip_institution_logo         = '';
		$archive = get_the_terms($archival_id, 'archive');
		if ( $archive && ! is_wp_error( $archive ) ) {
			$sip_institution_logo = strtoupper(carbon_get_term_meta($archive[0]->term_id, 'sip_institution_logo'));
			if ( $sip_institution_logo ) {
				// $sip_institution_logo_html    = wp_get_attachment_image( $sip_institution_logo, 'medium', false, array( 'height' => 50, ) );
				// we use the absolute path to the file instead of the url to minimize problems with html2pdf.
				$sip_institution_logo_abspath = get_attached_file( $sip_institution_logo );
				$sip_institution_logo         = '<img style="max-width:100%;height:50px;" src="' . $sip_institution_logo_abspath . '" height="50" width="auto"/>';
			}
		}

		$archival          = get_post($archival_id);
		$archival_user     = get_user_by('id', $archival->post_author);
		$archivist_user_id = (get_post_meta($archival_id, '_archival_archivar_user_id', true)) ?: get_current_user_id();
		$archivist         = get_user_by('id',  $archivist_user_id);
		$sip_folder        = starg_get_archival_upload_path() . $archival->post_author . '/' . $sip_folder . '/';

		$archival_address    = (get_post_meta($archival_id, '_archival_address', true)) ?: esc_html__('unknown', 'sip');
		$archival_originator = (get_post_meta($archival_id, '_archival_originator', true)) ?: esc_html__('unknown', 'sip');
		$archival_date_time  = get_post_meta($archival_id, '_archival_from', true);
		if ($archival_to = get_post_meta($archival_id, '_archival_to', true)) {
			$archival_date_time .= ' &mdash; ' . $archival_to;
		}
		$archival_upload_purpose    = get_post_meta($archival_id, '_archival_upload_purpose', true);
		$archival_blocking_time_row = '';
		if ($archival_blocking_time = get_post_meta($archival_id, '_archival_blocking_time', true)) {
			$archival_blocking_time_row = '<tr><th>' . esc_html__('Blocking Time', 'sip') . '</th><td>' . esc_html( $archival_blocking_time ) . '</td></tr>';
		}
		$archival_custom_meta_row = '';
		if ($sip_custom_meta = carbon_get_theme_option('sip_custom_meta')) {
			foreach ($sip_custom_meta as $custom_meta) {
				$meta_name = sanitize_title($custom_meta['sip_custom_meta_key']);
				if ($meta_value = get_post_meta($archival_id, '_archival_' . $meta_name, true)) {
					$archival_custom_meta_row .= '<tr><th>' . esc_html( $custom_meta['sip_custom_meta_title_' . $current_locale] ) . '</th><td>' . esc_html( $meta_value ) . '</td></tr>';
				}
			}
		}

		$archival_user_custom_meta_row = '';
		if ($sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta')) {
			foreach ($sip_custom_archival_user_meta as $custom_archival_user_meta) {
				$meta_name = sanitize_title($custom_archival_user_meta['sip_custom_archival_user_meta_key']);
				if ($meta_value = get_post_meta($archival_id, '_archival_' . $meta_name, true)) {
					$archival_user_custom_meta_row .= '<tr><th>' . $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale] . '</th><td>' . $meta_value . '</td></tr>';
				}
			}
		}

		ob_start();
		$pdf = true;
		include(STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-sip-folder.php');
		$content_sip_folder = ob_get_clean();

		$htmlContent = Create_Sip_Pdf::build_pdf_html(
			$sip_institution_logo,
			$archive,
			$archival,
			$date_time_format,
			$content_sip_folder,
			$archival_id,
			$archival_address,
			$archival_originator,
			$archival_date_time,
			$archival_upload_purpose,
			$archival_blocking_time_row,
			$archival_custom_meta_row,
			$archival_user_custom_meta_row,
			$archival_user,
			$archivist,
			$date_format
		);

		try {
			$html2pdf = new Html2Pdf('P', 'A4', substr($current_locale,0,2));
			$html2pdf->writeHTML( $htmlContent );
			$html2pdf->output();
			exit;
		} catch ( Exception $exception ) {
			$this->set_error_log_message( $exception->getMessage() );
			return false;
		}

	}

	private static function render_header($sip_institution_logo, $archive) {
		return '<page_header>
				<table border="0" cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td style="width: 50%; text-align: left;">' . wp_kses_post( $sip_institution_logo ) . '</td>
						<td style="width: 50%; text-align: right;">
							<strong>' . esc_html( $archive[0]->name ) . '</strong><br>
							' . wpautop( sanitize_textarea_field( $archive[0]->description ) ) . '
						</td>
					</tr>
				</table>
			</page_header>';
	}

	private static function render_footer($date_time_format) {
		return '<page_footer>
				<table border="0" cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td style="width: 33%; text-align: left;">' . esc_html( get_bloginfo('name') ) . '</td>
						<td style="width: 34%; text-align: center">[[page_cu]]/[[page_nb]]</td>
						<td style="width: 33%; text-align: right">' . date_i18n( esc_html( $date_time_format ) ) . '</td>
					</tr>
				</table>
			</page_footer>';
	}

	private static function render_title_block($archival, $date_time_format) {
		return '<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td style="width: 75%; text-align: left;">
						<h2>' . esc_html( $archival->post_title ) . '</h2>
					</td>
					<td style="width: 25%; text-align: right;">
						' . date_i18n( esc_attr( $date_time_format ), strtotime( esc_attr( $archival->post_date ) ) ) . '
					</td>
				</tr>
			</table>';
	}

	private static function render_archival_meta($archival_id, $archival_address, $archival_originator, $archival_date_time, $archival_upload_purpose, $archival_blocking_time_row, $archival_custom_meta_row) {
		return '<table border="0" cellpadding="0" cellspacing="5" width="100%">
				<tr>
					<th>' . esc_html__('Location', 'sip') . '</th>
					<td>' . wpautop( sanitize_textarea_field( $archival_address ) ) . '</td>
				</tr>
				<tr>
					<th>' . esc_html__('Originator', 'sip') . '</th>
					<td>' . esc_html( $archival_originator ) . '</td>
				</tr>
				<tr>
					<th>' . esc_html__('Date/Time', 'sip') . '</th>
					<td>' . esc_html( $archival_date_time ) . '</td>
				</tr>
				<tr>
					<th>' . esc_html__('Upload Purpose', 'sip') . '</th>
					<td>' . esc_html( $archival_upload_purpose ) . '</td>
				</tr>
				' . $archival_blocking_time_row . '
				' . $archival_custom_meta_row . '
				<tr>
					' . strip_tags( get_the_term_list( $archival_id, 'archival_tag', '<th>' . esc_html__('Tags', 'sip') . '</th><td>', ' | ', '</td>' ), '<th><td>' ) . '
				</tr>
			</table>';
	}

	private static function render_admin_meta($archival_id, $archival_user_custom_meta_row) {
		if (!current_user_can('edit_others_posts')) {
			return '';
		}

		return '<h4>' . esc_html__('Archive Information', 'sip') . '</h4>
			<table border="0" cellpadding="0" cellspacing="5" width="100%">
				<tr>
					<th>' . esc_html__('Numbering', 'sip') . '</th>
					<td>' . esc_html( get_post_meta($archival_id, '_archival_numeration', true) ) . '</td>
				</tr>
				<tr>
					<th>' . esc_html__('Annotation', 'sip') . '</th>
					<td>' . esc_html( get_post_meta($archival_id, '_archival_annotation', true) ) . '</td>
				</tr>
				' . $archival_user_custom_meta_row . '
			</table>';
	}

	private static function render_user_block($archival_user, $archivist, $date_format) {
		$user_address = get_user_meta($archival_user->ID, 'user_address', true);

		return '<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td style="width: 50%; text-align: left;">
						<h4>' . esc_html__('User', 'sip') . '</h4>
						<p>
							' . esc_html( $archival_user->display_name ) . '<br>
							' . esc_html( $user_address['street_number'] ) . '<br>
							' . esc_html( $user_address['zip'] ) . ' ' . esc_html( $user_address['city'] ) . '
						</p>
						<p>
							' . date_i18n( esc_html( $date_format ), strtotime( esc_html( get_user_meta( $archival_user->ID, 'user_birthday', true ) ) ) ) . '<br>
							' . esc_html( $archival_user->user_email ) . '
						</p>
					</td>
					<td style="width: 50%; text-align: left;">
						<h4>' . esc_html__('Archivist', 'sip') . '</h4>
						<p>' . esc_html( $archivist->display_name ) . '</p>
						<p>' . esc_html( $archivist->user_email ) . '</p>
					</td>
				</tr>
			</table>';
	}

	private static function build_pdf_html($sip_institution_logo, $archive, $archival, $date_time_format, $content_sip_folder, $archival_id, $archival_address, $archival_originator, $archival_date_time, $archival_upload_purpose, $archival_blocking_time_row, $archival_custom_meta_row, $archival_user_custom_meta_row, $archival_user, $archivist, $date_format) {
		$html = '
			<style>
				.sip { width: 100%; }
				.sip div { width: 50%; margin-bottom: 5mm; }
				table { page-break-inside: auto; }
				tr, td { page-break-inside: avoid; }
				img { max-width: 100%; width: auto; height: auto; }
			</style>

			<page backtop="20mm" backbottom="20mm" backleft="10mm" backright="10mm">
				' . Create_Sip_Pdf::render_header($sip_institution_logo, $archive) . '
				' . Create_Sip_Pdf::render_footer($date_time_format) . '
				' . Create_Sip_Pdf::render_title_block($archival, $date_time_format) . '
				' . wp_kses_post( $content_sip_folder ) . '
				' . apply_filters('the_content', $archival->post_content) . '
				' . Create_Sip_Pdf::render_archival_meta($archival_id, $archival_address, $archival_originator, $archival_date_time, $archival_upload_purpose, $archival_blocking_time_row, $archival_custom_meta_row) . '
				' . Create_Sip_Pdf::render_admin_meta($archival_id, $archival_user_custom_meta_row) . '
				' . Create_Sip_Pdf::render_user_block($archival_user, $archivist, $date_format) . '
			</page>';

		return $html;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array
	 */
	protected function get_valid_input_names() : array {
		return array(
			'sipFolder'  => 'sanitize_text_field',
		);
	}
}
