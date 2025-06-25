<?php
global $wpdb;

$current_locale = strtolower(get_locale());
$user_archive = false;
$tax_query = array();
$meta_query = array();

$paged = (get_query_var( 'paged' ))?:1;

$args = array(
	'post_type'   => 'archival',
	'post_status' => 'publish',
	'lang' => '',
    'paged'     => $paged,
);

if ( current_user_can( 'edit_others_posts' ) ) {
	$args['post_status'] = array( 'pending', 'publish' );
}

if ( ! current_user_can( 'manage_options' ) ) {
	$user_archive      = get_user_meta( get_current_user_id(), 'user_archive', true );
	$tax_query[]         = array(
			'taxonomy' => 'archive',
			'field'    => 'term_id',
			'terms'    => $user_archive
	);

}

if(isset($_GET['filter-archive']) && $_GET['filter-archive']) {
	$tax_query[]         = array(
        'taxonomy' => 'archive',
        'field'    => 'slug',
        'terms'    => $_GET['filter-archive']
	);
}

if(isset($_GET['filter-tag']) && $_GET['filter-tag']) {
	$tax_query[]         = array(
        'taxonomy' => 'archival_tag',
        'field'    => 'slug',
        'terms'    => $_GET['filter-tag']
	);
}

if(isset($_GET['filter-purpose']) && $_GET['filter-purpose']) {
	$meta_query[]         = array(
		'key' => '_archival_upload_purpose',
		'value'    => $_GET['filter-purpose'],
	);
}

if(isset($_GET['filter-year']) && $_GET['filter-year']) {
	$meta_query[]         = array(
		'key' => '_archival_from',
		'value'    => $_GET['filter-year'],
		'type' => 'DATETIME',
		'compare'    => 'LIKE'
	);
}

if(isset($_GET['filter-search']) && $_GET['filter-search']) {
    $args['s'] = $_GET['filter-search'];
}

if($tax_query) {
	$args['tax_query'] = $tax_query;
    if(count($tax_query) > 1) {
        $args['tax_query']['relation'] = 'AND';
    }
}

if($meta_query) {
	$args['meta_query'] = $meta_query;
	if(count($meta_query) > 1) {
		$args['tax_query']['relation'] = 'AND';
	}
}

//print_r($args);

