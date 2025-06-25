<div class="container sip">
	<ol class="pl-0 columns is-multiline">
		<?php while ($archivals->have_posts()) : $archivals->the_post();
			global $post;
			?>
			<li class="column is-6-tablet is-4-desktop">
				<div class="card">
                    <header class="card-header">
                        <p class="card-header-title">
                            <?php the_title(); ?>
                        </p>
	                    <?php if(current_user_can('edit_others_posts')) : ?>
                            <span class="card-header-icon">
                                <?php _e($post->post_status, 'sip'); ?>
                            </span>
                        <?php endif; ?>
                    </header>
					<div class="card-content">
						<div class="content">
							<p>
								<?php
								$excerpt_length = (int) _x( '30', 'excerpt_length' );
								$excerpt_length = (int) apply_filters( 'excerpt_length', $excerpt_length );
								echo wp_trim_words($post->post_content, $excerpt_length, '&hellip;');
								?>
							</p>
						</div>
                    </div>
                    <div class="card-footer">
                        <a class="card-footer-item" href="<?php the_permalink($post);?>"><?php _e('Preview', 'sip'); ?></a>
                        <?php if($sip_folder = get_post_meta($post->ID, '_archival_sip_folder', true)) : ?>
                            <?php if((current_user_can('edit_posts') && $post->post_status != 'publish') || current_user_can('edit_others_posts')) : ?>
                                <a class="card-footer-item" href="<?php the_permalink($pages[0]); ?>?sipFolder=<?= $sip_folder; ?>"><?php _e('Edit', 'sip'); ?></a>
                            <?php endif; ?>
                            <?php if(current_user_can('edit_others_posts')) : ?>
                                <a class="card-footer-item" href="<?= plugin_dir_url(__DIR__ ); ?>create-sip.php?sipFolder=<?= $sip_folder; ?>"><?php _e('SIP', 'sip'); ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
					</div>
				</div>
			</li>
		<?php endwhile; ?>
	</ol>
    <?php
    $GLOBALS['wp_query']->max_num_pages = $archivals->max_num_pages;
    the_posts_pagination(array('class' => 'archival-pagination'));
    ?>
</div>
