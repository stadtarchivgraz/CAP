<?php
/**
 * Template part containing the form for uploading the SIP.
 * Enqueued in page-template/sip-upload.php
 */

$sip_upload_form = apply_filters('starg/sip_upload_form', null);
if ( ! $sip_upload_form instanceof Sip_Upload_Form_Validation ) {
	$logging = apply_filters( 'starg/logging', null );
	if ( $logging instanceof Starg_Logging ) {
		$logging->create_log_entry( esc_attr__( 'Class Sip_Upload_Form_Validation not initialized!', 'sip' ) );
	}
	return;
}

$user           = wp_get_current_user();
$current_locale = strtolower(get_locale());
$sip_folder     = $sip_upload_form->get_sip_folder_id();
$archival_id    = $sip_upload_form->get_archival_id();
$archival       = false;
if ( $archival_id ) {
	$archival = get_post( $archival_id );
}

$archival_title       = $sip_upload_form->get_form_value( 'archival_title' );
$archival_originator  = $sip_upload_form->get_form_value( 'archival_originator' ) ?: $user->display_name;
$archival_description = $sip_upload_form->get_form_value( 'archival_description' );
$archival_from        = $sip_upload_form->get_form_value( 'archival_single_date' );
$archival_to          = $sip_upload_form->get_form_value( 'archival_to' );
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

			if ( isset( Render_Sip_Content_folder::$exif_dates ) && Render_Sip_Content_folder::$exif_dates ) {
				$exif_dates_min = (int) min(Render_Sip_Content_folder::$exif_dates);
				$exif_dates_max = (int) max(Render_Sip_Content_folder::$exif_dates);
				$period_days    = ceil(($exif_dates_max - $exif_dates_min) / 86400);
				if (! $archival_from) {
					$archival_from = date('Y-m-d\TH:i', $exif_dates_min);
				}
				if (! $archival_to && $period_days > 365) {
					$archival_to = date('Y.m.d H:i:s', $exif_dates_max);
				}
			}
			?>
			<form action="" method="post" class="mt-4">
				<input type="hidden" name="<?php echo $sip_upload_form->form_name_key; ?>" value="<?php echo $sip_upload_form->form_name; ?>" aria-hidden="true" />
				<input type="hidden" name="starg_form_post_id" value="<?php the_ID(); ?>" aria-hidden="true" />
				<?php wp_nonce_field( $sip_upload_form->nonce_action, $sip_upload_form->nonce_key, false ); ?>

				<div class="columns">
					<div class="column is-half">
						<div class="field">
							<label for="archival-title" class="label"><?php esc_html_e('Title', 'sip'); ?>*</label>
							<p class="control">
								<input id="archival-title" name="archival_title" class="input count-character" type="text" placeholder="<?php esc_html_e('Give your submission a descriptive title', 'sip'); ?>" value="<?php echo esc_html( $archival_title ); ?>" maxlength="100" required aria-describedby="archival-title-help-text">
							</p>
							<p id="archival-title-help-text" class="help"><?php esc_html_e( 'Enter a meaningful title for the post. The title is used for identification and is displayed in listings and on the post detail page.', 'sip' ); ?></p>
							<p id="archival-title_count" class="help"><span><?php echo strlen($archival_title); ?></span> | <?php esc_html_e('Maximum 100 characters.', 'sip'); ?></p>
						</div>
					</div>
					<div class="column is-half">
						<div class="field">
							<label for="archival-originator" class="label"><?php esc_html_e('Originator', 'sip'); ?>*</label>
							<p class="control">
								<input id="archival-originator" name="archival_originator" class="input" type="text" value="<?php echo esc_html( $archival_originator ); ?>" required aria-describedby="archival-originator-help-text">
							</p>
							<p id="archival-originator-help-text" class="help"><?php esc_html_e('If you are not the originator (creator) of the uploaded file, please enter the name of the originator here. This may be a person, institution, or organization and is used for proper attribution of rights.', 'sip'); ?></p>
						</div>
					</div>
				</div>
				
				<div class="field">
					<label for="archival-description" class="label"><?php esc_html_e('Description', 'sip'); ?>*</label>
					<p class="control">
						<textarea id="archival-description" name="archival_description" class="textarea count-character" rows="5" maxlength="5000" placeholder="<?php esc_html_e('You can describe your file in detail here (e.g.: Why is it important for the archive? What does the file show? In what context was the file created? Is there any additional information?)', 'sip'); ?>" required aria-describedby="archival-description-help-text"><?php echo wp_kses_post( $archival_description ); ?></textarea>
					</p>
					<p id="archival-description-help-text" class="help"><?php esc_html_e('Provide a detailed description of the post or the uploaded files. The description may include context, background information, or additional details and helps improve understanding and discoverability.', 'sip'); ?></p>
					<p id="archival-description_count" class="help"><span><?php echo strlen($archival_description); ?></span> | <?php esc_html_e('Maximum 5000 characters.', 'sip'); ?></p>
				</div>

				<?php // Tags ?>
				<div class="field">
					<label for="archival-tags" class="label"><?php esc_html_e('Tags', 'sip'); ?>*</label>
					<p class="control">
						<textarea id="archival-tags" name="archival_tags" class="textarea" maxlength="10" required></textarea>
					</p>
					<p id="archival-tags-help-text" class="help"><?php esc_html_e('Enter tags or keywords to categorize the post or uploaded files. Multiple keywords may be added to improve searchability and filtering.', 'sip'); ?></p>
					<p class="help"><?php esc_html_e('Minimum 1 | Maximum 10', 'sip'); ?></p>
				</div>

				<?php // Date/Time ?>
				<?php // todo: if we select a single date, we should hide the longer period inputs and vice versa! ?>
				<div class="field">
					<label for="archival-single-date" class="label"><?php esc_html_e('Date/time (for a precise time)', 'sip'); ?></label>
					<p class="control">
						<input id="archival-single-date" name="archival_single_date" type="datetime-local" value="<?php echo ($archival_from && !$archival_to) ? $archival_from : ''; ?>" aria-describedby="archival-single-date-help-text">
					</p>
					<p id="archival-single-date-help-text" class="help"><?php esc_html_e( 'Enter the date and, if applicable, the time when the uploaded files were created. This refers to the creation time of the content, not the upload date.', 'sip' ); ?></p>
				</div>
				<div class="field">
					<span class="label"><?php esc_html_e('Time period (for a longer period)', 'sip'); ?></span>
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
								<input id="archival-date-range-start" name="archival_date_range[]" type="number" max="<?php echo date('Y'); ?>" min="1850" step="1" maxlength="4" aria-describedby="archival-date-range-start-help-text">
							</p>
							<p id="archival-date-range-start-help-text" class="help"><?php esc_html_e( 'Enter the start period when the uploaded files might have been created. This refers to the creation time of the content, not the upload date.', 'sip' ); ?></p>
						</div>
						<div class="column">
							<p class="control">
								<input id="archival-date-range-end" name="archival_date_range[]" type="number" max="<?php echo date('Y'); ?>" min="1850" step="1" maxlength="4"aria-describedby="archival-date-range-end-help-text">
							</p>
							<p id="archival-date-range-end-help-text" class="help"><?php esc_html_e( 'Enter the end period when the uploaded files might have been created. This refers to the creation time of the content, not the upload date.', 'sip' ); ?></p>
						</div>
					</div>
				</div>

				<?php // Map ?>
				<div class="field">
					<?php
					$map_lat     = esc_attr( $sip_upload_form->get_form_value( 'archival_lat' ) );
					$map_lng     = esc_attr( $sip_upload_form->get_form_value( 'archival_lng' ) );
					$map_area    = esc_attr( $sip_upload_form->get_form_value( 'archival_area' ) );
					$address     = esc_attr( $sip_upload_form->get_form_value( 'archival_address' ) );
					$display_map = ( $address && ( ! $map_lat && ! $map_area ) ) ? false : true;
					?>
					<p class="label"><?php esc_html_e('Location', 'sip'); ?></p>
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
						<input id="archival-address" name="archival_address" type="text" class="input" value="<?php echo $address; ?>">
					</div>
					<input id="archival-lat" name="archival_lat" type="hidden" value="<?php echo $map_lat; ?>" aria-hidden="true">
					<input id="archival-lng" name="archival_lng" type="hidden" value="<?php echo $map_lng; ?>" aria-hidden="true">
					<input id="archival-area" name="archival_area" type="hidden" value="<?php echo $map_area; ?>" aria-hidden="true">
				</div>

				<?php // Upload purpose/Blocking time ?>
				<div class="columns">
					<div class="column">
						<div class="field">
							<label for="archival-upload-purpose" class="label"><?php esc_html_e('Upload purpose', 'sip'); ?>*</label>
							<p class="control">
								<select id="archival-upload-purpose" name="archival_upload_purpose" required aria-describedby="archival-upload-purpose-help-text">
									<?php
									$upload_purpose_options  = explode("\r\n", carbon_get_theme_option('sip_upload_purpose_options_' . $current_locale));
									$archival_upload_purpose = $sip_upload_form->get_form_value( 'archival_upload_purpose' );
									foreach ($upload_purpose_options as $upload_purpose_option) :
										?>
										<option value="<?php echo esc_attr($upload_purpose_option); ?>" <?php selected( $archival_upload_purpose, $upload_purpose_option ); ?>>
											<?php echo esc_attr($upload_purpose_option); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
							<p id="archival-upload-purpose-help-text" class="help"><?php esc_html_e( 'Select the purpose of the upload from the predefined list. The selected purpose helps categorize the content.', 'sip' ); ?></p>
						</div>
					</div>
					<div id="blocking-time" class="column">
						<div class="field">
							<label for="archival-blocking-time" class="label"><?php esc_html_e('Blocking time', 'sip'); ?>*</label>
							<p class="control">
								<select id="archival-blocking-time" name="archival_blocking_time" required aria-describedby="archival-blocking-time-help-text">
									<?php
									$blocking_time_options = explode("\r\n", carbon_get_theme_option('sip_blocking_time_options_' . $current_locale));
									$sip_blocking_time_calculate = esc_attr(carbon_get_theme_option('sip_blocking_time_calculate_' . $current_locale));
									$archival_blocking_time = $sip_upload_form->get_form_value( 'archival_blocking_time' );
									foreach ($blocking_time_options as $blocking_time_option) :
										if ($blocking_time_option == $sip_blocking_time_calculate) {
											$user_birthday = get_user_meta($user->ID, 'user_birthday', true);// todo: check if this works if we view this page as admin/editor!
											$option_number = $int_var = (int)filter_var($blocking_time_option, FILTER_SANITIZE_NUMBER_INT);
											$blocking_time_option .= ' (' . $option_number - (date('Y', time()) - date('Y', strtotime($user_birthday))) .    ')';
										}
										?>
										<option value="<?php echo esc_attr($blocking_time_option); ?>" <?php selected( $archival_blocking_time, $blocking_time_option ); ?>>
											<?php echo esc_attr($blocking_time_option); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
							<p id="archival-blocking-time-help-text" class="help"><?php esc_html_e( 'Select a blocking time to define how long the files should remain inaccessible to the public. The available blocking periods are predefined and determine when the content becomes publicly accessible.', 'sip' ); ?></p>
						</div>
					</div>
				</div>

				<?php
				// todo: refactor.
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

				<div class="field">
					<label class="checkbox">
						<input id="archival_right_transfer" type="checkbox" name="archival_right_transfer" value="yes" required <?php checked( esc_attr( $sip_upload_form->get_form_value( 'archival_right_transfer' ) ), 'yes' ); ?>>
						<?php // todo: maybe add some default text? ?>
						<?php echo wp_kses_post(get_option('_sip_right_transfer_text_' . $current_locale)); ?>
					</label>
				</div>

				<?php if (current_user_can('edit_others_posts')) : ?>
					<h3><?php esc_html_e('Archive information', 'sip'); ?></h3>

					<div class="field">
						<label for="archival-numeration" class="label"><?php esc_html_e('Numbering', 'sip'); ?></label>
						<p class="control">
							<input id="archival-numeration" name="archival_numeration" class="input" type="text" value="<?php echo esc_html( $sip_upload_form->get_form_value( 'archival_numeration' ) ); ?>">
						</p>
					</div>
					<div class="field">
						<label for="archival-annotation" class="label"><?php esc_html_e('Note', 'sip'); ?></label>
						<p class="control">
							<textarea id="archival-annotation" name="archival_annotation" class="textarea count-character" rows="5" maxlength="5000"><?php echo wp_kses_post( $sip_upload_form->get_form_value( 'archival_annotation' ) ); ?></textarea>
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

						<?php
						$editor_is_author = false;
						$post_is_draft    = false;
						$user_can_publish = current_user_can( 'publish_archival_records' );
						if ( $archival ) {
							$author_id        = (int) get_post_field( 'post_author', $archival->ID, true );
							$editor_is_author = ( $user_can_publish && $author_id === get_current_user_id() );
							$post_is_draft    = 'draft' === get_post_status( $archival->ID );
						}
						// contributors are only allowed to save_draft or submit_archival.
						// if a post is a draft, it should not be edited by editors and only saved as draft or submitted.
						// if a post is no draft and the editor is its author, it should be saved as draft or submitted.
						if ( ! $user_can_publish || $post_is_draft || ( $post_is_draft && $editor_is_author ) ) : ?>
							<div class="mb-4">
								<button class="button" name="save_sip" type="submit" value="save_draft">
									<?php esc_html_e('Save as draft', 'sip'); ?>
								</button>
							</div>
							<div>
								<button class="button is-success is-light is-outlined" name="save_sip" type="submit" value="submit_archival">
									<?php esc_html_e('Submit', 'sip'); ?>
								</button>
							</div>
						<?php else : ?>
							<button class="button is-success is-light is-outlined" name="save_sip" type="submit" value="save_archival">
								<?php esc_html_e('Save', 'sip'); ?>
							</button>
						<?php endif; ?>
					</p>
				</div>

				<?php
				$archival_tags_list_names = $sip_upload_form->get_form_value( 'archival_tags' );
				$archival_tags            = get_terms(array('taxonomy' => Archival_Custom_Posts::ARCHIVAL_TAG_CUSTOM_TAX_SLUG, 'hide_empty' => false));
				if ( is_wp_error( $archival_tags ) ) {
					$archival_tags_names      = array();
				} else {
					$archival_tags_names      = array_map('esc_attr', wp_list_pluck($archival_tags, 'name'));
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

						// the textarea for the tags.
						let inputElm = document.querySelector('#archival-tags');
						let tagify = new Tagify(inputElm, {
							whitelist: <?php echo json_encode($archival_tags_names); ?>,
							dropdown: {
								classname: "suggested-tags",
								enabled: 0, // show the dropdown immediately on focus
								maxItems: 5,
								position: "text", // place the dropdown near the typed text
								closeOnSelect: false, // keep the dropdown open after selecting a suggestion
								highlightFirst: true,
							},
							// placeholder: '',
							// blacklist: [],
							// enforceWhitelist: true, // don't allow new tags.
						});

						// link the label to the tagify input.
						let tagifyInput = inputElm.parentNode.querySelector( '.tagify__input' );
						tagifyInput.setAttribute('aria-labelledby', 'archival-tags');

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
