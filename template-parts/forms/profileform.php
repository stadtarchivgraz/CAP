<?php
$user = wp_get_current_user();
$current_locale = strtolower(get_locale());
$password_error = false;

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/update-user-profile.class.php' );
$update_user_profile = new Starg_Update_User_Profile;
$update_user_profile->maybe_process_update_user_profile();

// We need to initialize the class "Starg_Update_User_Password" in the main file of the Plugin in order to use WordPress-functions like "wp_update_user".
$update_user_password = apply_filters( 'starg/update_user_password', null );
if ( $update_user_password instanceof Starg_Update_User_Password ) {
	$password_error = $update_user_password->get_password_error();
	$update_user_password->display_notification();
}

require_once( STARG_SIP_PLUGIN_BASE_DIR . 'inc/form-validation/sip-archival-actions-form.class.php' );
$sip_archival_actions = new Sip_Archival_Actions;
$sip_archival_actions->process_sip_archival_actions();

$current_tab       = (get_query_var('tab')) ?: 'archivals';
$edit_archival_url = starg_get_the_edit_archival_page_url();
?>

<div class="container sip">
	<div class="tabs is-large">
		<ul>
			<li class="tab<?php echo ($current_tab === 'archivals') ? ' is-active' : ''; ?>" onclick="openTab(event,'archivals')">
				<a><?php esc_html_e('Submissions', 'sip'); ?></a>
			</li>
			<li class="tab<?php echo ($current_tab === 'drafts') ? ' is-active' : ''; ?>" onclick="openTab(event,'drafts')">
				<a><?php esc_html_e('Drafts', 'sip'); ?></a>
			</li>
			<li class="tab<?php echo ($current_tab === 'personal-data') ? ' is-active' : ''; ?>" onclick="openTab(event,'personal-data')">
				<a><?php esc_html_e('Personal account', 'sip'); ?></a>
			</li>
		</ul>
	</div>

	<section id="archivals" class="tab-content" <?php echo ($current_tab !== 'archivals') ? '  style="display: none"' : ''; ?>>
		<?php
		// needed for pagination!
		$paged          = get_query_var('paged') ?: 1;
		$published_args = array(
			'post_type'      => Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG,
			'post_status'    => array( 'pending', 'publish', ),
			'author'         => $user->ID,
			'lang'           => '',
			'paged'          => $paged,
		);

		$archivals = new WP_Query($published_args);

		if ( $archivals->have_posts() ) :
			include( STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-archivals-list.php' );
			wp_reset_postdata();
		else :
			$link_to_drafts_tab = '<a href="' . add_query_arg( array( 'tab' => 'drafts', ), starg_get_the_profile_page_template_url() ) . '">' . esc_attr_x( 'here', 'placeholder for a redirect link to a page for making adjustments.', 'sip' ) . '</a>';
			// translators: %s: Link to the page where the user can view their drafts.
			echo starg_get_notification_message( sprintf( esc_html__( 'You have not submitted any entries yet. Please check your drafts %s and complete at at least one of them.', 'sip' ), $link_to_drafts_tab ), 'is-info is-light' );
		endif;
		?>
	</section>
	<section id="drafts" class="tab-content" <?php echo ($current_tab !== 'drafts') ? '  style="display: none"' : ''; ?>>
		<?php
		// todo: with this approach of displaying the drafts we might hide some of the entries! Actually we display 50 at once if possible. Needs pagination!
		$archival_draft_args = array(
			'post_type'      => Archival_Custom_Posts::ARCHIVAL_POST_TYPE_SLUG,
			'post_status'    => 'draft',
			'author'         => $user->ID,
			'lang'           => '',
			'posts_per_page' => 50,
		);

		$archival_drafts = new WP_Query( $archival_draft_args );

		// we add all uploaded entries without connected archival post to the drafts.
		$archival_sip_folders = DB_Query_Helper::starg_get_archival_sip_folders_by_user_id( $user->ID );
		$upload_folder        = starg_get_archival_upload_path() . $user->ID . '/';
		$sip_folders          = glob($upload_folder . '*', GLOB_ONLYDIR);
		$user_sips            = array();

		if ( $archival_drafts->have_posts() ) :
			while ( $archival_drafts->have_posts() ) :
				$archival_drafts->the_post();
				$user_sips[strtotime(get_the_date('Y-m-d H:i:s'))] = array(
					'id'     => get_the_ID(),
					'title'  => get_the_title(),
					'sip'    => esc_attr( get_post_meta(get_the_ID(), '_archival_sip_folder', true) ),
					'status' => 'draft',
				);
			endwhile;
			wp_reset_postdata();
		endif;

		// Some files may have been uploaded without a corresponding archival post. We'll display them as 'uploads' so users can continue editing them.
		foreach ( $sip_folders as $sip_folder ) {
			$sip_folder_name = basename($sip_folder);
			if ( ! in_array($sip_folder_name, $archival_sip_folders) ) {
				$user_sips[ starg_get_folder_creation_days_ago( $sip_folder, true ) ]  = array(
					'title'  => $sip_folder_name,
					'sip'    => $sip_folder_name,
					'status' => 'upload',
				);
			}
		}

		if ( ! $user_sips ) :
			$link_to_drafts_tab = '<a href="' . $edit_archival_url . '">' . esc_attr_x( 'here', 'placeholder for a redirect link to a page for making adjustments.', 'sip' ) . '</a>';
			// translators: %s: Link to the page where an archival record can be uploaded.
			echo starg_get_notification_message( sprintf( esc_html__( 'You have not uploaded any files yet. Please upload your files %s.', 'sip' ), $link_to_drafts_tab ), 'is-info is-light' );
		else :
			// this sorts the actual drafts and their uploads based on the creation date.
			arsort($user_sips);

			$date_format = get_option('date_format');
			?>
			<table class="table is-striped is-narrow">
				<?php
				foreach ($user_sips as $date => $user_sip) :
					// we have no data for this entry, so we're not showing it. might be a deleted entry.
					if ( ! $user_sip['sip'] ) { continue; }
					?>
					<tr>
						<td><?php echo date_i18n($date_format, $date); ?></td>
						<td>
							<a class="has-text-weight-bold has-text-dark" href="<?php echo ( isset( $user_sip['id'] ) ) ? esc_url( starg_get_the_archival_page_template_url( $user_sip['id'] ) ) : '#'; ?>">
								<?php echo $user_sip['title']; ?>
							</a> - <?php esc_html_e($user_sip['status'], 'sip'); ?>
						</td>
						<td class="has-text-right">
							<a class="button is-large" href="<?php echo esc_url( add_query_arg( array( 'sipFolder' => $user_sip['sip'], ), $edit_archival_url ) ); ?>">
								<?php esc_html_e('Edit', 'sip'); ?>
							</a>

							<form target="" method="post" class="is-inline-block">
								<input type="hidden" name="<?php echo $sip_archival_actions->form_name_key; ?>" value="<?php echo $sip_archival_actions->form_name . '_' . $user_sip['sip']; ?>" aria-hidden="true" />
								<input type="hidden" name="starg_form_post_id" value="<?php the_ID(); ?>" aria-hidden="true" />
								<input type="hidden" name="sipFolder" value="<?php echo $user_sip['sip']; ?>" aria-hidden="true" />
								<input type="hidden" name="starg_form_suffix" value="<?php echo $user_sip['sip']; ?>" aria-hidden="true" />
								<?php wp_nonce_field( $sip_archival_actions->nonce_action, $sip_archival_actions->nonce_key . '_' . $user_sip['sip'], false ); ?>
								<?php // todo: maybe change to a modal? js-alerts are not that fancy! ?>
								<button class="button is-large is-danger" name="decline_archival" type="submit" value="decline" onclick="return confirm('<?php esc_html_e('Are you sure? All files will be deleted.', 'sip'); ?>')">
									<?php esc_html_e('Delete', 'sip'); ?>
								</button>
							</form>

						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>

	</section>
	<section id="personal-data" class="tab-content" <?php echo ($current_tab !== 'personal-data') ? '  style="display: none"' : ''; ?>>
		<form class="content" action="" method="post">
			<input type="hidden" name="<?php echo $update_user_profile->form_name_key; ?>" value="<?php echo $update_user_profile->form_name; ?>" aria-hidden="true" />
			<input type="hidden" name="starg_form_post_id" value="<?php the_ID(); ?>" aria-hidden="true" aria-hidden="true" />
			<?php wp_nonce_field( $update_user_profile->nonce_action, $update_user_profile->nonce_key ); ?>

			<h3><?php esc_html_e('Archive', 'sip'); ?></h3>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="user-login" class="label"><?php esc_html_e('Log in', 'sip'); ?></label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="user-login" class="input is-static" name="user_login_static" type="text" value="<?php echo $user->user_login; ?>" aria-readonly="true" readonly>
						</p>
					</div>
				</div>
			</div>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="display-name" class="label"><?php esc_html_e('Public name', 'sip'); ?></label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<?php
							// translators: Format for full name of an user. %1$s: first name. %2$s: last name.
							$display_name = ( $update_user_profile->get_form_value( 'first_name' ) && $update_user_profile->get_form_value( 'last_name' ) ) ? sprintf( esc_attr_x( '%1$s %2$s', 'Format for full name of an user. %1$s is first name, %2$s is last name. Rearrange if necessary.', 'sip' ), $update_user_profile->get_form_value( 'first_name' ), $update_user_profile->get_form_value( 'last_name' ) ) : '';
							?>
							<input id="display-name" class="input is-static" name="display_name" type="text" value="<?php echo $display_name; ?>" aria-readonly="true" readonly placeholder="<?php esc_attr_e( 'Will be generated from your name', 'sip' ); ?>">
						</p>
					</div>
				</div>
			</div>
			<?php $user_archive = $update_user_profile->get_form_value( 'user_archive' ); ?>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="archive" class="label"><?php esc_html_e('Archive', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<?php
							if ( ! $user_archive) :
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
								wp_dropdown_categories($args);
							else :
								$archive = get_term($user_archive, Archival_Custom_Posts::ARCHIVE_CUSTOM_TAX_SLUG); ?>
								<input id="archive" class="input is-static" name="static_user_archive" type="text" value="<?php echo $archive->name; ?>" aria-readonly="true" readonly>
							<?php endif; ?>
						</p>
						<?php if (!$user_archive) : ?>
							<p class="help"><?php esc_html_e('This selection cannot be changed', 'sip'); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<h3><?php esc_html_e('Personal account', 'sip'); ?></h3>

			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="first-name" class="label"><?php esc_html_e('First name', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="first-name" name="first_name" class="input" type="text" value="<?php echo $update_user_profile->get_form_value( 'first_name' ); ?>" placeholder="<?php esc_html_e('First name', 'sip'); ?>" required autocomplete="given-name">
						</p>
					</div>
				</div>
			</div>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="last-name" class="label"><?php esc_html_e('Surname', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="last-name" name="last_name" class="input" type="text" value="<?php echo $update_user_profile->get_form_value( 'last_name' ); ?>" placeholder="<?php esc_html_e('Surname', 'sip'); ?>" required autocomplete="family-name">
						</p>
					</div>
				</div>
			</div>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="email" class="label"><?php esc_html_e('Email address', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="email" name="user_email" class="input" type="email" value="<?php echo $update_user_profile->get_form_value( 'user_email' ); ?>" required autocomplete="email">
						</p>
					</div>
				</div>
			</div>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="date-of-birth" class="label"><?php esc_html_e('Date of birth', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="date-of-birth" name="user_birthday" class="input" type="date" value="<?php echo $update_user_profile->get_form_value( 'user_birthday' ); ?>" min="<?php echo date('Y-m-d', strtotime('100 years ago')); ?>" max="<?php echo date('Y-m-d', strtotime('18 years ago')); ?>" required>
						</p>
						<p class="help"><?php esc_html_e('You must be at least 18 years old', 'sip'); ?></p>
					</div>
				</div>
			</div>
			<?php $user_address = $update_user_profile->get_form_value( 'user_address' ); ?>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="address" class="label"><?php esc_html_e('Address', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="address" name="user_address[street_number]" class="input" type="text" placeholder="<?php esc_html_e('Street and Number', 'sip'); ?>" value="<?php echo (isset($user_address['street_number'])) ? esc_attr( $user_address['street_number'] ) : ''; ?>" required autocomplete="street-address">
						</p>
					</div>
				</div>
			</div>
			<div class="field is-horizontal">
				<div class="field-label is-normal"></div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="zip" name="user_address[zip]" class="input" type="text" placeholder="<?php esc_html_e('ZIP Code', 'sip'); ?>" value="<?php echo (isset($user_address['zip'])) ? esc_attr( $user_address['zip'] ) : ''; ?>" required autocomplete="postal-code">
						</p>
					</div>
					<div class="field">
						<p class="control">
							<input id="city" name="user_address[city]" class="input" type="text" placeholder="<?php esc_html_e('City', 'sip'); ?>" value="<?php echo (isset($user_address['city'])) ? esc_attr( $user_address['city'] ) : ''; ?>" required>
						</p>
					</div>
				</div>
			</div>
			<?php $user_privacy_policy_approval = $update_user_profile->get_form_value( 'user_privacy_policy_approval' ); ?>
			<div class="field is-horizontal">
				<div class="field-label is-normal"></div>
				<div class="field-body">
					<label class="checkbox">
						<input type="checkbox" name="user_privacy_policy_approval" required <?php checked( $user_privacy_policy_approval, 'on' ); ?>>
						<?php echo wp_kses_post( get_option('_sip_privacy_policy_approval_text_' . $current_locale) ); ?>
					</label>
				</div>
			</div>
			<div class="field">
				<p class="control has-text-right">
					<input type="hidden" name="ID" value="<?php echo $user->ID; ?>"><?php // todo: maybe change name="ID" to name="user_id" ?>
					<input type="submit" name="user_save" value="<?php esc_html_e('Save', 'sip'); ?>">
				</p>
			</div>
		</form>

		<form class="content" action="" method="post">
			<input type="hidden" name="<?php echo $update_user_password->form_name_key; ?>" value="<?php echo $update_user_password->form_name; ?>" aria-hidden="true" />
			<input type="hidden" name="starg_form_post_id" value="<?php the_ID(); ?>" aria-hidden="true" />
			<?php wp_nonce_field( $update_user_password->nonce_action, $update_user_password->nonce_key ); ?>

			<h3><?php esc_html_e('Change password', 'sip'); ?></h3>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="old-password" class="label"><?php esc_html_e('Old', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="old-password" name="oldpassword" class="input" type="password" required autocomplete="current-password">
						</p>
					</div>
				</div>
			</div>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="pass1" class="label"><?php esc_html_e('New', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="pass1" name="newpassword" class="input" type="password" required aria-describedby="pass-strength-result" autocomplete="new-password">
						</p>
						<p class="help">
							<?php esc_html_e('Strength of password', 'sip'); ?> <span id="pass-strength-result" class="hide-if-no-js empty" aria-live="polite"></span><br>
							<?php echo apply_filters('password_hint', __('Tip: Your password should be at least twelve characters long. For a stronger password, use upper and lower case letters, numbers and special characters such as !’?$%^&).', 'sip')) ?>
						</p>
					</div>
				</div>
			</div>
			<div class="field is-horizontal">
				<div class="field-label is-normal">
					<label for="repeat-password" class="label"><?php esc_html_e('Repeat', 'sip'); ?>*</label>
				</div>
				<div class="field-body">
					<div class="field">
						<p class="control">
							<input id="repeat-password" name="repeatpassword" class="input" type="password" required autocomplete="new-password">
						</p>
						<?php if ( $password_error ) : ?>
							<p class="help is-danger"><?php echo $password_error; ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php do_action('resetpass_form', $user); ?>
			<div class="field">
				<p class="control has-text-right">
					<input type="hidden" name="ID" value="<?php echo $user->ID; ?>">
					<input type="submit" name="password_save" value="<?php esc_html_e('Change password', 'sip'); ?>">
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
