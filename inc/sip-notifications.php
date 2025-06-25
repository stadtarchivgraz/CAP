<?php
use BracketSpace\Notification\Core\Sync;

add_action( 'notification/init', function () {
	$json_dir = dirname( __DIR__ ) . '/assets/notification-json/';
	Sync::enable($json_dir);
}, 5 );

add_action( 'notification/trigger/merge_tags', function( $trigger ) {
	if ( ! preg_match( '/post\/archival\/(updated|trashed|published|drafted|added|pending|scheduled)/', $trigger->get_slug() )) {
		return;
	}

	$trigger->add_merge_tag( new BracketSpace\Notification\Defaults\MergeTag\StringTag( [
		'slug'     => 'archive_editors',
		'name'     => __( 'Archive Editors', 'sip' ),
		'resolver' => function( $trigger ) {
			$archives = get_the_terms( $trigger->{ $trigger->get_post_type() }->ID, 'archive');
			$archive_editors = get_users( array(
				'meta_key' => 'user_archive',
				'meta_value' => $archives[0]->term_id,
				'fields' => 'user_email',
				'role' => 'editor',
			) );
			if($archive_editors) {
				return implode(',', $archive_editors);
			} else return get_bloginfo('admin_email');
		},
	] ) );

} );