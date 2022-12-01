<?php
/**
 * Post table hooks.
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-3.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */

// Die if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Post table hooks.
 *
 * @since unknown
 * @since 1.0.0 Fixed: CS bugs.
 */
class SmartQa_Post_Table_Hooks {
	/**
	 * Initialize the class
	 */
	public static function init() {
		smartqa()->add_filter( 'views_edit-question', __CLASS__, 'flag_view' );
		smartqa()->add_filter( 'views_edit-answer', __CLASS__, 'flag_view' );
		smartqa()->add_action( 'posts_clauses', __CLASS__, 'posts_clauses', 10, 2 );
		smartqa()->add_action( 'manage_answer_posts_custom_column', __CLASS__, 'answer_row_actions', 10, 2 );

		smartqa()->add_filter( 'manage_edit-question_columns', __CLASS__, 'cpt_question_columns' );
		smartqa()->add_action( 'manage_posts_custom_column', __CLASS__, 'custom_columns_value' );
		smartqa()->add_filter( 'manage_edit-answer_columns', __CLASS__, 'cpt_answer_columns' );
		smartqa()->add_filter( 'manage_edit-question_sortable_columns', __CLASS__, 'admin_column_sort_flag' );
		smartqa()->add_filter( 'manage_edit-answer_sortable_columns', __CLASS__, 'admin_column_sort_flag' );
		smartqa()->add_action( 'edit_form_after_title', __CLASS__, 'edit_form_after_title' );
		smartqa()->add_filter( 'manage_edit-comments_columns', __CLASS__, 'comment_flag_column' );
		smartqa()->add_filter( 'comment_status_links', __CLASS__, 'comment_flag_view' );
		smartqa()->add_action( 'current_screen', __CLASS__, 'comments_flag_query', 10, 2 );
		smartqa()->add_filter( 'post_updated_messages', __CLASS__, 'post_custom_message' );

		// phpcs:ignore smartqa()->add_filter( 'manage_comments_custom_column', __CLASS__, 'comment_flag_column_data', 10, 2 );
		// phpcs:ignore smartqa()->add_filter( 'post_row_actions', __CLASS__, 'add_question_flag_link', 10, 2 );
	}

	/**
	 * Add flagged post view.
	 *
	 * @param  array $views Views array.
	 * @return array
	 * @since unknown
	 * @since 1.0.0 Fixed: flags count.
	 */
	public static function flag_view( $views ) {
		global $post_type_object;
		$flagged_count = asqa_total_posts_count( 'answer' === $post_type_object->name ? 'answer' : 'question', 'flag' );
		$class         = asqa_sanitize_unslash( 'flagged', 'p' ) ? 'class="current" ' : '';

		$views['flagged'] = '<a ' . $class . 'href="edit.php?flagged=true&#038;post_type=' . $post_type_object->name . '">' . __( 'Flagged', 'smart-question-answer' ) . ' <span class="count">(' . $flagged_count->total . ')</span></a>';

		return $views;
	}

	/**
	 * Modify SQL query.
	 *
	 * @param array  $sql Sql claues.
	 * @param object $instance WP_Query instance.
	 * @return array
	 */
	public static function posts_clauses( $sql, $instance ) {
		global $pagenow, $wpdb;
		$vars = $instance->query_vars;

		if ( ! in_array( $vars['post_type'], array( 'question', 'answer' ), true ) ) {
			return $sql;
		}

		$sql['join']   = $sql['join'] . " LEFT JOIN {$wpdb->asqa_qameta} qameta ON qameta.post_id = {$wpdb->posts}.ID";
		$sql['fields'] = $sql['fields'] . ', qameta.*, qameta.votes_up - qameta.votes_down AS votes_net';

		// Show only flagged posts.
		if ( 'edit.php' === $pagenow && asqa_sanitize_unslash( 'flagged', 'p' ) ) {
			$sql['where']   = $sql['where'] . ' AND qameta.flags > 0';
			$sql['orderby'] = ' qameta.flags DESC, ' . $sql['orderby'];
		}

		$orderby = asqa_sanitize_unslash( 'orderby', 'p' );
		$order   = asqa_sanitize_unslash( 'order', 'p' ) === 'asc' ? 'asc' : 'desc';

		if ( 'flags' === $orderby ) {
			// Sort by flags.
			$sql['orderby'] = " qameta.flags {$order}";
		} elseif ( 'answers' === $orderby ) {
			// Sort by answers.
			$sql['orderby'] = " qameta.answers {$order}";
		} elseif ( 'votes' === $orderby ) {
			// Sort by answers.
			$sql['orderby'] = " votes_net {$order}";
		}

		return $sql;
	}

