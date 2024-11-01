<?php 
if( ! defined('WP_UNINSTALL_PLUGIN') )
	exit;

$allposts = get_posts( 'numberposts=-1&post_type=product&post_status=any' );

foreach( $allposts as $postinfo) {
	delete_post_meta( $postinfo->ID, 'links_for_sold' );
	delete_post_meta( $postinfo->ID, 'new_tab' );
}