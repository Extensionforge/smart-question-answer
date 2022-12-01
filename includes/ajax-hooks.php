<?php
/**
 * Register all ajax hooks.
 *
 * @author       Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license      GPL-2.0+
 * @link         https://extensionforge.com
 * @copyright    2014 Peter Mertzlin
 * @package      SmartQa
 * @subpackage   Ajax Hooks
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register all ajax callback
 */
class SmartQa_Ajax {
	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 */
	public static function init() {
		smartqa()->add_action( 'asqa_ajax_suggest_similar_questions', __CLASS__, 'suggest_similar_questions' );
		smartqa()->add_action( 'asqa_ajax_load_tinymce', __CLASS__, 'load_tinymce' );
		smartqa()->add_action( 'asqa_ajax_load_comments', 'SmartQa_Comment_Hooks', 'load_comments' );
		smartqa()->add_action( 'asqa_ajax_edit_comment_form', 'SmartQa_Comment_Hooks', 'edit_comment_form' );
		smartqa()->add_action( 'asqa_ajax_edit_comment', 'SmartQa_Comment_Hooks', 'edit_comment' );
		smartqa()->add_action( 'asqa_ajax_approve_comment', 'SmartQa_Comment_Hooks', 'approve_comment' );
		smartqa()->add_action( 'asqa_ajax_vote', 'SmartQa_Vote', 'vote' );

		smartqa()->add_action( 'asqa_ajax_delete_comment', 'SmartQa\Ajax\Comment_Delete', 'init' );
		smartqa()->add_action( 'wp_ajax_comment_modal', 'SmartQa\Ajax\Comment_Modal', 'init' );
		smartqa()->add_action( 'wp_ajax_nopriv_comment_modal', 'SmartQa\Ajax\Comment_Modal', 'init' );
		smartqa()->add_action( 'wp_ajax_asqa_toggle_best_answer', 'SmartQa\Ajax\Toggle_Best_Answer', 'init' );

		// Post actions.
		smartqa()->add_action( 'asqa_ajax_post_actions', 'SmartQa_Theme', 'post_actions' );
		smartqa()->add_action( 'asqa_ajax_action_toggle_featured', __CLASS__, 'toggle_featured' );
		smartqa()->add_action( 'asqa_ajax_action_close', __CLASS__, 'close_question' );
		smartqa()->add_action( 'asqa_ajax_action_toggle_delete_post', __CLASS__, 'toggle_delete_post' );
		smartqa()->add_action( 'asqa_ajax_action_delete_permanently', __CLASS__, 'permanent_delete_post' );
		smartqa()->add_action( 'asqa_ajax_action_status', 'SmartQa_Post_Status', 'change_post_status' );
		smartqa()->add_action( 'asqa_ajax_action_convert_to_post', __CLASS__, 'convert_to_post' );

		// Flag ajax callbacks.
		smartqa()->add_action( 'asqa_ajax_action_flag', 'SmartQa_Flag', 'action_flag' );

		// Uploader hooks.
		smartqa()->add_action( 'asqa_ajax_delete_attachment', 'SmartQa_Uploader', 'delete_attachment' );

		// List filtering.
		smartqa()->add_action( 'asqa_ajax_load_filter_order_by', __CLASS__, 'load_filter_order_by' );

		// Subscribe.
		smartqa()->add_action( 'asqa_ajax_subscribe', __CLASS__, 'subscribe_to_question' );
		smartqa()->add_action( 'wp_ajax_asqa_repeatable_field', 'SmartQa\Ajax\Repeatable_Field', 'init' );
		smartqa()->add_action( 'wp_ajax_nopriv_asqa_repeatable_field', 'SmartQa\Ajax\Repeatable_Field', 'init' );

		smartqa()->add_action( 'wp_ajax_asqa_form_question', 'ASQA_Form_Hooks', 'submit_question_form', 11, 0 );
		smartqa()->add_action( 'wp_ajax_nopriv_asqa_form_question', 'ASQA_Form_Hooks', 'submit_question_form', 11, 0 );
		smartqa()->add_action( 'wp_ajax_asqa_form_answer', 'ASQA_Form_Hooks', 'submit_answer_form', 11, 0 );
		smartqa()->add_action( 'wp_ajax_nopriv_asqa_form_answer', 'ASQA_Form_Hooks', 'submit_answer_form', 11, 0 );
		smartqa()->add_action( 'wp_ajax_asqa_form_comment', 'ASQA_Form_Hooks', 'submit_comment_form', 11, 0 );
		smartqa()->add_action( 'wp_ajax_nopriv_asqa_form_comment', 'ASQA_Form_Hooks', 'submit_comment_form', 11, 0 );
		smartqa()->add_action( 'wp_ajax_asqa_search_tags', __CLASS__, 'search_tags' );
		smartqa()->add_action( 'wp_ajax_nopriv_asqa_search_tags', __CLASS__, 'search_tags' );
		smartqa()->add_action( 'wp_ajax_asqa_image_upload', 'SmartQa_Uploader', 'image_upload' );
		smartqa()->add_action( 'wp_ajax_asqa_upload_modal', 'SmartQa_Uploader', 'upload_modal' );
		smartqa()->add_action( 'wp_ajax_nopriv_asqa_upload_modal', 'SmartQa_Uploader', 'upload_modal' );
	}

