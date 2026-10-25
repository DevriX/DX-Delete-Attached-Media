<?php
/**
 * Plugin Name: DX Delete Attached Media
 * Plugin URI: https://wordpress.org/plugins/dx-delete-attached-media/
 * Description: Delete attached media to all posts (if activated). Remove images assigned to a post to clear old archives.
 * Author: nofearinc
 * Author URI: https://devrix.com
 * Version: 2.1.0
 * Text Domain: dx-delete-attached-media
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook( __FILE__, 'dx_dam_plugin_activate' );
function dx_dam_plugin_activate() {
	$dx_delete_attached_media_options = array(
		'enable-feature' => '1',
		'date_sort_new'  => '1',
		'date_sort_old'  => '0',
		'with_parent'    => '1',
		'without_parent' => '0',
	);

	update_option( 'dx_delete_attached_media_options', $dx_delete_attached_media_options );
}

$dx_delete_attached_media_options = get_option( 'dx_delete_attached_media_options' );

if ( ! isset( $dx_delete_attached_media_options['enable-feature'] ) || ! isset( $dx_delete_attached_media_options['date_sort_new'] ) ||
	! isset( $dx_delete_attached_media_options['date_sort_old'] ) || ! isset( $dx_delete_attached_media_options['with_parent'] ) ||
	! isset( $dx_delete_attached_media_options['without_parent'] ) ) {
	$dx_delete_attached_media_options = array(
		'enable-feature' => '1',
		'date_sort_new'  => '1',
		'date_sort_old'  => '0',
		'with_parent'    => '1',
		'without_parent' => '0',
	);

	update_option( 'dx_delete_attached_media_options', $dx_delete_attached_media_options );
}
/**
* if checked hooked the functions
*/
if ( '1' === $dx_delete_attached_media_options['enable-feature'] ) {
	add_action( 'before_delete_post', 'dx_dam_delete_attachments' );
	add_action( 'admin_notices', 'dx_dam_screen' );
	add_action( 'current_screen', 'dx_dam_trash_media_column' );
}

/**
 * On a Trash list, add a column with the number of media files attached to each post.
 *
 * @param WP_Screen $screen The current admin screen.
 */
