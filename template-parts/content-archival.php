<?php
/**
 * Template part to display the content of the single-archival page.
 * we're in the loop!
 */

$current_locale = strtolower(get_locale());
$archival_id    = get_the_ID();

// maybe start the creation of the sip-zip:
$create_sip = apply_filters( 'starg/create_sip', null );
if ( $create_sip instanceof Create_Sip ) {
	$create_sip->display_notification();
}

// maybe start the creation of the sip-pdf:
$create_sip_pdf = apply_filters( 'starg/create_sip_pdf', null );
if ( $create_sip_pdf instanceof Create_Sip_Pdf ) {
	$create_sip_pdf->display_notification();
}

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/sip-archival-actions-form.class.php' );
$sip_archival_actions = new Sip_Archival_Actions;
$sip_archival_actions->process_sip_archival_actions();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('sip'); ?>>
	<div class="container">

		<div class="entry-header">
			<?php the_title('<h1 class="entry-title title is-2">', '</h1>'); ?>
		</div>

		<div class="entry-content content">
			<?php
			// display the content of the SIP content folder.
			include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-sip-folder.php' );

			the_content();

			// displays the map with marker where the files were found.
			include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-map.php' );

			?>
			<dl>
				<dt><?php esc_html_e('Location', 'sip'); ?></dt>
				<dd>
					<?php echo ( get_post_meta( $archival_id, '_archival_address', true ) ) ? : esc_attr__('unknown', 'sip'); ?>
					<?php
					$lat = esc_attr( get_post_meta( $archival_id, '_archival_lat', true ) );
					$lng = esc_attr( get_post_meta( $archival_id, '_archival_lng', true ) );
					if ($lat && $lng) {
						echo ' (' . $lat . '|' . $lng . ')';
					}
					?>
				</dd>
				<dt><?php esc_html_e('Originator', 'sip'); ?></dt>
				<dd><?php echo (get_post_meta($archival_id, '_archival_originator', true)) ?: esc_attr__('unknown', 'sip'); ?></dd>
				<dt><?php esc_html_e('Date/Time', 'sip'); ?></dt>
				<dd><?php echo get_post_meta($archival_id, '_archival_from', true); ?><?php echo ($archival_to = get_post_meta($archival_id, '_archival_to', true)) ? ' &mdash; ' . $archival_to : ''; ?></dd>
				<dt><?php esc_html_e('Upload Purpose', 'sip'); ?></dt>
				<dd><?php echo get_post_meta($archival_id, '_archival_upload_purpose', true); ?></dd>
				<?php if ($archival_blocking_time = get_post_meta($archival_id, '_archival_blocking_time', true)) : ?>
					<dt><?php esc_html_e('Blocking Time', 'sip'); ?></dt>
					<dd><?php echo esc_html( $archival_blocking_time ); ?></dd>
				<?php endif; ?>
				<?php if ($sip_custom_meta = carbon_get_theme_option('sip_custom_meta')) :
					foreach ($sip_custom_meta as $custom_meta) :
						$meta_name = sanitize_title($custom_meta['sip_custom_meta_key']);
						if ($meta_value = get_post_meta($archival_id, '_archival_' . $meta_name, true)) : ?>
							<dt><?php echo esc_attr( $custom_meta['sip_custom_meta_title_' . $current_locale] ); ?></dt>
							<dd><?php echo esc_attr( $meta_value ); ?></dd>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php
				echo strip_tags(get_the_term_list($archival_id, 'archival_tag', '<dt>' . esc_attr__('Tags', 'sip') . '</dt><dd>', ' | ', '</dd>'), '<dt><dd>');
				?>
			</dl>

			<?php if (current_user_can('edit_others_posts')) : ?>
				<h3><?php esc_html_e('Archiv Information', 'sip'); ?></h3>
				<dl>
					<dt><?php esc_html_e('Numbering', 'sip'); ?></dt>
					<dd><?php echo esc_attr( get_post_meta( $archival_id, '_archival_numeration', true ) ); ?></dd>
					<dt><?php esc_html_e('Annotation', 'sip'); ?></dt>
					<dd><?php echo esc_attr( get_post_meta( $archival_id, '_archival_annotation', true ) ); ?></dd>
					<?php if ($sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta')) :
						foreach ($sip_custom_archival_user_meta as $custom_archival_user_meta) :
							$meta_name = sanitize_title($custom_archival_user_meta['sip_custom_archival_user_meta_key']);
							if ($meta_value = get_post_meta($archival_id, '_archival_' . $meta_name, true)) : ?>
								<dt><?php echo esc_attr( $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale] ); ?></dt>
								<dd><?php echo esc_attr( $meta_value ); ?></dd>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</dl>
			<?php endif; ?>
		</div><!-- .entry-content -->

		<footer class="entry-footer default-max-width">
			<div class="archival-actions">
				<?php
				$archival_sip_folder = esc_attr( get_post_meta( $archival_id, '_archival_sip_folder', true ) );
				if ( $archival_sip_folder ) :
					$edit_archival_url            = starg_get_the_edit_archival_page_url();
					$archival_status              = get_post_status( $archival_id );
					$edit_archival_url_sip_folder = esc_url( add_query_arg( array( 'sipFolder' => $archival_sip_folder, ), $edit_archival_url ) );
					?>
					<form target="" method="post">
						<input type="hidden" name="starg_form_name" value="archival_actions_form" aria-hidden="true" />
						<input type="hidden" name="starg_form_post_id" value="<?php the_ID(); ?>" aria-hidden="true" />
						<input type="hidden" name="sipFolder" value="<?php echo $archival_sip_folder; ?>" aria-hidden="true" />
						<?php wp_nonce_field( 'starg_archival_actions_nonce_action', 'starg_archival_actions_nonce', false ); ?>

						<?php // Admins/Editors can edit, create the SIP and create a PDF of the site if the users entry has been accepted! ?>
						<?php if ( current_user_can( 'edit_others_posts' ) && 'publish' === $archival_status ) : ?>
							<?php $create_sip_url     = ( isset( $create_sip->url_endpoint ) )     ? add_query_arg( array( $create_sip->url_endpoint => true, 'sipFolder' => $archival_sip_folder, ) )     : '#'; ?>
							<?php $create_sip_pdf_url = ( isset( $create_sip_pdf->url_endpoint ) ) ? add_query_arg( array( $create_sip_pdf->url_endpoint => true, 'sipFolder' => $archival_sip_folder, ) ) : '#'; ?>
							<a class="button is-large" href="<?php echo $edit_archival_url_sip_folder; ?>" target="_blank">
								<?php esc_html_e('Edit', 'sip'); ?>
							</a>
							<a class="button is-large" href="<?php echo $create_sip_url; ?>">
								<?php esc_html_e('SIP', 'sip'); ?>
							</a>
							<a class="button is-large" href="<?php echo $create_sip_pdf_url; ?>">
								<?php esc_html_e('PDF', 'sip'); ?>
							</a>
						<?php // Admins/Editors can edit, accept or decline the users entry. ?>
						<?php elseif ( current_user_can( 'edit_others_posts' ) && 'publish' !== $archival_status ) : ?>
							<a class="button is-large" href="<?php echo $edit_archival_url_sip_folder; ?>">
								<?php esc_html_e('Edit', 'sip'); ?>
							</a>
							<button class="button is-large" name="accept_archival" type="submit" value="accept">
								<?php esc_html_e('Accept', 'sip'); ?>
							</button>
							<?php // todo: maybe change to a modal? js-alerts are not that fancy! ?>
							<button class="button has-text-weight-normal is-large" name="decline_archival" type="submit" value="decline" onclick="return confirm('<?php esc_html_e('Are you sure to decline? All files and data will be deleted.', 'sip'); ?>')">
								<?php esc_html_e('Decline', 'sip'); ?>
							</button>
						<?php // All other users can edit and submit their own entry as long as it has not been submitted. ?>
						<?php elseif ( 'pending' !== $archival_status && 'publish' !== $archival_status ) : ?>
							<a class="button is-large" href="<?php echo $edit_archival_url_sip_folder; ?>">
								<?php esc_html_e('Edit', 'sip'); ?>
							</a>
							<button class="button is-large" name="submit_archival" type="submit" value="submit">
								<?php esc_html_e('Submit', 'sip'); ?>
							</button>
						<?php endif; ?>

					</form>
				<?php else : ?>
					<?php // note: the option '_sip_cron_deleted_text_' uses placeholder like %s i suppose. ?>
					<p><?php echo sprintf( get_option('_sip_cron_deleted_text_' . $current_locale), carbon_get_theme_option('sip_cron_delete_days') ); ?></p>
				<?php endif; ?>
			</div>
		</footer><!-- .entry-footer -->

	</div><!-- .container -->

</article><!-- #post-<?php the_ID(); ?> -->