	/**
	 * Show similar questions while asking a question.
	 *
	 * @since 2.0.1
	 */
	public static function suggest_similar_questions() {
		// Die if question suggestion is disabled.
		if ( asqa_disable_question_suggestion() ) {
			wp_die( 'false' );
		}

		$keyword = asqa_sanitize_unslash( 'value', 'request' );
		if ( empty( $keyword ) || ( ! asqa_verify_default_nonce() && ! current_user_can( 'manage_options' ) ) ) {
				wp_die( 'false' );
		}

		$keyword   = asqa_sanitize_unslash( 'value', 'request' );
		$is_admin  = (bool) asqa_isset_post_value( 'is_admin', false );
		$questions = get_posts(
			array(
				'post_type' => 'question',
				'showposts' => 10,
				's'         => $keyword,
			)
		);

		if ( $questions ) {
				$items = '<div class="asqa-similar-questions-head">';
				// translators: %d is count of questions.
				$items .= '<p><strong>' . sprintf( _n( '%d similar question found', '%d similar questions found', count( $questions ), 'smart-question-answer' ), count( $questions ) ) . '</strong></p>';
				$items .= '<p>' . __( 'We have found some similar questions that have been asked earlier.', 'smart-question-answer' ) . '</p>';
				$items .= '</div>';

			$items .= '<div class="asqa-similar-questions">';

			foreach ( (array) $questions as $p ) {
				$count         = asqa_get_answers_count( $p->ID );
				$p->post_title = asqa_highlight_words( $p->post_title, $keyword );

				if ( $is_admin ) {
					$items .= '<div class="asqa-q-suggestion-item clearfix"><a class="select-question-button button button-primary button-small" href="' . add_query_arg(
						array(
							'post_type'   => 'answer',
							'post_parent' => $p->ID,
						),
						admin_url( 'post-new.php' )
					) . '">' . __( 'Select', 'smart-question-answer' ) . '</a><span class="question-title">' .
					// translators: %d is total answer count.
					$p->post_title . '</span><span class="acount">' . sprintf( _n( '%d Answer', '%d Answers', $count, 'smart-question-answer' ), $count ) . '</span></div>';
				} else {
					// translators: %d is total answer count.
					$items .= '<a class="asqa-sqitem clearfix" target="_blank" href="' . get_permalink( $p->ID ) . '"><span class="acount">' . sprintf( _n( '%d Answer', '%d Answers', $count, 'smart-question-answer' ), $count ) . '</span><span class="asqa-title">' . $p->post_title . '</span></a>';
				}
			}

			$items .= '</div>';
			$result = array(
				'status' => true,
				'html'   => $items,
			);
		} else {
			$result = array(
				'status'  => false,
				'message' => __( 'No related questions found.', 'smart-question-answer' ),
			);
		}

		asqa_ajax_json( $result );
	}

