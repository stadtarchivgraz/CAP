<?php
$current_locale = strtolower(get_locale());
$archival_id = get_the_ID();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('container sip'); ?>>

	<div class="entry-header">
		<?php the_title( '<h1 class="entry-title title is-2">', '</h1>' ); ?>
	</div>

	<div class="entry-content content">
		<?php
		include( dirname( __DIR__ ) . '/template-parts/content-sip-folder.php' );

		the_content();

		include( dirname( __DIR__ ) . '/template-parts/content-map.php' );

		?>
		<dl>
			<dt><?php _e('Location', 'sip'); ?></dt>
            <dd>
                <?= (get_post_meta($archival_id, '_archival_address', true))?:__('unknown', 'sip'); ?>
                <?php
                $lat = get_post_meta($archival_id, '_archival_lat', true);
                $lng = get_post_meta($archival_id, '_archival_lng', true);
                if($lat && $lng) {
                    echo ' (' . $lat . '|' . $lng . ')';
                }
                ?>
            </dd>
            <dt><?php _e('Originator', 'sip'); ?></dt>
			<dd><?= (get_post_meta($archival_id, '_archival_originator', true))?:__('unknown', 'sip'); ?></dd>
			<dt><?php _e('Date/Time', 'sip'); ?></dt>
			<dd><?= get_post_meta($archival_id, '_archival_from', true);?><?= ($archival_to = get_post_meta($archival_id, '_archival_to', true))?' &mdash; '. $archival_to:''; ?></dd>
			<dt><?php _e('Upload Purpose', 'sip'); ?></dt>
			<dd><?= get_post_meta($archival_id, '_archival_upload_purpose', true); ?></dd>
			<?php if($archival_blocking_time = get_post_meta($archival_id, '_archival_blocking_time', true)) : ?>
				<dt><?php _e('Blocking Time', 'sip'); ?></dt>
				<dd><?= $archival_blocking_time; ?></dd>
			<?php endif; ?>
			<?php if($sip_custom_meta = carbon_get_theme_option('sip_custom_meta' )) :
				foreach($sip_custom_meta as $custom_meta) :
					$meta_name = sanitize_title( $custom_meta['sip_custom_meta_key'] );
					if($meta_value = get_post_meta($archival_id, '_archival_' . $meta_name, true)) : ?>
						<dt><?= $custom_meta['sip_custom_meta_title_' . $current_locale]; ?></dt>
						<dd><?= $meta_value; ?></dd>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php
			echo strip_tags(get_the_term_list($archival_id, 'archival_tag', '<dt>' . __('Tags', 'sip') . '</dt><dd>', ' | ', '</dd>'), '<dt><dd>');
			?>
		</dl>

		<?php if(current_user_can('edit_others_posts')) : ?>
			<h3><?php _e('Archiv Information', 'sip'); ?></h3>
			<dl>
				<dt><?php _e('Numbering', 'sip'); ?></dt>
				<dd><?= get_post_meta($archival_id, '_archival_numeration', true); ?></dd>
				<dt><?php _e('Annotation', 'sip'); ?></dt>
				<dd><?= get_post_meta($archival_id, '_archival_annotation', true); ?></dd>
				<?php if($sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta' )) :
					foreach($sip_custom_archival_user_meta as $custom_archival_user_meta) :
						$meta_name = sanitize_title( $custom_archival_user_meta['sip_custom_archival_user_meta_key'] );
						if($meta_value = get_post_meta($archival_id, '_archival_' . $meta_name, true)) : ?>
							<dt><?= $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale]; ?></dt>
							<dd><?= $meta_value; ?></dd>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</dl>
		<?php endif; ?>
	</div><!-- .entry-content -->

	<footer class="entry-footer default-max-width">
		<div class="archival-actions">
			<?php
			$pages = get_pages(array(
				'meta_key' => '_wp_page_template',
				'meta_value' => 'sip-upload.php',
				'hierarchical' => 0
			));
			if($archival_sip_folder = get_post_meta($archival_id, '_archival_sip_folder', true)) :
				$archival_status = get_post_status($archival_id);
				?>
				<?php if(current_user_can('edit_others_posts') && $archival_status == 'publish') : ?>
					<a class="button is-large" href="<?php the_permalink($pages[0]); ?>?sipFolder=<?= $archival_sip_folder; ?>"><?php _e('Edit', 'sip'); ?></a>
					<a class="button is-large" href="<?= plugin_dir_url(__DIR__ ); ?>create-sip.php?sipFolder=<?= $archival_sip_folder; ?>"><?php _e('SIP', 'sip'); ?></a>
					<a class="button is-large" href="<?= plugin_dir_url(__DIR__ ); ?>create-sip-pdf.php?sipFolder=<?= $archival_sip_folder; ?>"><?php _e('PDF', 'sip'); ?></a>
				<?php elseif(current_user_can('edit_others_posts') && $archival_status != 'publish') : ?>
					<a class="button is-large" href="<?php the_permalink($pages[0]); ?>?sipFolder=<?= $archival_sip_folder; ?>"><?php _e('Edit', 'sip'); ?></a>
					<a class="button is-large" href="<?php the_permalink($pages[0]); ?>?sipFolder=<?= $archival_sip_folder; ?>&accept=1"><?php _e('Accept', 'sip'); ?></a>
					<a class="button is-large" href="<?php the_permalink($pages[0]); ?>?sipFolder=<?= $archival_sip_folder; ?>&decline=1" onclick="return confirm('<?php _e('Are you sure to decline? All files and data will be deleted.', 'sip'); ?>')"><?php _e('Decline', 'sip'); ?></a>
				<?php elseif(current_user_can('edit_posts') && $archival_status != 'publish') : ?>
					<a class="button is-large" href="<?php the_permalink($pages[0]); ?>?sipFolder=<?= $archival_sip_folder; ?>"><?php _e('Edit', 'sip'); ?></a>
					<a class="button is-large" href="<?php the_permalink($pages[0]); ?>?sipFolder=<?= $archival_sip_folder; ?>&submit=1"><?php _e('Submit', 'sip'); ?></a>
				<?php endif; ?>
			<?php else: ?>
				<p><?= sprintf(get_option('_sip_cron_deleted_text_' . $current_locale), carbon_get_theme_option( 'sip_cron_delete_days' )); ?></p>
			<?php endif; ?>
		</div>
	</footer><!-- .entry-footer -->

</article><!-- #post-<?php the_ID(); ?> -->
