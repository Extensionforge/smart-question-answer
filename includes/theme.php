<?php
/**
 * SmartQa theme and template handling.
 *
 * @author       Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license      GPL-3.0+
 * @link         https://extensionforge.com
 * @copyright    2014 Peter Mertzlin
 * @package      SmartQa
 * @subpackage   Theme Functions
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Return current page title.
 *
 * @return string current title
 * @since unknown
 * @since 1.0.0 Removed `question_name` query var check.
 */
function asqa_page_title() {
	$new_title = '';
	$new_title = apply_filters( 'asqa_page_title', $new_title );

	return $new_title;
}

/**
 * Check if current page is search page
 *
 * @return boolean
 */
function is_asqa_search() {
	if ( is_smartqa() && get_query_var( 'asqa_s' ) ) {
		return true;
	}

	return false;
}

/**
 * Get post status.
 *
 * @param boolean|integer $post_id question or answer ID.
 * @return string
 * @since 2.0.0
 */
function asqa_post_status( $post_id = false ) {
	if ( false === $post_id ) {
		$post_id = get_the_ID();
	}

	return get_post_status( $post_id );
}

/**
 * Check if current post is private.
 *
 * @param boolean|integer $post_id question or answer ID.
 * @return boolean
 */
function is_private_post( $post_id = false ) {
	if ( asqa_post_status( $post_id ) === 'private_post' ) {
		return true;
	}

	return false;
}

/**
 * Check if post is waiting moderation.
 *
 * @param boolean|integer $post_id question or answer ID.
 * @return bool
 */
function is_post_waiting_moderation( $post_id = false ) {
	if ( get_post_status( $post_id ) === 'moderate' ) {
		return true;
	}

	return false;
}

/**
 * Check if question is closed.
 *
 * @param boolean|integer $post_id question or answer ID.
 * @return boolean
 * @since 2.0.0
 */
function is_post_closed( $post_id = null ) {
	if ( '1' === asqa_get_post_field( 'closed', $post_id ) ) {
		return true;
	}
	return false;
}

/**
 * Check if question have a parent post.
 *
 * @param boolean|integer $post_id question or answer ID.
 * @return boolean
 * @since   2.0.0
 */
function asqa_have_parent_post( $post_id = false ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	// Get post.
	$post_o = asqa_get_post( $post_id );

	if ( $post_o->post_parent > 0 && 'question' === $post_o->post_type ) {
		return true;
	}

	return false;
}

/**
 * Smartqa pagination
 * Uses paginate_links.
 *
 * @param float  $current Current paged, if not set then get_query_var('paged') is used.
 * @param int    $total   Total number of pages, if not set then global $questions is used.
 * @param string $format  pagination format.
 * @param string $page_num_link  Base link.
 * @return string
 */
function asqa_pagination( $current = false, $total = false, $format = '?paged=%#%', $page_num_link = false ) {
	global $asqa_max_num_pages, $asqa_current;

	if ( is_front_page() ) {
		$format = '';
	}

	$big = 999999999; // Need an unlikely integer.

	if ( false === $current ) {
		$paged   = asqa_sanitize_unslash( 'asqa_paged', 'r', 1 );
		$current = is_front_page() ? max( 1, $paged ) : max( 1, get_query_var( 'paged' ) );
	} elseif ( ! empty( $asqa_current ) ) {
		$current = $asqa_current;
	}

	if ( ! empty( $asqa_max_num_pages ) ) {
		$total = $asqa_max_num_pages;
	} elseif ( false === $total && isset( smartqa()->questions->max_num_pages ) ) {
		$total = smartqa()->questions->max_num_pages;
	}

	if ( false === $page_num_link ) {
		$page_num_link = str_replace( array( '&amp;', '&#038;' ), '&', get_pagenum_link( $big ) );
	}

	$base = str_replace( $big, '%#%', $page_num_link );

	if ( 1 === $total ) {
		return;
	}

	echo '<div class="asqa-pagination clearfix">';
	$links = paginate_links(
		array(
			'base'     => $base,
			'format'   => $format,
			'current'  => $current,
			'total'    => $total,
			'end_size' => 2,
			'mid_size' => 2,
		)
	);
	$links = str_replace( '<a class="next page-numbers"', '<a class="next page-numbers" rel="next"', $links );
	$links = str_replace( '<a class="prev page-numbers"', '<a class="prev page-numbers" rel="prev"', $links );
	echo wp_kses_post( $links );
	echo '</div>';
}

