<?php
/**
 * All functions and classes related to flagging
 * This file keep all function required by flagging system.
 *
 * @link     https://extensionforge.com
 * @since    2.3.4
 * @package  SmartQa
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All flag methods.
 */
class SmartQa_Flag {
	/**
	 * Ajax callback to process post flag button
	 *
	 * @since 2.0.0
	 */
	public static function action_flag() {
		$post_id = (int) asqa_sanitize_unslash( 'post_id', 'r' );

		if ( ! asqa_verify_nonce( 'flag_' . $post_id ) || ! is_user_logged_in() ) {
			asqa_ajax_json( 'something_wrong' );
		}

		$userid     = get_current_user_id();
		$is_flagged = asqa_is_user_flagged( $post_id );

		// Die if already flagged.
		if ( $is_flagged ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'You have already reported this post.', 'smart-question-answer' ) ),
				)
			);
		}

		asqa_add_flag( $post_id );
		$count = asqa_update_flags_count( $post_id );

		asqa_ajax_json(
			array(
				'success'  => true,
				'action'   => array(
					'count'  => $count,
					'active' => true,
				),
				'snackbar' => array( 'message' => __( 'Thank you for reporting this post.', 'smart-question-answer' ) ),
			)
		);
	}

}

/**
 * Add flag vote data to asqa_votes table.
 *
 * @param integer $post_id     Post ID.
 * @param integer $user_id     User ID.
 * @return integer|boolean
 */
function asqa_add_flag( $post_id, $user_id = false ) {
	if ( false === $user_id ) {
		$user_id = get_current_user_id();
	}

	$inserted = asqa_vote_insert( $post_id, $user_id, 'flag' );

	return $inserted;
}

/**
 * Count flag votes.
 *
 * @param integer $post_id Post ID.
 * @return  integer
 * @since  4.0.0
 */
function asqa_count_post_flags( $post_id ) {
	$rows = asqa_count_votes(
		array(
			'vote_post_id' => $post_id,
			'vote_type'    => 'flag',
		)
	);

	if ( false !== $rows ) {
		return (int) $rows[0]->count;
	}

	return 0;
}

/**
 * Check if user already flagged a post.
 *
 * @param bool|integer $post Post.
 * @return bool
 */
function asqa_is_user_flagged( $post = null ) {
	$_post = asqa_get_post( $post );

	if ( is_user_logged_in() ) {
		return asqa_is_user_voted( $_post->ID, 'flag' );
	}

	return false;
}

/**
 * Flag button html.
 *
 * @param mixed $post Post.
 * @return string
 * @since 0.9
 */
function asqa_flag_btn_args( $post = null ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$_post   = asqa_get_post( $post );
	$flagged = asqa_is_user_flagged( $_post );

	$title = ( ! $flagged ) ? ( __( 'Flag this post', 'smart-question-answer' ) ) : ( __( 'You have flagged this post', 'smart-question-answer' ) );

	$actions['close'] = array(
		'cb'     => 'flag',
		'icon'   => 'apicon-check',
		'query'  => array(
			'__nonce' => wp_create_nonce( 'flag_' . $_post->ID ),
			'post_id' => $_post->ID,
		),
		'label'  => __( 'Flag', 'smart-question-answer' ),
		'title'  => $title,
		'count'  => $_post->flags,
		'active' => $flagged,
	);

	return $actions['close'];
}

/**
 * Delete multiple posts flags.
 *
 * @param integer $post_id Post id.
 * @return boolean
 */
function asqa_delete_flags( $post_id ) {
	return asqa_delete_votes( $post_id, 'flag' );
}

/**
 * Update total flagged question and answer count.
 *
 * @since 4.0.0
 */
function asqa_update_total_flags_count() {
	$opt                      = get_option( 'smartqa_global', array() );
	$opt['flagged_questions'] = asqa_total_posts_count( 'question', 'flag' );
	$opt['flagged_answers']   = asqa_total_posts_count( 'answer', 'flag' );

	update_option( 'smartqa_global', $opt );
}

/**
 * Return total flagged post count.
 *
 * @return array
 * @since 4.0.0
 */
function asqa_total_flagged_count() {
	$opt['flagged_questions'] = asqa_total_posts_count( 'question', 'flag' );
	$updated                  = true;

	$opt['flagged_answers'] = asqa_total_posts_count( 'answer', 'flag' );
	$updated                = true;

	return array(
		'questions' => $opt['flagged_questions'],
		'answers'   => $opt['flagged_answers'],
	);
}
