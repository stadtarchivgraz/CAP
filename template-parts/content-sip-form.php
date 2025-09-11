<?php
/**
 * Template part containing the form for uploading the SIP.
 * Enqueued in page-template/sip-upload.php
 */

$user           = wp_get_current_user();
$archival       = false; //get_post();
$archival_from  = false; // start date/time period
$archival_to    = false; // end date/time period
$current_locale = strtolower(get_locale());
$sip_folder     = ( isset( $_GET['sipFolder'] ) && $_GET['sipFolder'] ) ? sanitize_text_field( $_GET['sipFolder'] ) : false;


// if viewing an existing archival.
if ( $sip_folder ) {
	$archival_id = starg_get_archival_id_by_sip_folder( $sip_folder );
	if ($archival_id) {
		$archival            = get_post($archival_id);
		$archival_from       = esc_attr( get_post_meta( $archival_id, '_archival_from', true ) );
		$archival_to         = esc_attr( get_post_meta( $archival_id, '_archival_to', true ) );
		$archival_originator = esc_attr( get_post_meta( $archival_id, '_archival_originator', true ) );
	}
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('sip'); ?>>

	<div class="container">
		<?php // todo: if we display the title within elementor (or any other pagebuilder) we don't need it in this template. ?>
		<!-- <header class="entry-header mb-6"> -->
			<?php // the_title('<h2 class="entry-title title">', '</h2>'); ?>
		<!--</header>--><!-- .entry-header -->

		<div class="entry-content content">
			<?php
			// Display the uploaded items.
			include(STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-sip-folder.php');
			if ($exif_dates) {
				$exif_dates_min = min($exif_dates);
				$exif_dates_max = max($exif_dates);
				$period_days    = ceil(($exif_dates_max - $exif_dates_min) / 86400);
				if (! $archival_from) {
					$archival_from = date('Y-m-d\TH:i', $exif_dates_min);
				}
				if (! $archival_to && $period_days > 365) {
					$archival_to = date('Y.m.d H:i:s', $exif_dates_max);
				}
			}
			?>
			<form action="" method="post"><?php // todo: use values from Sip_Upload_Form_Validation. Maybe create it as a service! ?>
				<input type="hidden" name="starg_form_name" value="upload_sip_form" aria-hidden="true" />
				<input type="hidden" name="starg_form_post_id" value="<?php the_ID(); ?>" aria-hidden="true" />
				<?php wp_nonce_field( 'starg_upload_archival_nonce_action', 'starg_upload_archival_nonce', false ); ?>

				<div class="field">
					<label for="archival-title" class="label is-large"><?php esc_html_e('Title', 'sip'); ?>*</label>
					<p class="control is-large">
						<input id="archival-title" name="archival_title" class="input is-large count-character" type="text" placeholder="<?php esc_html_e('Give your submission a descriptive title', 'sip'); ?>" value="<?php echo ($archival) ? $archival->post_title : ''; ?>" maxlength="100" required>
					</p>
					<p id="archival-title_count" class="help"><span><?php echo ($archival) ? strlen($archival->post_title) : 0; ?></span> | <?php esc_html_e('Maximum 100 characters.', 'sip'); ?></p>
				</div>
				<div class="field">
					<label for="archival-originator" class="label"><?php esc_html_e('Originator', 'sip'); ?>*</label>
					<p class="control">
						<input id="archival-originator" name="archival_originator" class="input" type="text" value="<?php echo ($archival) ? $archival_originator : $user->display_name; ?>" required>
					</p>
					<p class="help"><?php esc_html_e('If you are not the originator (creator) of the uploaded file, please enter the name of the originator here', 'sip'); ?></p>
				</div>
				<div class="field">
					<label for="archival-description" class="label"><?php esc_html_e('Description', 'sip'); ?>*</label>
					<p class="control">
						<textarea id="archival-description" name="archival_description" class="textarea count-character" rows="10" maxlength="5000" placeholder="<?php esc_html_e('You can describe your file in detail here (e.g.: Why is it important for the archive? What does the file show? In what context was the file created? Is there any additional information?)', 'sip'); ?>" required><?php echo ($archival) ? wp_kses_post( $archival->post_content ) : ''; ?></textarea>
					</p>
					<p id="archival-description_count" class="help"><span><?php echo ($archival) ? strlen($archival->post_content) : 0; ?></span> | <?php esc_html_e('Maximum 5000 characters.', 'sip'); ?></p>
				</div>
				<?php // todo: if we select a single date, we should hide the longer period inputs and vice versa! ?>
				<div class="field">
					<label for="archival-single-date" class="label"><?php esc_html_e('Date/time (for a precise time)', 'sip'); ?></label>
					<p class="control">
						<input id="archival-single-date" name="archival_single_date" type="datetime-local" value="<?php echo ($archival_from && !$archival_to) ? $archival_from : ''; ?>">
					</p>
				</div>
				<div class="field">
					<label class="label"><?php esc_html_e('Time period (for a longer period)', 'sip'); ?></label>
					<div class="field">
						<label class="checkbox">
							<input type="checkbox" name="archival_hide_timeline" onclick="toggleField('date-range-control');">
							<?php esc_html_e('Hide timeline', 'sip'); ?>
						</label>
					</div>
					<div id="date-range-control" class="control date-range-control">
						<div id="date-range"></div>
					</div>
					<div class="columns">
						<div class="column">
							<p class="control">
								<input id="archival-date-range-start" name="archival_date_range[]" type="number" max="<?php echo date('Y'); ?>" min="1850" step="1" maxlength="4">
							</p>
						</div>
						<div class="column">
							<p class="control">
								<input id="archival-date-range-end" name="archival_date_range[]" type="number" max="<?php echo date('Y'); ?>" min="1850" step="1" maxlength="4">
							</p>
						</div>
					</div>
				</div>
				<div class="field">
					<?php
					$display_map = true;
					if ( $archival ) {
						$map_lat     = esc_attr( get_post_meta( $archival->ID, '_archival_lat', true ) );
						$map_lng     = esc_attr( get_post_meta( $archival->ID, '_archival_lng', true ) );
						$map_area    = get_post_meta( $archival->ID, '_archival_area', true ); // todo: maybe escape it!
						$address     = esc_attr( get_post_meta( $archival->ID, '_archival_address', true ) );
						$display_map = ( $address && ( ! $map_lat && ! $map_area ) ) ? false : true;
					}
					?>
					<label for="archival-map" class="label"><?php esc_html_e('Location', 'sip'); ?></label>
					<label class="checkbox">
						<input type="checkbox" name="archival_hide_map" onclick="toggleField('archival-map','archival-address-wrap');" <?php checked( ! $display_map ); ?>>
						<?php esc_html_e('Hide map', 'sip'); ?>
					</label>
					<div id="archival-map" <?php echo ( $display_map ) ? '': 'style="display:none;"'; ?>>
						<p><?php esc_html_e('Map (select an exact location or area)', 'sip'); ?></p>
						<?php include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-map.php' ); ?>
					</div>
					<div id="archival-address-wrap" <?php echo ( $display_map ) ? 'style="display:none;"': ''; ?>>
						<label for="archival-address" class="label"><?php esc_html_e('Address', 'sip'); ?></label>
						<input id="archival-address" name="archival_address" type="text" class="input" value="<?php echo ( $archival ) ? $address : ''; ?>">
					</div>
					<input id="archival-lat" name="archival_lat" type="hidden" value="<?php echo ( $archival )   ? $map_lat  : ''; ?>">
					<input id="archival-lng" name="archival_lng" type="hidden" value="<?php echo ( $archival )   ? $map_lng  : ''; ?>">
					<input id="archival-area" name="archival_area" type="hidden" value="<?php echo ( $archival ) ? $map_area : ''; ?>">
				</div>
				<div class="field">
					<label for="archival-tags" class="label"><?php esc_html_e('Tags', 'sip'); ?>*</label>
					<p class="control">
						<textarea id="archival-tags" name="archival_tags" class="textarea" rows="2" maxlength="10" required></textarea>
					</p>
					<p class="help"><?php esc_html_e('Minimum 1 | Maximum 10', 'sip'); ?></p>
				</div>
				<div class="columns">
					<div class="column">
						<div class="field">
							<label for="archival-upload-purpose" class="label"><?php esc_html_e('Upload purpose', 'sip'); ?></label>
							<p class="control">
								<select id="archival-upload-purpose" name="archival_upload_purpose" required>
									<?php
									$upload_purpose_options = explode("\r\n", carbon_get_theme_option('sip_upload_purpose_options_' . $current_locale));
									$archival_upload_purpose = ($archival) ? esc_attr(get_post_meta($archival->ID, '_archival_upload_purpose', true)) : '';
									foreach ($upload_purpose_options as $upload_purpose_option) :
										?>
										<option value="<?php echo esc_attr($upload_purpose_option); ?>" <?php echo ($archival_upload_purpose == $upload_purpose_option) ? ' selected' : ''; ?>>
											<?php echo esc_attr($upload_purpose_option); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
						</div>
					</div>
					<div id="blocking-time" class="column">
						<div class="field">
							<label for="archival-blocking-time" class="label"><?php esc_html_e('Blocking time', 'sip'); ?></label>
							<p class="control">
								<select id="archival-blocking-time" name="archival_blocking_time" required>
									<?php
									$blocking_time_options = explode("\r\n", carbon_get_theme_option('sip_blocking_time_options_' . $current_locale));
									$sip_blocking_time_calculate = esc_attr(carbon_get_theme_option('sip_blocking_time_calculate_' . $current_locale));
									$archival_blocking_time = ($archival) ? esc_attr(get_post_meta($archival->ID, '_archival_blocking_time', true)) : '';
									foreach ($blocking_time_options as $blocking_time_option) :
										if ($blocking_time_option == $sip_blocking_time_calculate) {
											$user_birthday = get_user_meta($user->ID, 'user_birthday', true);
											$option_number = $int_var = (int)filter_var($blocking_time_option, FILTER_SANITIZE_NUMBER_INT);
											$blocking_time_option .= ' (' . $option_number - (date('Y', time()) - date('Y', strtotime($user_birthday))) .    ')';
										}
										?>
										<option value="<?php echo esc_attr($blocking_time_option); ?>" <?php echo ($archival_blocking_time == $blocking_time_option) ? ' selected' : ''; ?>>
											<?php echo esc_attr($blocking_time_option); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
						</div>
					</div>
				</div>
				<?php
				$sip_custom_meta = carbon_get_theme_option('sip_custom_meta');
				foreach ($sip_custom_meta as $custom_meta) :
					$meta_name = sanitize_title($custom_meta['sip_custom_meta_key']);
					$meta_type = sanitize_text_field($custom_meta['sip_custom_meta_type']);
					?>
					<div class="field">
						<label for="<?php echo $meta_name; ?>" class="label"><?php echo esc_html($custom_meta['sip_custom_meta_title_' . $current_locale]); ?></label>
						<p class="control">
							<?php if ($meta_type == 'textarea') : ?>
								<textarea id="<?php echo $meta_name; ?>" name="<?php echo '_archival_' . $meta_name; ?>" class="textarea"><?php echo ($archival) ? wp_kses_post(get_post_meta($archival->ID, '_archival_' . $meta_name, true)) : ''; ?></textarea>
							<?php else : ?>
								<input id="<?php echo $meta_name; ?>" name="<?php echo '_archival_' . $meta_name; ?>" class="input" type="text" value="<?php echo ($archival) ? esc_attr(get_post_meta($archival->ID, '_archival_' . $meta_name, true)) : ''; ?>">
							<?php endif; ?>
						</p>
					</div>
				<?php endforeach; ?>

				<?php $archival_right_transfer = ($archival) ? esc_attr(get_post_meta($archival->ID, '_archival_right_transfer', true)) : ''; ?>
				<div class="field">
					<label class="checkbox">
						<input type="checkbox" name="archival_right_transfer" value="yes" required<?php echo ($archival_right_transfer) ? ' checked' : ''; ?>>
						<?php // todo: maybe add some default text? ?>
						<?php echo wp_kses_post(get_option('_sip_right_transfer_text_' . $current_locale)); ?>
					</label>
				</div>

				<?php if (current_user_can('edit_others_posts')) : ?>
					<h3><?php esc_html_e('Archive information', 'sip'); ?></h3>

					<div class="field">
						<label for="archival-numeration" class="label"><?php esc_html_e('Numbering', 'sip'); ?></label>
						<p class="control">
							<input id="archival-numeration" name="archival_numeration" class="input" type="text" value="<?php echo ($archival) ? esc_attr(get_post_meta($archival->ID, '_archival_numeration', true)) : ''; ?>">
						</p>
					</div>
					<div class="field">
						<label for="archival-annotation" class="label"><?php esc_html_e('Note', 'sip'); ?></label>
						<p class="control">
							<textarea id="archival-annotation" name="archival_annotation" class="textarea count-character" rows="10" maxlength="5000"><?php echo ($archival) ? wp_kses_post(get_post_meta($archival->ID, '_archival_annotation', true)) : ''; ?></textarea>
						</p>
					</div>
					<?php
					$sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta');
					foreach ($sip_custom_archival_user_meta as $custom_archival_user_meta) :
						$meta_name = sanitize_title($custom_archival_user_meta['sip_custom_archival_user_meta_key']);
						$meta_type = sanitize_title($custom_archival_user_meta['sip_custom_archival_user_meta_type']);
						?>
						<div class="field">
							<label for="<?php echo $meta_name; ?>" class="label"><?php echo esc_html($custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale]); ?></label>
							<p class="control">
								<?php if ($meta_type == 'textarea') : ?>
									<textarea id="<?php echo $meta_name; ?>" name="<?php echo '_archival_' . $meta_name; ?>" class="textarea"><?php echo ($archival) ? wp_kses_post(get_post_meta($archival->ID, '_archival_' . $meta_name, true)) : ''; ?></textarea>
								<?php else : ?>
									<input id="<?php echo $meta_name; ?>" name="<?php echo '_archival_' . $meta_name; ?>" class="input" type="text" value="<?php echo ($archival) ? esc_attr(get_post_meta($archival->ID, '_archival_' . $meta_name, true)) : ''; ?>">
								<?php endif; ?>
							</p>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<div class="field">
					<p class="control">
						<?php if ($archival) : ?>
							<input type="hidden" name="archival_ID" value="<?php echo $archival->ID; ?>">
						<?php endif; ?>
						<button class="button is-large" name="save_sip" type="submit" value="save_draft">
							<?php esc_html_e('Save as draft', 'sip'); ?>
						</button>
						<button class="button is-large" name="save_sip" type="submit" value="submit_archival">
							<?php esc_html_e('Submit', 'sip'); ?>
						</button>
					</p>
				</div>

				<?php
				$archival_tags = get_terms(array('taxonomy'   => 'archival_tag', 'hide_empty' => false));
				if ( is_wp_error( $archival_tags ) ) {
					$archival_tags_list_names = array();
					$archival_tags_names      = array();
				} else {
					$archival_tags_names = array_map('esc_attr', wp_list_pluck($archival_tags, 'name'));

					$archival_tags_list_names = '';
					if ($archival) {
						$archival_tags_list = get_the_terms($archival->ID, 'archival_tag');
						$archival_tags_list_names = array_map('esc_attr', wp_list_pluck($archival_tags_list, 'name'));
					}
				}

				$blocking_time_upload_purpose = esc_attr(carbon_get_theme_option('sip_blocking_time_upload_purpose_' . $current_locale));
				?>

				<script>
					document.addEventListener('DOMContentLoaded', () => {
						let range = document.getElementById('date-range'),
							input0 = document.getElementById('archival-date-range-start'),
							input1 = document.getElementById('archival-date-range-end'),
							inputs = [input0, input1];

						noUiSlider.create(range, {
							start: [<?php echo ($archival_from && $archival_to) ? substr($archival_from, 0, 4) : 0; ?>, <?php echo ($archival_from && $archival_to) ? substr($archival_to, 0, 4) : 0; ?>],
							range: {
								'min': [1850],
								'max': [<?php echo date('Y'); ?>]
							},
							step: 1,
							format: {
								to: function(value) {
									return value;
								},
								from: function(value) {
									return Number(value);
								}
							},
							pips: {
								mode: 'count',
								values: 10
							},
							connect: true,
						})
						range.noUiSlider.on('update', function(values, handle) {
							inputs[handle].value = values[handle];
						});
						input0.addEventListener('change', function() {
							range.noUiSlider.set([this.value, null]);
						});
						input1.addEventListener('change', function() {
							range.noUiSlider.set([null, this.value]);
						});


						const textCountElement = document.querySelectorAll(".count-character");

						textCountElement.forEach((item) => {
							item.addEventListener('keyup', onKeyupCountText);
						});

						function onKeyupCountText(e) {
							document.querySelector('#' + e.target.id + '_count span').textContent = e.target.value.length;
						}

						let uploadPurpose = document.querySelector('#archival-upload-purpose'),
							blockingTime = document.querySelector('#archival-blocking-time'),
							blocking = document.querySelector('#blocking-time');

						uploadPurpose.addEventListener('change', onChangeUploadPurpose);

						function onChangeUploadPurpose(e) {
							blockingTime.required = false;
							blocking.style.display = 'none';

							if ('<?php echo $blocking_time_upload_purpose; ?>'.search(e.target.value) !== -1) {
								blockingTime.required = true;
								blocking.style.display = 'block';
							}
						}

						let inputElm = document.querySelector('#archival-tags');
						let tagify = new Tagify(inputElm, {
							whitelist: <?php echo json_encode($archival_tags_names); ?>,
							dropdown: {
								classname: "suggested-tags",
								enabled: 0, // show the dropdown immediately on focus
								maxItems: 5,
								position: "text", // place the dropdown near the typed text
								closeOnSelect: false, // keep the dropdown open after selecting a suggestion
								highlightFirst: true
							}
						});

						tagify.addTags(<?php echo json_encode($archival_tags_list_names); ?>);

						inputElm.addEventListener('change', onChangeTagify);

					});

					function onChangeTagify(e) {
						// outputs a String
						if (e.target.tagifyValue) {
							let tags = JSON.parse(e.target.tagifyValue);
							if (tags.length > e.target.attributes.maxlength.value) {
								tagify.removeTag();
							}
						}
					}

					/**
					 * changes the visibility of one or more fields.
					 * @param string fields the id of an HTML element.
					 * @return void
					 */
					function toggleField(...fields) {
						if ( ! fields ) { return; }

						fields.forEach((fieldId) => {
							const el = document.getElementById(fieldId);
							if ( ! el ) { return; }

							if (el.tagName.toLowerCase() === 'input' && el.hasAttribute('type')) {
								const currentType = el.getAttribute('type');
								if (currentType === 'hidden') {
									el.setAttribute('type', 'text');
								} else if (currentType === 'text') {
									el.setAttribute('type', 'hidden');
								}
							} else {
								const currentDisplay = window.getComputedStyle(el).display;
								el.style.display = (currentDisplay === 'none') ? 'block' : 'none';
							}

							// recalculate the size of the map.
							if ( 'archival-map' === fieldId ) {
								setTimeout(function() {
									window.map.invalidateSize();
								}, 100);
							}
						});
					}
				</script>
			</form>
		</div><!-- .entry-content -->
	</div><!-- .container -->

</article><!-- #post-<?php the_ID(); ?> -->