/**
 * Register smartqa pages.
 *
 * @param string   $page_slug    slug for links.
 * @param string   $page_title   Page title.
 * @param callable $func         Hook to run when shortcode is found.
 * @param bool     $show_in_menu User can add this pages to their WordPress menu from appearance->menu->SmartQa.
 * @param bool     $private Only show to currently logged in user.
 *
 * @since 1.0.0
 */
function asqa_register_page( $page_slug, $page_title, $func, $show_in_menu = true, $private = false ) {
	smartqa()->pages[ $page_slug ] = array(
		'title'        => $page_title,
		'func'         => $func,
		'show_in_menu' => $show_in_menu,
		'private'      => $private,
	);
}

/**
 * Output current SmartQa page.
 *
 * @param string $current_page Pass current page to override.
 *
 * @since 2.0.0
 * @since 1.0.0 Fixed: page attribute not working.
 */
function asqa_page( $current_page = '' ) {
	$pages = smartqa()->pages;

	if ( empty( $current_page ) ) {
		$current_page = asqa_current_page();
		$current_page = '' === $current_page ? 'base' : $current_page;
	}

	if ( isset( $pages[ $current_page ]['func'] ) ) {
		call_user_func( $pages[ $current_page ]['func'] );
	} else {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		include asqa_get_theme_location( 'not-found.php' );
	}
}

/**
 * Return post actions array.
 *
 * @param mixed $_post Post.
 * @return array
 * @since  1.0.0
 */
function asqa_post_actions( $_post = null ) {
	$_post = asqa_get_post( $_post );

	$actions = array();

	if ( ! in_array( $_post->post_type, array( 'question', 'answer' ), true ) ) {
		return $actions;
	}

	// Featured link.
	if ( 'question' === $_post->post_type ) {
		$actions[] = asqa_featured_post_args( $_post->ID );
	}

	// Question close action.
	if ( asqa_user_can_close_question() && 'question' === $_post->post_type ) {
		$nonce       = wp_create_nonce( 'close_' . $_post->ID );
		$close_label = $_post->closed ? __( 'Open', 'smart-question-answer' ) : __( 'Close', 'smart-question-answer' );
		$close_title = $_post->closed ? __( 'Open this question for new answers', 'smart-question-answer' ) : __( 'Close this question for new answer.', 'smart-question-answer' );

		$actions[] = array(
			'cb'    => 'close',
			'icon'  => 'apicon-check',
			'query' => array(
				'nonce'   => $nonce,
				'post_id' => $_post->ID,
			),
			'label' => $close_label,
			'title' => $close_title,
		);
	}

	// Edit link.
	if ( asqa_user_can_edit_post( $_post->ID ) ) {
		$actions[] = array(
			'cb'    => 'edit',
			'label' => __( 'Edit', 'smart-question-answer' ),
			'href'  => asqa_post_edit_link( $_post ),
		);
	}

	// Flag link.
	$actions[] = asqa_flag_btn_args( $_post );

	$status_args = asqa_post_status_btn_args( $_post );

	if ( ! empty( $status_args ) ) {
		$actions[] = array(
			'label'  => __( 'Status', 'smart-question-answer' ),
			'header' => true,
		);

		$actions   = array_merge( $actions, $status_args );
		$actions[] = array( 'header' => true );
	}

	if ( asqa_user_can_delete_post( $_post->ID ) ) {
		if ( 'trash' === $_post->post_status ) {
			$label = __( 'Undelete', 'smart-question-answer' );
			$title = __( 'Restore this post', 'smart-question-answer' );
		} else {
			$label = __( 'Delete', 'smart-question-answer' );
			$title = __( 'Delete this post (can be restored again)', 'smart-question-answer' );
		}

		$actions[] = array(
			'cb'    => 'toggle_delete_post',
			'query' => array(
				'post_id' => $_post->ID,
				'__nonce' => wp_create_nonce( 'trash_post_' . $_post->ID ),
			),
			'label' => $label,
			'title' => $title,
		);
	}

	// Permanent delete link.
	if ( asqa_user_can_permanent_delete( $_post->ID ) ) {
		$actions[] = array(
			'cb'    => 'delete_permanently',
			'query' => array(
				'post_id' => $_post->ID,
				'__nonce' => wp_create_nonce( 'delete_post_' . $_post->ID ),
			),
			'label' => __( 'Delete Permanently', 'smart-question-answer' ),
			'title' => __( 'Delete post permanently (cannot be restored again)', 'smart-question-answer' ),
		);
	}

	// Convert question to a post.
	if ( ( is_super_admin() || current_user_can( 'manage_options' ) ) && 'question' === $_post->post_type ) {
		$actions[] = array(
			'cb'    => 'convert_to_post',
			'query' => array(
				'post_id' => $_post->ID,
				'__nonce' => wp_create_nonce( 'convert-post-' . $_post->ID ),
			),
			'label' => __( 'Convert to post', 'smart-question-answer' ),
			'title' => __( 'Convert this question to blog post', 'smart-question-answer' ),
		);
	}

	/**
	 * For filtering post actions buttons
	 *
	 * @var     array
	 * @since   2.0
	 */
	$actions = apply_filters( 'asqa_post_actions', array_filter( $actions ) );
	return array_values( $actions );
}

