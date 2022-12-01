<?php
/**
 * Class used for ajax callback `comment_modal`.
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
 * The `comment_modal` ajax callback.
 *
 * @since 4.1.8
 */
class Comment_Modal extends \SmartQa\Classes\Ajax {
	/**
	 * Instance of this class.
	 *
	 * @var null|Comment_Modal
	 */
	protected static $instance;

	/**
	 * The class constructor.
	 *
	 * Set requests and nonce key.
	 */
	protected function __construct() {
		$comment_id = asqa_sanitize_unslash( 'comment_id', 'r' );

		if ( empty( $comment_id ) ) {
			$this->req( 'post_id', asqa_sanitize_unslash( 'post_id', 'r' ) );
			$this->nonce_key = 'new_comment_' . $this->req( 'post_id' );
		} else {
			$this->req( 'comment_id', $comment_id );
			$this->nonce_key = 'edit_comment_' . $comment_id;
		}

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
		$post_id    = $this->req( 'post_id' );

		if ( ( ! empty( $comment_id ) && ! asqa_user_can_edit_comment( $comment_id ) ) || ( ! empty( $post_id ) && ! asqa_user_can_comment( $post_id ) ) ) {
			parent::verify_permission();
		}

		// Get comment object.
		if ( ! empty( $comment_id ) ) {
			$_comment = get_comment( $comment_id );
			$this->req( 'post_id', $_comment->comment_post_ID );
		}
	}

	/**
	 * Handle ajax for logged in users.
	 *
	 * @return void
	 */
	public function logged_in() {
		ob_start();
		asqa_comment_form( $this->req( 'post_id' ), $this->req( 'comment_id' ) );
		$html = ob_get_clean();

		$this->set_success();

		$title = $this->req( 'comment_id' ) ? __( 'Edit comment', 'smart-question-answer' ) : __( 'Add a comment', 'smart-question-answer' );

		$this->add_res(
			'modal',
			array(
				'name'    => 'comment',
				'title'   => $title,
				'content' => $html,
			)
		);
	}

	/**
	 * Handle ajax for non logged in users.
	 *
	 * @return void
	 */
	public function nopriv() {
		$this->logged_in();
	}
}
