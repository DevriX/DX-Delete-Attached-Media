=== DX Delete Attached Media ===
Contributors: devrix, nofearinc 
Tags: media, attachment, post
Requires at least: 4.5
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically deletes attached media from posts and custom post types added via the Media button.

== Description ==

[youtube https://www.youtube.com/watch?v=x51scLO71U0]

DX Delete Attached Media deletes all of the attached media files to your posts once they get deleted from the system. The standard core behavior deletes posts alone without taking care of related images. Now you can maintain your install and get rid of all solo attachments getting into your posts via the Media button and used nowhere else.

*The plugin works with WooCommerce and Easy Digital Downloads.*

== Screenshots ==
1. Insert newly uploaded media to your post.
2. Publish and then Delete your post.
3. You'll have to Delete your post Permanently.
4. The Attached Media is deleted.

== Installation ==

1. Upload 'dx-delete-attached-media' to the '/wp-content/plugins/' directory or find via the Plugins admin section
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's all. It would use out of the box, no settings needed. If you want to disable the functionality, disable the plugin from the Plugins section.


== Frequently Asked Questions ==

= Does it work for custom post types? =

Yes, as long as you attach images via the Media button, they are going to be deleted once the post/post type entry is deleted.

= Does it work for all of the deleted posts? =

It works for all of your deleted posts, as long as you delete them from the system. Keep in mind that the soft delete sends your post to Trash first, and deleting the media would happen once your Trash is being emptied. 

= Which media is kept? =

Files that are still used elsewhere are kept, and attached to a post that uses them. A file counts as used when it is the featured image of another post, when the file or any of its sizes appears in another post's content or excerpt (matched by its path in the uploads folder, so links from before a move to https or a new domain count too), or when it is listed in a classic [gallery] shortcode, an Easy Image Gallery gallery or a WooCommerce product gallery.

Every post type counts, including synced patterns, template parts and product variations, and every status except Trash. Uses that only store the file's ID somewhere else, such as custom fields or page builder data, aren't detected: add them with the filter below.

= Can I protect media used by another plugin, such as ACF image fields? =

Yes. Add the IDs of the posts that use the file with the `dx_dam_attachment_used_in` filter, which receives the list of post IDs and the attachment ID: `add_filter( 'dx_dam_attachment_used_in', 'my_media_uses', 10, 2 );`

= Can I keep the media of some posts? =

Yes. Return false from the `dx_dam_delete_post_attachments` filter, which receives `true` and the ID of the post being deleted, and that post's media stays in the library.

== Upgrade Notice ==
= 2.1.0 =
Media still used elsewhere is now kept. Media is also deleted when a post is deleted without the trash (WP-CLI, REST API, EMPTY_TRASH_DAYS 0, other plugins). To opt out, use the dx_dam_delete_post_attachments filter.

= 1.0 =
* Extended support for WooCommerce and Easy Digital Downloads.

== Changelog ==

= 2.1.0 =
* New: media that is still used elsewhere is no longer deleted. Besides featured images and files in post content, the plugin now checks excerpts, classic [gallery] shortcodes, Easy Image Gallery galleries, WooCommerce product galleries, posts of every status except Trash, and every post type, including synced patterns, template parts, product variations and post types of deactivated plugins. Files are matched by their path in the uploads folder, so links from before a move to https or a new domain count. Developers can add more with the dx_dam_attachment_used_in filter.
* New: the dx_dam_delete_post_attachments filter keeps the media of a post when it returns false. The delete callback is now named dx_dam_delete_attachments; code that removed the old delete_attachments callback should use this filter.
* New: the Trash screen shows how many media files are attached to each post.
* Fixed: opening the plugin's media list could detach media from posts whose post type wasn't registered at that moment (for example products while WooCommerce was deactivated), so that media was never deleted with its post. The list now only displays.
* Improved: the settings page uses the standard WordPress layout, and confirms changes on the page instead of pop-up alerts.
* Fixed: a settings change could be lost when saving took longer than a second (the page reloaded too early).
* Fixed: deleting a post without the trash (WP-CLI --force, the REST API, EMPTY_TRASH_DAYS set to 0, or another plugin deleting posts) left its media behind, orphaned. That media is now deleted too, unless it is used elsewhere.
* Fixed: the media list page stopped with a fatal error on PHP 8 when a media item had no file.
* Security: escaped admin output and validated the settings request.
* Prefixed the plugin's global functions and AJAX action to avoid conflicts with other plugins.
* Scripts and styles now load only on the plugin's own screens.
* Tested with WordPress 7.1, PHP 7.4 to 8.5.

= 2.0.6 = 
* Fixed vulnerability report

= 2.0.5.1 =
* Fix plugin conflict between DX DAM and other plugins  

= 2.0.5 = 
* Fixed plugin conflict with Post SMTP

= 2.0.4.1 =
* Revert to version 2.0.3

= 2.0.4 =
* Fixed plugin conflict with Post SMTP

= 2.0.3 =
* Fixed security issue
* Update tested-up version
* Update required PHP version

= 2.0.2 =
* Added default icon for other formats

= 2.0.1 =
* Added default options for the plugin filters settings
* Fixed the issue with the subfolder instalations

= 2.0 =
* Has been added separate menu for the plugin
* The plugin icon has been added
* On the main plugin page has been added a table with the following columns displaying: the media file, the parent post if is used, the other posts that contains it if is used
* On the main plugin page, a checkbox has been added for enabling/disabling the plugin’s functionality, called ‘Enable feature’.
* Added filter: by date (Newest/Oldest)
* Added filter: by Parent post (Used media/Unused media)
* A Help page has been added with instructions and screenshots describing the functionality of the plugin.

= 1.0.1 =
* Update the plugin version and Tested up to tag

= 1.0 =
* Extended support for WooCommerce and Easy Digital Downloads.

= 0.5 =
* Description update and version bump

= 0.4 =
* First release