/**
 * Post actions buttons.
 *
 * @since   2.0
 */
function asqa_post_actions_buttons() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$args = wp_json_encode(
		array(
			'post_id' => get_the_ID(),
			'nonce'   => wp_create_nonce( 'post-actions-' . get_the_ID() ),
		)
	);

	echo '<postActions class="asqa-dropdown"><button class="asqa-btn apicon-gear asqa-actions-handle asqa-dropdown-toggle" ap="actiontoggle" apquery="' . esc_js( $args ) . '"></button><ul class="asqa-actions asqa-dropdown-menu"></ul></postActions>';
}

/**
 * Return all order by options for questions list.
 *
 * @param  string $current_url Current page URL.
 * @return array
 * @since  1.0.0 Moved from `asqa_question_sorting()`.
 */
function asqa_get_questions_orderby( $current_url = '' ) {
	$param    = array();
	$search_q = get_query_var( 'asqa_s' );

	if ( ! empty( $search_q ) ) {
		$param['asqa_s'] = $search_q;
	}

	$navs = array(
		array(
			'key'   => 'order_by',
			'value' => 'active',
			'label' => __( 'Active', 'smart-question-answer' ),
		),
		array(
			'key'   => 'order_by',
			'value' => 'newest',
			'label' => __( 'New', 'smart-question-answer' ),
		),
	);

	if ( ! asqa_opt( 'disable_voting_on_question' ) ) {
		$navs[] = array(
			'key'   => 'order_by',
			'value' => 'voted',
			'label' => __( 'Votes', 'smart-question-answer' ),
		);
	}

	$navs[] = array(
		'key'   => 'order_by',
		'value' => 'answers',
		'label' => __( 'Answers', 'smart-question-answer' ),
	);
	$navs[] = array(
		'key'   => 'order_by',
		'value' => 'views',
		'label' => __( 'Views', 'smart-question-answer' ),
	);
	$navs[] = array(
		'key'   => 'order_by',
		'value' => 'unanswered',
		'label' => __( 'Unanswered', 'smart-question-answer' ),
	);
	$navs[] = array(
		'key'   => 'order_by',
		'value' => 'unsolved',
		'label' => __( 'Unsolved', 'smart-question-answer' ),
	);

	foreach ( (array) $navs as $k => $args ) {
		$active = asqa_get_current_list_filters( 'order_by' );

		if ( $active === $args['value'] ) {
			$navs[ $k ]['active'] = true;
		}
	}

	/**
	 * Filter question sorting.
	 *
	 * @param array $navs Questions orderby list.
	 * @since 2.3
	 */
	return apply_filters( 'asqa_questions_order_by', $navs );
}


/**
 * Output answers tab.
 *
 * @param string|boolean $base Current page url.
 * @since 1.0.0
 */
