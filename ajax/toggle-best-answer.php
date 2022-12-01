<?php
/**
 * Class used for ajax callback `asqa_toggle_best_answer`.
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
 * The `asqa_toggle_best_answer` ajax callback.
 *
 * @since 4.1.8
 */
class Toggle_Best_Answer extends \SmartQa\Classes\Ajax {
	/**
	 * Instance of this class.
	 *
	 * @var null|Toggle_Best_Answer
	 */
	protected static $instance;

	/**
	 * The class constructor.
	 *
	 * Set requests and nonce key.
	 */
	protected function __construct() {
		$this->req( 'answer_id', asqa_sanitize_unslash( 'answer_id', 'r' ) );
		$this->nonce_key = 'select-answer-' . $this->req( 'answer_id' );

		// Call parent.
		parent::__construct();
	}

	/**
	 * Verify user permission.
	 *
	 * @return void
	 */
	protected function verify_permission() {
		$answer_id = $this->req( 'answer_id' );

		if ( empty( $answer_id ) || ! asqa_user_can_select_answer( $answer_id ) ) {
			parent::verify_permission();
		}
	}

	/**
	 * Handle ajax for logged in users.
	 *
	 * @return void
	 */
	public function logged_in() {
		$_post = asqa_get_post( $this->req( 'answer_id' ) );

		// Unselect best answer if already selected.
		if ( asqa_have_answer_selected( $_post->post_parent ) ) {
			asqa_unset_selected_answer( $_post->post_parent );
			$this->set_success();
			$this->add_res( 'selected', false );
			$this->add_res( 'label', __( 'Select', 'smart-question-answer' ) );
			$this->snackbar( __( 'Best answer is unselected for your question.', 'smart-question-answer' ) );

			$this->send();
		}

		// Do not allow answer to be selected as best if status is moderate.
		if ( in_array( $_post->post_status, array( 'moderate', 'trash', 'private' ), true ) ) {
			$this->set_fail();
			$this->snackbar( __( 'This answer cannot be selected as best, update status to select as best answer.', 'smart-question-answer' ) );

			$this->send();
		}

		// Update question qameta.
		asqa_set_selected_answer( $_post->post_parent, $_post->ID );

		$this->set_success();
		$this->add_res( 'selected', true );
		$this->add_res( 'label', __( 'Unselect', 'smart-question-answer' ) );
		$this->snackbar( __( 'Best answer is selected for your question.', 'smart-question-answer' ) );
	}
}
