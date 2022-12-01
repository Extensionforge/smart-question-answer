<?php
/**
 * All Hooks of SmartQa
 *
 * @package   SmartQa
 * @author      Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-3.0+
 * @link            https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register common smartqa hooks
 */
class SmartQa_Hooks {

	/**
	 * Menu class.
	 *
	 * @var string
	 */
	public static $menu_class = '';
	/**
	 * Initialize the class
	 *
	 * @since 2.0.1
	 * @since 2.4.8 Removed `$ap` argument.
	 */
	public static function init() {
			smartqa()->add_action( 'registered_taxonomy', __CLASS__, 'add_asqa_tables' );
			smartqa()->add_action( 'asqa_processed_new_question', __CLASS__, 'after_new_question', 1, 2 );
			smartqa()->add_action( 'asqa_processed_new_answer', __CLASS__, 'after_new_answer', 1, 2 );
			smartqa()->add_action( 'before_delete_post', __CLASS__, 'before_delete' );
			smartqa()->add_action( 'wp_trash_post', __CLASS__, 'trash_post_action' );
			smartqa()->add_action( 'untrash_post', __CLASS__, 'untrash_posts' );
			smartqa()->add_action( 'comment_post', __CLASS__, 'new_comment_approve', 10, 2 );
			smartqa()->add_action( 'comment_unapproved_to_approved', __CLASS__, 'comment_approve' );
			smartqa()->add_action( 'comment_approved_to_unapproved', __CLASS__, 'comment_unapprove' );
			smartqa()->add_action( 'trashed_comment', __CLASS__, 'comment_trash' );
			smartqa()->add_action( 'delete_comment', __CLASS__, 'comment_trash' );
			smartqa()->add_action( 'edit_comment', __CLASS__, 'edit_comment' );
			smartqa()->add_action( 'asqa_publish_comment', __CLASS__, 'publish_comment' );
			smartqa()->add_action( 'asqa_unpublish_comment', __CLASS__, 'unpublish_comment' );
			smartqa()->add_action( 'wp_loaded', __CLASS__, 'flush_rules' );
			smartqa()->add_action( 'safe_style_css', __CLASS__, 'safe_style_css', 11 );
			smartqa()->add_action( 'save_post', __CLASS__, 'base_page_update', 10, 2 );
			smartqa()->add_action( 'save_post_question', __CLASS__, 'save_question_hooks', 1, 3 );
			smartqa()->add_action( 'save_post_answer', __CLASS__, 'save_answer_hooks', 1, 3 );
			smartqa()->add_action( 'transition_post_status', __CLASS__, 'transition_post_status', 10, 3 );
			smartqa()->add_action( 'asqa_vote_casted', __CLASS__, 'update_user_vote_casted_count', 10, 4 );
			smartqa()->add_action( 'asqa_vote_removed', __CLASS__, 'update_user_vote_casted_count', 10, 4 );
			smartqa()->add_action( 'asqa_display_question_metas', __CLASS__, 'display_question_metas', 100, 2 );
			smartqa()->add_action( 'widget_comments_args', __CLASS__, 'widget_comments_args' );

			smartqa()->add_filter( 'posts_clauses', 'ASQA_QA_Query_Hooks', 'sql_filter', 1, 2 );
			smartqa()->add_filter( 'posts_results', 'ASQA_QA_Query_Hooks', 'posts_results', 1, 2 );
			smartqa()->add_filter( 'posts_pre_query', 'ASQA_QA_Query_Hooks', 'modify_main_posts', 999999, 2 );
			smartqa()->add_filter( 'pre_get_posts', 'ASQA_QA_Query_Hooks', 'pre_get_posts' );

			// Theme hooks.
			smartqa()->add_action( 'init', 'SmartQa_Theme', 'init_actions' );
			smartqa()->add_filter( 'template_include', 'SmartQa_Theme', 'template_include' );
			smartqa()->add_filter( 'asqa_template_include', 'SmartQa_Theme', 'template_include_theme_compat' );
			smartqa()->add_filter( 'post_class', 'SmartQa_Theme', 'question_answer_post_class' );
			smartqa()->add_filter( 'body_class', 'SmartQa_Theme', 'body_class' );
			smartqa()->add_action( 'after_setup_theme', 'SmartQa_Theme', 'includes_theme' );
			smartqa()->add_filter( 'wp_title', 'SmartQa_Theme', 'asqa_title', 0 );
			smartqa()->add_action( 'asqa_before', 'SmartQa_Theme', 'asqa_before_html_body' );
			smartqa()->add_action( 'wp_head', 'SmartQa_Theme', 'wp_head', 11 );
			smartqa()->add_action( 'asqa_after_question_content', 'SmartQa_Theme', 'question_attachments', 11 );
			smartqa()->add_action( 'asqa_after_answer_content', 'SmartQa_Theme', 'question_attachments', 11 );
			smartqa()->add_filter( 'nav_menu_css_class', __CLASS__, 'fix_nav_current_class', 10, 2 );
			smartqa()->add_filter( 'wp_insert_post_data', __CLASS__, 'wp_insert_post_data', 1000, 2 );
			smartqa()->add_filter( 'asqa_form_contents_filter', __CLASS__, 'sanitize_description' );

			smartqa()->add_filter( 'template_include', 'SmartQa_Theme', 'smartqa_basepage_template', 9999 );
			smartqa()->add_filter( 'get_the_excerpt', 'SmartQa_Theme', 'get_the_excerpt', 9999, 2 );
			smartqa()->add_filter( 'post_class', 'SmartQa_Theme', 'remove_hentry_class', 10, 3 );
			smartqa()->add_action( 'asqa_after_question_content', 'SmartQa_Theme', 'after_question_content' );
			smartqa()->add_filter( 'asqa_after_answer_content', 'SmartQa_Theme', 'after_question_content' );

			smartqa()->add_filter( 'the_comments', 'SmartQa_Comment_Hooks', 'the_comments' );
			smartqa()->add_filter( 'get_comment_link', 'SmartQa_Comment_Hooks', 'comment_link', 10, 3 );
			smartqa()->add_filter( 'preprocess_comment', 'SmartQa_Comment_Hooks', 'preprocess_comment' );
			smartqa()->add_filter( 'comments_template', 'SmartQa_Comment_Hooks', 'comments_template' );

			// Common pages hooks.
			smartqa()->add_action( 'init', 'SmartQa_Common_Pages', 'register_common_pages' );

			// Register post status.
			smartqa()->add_action( 'init', 'SmartQa_Post_Status', 'register_post_status' );

			// Rewrite rules hooks.
			smartqa()->add_filter( 'request', 'SmartQa_Rewrite', 'alter_the_query' );
			smartqa()->add_filter( 'query_vars', 'SmartQa_Rewrite', 'query_var' );
			smartqa()->add_action( 'generate_rewrite_rules', 'SmartQa_Rewrite', 'rewrites', 1 );
			smartqa()->add_filter( 'paginate_links', 'SmartQa_Rewrite', 'bp_com_paged' );
			smartqa()->add_filter( 'parse_request', 'SmartQa_Rewrite', 'add_query_var' );
			smartqa()->add_action( 'template_redirect', 'SmartQa_Rewrite', 'shortlink' );

			// Upload hooks.
			smartqa()->add_action( 'deleted_post', 'SmartQa_Uploader', 'deleted_attachment' );
			smartqa()->add_action( 'init', 'SmartQa_Uploader', 'create_single_schedule' );
			smartqa()->add_action( 'asqa_delete_temp_attachments', 'SmartQa_Uploader', 'cron_delete_temp_attachments' );
			smartqa()->add_action( 'intermediate_image_sizes_advanced', 'SmartQa_Uploader', 'image_sizes_advanced' );

			// Vote hooks.
			smartqa()->add_action( 'asqa_before_delete_question', 'SmartQa_Vote', 'delete_votes' );
			smartqa()->add_action( 'asqa_before_delete_answer', 'SmartQa_Vote', 'delete_votes' );
			smartqa()->add_action( 'asqa_deleted_votes', 'SmartQa_Vote', 'asqa_deleted_votes', 10, 2 );

			// Form hooks.
			smartqa()->add_action( 'asqa_form_question', 'ASQA_Form_Hooks', 'question_form', 11 );
			smartqa()->add_action( 'asqa_form_answer', 'ASQA_Form_Hooks', 'answer_form', 11 );
			smartqa()->add_action( 'asqa_form_comment', 'ASQA_Form_Hooks', 'comment_form', 11 );
			smartqa()->add_action( 'asqa_form_image_upload', 'ASQA_Form_Hooks', 'image_upload_form', 11 );

			// Subscriptions.
			smartqa()->add_action( 'asqa_after_new_question', __CLASS__, 'question_subscription', 10, 2 );
			smartqa()->add_action( 'asqa_after_new_answer', __CLASS__, 'answer_subscription', 10, 2 );
			smartqa()->add_action( 'asqa_new_subscriber', __CLASS__, 'new_subscriber', 10, 4 );
			smartqa()->add_action( 'asqa_delete_subscribers', __CLASS__, 'delete_subscribers', 10, 2 );
			smartqa()->add_action( 'asqa_delete_subscriber', __CLASS__, 'delete_subscriber', 10, 3 );
			smartqa()->add_action( 'before_delete_post', __CLASS__, 'delete_subscriptions' );
			smartqa()->add_action( 'asqa_publish_comment', __CLASS__, 'comment_subscription' );
			smartqa()->add_action( 'deleted_comment', __CLASS__, 'delete_comment_subscriptions', 10, 2 );
			smartqa()->add_action( 'get_comments_number', __CLASS__, 'get_comments_number', 11, 2 );
	}

