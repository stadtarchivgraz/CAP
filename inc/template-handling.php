<?php
if (! defined('WPINC')) { die; }

class Starg_Template_Handling {

	public static function init() {
		add_filter( 'page_template', array( 'Starg_Template_Handling', 'starg_load_page_template' ) );
		add_filter( 'theme_page_templates', array( 'Starg_Template_Handling', 'starg_add_page_templates' ), 10, 1 );
		add_action( 'init', array( 'Starg_Template_Handling', 'starg_tab_rewrites' ) );

		add_filter( 'query_vars', array( 'Starg_Template_Handling', 'starg_add_query_vars' ) );

		add_action( 'init', array( 'Starg_Template_Handling', 'starg_check_for_polylang_pages' ) );
	}

	/**
	 * Load the Page-Template from the plugin.
	 */
	public static function starg_load_page_template( $page_template ) {
		if ( get_page_template_slug() == 'sip-archival.php' ) {
			$page_template = STARG_SIP_PLUGIN_BASE_DIR . 'page-templates/sip-archival.php';
		}
		if ( get_page_template_slug() == 'sip-archive.php' ) {
			$page_template = STARG_SIP_PLUGIN_BASE_DIR . 'page-templates/sip-archive.php';
		}
		if ( get_page_template_slug() == 'sip-profile.php' ) {
			$page_template = STARG_SIP_PLUGIN_BASE_DIR . 'page-templates/sip-profile.php';
		}
		if ( get_page_template_slug() == 'sip-upload.php' ) {
			$page_template = STARG_SIP_PLUGIN_BASE_DIR . 'page-templates/sip-upload.php';
		}
		return $page_template;
	}

	/**
	 * Includes options to the Page-Template select in the backend.
	 */
	public static function starg_add_page_templates( $page_templates ) {
		$page_templates[ 'sip-archival.php' ] = esc_attr__( 'SIP Archival', 'sip' );
		$page_templates[ 'sip-archive.php' ]  = esc_attr__( 'SIP Archive', 'sip' );
		$page_templates[ 'sip-profile.php' ]  = esc_attr__( 'SIP Profile', 'sip' );
		$page_templates[ 'sip-upload.php' ]   = esc_attr__( 'SIP Upload', 'sip' );
		return $page_templates;
	}

	/**
	 * Adds rewrites for pages to use the word "tab" as sign for tabbed content.
	 * Tabbing is triggered with javascript.
	 */
	public static function starg_tab_rewrites() {
		add_rewrite_tag('%tab%', '([^&]+)');
		add_rewrite_rule('([^/]*)/tab/([^/]*)/?','index.php?pagename=$matches[1]&tab=$matches[2]','top');
		add_rewrite_rule('([^/]*)/([^/]*)/tab/([^/]*)/?','index.php?pagename=$matches[2]&tab=$matches[3]','top');
	}

	/**
	 * Register a query var for the archival name/id in WordPress to be able to redirect the user to their own archival record entry.
	 * @param string[] $vars The array of allowed query variable names.
	 * @return string[]
	 */
	public static function starg_add_query_vars( $vars ) {
		$vars[] = 'archival_name';
		return $vars;
	}

	/**
	 * Maybe create rewrite rules for the archival page-templates where the user can view their entry.
	 * This function is tied to the Polylang plugin.
	 */
	public static function starg_check_for_polylang_pages() {
		// check if the polylang plugin is installed and active.
		if ( ! function_exists('pll_get_post') || ! function_exists('pll_languages_list')) { return; }

		$languages = wp_list_pluck( pll_languages_list( array( 'fields' => array(), ) ), 'slug' ); // get all languages as slugs.
		$default_language = pll_default_language('slug');
		$pages = get_pages( array(
			'meta_key'     => '_wp_page_template',
			'meta_value'   => 'sip-archival.php',
			'hierarchical' => 0,
		) );
		if ( ! $pages ) { return; }

		// Rewrite rule for every used language with translated page slug.
		foreach ($languages as $lang) {
			$translated_page_slug = get_page_uri(pll_get_post($pages[0]->ID, $lang)); // Translated Slug
			if ( ! $translated_page_slug) { continue; }

			add_rewrite_rule(
				"^$lang/$translated_page_slug/?$", // Translated Slug with language.
				"index.php?pagename=$translated_page_slug&lang=$lang",
				'top'
			);
			add_rewrite_rule(
				"^$lang/$translated_page_slug/([^/]*)/?$", // Translated Slug with language.
				"index.php?pagename=$translated_page_slug&archival_name=\$matches[1]&lang=$lang",
				'top'
			);
			if ($lang == $default_language) {
				add_rewrite_rule(
					"^$translated_page_slug/?$", // only translated Slug.
					"index.php?pagename=$translated_page_slug&lang=$lang",
					'top'
				);
				add_rewrite_rule(
					"^$translated_page_slug/([^/]*)/?$", // only translated Slug.
					"index.php?pagename=$translated_page_slug&archival_name=\$matches[1]&lang=$lang",
					'top'
				);
			}
		}
	}

}
