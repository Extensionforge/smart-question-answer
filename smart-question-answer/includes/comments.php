<?php
/**
 * SmartQa comments handling.
 *
 * @author       Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license      GPL-3.0+
 * @link         https://extensionforge.com
 * @copyright    2014 Peter Mertzlin
 * @package      SmartQa
 * @subpackage   Comments Hooks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Comments class
 */
class SmartQa_Comment_Hooks {

	/**
	 * Filter comments array to include only comments which user can read.
	 *
	 * @param array $comments Comments.
	 * @return array
	 * @since 4.1.0
	 */
	public static function the_comments( $comments ) {
		foreach ( $comments as $k => $c ) {
			if ( 'smartqa' === $c->comment_type && ! asqa_user_can_read_comment( $c ) ) {
				unset( $comments[ $k ] );
			}
		}

		return $comments;
	}

	/**
	 * Ajax callback for loading comments.
	 *
	 * @since 2.0.1
	 * @since 3.0.0 Moved from SmartQa_Ajax class.
	 *
	 * @category haveTest
	 */
	public static function load_comments() {
		global $avatar_size;
		$paged      = 1;
		$comment_id = asqa_sanitize_unslash( 'comment_id', 'r' );

		if ( ! empty( $comment_id ) ) {
			$_comment = get_comment( $comment_id );
			$post_id  = $_comment->comment_post_ID;
		} else {
			$post_id = asqa_sanitize_unslash( 'post_id', 'r' );
			$paged   = max( 1, asqa_isset_post_value( 'paged', 1 ) );
		}

		$_post = asqa_get_post( $post_id );

		$args = array(
			'show_more' => false,
		);

		if ( ! empty( $_comment ) ) {
			$avatar_size         = 60;
			$args['comment__in'] = $_comment->comment_ID;
		}

		ob_start();
		asqa_the_comments( $post_id, $args );
		$html = ob_get_clean();

		$type = 'question' === $_post->post_type ? __( 'Question', 'smart-question-answer' ) : __( 'Answer', 'smart-question-answer' );

		$result = array(
			'success' => true,
			'html'    => $html,
		);

		asqa_ajax_json( $result );
	}

	/**
	 * Modify comment query args for showing pending comments to moderator.
	 *
	 * @param  array $args Comment args.
	 * @return array
	 * @since  3.0.0
	 */
	public static function comments_template_query_args( $args ) {
		global $question_rendered;

		if ( true === $question_rendered && is_singular( 'question' ) ) {
			return false;
		}

		if ( asqa_user_can_approve_comment() ) {
			$args['status'] = 'all';
		}

		return $args;
	}

	/**
	 * Ajax callback to approve comment.
	 */
	public static function approve_comment() {
		$comment_id = (int) asqa_sanitize_unslash( 'comment_id', 'r' );

		if ( ! asqa_verify_nonce( 'approve_comment_' . $comment_id ) || ! asqa_user_can_approve_comment() ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Sorry, unable to approve comment', 'smart-question-answer' ) ),
				)
			);
		}

		$success  = wp_set_comment_status( $comment_id, 'approve' );
		$_comment = get_comment( $comment_id );
		$count    = get_comment_count( $_comment->comment_post_ID );

		if ( $success ) {
			$_comment = get_comment( $comment_id );
			asqa_ajax_json(
				array(
					'success'       => true,
					'cb'            => 'commentApproved',
					'comment_ID'    => $comment_id,
					'post_ID'       => $_comment->comment_post_ID,
					'commentsCount' => array(
						'text'       => sprintf(
							// translators: %d is comments count.
							_n( '%d Comment', '%d Comments', $count['all'], 'smart-question-answer' ),
							$count['all']
						),
						'number'     => $count['all'],
						'unapproved' => $count['awaiting_moderation'],
					),
					'snackbar'      => array( 'message' => __( 'Comment approved successfully.', 'smart-question-answer' ) ),
				)
			);
		}
	}

	/**
	 * Manipulate question and answer comments link.
	 *
	 * @param string     $link    The comment permalink with '#comment-$id' appended.
	 * @param WP_Comment $comment The current comment object.
	 * @param array      $args    An array of arguments to override the defaults.
	 */
	public static function comment_link( $link, $comment, $args ) {
		$_post = asqa_get_post( $comment->comment_post_ID );

		if ( ! in_array( $_post->post_type, array( 'question', 'answer' ), true ) ) {
			return $link;
		}

		$permalink = get_permalink( $_post );
		return $permalink . '#/comment/' . $comment->comment_ID;
	}

	/**
	 * Change comment_type while adding comments for question or answer.
	 *
	 * @param array $commentdata Comment data array.
	 * @return array
	 * @since 4.1.0
	 */
	public static function preprocess_comment( $commentdata ) {
		if ( ! empty( $commentdata['comment_post_ID'] ) ) {
			$post_type = get_post_type( $commentdata['comment_post_ID'] );

			if ( in_array( $post_type, array( 'question', 'answer' ), true ) ) {
				$commentdata['comment_type'] = 'smartqa';
			}
		}

		return $commentdata;
	}

	/**
	 * Override comments template for single question page.
	 * This will prevent post comments below single question.
	 *
	 * @param string $template Template.
	 * @return string
	 *
	 * @since 4.1.11
	 */
	public static function comments_template( $template ) {
		if ( is_singular( 'question' ) || is_smartqa() ) {
			$template = asqa_get_theme_location( 'post-comments.php' );
		}

		return $template;
	}
}