function dx_dam_trash_media_column( $screen ) {
	// Read-only check of the current list view, so no nonce is involved.
	if ( 'edit' !== $screen->base || ! isset( $_GET['post_status'] ) || 'trash' !== sanitize_key( wp_unslash( $_GET['post_status'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	add_filter(
		'manage_' . $screen->post_type . '_posts_columns',
		function ( $columns ) {
			$columns['dx_dam_media'] = esc_html__( 'Attached media', 'dx-delete-attached-media' );
			return $columns;
		}
	);
	add_action(
		'manage_' . $screen->post_type . '_posts_custom_column',
		function ( $column, $post_id ) {
			if ( 'dx_dam_media' !== $column ) {
				return;
			}
			$count = count(
				get_children(
					array(
						'post_parent' => $post_id,
						'post_type'   => 'attachment',
						'fields'      => 'ids',
					)
				)
			);
			if ( 0 === $count ) {
				echo '&mdash;';
				return;
			}
			/* translators: %s: number of media files attached to the post. */
			printf( esc_html( _n( 'Up to %s file', 'Up to %s files', $count, 'dx-delete-attached-media' ) ), esc_html( number_format_i18n( $count ) ) );
		},
		10,
		2
	);
}


/**
 * Make the plugin translatable ready
 *
 * @since   1.0.2
 */
add_action( 'plugins_loaded', 'dx_dam_domain' );
function dx_dam_domain() {
	load_plugin_textdomain( 'dx-delete-attached-media', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}

/**
 * Posts that still use an attachment, so it must not be deleted.
 *
 * Checks featured images; the file, the original of a scaled image and every generated size in the content
 * or excerpt of other posts; classic [gallery] shortcodes; Easy Image Gallery galleries and WooCommerce
 * product galleries. Posts of every type count (synced patterns, template parts, product variations, and
 * post types of deactivated plugins too) and every status except trash and auto-draft; revisions and other
 * attachments don't. The queries read the database directly, so language, search and other query filters
 * can't hide a use. Other plugins can add their own uses through the dx_dam_attachment_used_in filter.
 *
 * @param int $attachment_id Attachment ID.
 * @return int[] IDs of the posts using it.
 */
function dx_get_posts_by_attachment_id( $attachment_id ) {
	global $wpdb;

	$attachment_id = (int) $attachment_id;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery -- live data is needed while deleting, and query filters must not hide uses.

	// Featured images.
	$used_in_posts = $wpdb->get_col(
		$wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s", (string) $attachment_id )
	);

	// The file in other posts, matched by its path inside the uploads folder as well as by its URLs, so links
	// written before a move to https, a new domain or a CDN still count. One table scan for all of them.
	$conditions = array();
	foreach ( dx_dam_attachment_file_references( $attachment_id ) as $reference ) {
		$like         = '%' . $wpdb->esc_like( $reference ) . '%';
		$conditions[] = $wpdb->prepare( 'post_content LIKE %s OR post_excerpt LIKE %s', $like, $like );
	}
	if ( $conditions ) {
		$used_in_posts = array_merge(
			$used_in_posts,
			$wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type NOT IN ( 'revision', 'attachment' ) AND ( " . implode( ' OR ', $conditions ) . ' )' ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- every condition is prepared above.
		);
	}

	// Galleries kept in post meta: Easy Image Gallery (both formats) and WooCommerce product galleries.
	// LIKE narrows the candidates; only an exact ID match counts (115 must not match 1150).
	$gallery_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ( '_easy_image_gallery_v2', '_easy_image_gallery', '_product_image_gallery' ) AND meta_value LIKE %s",
			'%' . $wpdb->esc_like( (string) $attachment_id ) . '%'
		)
	);
	foreach ( $gallery_rows as $row ) {
		if ( in_array( $attachment_id, dx_dam_ids_in_gallery_value( $row->meta_value ), true ) ) {
			$used_in_posts[] = $row->post_id;
		}
	}

	// Classic [gallery ids="..."] shortcodes: LIKE narrows the candidates, the shortcode parser decides.
	$shortcode_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type NOT IN ( 'revision', 'attachment' ) AND post_content LIKE %s AND post_content LIKE %s",
			'%' . $wpdb->esc_like( '[gallery' ) . '%',
			'%' . $wpdb->esc_like( (string) $attachment_id ) . '%'
		)
	);
	foreach ( $shortcode_rows as $row ) {
		if ( in_array( $attachment_id, dx_dam_gallery_shortcode_ids( $row->post_content ), true ) ) {
			$used_in_posts[] = $row->ID;
		}
	}

	// phpcs:enable WordPress.DB.DirectDatabaseQuery

	/**
	 * Filters the posts that use an attachment. Attachments used by any post are kept and moved to it.
	 *
	 * @param int[] $used_in_posts IDs of the posts using the attachment.
	 * @param int   $attachment_id Attachment ID.
	 */
	$used_in_posts = apply_filters( 'dx_dam_attachment_used_in', $used_in_posts, $attachment_id );

	// Drop posts deleted earlier in this request (e.g. while emptying the trash), trashed posts and auto-drafts,
	// revisions, and attachments: media is never moved under another attachment.
	$used_in_posts = array_filter(
		array_unique( array_map( 'intval', (array) $used_in_posts ) ),
		function ( $post_id ) {
			$post = get_post( $post_id );
			return $post
				&& ! in_array( $post->post_status, array( 'trash', 'auto-draft' ), true )
				&& ! in_array( $post->post_type, array( 'revision', 'attachment' ), true );
		}
	);

	return array_values( $used_in_posts );
}

/**
 * Strings that identify an attachment's files in post content: the paths inside the uploads folder of the
 * file, of the original of a scaled image and of every generated size, plus their URLs.
 *
 * @param int $attachment_id Attachment ID.
 * @return string[]
 */
function dx_dam_attachment_file_references( $attachment_id ) {
	$references = array();
	$file       = get_post_meta( $attachment_id, '_wp_attached_file', true );

	if ( is_string( $file ) && '' !== $file ) {
		$file         = _wp_relative_upload_path( $file );
		$dir          = dirname( $file );
		$dir          = '.' === $dir ? '' : trailingslashit( $dir );
		$references[] = $file;

		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $metadata['original_image'] ) ) {
			$references[] = $dir . $metadata['original_image'];
		}
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size ) {
				if ( ! empty( $size['file'] ) ) {
					$references[] = $dir . $size['file'];
				}
			}
		}
	}

	// The URLs as WordPress serves them now, for files outside the uploads folder or rewritten by a plugin.
	$references[] = wp_get_attachment_url( $attachment_id );
	if ( wp_attachment_is_image( $attachment_id ) ) {
		foreach ( get_intermediate_image_sizes() as $size ) {
			$intermediate = image_get_intermediate_size( $attachment_id, $size );
			if ( $intermediate ) {
				$references[] = $intermediate['url'];
			}
		}
	}

	// An empty string would match every post (LIKE '%%'), so no file would ever be deleted.
	return array_values(
		array_unique(
			array_filter(
				$references,
				function ( $reference ) {
					return is_string( $reference ) && '' !== $reference;
				}
			)
		)
	);
}

