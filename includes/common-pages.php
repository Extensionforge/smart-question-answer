<?php
/**
 * Class for base page
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-3.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle output of all default pages of SmartQa
 */
class SmartQa_Common_Pages {
	/**
	 * Register all pages of SmartQa
	 */
	public static function register_common_pages() {
		asqa_register_page( 'base', asqa_opt( 'base_page_title' ), array( __CLASS__, 'base_page' ) );
		asqa_register_page( 'question', __( 'Question', 'smart-question-answer' ), array( __CLASS__, 'question_page' ), false );
		asqa_register_page( 'ask', __( 'Ask a Question', 'smart-question-answer' ), array( __CLASS__, 'ask_page' ) );
		asqa_register_page( 'search', __( 'Search', 'smart-question-answer' ), array( __CLASS__, 'search_page' ), false );
		asqa_register_page( 'edit', __( 'Edit Answer', 'smart-question-answer' ), array( __CLASS__, 'edit_page' ), false );
		asqa_register_page( 'activities', __( 'Activities', 'smart-question-answer' ), array( __CLASS__, 'activities_page' ), false );
	}

	/**
	 * Layout of base page.
	 */
	public static function base_page() {
		global $wp;

		$keywords          = get_search_query();
		$tax_relation      = ! empty( $wp->query_vars['asqa_tax_relation'] ) ? $wp->query_vars['asqa_tax_relation'] : 'OR';
		$args              = array();
		$args['tax_query'] = array( 'relation' => $tax_relation ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		if ( false !== $keywords ) {
			$args['s'] = $keywords;
		}

		if ( is_front_page() ) {
			$args['paged'] = get_query_var( 'page' );
		}

		// Set post parent.
		if ( get_query_var( 'post_parent', false ) ) {
			$args['post_parent'] = get_query_var( 'post_parent' );
		}

		if ( asqa_sanitize_unslash( 'unpublished', 'r' ) && is_user_logged_in() ) {
			$args['asqa_show_unpublished'] = true;
		}

		/**
		 * Filter main question list query arguments.
		 *
		 * @param array $args Wp_Query arguments.
		 */
		$args = apply_filters( 'asqa_main_questions_args', $args );

		smartqa()->questions = new Question_Query( $args );
		asqa_get_template_part( 'archive' );
	}

	/**
	 * Render question permissions message.
	 *
	 * @param object $_post Post object.
	 * @return string
	 * @since 1.0.0
	 */
	private static function question_permission_msg( $_post ) {
		$msg = false;

		// Check if user is allowed to read this question.
		if ( ! asqa_user_can_read_question( $_post->ID ) ) {
			if ( 'moderate' === $_post->post_status ) {
				$msg = __( 'This question is awaiting moderation and cannot be viewed. Please check back later.', 'smart-question-answer' );
			} else {
				$msg = __( 'Sorry! you are not allowed to read this question.', 'smart-question-answer' );
			}
		} elseif ( 'future' === $_post->post_status && ! asqa_user_can_view_future_post( $_post ) ) {
			$time_to_publish = human_time_diff( strtotime( $_post->post_date ), asqa_get_current_timestamp() );

			$msg = '<strong>' . sprintf(
				// Translators: %s contain time to publish.
				__( 'Question will be published in %s', 'smart-question-answer' ),
				$time_to_publish
			) . '</strong>';

			$msg .= '<p>' . esc_attr__( 'This question is not published yet and is not accessible to anyone until it get published.', 'smart-question-answer' ) . '</p>';
		}

		/**
		 * Filter single question page permission message.
		 *
		 * @param string $msg Message.
		 * @since 1.0.0
		 */
		$msg = apply_filters( 'asqa_question_page_permission_msg', $msg );

		return $msg;
	}

	/**
	 * Output single question page.
	 *
	 * @since 0.0.1
	 * @since 1.0.0 Changed template file name to single-question.php to question.php.
	 * @since 1.0.0 Re-setup current post.
	 * @since 1.0.05 Add while loop.
	 */
	public static function question_page() {
		global $question_rendered, $post;

		$question_rendered = false;
		$msg               = self::question_permission_msg( $post );

		// Check if user have permission.
		if ( false !== $msg ) {
			status_header( 403 );
			echo '<div class="asqa-no-permission">' . wp_kses_post( $msg ) . '</div>';
			$question_rendered = true;
			return;
		}

		if ( have_posts() ) {
			while ( have_posts() ) :
				the_post();
				include asqa_get_theme_location( 'single-question.php' );
			endwhile;
		}

		/**
		 * An action triggered after rendering single question page.
		 *
		 * @since 0.0.1
		 */
		do_action( 'asqa_after_question' );

		$question_rendered = true;
	}

	/**
	 * Output ask page template
	 */
	public static function ask_page() {
		$post_id = asqa_sanitize_unslash( 'id', 'r', false );

		if ( $post_id && ! asqa_verify_nonce( 'edit-post-' . $post_id ) ) {
			esc_attr_e( 'Something went wrong, please try again', 'smart-question-answer' );
			return;
		}

		include asqa_get_theme_location( 'ask.php' );

		/**
		 * Action called after ask page (shortcode) is rendered.
		 *
		 * @since 1.0.0
		 */
		do_action( 'asqa_after_ask_page' );
	}

	/**
	 * Load search page template
	 *
	 * @since unknown
	 * @since 1.0.0 Added missing exit statement.
	 */
	public static function search_page() {
		$keywords = asqa_sanitize_unslash( 'asqa_s', 'query_var', false );
		wp_safe_redirect( add_query_arg( array( 'asqa_s' => $keywords ), asqa_get_link_to( '/' ) ) );
		exit;
	}

	/**
	 * Output edit page template
	 */
	public static function edit_page() {
		$post_id = (int) asqa_sanitize_unslash( 'id', 'r' );

		if ( ! asqa_verify_nonce( 'edit-post-' . $post_id ) || empty( $post_id ) || ! asqa_user_can_edit_answer( $post_id ) ) {
				echo '<p>' . esc_attr__( 'Sorry, you cannot edit this answer.', 'smart-question-answer' ) . '</p>';
				return;
		}

		global $editing_post;
		$editing_post = asqa_get_post( $post_id );

		asqa_answer_form( $editing_post->post_parent, true );
	}

	/**
	 * Render activities page.
	 *
	 * @return void
	 * @since 1.0.0
	 * @since 1.0.0 Added Exclude roles arguments.
	 */
	public static function activities_page() {
		$roles = array_keys( asqa_opt( 'activity_exclude_roles' ) );
		$args  = array();

		if ( ! empty( $roles ) ) {
			$args['exclude_roles'] = $roles;
		}

		$activities = new SmartQa\Activity( $args );
		include asqa_get_theme_location( 'activities/activities.php' );
	}

	/**
	 * If page is not found then set header as 404
	 */
	public static function set_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		include asqa_get_theme_location( 'not-found.php' );
	}
}

