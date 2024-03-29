<?php
/**
 * SmartQa upload handler.
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-3.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 * @since     4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SmartQa upload hooks.
 */
class SmartQa_Uploader {

	/**
	 * Delete question or answer attachment.
	 */
	public static function delete_attachment() {
		$attachment_id = asqa_sanitize_unslash( 'attachment_id', 'r' );

		if ( ! asqa_verify_nonce( 'delete-attachment-' . $attachment_id ) ) {
			asqa_ajax_json( 'no_permission' );
		}

		// If user cannot delete then die.
		if ( ! asqa_user_can_delete_attachment( $attachment_id ) ) {
			asqa_ajax_json( 'no_permission' );
		}

		$attach = get_post( $attachment_id );
		$row    = wp_delete_attachment( $attachment_id, true );

		if ( false !== $row ) {
			asqa_update_post_attach_ids( $attach->post_parent );
			asqa_ajax_json( array( 'success' => true ) );
		}

		asqa_ajax_json(
			array(
				'success'  => false,
				'snackbar' => array( 'message' => __( 'Unable to delete attachment', 'smart-question-answer' ) ),
			)
		);
	}

	/**
	 * Update users temporary attachment count before a attachment deleted.
	 *
	 * @param integer $post_id Post ID.
	 */
	public static function deleted_attachment( $post_id ) {
		$_post = get_post( $post_id );

		if ( 'attachment' === $_post->post_type ) {
			asqa_update_user_temp_media_count();
			asqa_update_post_attach_ids( $_post->post_parent );
		}
	}

	/**
	 * Schedule event twice daily.
	 */
	public static function create_single_schedule() {
		// Check if event scheduled before.
		if ( ! wp_next_scheduled( 'asqa_delete_temp_attachments' ) ) {
			// Shedule event to run every day.
			wp_schedule_event( time(), 'twicedaily', 'asqa_delete_temp_attachments' );
		}
	}

	/**
	 * Delete temporary media which are older then today.
	 *
	 * @since 4.1.8 Delete files from temporary directory as we well.
	 */
	public static function cron_delete_temp_attachments() {
		global $wpdb;

		$posts = $wpdb->get_results( "SELECT ID, post_author FROM $wpdb->posts WHERE post_type = 'attachment' AND post_title='_asqa_temp_media' AND post_date >= CURDATE()" ); // db call okay, db cache okay.

		$authors = array();

		if ( $posts ) {
			foreach ( (array) $posts as $_post ) {
				wp_delete_attachment( $_post->ID, true );
				asqa_update_post_attach_ids( $_post->post_parent );
				$authors[] = $_post->post_author;
			}

			// Update temporary attachment counts of a user.
			foreach ( (array) array_unique( $authors ) as $author ) {
				asqa_update_user_temp_media_count( $author );
			}
		}

		// Delete all temporary files.
		$uploads  = wp_upload_dir();
		$files    = glob( $uploads['basedir'] . '/smartqa-temp/*' );
		$interval = strtotime( '-2 hours' );

		if ( $files ) {
			foreach ( $files as $file ) {
				if ( filemtime( $file ) <= $interval ) {
					unlink( $file );
				}
			}
		}
	}

	/**
	 * Ajax callback for image upload form.
	 *
	 * @return void
	 * @since 4.1.8
	 */
	public static function upload_modal() {
		// Check nonce.
		if ( ! asqa_verify_nonce( 'asqa_upload_image' ) ) {
			asqa_send_json( 'something_wrong' );
		}

		// Check if user have permission to upload tem image.
		if ( ! asqa_user_can_upload() ) {
			asqa_send_json(
				array(
					'success'  => false,
					'snackbar' => array(
						'message' => __( 'Sorry! you do not have permission to upload image.', 'smart-question-answer' ),
					),
				)
			);
		}

		$image_for = asqa_sanitize_unslash( 'image_for', 'r' );

		ob_start();
		smartqa()->get_form( 'image_upload' )->generate(
			array(
				'hidden_fields' => array(
					array(
						'name'  => 'action',
						'value' => 'asqa_image_upload',
					),
					array(
						'name'  => 'image_for',
						'value' => $image_for,
					),
				),
			)
		);
		$html = ob_get_clean();

		asqa_send_json(
			array(
				'success' => true,
				'action'  => 'asqa_upload_modal',
				'html'    => $html,
				'title'   => __( 'Select image file to upload', 'smart-question-answer' ),
			)
		);
	}