/**
 * Attachment IDs in a stored gallery value: Easy Image Gallery's serialized galleries (IDs under DATA),
 * or a comma-separated list (Easy Image Gallery 1.x, WooCommerce product galleries).
 *
 * @param mixed $value The meta value.
 * @return int[]
 */
function dx_dam_ids_in_gallery_value( $value ) {
	$value = maybe_unserialize( $value );

	if ( is_array( $value ) ) {
		$ids = array();
		foreach ( $value as $gallery ) {
			if ( is_array( $gallery ) && isset( $gallery['DATA'] ) ) {
				$ids = array_merge( $ids, wp_parse_id_list( $gallery['DATA'] ) );
			}
		}
		return $ids;
	}

	return wp_parse_id_list( (string) $value );
}

/**
 * Attachment IDs listed in the classic [gallery ids="..."] (or older include="...") shortcodes of a text.
 *
 * @param string $content Post content.
 * @return int[]
 */
function dx_dam_gallery_shortcode_ids( $content ) {
	$ids = array();

	if ( ! preg_match_all( '/' . get_shortcode_regex( array( 'gallery' ) ) . '/', $content, $matches ) ) {
		return $ids;
	}

	foreach ( $matches[3] as $attributes ) {
		$atts = shortcode_parse_atts( $attributes );
		foreach ( array( 'ids', 'include' ) as $key ) {
			if ( ! empty( $atts[ $key ] ) ) {
				$ids = array_merge( $ids, wp_parse_id_list( $atts[ $key ] ) );
			}
		}
	}

	return $ids;
}

/**
 * Delete attachments if no post is using the attachment
 * Checks if attachment is used in post and changes the parent id if the original post is deleted.
 */
function dx_dam_delete_attachments( $post_id ) {
	/**
	 * Filters whether to delete the media attached to a post that is being deleted permanently.
	 *
	 * Return false to keep it, for example while another plugin replaces posts programmatically.
	 * Until 2.0.6 this callback was named delete_attachments, so remove_action() calls that used that
	 * name no longer apply; this filter is the stable way to opt out.
	 *
	 * @since 2.1.0
	 *
	 * @param bool $delete  Whether to delete the post's attached media. Default true.
	 * @param int  $post_id ID of the post being deleted.
	 */
	if ( ! apply_filters( 'dx_dam_delete_post_attachments', true, $post_id ) ) {
		return;
	}

	$custom      = get_post_custom( $post_id );
	$args        = array(
		'post_type'   => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'post_parent' => $post_id,
	);
	$attachments = get_posts( $args );

	if ( $attachments ) {
		foreach ( $attachments as $attachment ) {
			// The post being deleted is not a use: on a direct (non-trash) delete it still matches its own media.
			$used_in_posts = array_values( array_diff( dx_get_posts_by_attachment_id( $attachment->ID ), array( $post_id ) ) );
			if ( ! empty( $used_in_posts ) ) {
				$args = array(
					'ID'          => $attachment->ID,
					'post_parent' => $used_in_posts[0],
				);
				wp_update_post( $args );

			} else {
				wp_delete_attachment( $attachment->ID, true );
			}
		}
	}
}

/**
 * Run activation hook for custom message
 *
 * @since   1.0.2
 */
register_activation_hook( __FILE__, 'dx_dam_acivate' );
function dx_dam_acivate() {
	/**
	 * admin_notices hook does not run here
	 *
	 * Adding transient which will be used on admin_notices right away is the alternative
	 */
	set_transient( 'dx_dam_activation', true, 5 );
}

/**
 * Add welcome message once the plugn has been activated for the first time
 *
 * It will inform the user of potential data loss and backup message as well
 *
 * @since   1.0.2
 */
add_action( 'admin_notices', 'dx_dam_activation_welcome' );
function dx_dam_activation_welcome() {
	if ( is_admin() && get_transient( 'dx_dam_activation' ) ) {
		?>
		<div class="updated notice is-dismissible">
			<h1 class="heading"><?php esc_html_e( 'Thank you for installing DX Delete Attached Media!', 'dx-delete-attached-media' ); ?></h1>
			<p class="description">
				<?php
				printf(
					'%s<strong>%s</strong>%s.',
					esc_html__( 'This plugin deletes all attached media to a post once you have decided to delete it permanently. Please, always create a site ', 'dx-delete-attached-media' ),
					esc_html__( 'backup', 'dx-delete-attached-media' ),
					esc_html__( ' before deleting a post', 'dx-delete-attached-media' )
				);
				?>
			</p>
		</div>
		<?php
		delete_transient( 'dx_dam_activation' );
	}
}

