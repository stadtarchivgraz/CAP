<?php
if (! defined('WPINC')) { die; }

class Archival_Custom_Posts {

	// todo: change all static uses of the cpt and taxonomies to use the constants!
	public const ARCHIVAL_POST_TYPE_SLUG      = 'archival';
	public const ARCHIVE_CUSTOM_TAX_SLUG      = 'archive'; // the archival institution.
	public const ARCHIVAL_TAG_CUSTOM_TAX_SLUG = 'archival_tag';

	public static function init() {
		add_action( 'init',                                array( 'Archival_Custom_Posts', 'starg_custom_post_type_archival' ) );
		add_action( 'init',                                array( 'Archival_Custom_Posts', 'starg_create_custom_taxonomies' ) );
		add_action( 'restrict_manage_posts',               array( 'Archival_Custom_Posts', 'starg_filter_archival_by_taxonomy' ) );
		add_action( 'pre_get_posts',                       array( 'Archival_Custom_Posts', 'starg_admin_filter_archival' ), 99, 1 );
		add_action( 'manage_archival_posts_custom_column', array( 'Archival_Custom_Posts', 'starg_archival_table_content' ), 10, 2 );
		add_filter( 'manage_archival_posts_columns',       array( 'Archival_Custom_Posts', 'starg_archival_table_head' ) );
		add_filter( 'wp_editor_settings',                  array( 'Archival_Custom_Posts', 'starg_archival_remove_media_buttons' ), 10, 2 );
	}

