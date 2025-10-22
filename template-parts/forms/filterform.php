<?php
if (! defined('WPINC')) { die; }

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/filter_archival_query.class.php' );
$filter_archival_query = new Filter_Archival_Query();
$filtered_query = $filter_archival_query->maybe_trigger_filter();
if ( ! $filtered_query ) {
	echo starg_get_notification_message( esc_html__( 'You are not allowed to view this page.', 'sip' ), 'is-error is-light' );
	return;
}

$filter_input    = $filter_archival_query->get_user_input();
$current_locale  = strtolower(get_locale());
$user_archive_id = (int) esc_attr( get_user_meta( get_current_user_id(), 'user_archive', true ) );
?>

<form id="sip-filter" action="" method="get" class="container">
	<div class="columns is-multiline">
		<?php
		$archive = $filter_input['filter-archive'];
		$tag     = $filter_input['filter-tag'];
		$purpose = $filter_input['filter-purpose'];
		$year    = $filter_input['filter-year'];
		$search  = $filter_input['filter-search'];

		$archive_id   = $user_archive_id;
		$archive_term = get_term_by( 'slug', $archive, Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG );
		if ( $archive_term ) {
			$archive_id = $archive_term->term_id;
		}

		$archival_terms = get_terms( array( 'taxonomy' => Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG, 'hide_empty' => true, 'number' => 1, ) );
		if ( current_user_can( 'edit_others_posts' ) && ! is_wp_error( $archival_terms ) && $archival_terms ) :
			?>
			<div class="column is-full">
				<div class="field">
					<label for="filter-archive"><?php esc_html_e('Archive', 'sip'); ?></label>
					<div class="control">
						<?php
						$selected = $archive;
						if ( ! $selected && ! current_user_can( 'manage_options' ) ) {
							$archive_object = get_term_by( 'term_id', $user_archive_id, Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG );
							if ( $archive_object ) {
								$selected = $archive_object->slug;
							}
						}

						// Keep in mind, that this function only counts published posts!
						// We use the "none"-Option here instead of "all" as we can not easily set the option for "show_option_all" other than 0 but we can set "show_option_none" alongside "option_none_value"!
						wp_dropdown_categories(array(
							'show_option_none'  => esc_attr__('all', 'sip'),
							'option_none_value' => 'all',
							'taxonomy'          => Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG,
							'name'              => 'filter-archive',
							'orderby'           => 'name',
							'selected'          => $selected,
							// 'show_count'        => true,
							'hide_empty'        => true,
							'value_field'       => 'slug',
							'hierarchical'      => true,
							'hide_if_empty'     => true,
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
						if ( ! $archive ) {
							$upload_purpose[$single_upload_purpose_option] = DB_Query_Helper::starg_get_upload_purpose_post_count( $single_upload_purpose_option );
						} else {
							$upload_purpose[$single_upload_purpose_option] = DB_Query_Helper::starg_get_upload_purpose_post_count_for_user( $archive_id, $single_upload_purpose_option );
						}
					}
					?>
					<div class="field">
						<label for="filter-purpose"><?php esc_html_e('Upload purpose', 'sip'); ?></label>
						<div class="control">
							<select name="filter-purpose" id="filter-purpose" class="postform">
								<option value="0"><?php esc_html_e('Show all', 'sip'); ?></option>
								<?php
								$selected_upload_purpose = $purpose;
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
					if ( ! $archive_id ) {
						$years = DB_Query_Helper::starg_get_upload_year_post_count();
					} else {
						$years = DB_Query_Helper::starg_get_upload_year_post_count_for_user( $archive_id );
					}
					?>
					<div class="field">
						<label for="filter-year"><?php esc_html_e('Year', 'sip'); ?></label>
						<div class="control">
							<select name="filter-year" id="filter-year" class="postform">
								<option value="0"><?php esc_html_e('Show all', 'sip'); ?></option>
								<?php
								$selected_year = $year;
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
							$archival_tag_taxonomy = get_taxonomy(Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG);
							if ( $archive && $archive_id ) :
								$all_archive_tags = DB_Query_Helper::starg_get_archive_tags( ( 'all' === $archive ) ? 0 : $archive_id );
								if ( $all_archive_tags ) :
									?>
									<select name="filter-tag" id="filter-tag">
										<option value="0">
											<?php echo sprintf( esc_html__('Show all %s', 'sip'), $archival_tag_taxonomy->label); ?>
										</option>
										<?php
										foreach ($all_archive_tags as $archive_tag) :
											?>
											<option value="<?php echo esc_attr( $archive_tag->slug ); ?>" <?php selected( $tag, $archive_tag->slug ); ?>>
												<?php echo esc_html( $archive_tag->name ); ?> (<?php echo esc_attr( $archive_tag->count ); ?>)
											</option>
										<?php endforeach; ?>
									</select>
								<?php
								endif;
							else :
								wp_dropdown_categories(array(
									'show_option_all' => sprintf( esc_attr__('Show all %s', 'sip'), $archival_tag_taxonomy->label ),
									'taxonomy'        => Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG,
									'name'            => 'filter-tag',
									'orderby'         => 'name',
									'selected'        => $tag,
									'show_count'      => true,
									'hide_empty'      => false,
									'value_field'     => 'slug',
									'hierarchical'    => true,
									'hide_if_empty'   => true,
								));
							endif;
							?>
						</div>
					</div>
				</div>
				<div class="column is-6-tablet">
					<div class="field">
						<label for="filter-search"><?php esc_html_e('Search', 'sip'); ?></label>
						<input type="text" class="input" id="filter-search" name="filter-search" value="<?php echo $search; ?>">
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
$archivals = new WP_Query( $filtered_query );

if ( $archivals->have_posts() ) :
	include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-archivals-list.php');
	wp_reset_postdata();
endif;
