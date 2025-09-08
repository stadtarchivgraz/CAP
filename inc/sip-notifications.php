<?php

/**
 * Registers a custom merge tag "archive_editors" for specific post-related Notification triggers.
 *
 * This merge tag resolves to the email addresses of users with the "editor" role
 * who are assigned to the same archive term as the triggered post (via the "user_archive" user meta).
 * If no matching editors are found, the site's admin email is used as a fallback.
 *
 * The function also includes debug logging for error tracing, such as:
 * - Missing merge tag class
 * - Issues retrieving archive terms
 * - Trigger processing failures
 *
 * Hook: `notification/trigger/merge_tags`
 * Plugin: Notification (by BracketSpace)
 *
 * @param \Notification\Trigger\Trigger $trigger The current trigger object passed by the Notification plugin.
 *
 * @return void
 */
function starg_trigger_notification_merge_tags($trigger) : void {
	if (! preg_match('/post\/archival\/(updated|trashed|published|drafted|added|pending|scheduled)/', $trigger->get_slug())) {
		return;
	}

	$logging = apply_filters( 'starg/logging', null );
	if ( ! class_exists( 'BracketSpace\Notification\Defaults\MergeTag\StringTag' ) ) {
		if ( $logging instanceof Starg_Logging ) {
			$logging->create_log_entry( esc_html__( 'Can not create merge_tag for the notification plugin. Notification plugin might not be installed.', 'sip' ) );
		}
	}

	try {
		$trigger->add_merge_tag(new BracketSpace\Notification\Defaults\MergeTag\StringTag(array(
			'slug'     => 'archive_editors',
			'name'     => __('Archive Editors', 'sip'),
			'resolver' => function ($trigger) {
				$post     = $trigger->post;
				$archives = get_the_terms($post->ID, 'archive');
				if ( empty($archives) || is_wp_error($archives) ) {
					if ( $logging instanceof Starg_Logging ) {
						$logging->create_log_entry( esc_html__( 'Error creating merge_tag for notification plugin. No archives found.', 'sip' ) );
					}
					return get_bloginfo('admin_email');
				}

				$archive_id      = $archives[0]->term_id;
				$archive_editors = get_users(array(
					'meta_key'   => 'user_archive',
					'meta_value' => $archive_id,
					'fields'     => 'user_email',
					'role'       => 'editor',
				));

				if ( ! empty($archive_editors) ) {
					return implode(',', $archive_editors);
				} else {
					return get_bloginfo('admin_email');
				}
			},
		)));
	} catch( Throwable $exception ) {
		if ( $logging instanceof Starg_Logging ) {
			// translators: %s: Error message.
			$logging->create_log_entry( sprintf( esc_html__( 'Error creating merge_tag for notification plugin: %s.', 'sip' ), $exception->getMessage() ) );
		}
	}
}
add_action('notification/trigger/merge_tags', 'starg_trigger_notification_merge_tags');
