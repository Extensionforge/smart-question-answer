<?php
/**
 * Plugin rewrite rules and query variables
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-2.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * This class handle all rewrite rules and define query variable of smartqa
 *
 * @since 2.0.0
 */
class SmartQa_Rewrite {
	/**
	 * Internal increment.
	 *
	 * @var int
	 */
	private static $counter = 1;

	/**
	 * Filter global request array.
	 *
	 * @param  array $request Request array.
	 * @return array
	 * @since  4.1.0
	 */
	public static function alter_the_query( $request ) {
		if ( isset( $request['post_type'] ) && 'answer' === $request['post_type'] ) {
			if ( ! empty( $request['feed'] ) ) {
				unset( $request['question_id'] );
				unset( $request['answer'] );
			}

			if ( isset( $request['embed'] ) && 'true' === $request['embed'] ) {
				$request['p'] = $request['answer_id'];
			}
		}

		return $request;
	}

	/**
	 * Register query vars
	 *
	 * @param  array $query_vars Registered query variables.
	 * @return array
	 *
	 * @since 4.1.11 Fixed 'answer_id' is inserted twice.
	 */
	public static function query_var( $query_vars ) {
		$query_vars[] = 'edit_post_id';
		$query_vars[] = 'asqa_nonce';
		$query_vars[] = 'question_id';
		$query_vars[] = 'answer_id';
		$query_vars[] = 'answer';
		$query_vars[] = 'ask';
		$query_vars[] = 'asqa_page';
		$query_vars[] = 'qcat_id';
		$query_vars[] = 'qcat';
		$query_vars[] = 'qtag_id';
		$query_vars[] = 'q_tag';
		$query_vars[] = 'asqa_s';
		$query_vars[] = 'parent';
		$query_vars[] = 'asqa_user';
		$query_vars[] = 'user_page';
		$query_vars[] = 'asqa_paged';

		return $query_vars;
	}

	/**
	 * Generate rewrite rules for SmartQa.
	 *
	 * @return void
	 * @since 4.1.0
	 */
	public static function rewrite_rules() {
		global $wp_rewrite;
		$q_struct = SmartQa_PostTypes::question_perm_structure();
		$rules    = $wp_rewrite->generate_rewrite_rules( $q_struct->rule, EP_NONE, false, false, true );

		$rule = key( $rules );

		smartqa()->question_rule = array(
			'rule'    => substr( $rule, 0, -3 ),
			'rewrite' => reset( $rules ),
		);
	}