	/**
	 * Add SmartQa tables in $wpdb.
	 */
	public static function add_asqa_tables() {
		asqa_append_table_names();
	}

	/**
	 * Things to do after creating a question
	 *
	 * @param   integer $post_id Question id.
	 * @param   object  $post Question post object.
	 * @since   1.0
	 * @since   4.1.2 Removed @see asqa_update_post_activity_meta().
	 */
	public static function after_new_question( $post_id, $post ) {

		/**
		 * Action triggered after inserting a question
		 *
		 * @since 0.9
		 */
		do_action( 'asqa_after_new_question', $post_id, $post );
	}

	/**
	 * Things to do after creating an answer
	 *
	 * @param   integer $post_id answer id.
	 * @param   object  $post answer post object.
	 * @since 2.0.1
	 * @since 4.1.2  Removed @see asqa_update_post_activity_meta().
	 * @since 4.1.11 Removed @see asqa_update_answers_count().
	 */
	public static function after_new_answer( $post_id, $post ) {
		// Update answer count.
		asqa_update_answers_count( $post->post_parent );

		/**
		 * Action triggered after inserting an answer
		 *
		 * @since 0.9
		 */
		do_action( 'asqa_after_new_answer', $post_id, $post );
	}

	/**
	 * This callback handles pre delete question actions.
	 *
	 * Before deleting a question we have to make sure that all answers
	 * and metas are cleared. Some hooks in answer may require question data
	 * so its better to delete all answers before deleting question.
	 *
	 * @param   integer $post_id Question or answer ID.
	 * @since unknown
	 * @since 4.1.6 Delete cache for `asqa_is_answered`.
	 * @since 4.1.8 Delete uploaded images and `smartqa-images` meta.
	 */
	public static function before_delete( $post_id ) {
		$post = asqa_get_post( $post_id );

		if ( ! asqa_is_cpt( $post ) ) {
			return;
		}

		// Get smartqa uploads.
		$images = get_post_meta( $post_id, 'smartqa-image' );
		if ( ! empty( $images ) ) {

			// Delete all uploaded images.
			foreach ( $images as $img ) {
				$uploads = wp_upload_dir();
				$file    = $uploads['basedir'] . "/smartqa-uploads/$img";

				if ( file_exists( $file ) ) {
					unlink( $file );
				}
			}
		}

		if ( 'question' === $post->post_type ) {

			/**
			 * Action triggered before deleting a question form database.
			 *
			 * At this point question are not actually deleted from database hence
			 * it will be easy to perform actions which uses mysql queries.
			 *
			 * @param integer $post_id Question id.
			 * @param WP_Post $post    Question object.
			 * @since unknown
			 */
			do_action( 'asqa_before_delete_question', $post->ID, $post );

			$answers = get_posts( [ 'post_parent' => $post->ID, 'post_type' => 'answer' ] ); // @codingStandardsIgnoreLine

			foreach ( (array) $answers as $a ) {
				self::delete_answer( $a->ID, $a );
				wp_delete_post( $a->ID, true );
			}

			// Delete qameta.
			asqa_delete_qameta( $post->ID );
		} elseif ( 'answer' === $post->post_type ) {
			self::delete_answer( $post_id, $post );
		}
	}

