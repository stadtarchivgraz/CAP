<?php
if (! defined('WPINC')) { die; }

class Filter_Archival_Query extends Form_Validation {
	protected string $request_method = 'get';
	public string $nonce_action      = 'faq_nonce_action';
	public string $nonce_key         = 'faq_nonce_key';
	public string $form_name         = 'faq_form';
	private array $user_input        = array();

	function __construct() {
		// make sure the $user_inputs have all the needed array-keys.
		$valid_input_names = $this->get_valid_input_names();
		foreach ( $valid_input_names as $input_name => $sanitization_method ) {
			$this->user_input[ $input_name ] = '';
		}
	}

	/**
	 * Perform main validation for the form in question.
	 * We do not accept any user-input if one of these checks fails!
	 * @return bool true on success, false on failure.
	 */
	protected function form_validation() : bool {
		if ( ! defined( 'WPINC' ) ) { return false; } // WordPress must be running to continue!
		if ( ! current_user_can('edit_others_posts') ) { return false; } // A valid User must be logged in to continue!
		//if ( ! isset( $_REQUEST[ $this->url_endpoint ] ) ) { return false; } // A valid URL-Endpoint must be defined.

		// if ( ! isset( $_REQUEST[ $this->form_name_key ] ) || $this->form_name !== $_REQUEST[ $this->form_name_key ] ) { return false; } // The data must be from the expected form to continue!
		// if ( ! isset( $_REQUEST[ $this->nonce_key ] ) ) { return false; } // There must be a nonce-input to continue!
		// if ( ! wp_verify_nonce( sanitize_key( $_REQUEST[ $this->nonce_key ] ), $this->nonce_action ) ) { return false; } // The nonce must be valid to continue!

		return true; // all good, form is valid!
	}

	/**
	 * Retrieves either the original arguments for the archival records query or enriched query-args with filtered values.
	 * For a list of qualified arguments to filter for @see WP_Query::parse_query()
	 * @return array|false
	 */
	public function maybe_trigger_filter() {
		$is_form_valid = $this->form_validation();
		if ( ! $is_form_valid ) { return false; }

		// the main args for the WP_Query.
		$paged      = get_query_var('paged') ?: 1;
		$tax_query  = array();
		$meta_query = array();
		$args       = array(
			'post_type'   => Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG,
			'post_status' => 'publish',
			'paged'       => $paged,
			// 'lang'        => '',
		);

		// Admins/Editors need to see all posts
		if ( current_user_can('edit_others_posts') ) {
			$args['post_status'] = array( 'pending', 'publish', 'draft', );
		}
		
		if ( ! current_user_can( 'manage_options' ) ) {
			$user_archive = (int) esc_attr( get_user_meta(get_current_user_id(), 'user_archive', true) );
			if ($user_archive) {
				$tax_query[] = array(
					'taxonomy' => Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG,
					'field'    => 'term_id',
					'terms'    => $user_archive,
				);
			}
		}

		// set the main institution for the editor. All editors should only see the uploads which are in their responsibility.
		if ( $tax_query ) {
			$args['tax_query'] = $tax_query;
		}

		// if the filter form was not sent we return the main Query.
		$this->user_input = $this->user_input_sanitization();
		if ( ! $this->user_input ) { return $args; }

		$archive = $this->user_input['filter-archive'];
		$tag     = $this->user_input['filter-tag'];
		$purpose = $this->user_input['filter-purpose'];
		$year    = $this->user_input['filter-year'];
		$search  = $this->user_input['filter-search'];

		if ( $archive ) {
			if ( 'all' === $archive ) {
				$args['tax_query'] = array();
				$tax_query = array();
			} else {
				// allow overriding the main institution tax_query so an editor can also see other uploads from other institutions.
				$tax_query = array(
					array(
						'taxonomy' => Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG,
						'field'    => 'slug',
						'terms'    => $archive,
					),
				);
			}
		}

		if ( $tag ) {
			$tax_query[] = array(
				'taxonomy' => Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG,
				'field'    => 'slug',
				'terms'    => $tag,
			);
		}

		if ( $purpose ) {
			$meta_query[] = array(
				'key'   => '_archival_upload_purpose',
				'value' => $purpose,
			);
		}

		if ( $year ) {
			$meta_query[] = array(
				'key'     => '_archival_from',
				'value'   => $year,
				'type'    => 'DATETIME',
				'compare' => 'LIKE',
			);
		}

		if ( $search ) {
			$args['s'] = $search;
		}

		if ( $tax_query ) {
			if (count($tax_query) > 1) {
				$tax_query['relation'] = 'AND';
			}
			$args['tax_query'] = $tax_query;
		}

		if ( $meta_query) {
			if (count($meta_query) > 1) {
				$meta_query['relation'] = 'AND';
			}
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

	/**
	 * Retrieves an array of input-keys with their values if present.
	 * @return array
	 */
	public function get_user_input(): array {
		return $this->user_input;
	}

	/**
	 * Describes which inputs we want to process in the form and against which sanitizing function we apply to them.
	 * @return array the array should be formed as [ 'input_name' => 'sanitizing_function', ]
	 */
	function get_valid_input_names(): array {
		return array(
			'filter-archive' => 'sanitize_text_field',
			'filter-tag'     => 'sanitize_text_field',
			'filter-purpose' => 'sanitize_text_field',
			'filter-year'    => 'sanitize_text_field',
			'filter-search'  => 'sanitize_text_field',
		);
	}
	
	/**
	 * Describes which inputs of the form are required.
	 * If a form has not delivered one of these inputs, we do not trigger any action but display an error message.
	 * For performance reasons we use the input names as keys for the array. This way we can use isset() instead of in_array().
	 * @return array
	 */
	function get_required_input_names(): array {
		return array();
	}
}