	/**
	 * Ajax callback for `asqa_image_upload`. Process `image_upload` form.
	 *
	 * @return void
	 * @since 4.1.8
	 * @since 4.1.13 Pass a `image_for` in JSON so that javascript callback can be triggered.
	 */
	public static function image_upload() {
		$form = smartqa()->get_form( 'image_upload' );

		// Check if user have permission to upload tem image.
		if ( ! asqa_user_can_upload() ) {
			asqa_send_json(
				array(
					'success'  => false,
					'snackbar' => array(
						'message' => __( 'Sorry! you do not have permission to upload image.', 'smart-question-answer' ),
					),
				)
			);
		}

		// Nonce check.
		if ( ! $form->is_submitted() ) {
			asqa_send_json( 'something_wrong' );
		}

		$image_for = asqa_sanitize_unslash( 'image_for', 'r' );
		$values    = $form->get_values();

		// Check for errors.
		if ( $form->have_errors() ) {
			asqa_send_json(
				array(
					'success'       => false,
					'snackbar'      => array(
						'message' => __( 'Unable to upload image(s). Please check errors.', 'smart-question-answer' ),
					),
					'form_errors'   => $form->errors,
					'fields_errors' => $form->get_fields_errors(),
				)
			);
		}

		$field = $form->find( 'image' );

		// Call save.
		$files = $field->save_cb();

		$res = array(
			'success'   => true,
			'action'    => 'asqa_image_upload',
			'image_for' => $image_for,
			'snackbar'  => array( 'message' => __( 'Successfully uploaded image', 'smart-question-answer' ) ),
			'files'     => $files,
		);

		// Send response.
		if ( is_array( $res ) ) {
			asqa_send_json( $res );
		}

		asqa_send_json( 'something_wrong' );
	}

	/**
	 * Callback for hook `intermediate_image_sizes_advanced`.
	 *
	 * @param array $sizes Image sizes.
	 * @return array
	 */
	public static function image_sizes_advanced( $sizes ) {
		global $asqa_thumbnail_only;

		if ( true === $asqa_thumbnail_only ) {
			return array(
				'thumbnail' => array(
					'width'  => 150,
					'height' => 150,
					'crop'   => true,
				),
			);
		}

		return $sizes;
	}
}

/**
 * Upload and create an attachment. Set post_status as _asqa_temp_media,
 * later it will be removed using cron if no post parent is set.
 *
 * This function will prevent users to upload if they have more then defined
 * numbers of un-attached medias.
 *
 * @param array       $file           $_FILE variable.
 * @param boolean     $temp           Is temporary image? If so it will be deleted if no post parent.
 * @param boolean     $parent_post    Attachment parent post ID.
 * @param false|array $mimes      Mime types.
 * @return integer|boolean|object
 * @since  3.0.0 Added new argument `$post_parent`.
 * @since  4.1.5 Added new argument `$mimes` so that default mimes can be overridden.
 */
