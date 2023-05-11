<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class DX_DAM_List extends WP_List_Table {

	/**
	 * Total posts
	 *
	 * @since 1.0.2
	 */
	private $count;

	// Constructor
	function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => __( 'attachment', 'dx-delete-attached-media' ),
				'plural'   => __( 'attachments', 'dx-delete-attached-media' ),
				'ajax'     => false,
			)
		);

		// Initialize data
		$this->get_data();
	}

	/**
	 * Get the attachment using WP_Query
	 *
	 * @since 1.0.2
	 */
	public function get_data( $current_page = 1 ): array {
		$data = array();

		$dx_delete_attached_media_options = get_option( 'dx_delete_attached_media_options' );

		/**
		 * Get all attachments
		 *
		 * We delete based on this param
		 */
		$post_parent = 'post_parent__not_in';
		$sort_order  = 'ASC';

		if ( '0' === $dx_delete_attached_media_options['with_parent'] ) {
			$post_parent = 'post_parent__in';
		}
		if ( '1' === $dx_delete_attached_media_options['date_sort_new'] ) {
			$sort_order = 'DESC';
		}

		$attachements = new WP_Query(
			array(
				'post_type'     => 'attachment',
				'post_status'   => 'inherit',
				'post_per_page' => 10,
				'paged'         => $current_page,
				$post_parent    => array( 0 ),
				'order_by'      => 'date',
				'order'         => $sort_order,
			)
		);

		if ( $attachements->post_count > 0 ) {
			foreach ( $attachements->posts as $attchement ) {
				$used_in_content = array();
				$attachment_id   = $attchement->ID;
				$attachment_urls = array( wp_get_attachment_url( $attachment_id ) );

				if ( wp_attachment_is_image( $attachment_id ) ) {
					foreach ( get_intermediate_image_sizes() as $size ) {
						$intermediate = image_get_intermediate_size( $attachment_id, $size );
						if ( $intermediate ) {
							$attachment_urls[] = $intermediate['url'];
						}
					}
				}

				$used_in_content = array();
				/**
				 * Get all post types that have attached media in content
				 */
				foreach ( $attachment_urls as $attachment_url ) {
					$content_query = new WP_Query(
						array(
							's'              => $attachment_url,
							'post_type'      => get_post_types(),
							'fields'         => 'ids',
							'no_found_rows'  => true,
							'posts_per_page' => -1,
							'order'          => 'ASC',
						)
					);

					$used_in_content = array_merge( $used_in_content, $content_query->posts );
				}

				$used_as_thumbnail = array();

				/**
				 * Get all post types that have attached media as featured image
				 */
				if ( wp_attachment_is_image( $attachment_id ) ) {
					$thumbnail_query = new WP_Query(
						array(
							'meta_key'       => '_thumbnail_id',
							'meta_value'     => $attachment_id,
							'post_type'      => get_post_types(),
							'fields'         => 'ids',
							'no_found_rows'  => true,
							'posts_per_page' => -1,
							'order'          => 'ASC',
						)
					);

					$used_as_thumbnail = $thumbnail_query->posts;
				}

				$used_in_content      = array_unique( $used_in_content );
				$used_as_thumbnail    = array_unique( $used_as_thumbnail );
				$attachment_ancestors = get_post_ancestors( $attachment_id );

				$merged_array = array_merge( $used_in_content, $used_as_thumbnail, $attachment_ancestors );
				$merged_array = array_unique( $merged_array );

				foreach ( $merged_array as $post ) {
					if ( ! post_type_exists( get_post_type( $post ) ) ) {
						array_splice( $merged_array, array_search( $post, $merged_array, true ) );
					}
				}
				
				if ( ! in_array( $attchement->post_parent, $merged_array, true ) ) {
					if ( ! empty( $merged_array ) ) {
						$attchement->post_parent = $merged_array[0];
						wp_update_post(
							array(
								'ID'          => $attchement->ID,
								'post_parent' => 1,
							)
						);
					} else {
						wp_update_post(
							array(
								'ID'          => $attchement->ID,
								'post_parent' => 0,
							)
						);
					}
				}
				/**
				 * If the media is used in one or more posts
				 */
				if ( ! empty( $attchement->post_parent ) && file_is_valid_image( get_attached_file( $attchement->ID ) ) ) {
					$data[] = array(
						'post_title'  => '<a href="' . admin_url() . 'post.php?post=' . $attchement->ID . '&action=edit' . '">' . '<img src="' . wp_get_attachment_thumb_url( $attchement->ID ) . '" width="50px" />',
						'post_parent' => '<a href="' . get_permalink( $attchement->post_parent ) . '"><b>' . get_the_title( $attchement->post_parent ) . '</b></a>',
						'all_posts'   => $merged_array,
					);
				} elseif ( ! empty( $attchement->post_parent ) && ! file_is_valid_image( get_attached_file( $attchement->ID ) ) ) {
					$data[] = array(
						'post_title'  => '<a href="' . admin_url() . 'post.php?post=' . $attchement->ID . '&action=edit' . '">' . '<span class="dashicons dashicons-media-document"></span>',
						'post_parent' => '<a href="' . get_permalink( $attchement->post_parent ) . '"><b>' . get_the_title( $attchement->post_parent ) . '</b></a>',
						'all_posts'   => $merged_array,
					);
				} elseif ( empty( $attchement->post_parent ) && ! file_is_valid_image( get_attached_file( $attchement->ID ) ) ) {
					$data[] = array(
						'post_title'  => '<a href="' . admin_url() . 'post.php?post=' . $attchement->ID . '&action=edit' . '">' . '<span class="dashicons dashicons-media-document"></span>',
						'post_parent' => '<span class="dx-table-bold">Unused file</span>',
						'all_posts'   => $merged_array,
					);
				} else {
					$data[] = array(
						'post_title'  => '<a href="' . admin_url() . 'post.php?post=' . $attchement->ID . '&action=edit' . '">' . '<img src="' . wp_get_attachment_thumb_url( $attchement->ID ) . '" width="50px" />',
						'post_parent' => '<span class="dx-table-bold">Unused media</span>',
						'all_posts'   => $merged_array,
					);
				}
			}

			$this->count = $attachements->found_posts;
		}
		return $data;
	}

	/**
	 * Default test to be shown if no attachments found
	 *
	 * @since 1.02
	 */
	public function no_items() {
		esc_html_e( 'No attachments found..', 'dx-delete-attached-media' );
	}

	/**
	 * Default columns
	 *
	 * Nothing cancy here
	 *
	 * @since   1.0.2
	 */
	public function get_columns() {
		$columns = array(
			'post_title'  => __( 'Preview', 'dx-delete-attached-media' ),
			'post_parent' => __( 'Parent Post', 'dx-delete-attached-media' ),
			'all_posts'   => __( 'All posts with this media', 'dx-delete-attached-media' ),
		);
		return $columns;
	}

	/**
	 * Map the columns and data
	 *
	 * @since 1.0.2
	 */
	public function column_default( $attachment_id, $context ) {

		switch ( $context ) {
			case 'post_title':
			case 'post_parent':
				return $attachment_id[ $context ];
			case 'all_posts':
				$data = '';
				foreach ( $attachment_id[ $context ] as $current_post_id ) {
					$data .= '<p><span class="dx-table-bold"><a href="' . get_permalink( $current_post_id ) . '">' . get_the_title( $current_post_id ) . '</a></span></p> ';
				}

				if ( ! $data && '<span class="dx-table-bold">Unused file</span>' === $attachment_id['post_parent'] ) {
					$data .= '<span class="dx-table-bold">There are no posts with this file</span>';
				}

				if ( ! $data && '<span class="dx-table-bold">Unused media</span>' === $attachment_id['post_parent'] ) {
					$data .= '<span class="dx-table-bold">There are no posts with this media</span>';
				}

				return $data;
		}
	}

	/**
	 * Prepare items
	 *
	 * @since 1.0.2
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$this->_column_headers = array( $columns, $hidden );

		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$total_items  = $this->count;
		$data         = $this->get_data( $current_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = $data;
	}
}

$attachment_table = new DX_DAM_List();
$attachment_table->prepare_items();
$attachment_table->display();
