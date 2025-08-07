<?php
/**
 * Register the "archival" custom post type and it's taxonomies "archive" and "archival_tag".
 * @return void
 */
function starg_custom_post_type_archival() : void {
	///////////////
	// post-type //
	///////////////
	$post_type_labels = array(
		'name'                  => _x( 'Archival materials', 'Post Type General Name', 'sip' ),
		'singular_name'         => _x( 'Archival', 'Post Type Singular Name', 'sip' ),
		'menu_name'             => __( 'Archival Materials', 'sip' ),
		'name_admin_bar'        => __( 'Archival', 'sip' ),
		'archives'              => __( 'Item Archives', 'sip' ),
		'attributes'            => __( 'Item Attributes', 'sip' ),
		'parent_item_colon'     => __( 'Parent Item:', 'sip' ),
		'all_items'             => __( 'All Items', 'sip' ),
		'add_new_item'          => __( 'Add New Item', 'sip' ),
		'add_new'               => __( 'Add New', 'sip' ),
		'new_item'              => __( 'New Item', 'sip' ),
		'edit_item'             => __( 'Edit Item', 'sip' ),
		'update_item'           => __( 'Update Item', 'sip' ),
		'view_item'             => __( 'View Item', 'sip' ),
		'view_items'            => __( 'View Items', 'sip' ),
		'search_items'          => __( 'Search Item', 'sip' ),
		'not_found'             => __( 'Not found', 'sip' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'sip' ),
		'featured_image'        => __( 'Featured Image', 'sip' ),
		'set_featured_image'    => __( 'Set featured image', 'sip' ),
		'remove_featured_image' => __( 'Remove featured image', 'sip' ),
		'use_featured_image'    => __( 'Use as featured image', 'sip' ),
		'insert_into_item'      => __( 'Insert into item', 'sip' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'sip' ),
		'items_list'            => __( 'Items list', 'sip' ),
		'items_list_navigation' => __( 'Items list navigation', 'sip' ),
		'filter_items_list'     => __( 'Filter items list', 'sip' ),
	);
	$post_type_rewrite = array(
		'slug'                  => __('archival', 'sip'),
		'with_front'            => true,
		'pages'                 => true,
		'feeds'                 => true,
	);
	$post_type_args = array(
		'label'                 => __( 'Archival', 'sip' ),
		'description'           => __( 'Post Type for archival materials in SIP Plugin', 'sip' ),
		'labels'                => $post_type_labels,
		'supports'              => array( 'title','editor','author', ),
		'taxonomies'            => array( 'archival_tag','archive', ),
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
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		// 'rewrite'               => $post_type_rewrite,

		// changes from live server!
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'rewrite'               => false, //$rewrite,

		'capability_type'       => 'post',
	);
	register_post_type( 'archival', $post_type_args );

	/////////////////////
	// custom-taxonomy //
	/////////////////////
	$archive_tax_labels = array(
		'name'                       => _x( 'Archives', 'Taxonomy General Name', 'sip' ),
		'singular_name'              => _x( 'Archive', 'Taxonomy Singular Name', 'sip' ),
		'menu_name'                  => __( 'Archive', 'sip' ),
		'all_items'                  => __( 'All Archives', 'sip' ),
		'parent_item'                => __( 'Parent Item', 'sip' ),
		'parent_item_colon'          => __( 'Parent Item:', 'sip' ),
		'new_item_name'              => __( 'New Item Name', 'sip' ),
		'add_new_item'               => __( 'Add New Item', 'sip' ),
		'edit_item'                  => __( 'Edit Item', 'sip' ),
		'update_item'                => __( 'Update Item', 'sip' ),
		'view_item'                  => __( 'View Item', 'sip' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'sip' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'sip' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'sip' ),
		'popular_items'              => __( 'Popular Items', 'sip' ),
		'search_items'               => __( 'Search Items', 'sip' ),
		'not_found'                  => __( 'Not Found', 'sip' ),
		'no_terms'                   => __( 'No items', 'sip' ),
		'items_list'                 => __( 'Items list', 'sip' ),
		'items_list_navigation'      => __( 'Items list navigation', 'sip' ),
	);
	$archive_tax_rewrite = array(
		'slug'                       => __('archive', 'sip'),
		'with_front'                 => true,
		'hierarchical'               => false,
	);
	$archive_tax_capabilities = array(
		'manage_terms'               => 'manage_options',
		'edit_terms'                 => 'manage_options',
		'delete_terms'               => 'manage_options',
		'assign_terms'               => 'edit_posts',
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
	register_taxonomy( 'archive', array( 'archival' ), $archive_tags_args );

	/////////////////////
	// custom-taxonomy //
	/////////////////////
	$archival_tax_labels = array(
		'name'                       => _x( 'Archival Tags', 'Taxonomy General Name', 'sip' ),
		'singular_name'              => _x( 'Archival Tag', 'Taxonomy Singular Name', 'sip' ),
		'menu_name'                  => __( 'Archival Tag', 'sip' ),
		'all_items'                  => __( 'All Items', 'sip' ),
		'parent_item'                => __( 'Parent Item', 'sip' ),
		'parent_item_colon'          => __( 'Parent Item:', 'sip' ),
		'new_item_name'              => __( 'New Item Name', 'sip' ),
		'add_new_item'               => __( 'Add New Item', 'sip' ),
		'edit_item'                  => __( 'Edit Item', 'sip' ),
		'update_item'                => __( 'Update Item', 'sip' ),
		'view_item'                  => __( 'View Item', 'sip' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'sip' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'sip' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'sip' ),
		'popular_items'              => __( 'Popular Items', 'sip' ),
		'search_items'               => __( 'Search Items', 'sip' ),
		'not_found'                  => __( 'Not Found', 'sip' ),
		'no_terms'                   => __( 'No items', 'sip' ),
		'items_list'                 => __( 'Items list', 'sip' ),
		'items_list_navigation'      => __( 'Items list navigation', 'sip' ),
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
	);
	register_taxonomy( 'archival_tag', array( 'archival' ), $archival_tags_args );

}
add_action( 'init', 'starg_custom_post_type_archival', 0 );

/**
 * Adds additional Filters for the archival post-type in the backend.
 * @link https://developer.wordpress.org/reference/hooks/restrict_manage_posts/
 * @return void
 */
function starg_filter_archival_by_taxonomy() : void {
	$current_locale = strtolower( get_locale() );
	global $typenow;

	if ( 'archival' !== $typenow ) { return; }

	// displays a select in the backend to filter between taxonomies for the archival post-type.
	if ( current_user_can( 'manage_options' ) ) {
		$taxonomies = array( 'archive' );
		foreach ( $taxonomies as $taxonomy ) {
			$selected      = ( isset( $_GET[ $taxonomy ] ) ) ? sanitize_key( $_GET[ $taxonomy ] ) : '';
			$info_taxonomy = get_taxonomy( $taxonomy );
			wp_dropdown_categories( array(
				'show_option_all' => sprintf( __( 'Show all %s', 'sip' ), $info_taxonomy->label ),
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
		<option value="0"><?php _e( 'Show all', 'sip' ); ?></option>
		<?php foreach ( $options as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>"<?php echo ( $value === $upload_purpose ) ? ' selected' : ''; ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
<?php
}
add_action( 'restrict_manage_posts', 'starg_filter_archival_by_taxonomy' );

/**
 * Filters the archival posts in the backend based on the upload purposes.
 * @param WP_Query $query
 * @return void
 */
function starg_admin_filter_archival( WP_Query $query ) : void {
	if ( ! is_admin() ) { return; }

	global $typenow;
	if ( 'archival' !== $typenow ) { return; }

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
add_action( 'pre_get_posts', 'starg_admin_filter_archival', 99, 1 );

/**
 * Creates additional columns for the archival posts in the backend.
 * Added Columns are: sip_folder, purpose, user, numeration
 * @link https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/
 *
 * @param string[] $defaults
 *
 * @return array
 */
function starg_archival_table_head( array $defaults = array() ) : array {
	$defaults_temp = $defaults;
	unset($defaults);

	$defaults['cb']          = $defaults_temp['cb'];
	$defaults['title']       = $defaults_temp['title'];

	unset($defaults_temp['cb'],$defaults_temp['title']);

	$defaults['sip_folder']  = __('SIP-Folder', 'sip');
	$defaults['purpose']     = __('Upload Purpose', 'sip');
	$defaults['user']        = __('User', 'sip');
	$defaults['numeration']  = __('Numeration', 'sip');

	$defaults = array_merge( $defaults, $defaults_temp );
	return $defaults;
}
add_filter( 'manage_archival_posts_columns', 'starg_archival_table_head' );

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
function starg_archival_table_content( string $column_name, int $post_id ) : void {
	if ( $column_name === 'sip_folder' ) {
		$sip_folder = esc_attr( get_post_meta( $post_id, '_archival_sip_folder', true ) );
		echo ( $sip_folder ) ? : __( 'deleted', 'sip' );
	}

	if ( $column_name === 'purpose' ) {
		$archival_upload_purpose  = esc_attr( get_post_meta( $post_id, '_archival_upload_purpose', true ) );
		$upload_purpose_admin_url = add_query_arg( array(
			'post_type'      => 'archival',
			'upload_purpose' => $archival_upload_purpose,
		), admin_url( 'edit.php' ) );
		echo '<a href="' . $upload_purpose_admin_url . '">' . $archival_upload_purpose . '</a>';
	}

	if ( $column_name === 'user' ) {
		$author_id      = get_post_field( 'post_author', $post_id );
		$display_name   = get_the_author_meta( 'display_name' , $author_id );
		$user_admin_url = add_query_arg( array(
			'post_type' => 'archival',
			'author'    => $author_id,
		), admin_url( 'edit.php' ) );
		echo '<a href="' . $user_admin_url . '">' . $display_name . '</a>';
	}

	if ( $column_name === 'numeration' ) {
		echo esc_attr( get_post_meta( $post_id, '_archival_numeration', true ) );
	}
}
add_action( 'manage_archival_posts_custom_column', 'starg_archival_table_content', 10, 2 );

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
function starg_archival_remove_media_buttons( array $settings, string $editor_id ) : array {
	global $current_screen;
	if ( 'archival' !== $current_screen->post_type || $editor_id !== 'content' ) {
		return $settings;
	}

	$settings[ 'tinymce' ]       = false;
	$settings[ 'quicktags' ]     = false;
	$settings[ 'media_buttons' ] = false;

	return $settings;
}
add_filter( 'wp_editor_settings', 'starg_archival_remove_media_buttons', 10, 2 );
