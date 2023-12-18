<?php
/**
 * Plugin Name: DX Delete Attached Media
 * Plugin URI: https://wordpress.org/plugins/dx-delete-attached-media/
 * Description: Delete attached media to all posts (if activated). Remove images assigned to a post to clear old archives.
 * Author: nofearinc
 * Author URI: https://devrix.com
 * Version: 2.0.6
 */

register_activation_hook( __FILE__, 'am_plugin_activate' );
function am_plugin_activate() {
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
	add_action( 'before_delete_post', 'delete_attachments' );
	add_action( 'admin_notices', 'dx_dam_screen' );
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

function dx_get_posts_by_attachment_id( $attachment_id ) {

	$used_in_posts = array();

	if ( wp_attachment_is_image( $attachment_id ) ) {
		$query         = new WP_Query(
			array(
				'meta_key'       => '_thumbnail_id',
				'meta_value'     => $attachment_id,
				'post_type'      => 'any',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'posts_per_page' => -1,
			)
		);
		$used_in_posts = array_merge( $used_in_posts, $query->posts );
	}

	$attachment_urls = array( wp_get_attachment_url( $attachment_id ) );

	if ( wp_attachment_is_image( $attachment_id ) ) {
		foreach ( get_intermediate_image_sizes() as $size ) {
			$intermediate = image_get_intermediate_size( $attachment_id, $size );
			if ( $intermediate ) {
				$attachment_urls[] = $intermediate['url'];
			}
		}
	}

	foreach ( $attachment_urls as $attachment_url ) {
		$query         = new WP_Query(
			array(
				's'              => $attachment_url,
				'post_type'      => 'any',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'posts_per_page' => -1,
			)
		);
		$used_in_posts = array_merge( $used_in_posts, $query->posts );
	}
	$used_in_posts = array_unique( $used_in_posts );

	return $used_in_posts;
}

/**
 * Delete attachments if no post is using the attachment
 * Checks if attachment is used in post and changes the parent id if the original post is deleted.
 */
function delete_attachments( $post_id ) {
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
			$used_in_posts = dx_get_posts_by_attachment_id( $attachment->ID );
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
			<h1 class="heading"><?php _e( 'Thank you for installing DX Delete Attached Media!', 'dx-delete-attached-media' ); ?></h1>
			<p class="description">
				<?php
				printf(
					'%s<strong>%s</strong>%s.',
					__( 'This plugin deletes all attached media to a post once you have decided to delete it permanently. Please, always create a site ', 'dx-delete-attached-media' ),
					__( 'backup', 'dx-delete-attached-media' ),
					__( ' before deleting a post', 'dx-delete-attached-media' )
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

	add_submenu_page( 'dx-dam-options', __( 'Help page', 'dx-dam-help-page' ), __( 'Help page', 'dx-dam-help-page' ), 'manage_options', 'dx_dam_help', 'dx_dam_help_callback' );
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
		<h1 class="heading"><?php _e( 'DX Delete Attached Media', 'dx-delete-attached-media' ); ?></h1>
		<p class="description"><?php _e( 'This is a list of attachements that will be deleted once you have opted to delete the parent post permanently. Always make a site backup before deleting a post.', 'dx-delete-attached-media' ); ?></p>
		<?php
		if ( current_user_can( 'manage_options' ) ) :
			$checkbox_enable_feature_settings = '<input type="checkbox" id="enable-feature" name="enable-feature" value="1" ' . checked( 1, $dx_delete_attached_media_options['enable-feature'], false ) . '/>';

			$sort_date_newest = '<input type="radio" id="date_sort_new" name="date_sort_new" value="1" ' . checked( 1, $dx_delete_attached_media_options['date_sort_new'], false ) . '/>';
			$sort_date_oldest = '<input type="radio" id="date_sort_old" name="date_sort_old" value="1" ' . checked( 1, $dx_delete_attached_media_options['date_sort_old'], false ) . '/>';
			$parent_with      = '<input type="radio" id="with_parent" name="with_parent" value="1" ' . checked( 1, $dx_delete_attached_media_options['with_parent'], false ) . '/>';
			$parent_without   = '<input type="radio" id="without_parent" name="without_parent" value="1" ' . checked( 1, $dx_delete_attached_media_options['without_parent'], false ) . '/>';

			?>
			<html>
				<head>
				<style>
				table {
				font-family: arial, sans-serif;
				border-collapse: collapse;
				}

				td, th {
				text-align: left;
				padding: 8px;
				}

				tr:nth-child(even) {
				background-color: #dddddd;
				}
				</style>
				</head>
				<body>

				<h2><?php echo $checkbox_enable_feature_settings; ?>Enable feature</h2>

				<table>
				<tr>
					<h3><th>Filters</th></h3>
					<th></th>
					<th></th>
				</tr>
				<tr>
					<th>Media files</th>
					<th></th>
					<th></th>
				</tr>
				<tr>
					<td>Date added</td>
					<td><?php echo $sort_date_newest; ?>Newest</td>
					<td><?php echo $sort_date_oldest; ?>Oldest</td>
				</tr>
				<tr>
					<td>Parent post</td>
					<td><?php echo $parent_with; ?>Used media</td>
					<td><?php echo $parent_without; ?>Unused media</td>
				</tr>

				</table>

				</body>
				</html>

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

	if ( isset( $_GET['post_status'] ) && isset( $screen->base ) ) {
		if ( 'edit' === $screen->base && 'trash' === $_GET['post_status'] ) {
			?>
			<div class="updated notice">
				<p class="description">
					<?php
					printf(
						'%s<strong>%s</strong>%s.',
						__( 'Howdy!, You are using DX Delete Attached Media plugin. It deletes all attached media to a post once you have decided to delete the post permanently. Please, always create a site ', 'dx-delete-attached-media' ),
						__( 'backup', 'dx-delete-attached-media' ),
						__( ' before deleting a post', 'dx-delete-attached-media' )
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
	wp_enqueue_script( 'jquery' );
	wp_register_script( 'dx-dam-script', plugins_url( '/assets/js/dx-dam-script.js', __FILE__ ), array( 'jquery' ), '2.0', true );
	wp_enqueue_script( 'dx-dam-script' );
	wp_localize_script(
		'dx-dam-script',
		'DX_DAM',
		array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'dx-dam-nonce' ),
		)
	);
	wp_enqueue_style( 'main', plugin_dir_url( __FILE__ ) . '/assets/css/main.css', array(), '1.0' );

	if ( 'toplevel_page_dx-dam-options' === $hook ) {
		wp_enqueue_style( 'main', plugin_dir_url( __FILE__ ) . '/assets/css/main.css', array(), '1.1' );
	}
}

add_action( 'init', 'dxdam_admin_user' );
add_action( 'after_setup_theme', 'dxdam_admin_user' );
/**
 * Check if the current user is administrator.
 */
function dxdam_admin_user() {
	if ( current_user_can( 'manage_options' ) ) {
		add_action( 'wp_ajax_add_to_base', 'add_to_base', 1 );
	}
}

/**
 * Add to base the value of the checkbox/radio button
 */
function add_to_base() {
	$dxdam_data = wp_unslash( $_POST['data'] );
	if ( ! wp_verify_nonce( $dxdam_data['nonce'], 'dx-dam-nonce' ) ) {
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