/**
 * Add options page for this plugin and help page
 *
 * @since   1.0.2
 */
add_action( 'admin_menu', 'dx_dam_page' );
function dx_dam_page() {
	add_menu_page(
		__( 'DX Delete Attached Media', 'dx-delete-attached-media' ),
		__( 'DX Delete Attached Media', 'dx-delete-attached-media' ),
		'manage_options',
		'dx-dam-options',
		'dx_dam_page_callback',
		'dashicons-images-alt2'
	);

	add_submenu_page( 'dx-dam-options', __( 'Help page', 'dx-delete-attached-media' ), __( 'Help page', 'dx-delete-attached-media' ), 'manage_options', 'dx_dam_help', 'dx_dam_help_callback' );
}

/**
 * Plugins help page include
 *
 * @return void
 */
function dx_dam_help_callback() {
	include 'templates/dx-dam-help-page.php';
}


/**
 * Plugins option page
 *
 * List all post type attachment that can be deleted
 *
 * @since   1.0.2
 */
function dx_dam_page_callback() {
	$dx_delete_attached_media_options = get_option( 'dx_delete_attached_media_options' );

	?>
<div class="dx-template-wrapper">
	<div class="wrap content-wrapper">
		<h1 class="heading"><?php esc_html_e( 'DX Delete Attached Media', 'dx-delete-attached-media' ); ?></h1>
		<p class="description"><?php esc_html_e( 'This is a list of attachements that will be deleted once you have opted to delete the parent post permanently. Always make a site backup before deleting a post.', 'dx-delete-attached-media' ); ?></p>
		<?php
		if ( current_user_can( 'manage_options' ) ) :
			$checkbox_enable_feature_settings = '<input type="checkbox" id="enable-feature" name="enable-feature" value="1" ' . checked( 1, $dx_delete_attached_media_options['enable-feature'], false ) . '/>';

			$sort_date_newest = '<input type="radio" id="date_sort_new" name="date_sort_new" value="1" ' . checked( 1, $dx_delete_attached_media_options['date_sort_new'], false ) . '/>';
			$sort_date_oldest = '<input type="radio" id="date_sort_old" name="date_sort_old" value="1" ' . checked( 1, $dx_delete_attached_media_options['date_sort_old'], false ) . '/>';
			$parent_with      = '<input type="radio" id="with_parent" name="with_parent" value="1" ' . checked( 1, $dx_delete_attached_media_options['with_parent'], false ) . '/>';
			$dx_dam_input_html = array(
				'input' => array( 'type' => true, 'id' => true, 'name' => true, 'value' => true, 'checked' => true ),
			);
			$parent_without   = '<input type="radio" id="without_parent" name="without_parent" value="1" ' . checked( 1, $dx_delete_attached_media_options['without_parent'], false ) . '/>';

			?>
			<div id="dx-dam-status" class="notice inline" role="status" style="display:none"><p></p></div>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Delete attached media', 'dx-delete-attached-media' ); ?></th>
					<td><label for="enable-feature"><?php echo wp_kses( $checkbox_enable_feature_settings, $dx_dam_input_html ); ?> <?php esc_html_e( 'Delete a post\'s attached media when the post is deleted permanently', 'dx-delete-attached-media' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sort the list below', 'dx-delete-attached-media' ); ?></th>
					<td>
						<label for="date_sort_new"><?php echo wp_kses( $sort_date_newest, $dx_dam_input_html ); ?> <?php esc_html_e( 'Newest first', 'dx-delete-attached-media' ); ?></label><br />
						<label for="date_sort_old"><?php echo wp_kses( $sort_date_oldest, $dx_dam_input_html ); ?> <?php esc_html_e( 'Oldest first', 'dx-delete-attached-media' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show', 'dx-delete-attached-media' ); ?></th>
					<td>
						<label for="with_parent"><?php echo wp_kses( $parent_with, $dx_dam_input_html ); ?> <?php esc_html_e( 'Used media (attached to a post)', 'dx-delete-attached-media' ); ?></label><br />
						<label for="without_parent"><?php echo wp_kses( $parent_without, $dx_dam_input_html ); ?> <?php esc_html_e( 'Unused media (not attached to any post)', 'dx-delete-attached-media' ); ?></label>
					</td>
				</tr>
			</table>
			<?php
		endif;
		include_once plugin_dir_path( __FILE__ ) . '/dx-attachments-list.php';
		?>
	</div>
</div>
	<?php
}

/**
 * Hook an admin_notice() whenever the user is in the Trash table
 *
 * @since   1.0.2
 */
function dx_dam_screen() {
	if ( ! is_admin() ) {
		return;
	}

	$screen = get_current_screen();

	// Read-only check of the current list view, so no nonce is involved.
	if ( isset( $_GET['post_status'] ) && isset( $screen->base ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'edit' === $screen->base && 'trash' === sanitize_key( wp_unslash( $_GET['post_status'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="updated notice">
				<p class="description">
					<?php
					printf(
						'%s<strong>%s</strong>%s.',
						esc_html__( 'Howdy! DX Delete Attached Media deletes the media attached to a post when you delete the post permanently. Files still used elsewhere (featured images, galleries, other posts) are kept. Please, always create a site ', 'dx-delete-attached-media' ),
						esc_html__( 'backup', 'dx-delete-attached-media' ),
						esc_html__( ' before deleting a post', 'dx-delete-attached-media' )
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}

/**
 * Add jQuery and CSS
 */
add_action( 'admin_enqueue_scripts', 'add_dx_dam_js' );
function add_dx_dam_js( $hook ) {
	// Only the plugin's own screens use these; elsewhere they were dead weight under a generic 'main' handle.
	if ( false === strpos( $hook, 'dx-dam-options' ) && false === strpos( $hook, 'dx_dam_help' ) ) {
		return;
	}
	wp_enqueue_script( 'jquery' );
	wp_register_script( 'dx-dam-script', plugins_url( '/assets/js/dx-dam-script.js', __FILE__ ), array( 'jquery' ), '2.1.0', true );
	wp_enqueue_script( 'dx-dam-script' );
	wp_localize_script(
		'dx-dam-script',
		'DX_DAM',
		array(
			'url'    => admin_url( 'admin-ajax.php' ),
			'nonce'  => wp_create_nonce( 'dx-dam-nonce' ),
			'saving' => __( 'Saving...', 'dx-delete-attached-media' ),
			'saved'  => __( 'Saved. Updating the list...', 'dx-delete-attached-media' ),
			'failed' => __( 'Sorry, something is wrong. Please try again.', 'dx-delete-attached-media' ),
		)
	);
	wp_enqueue_style( 'dx-dam-main', plugin_dir_url( __FILE__ ) . '/assets/css/main.css', array(), '2.1.0' );
}

add_action( 'init', 'dxdam_admin_user' );
add_action( 'after_setup_theme', 'dxdam_admin_user' );
/**
 * Check if the current user is administrator.
 */
function dxdam_admin_user() {
	if ( current_user_can( 'manage_options' ) ) {
		add_action( 'wp_ajax_dx_dam_add_to_base', 'dx_dam_add_to_base', 1 );
	}
}

/**
 * Add to base the value of the checkbox/radio button
 */
function dx_dam_add_to_base() {
	if ( ! isset( $_POST['data'] ) || ! is_array( $_POST['data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is inside data, checked below.
		wp_send_json_error( array( 'message' => __( 'Sorry, something is wrong. Please try again.', 'dx-delete-attached-media' ) ), 400 );
	}
	$dxdam_data = map_deep( wp_unslash( $_POST['data'] ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! isset( $dxdam_data['nonce'] ) || ! wp_verify_nonce( $dxdam_data['nonce'], 'dx-dam-nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Sorry, something is wrong. Please try again.', 'dx-delete-attached-media' ) ), 500 );
	}

	$dx_delete_attached_media_options = get_option( 'dx_delete_attached_media_options' );
	if ( isset( $_POST['data'] ) ) {
		if ( isset( $dxdam_data['enable-feature'] ) ) {
			$dx_delete_attached_media_options['enable-feature'] = esc_html( $dxdam_data['enable-feature'] );
		}
		if ( isset( $dxdam_data['date_sort_new'] ) ) {
			$dx_delete_attached_media_options['date_sort_new'] = esc_html( $dxdam_data['date_sort_new'] );
		}
		if ( isset( $dxdam_data['date_sort_old'] ) ) {
			$dx_delete_attached_media_options['date_sort_old'] = esc_html( $dxdam_data['date_sort_old'] );
		}
		if ( isset( $dxdam_data['with_parent'] ) ) {
			$dx_delete_attached_media_options['with_parent'] = esc_html( $dxdam_data['with_parent'] );
		}
		if ( isset( $dxdam_data['without_parent'] ) ) {
			$dx_delete_attached_media_options['without_parent'] = esc_html( $dxdam_data['without_parent'] );
		}
		update_option( 'dx_delete_attached_media_options', $dx_delete_attached_media_options );
		wp_send_json_success();
	}
}