	/**
	 * Delete answer.
	 *
	 * @param   integer $post_id Question or answer ID.
	 * @param   object  $post Post Object.
	 * @since unknown
	 * @since 4.1.2 Removed @see asqa_update_post_activity_meta().
	 */
	public static function delete_answer( $post_id, $post ) {
		do_action( 'asqa_before_delete_answer', $post->ID, $post );

		if ( asqa_is_selected( $post ) ) {
			asqa_unset_selected_answer( $post->post_parent );
		}

		// Delete qameta.
		asqa_delete_qameta( $post->ID );
	}

	/**
	 * If a question is sent to trash, then move its answers to trash as well
	 *
	 * @param   integer $post_id Post ID.
	 * @since 2.0.0
	 * @since 4.1.2 Removed @see asqa_update_post_activity_meta().
	 * @since 4.1.6 Delete cache for `asqa_is_answered`.
	 */
	public static function trash_post_action( $post_id ) {
		$post = asqa_get_post( $post_id );

		if ( 'question' === $post->post_type ) {
			do_action( 'asqa_trash_question', $post->ID, $post );

			// Save current post status so that it can be restored.
			update_post_meta( $post->ID, '_asqa_last_post_status', $post->post_status );

			//@codingStandardsIgnoreStart
			$ans = get_posts( array(
				'post_type'   => 'answer',
				'post_status' => 'publish',
				'post_parent' => $post_id,
				'showposts'   => -1,
			));
			//@codingStandardsIgnoreEnd

			foreach ( (array) $ans as $p ) {
				$selcted_answer = asqa_selected_answer();
				if ( $selcted_answer === $p->ID ) {
					asqa_unset_selected_answer( $p->post_parent );
				}

				wp_trash_post( $p->ID );
			}
		}

		if ( 'answer' === $post->post_type ) {

			/**
			 * Triggered before trashing an answer.
			 *
			 * @param integer $post_id Answer ID.
			 * @param object $post Post object.
			 */
			do_action( 'asqa_trash_answer', $post->ID, $post );

			// Save current post status so that it can be restored.
			update_post_meta( $post->ID, '_asqa_last_post_status', $post->post_status );

			asqa_update_answers_count( $post->post_parent );
		}
	}

