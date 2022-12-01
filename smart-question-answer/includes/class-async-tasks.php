<?php
/**
 * Registers async action hooks.
 *
 * @package SmartQa
 * @since 4.1.8
 */

namespace SmartQa\AsyncTasks;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Run async tasks for hook asqa_after_new_question.
 *
 * @since 4.1.8
 */
class NewQuestion extends \WP_Async_Task {

	/**
	 * The hook name.
	 *
	 * @var string
	 */
	protected $action = 'asqa_after_new_question';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @param array $data Arguments.
	 * @return array
	 * @since 4.1.8
	 */
	protected function prepare_data( $data ) {
		$post_id = $data[0];
		return array( 'post_id' => $post_id );
	}

	/**
	 * Run action.
	 *
	 * @return void
	 * @since 4.1.8
	 */
	protected function run_action() {
		$post_id = asqa_sanitize_unslash( 'post_id', 'g' );
		$post    = get_post( $post_id );

		if ( $post ) {
			do_action( "wp_async_$this->action", $post->ID, $post );
		}
	}

}

/**
 * Run async tasks for hook asqa_after_new_answer.
 *
 * @since 4.1.8
 */
class NewAnswer extends \WP_Async_Task { // phpcs:ignore
	/**
	 * The hook name.
	 *
	 * @var string
	 */
	protected $action = 'asqa_after_new_answer';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @param array $data Arguments.
	 * @return array
	 * @since 4.1.8
	 */
	protected function prepare_data( $data ) {
		$post_id = $data[0];
		return array( 'post_id' => $post_id );
	}

	/**
	 * Run action.
	 *
	 * @return void
	 * @since 4.1.8
	 */
	protected function run_action() {
		$post_id = asqa_sanitize_unslash( 'post_id', 'g' );
		$post    = get_post( $post_id );

		if ( $post ) {
			do_action( "wp_async_$this->action", $post->ID, $post );
		}
	}

}

/**
 * Run async tasks for hook `asqa_select_answer`.
 *
 * @since 4.1.8
 */
class SelectAnswer extends \WP_Async_Task { // phpcs:ignore

	/**
	 * The hook name.
	 *
	 * @var string
	 */
	protected $action = 'asqa_select_answer';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @param array $data Arguments.
	 * @return array
	 * @since 4.1.8
	 */
	protected function prepare_data( $data ) {
		return array( 'post_id' => $data[0]->ID );
	}

	/**
	 * Run action.
	 *
	 * @return void
	 * @since 4.1.8
	 */
	protected function run_action() {
		$post_id = asqa_sanitize_unslash( 'post_id' );
		$post    = asqa_get_post( $post_id );

		if ( $post ) {
			do_action( "wp_async_$this->action", $post );
		}
	}

}

/**
 * Run async tasks for hook `asqa_publish_comment`.
 *
 * @since 4.1.8
 */
class PublishComment extends \WP_Async_Task { // phpcs:ignore

	/**
	 * The hook name.
	 *
	 * @var string
	 */
	protected $action = 'asqa_publish_comment';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @param array $data Arguments.
	 * @return array
	 * @since 4.1.8
	 */
	protected function prepare_data( $data ) {
		return array( 'comment_id' => $data[0]->comment_ID );
	}

	/**
	 * Run action.
	 *
	 * @return void
	 * @since 4.1.8
	 */
	protected function run_action() {
		$comment_id = asqa_sanitize_unslash( 'comment_id' );
		$comment    = get_comment( $comment_id );

		if ( $comment ) {
			do_action( "wp_async_$this->action", $comment );
		}
	}

}

/**
 * Run async tasks for hook `asqa_processed_update_question`.
 *
 * @since 4.1.8
 */
class UpdateQuestion extends \WP_Async_Task { // phpcs:ignore
	/**
	 * The hook name.
	 *
	 * @var string
	 */
	protected $action = 'asqa_processed_update_question';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @param array $data Arguments.
	 * @return array
	 * @since 4.1.8
	 */
	protected function prepare_data( $data ) {
		$post_id = $data[0];
		return array( 'post_id' => $post_id );
	}

	/**
	 * Run action.
	 *
	 * @return void
	 * @since 4.1.8
	 */
	protected function run_action() {
		$post_id = asqa_sanitize_unslash( 'post_id', 'g' );
		$post    = get_post( $post_id );

		if ( $post ) {
			do_action( "wp_async_$this->action", $post->ID, $post );
		}
	}

}

/**
 * Run async tasks for hook `asqa_processed_update_answer`.
 *
 * @since 4.1.8
 */
class UpdateAnswer extends \WP_Async_Task { // phpcs:ignore

	/**
	 * The hook name.
	 *
	 * @var string
	 */
	protected $action = 'asqa_processed_update_answer';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @param array $data Arguments.
	 * @return array
	 * @since 4.1.8
	 */
	protected function prepare_data( $data ) {
		$post_id = $data[0];
		return array( 'post_id' => $post_id );
	}

	/**
	 * Run action.
	 *
	 * @return void
	 * @since 4.1.8
	 */
	protected function run_action() {
		$post_id = asqa_sanitize_unslash( 'post_id', 'g' );
		$post    = get_post( $post_id );

		if ( $post ) {
			do_action( "wp_async_$this->action", $post->ID, $post );
		}
	}

}

new NewQuestion();
new NewAnswer();
new SelectAnswer();
new PublishComment();
new UpdateQuestion();
new UpdateAnswer();
