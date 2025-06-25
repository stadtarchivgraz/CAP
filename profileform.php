<?php
wp_enqueue_script( 'utils' );
wp_enqueue_script( 'user-profile' );

$user = wp_get_current_user();
$current_locale = strtolower(get_locale());
$password_error = false;

if(isset($_POST['user_save']) && $_POST['ID']) {
    $user_data['ID'] = $_POST['ID'];
    $user_data['display_name'] = $_POST['first_name'] . ' ' . $_POST['last_name'];
    $user_data['first_name'] = $_POST['first_name'];
    $user_data['last_name'] = $_POST['last_name'];
    $user_data['user_email'] = $_POST['user_email'];
    wp_update_user($user_data);
    if(isset($_POST['user_archive'])) {
	    update_user_meta($_POST['ID'], 'user_archive', $_POST['user_archive']);
    }
	update_user_meta($_POST['ID'], 'user_address', $_POST['user_address']);
	update_user_meta($_POST['ID'], 'user_birthday', $_POST['user_birthday']);
	update_user_meta($_POST['ID'], 'user_privacy_policy_approval', $_POST['user_privacy_policy_approval']);
	update_user_meta($_POST['ID'], 'user_archive_profile', 1);
    ?>
    <div class="container sip">
        <div class="notification is-success is-light">
            <button class="delete"></button>
		    <?php _e('Your personal information has been successfully changed.', 'sip'); ?>
        </div>
    </div>
    <?php
}
if(isset($_POST['password_save']) && $_POST['ID']) {
    if(!$_POST['oldpassword']) {
        $password_error = __('Enter your old password.', 'sip');
    } elseif(!wp_check_password( $_POST['oldpassword'], $user->user_pass, $user->ID )) {
        $password_error = __('The old password is incorrect.', 'sip');
    } elseif(strlen($_POST['newpassword']) < 12) {
        $password_error = __('The new password is to short.', 'sip');
    } elseif($_POST['newpassword'] != $_POST['repeatpassword']) {
        $password_error = __('The repeat and the new password do not match.', 'sip');
    } else {
        wp_set_password( $_POST['newpassword'],  $_POST['ID']);
    }
    if($password_error) : ?>
        <div class="container sip">
            <div class="notification is-danger is-light">
                <button class="delete"></button>
                <?php _e('The password could not be changed. See error message in the form.', 'sip'); ?>
            </div>
        </div>
    <?php else : ?>
        <div class="container sip">
            <div class="notification is-success is-light">
                <button class="delete"></button>
                <?php _e('Your password was changed successfully. You have to log in again.', 'sip'); ?>
            </div>
        </div>
    <?php endif;
}

$current_tab = (get_query_var( 'tab' ))?:'archivals';

$pages = get_pages(array(
	'meta_key' => '_wp_page_template',
	'meta_value' => 'sip-upload.php',
	'hierarchical' => 0
));
?>