	/**
	 * If questions is restored then restore its answers too.
	 *
	 * @param   integer $post_id Post ID.
	 * @since 2.0.0
	 * @since 4.1.2 Removed @see asqa_update_post_activity_meta().
	 * @since 4.1.11 Renamed method from `untrash_ans_on_question_untrash` to `untrash_posts`.
	 */
	public static function untrash_posts( $post_id ) {
		$_post = asqa_get_post( $post_id );

		if ( 'question' === $_post->post_type ) {
			do_action( 'asqa_untrash_question', $_post->ID, $_post );
			//@codingStandardsIgnoreStart
			$ans = get_posts( array(
				'post_type'   => 'answer',
				'post_status' => 'trash',
				'post_parent' => $post_id,
				'showposts'   => -1,
			));
			//@codingStandardsIgnoreStart

			foreach ( (array) $ans as $p ) {
				//do_action( 'asqa_untrash_answer', $p->ID, $p );
				wp_untrash_post( $p->ID );
			}
		}

		if ( 'answer' === $_post->post_type ) {
			$ans = asqa_count_published_answers( $_post->post_parent );
			do_action( 'asqa_untrash_answer', $_post->ID, $_post );

			// Update answer count.
			asqa_update_answers_count( $_post->post_parent, $ans + 1 );
		}
	}

	/**
	 * Used to create an action when comment publishes.
	 *
	 * @param	integer			 $comment_id Comment ID.
	 * @param	integer|false $approved	 1 if comment is approved else false.
	 *
	 * @since unknown
	 * @since 4.1.0 Do not check post_type, instead comment type.
	 */
	public static function new_comment_approve( $comment_id, $approved ) {
		if ( 1 === $approved ) {
			$comment = get_comment( $comment_id );

			if ( 'smartqa' === $comment->comment_type ) {
				/**
				 * Action is triggered when a smartqa comment is published.
				 *
				 * @param object $comment Comment object.
				 * @since unknown
				 */
				do_action( 'asqa_publish_comment', $comment );
			}
		}
	}

	/**
	 * Used to create an action when comment get approved.
	 *
	 * @param	array|object $comment Comment object.
	 *
	 * @since unknown
	 * @since 4.1.0 Do not check post_type, instead comment type.
	 */
	public static function comment_approve( $comment ) {
		if ( 'smartqa' === $comment->comment_type ) {
			/** This action is documented in includes/hooks.php */
			do_action( 'asqa_publish_comment', $comment );
		}
	}

	/**
	 * Used to create an action when comment get unapproved.
	 *
	 * @param	array|object $comment Comment object.
	 * @since unknown
	 * @since 4.1.0 Do not check post_type, instead comment type.
	 */
	public static function comment_unapprove( $comment ) {
		if ( 'smartqa' === $comment->comment_type ) {
			/**
			 * Action is triggered when a smartqa comment is unpublished.
			 *
			 * @param object $comment Comment object.
			 * @since unknown
			 */
			do_action( 'asqa_unpublish_comment', $comment );
		}
	}

	/**
	 * Used to create an action when comment get trashed.
	 *
	 * @param	integer $comment_id Comment ID.
	 * @since unknown
	 * @since 4.1.0 Do not check post_type, instead comment type.
	 */
	public static function comment_trash( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( 'smartqa' === $comment->comment_type ) {
			/** This action is documented in includes/hooks.php */
			do_action( 'asqa_unpublish_comment', $comment );
		}
	}

