<?php
/**
 * Question query
 *
 * @package SmartQa
 */

/**
 * Exit if the file is accessed directly over web.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * QA Query class
 *
 * @since 3.1.0
 */
class Question_Query extends WP_Query {
	/**
	 * Count SQL query.
	 *
	 * @var string
	 */
	public $count_request;

	/**
	 * Initialize class.
	 *
	 * @param array|string $args Query args.
	 * @since unknown
	 * @since 4.1.5 Include future questions if user have privilege.
	 */
	public function __construct( $args = array() ) {
		if ( is_front_page() ) {
			$paged = asqa_sanitize_unslash( 'asqa_paged', 'g', 1 );
		} else {
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		}

		if ( isset( $args['post_parent'] ) ) {
			$post_parent = $args['post_parent'];
		} else {
			$post_parent = ( get_query_var( 'parent' ) ) ? get_query_var( 'parent' ) : false;
		}

		if ( ! isset( $args['asqa_order_by'] ) ) {
			$args['asqa_order_by'] = asqa_get_current_list_filters( 'order_by', 'active' );
		}

		$defaults = array(
			'showposts'              => asqa_opt( 'question_per_page' ),
			'paged'                  => $paged,
			'asqa_query'               => true,
			'asqa_order_by'            => 'active',
			'asqa_question_query'      => true,
			'post_status'            => array( 'publish' ),
			'asqa_current_user_ignore' => false,
		);

		$this->args                = wp_parse_args( $args, $defaults );
		$this->args['asqa_order_by'] = sanitize_title( $this->args['asqa_order_by'] );

		/**
		 * This was suggested by https://github.com/nash-ye.
		 */
		if ( ! $this->args['asqa_current_user_ignore'] ) {
			// Check if user can read private post.
			if ( asqa_user_can_view_private_post() ) {
				$this->args['post_status'][] = 'private_post';
			}

			// Check if user can read moderate posts.
			if ( asqa_user_can_view_moderate_post() ) {
				$this->args['post_status'][] = 'moderate';
			}

			// Check if user can read moderate posts.
			if ( asqa_user_can_view_future_post() ) {
				$this->args['post_status'][] = 'future';
			}

			// Show trash posts to super admin.
			if ( is_super_admin() ) {
				$this->args['post_status'][] = 'trash';
			}

			$this->args['post_status'] = array_unique( $this->args['post_status'] );
		}

		// Show only the unpublished post of author.
		if ( isset( $args['asqa_show_unpublished'] ) && true === $this->args['asqa_show_unpublished'] ) {
			$this->args['asqa_current_user_ignore'] = true;
			$this->args['author']                 = get_current_user_id();
			$this->args['post_status']            = array( 'moderate', 'pending', 'draft', 'trash' );
		}

		if ( $post_parent ) {
			$this->args['post_parent'] = $post_parent;
		}

		if ( '' !== get_query_var( 'asqa_s' ) ) {
			$this->args['s'] = asqa_sanitize_unslash( 'asqa_s', 'query_var' );
		}

		$this->args['post_type'] = 'question';
		parent::__construct( $this->args );
	}

	/**
	 * Get posts.
	 */
	public function get_questions() {
		return parent::get_posts();
	}

	/**
	 * Update loop index to next post.
	 */
	public function next_question() {
		return parent::next_post();
	}

	/**
	 * Undo the pointer to next.
	 */
	public function reset_next() {
		$this->current_post--;
		$this->post = $this->posts[ $this->current_post ];
		return $this->post;
	}

