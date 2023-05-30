=== DX Delete Attached Media ===
Contributors: devrix, nofearinc 
Tags: media, attachment, post
Requires at least: 4.5
Requires PHP: 7.3
Tested up to: 6.2
Stable tag: 2.0.5.1

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

== Upgrade Notice ==
= 1.0 =
* Extended support for WooCommerce and Easy Digital Downloads.

== Changelog ==

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