	/**
	 * Actions to run after posting a comment
	 *
	 * @param	object|array $comment Comment object.
	 * @since unknown
	 * @since 4.1.2 Log to activity table on new comment. Removed @see asqa_update_post_activity_meta().
	 */
	public static function publish_comment( $comment ) {
		$comment = (object) $comment;

		$post = asqa_get_post( $comment->comment_post_ID );

		if ( ! in_array( $post->post_type, [ 'question', 'answer' ], true ) ) {
			return false;
		}

		$count = get_comment_count( $comment->comment_post_ID );
		asqa_insert_qameta( $comment->comment_post_ID, array(
			'fields'       => [ 'unapproved_comments' => $count['awaiting_moderation'] ],
			'last_updated' => current_time( 'mysql' ),
		) );


		// Log to activity table.
		asqa_activity_add( array(
			'q_id'    => 'answer' === $post->post_type ? $post->post_parent: $post->ID,
			'action'  => 'new_c',
			'a_id'    => 'answer' === $post->post_type ? $post->ID: 0,
			'c_id'    => $comment->comment_ID,
			'user_id' => $comment->user_id,
		) );
	}

	/**
	 * Actions to run after unpublishing a comment.
	 *
	 * @param	object|array $comment Comment object.
	 * @since 4.1.2 Removed @see asqa_update_post_activity_meta().
	 */
	public static function unpublish_comment( $comment ) {
		$comment = (object) $comment;
		$count = get_comment_count( $comment->comment_post_ID );
		asqa_insert_qameta( $comment->comment_post_ID, [ 'fields' => [ 'unapproved_comments' => $count['awaiting_moderation'] ] ] );
	}

	/**
	 * Edit comment hook callback.
	 *
	 * @since unknown
	 * @since 4.1.2 Removed @see asqa_update_post_activity_meta().
	 */
	public static function edit_comment( $comment_id ) {
		$comment = get_comment( $comment_id );
		$post = asqa_get_post( $comment->comment_post_ID );

		if ( ! asqa_is_cpt( $post ) ) {
			return;
		}

		$q_id = 'answer' === $post->post_type ? $post->post_parent : $post->ID;
		$a_id = 'answer' === $post->post_type ? $post->ID : 0;

		// Insert activity.
		asqa_activity_add( array(
			'q_id'   => $q_id,
			'a_id'   => $a_id,
			'action' => 'edit_c',
      'c_id'   => $comment_id,
		) );
	}

	/**
	 * Add current-menu-item class in SmartQa pages
	 *
	 * @param	array	$class Menu class.
	 * @param	object $item Current menu item.
	 * @return array menu item.
	 * @since	2.1
	 */
	public static function fix_nav_current_class( $class, $item ) {
		// Return if empty or `$item` is not object.
		if ( empty( $item ) || ! is_object( $item ) ) {
			return $class;
		}

		if ( asqa_current_page() === $item->object ) {
			$class[] = 'current-menu-item';
		}

		return $class;
	}

	/**
	 * Check if flushing rewrite rule is needed
	 *
	 * @return void
	 */
	public static function flush_rules() {
		if ( asqa_opt( 'asqa_flush' ) != 'false' ) {
			flush_rewrite_rules( true );
			asqa_opt( 'asqa_flush', 'false' );
		}
	}

	/**
	 * Filter post so that anonymous author should not be replaced
	 * by current user approving post.
	 *
	 * @param	array $data post data.
	 * @param	array $args Post arguments.
	 * @return array
	 * @since 2.2
	 * @since 4.1.0 Fixed: `post_author` get replaced if `anonymous_name` is empty.
	 *
	 * @global object $post Global post object.
	 */
	public static function wp_insert_post_data( $data, $args ) {
		global $post;

		if ( in_array( $args['post_type'], [ 'question', 'answer' ], true ) ) {
			$fields = asqa_get_post_field( 'fields', $args['ID'] );

			if ( ( is_object( $post ) && '0' === $post->post_author ) || ( !empty( $fields ) && !empty( $fields['anonymous_name'] ) ) ) {
				$data['post_author'] = '0';
			}
		}

		return $data;
	}

	/**
	 * Sanitize post description
	 *
	 * @param	string $contents Post content.
	 * @return string					 Return sanitized post content.
	 */
	public static function sanitize_description( $contents ) {
		$contents = asqa_trim_traling_space( $contents );
		return $contents;
	}

	/**
	 * Allowed CSS attributes for post_content
	 *
	 * @param	array $attr Allowed CSS attributes.
	 * @return array
	 * @since 4.1.11 Fixed wrong variable name.
	 */
	public static function safe_style_css( $attr ) {
		global $asqa_kses_check; // Check if wp_kses is called by SmartQa.

		if ( isset( $asqa_kses_check ) && $asqa_kses_check ) {
			$attr = array( 'text-decoration', 'text-align' );
		}
		return $attr;
	}

	/**
	 * Flush rewrite rule if base page is updated.
	 *
	 * @param	integer $post_id Base page ID.
	 * @param	object	$post		Post object.
	 * @since 4.1.0   Update respective page slug in options.
	 */
	public static function base_page_update( $post_id, $post ) {
		if ( wp_is_post_revision( $post ) ) {
			return;
		}

		$main_pages = array_keys( asqa_main_pages() );
		$page_ids = [];

		foreach ( $main_pages as $slug ) {
			$page_ids[ asqa_opt( $slug ) ] = $slug;
		}

		if ( in_array( $post_id, array_keys( $page_ids ) ) ) {
			$current_opt = $page_ids[ $post_id ];

			asqa_opt( $current_opt, $post_id );
			asqa_opt( $current_opt . '_id', $post->post_name );

			asqa_opt( 'asqa_flush', 'true' );
		}
	}

