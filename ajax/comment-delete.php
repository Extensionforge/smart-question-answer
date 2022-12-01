<?php
/**
 * Class used for ajax callback `comment_delete`.
 * This class is auto loaded by SmartQa loader on demand.
 *
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 * @subpackage Ajax
 * @since 4.1.8
 */

namespace SmartQa\Ajax;

// Die if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The `comment_delete` ajax callback.
 *
 * @since 4.1.8
 */
class Comment_Delete extends \SmartQa\Classes\Ajax {
	/**
	 * Instance of this class.
	 *
	 * @var null|Comment_Delete
	 */
	protected static $instance;

	/**
	 * The class constructor.
	 *
	 * Set requests and nonce key.
	 */
	protected function __construct() {
		$comment_id      = asqa_sanitize_unslash( 'comment_id', 'r' );
		$this->nonce_key = 'delete_comment_' . $comment_id;

		$this->req( 'comment_id', $comment_id );

		// Call parent.
		parent::__construct();
	}

	/**
	 * Verify user permission.
	 *
	 * @return void
	 */
	protected function verify_permission() {
		$comment_id = $this->req( 'comment_id' );

		if ( ! empty( $comment_id ) && ! asqa_user_can_delete_comment( $comment_id ) ) {
			parent::verify_permission();
		}
	}

	/**
	 * Handle ajax for logged in users.
	 *
	 * @return void
	 */
	public function logged_in() {
		$comment_id = $this->req( 'comment_id' );
		$_comment   = get_comment( $comment_id );

		// Check if deleting comment is locked.
		if ( asqa_comment_delete_locked( $_comment->comment_ID ) && ! is_super_admin() ) {
			$this->set_fail();

			$this->snackbar(
				sprintf(
					// Translators: %s contain comment created date. i.e. 10 hours.
					__( 'The comment is locked and cannot be deleted. Any comments posted before %s cannot be deleted.', 'smart-question-answer' ),
					human_time_diff( asqa_get_current_timestamp() + asqa_opt( 'disable_delete_after' ) )
				)
			);

			$this->send();
		}

		$delete = wp_delete_comment( (int) $_comment->comment_ID, true );

		if ( $delete ) {
			do_action( 'asqa_unpublish_comment', $_comment );
			do_action( 'asqa_after_deleting_comment', $_comment );

			$count = get_comment_count( $_comment->comment_post_ID );

			$this->set_success();
			$this->snackbar( __( 'Comment successfully deleted', 'smart-question-answer' ) );
			$this->add_res( 'cb', 'commentDeleted' );
			$this->add_res( 'post_ID', $_comment->comment_post_ID );
			$this->add_res(
				'commentsCount',
				array(
					'text'       => sprintf(
						// Translators: %d contain comment count.
						_n( '%d Comment', '%d Comments', $count['all'], 'smart-question-answer' ),
						$count['all']
					),
					'number'     => $count['all'],
					'unapproved' => $count['awaiting_moderation'],
				)
			);
		}
	}
}
