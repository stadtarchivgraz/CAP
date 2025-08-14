<?php

$current_locale = strtolower(get_locale());
$user_archive   = false;
$tax_query      = array();
$meta_query     = array();

$paged = get_query_var('paged') ?: 1;

// sets the main arguments for the archival-query.
$args = array(
	'post_type'   => 'archival',
	'post_status' => 'publish',
	'lang'        => '',
	'paged'       => $paged,
);

// Admins or editors should see drafts as well.
if ( current_user_can('edit_others_posts') ) {
	$args['post_status'] = array( 'pending', 'publish', 'draft', );
}

// regular user should only see their entries.
if ( ! current_user_can('manage_options') ) {
	$user_archive = (int) esc_attr( get_user_meta(get_current_user_id(), 'user_archive', true) );
	$tax_query[]  = array(
		'taxonomy' => 'archive',
		'field'    => 'term_id',
		'terms'    => $user_archive
	);
}

// check for filtering options
if (isset($_GET['filter-archive']) && $_GET['filter-archive'] ) {
	$tax_query[] = array(
		'taxonomy' => 'archive',
		'field'    => 'slug',
		'terms'    => sanitize_text_field( $_GET['filter-archive'] ),
	);
}

if ( isset($_GET['filter-tag']) && $_GET['filter-tag'] ) {
	$tax_query[] = array(
		'taxonomy' => 'archival_tag',
		'field'    => 'slug',
		'terms'    => sanitize_text_field( $_GET['filter-tag'] )
	);
}

if ( isset($_GET['filter-purpose']) && $_GET['filter-purpose'] ) {
	$meta_query[] = array(
		'key'   => '_archival_upload_purpose',
		'value' => sanitize_text_field( $_GET['filter-purpose'] ),
	);
}

if ( isset($_GET['filter-year']) && $_GET['filter-year'] ) {
	$meta_query[] = array(
		'key'     => '_archival_from',
		'value'   => sanitize_text_field( $_GET['filter-year'] ),
		'type'    => 'DATETIME',
		'compare' => 'LIKE'
	);
}

if ( isset($_GET['filter-search']) && $_GET['filter-search'] ) {
	$args['s'] = sanitize_text_field( $_GET['filter-search'] );
}

if ($tax_query) {
	$args['tax_query'] = $tax_query;
	if (count($tax_query) > 1) {
		$args['tax_query']['relation'] = 'AND';
	}
}

if ($meta_query) {
	$args['meta_query'] = $meta_query;
	if (count($meta_query) > 1) {
		$args['tax_query']['relation'] = 'AND';
	}
}
?>