	/**
	 * Trigger posts hooks right after saving question.
	 *
	 * @param	integer $post_id Post ID.
	 * @param	object	$post		Post Object
	 * @param	boolean $updated Is updating post
	 * @since 4.1.0
	 * @since 4.1.2 Do not process if form not submitted. Insert updated to activity table.
	 * @since 4.1.8 Add `asqa_delete_images_not_in_content`.
	 */
	public static function save_question_hooks( $post_id, $post, $updated ) {
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return;
		}

		if ( $updated ) {
			// Deleted unused images from meta.
			asqa_delete_images_not_in_content( $post_id );
		}

		$form = smartqa()->get_form( 'question' );
		$values = $form->get_values();

		$qameta = array(
			'last_updated' => current_time( 'mysql' ),
			'answers'      => asqa_count_published_answers( $post_id ),
		);

		// Check if anonymous post and have name.
		if ( $form->is_submitted() && ! is_user_logged_in() && asqa_allow_anonymous() && ! empty( $values['anonymous_name']['value'] ) ) {
			$qameta['fields'] = array(
				'anonymous_name' => $values['anonymous_name']['value'],
			);
		}

		/**
		 * Modify qameta args which will be inserted after inserting
		 * or updating question.
		 *
		 * @param array   $qameta  Qameta arguments.
		 * @param object  $post    Post object.
		 * @param boolean $updated Is updated.
		 * @since 4.1.0
		 */
		$qameta = apply_filters( 'asqa_insert_question_qameta', $qameta, $post, $updated );
		asqa_insert_qameta( $post_id, $qameta );

		if ( $updated ) {
			/**
			 * Action triggered right after updating question.
			 *
			 * @param integer $post_id Inserted post ID.
			 * @param object	$post		Inserted post object.
			 * @since 0.9
			 * @since 4.1.0 Removed `$post->post_type` variable.
			 */
			do_action( 'asqa_processed_update_question' , $post_id, $post );

		} else {
			/**
			 * Action triggered right after inserting new question.
			 *
			 * @param integer $post_id Inserted post ID.
			 * @param object	$post		Inserted post object.
			 * @since 0.9
			 * @since 4.1.0 Removed `$post->post_type` variable.
			 */
			do_action( 'asqa_processed_new_question', $post_id, $post );
		}