function asqa_answers_tab( $base = false ) {
	$sort = asqa_sanitize_unslash( 'order_by', 'r', asqa_opt( 'answers_sort' ) );

	if ( ! $base ) {
		$base = get_permalink();
	}

	$navs = array(
		'active' => array(
			'link'  => add_query_arg( array( 'order_by' => 'active' ), $base ),
			'title' => __( 'Active', 'smart-question-answer' ),
		),
	);

	if ( ! asqa_opt( 'disable_voting_on_answer' ) ) {
		$navs['voted'] = array(
			'link'  => add_query_arg( array( 'order_by' => 'voted' ), $base ),
			'title' => __( 'Voted', 'smart-question-answer' ),
		);
	}

	$navs['newest'] = array(
		'link'  => add_query_arg( array( 'order_by' => 'newest' ), $base ),
		'title' => __( 'Newest', 'smart-question-answer' ),
	);
	$navs['oldest'] = array(
		'link'  => add_query_arg( array( 'order_by' => 'oldest' ), $base ),
		'title' => __( 'Oldest', 'smart-question-answer' ),
	);

	echo '<ul id="answers-order" class="asqa-answers-tab asqa-ul-inline clearfix">';
	foreach ( (array) $navs as $k => $nav ) {
		echo '<li' . ( $sort === $k ? ' class="active"' : '' ) . '><a href="' . esc_url( $nav['link'] . '#answers-order' ) . '">' . esc_attr( $nav['title'] ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Answer meta to display.
 *
 * @param false|integer $answer_id Answer id.
 * @return string
 * @since 1.0.0
 * @deprecated 1.0.0
 */
function asqa_display_answer_metas( $answer_id = false ) {
	_deprecated_function( __FUNCTION__, '1.0.0' );
	return;

	// @codingStandardsIgnoreStart
	if ( false === $answer_id ) {
		$answer_id = get_the_ID();
	}

	$metas = array();
	if ( asqa_is_selected( $answer_id ) ) {
		$metas['best_answer'] = '<span class="asqa-best-answer-label">' . __( 'Best answer', 'smart-question-answer' ) . '</span>';
	}

	$metas['history'] = asqa_last_active_time( $answer_id );

	/**
	 * Used to filter answer display meta.
	 *
	 * @since 1.0.0
	 * @deprecated 1.0.0
	 */
	$metas = apply_filters( 'asqa_display_answer_metas', $metas, $answer_id );

	$output = '';
	if ( ! empty( $metas ) && is_array( $metas ) ) {
		foreach ( $metas as $meta => $display ) {
			$output .= "<span class='asqa-display-meta-item {$meta}'>{$display}</span>";
		}
	}

	return $output;
	// @codingStandardsIgnoreEnd
}

/**
 * Echo ask button.
 *
 * @since 2.1
 */
function asqa_ask_btn() {
	echo asqa_get_ask_btn(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the ask button.
 *
 * @return string Ask button HTML
 * @since 2.1
 */
function asqa_get_ask_btn() {
	$link = asqa_get_link_to( 'ask' );

	/**
	 * Filter ask button link.
	 *
	 * @param string $link
	 */
	$link = apply_filters( 'asqa_ask_btn_link', $link );

	return '<a class="asqa-btn-ask" href="' . esc_url( $link ) . '">' . esc_attr__( 'Ask question', 'smart-question-answer' ) . '</a>';
}

/**
 * Include template php files.
 *
 * @param string      $file File name without extension.
 * @param array|false $args Arguments to be passed.
 * @since 2.1
 */
function asqa_get_template_part( $file, $args = false ) {
	if ( false !== $args ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	}

	include asqa_get_theme_location( $file . '.php' );
}

/**
 * Return current SmartQa page
 *
 * @param false|string $looking_for Looking for page.
 *
 * @return string|bool
 *
 * @since unknown
 * @since 1.0.0 Check if ask question page.
 * @since 1.0.0 Do not return `base` by default.
 * @since 1.0.0 If 404 do not return anything.
 * @since 1.0.0 Changed cache key which was causing conflict with core.
 * @since 1.0.05 Added parameter `$looking_for`.
 */
function asqa_current_page( $looking_for = false ) {
	$query_var  = get_query_var( 'asqa_page', '' );
	$main_pages = array_keys( asqa_main_pages() );
	$page_ids   = array();

	foreach ( $main_pages as $page_slug ) {
		$page_ids[ asqa_opt( $page_slug ) ] = $page_slug;
	}

	if ( is_question() || is_singular( 'question' ) ) {
		$query_var = 'question';
	} elseif ( 'edit' === $query_var ) {
		$query_var = 'edit';
	} elseif ( in_array( $query_var . '_page', $main_pages, true ) ) {
		$query_var = $query_var;
	} elseif ( in_array( get_the_ID(), array_keys( $page_ids ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
		$query_var = str_replace( '_page', '', $page_ids[ get_the_ID() ] );
	} elseif ( 'base' === $query_var ) {
		$query_var = 'base';
	} elseif ( is_404() ) {
		$query_var = '';
	}

	/**
	 * Filter SmartQa current page.
	 *
	 * @param    string $query_var Current page slug.
	 */
	$ret = apply_filters( 'asqa_current_page', esc_attr( $query_var ) );

	if ( false !== $looking_for ) {
		return $looking_for === $ret;
	}

	return $ret;
}

/**
 * SmartQa CSS and JS.
 *
 * @return array
 */
function asqa_assets() {
	wp_register_script( 'selectize', SMARTQA_URL . 'assets/js/lib/selectize.min.js', array( 'jquery' ), ASQA_VERSION, true );

	wp_register_script( 'smartqa-common', SMARTQA_URL . 'assets/js/common.js', array( 'jquery', 'jquery-form', 'underscore', 'backbone', 'selectize' ), ASQA_VERSION, true );
	wp_register_script( 'smartqa-question', SMARTQA_URL . 'assets/js/question.js', array( 'smartqa-common' ), ASQA_VERSION, true );
	wp_register_script( 'smartqa-ask', SMARTQA_URL . 'assets/js/ask.js', array( 'smartqa-common' ), ASQA_VERSION, true );
	wp_register_script( 'smartqa-list', SMARTQA_URL . 'assets/js/list.js', array( 'smartqa-common' ), ASQA_VERSION, true );
	wp_register_script( 'smartqa-notifications', SMARTQA_URL . 'assets/js/notifications.js', array( 'smartqa-common' ), ASQA_VERSION, true );
	wp_register_script( 'smartqa-theme', asqa_get_theme_url( 'js/theme.js', false, false ), array( 'smartqa-common', 'smartqa-question', 'smartqa-ask', 'smartqa-list', 'smartqa-notifications' ), ASQA_VERSION, true );

		$script_params = array(
    'asqa_maximum_uploads' => round( asqa_opt( 'max_upload_size' ) / ( 1024 * 1024 ), 2 ) 
);
	wp_localize_script( 'smartqa-theme', 'scriptParams', $script_params );
	

	wp_register_style( 'smartqa-fonts', asqa_get_theme_url( 'css/fonts.css', false, false ), array(), ASQA_VERSION );
	wp_register_style( 'smartqa-main', asqa_get_theme_url( 'css/main.css', false, false ), array( 'smartqa-fonts' ), ASQA_VERSION );
	wp_register_style( 'smartqa-rtl', asqa_get_theme_url( 'css/rtl.css', false, false ), array( 'smartqa-main' ), ASQA_VERSION );

	return array();
}

/**
 * Enqueue SmartQa assets.
 *
 * @since 2.4.6
 */
function asqa_enqueue_scripts() {
	if ( asqa_current_page() !== '' ) {
		wp_enqueue_script( 'smartqa-theme' );
	}

	wp_enqueue_style( 'smartqa-main' );

	if ( is_rtl() ) {
		wp_enqueue_style( 'smartqa-rtl' );
	}
}

/**
 * Get all list filters.
 */
function asqa_get_list_filters() {
	$param    = array();
	$search_q = get_query_var( 'asqa_s' );

	if ( ! empty( $search_q ) ) {
		$param['asqa_s'] = $search_q;
	}

	$filters = array(
		'order_by' => array(
			'title'    => __( 'Order By', 'smart-question-answer' ),
			'items'    => array(),
			'multiple' => false,
		),
	);

	/*
	 * Filter question sorting.
	 * @param array Question sortings.
	 * @since 2.3
	 */
	return apply_filters( 'asqa_list_filters', $filters );
}

/**
 * Output list filters form.
 *
 * @param string $current_url Current Url.
 */
function asqa_list_filters( $current_url = '' ) {
	$filters = asqa_get_list_filters();

	echo '<form id="asqa-filters" class="asqa-filters clearfix" method="GET">';

	foreach ( (array) $filters as $key => $filter ) {
		$active = '';

		$current_order_by = asqa_get_current_list_filters( 'order_by' );

		if ( ! empty( $current_order_by ) ) {
			$active_arr = asqa_search_array( asqa_get_questions_orderby(), 'value', $current_order_by );

			if ( ! empty( $active_arr ) ) {
				$active = ': <span class="asqa-filter-active">' . $active_arr[0]['label'] . '</span>';
			}
		}

		$active = apply_filters( 'asqa_list_filter_active_' . $key, $active, $filter );

		$args = wp_json_encode(
			array(
				'__nonce' => wp_create_nonce( 'filter_' . $key ),
				'filter'  => $key,
			)
		);
		echo '<div class="asqa-dropdown asqa-filter filter-' . esc_attr( $key ) . '">';
		echo '<a class="asqa-dropdown-toggle asqa-filter-toggle" href="#" asqa-filter apquery="' . esc_js( $args ) . '">' . esc_attr( $filter['title'] ) . wp_kses_post( $active ) . '</a>';
		echo '</div>';
	}

	echo '<button id="asqa-filter-reset" type="submit" name="reset-filter" title="' . esc_attr__( 'Reset sorting and filter', 'smart-question-answer' ) . '"><i class="apicon-x"></i><span>' . esc_attr__( 'Clear Filter', 'smart-question-answer' ) . '</span></button>';

	foreach ( (array) asqa_get_current_list_filters() as $key => $value ) {
		if ( ! is_array( $value ) ) {
			echo '<input type="hidden" value="' . esc_attr( $value ) . '" name="' . esc_attr( $key ) . '" />';
		} else {
			foreach ( (array) $value as $v ) {
				echo '<input type="hidden" value="' . esc_attr( $v ) . '" name="' . esc_attr( $key ) . '[]" />';
			}
		}
	}

	echo '</form>';
}
/**
 * Print select anser HTML button.
 *
 * @param mixed $_post Post.
 * @return string
 */
function asqa_select_answer_btn_html( $_post = null ) {
	if ( ! asqa_user_can_select_answer( $_post ) ) {
		return;
	}

	$_post = asqa_get_post( $_post );
	$nonce = wp_create_nonce( 'select-answer-' . $_post->ID );

	$q = wp_json_encode(
		array(
			'answer_id' => $_post->ID,
			'__nonce'   => $nonce,
		)
	);

	$active = false;

	$title = __( 'Select this answer as best', 'smart-question-answer' );
	$label = __( 'Select', 'smart-question-answer' );

	$have_best = asqa_have_answer_selected( $_post->post_parent );
	$selected  = asqa_is_selected( $_post );
	$hide      = false;

	if ( $have_best && $selected ) {
		$title  = __( 'Unselect this answer', 'smart-question-answer' );
		$label  = __( 'Unselect', 'smart-question-answer' );
		$active = true;
	}

	if ( $have_best && ! $selected ) {
		$hide = true;
	}

	return '<a href="#" class="asqa-btn-select asqa-btn ' . ( $active ? ' active' : '' ) . ( $hide ? ' hide' : '' ) . '" ap="select_answer" apquery="' . esc_js( $q ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $label ) . '</a>';
}

/**
 * Output chnage post status button.
 *
 * @param   mixed $_post Post.
 * @return  null|string
 * @since   4.0.0
 */
function asqa_post_status_btn_args( $_post = null ) {
	$_post = asqa_get_post( $_post );
	$args  = array();

	if ( 'trash' === $_post->post_status ) {
		return $args;
	}

	if ( asqa_user_can_change_status( $_post->ID ) ) {
		global $wp_post_statuses;
		$allowed_status = array( 'publish', 'private_post', 'moderate' );
		$status_labels  = array();

		foreach ( (array) $allowed_status as $s ) {
			if ( isset( $wp_post_statuses[ $s ] ) ) {
				$status_labels[ $s ] = esc_attr( $wp_post_statuses[ $s ]->label );
			}
		}

		foreach ( (array) $status_labels as $slug => $label ) {
			$can = true;

			if ( 'moderate' === $slug && ! asqa_user_can_change_status_to_moderate() ) {
				$can = false;
			}

			if ( $can ) {
				$args[] = array(
					'cb'     => 'status',
					'active' => ( $slug === $_post->post_status ),
					'query'  => array(
						'status'  => $slug,
						'__nonce' => wp_create_nonce( 'change-status-' . $slug . '-' . $_post->ID ),
						'post_id' => $_post->ID,
					),
					'label'  => esc_attr( $label ),
				);
			}
		}

		return $args;
	}
}


/**
 * Return set featured question action args.
 *
 * @param  boolean|integer $post_id Post ID.
 * @return array
 */
function asqa_featured_post_args( $post_id = false ) {
	if ( ! is_user_logged_in() || ! asqa_user_can_toggle_featured() ) {
		return array();
	}

	if ( false === $post_id ) {
		$post_id = get_question_id();
	}

	$is_featured = asqa_is_featured_question( $post_id );

	if ( $is_featured ) {
		$title = __( 'Unmark this question as featured', 'smart-question-answer' );
		$label = __( 'Unfeature', 'smart-question-answer' );
	} else {
		$title = __( 'Mark this question as featured', 'smart-question-answer' );
		$label = __( 'Feature', 'smart-question-answer' );
	}

	return array(
		'cb'     => 'toggle_featured',
		'active' => $is_featured,
		'query'  => array(
			'__nonce' => wp_create_nonce( 'set_featured_' . $post_id ),
			'post_id' => $post_id,
		),
		'title'  => esc_attr( $title ),
		'label'  => esc_attr( $label ),
	);
}

/**
 * Output question subscribe button.
 *
 * @param object|integer|false $_post Post object or ID.
 * @param boolean              $echo Echo or return.
 * @return string|null
 * @since 4.0.0
 */
function asqa_subscribe_btn( $_post = false, $echo = true ) {
	$_post = asqa_get_post( $_post );

	$args        = wp_json_encode(
		array(
			'__nonce' => wp_create_nonce( 'subscribe_' . $_post->ID ),
			'id'      => $_post->ID,
		)
	);
	$subscribers = (int) asqa_get_post_field( 'subscribers', $_post );
	$subscribed  = asqa_is_user_subscriber( 'question', $_post->ID );
	$label       = $subscribed ? __( 'Unsubscribe', 'smart-question-answer' ) : __( 'Subscribe', 'smart-question-answer' );

	$html = '<a href="#" class="asqa-btn asqa-btn-subscribe asqa-btn-small ' . ( $subscribed ? 'active' : '' ) . '" apsubscribe apquery="' . esc_js( $args ) . '">' . esc_attr( $label ) . '<span class="apsubscribers-count">' . esc_attr( $subscribers ) . '</span></a>';

	if ( ! $echo ) {
		return $html;
	}

	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Create array of object containing SmartQa pages. To be used in admin menu metabox.
 *
 * @return array
 * @since unknown
 * @since 1.0.0 Improved ask page object.
 */
function asqa_menu_obejct() {
	$menu_items = array();

	foreach ( (array) smartqa()->pages as $k => $args ) {
		if ( $args['show_in_menu'] ) {
			$object_id = 1;
			$object    = $k;
			$title     = $args['title'];
			$url       = home_url( '/' );
			$type      = 'smartqa-links';

			$main_pages = array_keys( asqa_main_pages() );

			if ( in_array( $k . '_page', $main_pages, true ) ) {
				$post      = get_post( asqa_opt( $k . '_page' ) );
				$object_id = asqa_opt( $k . '_page' );
				$object    = 'page';
				$url       = get_permalink( $post );
				$title     = $post->post_title;
				$type      = 'post_type';
			}

			$menu_items[] = (object) array(
				'ID'               => 1,
				'db_id'            => 0,
				'menu_item_parent' => 0,
				'object_id'        => $object_id,
				'post_parent'      => 0,
				'type'             => $type,
				'object'           => $object,
				'type_label'       => __( 'SmartQa links', 'smart-question-answer' ),
				'title'            => $title,
				'url'              => $url,
				'target'           => '',
				'attr_title'       => '',
				'description'      => '',
				'classes'          => array( 'smartqa-menu-' . $k ),
				'xfn'              => '',
			);
		}
	}

	/**
	 * Hook for filtering default SmartQa menu objects.
	 *
	 * @param array $menu_items Array of menu objects.
	 * @since 1.0.0
	 */
	return apply_filters( 'asqa_menu_object', $menu_items );
}

/**
 * Return SmartQa page slug.
 *
 * @param string $slug Default page slug.
 * @return string
 */
function asqa_get_page_slug( $slug ) {
	$option = asqa_opt( $slug . '_page_slug' );

	if ( ! empty( $option ) ) {
		$slug = $option;
	}

	return apply_filters( 'asqa_page_slug_' . $slug, $slug );
}

/**
 * This fun little function fills up some WordPress globals with dummy data to
 * stop your average page template from complaining about it missing.
 *
 * @since 1.0.0
 * @global WP_Query $wp_query
 * @global object $post
 * @param array $args Arguments.
 */
function asqa_theme_compat_reset_post( $args = array() ) {
	global $wp_query, $post;

	// Switch defaults if post is set.
	if ( isset( $wp_query->post ) ) {
		$dummy = wp_parse_args(
			$args,
			array(
				'ID'                    => $wp_query->post->ID,
				'post_status'           => $wp_query->post->post_status,
				'post_author'           => $wp_query->post->post_author,
				'post_parent'           => $wp_query->post->post_parent,
				'post_type'             => $wp_query->post->post_type,
				'post_date'             => $wp_query->post->post_date,
				'post_date_gmt'         => $wp_query->post->post_date_gmt,
				'post_modified'         => $wp_query->post->post_modified,
				'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
				'post_content'          => $wp_query->post->post_content,
				'post_title'            => $wp_query->post->post_title,
				'post_excerpt'          => $wp_query->post->post_excerpt,
				'post_content_filtered' => $wp_query->post->post_content_filtered,
				'post_mime_type'        => $wp_query->post->post_mime_type,
				'post_password'         => $wp_query->post->post_password,
				'post_name'             => $wp_query->post->post_name,
				'guid'                  => $wp_query->post->guid,
				'menu_order'            => $wp_query->post->menu_order,
				'pinged'                => $wp_query->post->pinged,
				'to_ping'               => $wp_query->post->to_ping,
				'ping_status'           => $wp_query->post->ping_status,
				'comment_status'        => $wp_query->post->comment_status,
				'comment_count'         => $wp_query->post->comment_count,
				'filter'                => $wp_query->post->filter,

				'is_404'                => false,
				'is_page'               => false,
				'is_single'             => false,
				'is_archive'            => false,
				'is_tax'                => false,
			)
		);
	} else {
		$dummy = wp_parse_args(
			$args,
			array(
				'ID'                    => -9999,
				'post_status'           => 'publish',
				'post_author'           => 0,
				'post_parent'           => 0,
				'post_type'             => 'page',
				'post_date'             => 0,
				'post_date_gmt'         => 0,
				'post_modified'         => 0,
				'post_modified_gmt'     => 0,
				'post_content'          => '',
				'post_title'            => '',
				'post_excerpt'          => '',
				'post_content_filtered' => '',
				'post_mime_type'        => '',
				'post_password'         => '',
				'post_name'             => '',
				'guid'                  => '',
				'menu_order'            => 0,
				'pinged'                => '',
				'to_ping'               => '',
				'ping_status'           => '',
				'comment_status'        => 'closed',
				'comment_count'         => 0,
				'filter'                => 'raw',

				'is_404'                => false,
				'is_page'               => false,
				'is_single'             => false,
				'is_archive'            => false,
				'is_tax'                => false,
			)
		);
	}

	// Bail if dummy post is empty.
	if ( empty( $dummy ) ) {
		return;
	}

	// Set the $post global.
	$post = new WP_Post( (object) $dummy ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	// Copy the new post global into the main $wp_query.
	$wp_query->post  = $post;
	$wp_query->posts = array( $post );

	// Prevent comments form from appearing.
	$wp_query->post_count = 1;
	$wp_query->is_404     = $dummy['is_404'];
	$wp_query->is_page    = $dummy['is_page'];
	$wp_query->is_single  = $dummy['is_single'];
	$wp_query->is_archive = $dummy['is_archive'];
	$wp_query->is_tax     = $dummy['is_tax'];

	// Clean up the dummy post.
	unset( $dummy );

	if ( ! $wp_query->is_404() ) {
		status_header( 200 );
	}

	// If we are resetting a post, we are in theme compat.
	smartqa()->theme_compat->active = true;
}