<form id="sip-filter" name="sip-filter" action="" method="get" class="container">
	<div class="columns is-multiline">
		<?php // todo: maybe extract the get_terms part as it might result in a wp_error! ?>
		<?php if ( current_user_can( 'manage_options' ) && get_terms( array( 'taxonomy' => 'archive', 'hide_empty' => false, ) ) ) : ?>
			<div class="column is-full">
				<div class="field">
					<label for="filter-archive"><?php esc_html_e('Archive', 'sip'); ?></label>
					<div class="control">
						<?php
						$selected = (isset($_GET['filter-archive'])) ? sanitize_text_field( $_GET['filter-archive'] ) : '';
						wp_dropdown_categories(array(
							'show_option_all' => esc_attr__('all', 'sip'),
							'taxonomy'        => 'archive',
							'name'            => 'filter-archive',
							'orderby'         => 'name',
							'selected'        => $selected,
							'show_count'      => true,
							'hide_empty'      => true,
							// 'hide_empty'      => false,
							'value_field'     => 'slug',
							'hierarchical'    => true,
						));
						?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="column is-full-tablet is-6-desktop">
			<div class="columns">
				<div class="column is-6-tablet">
					<?php
					$upload_purpose = array();
					$upload_purpose_options = array();

					// todo: we should change the way we store the upload purposes! Currently, we're saving the translated string from the plugin options.
					// This means we get different values for different user! This means we can't filter ALL entries based on this metadata - we can only filter all german ones, all english ones and so on!
					// maybe bypass this problem by looping through every translation?
					if ( carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale ) ) {
						$upload_purpose_options = carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale );
						$upload_purpose_options = explode("\r\n",  $upload_purpose_options);
					}

					foreach ( $upload_purpose_options as $single_upload_purpose_option ) {
						$single_upload_purpose_option = esc_attr( $single_upload_purpose_option );
						if ( ! $user_archive ) {
							$upload_purpose[$single_upload_purpose_option] = starg_get_upload_purpose_post_count( $single_upload_purpose_option );
						} else {
							$upload_purpose[$single_upload_purpose_option] = starg_get_upload_purpose_post_count_for_user( $user_archive, $single_upload_purpose_option );
						}
					}
					?>
					<div class="field">
						<label for="filter-purpose"><?php esc_html_e('Upload purpose', 'sip'); ?></label>
						<div class="control">
							<select name="filter-purpose" id="filter-purpose" class="postform">
								<option value="0"><?php esc_html_e('Show all', 'sip'); ?></option>
								<?php
								$selected_upload_purpose = ( isset( $_GET['filter-purpose'] ) && $_GET['filter-purpose'] ) ? sanitize_text_field( $_GET['filter-purpose'] ) : 0;
								foreach ( $upload_purpose as $key => $count ) :
									?>
									<?php if ( $count ) : ?>
										<option class="level-0" value="<?php echo $key; ?>" <?php selected( $selected_upload_purpose, $key ); ?>>
											<?php echo $key; ?>&nbsp;&nbsp;(<?php echo $count; ?>)
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				<div class="column is-6-tablet">
					<?php
					if ( ! $user_archive ) {
						$years = starg_get_upload_year_post_count();
					} else {
						$years = starg_get_upload_year_post_count_for_user( $user_archive );
					}
					?>
					<div class="field">
						<label for="filter-year"><?php esc_html_e('Year', 'sip'); ?></label>
						<div class="control">
							<select name="filter-year" id="filter-year" class="postform">
								<option value="0"><?php esc_html_e('Show all', 'sip'); ?></option>
								<?php
								$selected_year = ( isset( $_GET['filter-year'] ) && $_GET['filter-year'] ) ? sanitize_text_field( $_GET['filter-year'] ) : 0;
								foreach ( $years as $year ) :
									?>
									<?php if ( $year->sip_count && $year->sip_date ) : ?>
										<option class="level-0" value="<?php echo $year->sip_date; ?>" <?php selected( $selected_year, $year->sip_date ); ?>>
											<?php echo $year->sip_date; ?>&nbsp;&nbsp;(<?php echo $year->sip_count; ?>)
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="column is-full-tablet is-6-desktop">
			<div class="columns">
				<div class="column is-6-tablet">
					<div class="field">
						<label for="filter-tag"><?php esc_html_e('Tags', 'sip'); ?></label>
						<div class="control">
							<?php
							$selected_archival_tag = ( isset( $_GET['filter-tag'] ) && $_GET['filter-tag'] ) ? sanitize_text_field( $_GET['filter-tag'] ) : 0;
							$archival_tag_taxonomy = get_taxonomy('archival_tag');
							if ( $user_archive ) :
								$all_archive_tags = starg_get_archive_tags($user_archive);
								if ( $all_archive_tags ) :
									?>
									<select name="filter-tag" id="filter-tag">
										<option value="0">
											<?php echo sprintf(__('Show all %s', 'sip'), $archival_tag_taxonomy->label); ?>
										</option>
										<?php
										foreach ($all_archive_tags as $archive_tag) :
											?>
											<option value="<?php echo $archive_tag->term_taxonomy_id; ?>" <?php selected( $selected_archival_tag, $archive_tag->term_taxonomy_id ); ?>>
												<?php echo starg_get_archival_tag_name( $archive_tag->term_taxonomy_id ); ?> (<?php echo $archive_tag->count; ?>)
											</option>
										<?php endforeach; ?>
									</select>
								<?php
								endif;
							else :
								wp_dropdown_categories(array(
									'show_option_all' => sprintf( esc_attr__('Show all %s', 'sip'), $archival_tag_taxonomy->label ),
									'taxonomy'        => 'archival_tag',
									'name'            => 'filter-tag',
									'orderby'         => 'name',
									'selected'        => $selected_archival_tag,
									'show_count'      => true,
									'hide_empty'      => false,
									'value_field'     => 'slug',
									'hierarchical'    => true,
								));
							endif;
							?>
						</div>
					</div>
				</div>
				<div class="column is-6-tablet">
					<div class="field">
						<label for="filter-search"><?php esc_html_e('Search', 'sip'); ?></label>
						<input type="text" class="input" id="filter-search" name="filter-search" value="<?php echo (isset($_GET['filter-search'])) ? sanitize_text_field( $_GET['filter-search'] ) : ''; ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
</form>

<?php // todo: maybe change to AJAX to be able to use nonces and more security checks? ?>
<script>
	const sip_filter = document.getElementById("sip-filter");

	sip_filter.addEventListener("change", function() {
		this.submit();
	});
</script>

<?php
$archivals = new WP_Query($args);

if ( $archivals->have_posts() ) :
	include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-archivals-list.php');
	wp_reset_postdata();
endif;
