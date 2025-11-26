<?php

class Shortcode_Helper {
	const STARG_SHORTCODE_VERSION = '1.0.0';

	public static function init() {
		add_shortcode('starg_email',        array('Shortcode_Helper', 'add_email_shortcode'), 10, 3);

		add_action('admin_head',            array('Shortcode_Helper', 'maybe_add_custom_shortcode_button'));
		add_action('admin_enqueue_scripts', array('Shortcode_Helper', 'enqueue_starg_shortcode_script' ));

		if ( class_exists( '\Elementor\Widget_Base' ) ) {
			require_once( STARG_SIP_PLUGIN_BASE_DIR . 'elementor-integration/Email_Shortcode_Widget.class.php' );
			add_action('elementor/widgets/register', function($widgets_manager) {
				$widgets_manager->register(new \Starg_Email_Elementor_Widget());
			});
		}
	}

	/*************************/
	/* the single shortcodes */
	/*************************/

	/**
	 * Converts an email address to a series of HTML entities to make it harder for spam bots to recognize it.
	 * @param array{class: string} $atts additional attributes for the created link. [starg_email class="button"]some-account@some-url.tld[/starg_email]
	 * @param string $content the actual email address encapsulated between the shortcode tags. [starg_email]some-account@some-url.tld[/starg_email]
	 * @param string $shortcode_tag the tag of the shortcode. "starg_email"
	 * @return string
	 */
	public static function add_email_shortcode(array $atts, string $content, string $shortcode_tag): string {
		if (! $content || ! is_email($content)) {
			return sanitize_text_field($content);
		}

		$attributes = shortcode_atts(array(
			'class' => '',
		), $atts);

		$class = ($attributes['class']) ? 'class="' . esc_attr($attributes['class']) . '"' : '';
		$email = antispambot(sanitize_text_field($content));
		return '<a href="mailto:' . $email . '"' . $class . '>' . $email . '</a>';
	}

	/*******************/
	/* mce integration */
	/*******************/

	public static function maybe_add_custom_shortcode_button() {
		if (! current_user_can('edit_posts') && ! current_user_can('edit_pages')) { return; }

		if (get_user_option('rich_editing') != 'true') { return; }

		add_filter( 'mce_external_plugins', array( 'Shortcode_Helper', 'add_shortcode_buttons_to_mce' ) );
		add_filter( 'mce_buttons',          array( 'Shortcode_Helper', 'register_email_shortcode_buttons' ) );
	}

	/**
	 * Adds the script to the list of mce plugins.
	 */
	public static function add_shortcode_buttons_to_mce($plugin_array) {
		if ( ! file_exists( STARG_SIP_PLUGIN_BASE_DIR . '/js/shortcode-buttons.js' ) ) { return $plugin_array; }

		$plugin_array['starg_email_shortcode_buttons'] = STARG_SIP_PLUGIN_BASE_URL . '/js/shortcode-buttons.js';
		return $plugin_array;
	}

	/**
	 * Add the button to the mce toolbar.
	 */
	public static function register_email_shortcode_buttons($buttons) {
		array_push($buttons, 'starg_email_shortcode_buttons');
		return $buttons;
	}

	public static function enqueue_starg_shortcode_script() {
		wp_register_script( 'starg_email_shortcode_buttons', STARG_SIP_PLUGIN_BASE_URL . '/js/shortcode-buttons.js', array( 'jquery', ), Shortcode_Helper::STARG_SHORTCODE_VERSION, true );
	}

	/****************************/
	/*           TODO           */
	/* block-editor integration */
	/****************************/

}
