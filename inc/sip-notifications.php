<?php
use BracketSpace\Notification\Core\Sync;

/**
 * Configures a list of notifications for archivals.
 * Actually implemented are notifications
 *  - for archival_editors, if a new entry awaits approval
 *  - for archival_author_user, if ones entry was approved.
 *
 * @todo: translation for the notification text in the json files.
 */
function starg_add_notification_json () {
	$archivals = get_posts( array(
		'post_type'      => 'archival',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	if ( empty( $archivals ) || ! $archivals ) {
		return;
	}

	$json_dir = dirname( __DIR__ ) . '/assets/notification-json/';
	Sync::enable( $json_dir );
}
add_action( 'notification/init', 'starg_add_notification_json', 5 );

function starg_trigger_notificaton_merge_tags( $trigger ) {
	if ( ! preg_match( '/post\/archival\/(updated|trashed|published|drafted|added|pending|scheduled)/', $trigger->get_slug() ) ) {
		return;
	}

	$trigger->add_merge_tag( new BracketSpace\Notification\Defaults\MergeTag\StringTag( array(
		'slug'     => 'archive_editors',
		'name'     => __( 'Archive Editors', 'sip' ),
		'resolver' => function( $trigger ) {
			$archives = get_the_terms( $trigger->{ $trigger->get_post_type() }->ID, 'archive' );
			$archive_editors = get_users( array(
				'meta_key'   => 'user_archive',
				'meta_value' => $archives[0]->term_id,
				'fields'     => 'user_email',
				'role'       => 'editor',
			) );
			if ( $archive_editors ) {
				return implode( ',', $archive_editors );
			} else {
				return get_bloginfo( 'admin_email' );
			}
		},
	) ) );
}
add_action( 'notification/trigger/merge_tags', 'starg_trigger_notificaton_merge_tags' );
