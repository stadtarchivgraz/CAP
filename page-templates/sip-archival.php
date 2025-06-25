<?php
// Template Name: Archival
if(!$archival_name = get_query_var('archival_name')) {
	if($pages = get_pages(array( 'meta_key' => '_wp_page_template', 'meta_value' => 'sip-archive.php', 'hierarchical' => 0 ))) {
		wp_redirect( get_the_permalink( $pages[0] ) );
	} else wp_redirect( home_url() );
}

global $post;
if(!is_numeric($archival_name)) {
	$post = get_page_by_path( $archival_name, OBJECT, 'archival' );
} else $post = get_post($archival_name);

if(!$post) {
	if($pages = get_pages(array( 'meta_key' => '_wp_page_template', 'meta_value' => 'sip-archive.php', 'hierarchical' => 0 ))) {
		wp_redirect( get_the_permalink( $pages[0] ) );
	} else wp_redirect( home_url() );
}

get_header();



include( dirname( __DIR__ ) . '/template-parts/content-archival.php' );

wp_reset_postdata();

get_footer();