	/**
	 * Rewrite rules.
	 *
	 * @return array
	 */
	public static function rewrites() {
		global $wp_rewrite;

		$rule         = smartqa()->question_rule['rule'];
		$rewrite      = smartqa()->question_rule['rewrite'];
		$all_rules    = array();
		$base_page_id = asqa_opt( 'base_page' );
		$slug_main    = asqa_base_page_slug();
		$lang_rule    = '';
		$lang_rewrite = '';

		// Support polylang permalink.
		if ( function_exists( 'pll_languages_list' ) ) {
			if ( ! empty( pll_languages_list() ) ) {
				$lang_rule    = '(' . implode( '|', pll_languages_list() ) . ')/';
				$lang_rewrite = '&lang=$matches[#]';
			}
		}

		$slug         = $lang_rule . $slug_main . '/';
		$base_page_id = $base_page_id . $lang_rewrite;

		$answer_rewrite = str_replace( 'post_type=question', 'post_type=answer', $rewrite );
		$answer_rewrite = str_replace( '&question=', '&question_slug=', $answer_rewrite );
		$answer_rewrite = str_replace( '&p=', '&question_id=', $answer_rewrite );

		$all_rules = array(
			$slug . 'search/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?s=$matches[#]&paged=$matches[#]&post_type=question',
			$slug . 'search/([^/]+)/?$'                   => 'index.php?s=$matches[#]&post_type=question',
			$slug . 'edit/?$'                             => 'index.php?pagename=' . $slug_main . '&asqa_page=edit',
			$rule . '/answer/([0-9]+)/(feed|rdf|rss|rss2|atom)/?$' => $answer_rewrite . '&answer_id=$matches[#]&feed=$matches[#]',
			$rule . '/answer/([0-9]+)/embed/?$'           => $answer_rewrite . '&answer_id=$matches[#]&embed=true',
			$rule . '/answer/([0-9]+)/?$'                 => $rewrite . '&answer_id=$matches[#]',
			$rule . '/page/?([0-9]{1,})/?$'               => $rewrite . '&asqa_paged=$matches[#]',
			$rule . '/(feed|rdf|rss|rss2|atom)/?$'        => $rewrite . '&feed=$matches[#]',
			$rule . '/embed/?$'                           => $rewrite . '&embed=true',
			$rule . '/?$'                                 => $rewrite,

		);

		/**
		 * Allows filtering SmartQa rewrite rules.
		 *
		 * @param array $all_rules Rewrite rules.
		 * @since 4.1.0
		 */
		$all_rules = apply_filters( 'asqa_rewrites', $all_rules, $slug, $base_page_id );
		$asqa_rules  = array();

		foreach ( $all_rules as $r => $re ) {
			$re             = preg_replace( '/\\$([1-9]+)/', '$matches[#]', $re );
			$re             = preg_replace_callback( '/\#/', array( __CLASS__, 'incr_hash' ), $re );
			$asqa_rules[ $r ] = $re;
			self::$counter  = 1;
		}
		$front             = ltrim( $wp_rewrite->front, '/' );
		$wp_rewrite->rules = asqa_array_insert_after( $wp_rewrite->rules, $front . 'type/([^/]+)/?$', $asqa_rules );
		return $wp_rewrite->rules;
	}

	/**
	 * Increment hash.
	 *
	 * @param array $matches Matches.
	 */
	public static function incr_hash( $matches ) {
		return self::$counter++;
	}

	/**
	 * BuddyPress pagination fix.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public static function bp_com_paged( $args ) {
		if ( function_exists( 'bp_current_component' ) ) {
			$bp_com = bp_current_component();

			if ( 'questions' === $bp_com || 'answers' === $bp_com ) {
				return preg_replace( '/page.([0-9]+)./', '?paged=$1', $args );
			}
		}

		return $args;
	}

	/**
	 * Push custom query args in `$wp`.
	 *
	 * @param object $wp WP query object.
	 */
	public static function add_query_var( $wp ) {
		if ( ! empty( $wp->query_vars['asqa_user'] ) ) {
			$user = get_user_by( 'login', sanitize_text_field( urldecode( $wp->query_vars['asqa_user'] ) ) );

			if ( $user ) {
				$wp->set_query_var( 'asqa_user_id', (int) $user->ID );
			} else {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				get_template_part( 404 );
				exit();
			}
		}
	}

	/**
	 * Handles shortlink redirects.
	 *
	 * @since unknown
	 * @since 4.1.6 Fixed: question and answer links are redirected to home.
	 */
	public static function shortlink() {
		global $wp_query;
		$page = get_query_var( 'asqa_page' );

		if ( empty( $page ) || 'shortlink' !== $page ) {
			return;
		}

		$post_id = asqa_isset_post_value( 'asqa_q', asqa_isset_post_value( 'asqa_a', false ) );
		$post_id = asqa_isset_post_value( 'asqa_p', $post_id );

		// Post redirect.
		if ( $post_id ) {
			$permalink = get_permalink( $post_id );
			wp_redirect( $permalink, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		// Comment redirect.
		if ( asqa_isset_post_value( 'asqa_c', false ) ) {
			$permalink = get_comment_link( asqa_isset_post_value( 'asqa_c' ) );
			wp_redirect( $permalink, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		// User redirect.
		if ( asqa_isset_post_value( 'asqa_u', false ) ) {
			$permalink = asqa_user_link( asqa_isset_post_value( 'asqa_u' ), asqa_isset_post_value( 'sub' ) );
			wp_redirect( $permalink, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}
	}
}