	/**
	 * Process ajax trash posts callback.
	 */
	public static function toggle_delete_post() {
		$post_id = (int) asqa_sanitize_unslash( 'post_id', 'request' );

		$failed_response = array(
			'success'  => false,
			'snackbar' => array( 'message' => __( 'Unable to trash this post', 'smart-question-answer' ) ),
		);

		if ( ! asqa_verify_nonce( 'trash_post_' . $post_id ) ) {
			asqa_ajax_json( $failed_response );
		}

		$post = asqa_get_post( $post_id );

		$post_type = 'question' === $post->post_type ? __( 'Question', 'smart-question-answer' ) : __( 'Answer', 'smart-question-answer' );

		if ( 'trash' === $post->post_status ) {
			if ( ! asqa_user_can_restore( $post ) ) {
				asqa_ajax_json( $failed_response );
			}

			wp_untrash_post( $post->ID );

			asqa_ajax_json(
				array(
					'success'     => true,
					'action'      => array(
						'active' => false,
						'label'  => __( 'Delete', 'smart-question-answer' ),
						'title'  => __( 'Delete this post (can be restored again)', 'smart-question-answer' ),
					),
					// translators: %s post type.
					'snackbar'    => array( 'message' => sprintf( __( '%s is restored', 'smart-question-answer' ), $post_type ) ),
					'newStatus'   => 'publish',
					'postmessage' => asqa_get_post_status_message( $post_id ),
				)
			);
		}

		if ( ! asqa_user_can_delete_post( $post_id ) ) {
			asqa_ajax_json( $failed_response );
		}

		// Delete lock feature.
		// Do not allow post to be trashed if defined time elapsed.
		if ( ( time() > ( get_the_time( 'U', $post->ID ) + (int) asqa_opt( 'disable_delete_after' ) ) ) && ! is_super_admin() ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					// translators: %s is human time difference.
					'snackbar' => array( 'message' => sprintf( __( 'This post was created %s, hence you cannot trash it', 'smart-question-answer' ), asqa_human_time( get_the_time( 'U', $post->ID ) ) ) ),
				)
			);
		}

		wp_trash_post( $post_id );

		asqa_ajax_json(
			array(
				'success'     => true,
				'action'      => array(
					'active' => true,
					'label'  => __( 'Undelete', 'smart-question-answer' ),
					'title'  => __( 'Restore this post', 'smart-question-answer' ),
				),
				// translators: %s is post type.
				'snackbar'    => array( 'message' => sprintf( __( '%s is trashed', 'smart-question-answer' ), $post_type ) ),
				'newStatus'   => 'trash',
				'postmessage' => asqa_get_post_status_message( $post_id ),
			)
		);
	}

	/**
	 * Handle Ajax callback for permanent delete of post.
	 */
	public static function permanent_delete_post() {
		$post_id = (int) asqa_sanitize_unslash( 'post_id', 'request' );

		if ( ! asqa_verify_nonce( 'delete_post_' . $post_id ) || ! asqa_user_can_permanent_delete( $post_id ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Sorry, unable to delete post', 'smart-question-answer' ) ),
				)
			);
		}

		$post = asqa_get_post( $post_id );

		if ( 'question' === $post->post_type ) {
			/**
			 * Triggered right before deleting question.
			 *
			 * @param  integer $post_id question ID.
			 */
			do_action( 'asqa_wp_trash_question', $post_id );
		} else {
			/**
			 * Triggered right before deleting answer.
			 *
			 * @param  integer $post_id answer ID.
			 */
			do_action( 'asqa_wp_trash_answer', $post_id );
		}

		wp_delete_post( $post_id, true );

		if ( 'question' === $post->post_type ) {
			asqa_ajax_json(
				array(
					'success'  => true,
					'redirect' => asqa_base_page_link(),
					'snackbar' => array( 'message' => __( 'Question is deleted permanently', 'smart-question-answer' ) ),
				)
			);
		}

		$current_ans = asqa_count_published_answers( $post->post_parent );

		// translators: %d is answers count.
		$count_label = sprintf( _n( '%d Answer', '%d Answers', $current_ans, 'smart-question-answer' ), $current_ans );

		asqa_ajax_json(
			array(
				'success'      => true,
				'snackbar'     => array( 'message' => __( 'Answer is deleted permanently', 'smart-question-answer' ) ),
				'deletePost'   => $post_id,
				'answersCount' => array(
					'text'   => $count_label,
					'number' => $current_ans,
				),
			)
		);
	}

	/**
	 * Handle toggle featured question ajax callback
	 *
	 * @since unknown
	 * @since 4.1.2 Insert to activity table when question is featured.
	 */
	public static function toggle_featured() {
		$post_id = (int) asqa_sanitize_unslash( 'post_id', 'request' );

		if ( ! asqa_user_can_toggle_featured() || ! asqa_verify_nonce( 'set_featured_' . $post_id ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Sorry, you cannot toggle a featured question', 'smart-question-answer' ) ),
				)
			);
		}

		$post = asqa_get_post( $post_id );

		// Do nothing if post type is not question.
		if ( 'question' !== $post->post_type ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Only question can be set as featured', 'smart-question-answer' ) ),
				)
			);
		}

		// Check if current question ID is in featured question array.
		if ( asqa_is_featured_question( $post ) ) {
			asqa_unset_featured_question( $post->ID );
			asqa_ajax_json(
				array(
					'success'  => true,
					'action'   => array(
						'active' => false,
						'title'  => __( 'Mark this question as featured', 'smart-question-answer' ),
						'label'  => __( 'Feature', 'smart-question-answer' ),
					),
					'snackbar' => array( 'message' => __( 'Question is unmarked as featured.', 'smart-question-answer' ) ),
				)
			);
		}

		asqa_set_featured_question( $post->ID );

		// Update activity.
		asqa_activity_add(
			array(
				'q_id'   => $post->ID,
				'action' => 'featured',
			)
		);

		asqa_ajax_json(
			array(
				'success'  => true,
				'action'   => array(
					'active' => true,
					'title'  => __( 'Unmark this question as featured', 'smart-question-answer' ),
					'label'  => __( 'Unfeature', 'smart-question-answer' ),
				),
				'snackbar' => array( 'message' => __( 'Question is marked as featured.', 'smart-question-answer' ) ),
			)
		);
	}

	/**
	 * Close question callback.
	 *
	 * @since unknown
	 * @since 4.1.2 Add activity when question is closed.
	 */
	public static function close_question() {
		$post_id = asqa_sanitize_unslash( 'post_id', 'p' );

		// Check permission and nonce.
		if ( ! is_user_logged_in() || ! check_ajax_referer( 'close_' . $post_id, 'nonce', false ) || ! asqa_user_can_close_question() ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'You cannot close a question', 'smart-question-answer' ) ),
				)
			);
		}

		$_post       = asqa_get_post( $post_id );
		$toggle      = asqa_toggle_close_question( $post_id );
		$close_label = $_post->closed ? __( 'Close', 'smart-question-answer' ) : __( 'Open', 'smart-question-answer' );
		$close_title = $_post->closed ? __( 'Close this question for new answer.', 'smart-question-answer' ) : __( 'Open this question for new answers', 'smart-question-answer' );

		$message = 1 === $toggle ? __( 'Question closed', 'smart-question-answer' ) : __( 'Question is opened', 'smart-question-answer' );

		// Log in activity table.
		if ( 1 === $toggle ) {
			asqa_activity_add(
				array(
					'q_id'   => $_post->ID,
					'action' => 'closed_q',
				)
			);
		}

		$results = array(
			'success'     => true,
			'action'      => array(
				'label' => $close_label,
				'title' => $close_title,
			),
			'snackbar'    => array( 'message' => $message ),
			'postmessage' => asqa_get_post_status_message( $post_id ),
		);

		asqa_ajax_json( $results );
	}

	/**
	 * Send JSON response and terminate.
	 *
	 * @param array|string $result Ajax response.
	 */
	public static function send( $result ) {
		asqa_send_json( asqa_ajax_responce( $result ) );
	}

	/**
	 * Load tinyMCE assets using ajax.
	 *
	 * @since 3.0.0
	 */
	public static function load_tinymce() {
		asqa_answer_form( asqa_sanitize_unslash( 'question_id', 'r' ) );
		asqa_ajax_tinymce_assets();

		wp_die();
	}

	/**
	 * Ajax callback for converting a question into a post.
	 *
	 * @since 3.0.0
	 */
	public static function convert_to_post() {
		$post_id = asqa_sanitize_unslash( 'post_id', 'r' );

		if ( ! asqa_verify_nonce( 'convert-post-' . $post_id ) || ! ( is_super_admin() || current_user_can( 'manage_options' ) ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Sorry, you are not allowed to convert this question to post', 'smart-question-answer' ) ),
				)
			);
		}

		$row = set_post_type( $post_id, 'post' );

		// After success trash all answers.
		if ( $row ) {
			global $wpdb;

			// Get IDs of all answer.
			$answer_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d and post_type = 'answer' ", (int) $post_id ) ); // phpcs:ignore WordPress.DB

			foreach ( (array) $answer_ids as $id ) {
				wp_delete_post( $id );
			}

			asqa_ajax_json(
				array(
					'success'  => true,
					// translators: %s is post title.
					'snackbar' => array( 'message' => sprintf( __( ' Question &ldquo;%s&rdquo; is converted to post and its answers are trashed', 'smart-question-answer' ), get_the_title( $post_id ) ) ),
					'redirect' => get_the_permalink( $post_id ),
				)
			);
		}
	}

	/**
	 * Ajax callback for loading order by filter.
	 *
	 * @since 4.0.0
	 */
	public static function load_filter_order_by() {
		$filter = asqa_sanitize_unslash( 'filter', 'r' );
		check_ajax_referer( 'filter_' . $filter, '__nonce' );

		asqa_ajax_json(
			array(
				'success'  => true,
				'multiple' => false,
				'items'    => asqa_get_questions_orderby(),
			)
		);
	}

	/**
	 * Subscribe user to a question.
	 *
	 * @return void
	 * @since unknown
	 */
	public static function subscribe_to_question() {
		$post_id = (int) asqa_sanitize_unslash( 'id', 'r' );

		if ( ! is_user_logged_in() ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'You must be logged in to subscribe to a question', 'smart-question-answer' ) ),
				)
			);
		}

		$_post = asqa_get_post( $post_id );

		if ( 'question' === $_post->post_type && ! asqa_verify_nonce( 'subscribe_' . $post_id ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Sorry, unable to subscribe', 'smart-question-answer' ) ),
				)
			);
		}

		// Check if already subscribed, toggle if subscribed.
		$exists = asqa_get_subscriber( false, 'question', $post_id );

		if ( $exists ) {
			asqa_delete_subscriber( $post_id, get_current_user_id(), 'question' );
			asqa_ajax_json(
				array(
					'success'  => true,
					'snackbar' => array( 'message' => __( 'Successfully unsubscribed from question', 'smart-question-answer' ) ),
					'count'    => asqa_get_post_field( 'subscribers', $post_id ),
					'label'    => __( 'Subscribe', 'smart-question-answer' ),
				)
			);
		}

		// Insert subscriber.
		$insert = asqa_new_subscriber( false, 'question', $post_id );

		if ( false === $insert ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Sorry, unable to subscribe', 'smart-question-answer' ) ),
				)
			);
		}

		asqa_ajax_json(
			array(
				'success'  => true,
				'snackbar' => array( 'message' => __( 'Successfully subscribed to question', 'smart-question-answer' ) ),
				'count'    => asqa_get_post_field( 'subscribers', $post_id ),
				'label'    => __( 'Unsubscribe', 'smart-question-answer' ),
			)
		);
	}

	/**
	 * Ajax callback for `asqa_search_tags`. This was called by tags field
	 * for fetching tags suggestions.
	 *
	 * @return void
	 * @since 4.1.5
	 */
	public static function search_tags() {
		$q          = asqa_sanitize_unslash( 'q', 'r' );
		$form       = asqa_sanitize_unslash( 'form', 'r' );
		$field_name = asqa_sanitize_unslash( 'field', 'r' );

		if ( ! asqa_verify_nonce( 'tags_' . $form . $field_name ) ) {
			wp_send_json( '{}' );
		}

		// Die if not valid form.
		if ( ! smartqa()->form_exists( $form ) ) {
			asqa_ajax_json( 'something_wrong' );
		}

		$field = smartqa()->get_form( $form )->find( $field_name );

		// Check if field exists and type is tags.
		if ( ! is_a( $field, 'SmartQa\Form\Field\Tags' ) ) {
			asqa_ajax_json( 'something_wrong' );
		}

		$taxo = $field->get( 'terms_args.taxonomy' );
		$taxo = ! empty( $taxo ) ? $taxo : 'tag';

		$terms = get_terms(
			array(
				'taxonomy'   => $taxo,
				'search'     => $q,
				'count'      => true,
				'number'     => 20,
				'hide_empty' => false,
				'orderby'    => 'count',
			)
		);

		$format = array();

		if ( $terms ) {
			foreach ( $terms as $t ) {
				$format[] = array(
					'term_id'     => $t->term_id,
					'name'        => $t->name,
					'description' => $t->description,
					// translators: %d is question count.
					'count'       => sprintf( _n( '%d Question', '%d Questions', $t->count, 'smart-question-answer' ), $t->count ),
				);
			}
		}

		wp_send_json( $format );
	}
}
