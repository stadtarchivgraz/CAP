<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_One
 * @since Twenty Twenty-One 1.0
 */
$user = wp_get_current_user();
$archival = false; //get_post();
$archival_from = false;
$archival_to = false;
$current_locale = strtolower(get_locale());

if(isset($_GET['sipFolder']) && $_GET['sipFolder']) {
    global $wpdb;
    if($archival_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder' AND meta_value = %s", $_GET['sipFolder']))) {
	    $archival = get_post($archival_id);
	    $archival_from = get_post_meta($archival->ID, '_archival_from', true);
	    $archival_to = get_post_meta($archival->ID, '_archival_to', true);
	    $archival_originator = get_post_meta($archival->ID, '_archival_originator', true);
    }
}

?>

<script src="<?= plugin_dir_url(__DIR__ ); ?>assets/js/tagify.js"></script>
<script src="<?= plugin_dir_url(__DIR__ ); ?>assets/js/tagify.polyfills.min.js"></script>
<link href="<?= plugin_dir_url(__DIR__ ); ?>assets/css/tagify.css" rel="stylesheet" type="text/css" />

<script src="<?= plugin_dir_url(__DIR__ ); ?>assets/js/nouislider.min.js"></script>
<link href="<?= plugin_dir_url(__DIR__ ); ?>assets/css/nouislider.min.css" rel="stylesheet" type="text/css" />

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <header class="entry-header alignwide">
        <?php the_title( '<h2 class="entry-title">', '</h2>' ); ?>
    </header><!-- .entry-header -->

	<div class="entry-content content">
        <?php
        include( dirname( __FILE__ ) . '/content-sip-folder.php' );
        if($exif_dates) {
            $exif_dates_min = min( $exif_dates );
            $exif_dates_max = max( $exif_dates );
            $period_days    = ceil( ( $exif_dates_max - $exif_dates_min ) / 86400 );
            if ( ! $archival_from ) {
                $archival_from = date( 'Y-m-d\TH:i', $exif_dates_min );
            }
            if ( ! $archival_to && $period_days > 365 ) {
                $archival_to = date( 'Y.m.d H:i:s', $exif_dates_max );
            }
        }
        ?>
        <div class="container sip">
            <form action="" method="post">
                <div class="field">
                    <label for="archival-title" class="label is-large"><?php _e('Title', 'sip'); ?>*</label>
                    <p class="control is-large">
                        <input id="archival-title" name="archival_title" class="input is-large count-character" type="text" placeholder="<?php _e('Give your submission a descriptive title', 'sip'); ?>" value="<?= ($archival)?$archival->post_title:''; ?>" maxlength="100" required>
                    </p>
                    <p id="archival-title_count" class="help"><span><?= ($archival)?strlen($archival->post_title):0; ?></span> | <?php _e('Maximum 100 characters.', 'sip'); ?></p>
                </div>
                <div class="field">
                    <label for="archival-originator" class="label"><?php _e('Originator', 'sip'); ?>*</label>
                    <p class="control">
                        <input id="archival-originator" name="archival_originator" class="input" type="text" value="<?= ($archival)?$archival_originator:$user->display_name; ?>" required>
                    </p>
                    <p class="help"><?php _e('If you are not the originator (creator) of the uploaded file, please enter the name of the originator here', 'sip'); ?></p>
                </div>
                <div class="field">
                    <label for="archival-description" class="label"><?php _e('Description', 'sip'); ?>*</label>
                    <p class="control">
                        <textarea id="archival-description" name="archival_description" class="textarea count-character" rows="10" maxlength="5000" placeholder="<?php _e('You can describe your file in detail here (e.g.: Why is it important for the archive? What does the file show? In what context was the file created? Is there any additional information?)', 'sip'); ?>" required><?= ($archival)?$archival->post_content:''; ?></textarea>
                    </p>
                    <p id="archival-description_count" class="help"><span><?= ($archival)?strlen($archival->post_content):0; ?></span> | <?php _e('Maximum 5000 characters.', 'sip'); ?></p>
                </div>
                <div class="field">
                    <label for="archival-single-date" class="label"><?php _e('Date/time (for a precise time)', 'sip'); ?></label>
                    <p class="control">
                        <input id="archival-single-date" name="archival_single_date" type="datetime-local" value="<?= ($archival_from && !$archival_to)?$archival_from:''; ?>">
                    </p>
                </div>
                <div class="field">
                    <label class="label"><?php _e('Time period (for a longer period)', 'sip'); ?></label>
                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" onclick="toggleField('date-range-control');">
                            <?php _e('Hide timeline', 'sip'); ?>
                        </label>
                    </div>
                    <div id="date-range-control" class="control date-range-control">
                        <div id="date-range">

                        </div>
                    </div>
                    <div class="columns">
                        <div class="column">
                            <p class="control">
                                <input id="archival-date-range-start" name="archival_date_range[]" type="number" max="<?= date('Y'); ?>" min="1850" step="1" maxlength="4">
                            </p>
                        </div>
                        <div class="column">
                            <p class="control">
                                <input id="archival-date-range-end" name="archival_date_range[]" type="number" max="<?= date('Y'); ?>" min="1850" step="1" maxlength="4">
                            </p>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label for="archival-map" class="label"><?php _e('Location', 'sip'); ?></label>
                    <label class="checkbox">
                        <input type="checkbox" onclick="toggleField('archival-map','archival-address');">
                        <?php _e('Hide map', 'sip'); ?>
                    </label>
                    <div id="archival-map">
                        <p><?php _e('Map (select an exact location or area)', 'sip'); ?></p>
	                    <?php include( dirname( __DIR__ ) . '/template-parts/content-map.php' ); ?>
                    </div>
                    <div><input id="archival-address" name="archival_address" type="hidden" class="input" value="<?= ($archival)?get_post_meta($archival->ID, '_archival_address', true):''; ?>"></div>
                    <input id="archival-lat" name="archival_lat" type="hidden" value="<?= ($archival)?get_post_meta($archival->ID, '_archival_lat', true):''; ?>">
                    <input id="archival-lng" name="archival_lng" type="hidden" value="<?= ($archival)?get_post_meta($archival->ID, '_archival_lng', true):''; ?>">
                    <input id="archival-area" name="archival_area" type="hidden" value="<?= ($archival)?get_post_meta($archival->ID, '_archival_area', true):''; ?>">
                </div>
                <div class="field">
                    <label for="archival-tags" class="label"><?php _e('Tags', 'sip'); ?>*</label>
                    <p class="control">
                        <textarea id="archival-tags" name="archival_tags" class="textarea" rows="2" maxlength="10" required></textarea>
                    </p>
                    <p class="help"><?php _e('Minimum 1 | Maximum 10', 'sip'); ?></p>
                </div>
                <div class="columns">
                    <div class="column">
                        <div class="field">
                            <label for="archival-upload-purpose" class="label"><?php _e('Upload purpose', 'sip'); ?></label>
                            <p class="control">
                                <select id="archival-upload-purpose" name="archival_upload_purpose" required>
                                    <?php
                                    $upload_purpose_options = explode("\r\n", carbon_get_theme_option( 'sip_upload_purpose_options_' . $current_locale ) );
                                    $archival_upload_purpose = ($archival)?get_post_meta($archival->ID, '_archival_upload_purpose', true):'';
                                    foreach ($upload_purpose_options as $upload_purpose_option) : ?>
                                        <option value="<?= $upload_purpose_option; ?>"<?= ($archival_upload_purpose == $upload_purpose_option)?' selected':''; ?>><?= $upload_purpose_option; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                        </div>
                    </div>
                    <div id="blocking-time" class="column">
                        <div class="field">
                            <label for="archival-blocking-time" class="label"><?php _e('Blocking time', 'sip'); ?></label>
                            <p class="control">
                                <select id="archival-blocking-time" name="archival_blocking_time" required>
                                    <?php
                                    $blocking_time_options = explode("\r\n", carbon_get_theme_option( 'sip_blocking_time_options_' . $current_locale ) );
                                    $sip_blocking_time_calculate = carbon_get_theme_option( 'sip_blocking_time_calculate_' . $current_locale );
                                    $archival_blocking_time = ($archival)?get_post_meta($archival->ID, '_archival_blocking_time', true):'';
                                    foreach ($blocking_time_options as $blocking_time_option) :
                                        if($blocking_time_option == $sip_blocking_time_calculate) {
                                            $user_birthday = get_user_meta($user->ID, 'user_birthday', true);
                                            $option_number = $int_var = (int)filter_var($blocking_time_option, FILTER_SANITIZE_NUMBER_INT);
                                            $blocking_time_option .= ' (' . $option_number - (date('Y', time()) - date('Y', strtotime($user_birthday))) .    ')';
                                        }
                                        ?>
                                        <option value="<?= $blocking_time_option; ?>"<?= ($archival_blocking_time == $blocking_time_option)?' selected':''; ?>><?= $blocking_time_option; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                        </div>
                    </div>
                </div>
                <?php
                $sip_custom_meta = carbon_get_theme_option('sip_custom_meta');
                foreach($sip_custom_meta as $custom_meta) :
                    $meta_name = sanitize_title( $custom_meta['sip_custom_meta_key'] );
                    $meta_type = $custom_meta['sip_custom_meta_type'];
                    ?>
                    <div class="field">
                        <label for="<?= $meta_name; ?>" class="label"><?= $custom_meta['sip_custom_meta_title_' . $current_locale]; ?></label>
                        <p class="control">
                            <?php if($meta_type == 'textarea') : ?>
                                <textarea id="<?= $meta_name; ?>" name="<?= '_archival_' . $meta_name; ?>" class="textarea"><?= ($archival)?get_post_meta($archival->ID, '_archival_' . $meta_name, true):''; ?></textarea>
                            <?php else : ?>
                                <input id="<?= $meta_name; ?>" name="<?= '_archival_' . $meta_name; ?>" class="input" type="text" value="<?= ($archival)?get_post_meta($archival->ID, '_archival_' . $meta_name, true):''; ?>">
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>

                <?php $archival_right_transfer = ($archival)?get_post_meta($archival->ID, '_archival_right_transfer', true):''; ?>
                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox" name="archival_right_transfer" value="yes" required<?= ($archival_right_transfer)?' checked':''; ?>>
                        <?= get_option('_sip_right_transfer_text_' . $current_locale); ?>
                    </label>
                </div>

                <?php if(current_user_can('edit_others_posts')) : ?>
                    <h3><?php _e('Archive information', 'sip'); ?></h3>

                    <div class="field">
                        <label for="archival-numeration" class="label"><?php _e('Numbering', 'sip'); ?></label>
                        <p class="control">
                            <input id="archival-numeration" name="archival_numeration" class="input" type="text" value="<?= ($archival)?get_post_meta($archival->ID, '_archival_numeration', true):''; ?>">
                        </p>
                    </div>
                    <div class="field">
                        <label for="archival-annotation" class="label"><?php _e('Note', 'sip'); ?></label>
                        <p class="control">
                            <textarea id="archival-annotation" name="archival_annotation" class="textarea count-character" rows="10" maxlength="5000"><?= ($archival)?get_post_meta($archival->ID, '_archival_annotation', true):''; ?></textarea>
                        </p>
                    </div>
                    <?php
                    $sip_custom_archival_user_meta = carbon_get_theme_option('sip_custom_archival_user_meta');
                    foreach($sip_custom_archival_user_meta as $custom_archival_user_meta) :
                        $meta_name = sanitize_title( $custom_archival_user_meta['sip_custom_archival_user_meta_key'] );
                        $meta_type = sanitize_title( $custom_archival_user_meta['sip_custom_archival_user_meta_type'] );
                        ?>
                        <div class="field">
                            <label for="<?= $meta_name; ?>" class="label"><?= $custom_archival_user_meta['sip_custom_archival_user_meta_title_' . $current_locale]; ?></label>
                            <p class="control">
                                <?php if($meta_type == 'textarea') : ?>
                                    <textarea id="<?= $meta_name; ?>" name="<?= '_archival_' . $meta_name; ?>" class="textarea"><?= ($archival)?get_post_meta($archival->ID, '_archival_' . $meta_name, true):''; ?></textarea>
                                <?php else : ?>
                                    <input id="<?= $meta_name; ?>" name="<?= '_archival_' . $meta_name; ?>" class="input" type="text" value="<?= ($archival)?get_post_meta($archival->ID, '_archival_' . $meta_name, true):''; ?>">
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="field">
                    <p class="control">
                        <?php
                        if($archival) : ?>
                        <input type="hidden" name="archival_ID" value="<?= $archival->ID; ?>">
                        <?php endif; ?>
                        <input name="save-sip" type="submit" value="<?php _e('Save and preview', 'sip'); ?>">
                    </p>
                </div>

                <?php
                $archival_tags = get_terms(array('taxonomy'   => 'archival_tag', 'hide_empty' => false));
                $archival_tags_names = wp_list_pluck($archival_tags, 'name');

                $archival_tags_list_names = '';
                if($archival) {
                    $archival_tags_list = get_the_terms( $archival->ID, 'archival_tag' );
                    $archival_tags_list_names = wp_list_pluck($archival_tags_list, 'name');
                }

                $blocking_time_upload_purpose = carbon_get_theme_option( 'sip_blocking_time_upload_purpose_' . $current_locale );
                ?>

                <script>
                    let range = document.getElementById('date-range'),
                        input0 = document.getElementById('archival-date-range-start'),
                        input1 = document.getElementById('archival-date-range-end'),
                        inputs = [input0, input1]

                    noUiSlider.create(range, {
                        start: [<?= ($archival_from && $archival_to)?substr($archival_from,0,4):0; ?>, <?= ($archival_from && $archival_to)?substr($archival_to,0,4):0; ?>],
                        range: {
                            'min': [1850],
                            'max': [<?= date('Y'); ?>]
                        },
                        step: 1,
                        format: {
                            to: function (value) {
                                return value
                            },
                            from: function (value) {
                                return Number(value)
                            }
                        },
                        pips: {
                            mode: 'count',
                            values: 10
                        },
                        connect: true,
                    })
                    range   .noUiSlider.on('update', function (values, handle) {
                        inputs[handle].value = values[handle];
                    });
                    input0.addEventListener('change', function () {
                        range.noUiSlider.set([this.value, null]);
                    });
                    input1.addEventListener('change', function () {
                        range.noUiSlider.set([null, this.value]);
                    });


                    const textCountElement = document.querySelectorAll(".count-character");

                    textCountElement.forEach((item) => {
                        item.addEventListener('keyup', onKeyupCountText)
                    });

                    function onKeyupCountText(e) {
                        document.querySelector('#' + e.target.id + '_count span').textContent = e.target.value.length
                    }

                    let uploadPurpose = document.querySelector('#archival-upload-purpose'),
                        blockingTime = document.querySelector('#archival-blocking-time'),
                        blocking = document.querySelector('#blocking-time')

                    uploadPurpose.addEventListener('change', onChangeUploadPurpose)

                    function onChangeUploadPurpose(e){
                        if('<?= $blocking_time_upload_purpose; ?>'.search(e.target.value) !== -1) {
                            blockingTime.required = true
                            blocking.style.display = 'block'
                        } else {
                            blockingTime.required = false
                            blocking.style.display = 'none'
                        }

                    }

                    let inputElm = document.querySelector('#archival-tags'),
                        tagify = new Tagify (inputElm, {
                            whitelist : <?= json_encode($archival_tags_names); ?>,
                            dropdown : {
                                classname: "color-blue",
                                enabled: 0,              // show the dropdown immediately on focus
                                maxItems: 5,
                                position: "text",         // place the dropdown near the typed text
                                closeOnSelect: false,          // keep the dropdown open after selecting a suggestion
                                highlightFirst: true
                            }
                        })

                    tagify.addTags(<?= json_encode($archival_tags_list_names); ?>)

                    inputElm.addEventListener('change', onChangeTagify)

                    function onChangeTagify(e){
                        // outputs a String
                        if(e.target.tagifyValue) {
                            let tags = JSON.parse(e.target.tagifyValue);
                            if(tags.length > e.target.attributes.maxlength.value) {
                                tagify.removeTag();
                            }
                        }
                    }

                    function toggleField(field, field2 = false) {
                        let field_element = document.getElementById(field);
                        if (field_element.style.display === "none") {
                            field_element.style.display = "block"; // Show content
                        } else {
                            field_element.style.display = "none"; // Hide content
                        }
                        if(field2) {
                            let field2_element = document.getElementById(field2);
                            if (field2_element.getAttribute('type') === "hidden") {
                                field2_element.setAttribute('type', 'text'); // Show content
                            } else {
                                field2_element.setAttribute('type', 'hidden');
                            }
                        }
                    }
                </script>
            </form>
        </div>
	</div><!-- .entry-content -->

</article><!-- #post-<?php the_ID(); ?> -->