?>
<form id="sip-filter" name="sip-filter" action="" method="get" class="container sip">
	<div class="columns is-multiline">
        <?php if(current_user_can('manage_options')) : ?>
            <div class="column is-full">
                <field>
                    <label for="filter-archive"><?php _e( 'Archive', 'sip' ); ?></label>
                    <div class="control">
                        <?php
                        $selected      = ( isset( $_GET['filter-archive'] ) ) ? $_GET['filter-archive'] : '';
                        $info_taxonomy = get_taxonomy( 'archive' );
                        wp_dropdown_categories( array(
	                        'show_option_all'   => __('all', 'sip'),
                            'taxonomy'        =>'archive',
                            'name'            => 'filter-archive',
                            'orderby'         => 'name',
                            'selected'        => $selected,
                            'show_count'      => true,
                            'hide_empty'      => true,
                            'value_field'     => 'slug',
                            'hierarchical'    => true

                        ) );
                        ?>
                    </div>
                </field>
            </div>
        <?php endif; ?>
        <div class="column is-full-tablet is-6-desktop">
            <div class="columns">
                <div class="column is-6-tablet">
                    <?php
                    $upload_purpose = array();
                    if($upload_purpose_options = carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale )) {
	                    $upload_purpose_options = explode("\r\n",  $upload_purpose_options);
                    } else $upload_purpose_options = array();
                    foreach ($upload_purpose_options as $upload_purpose_option) {
                        if(!$user_archive) {
                            $upload_purpose[$upload_purpose_option] = $wpdb->get_var($wpdb->prepare("SELECT count(post_id) FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id = ID WHERE meta_key = '_archival_upload_purpose' AND meta_value = %s AND post_status = 'publish'", $upload_purpose_option));
                        } else {
                            $upload_purpose[$upload_purpose_option] = $wpdb->get_var($wpdb->prepare("SELECT count(post_id) FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id = ID LEFT JOIN $wpdb->term_relationships ON object_id = ID WHERE term_taxonomy_id = %d AND meta_key = '_archival_upload_purpose' AND meta_value = %s AND post_status = 'publish'", $user_archive, $upload_purpose_option));
                        }
                    }
                    ?>
                    <field>
                        <label for="filter-purpose"><?php _e( 'Upload purpose', 'sip' ); ?></label>
                        <div class="control">
                            <select name="filter-purpose" id="filter-purpose" class="postform">
                                <option value="0"><?php _e( 'Show all', 'sip' ); ?></option>
                                <?php foreach ($upload_purpose as $key => $count) : ?>
                                    <?php if($count) :
                                        $selected = ( isset( $_GET['filter-purpose'] ) &&  $_GET['filter-purpose'] === $key) ? ' selected' : '';
                                        ?>
                                        <option class="level-0" value="<?= $key; ?>"<?= $selected; ?>><?= $key; ?>&nbsp;&nbsp;(<?= $count; ?>)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </field>
                </div>
                <div class="column is-6-tablet">
                    <?php
                    if(!$user_archive) {
                        $years = $wpdb->get_results( "SELECT count(post_id) as sip_count, DATE_FORMAT(meta_value, '%Y') as sip_date FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id = ID WHERE meta_key = '_archival_from' AND post_status = 'publish' GROUP BY sip_date ORDER BY sip_date DESC" );
                    } else {
                        $years = $wpdb->get_results($wpdb->prepare("SELECT count(post_id) as sip_count, DATE_FORMAT(meta_value, '%Y') as sip_date FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id = ID LEFT JOIN $wpdb->term_relationships ON object_id = ID WHERE term_taxonomy_id = %d AND meta_key = '_archival_from' AND post_status = 'publish' GROUP BY sip_date ORDER BY sip_date DESC", $user_archive ));
                    }
                    ?>
                    <field>
                        <label for="filter-year"><?php _e( 'Year', 'sip' ); ?></label>
                        <div class="control">
                            <select name="filter-year" id="filter-year" class="postform">
                                <option value="0"><?php _e( 'Show all', 'sip' ); ?></option>
                                <?php foreach ($years as $year) : ?>
                                    <?php if($year->sip_count) :
                                        $selected = ( isset( $_GET['filter-year'] ) &&  $_GET['filter-year'] === $year->sip_date) ? ' selected' : '';
                                        ?>
                                        <option class="level-0" value="<?= $year->sip_date; ?>"<?= $selected; ?>><?= $year->sip_date; ?>&nbsp;&nbsp;(<?= $year->sip_count; ?>)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </field>
                </div>
            </div>
        </div>

		<div class="column is-full-tablet is-6-desktop">
			<div class="columns">
                <div class="column is-6-tablet">
                    <field>
                        <label for="filter-tag"><?php _e( 'Tags', 'sip' ); ?></label>
                        <div class="control">
							<?php
							$info_taxonomy = get_taxonomy( 'archival_tag' );
							if($user_archive) {
	                            $all_archive_tags = get_archive_tags($user_archive); ?>
                                <select name="filter-tag" id="filter-tag">
                                    <option value="0"><?= sprintf( __( 'Show all %s', 'sip' ), $info_taxonomy->label ); ?></option>
                                    <?php foreach ($all_archive_tags as $archive_tag) :
	                                    $selected = ( isset( $_GET['filter-tag'] ) && $_GET['filter-tag'] == $archive_tag->term_taxonomy_id ) ? ' selected' : ''; ?>
                                        <option value="<?= $archive_tag->term_taxonomy_id; ?>"<?= $selected; ?>><?= get_archival_tag_name($archive_tag->term_taxonomy_id); ?> (<?= $archive_tag->count; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            <?php
                            } else {
								$selected      = ( isset( $_GET['filter-tag'] ) ) ? $_GET['filter-tag'] : '';
								wp_dropdown_categories( array(
									'show_option_all' => sprintf( __( 'Show all %s', 'sip' ), $info_taxonomy->label ),
									'taxonomy'        =>'archival_tag',
									'name'            => 'filter-tag',
									'orderby'         => 'name',
									'selected'        => $selected,
									'show_count'      => true,
									'hide_empty'      => true,
									'value_field'     => 'slug',
									'hierarchical'    => true
								) );
							}
							?>
                        </div>
                    </field>
                </div>
				<div class="column is-6-tablet">
                    <field>
                        <label for="filter-search"><?php _e( 'Search', 'sip' ); ?></label>
                        <input type="text" class="input" id="filter-search" name="filter-search" value="<?= (isset( $_GET['filter-search'] ))?$_GET['filter-search']:''; ?>">
                    </field>
				</div>
			</div>
		</div>
	</div>
</form>

<script>
    const sip_filter = document.getElementById("sip-filter");

    sip_filter.addEventListener("change", function() {
        this.submit();
    });

</script>

<?php
$archivals = new WP_Query($args);

//print_r($archivals);

$pages = get_pages(array(
	'meta_key' => '_wp_page_template',
	'meta_value' => 'sip-upload.php',
	'hierarchical' => 0 
));

if($archivals->have_posts()) :
	include( dirname( __FILE__ ) . '/template-parts/content-archivals-list.php' );
endif;
wp_reset_query();
?>
