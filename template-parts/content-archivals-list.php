<?php
$edit_archival_url = starg_get_the_edit_archival_page_url();

// maybe start the creation of the sip-zip:
$create_sip = apply_filters( 'starg/create_sip', null );
if ( $create_sip instanceof Create_Sip ) {
	$create_sip->display_notification();
}
?>

<ol class="pb-0 columns is-multiline starg_archival_list">
	<?php
	// @var WP_Query $archivals declared in filterform.php or in profileform.php
	while ($archivals->have_posts()) :
		$archivals->the_post();
		$archival_post_status = get_post_status();
		$current_post_id      = get_the_ID();
		$archival_post_url    = starg_get_the_archival_page_template_url( $current_post_id );
		?>
		<li class="column is-6-tablet is-4-desktop">
			<div class="card is-flex is-flex-direction-column" style="height: 100%;">
				<header class="card-header">
					<a class="card-header-title" href="<?php echo $archival_post_url; ?>">
						<?php the_title(); ?>
					</a>
					<?php if (current_user_can('edit_others_archival_records', $current_post_id)) : ?>
						<a class="card-header-icon" href="<?php echo $archival_post_url; ?>">
							<?php
							// actually u should not use variables in translation functions like this.
							// This only works because the needed values for the post_status are defined in the main file of the plugin.
							?>
							<?php echo ( 'publish' === $archival_post_status ) ? esc_html__( 'Accepted', 'sip' ) : esc_html_e($archival_post_status, 'sip'); ?>
						</a>
					<?php endif; ?>
				</header>
				<div class="card-content is-flex-grow-1">
					<div class="content">
						<p>
							<?php
							// We're setting the length of the excerpt with a translation function. This way every translator can set a different number of words to display in the excerpt.
							$excerpt_length = (int) esc_attr_x('30', 'This overrides the default length of the excerpt in the archive for archival record posts.', 'sip');
							$excerpt_length = (int) apply_filters('excerpt_length', $excerpt_length);
							echo wp_trim_words(get_the_content(), $excerpt_length, '&hellip;');
							?>
						</p>
					</div>
				</div>
				<div class="card-footer">
					<a class="card-footer-item" href="<?php echo $archival_post_url; ?>"><?php esc_html_e('Preview', 'sip'); ?></a>
					<?php if ($sip_folder = esc_attr( get_post_meta( $current_post_id, '_archival_sip_folder', true ) ) ) : ?>
						<?php if ( ( current_user_can('edit_archival', $current_post_id ) && 'publish' !== $archival_post_status) || current_user_can( 'edit_others_archival_records', $current_post_id ) ) : ?>
							<a class="card-footer-item" href="<?php echo esc_url( add_query_arg( array( 'sipFolder' => $sip_folder, ), $edit_archival_url ) ); ?>">
								<?php esc_html_e('Edit', 'sip'); ?>
							</a>
						<?php endif; ?>
						<?php if ( current_user_can('edit_others_archival_records', $current_post_id) ) : ?>
							<?php // todo: maybe remove target="_blank"? ?>
							<a class="card-footer-item" href="<?php echo ( isset( $create_sip->url_endpoint ) ) ? add_query_arg( array( $create_sip->url_endpoint => true, 'sipFolder' => $sip_folder ) ) : '#'; ?>" target="_blank" title="<?php esc_attr_e('Create the SIP', 'sip'); ?>">
								<?php esc_html_e('SIP', 'sip'); ?>
							</a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</li>
	<?php endwhile; ?>
</ol>
<?php
$GLOBALS['wp_query']->max_num_pages = $archivals->max_num_pages;
the_posts_pagination(array('class' => 'archival-pagination',));