		// Update qameta terms.
		asqa_update_qameta_terms( $post_id );
	}

	/**
	 * Trigger posts hooks right after saving answer.
	 *
	 * @param	integer $post_id Post ID.
	 * @param	object	$post		Post Object
	 * @param	boolean $updated Is updating post
	 * @since 4.1.0
	 * @since 4.1.2 Do not process if form not submitted. Insert updated to activity table.
	 * @since 4.1.8 Add `asqa_delete_images_not_in_content`.
	 */
	public static function save_answer_hooks( $post_id, $post, $updated ) {
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return;
		}

		if ( $updated ) {
			// Deleted unused images from meta.
			asqa_delete_images_not_in_content( $post_id );
		}

		$form = smartqa()->get_form( 'answer' );

		$values = $form->get_values();
		$activity_type = ! empty( $values['post_id']['value'] ) ? 'edit_answer' : 'new_answer';

		// Update parent question's answer count.
		asqa_update_answers_count( $post->post_parent );

		$qameta = array(
			'last_updated' => current_time( 'mysql' ),
			'activities'   => array(
				'type'    => $activity_type,
				'user_id' => $post->post_author,
				'date'    => current_time( 'mysql' ),
			),
		);

		// Check if anonymous post and have name.
		if ( $form->is_submitted() && ! is_user_logged_in() && asqa_allow_anonymous() && ! empty( $values['anonymous_name']['value'] ) ) {
			$qameta['fields'] = array(
				'anonymous_name' => $values['anonymous_name']['value'],
			);
		}

		/**
		 * Modify qameta args which will be inserted after inserting
		 * or updating answer.
		 *
		 * @param array   $qameta  Qameta arguments.
		 * @param object  $post    Post object.
		 * @param boolean $updated Is updated.
		 * @since 4.1.0
		 */
		$qameta = apply_filters( 'asqa_insert_answer_qameta', $qameta, $post, $updated );
		asqa_insert_qameta( $post_id, $qameta );

		if ( $updated ) {
			/**
			 * Action triggered right after updating answer.
			 *
			 * @param integer $post_id Inserted post ID.
			 * @param object	$post		Inserted post object.
			 * @since 0.9
			 * @since 4.1.0 Removed `$post->post_type` variable.
			 */
			do_action( 'asqa_processed_update_answer' , $post_id, $post );

		} else {
			/**
			 * Action triggered right after inserting new answer.
			 *
			 * @param integer $post_id Inserted post ID.
			 * @param object	$post		Inserted post object.
			 * @since 0.9
			 * @since 4.1.0 Removed `$post->post_type` variable.
			 */
			do_action( 'asqa_processed_new_answer', $post_id, $post );
		}

		// Update qameta terms.
		asqa_update_qameta_terms( $post_id );
	}

	/**
	 * Trigger activity update hook on question and answer status transition.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       WordPress post object.
	 * @return void
	 * @since 4.1.2
	 */
	public static function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'new' === $old_status || ! in_array( $post->post_type, [ 'answer', 'question' ], true ) ) {
			return;
		}

		$question_id = 'answer' === $post->post_type ? $post->post_parent : $post->ID;
		$answer_id   = 'answer' === $post->post_type ? $post->ID : 0;

		// Log to db.
		asqa_activity_add( array(
			'q_id'   => $question_id,
			'a_id'   => $answer_id,
			'action' => 'status_' . $new_status,
		) );
	}

	/**
	 * Update user meta of vote
	 *
	 * @param	integer $userid			  User ID who is voting.
	 * @param	string	$type			  Vote type.
	 * @param	integer $actionid	      Post ID.
	 * @param	integer $receiving_userid User who is receiving vote.
	 */
	public static function update_user_vote_casted_count( $userid, $type, $actionid, $receiving_userid ) {
		$voted = asqa_count_post_votes_by( 'user_id', $userid );
		// Update total casted vote of user.
		update_user_meta( $userid, '__up_vote_casted', $voted['votes_up'] );
		update_user_meta( $userid, '__down_vote_casted', $voted['votes_down'] );
	}

	/**
	 * Update qameta subscribers count on adding new subscriber.
	 *
	 * @param integer $rows Number of rows deleted.
	 * @param string  $where Where clause.
	 */
	public static function delete_subscriber( $ref_id, $user_id, $event ) {
		// Remove ids from event.
		$esc_event = asqa_esc_subscriber_event( $event );

		if ( in_array( $esc_event, [ 'question', 'answer', 'comment' ], true ) ) {
			asqa_update_subscribers_count( $ref_id );
		}
	}

	public static function display_question_metas( $metas, $question_id ) {
		if ( is_user_logged_in() && is_question() && asqa_is_addon_active( 'email.php' ) ) {
			$metas['subscribe'] = asqa_subscribe_btn( false, false );
		}

		return $metas;
	}

	/**
	 * Make human_time_diff strings translatable.
	 *
	 * @param	string $since Time since.
	 * @return string
	 * @since	2.4.8
	 *
	 * @deprecated 4.1.13
	 */
	public static function human_time_diff( $since ) {
		$replace = array(
			'min'   => __( 'minute', 'smart-question-answer' ),
			'mins'  => __( 'minutes', 'smart-question-answer' ),
			'hour'  => __( 'hour', 'smart-question-answer' ),
			'hours' => __( 'hours', 'smart-question-answer' ),
			'day'   => __( 'day', 'smart-question-answer' ),
			'days'  => __( 'days', 'smart-question-answer' ),
			'week'  => __( 'week', 'smart-question-answer' ),
			'weeks' => __( 'weeks', 'smart-question-answer' ),
			'year'  => __( 'year', 'smart-question-answer' ),
			'years' => __( 'years', 'smart-question-answer' ),
		);

		return strtr( $since, $replace );
	}

	/**
	 * Filter recent comments widget args.
	 * Exclude SmartQa comments from recent commenst widget.
	 *
	 * @param array $args Comments arguments.
	 * @return array
	 */
	public static function widget_comments_args( $args ) {
		$args['type__not_in'] = [ 'smartqa' ];
		return $args;
	}

	/**
	 * Subscribe OP to his own question.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post post objct.
	 *
	 * @category haveTest
	 *
	 * @since unknown Introduced
	 * @since 4.1.5 Moved from addons/free/email.php
	 */
	public static function question_subscription( $post_id, $_post ) {
		if ( $_post->post_author > 0 ) {
			asqa_new_subscriber( $_post->post_author, 'question', $_post->ID );
		}
	}

	/**
	 * Subscribe author to their answer. Answer id is stored in event name.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post   Post object.
	 *
	 * @category haveTest
	 *
	 * @since unknown Introduced
	 * @since 4.1.5 Moved from addons/free/email.php
	 */
	public static function answer_subscription( $post_id, $_post ) {
		if ( $_post->post_author > 0 ) {
			asqa_new_subscriber( $_post->post_author, 'answer_' . $post_id, $_post->post_parent );
		}
	}

	/**
	 * Update qameta subscribers count on adding new subscriber.
	 *
	 * @param integer $subscriber_id id of new subscriber added.
	 * @param integer $user_id id of user.
	 * @param string  $event Subscribe event.
	 * @param integer $ref_id Reference id.
	 *
	 * @category haveTest
	 *
	 * @since unknown
	 * @since 4.1.5 Update answer subscribers count.
	 */
	public static function new_subscriber( $subscribe_id, $user_id, $event, $ref_id ) {
		// Remove ids from event.
		$esc_event = asqa_esc_subscriber_event( $event );

		if ( in_array( $esc_event, [ 'question', 'answer', 'comment' ], true ) ) {
			asqa_update_subscribers_count( $ref_id );
		}

		// Update answer subscribers count.
		if ( 'answer' === $esc_event ) {
			$event_id = asqa_esc_subscriber_event_id( $event );
			asqa_update_subscribers_count( $event_id );
		}
	}

	/**
	 * Update qameta subscribers count before deleting subscribers.
	 *
	 * @param string $rows  Number of rows deleted.
	 * @param string $where Where clause.
	 *
	 * @category haveTest
	 *
	 * @since 4.1.5
	 */
	public static function delete_subscribers( $rows, $where ) {
		if ( ! isset( $where['subs_ref_id'] ) || ! isset( $where['subs_event'] ) ) {
			return;
		}

		// Remove ids from event.
		$esc_event = asqa_esc_subscriber_event( $where['subs_event'] );

		if ( in_array( $esc_event, [ 'question', 'answer', 'comment' ], true ) ) {
			asqa_update_subscribers_count( $where['subs_ref_id'] );
		}
	}

	/**
	 * Delete subscriptions.
	 *
	 * @param integer $postid Post ID.
	 *
	 * @since unknown Introduced
	 * @since 4.1.5 Moved from addons/free/email.php
	 */
	public static function delete_subscriptions( $postid ) {
		$_post = get_post( $postid );

		if ( 'question' === $_post->post_type ) {
			// Delete question subscriptions.
			asqa_delete_subscribers( array(
				'subs_event'  => 'question',
				'subs_ref_id' => $postid,
			) );
		}

		if ( 'answer' === $_post->post_type ) {
			// Delete question subscriptions.
			asqa_delete_subscribers( array(
				'subs_event'  => 'answer_' . $_post->post_parent,
			) );
		}
	}

	/**
	 * Add comment subscriber.
	 *
	 * If question than subscription event will be `question_{$question_id}` and ref id will contain
	 * comment id. If answer than subscription event will be `answer_{$answer_id}` and ref_id
	 * will contain comment ID.
	 *
	 * @param object $comment Comment object.
	 * @since unknown Introduced
	 * @since 4.1.5 Moved from addons/free/email.php
	 * @since 4.1.8 Changed event.
	 */
	public static function comment_subscription( $comment ) {
		if ( $comment->user_id > 0 ) {
			$_post = asqa_get_post( $comment->comment_post_ID );
			$type = $_post->post_type . '_' . $_post->ID;
			asqa_new_subscriber( $comment->user_id, $type, $comment->comment_ID );
		}
	}

	/**
	 * Delete comment subscriptions right before deleting comment.
	 *
	 * @param integer        $comment_id Comment ID.
	 * @param int|WP_Comment $_comment   Comment object.
	 *
	 * @since unknown Introduced
	 * @since 4.1.5 Moved from addons/free/email.php
	 * @since 4.1.8 Changed event.
	 */
	public static function delete_comment_subscriptions( $comment_id, $_comment ) {
		$_post = get_post( $_comment->comment_post_ID );

		if ( in_array( $_post->post_type, [ 'question', 'answer' ], true ) ) {
			$type = $_post->post_type . '_' . $_post->ID;
			$row = asqa_delete_subscribers( array(
				'subs_event'  => $type,
				'subs_ref_id' => $_comment->comment_ID,
			) );
		}
	}

	/**
	 * Include smartqa comments count.
	 * This fixes no comments visible while using DIVI.
	 *
	 * @param integer $count   Comments count
	 * @param integer $post_id Post ID.
	 * @return integer
	 *
	 * @since 4.1.13
	 */
	public static function get_comments_number( $count, $post_id ) {
		global $post_type;

		if ( $post_type == 'question' || ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && 'asqa_form_comment' === asqa_isset_post_value( 'action' ) ) ) {
			$get_comments     = get_comments( array(
				'post_id' => $post_id,
				'status'  => 'approve'
			) );

			$types = separate_comments( $get_comments );
			if( ! empty( $types['smartqa'] ) ) {
				$count = count( $types['smartqa'] );
			}
		}

		return $count;
	}
}