	/**
	 * Register the "archival" custom post type.
	 * @return void
	 */
	public static function starg_custom_post_type_archival() : void {
		////////////////////////////////////
		// post-type for archival records //
		////////////////////////////////////
		$post_type_labels = array(
			'name'                  => esc_html_x( 'Archival materials', 'Post Type General Name', 'sip' ),
			'singular_name'         => esc_html_x( 'Archival', 'Post Type Singular Name', 'sip' ),
			'menu_name'             => esc_html__( 'Archival Materials', 'sip' ),
			'name_admin_bar'        => esc_html__( 'Archival', 'sip' ),
			'archives'              => esc_html__( 'Item Archives', 'sip' ),
			'attributes'            => esc_html__( 'Item Attributes', 'sip' ),
			'parent_item_colon'     => esc_html__( 'Parent Item:', 'sip' ),
			'all_items'             => esc_html__( 'All Items', 'sip' ),
			'add_new_item'          => esc_html__( 'Add New Item', 'sip' ),
			'add_new'               => esc_html__( 'Add New', 'sip' ),
			'new_item'              => esc_html__( 'New Item', 'sip' ),
			'edit_item'             => esc_html__( 'Edit Item', 'sip' ),
			'update_item'           => esc_html__( 'Update Item', 'sip' ),
			'view_item'             => esc_html__( 'View Item', 'sip' ),
			'view_items'            => esc_html__( 'View Items', 'sip' ),
			'search_items'          => esc_html__( 'Search Item', 'sip' ),
			'not_found'             => esc_html__( 'Not found', 'sip' ),
			'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'sip' ),
			'featured_image'        => esc_html__( 'Featured Image', 'sip' ),
			'set_featured_image'    => esc_html__( 'Set featured image', 'sip' ),
			'remove_featured_image' => esc_html__( 'Remove featured image', 'sip' ),
			'use_featured_image'    => esc_html__( 'Use as featured image', 'sip' ),
			'insert_into_item'      => esc_html__( 'Insert into item', 'sip' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this item', 'sip' ),
			'items_list'            => esc_html__( 'Items list', 'sip' ),
			'items_list_navigation' => esc_html__( 'Items list navigation', 'sip' ),
			'filter_items_list'     => esc_html__( 'Filter items list', 'sip' ),
		);
		$post_type_rewrite = array(
			'slug'                  => esc_attr__( 'archival', 'sip' ),
			'with_front'            => true,
			'pages'                 => true,
			'feeds'                 => true,
		);
		$post_type_args = array(
			'label'                 => esc_html__( 'Archival', 'sip' ),
			'description'           => esc_html__( 'Post Type for archival materials in the SIP Plugin', 'sip' ),
			'labels'                => $post_type_labels,
			'supports'              => array( 'title','editor','author', ),
			'taxonomies'            => array( Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG, Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG, ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-archive',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			// 'exclude_from_search'   => false,
			// 'publicly_queryable'    => true,
			//'rewrite'               => $post_type_rewrite,

			// changes from live server!
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'rewrite'               => false, //$rewrite,

			// to change the minimum user role from author (which is not good) to subscriber, we create own capabilities for our cpt.
			// NOTE that we need to add the post_id in functions like "current_user_can( 'edit_archival', get_the_ID() )" to be able to check these capabilities!
			'map_meta_cap'          => true,
			'capability_type'       => array( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG, 'archival_records', ),
			'capabilities'          => array(
				'edit_post'              => 'edit_archival',
				'read_post'              => 'read_archival',
				'delete_post'            => 'delete_archival',
				'edit_posts'             => 'edit_archival_records',
				'edit_others_posts'      => 'edit_others_archival_records',
				'delete_posts'           => 'delete_archival_records',
				'publish_posts'          => 'publish_archival_records',
				'read_private_posts'     => 'read_private_archival_records',
				'delete_private_posts'   => 'delete_private_archival_records',
				'delete_published_posts' => 'delete_published_archival_records',
				'delete_others_posts'    => 'delete_others_archival_records',
				'edit_private_posts'     => 'edit_private_archival_records',
				'edit_published_posts'   => 'edit_published_archival_records',
			),
		);

		register_post_type( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG, $post_type_args );
	}

	/**
	 * Register the custom taxonomies "archive" and "archival_tag" for our custom post type "archival"
	 */
	public static function starg_create_custom_taxonomies() : void {
		// we set custom capabilities for both our custom taxonomies.
		$archive_tax_capabilities = array(
			'manage_terms' => 'manage_archival_terms',
			'edit_terms'   => 'edit_archival_terms',
			'delete_terms' => 'delete_archival_terms',
			'assign_terms' => 'edit_archival_terms',
		);

		//////////////////////////////////////////////
		// custom-taxonomy for archival institution //
		//////////////////////////////////////////////
		$archive_tax_labels = array(
			'name'                       => esc_html_x( 'Archives', 'Taxonomy General Name', 'sip' ),
			'singular_name'              => esc_html_x( 'Archive', 'Taxonomy Singular Name', 'sip' ),
			'menu_name'                  => esc_html__( 'Archive', 'sip' ),
			'all_items'                  => esc_html__( 'All Archives', 'sip' ),
			'parent_item'                => esc_html__( 'Parent Item', 'sip' ),
			'parent_item_colon'          => esc_html__( 'Parent Item:', 'sip' ),
			'new_item_name'              => esc_html__( 'New Item Name', 'sip' ),
			'add_new_item'               => esc_html__( 'Add New Item', 'sip' ),
			'edit_item'                  => esc_html__( 'Edit Item', 'sip' ),
			'update_item'                => esc_html__( 'Update Item', 'sip' ),
			'view_item'                  => esc_html__( 'View Item', 'sip' ),
			'separate_items_with_commas' => esc_html__( 'Separate items with commas', 'sip' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove items', 'sip' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used', 'sip' ),
			'popular_items'              => esc_html__( 'Popular Items', 'sip' ),
			'search_items'               => esc_html__( 'Search Items', 'sip' ),
			'not_found'                  => esc_html__( 'Not Found', 'sip' ),
			'no_terms'                   => esc_html__( 'No items', 'sip' ),
			'items_list'                 => esc_html__( 'Items list', 'sip' ),
			'items_list_navigation'      => esc_html__( 'Items list navigation', 'sip' ),
		);
		$archive_tax_rewrite = array(
			'slug'         => esc_attr__('archive', 'sip'), // todo: maybe it's better to not translate slugs.
			'with_front'   => true,
			'hierarchical' => false,
		);
		$archive_tags_args = array(
			'labels'                     => $archive_tax_labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => false,
			'rewrite'                    => $archive_tax_rewrite,
			'capabilities'               => $archive_tax_capabilities,
		);
		register_taxonomy( Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG, array( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG, ), $archive_tags_args );

		///////////////////////////////////////
		// custom-taxonomy for archival tags //
		///////////////////////////////////////
		$archival_tax_labels = array(
			'name'                       => esc_html_x( 'Archival Tags', 'Taxonomy General Name', 'sip' ),
			'singular_name'              => esc_html_x( 'Archival Tag', 'Taxonomy Singular Name', 'sip' ),
			'menu_name'                  => esc_html__( 'Archival Tag', 'sip' ),
			'all_items'                  => esc_html__( 'All Items', 'sip' ),
			'parent_item'                => esc_html__( 'Parent Item', 'sip' ),
			'parent_item_colon'          => esc_html__( 'Parent Item:', 'sip' ),
			'new_item_name'              => esc_html__( 'New Item Name', 'sip' ),
			'add_new_item'               => esc_html__( 'Add New Item', 'sip' ),
			'edit_item'                  => esc_html__( 'Edit Item', 'sip' ),
			'update_item'                => esc_html__( 'Update Item', 'sip' ),
			'view_item'                  => esc_html__( 'View Item', 'sip' ),
			'separate_items_with_commas' => esc_html__( 'Separate items with commas', 'sip' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove items', 'sip' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used', 'sip' ),
			'popular_items'              => esc_html__( 'Popular Items', 'sip' ),
			'search_items'               => esc_html__( 'Search Items', 'sip' ),
			'not_found'                  => esc_html__( 'Not Found', 'sip' ),
			'no_terms'                   => esc_html__( 'No items', 'sip' ),
			'items_list'                 => esc_html__( 'Items list', 'sip' ),
			'items_list_navigation'      => esc_html__( 'Items list navigation', 'sip' ),
		);
		$archival_tags_args = array(
			'labels'                     => $archival_tax_labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => false,
			'show_tagcloud'              => false,
			'rewrite'                    => false,
			'capabilities'               => $archive_tax_capabilities,
		);
		register_taxonomy( Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG, array( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG, ), $archival_tags_args );

	}

	/**
	 * Adds additional Filters for the archival post-type in the backend.
	 * @link https://developer.wordpress.org/reference/hooks/restrict_manage_posts/
	 * @return void
	 */
	public static function starg_filter_archival_by_taxonomy() : void {
		$current_locale = strtolower( get_locale() );
		global $typenow;

		if ( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG !== $typenow ) { return; }

		// displays a select in the backend to filter between taxonomies for the archival post-type.
		if ( current_user_can( 'manage_options' ) ) {
			$taxonomies = array( Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG, );
			foreach ( $taxonomies as $taxonomy ) {
				$selected      = ( isset( $_GET[ $taxonomy ] ) ) ? sanitize_key( $_GET[ $taxonomy ] ) : '';
				$info_taxonomy = get_taxonomy( $taxonomy );
				wp_dropdown_categories( array(
					// translators: %s: Label of the taxonomy.
					'show_option_all' => sprintf( esc_html__( 'Show all %s', 'sip' ), $info_taxonomy->label ),
					'taxonomy'        => $taxonomy,
					'name'            => $taxonomy,
					'orderby'         => 'name',
					'selected'        => $selected,
					'show_count'      => true,
					'hide_empty'      => false,
					'value_field'     => 'slug',
					'hierarchical'    => true,
				) );
			}
		}

		// create a select to filter for upload-purposes.
		$upload_purpose_options = array();
		if ( carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale ) ) {
			$upload_purpose_options = carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale );
			$upload_purpose_options = explode( "\r\n", $upload_purpose_options );
		}

		$options = array_combine( $upload_purpose_options, $upload_purpose_options );
		$upload_purpose = ( isset( $_GET[ 'upload_purpose' ] ) ) ? sanitize_text_field( $_GET[ 'upload_purpose' ] ) : '';
		?>
		<select name="upload_purpose">
			<option value="0"><?php esc_attr_e( 'Show all', 'sip' ); ?></option>
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>"<?php echo ( $value === $upload_purpose ) ? ' selected' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php
	}

