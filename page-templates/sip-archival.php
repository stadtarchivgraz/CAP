<?php
// Template Name: SIP Archival

$archival_name = get_query_var( 'archival_name' );
if ( ! $archival_name ) {
	if ( $pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'sip-archive.php', 'hierarchical' => 0, ) ) ) {
		wp_safe_redirect( get_the_permalink( $pages[0] ) );
		exit;
	} else {
		wp_safe_redirect( home_url() );
		exit;
	}
}

global $post;
if ( ! is_numeric( $archival_name ) ) {
	$post = get_page_by_path( $archival_name, OBJECT, 'archival' );
} else {
	$post = get_post( $archival_name );
}

if ( ! $post ) {
	if ( $pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'sip-archive.php', 'hierarchical' => 0, ) ) ) {
		wp_safe_redirect( get_the_permalink( $pages[0] ) );
		exit;
	} else {
		wp_safe_redirect( home_url() );
		exit;
	}
}

get_header();

// only admin/editor and the author should be able to view this page.
$author_id = (int) get_post_field( 'post_author', (int) $archival_name );

if ( current_user_can( 'edit_others_posts' ) || ( is_user_logged_in() && $author_id === get_current_user_id() ) ) {
	include(STARG_SIP_PLUGIN_BASE_DIR . 'template-parts/content-archival.php');
} else {
	echo starg_get_notification_message(esc_html__('You are not allowed to view this page!', 'sip'), 'is-warning is-light');
}

get_footer();
