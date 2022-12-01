<?php
/**
 * Post status related codes
 *
 * @link     https://extensionforge.com
 * @since    2.0.1
 * @license  GPL3+
 * @package  SmartQa
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SmartQa post status helper class.
 *
 * @since unknown
 */
class SmartQa_Post_Status {

	/**
	 * Register post status for question and answer CPT
	 */
	public static function register_post_status() {
		register_post_status(
			'moderate',
			array(
				'label'                     => __( 'Moderate', 'smart-question-answer' ),
				'public'                    => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				// translators: %s is count of post awaiting moderation.
				'label_count'               => _n_noop( 'Moderate <span class="count">(%s)</span>', 'Moderate <span class="count">(%s)</span>', 'smart-question-answer' ),
			)
		);

		register_post_status(
			'private_post',
			array(
				'label'                     => __( 'Private', 'smart-question-answer' ),
				'public'                    => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				// translators: %s is count of private post.
				'label_count'               => _n_noop( 'Private Post <span class="count">(%s)</span>', 'Private Post <span class="count">(%s)</span>', 'smart-question-answer' ),
			)
		);
	}

	/**
	 * Handle change post status ajax request.
	 *
	 * @since 2.1
	 */
	public static function change_post_status() {
		$post_id = (int) asqa_sanitize_unslash( 'post_id', 'request' );
		$status  = asqa_sanitize_unslash( 'status', 'request' );

		// Check if user has permission else die.
		if ( ! is_user_logged_in() || ! in_array( $status, array( 'publish', 'moderate', 'private_post', 'trash' ), true ) || ! asqa_verify_nonce( 'change-status-' . $status . '-' . $post_id ) || ! asqa_user_can_change_status( $post_id ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'You are not allowed to change post status', 'smart-question-answer' ) ),
				)
			);
		}

		$post                       = asqa_get_post( $post_id );
		$update_data                = array();
		$update_data['post_status'] = $status;
		$update_data['ID']          = $post->ID;

		wp_update_post( $update_data );

		// Unselect as best answer if moderate.
		if ( 'answer' === $post->post_type && 'moderate' === $status && asqa_have_answer_selected( $post->post_parent ) ) {
			asqa_unset_selected_answer( $post->ID );
		}

		do_action( 'asqa_post_status_updated', $post->ID );

		$activity_type = 'moderate' === $post->post_status ? 'approved_' . $post->post_type : 'changed_status';
		asqa_update_post_activity_meta( $post_id, $activity_type, get_current_user_id() );

		asqa_ajax_json(
			array(
				'success'     => true,
				'snackbar'    => array( 'message' => __( 'Post status updated successfully', 'smart-question-answer' ) ),
				'action'      => array( 'active' => true ),
				'postmessage' => asqa_get_post_status_message( $post->ID ),
				'newStatus'   => $status,
			)
		);
	}
}

/**
 * Post status message.
 *
 * @param mixed $post_id Post.
 * @return string
 * @since 4.0.0
 */
function asqa_get_post_status_message( $post_id = false ) {
	$post      = asqa_get_post( $post_id );
	$post_type = 'question' === $post->post_type ? __( 'Question', 'smart-question-answer' ) : __( 'Answer', 'smart-question-answer' );

	$ret = '';
	$msg = '';
	if ( is_private_post( $post_id ) ) {
		$ret = '<i class="apicon-lock"></i><span>' .
		// translators: %s is post type.
		sprintf( __( 'This %s is marked as a private, only admin and post author can see.', 'smart-question-answer' ), $post_type ) . '</span>';
	} elseif ( is_post_waiting_moderation( $post_id ) ) {
		$ret = '<i class="apicon-alert"></i><span>' .
		// translators: %s is post type.
		sprintf( __( 'This %s is waiting for the approval by the moderator.', 'smart-question-answer' ), $post_type ) . '</span>';
	} elseif ( is_post_closed( $post_id ) ) {
		$ret = '<i class="apicon-x"></i><span>' . __( 'Question is closed for new answers.', 'smart-question-answer' ) . '</span>';
	} elseif ( 'trash' === $post->post_status ) {
		// translators: %s is post type.
		$ret = '<i class="apicon-trashcan"></i><span>' . sprintf( __( 'This %s has been trashed, you can delete it permanently from wp-admin.', 'smart-question-answer' ), $post_type ) . '</span>';
	} elseif ( 'future' === $post->post_status ) {
		$ret = '<i class="apicon-clock"></i><span>' .
		// translators: %s is post type.
		sprintf( __( 'This %s is not published yet and is not accessible to anyone until it get published.', 'smart-question-answer' ), $post_type ) . '</span>';
	}

	if ( ! empty( $ret ) ) {
		$msg = '<div class="asqa-notice status-' . $post->post_status . ( is_post_closed( $post_id ) ? ' closed' : '' ) . '">' . $ret . '</div>';
	}

	return apply_filters( 'asqa_get_post_status_message', $msg, $post_id );
}

/**
 * Return description of a post status.
 *
 * @param  boolean|integer $post_id Post ID.
 */
function asqa_post_status_badge( $post_id = false ) {
	$ret = '<postmessage>';
	$msg = asqa_get_post_status_message( $post_id );

	if ( ! empty( $msg ) ) {
		$ret .= $msg;
	}

	$ret .= '</postmessage>';

	return $ret;
}