function asqa_upload_user_file( $file = array(), $temp = true, $parent_post = '', $mimes = false ) {
	require_once ABSPATH . 'wp-admin/includes/admin.php';

	// Check if file is greater then allowed size.
	if ( $file['size'] > asqa_opt( 'max_upload_size' ) ) {
		// translators: %s is file size.
		return new WP_Error( 'file_size_error', sprintf( __( 'File cannot be uploaded, size is bigger than %s MB', 'smart-question-answer' ), round( asqa_opt( 'max_upload_size' ) / ( 1024 * 1024 ), 2 ) ) );
	}

	$file_return = wp_handle_upload(
		$file,
		array(
			'test_form' => false,
			'mimes'     => false === $mimes ? asqa_allowed_mimes() : $mimes,
		)
	);

	if ( isset( $file_return['error'] ) || isset( $file_return['upload_error_handler'] ) ) {
		return new WP_Error( 'upload_error', $file_return['error'], $file_return );
	}

	$attachment = array(
		'post_parent'    => $parent_post,
		'post_mime_type' => $file_return['type'],
		'post_content'   => '',
		'guid'           => $file_return['url'],
	);

	// Add special post status if is temporary attachment.
	if ( false !== $temp ) {
		$attachment['post_title'] = '_asqa_temp_media';
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	$attachment_id = wp_insert_attachment( $attachment, $file_return['file'] );

	if ( ! empty( $attachment_id ) ) {
		asqa_update_user_temp_media_count();
	}

	return $attachment_id;
}

/**
 * Return allowed mime types.
 *
 * @return array
 * @since  3.0.0
 */
function asqa_allowed_mimes() {
	$mimes = array(
		'jpg|jpeg' => 'image/jpeg',
		'gif'      => 'image/gif',
		'png'      => 'image/png',
		'doc|docx' => 'application/msword',
		'xls'      => 'application/vnd.ms-excel',
		'pdf' => 'application/pdf',
		'pdf' => 'application/pdf',
		'PDF' => 'application/pdf',
		'zip' => 'application/zip',
		'rar' => 'application/rar',
		'ZIP' => 'application/zip',
		'RAR' => 'application/rar',
		'png' => 'image/png',
		'PNG' => 'image/png',
		'pdf' => 'application/pdf',	
		'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'rtf' => 'application/rtf',
		'txt' => 'text/plain',
		'log' => 'text/plain',
		'list' => 'text/plain',
		'xls' => 'application/excel',
		'xls' => 'application/vnd.ms-excel',
		'xls' => 'application/x-excel',
		'xls' => 'application/x-msexcel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
'odt' => 'application/vnd.oasis.opendocument.text',
	);

	/**
	 * Filter allowed mimes types.
	 *
	 * @param array $mimes Default mimes types.
	 * @since 3.0.0
	 */
	return apply_filters( 'asqa_allowed_mimes', $mimes );
}

/**
 * Delete all un-attached media of user.
 *
 * @param integer $user_id User ID.
 */
function asqa_clear_unattached_media( $user_id = false ) {
	if ( false === $user_id ) {
		$user_id = get_current_user_id();
	}

	global $wpdb;
	$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_title='_asqa_temp_media' AND post_author = %d", $user_id ) ); // db call okay, db cache okay.

	foreach ( (array) $post_ids as $id ) {
		wp_delete_attachment( $id, true );
	}
}

/**
 * Set parent post for an attachment.
 *
 * @param integer|array $media_id      Attachment ID.
 * @param integer       $post_parent   Post parent id.
 * @param false|int     $user_id       User id.
 */
function asqa_set_media_post_parent( $media_id, $post_parent, $user_id = false ) {
	if ( ! is_array( $media_id ) ) {
		$media_id = array( $media_id );
	}

	if ( false === $user_id ) {
		$user_id = get_current_user_id();
	}

	foreach ( (array) $media_id as $id ) {
		$attach = get_post( $id );

		if ( $attach && 'attachment' === $attach->post_type && $user_id == $attach->post_author ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$postarr = array(
				'ID'          => $attach->ID,
				'post_parent' => $post_parent,
				'post_title'  => preg_replace( '/\.[^.]+$/', '', basename( $attach->guid ) ),
			);

			wp_update_post( $postarr );
		}
	}

	asqa_update_post_attach_ids( $post_parent );
	asqa_update_user_temp_media_count( $user_id );
}

/**
 * Count temporary attachments of a user.
 *
 * @param  integer $user_id User ID.
 * @return integer
 */
function asqa_count_users_temp_media( $user_id ) {
	global $wpdb;

	$count = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $wpdb->posts WHERE post_title = '_asqa_temp_media' AND post_author=%d AND post_type='attachment'", $user_id ) ); // phpcs:ignore WordPress.DB

	return (int) $count;
}

/**
 * Update users temproary media uploads count.
 *
 * @param integer $user_id User ID.
 */
function asqa_update_user_temp_media_count( $user_id = false ) {
	if ( false === $user_id ) {
		$user_id = get_current_user_id();
	}

	// @codingStandardsIgnoreLine
	update_user_meta( $user_id, '_asqa_temp_media', asqa_count_users_temp_media( $user_id ) );
}

/**
 * Check if user have uploaded maximum numbers of allowed attachments.
 *
 * @param  integer $user_id User ID.
 * @return boolean
 */
function asqa_user_can_upload_temp_media( $user_id = false ) {
	if ( false === $user_id ) {
		$user_id = get_current_user_id();
	}

	// @codingStandardsIgnoreLine
	$temp_images = (int) get_user_meta( $user_id, '_asqa_temp_media', true );

	if ( $temp_images < asqa_opt( 'uploads_per_post' ) ) {
		return true;
	}

	return false;
}

/**
 * Pre fetch and cache all question and answer attachments.
 *
 * @param  array $ids Post IDs.
 * @since  4.0.0
 */
function asqa_post_attach_pre_fetch( $ids ) {
	if ( $ids && is_user_logged_in() ) {
		$args = array(
			'post_type' => 'attachment',
			'include'   => $ids,
		);

		$posts = get_posts( $args );// @codingStandardsIgnoreLine
		update_post_cache( $posts );
	}
}

/**
 * Delete images uploaded in post.
 *
 * This function should be called after saving post content. This
 * will find previously uploaded images from post meta and then
 * compare with the image `src` present in content and if any image
 * does not exists then image file and post meta is deleted.
 *
 * @param integer $post_id Post ID.
 * @return void
 * @since 4.1.8
 */
function asqa_delete_images_not_in_content( $post_id ) {
	$_post = asqa_get_post( $post_id );

	preg_match_all( '/<img.*?src\s*="([^"]+)".*?>/', $_post->post_content, $matches, PREG_SET_ORDER );

	$new_matches = array();

	if ( ! empty( $matches ) ) {
		foreach ( $matches as $m ) {
			$new_matches[] = basename( $m[1] );
		}
	}

	$images = get_post_meta( $post_id, 'smartqa-image' );

	if ( ! empty( $images ) ) {
		// Delete image if not in $matches.
		foreach ( $images as $img ) {
			if ( ! in_array( $img, $new_matches, true ) ) {
				delete_post_meta( $post_id, 'smartqa-image', $img );

				$uploads = wp_upload_dir();
				$file    = $uploads['basedir'] . "/smartqa-uploads/$img";
				unlink( $file );
			}
		}
	}
}