	/**
	 * Filters the archival posts in the backend based on the upload purposes.
	 * @param WP_Query $query
	 * @return void
	 */
	public static function starg_admin_filter_archival( WP_Query $query ) : void {
		if ( ! is_admin() ) { return; }

		global $typenow;
		if ( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG !== $typenow ) { return; }

		$upload_purpose = ( isset( $_GET[ 'upload_purpose' ] ) ) ? sanitize_text_field( $_GET[ 'upload_purpose' ] ) : '';
		if ( $upload_purpose ) {
			$current_meta = $query->get('meta_query');
			if ( ! is_array( $current_meta ) ) {
				$current_meta = array();
			}
			$upload_purpose_meta = array(
				array(
					'key'     => '_archival_upload_purpose',
					'value'   => $upload_purpose,
				)
			);
			$meta_query = $current_meta[] = $upload_purpose_meta;
			$query->set( 'meta_query', array( $meta_query ) );
		}
	}

	/**
	 * Creates additional columns for the archival posts in the backend.
	 * Added Columns are: sip_folder, purpose, user, numeration
	 * @link https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/
	 *
	 * @param string[] $defaults
	 *
	 * @return array
	 */
	public static function starg_archival_table_head( array $defaults = array() ) : array {
		$defaults_temp = $defaults;
		unset($defaults);

		$defaults['cb']          = $defaults_temp['cb'];
		$defaults['title']       = $defaults_temp['title'];

		unset($defaults_temp['cb'],$defaults_temp['title']);

		$defaults['sip_folder']  = esc_html__('SIP-Folder', 'sip');
		$defaults['purpose']     = esc_html__('Upload Purpose', 'sip');
		$defaults['user']        = esc_html__('User', 'sip');
		$defaults['numeration']  = esc_html__('Numeration', 'sip');

		$defaults = array_merge( $defaults, $defaults_temp );
		return $defaults;
	}