/**
 * Load comment form button.
 *
 * @param   mixed $_post Echo html.
 * @return  string
 * @since   0.1
 * @since   4.1.0 Added @see asqa_user_can_read_comments() check.
 * @since   4.1.2 Hide comments button if comments are already showing.
 */
function asqa_comment_btn_html( $_post = null ) {
	if ( ! asqa_user_can_read_comments( $_post ) ) {
		return;
	}

	$_post = asqa_get_post( $_post );

	if ( 'question' === $_post->post_type && asqa_opt( 'disable_comments_on_question' ) ) {
		return;
	}

	if ( 'answer' === $_post->post_type && asqa_opt( 'disable_comments_on_answer' ) ) {
		return;
	}

	$comment_count = get_comments_number( $_post->ID );
	$args          = wp_json_encode(
		array(
			'post_id' => $_post->ID,
			'__nonce' => wp_create_nonce( 'comment_form_nonce' ),
		)
	);

	$unapproved = '';

	if ( asqa_user_can_approve_comment() ) {
		$unapproved_count = ! empty( $_post->fields['unapproved_comments'] ) ? (int) $_post->fields['unapproved_comments'] : 0;
		$unapproved       = '<b class="unapproved' . ( $unapproved_count > 0 ? ' have' : '' ) . '" asqa-un-commentscount title="' . esc_attr__( 'Comments awaiting moderation', 'smart-question-answer' ) . '">' . $unapproved_count . '</b>';
	}

	$output = asqa_new_comment_btn( $_post->ID, false );

	return $output;
}

/**
 * Comment actions args.
 *
 * @param object|integer $comment Comment object.
 * @return array
 * @since 4.0.0
 */
function asqa_comment_actions( $comment ) {
	$comment = get_comment( $comment );
	$actions = array();

	if ( asqa_user_can_edit_comment( $comment->comment_ID ) ) {
		$actions[] = array(
			'label' => __( 'Edit', 'smart-question-answer' ),
			'href'  => '#',
			'query' => array(
				'action'     => 'comment_modal',
				'__nonce'    => wp_create_nonce( 'edit_comment_' . $comment->comment_ID ),
				'comment_id' => $comment->comment_ID,
			),
		);
	}

	if ( asqa_user_can_delete_comment( $comment->comment_ID ) ) {
		$actions[] = array(
			'label' => __( 'Delete', 'smart-question-answer' ),
			'href'  => '#',
			'query' => array(
				'__nonce'        => wp_create_nonce( 'delete_comment_' . $comment->comment_ID ),
				'asqa_ajax_action' => 'delete_comment',
				'comment_id'     => $comment->comment_ID,
			),
		);
	}

	if ( '0' === $comment->comment_approved && asqa_user_can_approve_comment() ) {
		$actions[] = array(
			'label' => __( 'Approve', 'smart-question-answer' ),
			'href'  => '#',
			'query' => array(
				'__nonce'        => wp_create_nonce( 'approve_comment_' . $comment->comment_ID ),
				'asqa_ajax_action' => 'approve_comment',
				'comment_id'     => $comment->comment_ID,
			),
		);
	}

	/**
	 * For filtering comment action buttons.
	 *
	 * @param array $actions Comment actions.
	 * @since   2.0.0
	 */
	return apply_filters( 'asqa_comment_actions', $actions );
}

/**
 * Check if comment delete is locked.
 *
 * @param  integer $comment_id     Comment ID.
 * @return bool
 * @since  3.0.0
 */
function asqa_comment_delete_locked( $comment_id ) {
	$comment       = get_comment( $comment_id );
	$commment_time = mysql2date( 'U', $comment->comment_date_gmt ) + (int) asqa_opt( 'disable_delete_after' );
	return time() > $commment_time;
}