	/**
	 * Add action links below question/answer content in wp post list.
	 *
	 * @param  string  $column  Current column name.
	 * @param  integer $post_id Current post id.
	 */
	public static function answer_row_actions( $column, $post_id ) {
		global $post, $mode;

		if ( 'answer_content' !== $column ) {
			return;
		}

		$content = asqa_truncate_chars( wp_strip_all_tags( get_the_excerpt() ), 90 );

		// Pregmatch will return an array and the first 80 chars will be in the first element.
		echo '<a href="' . esc_url( get_permalink( $post->post_parent ) ) . '" class="row-title">' . esc_html( $content ) . '</a>';

		// First set up some variables.
		$actions          = array();
		$post_type_object = get_post_type_object( $post->post_type ); // override ok.
		$can_edit_post    = current_user_can( $post_type_object->casqa->edit_post, $post->ID );

		// Actions to delete/trash.
		if ( current_user_can( $post_type_object->casqa->delete_post, $post->ID ) ) {
			if ( 'trash' === $post->post_status ) {
				$_wpnonce           = wp_create_nonce( 'untrash-post_' . $post_id );
				$url                = admin_url( 'post.php?post=' . $post_id . '&action=untrash&_wpnonce=' . $_wpnonce );
				$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash', 'smart-question-answer' ) ) . "' href='" . $url . "'>" . __( 'Restore', 'smart-question-answer' ) . '</a>';
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash', 'smart-question-answer' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash', 'smart-question-answer' ) . '</a>';
			}

			if ( 'trash' === $post->post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently', 'smart-question-answer' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', 'smart-question-answer' ) . '</a>';
			}
		}

		if ( $can_edit_post ) {
			// translators: %s is post title.
			$anchor_title = sprintf( __( 'Edit &#8220;%s&#8221;', 'smart-question-answer' ), $post->title );

			$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, '', true ) . '" title="' . esc_attr( $anchor_title ) . '" rel="permalink">' . __( 'Edit', 'smart-question-answer' ) . '</a>';
		}

		// Actions to view/preview.
		if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ), true ) && $can_edit_post ) {
			// translators: %s is post title.
			$anchor_title = sprintf( __( 'Preview &#8220;%s&#8221;', 'smart-question-answer' ), $post->title );
			$anchor_url   = add_query_arg( 'preview', 'true', get_permalink( $post->ID ) );

			$actions['view'] = '<a href="' . esc_url( $anchor_url ) . '" title="' . esc_attr( $anchor_title ) . '" rel="permalink">' . __( 'Preview', 'smart-question-answer' ) . '</a>';
		} elseif ( 'trash' !== $post->post_status ) {
			// translators: %s is post title.
			$anchor_title = sprintf( __( 'View &#8220;%s&#8221;', 'smart-question-answer' ), $post->title );

			$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( $anchor_title ) . '" rel="permalink">' . __( 'View', 'smart-question-answer' ) . '</a>';
		}

		$wp_list_table = new WP_List_Table();

		echo $wp_list_table->row_actions( $actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Add clear flag action button in question list.
	 *
	 * @param array  $actions Actions array.
	 * @param object $post    Post object.
	 */
	public static function add_question_flag_link( $actions, $post ) {
		if ( asqa_get_post_field( 'flags', $post ) ) {
			$actions['flag'] = '<a href="#" data-query="asqa_clear_flag::' . wp_create_nonce( 'clear_flag_' . $post->ID ) . '::' . $post->ID . '" class="asqa-ajax-btn flag-clear" data-cb="afterFlagClear">' . __( 'Clear flag', 'smart-question-answer' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Alter columns in question cpt.
	 *
	 * @param  array $columns Table column.
	 * @return array
	 * @since  2.0.0
	 */
	public static function cpt_question_columns( $columns ) {
		$columns              = array();
		$columns['cb']        = '<input type="checkbox" />';
		$columns['asqa_author'] = __( 'Author', 'smart-question-answer' );
		$columns['title']     = __( 'Title', 'smart-question-answer' );

		if ( taxonomy_exists( 'question_category' ) ) {
			$columns['question_category'] = __( 'Category', 'smart-question-answer' );
		}

		if ( taxonomy_exists( 'question_tag' ) ) {
			$columns['question_tag'] = __( 'Tag', 'smart-question-answer' );
		}

		$columns['status']   = __( 'Status', 'smart-question-answer' );
		$columns['answers']  = __( 'Ans', 'smart-question-answer' );
		$columns['comments'] = __( 'Comments', 'smart-question-answer' );
		$columns['votes']    = __( 'Votes', 'smart-question-answer' );
		$columns['flags']    = __( 'Flags', 'smart-question-answer' );
		$columns['date']     = __( 'Date', 'smart-question-answer' );

		return $columns;
	}

	/**
	 * Custom post table column values.
	 *
	 * @param string $column Columns name.
	 */
	public static function custom_columns_value( $column ) {
		global $post;

		if ( ! in_array( $post->post_type, array( 'question', 'answer' ), true ) ) {
			return $column;
		}

		if ( 'asqa_author' === $column ) {
			echo '<a class="asqa-author-col" href="' . esc_url( asqa_user_link( $post->post_author ) ) . '">';
			asqa_author_avatar( 28 );
			echo '<span>' . esc_attr( asqa_user_display_name() ) . '</span>';
			echo '</a>';
		} elseif ( 'status' === $column ) {
			global $wp_post_statuses;
			echo '<span class="post-status">';

			if ( isset( $wp_post_statuses[ $post->post_status ] ) ) {
				echo esc_attr( $wp_post_statuses[ $post->post_status ]->label );
			}

			echo '</span>';
		} elseif ( 'question_category' === $column && taxonomy_exists( 'question_category' ) ) {
			$category = get_the_terms( $post->ID, 'question_category' );

			if ( ! empty( $category ) ) {
				$out = array();

				foreach ( (array) $category as $cat ) {
					$out[] = edit_term_link( $cat->name, '', '', $cat, false );
				}
				echo join( ', ', $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				esc_html_e( '--', 'smart-question-answer' );
			}
		} elseif ( 'question_tag' === $column && taxonomy_exists( 'question_tag' ) ) {
			$terms = get_the_terms( $post->ID, 'question_tag' );

			if ( ! empty( $terms ) ) {
				$out = array();

				foreach ( (array) $terms as $term ) {
					$url   = esc_url(
						add_query_arg(
							array(
								'post_type'    => $post->post_type,
								'question_tag' => $term->slug,
							),
							'edit.php'
						)
					);
					$out[] = sprintf( '<a href="%s">%s</a>', $url, esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'question_tag', 'display' ) ) );
				}

				echo join( ', ', $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				esc_attr_e( '--', 'smart-question-answer' );
			}
		} elseif ( 'answers' === $column ) {
			$url = add_query_arg(
				array(
					'post_type'   => 'answer',
					'post_parent' => $post->ID,
				),
				'edit.php'
			);

			// translators: %d is total numbers of answer.
			$anchor_title = sprintf( _n( '%d Answer', '%d Answers', $post->answers, 'smart-question-answer' ), $post->answers );

			echo '<a class="ans-count" title="' . esc_html( $anchor_title ) . '" href="' . esc_url( $url ) . '">' . esc_attr( $post->answers ) . '</a>';
		} elseif ( 'parent_question' === $column ) {
			$url = add_query_arg(
				array(
					'post'   => $post->post_parent,
					'action' => 'edit',
				),
				'post.php'
			);
			echo '<a class="parent_question" href="' . esc_url( $url ) . '"><strong>';
			the_title( $post->post_parent );
			echo '</strong></a>';
		} elseif ( 'votes' === $column ) {
			echo '<span class="vote-count">' . esc_attr( $post->votes_net ) . '</span>';
		} elseif ( 'flags' === $column ) {
			echo '<span class="flag-count' . ( $post->flags ? ' flagged' : '' ) . '">' . esc_attr( $post->flags ) . '</span>';
		}
	}

	/**
	 * Answer CPT columns.
	 *
	 * @param  array $columns Columns.
	 * @return array
	 * @since 2.0.0
	 */
	public static function cpt_answer_columns( $columns ) {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'asqa_author'      => __( 'Author', 'smart-question-answer' ),
			'answer_content' => __( 'Content', 'smart-question-answer' ),
			'status'         => __( 'Status', 'smart-question-answer' ),
			'comments'       => __( 'Comments', 'smart-question-answer' ),
			'votes'          => __( 'Votes', 'smart-question-answer' ),
			'flags'          => __( 'Flags', 'smart-question-answer' ),
			'date'           => __( 'Date', 'smart-question-answer' ),
		);

		return $columns;
	}

	/**
	 * Flag sorting.
	 *
	 * @param array $columns Sorting columns.
	 * @return array
	 */
	public static function admin_column_sort_flag( $columns ) {
		$columns['flags']   = 'flags';
		$columns['answers'] = 'answers';
		$columns['votes']   = 'votes';

		return $columns;
	}

	/**
	 * Show question detail above new answer.
	 *
	 * @return void
	 * @since 2.0
	 */
	public static function edit_form_after_title() {
		global $typenow, $pagenow, $post;

		if ( in_array( $pagenow, array( 'post-new.php', 'post.php' ), true ) && 'answer' === $post->post_type ) {
			$post_parent = asqa_sanitize_unslash( 'action', 'g', false ) ? $post->post_parent : asqa_sanitize_unslash( 'post_parent', 'g' );
			echo '<div class="asqa-selected-question">';

			if ( ! isset( $post_parent ) ) {
				echo '<p class="no-q-selected">' . esc_attr__( 'This question is orphan, no question is selected for this answer', 'smart-question-answer' ) . '</p>';
			} else {
				$q       = asqa_get_post( $post_parent );
				$answers = asqa_get_post_field( 'answers', $q );
				?>

				<a class="asqa-q-title" href="<?php echo esc_url( get_permalink( $q->post_id ) ); ?>">
					<?php echo esc_attr( $q->post_title ); ?>
				</a>
				<div class="asqa-q-meta">
					<span class="asqa-a-count">
						<?php
							echo esc_html(
								// translators: %d is answers count.
								sprintf( _n( '%d Answer', '%d Answers', $answers, 'smart-question-answer' ), $answers )
							);
						?>
					</span>
					<span class="asqa-edit-link">|
						<a href="<?php echo esc_url( get_edit_post_link( $q->ID ) ); ?>">
							<?php esc_attr_e( 'Edit question', 'smart-question-answer' ); ?>
						</a>
					</span>
				</div>
				<div class="asqa-q-content"><?php echo wp_kses_post( $q->post_content ); ?></div>
				<input type="hidden" name="post_parent" value="<?php echo esc_attr( $post_parent ); ?>" />

				<?php
			}
			echo '</div>';
		}
	}

	/**
	 * Adds flags column in comment table.
	 *
	 * @param array $columns Comments table columns.
	 * @since 2.4
	 */
	public static function comment_flag_column( $columns ) {
		$columns['comment_flag'] = __( 'Flag', 'smart-question-answer' );
		return $columns;
	}

	/**
	 * Show comment_flag data in comment table.
	 *
	 * @param  string  $column         name of the comment table column.
	 * @param  integer $comment_id     Current comment ID.
	 * @return void
	 * @todo fix undefined constant `SMARTQA_FLAG_META`.
	 */
	public static function comment_flag_column_data( $column, $comment_id ) {
		if ( 'comment_flag' === $column ) {
			$count = get_comment_meta( $comment_id, SMARTQA_FLAG_META, true );

			if ( $count ) {
				echo '<span class="asqa-comment-col-flag">';
				echo esc_html( $count );
				echo '</span>';
			}
		}
	}

	/**
	 * Add flag view link in comment table
	 *
	 * @param  array $views view items array.
	 * @return array
	 */
	public static function comment_flag_view( $views ) {
		$views['flagged'] = '<a href="edit-comments.php?show_flagged=true"' . ( asqa_sanitize_unslash( 'show_flagged', 'g' ) ? ' class="current"' : '' ) . '>' . esc_attr__( 'Flagged', 'smart-question-answer' ) . '</a>';
		return $views;
	}

	/**
	 * Delay hooking our clauses filter to ensure it's only applied when needed.
	 *
	 * @param object $screen Current screen.
	 */
	public static function comments_flag_query( $screen ) {
		if ( 'edit-comments' !== $screen->id ) {
				return;
		}

		// Check if our Query Var is defined.
		if ( asqa_sanitize_unslash( 'show_flagged', 'p' ) ) {
			add_action( 'comments_clauses', array( 'SmartQa_Admin', 'filter_comments_query' ) );
		}
	}

	/**
	 * Custom post update message.
	 *
	 * @param array $messages Messages.
	 * @return array
	 */
	public static function post_custom_message( $messages ) {
		global $post;
		if ( 'answer' === $post->post_type && (int) asqa_sanitize_unslash( 'message', 'g' ) === 99 ) {
			add_action( 'admin_notices', array( __CLASS__, 'ans_notice' ) );
		}

		return $messages;
	}

	/**
	 * Answer error when there is not any question set.
	 */
	public static function ans_notice() {
		echo '<div class="error">
			<p>' . esc_html__( 'Please fill parent question field, Answer was not saved!', 'smart-question-answer' ) . '</p>
		</div>';
	}

}