	/**
	 * Adds the data to the added columns for the archival posts in the backend.
	 * Columns are added in function @see starg_archival_table_head
	 * Added Columns are: sip_folder, purpose, user, numeration
	 *
	 * @link https://developer.wordpress.org/reference/hooks/manage_post-post_type_posts_custom_column/
	 *
	 * @param string $column_name
	 * @param int $post_id
	 *
	 * @return void
	 */
	public static function starg_archival_table_content( string $column_name, int $post_id ) : void {
		if ( $column_name === 'sip_folder' ) {
			$sip_folder = esc_attr( get_post_meta( $post_id, '_archival_sip_folder', true ) );
			echo ( $sip_folder ) ? : esc_html__( 'deleted', 'sip' );
		}

		if ( $column_name === 'purpose' ) {
			$archival_upload_purpose  = esc_attr( get_post_meta( $post_id, '_archival_upload_purpose', true ) );
			$upload_purpose_admin_url = add_query_arg( array(
				'post_type'      => Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG,
				'upload_purpose' => $archival_upload_purpose,
			), admin_url( 'edit.php' ) );
			echo '<a href="' . $upload_purpose_admin_url . '">' . $archival_upload_purpose . '</a>';
		}

		if ( $column_name === 'user' ) {
			$author_id      = esc_attr( get_post_field( 'post_author', $post_id ) );
			$display_name   = esc_html( get_the_author_meta( 'display_name' , $author_id ) );
			$user_admin_url = add_query_arg( array(
				'post_type' => Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG,
				'author'    => $author_id,
			), admin_url( 'edit.php' ) );
			echo '<a href="' . $user_admin_url . '">' . $display_name . '</a>';
		}

		if ( $column_name === 'numeration' ) {
			echo esc_attr( get_post_meta( $post_id, '_archival_numeration', true ) );
		}
	}

	/**
	 * Deactivates some settings for the wordpress editor for archival posts.
	 * The editor will not be able to use the visual editor, the quicktags or the media-upload-button.
	 * @link https://developer.wordpress.org/reference/hooks/wp_editor_settings/
	 *
	 * @param array $settings
	 * @param string $editor_id
	 *
	 * @return array $settings
	 */
	public static function starg_archival_remove_media_buttons( array $settings, string $editor_id ) : array {
		$current_screen = get_current_screen();
		if ( Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG !== $current_screen->post_type || $editor_id !== 'content' ) {
			return $settings;
		}

		$settings[ 'tinymce' ]       = false;
		$settings[ 'quicktags' ]     = false;
		$settings[ 'media_buttons' ] = false;

		return $settings;
	}

}