/**
 * Output comment wrapper.
 *
 * @param mixed $_post Post ID or object.
 * @param array $args  Arguments.
 * @param array $single Is on single page? Default is `false`.
 *
 * @return void
 * @since 2.1
 * @since 4.1.0 Added two args `$_post` and `$args` and using WP_Comment_Query.
 * @since 4.1.1 Check if valid post and post type before loading comments.
 * @since 4.1.2 Introduced new argument `$single`.
 */
function asqa_the_comments( $_post = null, $args = array(), $single = false ) {
	// If comment number is 0 then dont show on single question.
	if ( $single && asqa_opt( 'comment_number' ) < 1 ) {
		return;
	}

	global $comment;

	$_post = asqa_get_post( $_post );

	// Check if valid post.
	if ( ! $_post || ! in_array( $_post->post_type, array( 'question', 'answer' ), true ) ) {
		echo '<div class="asqa-comment-no-perm">' . esc_attr__( 'Not a valid post ID.', 'smart-question-answer' ) . '</div>';
		return;
	}

	if ( ! asqa_user_can_read_comments( $_post ) ) {
		echo '<div class="asqa-comment-no-perm">' . esc_attr__( 'Sorry, you do not have permission to read comments.', 'smart-question-answer' ) . '</div>';

		return;
	}

	if ( 'question' === $_post->post_type && asqa_opt( 'disable_comments_on_question' ) ) {
		return;
	}

	if ( 'answer' === $_post->post_type && asqa_opt( 'disable_comments_on_answer' ) ) {
		return;
	}

	if ( 0 == get_comments_number( $_post->ID ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( ! $single ) {
			echo '<div class="asqa-comment-no-perm">' . esc_attr__( 'No comments found.', 'smart-question-answer' ) . '</div>';
		}
		return;
	}

	$user_id = get_current_user_id();
	$paged   = (int) max( 1, asqa_isset_post_value( 'paged', 1 ) );

	$default = array(
		'post_id'       => $_post->ID,
		'order'         => 'ASC',
		'status'        => 'approve',
		'number'        => $single ? asqa_opt( 'comment_number' ) : 99,
		'show_more'     => true,
		'no_found_rows' => false,
	);

	// Always include current user comments.
	if ( ! empty( $user_id ) && $user_id > 0 ) {
		$default['include_unapproved'] = array( $user_id );
	}

	if ( asqa_user_can_approve_comment() ) {
		$default['status'] = 'all';
	}

	$args = wp_parse_args( $args, $default );
	if ( $paged > 1 ) {
		$args['offset'] = asqa_opt( 'comment_number' );
	}

	$query = new WP_Comment_Query( $args );
	if ( 0 == $query->found_comments && ! $single ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		echo '<div class="asqa-comment-no-perm">' . esc_attr__( 'No comments found.', 'smart-question-answer' ) . '</div>';
		return;
	}

	foreach ( $query->comments as $c ) {
		$comment = $c; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		asqa_get_template_part( 'comment' );
	}

	echo '<div class="asqa-comments-footer">';
	if ( $query->max_num_pages > 1 && $single ) {
		echo '<a class="asqa-view-comments" href="#/comments/' . (int) $_post->ID . '/all">' .
		// translators: %s is total comments found.
		esc_attr( sprintf( __( 'Show %s more comments', 'smart-question-answer' ), $query->found_comments - asqa_opt( 'comment_number' ) ) ) . '</a>';
	}

	echo '</div>';
}

/**
 * A wrapper function for @see asqa_the_comments() for using in
 * post templates.
 *
 * @return void
 * @since 4.1.2
 */
function asqa_post_comments() {
	echo '<apcomments id="comments-' . esc_attr( get_the_ID() ) . '" class="have-comments">';
	asqa_the_comments( null, array(), true );
	echo '</apcomments>';

	// New comment button.
	echo asqa_comment_btn_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return or print new comment button.
 *
 * @param integer $post_id Post id.
 * @param boolean $echo    Return or echo. Default is echo.
 * @return string|void
 * @since 4.1.8
 */
function asqa_new_comment_btn( $post_id, $echo = true ) {
	if ( asqa_user_can_comment( $post_id ) ) {
		$output = '';

		$btn_args = wp_json_encode(
			array(
				'action'  => 'comment_modal',
				'post_id' => $post_id,
				'__nonce' => wp_create_nonce( 'new_comment_' . $post_id ),
			)
		);

		$output .= '<a href="#" class="asqa-btn-newcomment" aponce="false" apajaxbtn apquery="' . esc_js( $btn_args ) . '">';
		$output .= esc_attr__( 'Add a Comment', 'smart-question-answer' );
		$output .= '</a>';

		if ( false === $echo ) {
			return $output;
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}