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

		<?php // todo: if we display the title within elementor (or any other pagebuilder) we don't need it in this template. ?>
		<!-- <div class="entry-header"> -->
			<?php //the_title('<h1 class="entry-title title is-2">', '</h1>'); ?>
		<!-- </div> -->

		<div class="entry-content content">
			<?php
			// display the content of the SIP content folder.
			include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-sip-folder.php' );

			the_content();

			// only include the map if we have data to display.
			$map_lat     = esc_attr( get_post_meta( $archival_id, '_archival_lat', true ) );
			$map_lng     = esc_attr( get_post_meta( $archival_id, '_archival_lng', true ) );
			$map_area    = get_post_meta( $archival_id, '_archival_area', true ); // todo: maybe escape it!
			$address     = esc_attr( get_post_meta( $archival_id, '_archival_address', true ) );
			$display_map = ( $address && ( ! $map_lat && ! $map_area ) ) ? false : true;
			if ( $display_map ) {
				// displays the map with marker where the files were found.
				include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-map.php' );
			}

			$originator     = esc_html( get_post_meta($archival_id, '_archival_originator', true) );
			$date_from      = esc_html( get_post_meta($archival_id, '_archival_from', true) );
			$date_to        = esc_html( get_post_meta($archival_id, '_archival_to', true) );
			$upload_purpose = esc_html( get_post_meta($archival_id, '_archival_upload_purpose', true) );
			$blocking_time  = esc_html( get_post_meta($archival_id, '_archival_blocking_time', true ))
			?>

			<dl>
				<dt><?php esc_html_e('Location', 'sip'); ?></dt>
				<dd>
					<?php echo ( $address ) ? esc_html( $address ) : esc_html__('unknown', 'sip'); ?>
					<?php
					if ($map_lat && $map_lng) {
						echo ' (' . $map_lat . '|' . $map_lng . ')';
					}
					?>
				</dd>
				<dt><?php esc_html_e('Originator', 'sip'); ?></dt>
				<dd><?php echo $originator ?: esc_html__('unknown', 'sip'); ?></dd>
				<dt><?php esc_html_e('Date/Time', 'sip'); ?></dt>
				<dd><?php echo $date_from; ?><?php echo ( $date_to ) ? ' &mdash; ' . $date_to : ''; ?></dd>
				<dt><?php esc_html_e('Upload Purpose', 'sip'); ?></dt>
				<dd><?php echo $upload_purpose; ?></dd>
				<?php if ( $blocking_time ) : ?>
					<dt><?php esc_html_e('Blocking Time', 'sip'); ?></dt>
					<dd><?php echo esc_html( $blocking_time ); ?></dd>
				<?php endif; ?>
				<?php if ($sip_custom_meta = carbon_get_theme_option('sip_custom_meta')) :
					foreach ($sip_custom_meta as $custom_meta) :
						$meta_name = sanitize_title($custom_meta['sip_custom_meta_key']);
						if ($meta_value = get_post_meta($archival_id, '_archival_' . $meta_name, true)) : ?>
							<dt><?php echo esc_html( $custom_meta['sip_custom_meta_title_' . $current_locale] ); ?></dt>
							<dd><?php echo esc_html( $meta_value ); ?></dd>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php
				echo strip_tags(get_the_term_list($archival_id, 'archival_tag', '<dt>' . esc_html__('Tags', 'sip') . '</dt><dd>', ' | ', '</dd>'), '<dt><dd>');
				?>
			</dl>

			<?php if (current_user_can('edit_others_archival_records')) : ?>
				<h3><?php esc_html_e('Archive Information', 'sip'); ?></h3>
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
				$archival_status     = get_post_status( $archival_id );
				$archival_sip_folder = esc_attr( get_post_meta( $archival_id, '_archival_sip_folder', true ) );
				if ( $archival_sip_folder && 'trash' !== $archival_status ) :
					$edit_archival_url            = starg_get_the_edit_archival_page_url();
					$edit_archival_url_sip_folder = add_query_arg( array( 'sipFolder' => $archival_sip_folder, ), $edit_archival_url );
					?>

					<form target="" method="post">
						<input type="hidden" name="<?php echo $sip_archival_actions->form_name_key; ?>" value="<?php echo $sip_archival_actions->form_name; ?>" aria-hidden="true" />
						<input type="hidden" name="starg_form_post_id" value="<?php the_ID(); ?>" aria-hidden="true" />
						<input type="hidden" name="sipFolder" value="<?php echo $archival_sip_folder; ?>" aria-hidden="true" />
						<?php wp_nonce_field( $sip_archival_actions->nonce_action, $sip_archival_actions->nonce_key, false ); ?>

						<?php // Admins/Editors can edit, create the SIP and create a PDF of the site if the users entry has been accepted! ?>
						<?php if ( current_user_can( 'edit_others_archival_records' ) && 'publish' === $archival_status ) : ?>
							<?php $create_sip_url     = ( isset( $create_sip->url_endpoint ) )     ? add_query_arg( array( $create_sip->url_endpoint => true, 'sipFolder' => $archival_sip_folder, ) )     : '#'; ?>
							<?php $create_sip_pdf_url = ( isset( $create_sip_pdf->url_endpoint ) ) ? add_query_arg( array( $create_sip_pdf->url_endpoint => true, 'sipFolder' => $archival_sip_folder, ) ) : '#'; ?>
							<div class="mb-4">
								<a class="button" href="<?php echo $edit_archival_url_sip_folder; ?>" target="_blank">
									<?php esc_html_e('Edit', 'sip'); ?>
								</a>
							</div>
							<a class="button" href="<?php echo $create_sip_url; ?>">
								<?php esc_html_e('SIP', 'sip'); ?>
							</a>
							<a class="button" href="<?php echo $create_sip_pdf_url; ?>">
								<?php esc_html_e('PDF', 'sip'); ?>
							</a>
						<?php // Admins/Editors can edit, accept or decline the users entry. ?>
						<?php elseif ( current_user_can( 'edit_others_archival_records' ) && 'publish' !== $archival_status ) : ?>
							<div class="mb-4">
								<a class="button" href="<?php echo $edit_archival_url_sip_folder; ?>">
									<?php esc_html_e('Edit', 'sip'); ?>
								</a>
							</div>

							<div class="mb-4">
								<button class="button is-success is-light is-outlined" name="accept_archival" type="submit" value="accept">
									<?php esc_html_e('Accept', 'sip'); ?>
								</button>
							</div>

							<div>
								<?php $sip_archival_actions->archival_decline_button( $archival_sip_folder, get_the_title( $archival_id ) ); ?>

								<?php $sip_archival_actions->archival_decline_button_with_response_form( 'response_' . $archival_sip_folder, get_the_title( $archival_id ) ); ?>
							</div>
						<?php // All other users can edit and submit their own entry as long as it has not been submitted. ?>
						<?php elseif ( 'pending' !== $archival_status && 'publish' !== $archival_status ) : ?>
							<div class="mb-4">
								<a class="button" href="<?php echo $edit_archival_url_sip_folder; ?>">
									<?php esc_html_e('Edit', 'sip'); ?>
								</a>
							</div>
							<div>
								<button class="button is-success is-light is-outlined" name="submit_archival" type="submit" value="submit">
									<?php esc_html_e('Submit', 'sip'); ?>
								</button>
							</div>
						<?php endif; ?>
					</form>

				<?php elseif ( 'trash' === $archival_status ) : ?>
					<?php echo starg_get_notification_message( esc_html__( 'This post has been deleted and is in the trash.', 'sip' ), 'is-warning is-light' ); ?>

				<?php else : ?>
					<?php // note: the option '_sip_cron_deleted_text_' uses placeholder like %s. ?>
					<p><?php echo wp_kses_post( sprintf( get_option('_sip_cron_deleted_text_' . $current_locale), carbon_get_theme_option('sip_cron_delete_days') ) ); ?></p>
				<?php endif; ?>

			</div>
		</footer><!-- .entry-footer -->

	</div><!-- .container -->

</article><!-- #post-<?php the_ID(); ?> -->