	/**
	 * Set current question in loop.
	 */
	public function the_question() {
		global $post;
		$this->in_the_loop = true;

		if ( -1 === $this->current_post ) {
			do_action_ref_array( 'asqa_query_loop_start', array( &$this ) );
		}

		$post = $this->next_question(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		setup_postdata( $post );
		smartqa()->current_question = $post;
	}

	/**
	 * Check if loop have questions.
	 *
	 * @return boolean
	 */
	public function have_questions() {
		return parent::have_posts();
	}

	/**
	 * Rewind questions in loop.
	 */
	public function rewind_questions() {
		parent::rewind_posts();
	}

	/**
	 * Check if main question query.
	 *
	 * @return boolean
	 */
	public function is_main_query() {
		return smartqa()->questions === $this;
	}

	/**
	 * Reset current question in loop.
	 */
	public function reset_questions_data() {
		parent::reset_postdata();

		if ( ! empty( $this->post ) ) {
			smartqa()->current_question = $this->post;
		}
	}

	/**
	 * Utility method to get all the ids in this request
	 *
	 * @return array of question ids
	 */
	public function get_ids() {
		if ( $this->asqa_ids ) {
			return;
		}

		$this->asqa_ids = array(
			'post_ids'   => array(),
			'attach_ids' => array(),
			'user_ids'   => array(),
		);

		foreach ( (array) $this->posts as $_post ) {
			$this->asqa_ids['post_ids'][] = $_post->ID;
			$this->asqa_ids['attach_ids'] = array_merge( explode( ',', $_post->attach ), $this->asqa_ids['attach_ids'] );
			if ( ! empty( $_post->post_author ) ) {
				$this->asqa_ids['user_ids'][] = $_post->post_author;
			}

			// Add activities user_id to array.
			if ( ! empty( $_post->activities ) && ! empty( $_post->activities['user_id'] ) ) {
				$this->asqa_ids['user_ids'][] = $_post->activities['user_id'];
			}
		}
		// Unique ids only.
		foreach ( (array) $this->asqa_ids as $k => $ids ) {
			$this->asqa_ids[ $k ] = array_unique( $ids );
		}
	}

	/**
	 * Pre fetch current users vote on all answers
	 *
	 * @since 3.1.0
	 * @since 4.1.2 Prefetch post activities.
	 */
	public function pre_fetch() {
		$this->get_ids();
		asqa_prefetch_recent_activities( $this->asqa_ids['post_ids'] );
		asqa_user_votes_pre_fetch( $this->asqa_ids['post_ids'] );
		asqa_post_attach_pre_fetch( $this->asqa_ids['attach_ids'] );

		// Pre fetch users.
		if ( ! empty( $this->asqa_ids['user_ids'] ) ) {
			asqa_post_author_pre_fetch( $this->asqa_ids['user_ids'] );
		}

		do_action( 'asqa_pre_fetch_question_data', $this->asqa_ids );
	}
}

/**
 * Get posts with qameta fields.
 *
 * @param  mixed $post Post object.
 * @return object
 */
function asqa_get_post( $post = null ) {
	if ( empty( $post ) && isset( $GLOBALS['post'] ) ) {
		$post = $GLOBALS['post']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	if ( $post instanceof WP_Post || is_object( $post ) ) {
		$_post = $post;
	} else {
		$_post = WP_Post::get_instance( $post );
	}

	if ( $_post && ! isset( $_post->asqa_qameta_wrapped ) ) {
		$_post = asqa_append_qameta( $_post );
	}

	return $_post;
}

/**
 * Check if there is post in loop
 *
 * @return boolean
 */
function asqa_have_questions() {
	if ( smartqa()->questions ) {
		return smartqa()->questions->have_posts();
	}

	return false;
}

/**
 * Set current question in loop.
 *
 * @return Object
 */
function asqa_the_question() {
	if ( smartqa()->questions ) {
		return smartqa()->questions->the_question();
	}
}

/**
 * Return total numbers of questions found.
 *
 * @return integer
 */
function asqa_total_questions_found() {
	if ( smartqa()->questions ) {
		return smartqa()->questions->found_posts;
	}
}

/**
 * Reset original question query.
 *
 * @return boolean
 * @since unknown
 * @since 4.1.0 Check if global `$questions` exists.
 */
function asqa_reset_question_query() {
	if ( smartqa()->questions ) {
		return smartqa()->questions->reset_questions_data();
	}
}

/**
 * Return link of user profile page
 *
 * @return string
 */
function asqa_get_profile_link() {
	global $post;

	if ( ! $post ) {
		return false;
	}

	return asqa_user_link( $post->post_author );
}

/**
 * Echo user profile link
 */
function asqa_profile_link() {
	echo asqa_get_profile_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return question author avatar.
 *
 * @param  integer $size Avatar size.
 * @param  mixed   $_post Post.
 * @return string
 */
function asqa_get_author_avatar( $size = 45, $_post = null ) {
	$_post = asqa_get_post( $_post );

	if ( ! $_post ) {
		return;
	}

	$author = 0 == $_post->post_author ? 'anonymous_' . $_post->ID : $_post->post_author; // @codingStandardsIgnoreLine

	// @codingStandardsIgnoreLine
	if ( false !== strpos( $author, 'anonymous' ) && is_array( $_post->fields ) && ! empty( $_post->fields['anonymous_name'] ) ) {
		$author = $_post->fields['anonymous_name'];
	}

	return get_avatar( $author, $size );
}

/**
 * Echo question author avatar.
 *
 * @param  integer $size Avatar size.
 * @param  mixed   $_post Post.
 */
function asqa_author_avatar( $size = 45, $_post = null ) {
	echo asqa_get_author_avatar( $size, $_post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return total published answer count.
 *
 * @param  mixed $_post Post ID, Object or null.
 * @return integer
 */
function asqa_get_answers_count( $_post = null ) {
	$_post = asqa_get_post( $_post );
	return (int) $_post->answers;
}

/**
 * Echo total votes count of a post.
 *
 * @param WP_Post|int|null $_post Question ID, Object or null.
 */
function asqa_answers_count( $_post = null ) {
	echo (int) asqa_get_answers_count( $_post );
}

/**
 * Return count of net vote of a question.
 *
 * @param  mixed $_post Post ID, Object or null.
 * @return integer
 */
function asqa_get_votes_net( $_post = null ) {
	$_post = asqa_get_post( $_post );
	return (int) $_post->votes_net;
}

/**
 * Echo count of net vote of a question.
 *
 * @param WP_Post|int|null $_post Question ID, Object or null.
 */
function asqa_votes_net( $_post = null ) {
	echo (int) asqa_get_votes_net( $_post );
}

/**
 * Echo post status of a question.
 *
 * @param  mixed $_post Post ID, Object or null.
 */
function asqa_question_status( $_post = null ) {
	$_post = asqa_get_post( $_post );

	if ( 'publish' === $_post->post_status ) {
		return;
	}

	$status_obj = get_post_status_object( $_post->post_status );
	echo '<span class="asqa-post-status ' . esc_attr( $_post->post_status ) . '">' . esc_attr( $status_obj->label ) . '</span>';
}

/**
 * Question meta to display.
 *
 * @param false|integer $question_id question id.
 * @since unknown
 * @since 4.1.2 Use @see asqa_recent_activity() for showing activity.
 * @since 4.1.8 Show short views count.
 */
function asqa_question_metas( $question_id = false ) {
	if ( false === $question_id ) {
		$question_id = get_the_ID();
	}

	$metas = array();

	// If featured question.
	if ( asqa_is_featured_question( $question_id ) ) {
		$metas['featured'] = __( 'Featured', 'smart-question-answer' );
	}

	if ( asqa_have_answer_selected() ) {
		$metas['solved'] = '<i class="apicon-check"></i><i>' . __( 'Solved', 'smart-question-answer' ) . '</i>';
	}

	$view_count = asqa_get_post_field( 'views' );

	// translators: %s is views count i.e. 2.1k views.
	$metas['views'] = '<i class="apicon-eye"></i><i>' . sprintf( __( '%s views', 'smart-question-answer' ), asqa_short_num( $view_count ) ) . '</i>';

	if ( is_question() ) {
		$last_active     = asqa_get_last_active( get_question_id() );
		$metas['active'] = '<i class="apicon-pulse"></i><i><time class="published updated" itemprop="dateModified" datetime="' . mysql2date( 'c', $last_active ) . '">' . $last_active . '</time></i>';
	}

	if ( ! is_question() ) {
		$metas['history'] = '<i class="apicon-pulse"></i>' . asqa_recent_activity( $question_id, false );
	}

	/**
	 * Used to filter question display meta.
	 *
	 * @param array $metas
	 */
	$metas = apply_filters( 'asqa_display_question_metas', $metas, $question_id );

	$output = '';
	if ( ! empty( $metas ) && is_array( $metas ) ) {
		foreach ( $metas as $meta => $display ) {
			$output .= "<span class='asqa-display-meta-item {$meta}'>{$display}</span>";
		}
	}

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get recent activity of a post.
 *
 * @param  mixed $_post Post ID, Object or null.
 * @return string
 */
function asqa_get_recent_post_activity( $_post = null ) {
	$_post = asqa_get_post( $_post );
	return asqa_latest_post_activity_html( $_post->ID );
}

/**
 * Echo recent activity of a post.
 */
function asqa_recent_post_activity() {
	echo asqa_get_recent_post_activity(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get last active time in human readable format.
 *
 * @param  mixed $post_id Post ID/Object.
 * @return string
 * @since  2.4.8 Convert mysql date to GMT.
 */
function asqa_get_last_active( $post_id = null ) {
	$p    = asqa_get_post( $post_id );
	$date = ! empty( $p->last_updated ) ? $p->last_updated : $p->post_modified_gmt;
	return asqa_human_time( get_gmt_from_date( $date ), false );
}

/**
 * Echo last active time in human readable format.
 *
 * @param  mixed $post_id Post ID/Object.
 * @since  2.4.8 Convert mysql date to GMT.
 */
function asqa_last_active( $post_id = null ) {
	echo esc_attr( asqa_get_last_active( $post_id ) );
}


/**
 * Check if question have answer selected.
 *
 * @param  mixed $question Post object or ID.
 * @return boolean
 */
function asqa_have_answer_selected( $question = null ) {
	$question = asqa_get_post( $question );

	return ! empty( $question->selected_id );
}

/**
 * Return the ID of selected answer from a question.
 *
 * @param object|null|integer $_post Post object, ID or null.
 * @return integer
 */
function asqa_selected_answer( $_post = null ) {
	$_post = asqa_get_post( $_post );

	if ( ! $_post ) {
		return false;
	}

	return $_post->selected_id;
}

/**
 * Check if current answer is selected as a best
 *
 * @param mixed $_post Post.
 * @return boolean
 * @since 2.1
 */
function asqa_is_selected( $_post = null ) {
	$_post = asqa_get_post( $_post );

	if ( ! $_post ) {
		return false;
	}

	return (bool) $_post->selected;
}

/**
 * Return post time.
 *
 * @param  mixed  $_post   Post ID, Object or null.
 * @param  string $format Date format.
 * @return String
 */
function asqa_get_time( $_post = null, $format = '' ) {
	$_post = asqa_get_post( $_post );

	if ( ! $_post ) {
		return;
	}

	return get_post_time( $format, true, $_post->ID, true );
}

/**
 * Check if current post is marked as featured
 *
 * @param  boolean|integer $question    Question ID to check.
 * @return boolean
 * @since 2.2.0.1
 */
function asqa_is_featured_question( $question = null ) {
	$question = asqa_get_post( $question );
	return (bool) $question->featured;
}

/**
 * Get terms of a question.
 *
 * @param  boolean|string $taxonomy Taxonomy slug.
 * @param  mixed          $_post     Post object, ID or null.
 * @return string
 */
function asqa_get_terms( $taxonomy = false, $_post = null ) {
	$_post = asqa_get_post( $_post );
	if ( ! empty( $_post->terms ) ) {
		return $_post->terms;
	}
	return false;
}


/**
 * Check if post have terms of a taxonomy.
 *
 * @param  boolean|integer $post_id  Post ID.
 * @param  string          $taxonomy Taxonomy name.
 * @return boolean
 */
function asqa_post_have_terms( $post_id = false, $taxonomy = 'question_category' ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$terms = get_the_terms( $post_id, $taxonomy );

	if ( ! empty( $terms ) ) {
		return true;
	}

	return false;
}

/**
 * Check if question or answer have attachemnts.
 *
 * @param  mixed $_post Post.
 * @return boolean
 */
function asqa_have_attach( $_post = null ) {
	$_post = asqa_get_post( $_post );
	if ( ! empty( $_post->attach ) ) {
		return true;
	}
	return false;
}

/**
 * Get attachment ids of a question or answer.
 *
 * @param  mixed $_post Post.
 * @return array|boolean
 */
function asqa_get_attach( $_post = null ) {
	$_post = asqa_get_post( $_post );
	if ( ! empty( $_post->attach ) ) {
		return explode( ',', $_post->attach );
	}
	return array();
}


/**
 * Get latest activity of question or answer.
 *
 * @param  mixed           $post_id Question or answer ID.
 * @param  integer|boolean $answer_activities Show answers activities as well.
 * @return string
 */
function asqa_latest_post_activity_html( $post_id = false, $answer_activities = false ) {
	if ( false === $post_id ) {
		$post_id = get_the_ID();
	}

	$_post    = asqa_get_post( $post_id );
	$activity = $_post->activities;

	if ( false !== $answer_activities && ! empty( $_post->activities['child'] ) ) {
		$activity = $_post->activities['child'];
	}

	if ( ! empty( $activity ) && ! empty( $activity['date'] ) ) {
		$activity['date'] = get_gmt_from_date( $activity['date'] );
	}

	if ( false === $answer_activities && ( ! isset( $activity['type'] ) || in_array( $activity['type'], array( 'new_answer', 'new_question' ), true ) ) ) {
		return;
	}

	$html = '';

	if ( $activity ) {
		$user_id       = ! empty( $activity['user_id'] ) ? $activity['user_id'] : 0;
		$activity_type = ! empty( $activity['type'] ) ? $activity['type'] : '';
		$html         .= '<span class="asqa-post-history">';
		$html         .= '<a href="' . asqa_user_link( $user_id ) . '" itemprop="author" itemscope itemtype="http://schema.org/Person"><span itemprop="name">' . asqa_user_display_name( $user_id ) . '</span></a>';
		$html         .= ' ' . asqa_activity_short_title( $activity_type );

		if ( ! empty( $activity['date'] ) ) {
			$html .= ' <a href="' . get_permalink( $_post ) . '">';
			$html .= '<time itemprop="dateModified" datetime="' . mysql2date( 'c', $activity['date'] ) . '">' . asqa_human_time( $activity['date'], false ) . '</time>';
			$html .= '</a>';
		}

		$html .= '</span>';
	}

	if ( $html ) {
		return apply_filters( 'asqa_latest_post_activity_html', $html );
	}

	return false;
}


/**
 * Output answers of current question.
 *
 * @since 2.1
 * @since 4.1.0 Removed calling function @see `asqa_reset_question_query`.
 */
function asqa_answers() {
	global $answers;
	$answers = asqa_get_answers();

	asqa_get_template_part( 'answers' );
	asqa_reset_question_query();
}
