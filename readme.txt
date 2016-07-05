=== DX Delete Attached Media ===
Contributors: devrix, nofearinc 
Donate link: http://devrix.com
Tags: media, attachment, post
Requires at least: 3.0
Tested up to: 4.0
Stable tag: 0.5

Automatically deletes attached media from posts and custom post types added via the Media button.

== Description ==

DX Delete Attached Media deletes all of the attached media files to your posts once they get deleted from the system. The standard core behavior deletes posts alone without taking care of related images. Now you can maintain your install and get rid of all solo attachments getting into your posts via the Media button and used nowhere else.

== Installation ==

1. Upload 'dx-delete-attached-media' to the '/wp-content/plugins/' directory or find via the Plugins admin section
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's all. It would use out of the box, no settings needed. If you want to disable the functionality, disable the plugin from the Plugins section.


== Frequently Asked Questions ==

= Does it work for custom post types? =

Yes, as long as you attach images via the Media button, they are going to be deleted once the post/post type entry is deleted.

= Does it work for all of the deleted posts? =

It works for all of your deleted posts, as long as you delete them from the system. Keep in mind that the soft delete sends your post to Trash first, and deleting the media would happen once your Trash is being emptied. 

== Changelog ==

= 0.5 =
* Description update and version bump

= 0.4 =
* First release