<div class="container sip">
    <div class="tabs is-large">
        <ul>
            <li class="tab<?= ($current_tab === 'archivals')?' is-active':''; ?>" onclick="openTab(event,'archivals')"><a><?php _e('Submissions', 'sip'); ?></a></li>
            <li class="tab<?= ($current_tab === 'drafts')?' is-active':''; ?>" onclick="openTab(event,'drafts')"><a><?php _e('Drafts', 'sip'); ?></a></li>
            <li class="tab<?= ($current_tab === 'personal-data')?' is-active':''; ?>" onclick="openTab(event,'personal-data')"><a><?php _e('Personal account', 'sip'); ?></a></li>
        </ul>
    </div>

    <section id="archivals" class="tab-content"<?= ($current_tab !== 'archivals')?'  style="display: none"':''; ?>>
        <?php
        $args = array(
            'post_type' => 'archival',
            'post_status' => 'publish',
            'author' => $user->ID,
            'lang' => ''
        );

        $archivals = new WP_Query($args);

        if($archivals->have_posts()) :
            include( dirname( __FILE__ ) . '/template-parts/content-archivals-list.php' );
        endif;
        wp_reset_query();
        ?>
    </section>
    <section id="drafts" class="tab-content"<?= ($current_tab !== 'drafts')?'  style="display: none"':''; ?>>
        <?php
        $args = array(
            'post_type' => 'archival',
            'post_status' => 'draft',
            'author' => $user->ID,
            'lang' => ''
        );

        $archivals = new WP_Query($args);

        global $wpdb;
        $archival_sip_folders = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id = ID WHERE meta_key = '_archival_sip_folder' AND post_author = %d", $user->ID ));
        $archival_sip_folders = wp_list_pluck($archival_sip_folders, 'meta_value');
        $upload_folder = carbon_get_theme_option( 'sip_upload_path' ) . $user->ID . '/';
        $sip_folders = glob($upload_folder . '*' , GLOB_ONLYDIR);

        $user_sips = array();

        while($archivals->have_posts()) :
            $archivals->the_post();
            $user_sips[strtotime(get_the_date('Y-m-d H:i:s'))] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'sip' => get_post_meta(get_the_ID(), '_archival_sip_folder', true),
                'status' => 'draft'
            );
        endwhile;

        foreach($sip_folders as $sip_folder) {
	        $sip_folder_name = basename($sip_folder);
	        if (!in_array($sip_folder_name, $archival_sip_folders)) {
		        $user_sips[get_folder_creation_days_ago($sip_folder, true)] = array(
			        'title' => $sip_folder_name,
                    'sip' => $sip_folder_name,
                    'status' => 'upload'
		        );
	        }
        }

        arsort($user_sips);

        $date_format = get_option('date_format');
        ?>
        <table class="table is-striped is-narrow">
		    <?php foreach ($user_sips as $date => $user_sip) : ?>
			    <tr>
                    <td><?= date_i18n($date_format, $date); ?></td>
                    <td><strong><?= $user_sip['title']; ?></strong> - <?php _e($user_sip['status'], 'sip'); ?></td>
                    <td class="has-text-right">
                        <a class="button is-large" href="<?php the_permalink($pages[0]); ?>?sipFolder=<?= $user_sip['sip']; ?>"><?php _e('Edit', 'sip'); ?></a>
                        <a class="button is-large is-danger" href="?sipFolder=<?= $user_sip['sip']; ?>&delete=1<?= (isset($user_sip['id']))?'&archival_id='.$user_sip['id']:''; ?>" onclick="return confirm('<?php _e('Are you sure? All files will be deleted.', 'sip'); ?>')"><?php _e('Delete', 'sip'); ?></a>
                    </td>
                </tr>
		    <?php endforeach; ?>
        </table>
        <?php wp_reset_query(); ?>
    </section>
    <section id="personal-data" class="tab-content"<?= ($current_tab !== 'personal-data')?'  style="display: none"':''; ?>>
        <form class="content" action="" method="post">
            <h3><?php _e('Archive', 'sip'); ?></h3>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label"><?php _e('Log in', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input class="input is-static" type="text" value="<?= $user->user_login; ?>" aria-readonly="true" readonly>
                        </p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="display-name" class="label"><?php _e('Public name', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input class="input is-static" name="display_name" type="text" value="<?= $user->first_name; ?> <?= $user->last_name; ?>" aria-readonly="true" readonly>
                        </p>
                    </div>
                </div>
            </div>
            <?php $user_archive = get_user_meta($user->ID, 'user_archive', true); ?>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="archive" class="label"><?php _e('Archive', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <?php
                            if(!$user_archive) :
                                $args = array(
                                    'name'              => 'user_archive',
                                    'id'                => 'archive',
                                    'class'             => 'postform',
                                    'taxonomy'          => 'archive',
                                    'hide_empty'        => false,
                                    'hide_if_empty'     => false,
                                    'value_field'       => 'term_id',
                                    'required'          => true,
                                    'selected'          => $user_archive
                                );
                                wp_dropdown_categories( $args );
                            else :
                                $archive = get_term($user_archive, 'archive'); ?>
                                <input class="input is-static" type="text" value="<?= $archive->name; ?>" aria-readonly="true" readonly>
                            <?php endif; ?>
                        </p>
                        <?php if(!$user_archive) : ?>
                            <p class="help"><?php _e('This selection cannot be changed', 'sip'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <h3><?php _e('Personal account', 'sip'); ?></h3>

            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="first-name" class="label"><?php _e('First name', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="first-name" name="first_name" class="input" type="text" value="<?= $user->first_name; ?>" required>
                        </p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="last-name" class="label"><?php _e('Surname', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="last-name" name="last_name" class="input" type="text" value="<?= $user->last_name; ?>" required>
                        </p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="email" class="label"><?php _e('Email address', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="email" name="user_email" class="input" type="email" value="<?= $user->user_email; ?>" required>
                        </p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="date-of-birth" class="label"><?php _e('Date of birth', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="date-of-birth" name="user_birthday" class="input" type="date" value="<?= get_user_meta($user->ID, 'user_birthday', true); ?>"  min="<?= date('Y-m-d',strtotime('100 years ago')); ?>" max="<?= date('Y-m-d',strtotime('18 years ago')); ?>" required>
                        </p>
                        <p class="help"><?php _e('You must be at least 18 years old', 'sip'); ?></p>
                    </div>
                </div>
            </div>
            <?php $user_address = get_user_meta($user->ID, 'user_address', true); ?>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="address" class="label"><?php _e('Address', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="address" name="user_address[street_number]" class="input" type="text" placeholder="<?php _e('Street and Number', 'sip'); ?>" value="<?= (isset($user_address['street_number']))?$user_address['street_number']:''; ?>" required>
                        </p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal"></div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="zip-code" name="user_address[zip]" class="input" type="text" placeholder="<?php _e('ZIP Code', 'sip'); ?>" value="<?= (isset($user_address['zip']))?$user_address['zip']:''; ?>" required>
                        </p>
                    </div>
                    <div class="field">
                        <p class="control">
                            <input id="city" name="user_address[city]" class="input" type="text" placeholder="<?php _e('City', 'sip'); ?>" value="<?= (isset($user_address['city']))?$user_address['city']:''; ?>" required>
                        </p>
                    </div>
                </div>
            </div>
	        <?php $user_privacy_policy_approval = get_user_meta($user->ID, 'user_privacy_policy_approval', true); ?>
            <div class="field is-horizontal">
                <div class="field-label is-normal"></div>
                <div class="field-body">
                    <label class="checkbox">
                        <input type="checkbox" name="user_privacy_policy_approval" required<?= ($user_privacy_policy_approval)?' checked':''; ?>>
				        <?= get_option('_sip_privacy_policy_approval_text_' . $current_locale); ?>
                    </label>
                </div>
            </div>
            <div class="field">
                <p class="control has-text-right">
                    <input type="hidden" name="ID" value="<?= $user->ID; ?>">
                    <input type="submit" name="user_save" value="<?php _e('Save', 'sip'); ?>">
                </p>
            </div>
        </form>

        <form class="content" action="" method="post">
            <h3><?php _e('Change password', 'sip'); ?></h3>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="old-passwort" class="label"><?php _e('Old', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="old-password" name="oldpassword" class="input" type="password">
                        </p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="pass1" class="label"><?php _e('New', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="pass1" name="newpassword" class="input" type="password" aria-describedby="pass-strength-result">
                        </p>
                        <p class="help">
                            <?php _e('Strength of password', 'sip'); ?> <span id="pass-strength-result" class="hide-if-no-js empty" aria-live="polite"></span><br>
                            <?= apply_filters( 'password_hint', __('Tip: Your password should be at least twelve characters long. For a stronger password, use upper and lower case letters, numbers and special characters such as !’?$%^&).', 'sip') ) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label for="repeat-password" class="label"><?php _e('Repeat', 'sip'); ?></label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <p class="control">
                            <input id="repeat-password" name="repeatpassword" class="input" type="password">
                        </p>
                        <?php if($password_error) : ?>
                            <p class="help is-danger"><?= $password_error; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
            do_action('resetpass_form', $user);
            ?>
            <div class="field">
                <p class="control has-text-right">
                    <input type="hidden" name="ID" value="<?= $user->ID; ?>">
                    <input type="submit" name="password_save" value="<?php _e('Change password', 'sip'); ?>">
                </p>
            </div>
        </form>
    </section>
</div>

<script>
    function openTab(evt, tabName) {
        var i, x, tablinks;
        x = document.getElementsByClassName("tab-content");
        for (i = 0; i < x.length; i++) {
            x[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tab");
        for (i = 0; i < x.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" is-active", "");
        }
        document.getElementById(tabName).style.display = "block";
        document.getElementById('pass1').disabled = false;
        evt.currentTarget.className += " is-active";

        history.pushState(null, 'Title', '<?php the_permalink(); ?>tab/' + tabName + '/');
    }
    (document.querySelectorAll('.notification .delete') || []).forEach(($delete) => {
        const $notification = $delete.parentNode;

        $delete.addEventListener('click', () => {
            $notification.parentNode.removeChild($notification);
        });
    });
</script>
