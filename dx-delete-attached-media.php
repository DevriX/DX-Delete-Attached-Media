<?php
/**
 * Plugin Name: DX Delete Attached Media
 * Plugin URI: http://wordpress.org/extend/plugins/dx-delete-attached-media/
 * Description: Delete attached media to all posts (if activated). Remove images assigned to a post to clear old archives.
 * Author: nofearinc
 * Author URI: http://devrix.com
 * Version: 0.5
 */
// TODO: options for delete_hook or trash_hook
// TODO: option for admin panel or single post/page

function delete_attachments($post_id) {
	$args = array(
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'post_parent' => $post_id
	);
	$attachments = get_posts($args);
	
	if ($attachments) {
		foreach($attachments as $attachment) {
			wp_delete_attachment($attachment->ID, true);
			
		}
	}
}

add_action('before_delete_post', 'delete_attachments');
