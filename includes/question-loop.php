<?php
/**
 * Question class
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-3.0+
 * @link      https://extensionforge.com/
 * @copyright 2014 Peter Mertzlin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'asqa_get_questions' ) ) {

	/**
	 * Get questions query.
	 *
	 * @param array $args WP_Query arguments.
	 * @return Question_Query
	 */
	function asqa_get_questions( $args = array() ) {
		if ( is_front_page() ) {
			$paged = (int) asqa_sanitize_unslash( 'asqa_paged', 'g', 1 );
		} else {
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		}

		if ( ! isset( $args['post_parent'] ) ) {
			$args['post_parent'] = get_query_var( 'parent' ) ? get_query_var( 'parent' ) : false;
		}

		$args = wp_parse_args(
			$args,
			array(
				'showposts' => asqa_opt( 'question_per_page' ),
				'paged'     => $paged,
				'asqa_query'  => 'featured_post',
			)
		);

		return new Question_Query( $args );
	}
}


/**
 * Get an question by ID.
 *
 * @param  integer $question_id Question ID.
 * @return Question_Query
 * @since 2.1
 */
function asqa_get_question( $question_id ) {
	$args = array(
		'p'           => $question_id,
		'asqa_query'    => 'single_question',
		'post_status' => array( 'publish' ),
	);

	if ( asqa_user_can_view_future_post( $question_id ) ) {
		$args['post_status'][] = 'future';
	}

	if ( asqa_user_can_view_private_post( $question_id ) ) {
		$args['post_status'][] = 'private_post';
	}

	if ( asqa_user_can_view_moderate_post( $question_id ) ) {
		$args['post_status'][] = 'moderate';
	}

	return new Question_Query( $args );
}

/**
 * Output questions page pagination.
 *
 * @param integer|false $paged Current paged value.
 *
 * @return void
 * @since 4.1.0 Added new argument `$paged`.
 */
function asqa_questions_the_pagination( $paged = false ) {
	if ( is_front_page() ) {
		$paged = get_query_var( 'page' );
	} elseif ( get_query_var( 'asqa_paged' ) ) {
		$paged = get_query_var( 'asqa_paged' );
	} elseif ( get_query_var( 'paged' ) ) {
		$paged = get_query_var( 'paged' );
	}

	asqa_pagination( $paged, smartqa()->questions->max_num_pages );
}
