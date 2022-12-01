<?php
/**
 * SmartQas admin ajax class
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-2.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 * @since 1.0.0 Fixed: CS bugs.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package SmartQa
 */
class SmartQa_Admin_Ajax {
	/**
	 * Initialize admin ajax
	 */
	public static function init() {
		smartqa()->add_action( 'wp_ajax_asqa_delete_flag', __CLASS__, 'asqa_delete_flag' );
		smartqa()->add_action( 'asqa_ajax_asqa_clear_flag', __CLASS__, 'clear_flag' );
		smartqa()->add_action( 'asqa_ajax_asqa_admin_vote', __CLASS__, 'asqa_admin_vote' );
		smartqa()->add_action( 'asqa_ajax_get_all_answers', __CLASS__, 'get_all_answers' );
		smartqa()->add_action( 'wp_ajax_asqa_uninstall_data', __CLASS__, 'asqa_uninstall_data' );
		smartqa()->add_action( 'wp_ajax_asqa_toggle_addon', __CLASS__, 'asqa_toggle_addon' );
		smartqa()->add_action( 'wp_ajax_asqa_recount_votes', __CLASS__, 'recount_votes' );
		smartqa()->add_action( 'wp_ajax_asqa_recount_answers', __CLASS__, 'recount_answers' );
		smartqa()->add_action( 'wp_ajax_asqa_recount_flagged', __CLASS__, 'recount_flagged' );
		smartqa()->add_action( 'wp_ajax_asqa_recount_subscribers', __CLASS__, 'recount_subscribers' );
		smartqa()->add_action( 'wp_ajax_asqa_recount_reputation', __CLASS__, 'recount_reputation' );
		smartqa()->add_action( 'wp_ajax_asqa_recount_views', __CLASS__, 'recount_views' );
	}

	/**
	 * Delete post flag
	 */
	public static function asqa_delete_flag() {
		$post_id = (int) asqa_sanitize_unslash( 'id', 'p' );

		if ( asqa_verify_nonce( 'flag_delete' . $post_id ) && current_user_can( 'manage_options' ) ) {
			asqa_set_flag_count( $post_id, 0 );
		}

		wp_die();
	}

	/**
	 * Clear post flags.
	 *
	 * @since 2.4.6
	 */
	public static function clear_flag() {
		$post_id = asqa_sanitize_unslash( 'post_id', 'p' );

		if ( current_user_can( 'manage_options' ) && asqa_verify_nonce( 'clear_flag_' . $post_id ) ) {
			asqa_delete_flags( $post_id, 'flag' );
			echo 0;
		}

		wp_die();
	}

	/**
	 * Handle ajax vote in wp-admin post edit screen.
	 * Cast vote as guest user with ID 0, so that when this vote never get
	 * rest if user vote.
	 *
	 * @since 2.5
	 */
	public static function asqa_admin_vote() {
		$args = asqa_sanitize_unslash( 'args', 'p' );

		if ( current_user_can( 'manage_options' ) && asqa_verify_nonce( 'admin_vote' ) ) {
			$post = asqa_get_post( $args[0] );

			if ( $post ) {
				$value  = 'up' === $args[1] ? true : false;
				$counts = asqa_add_post_vote( $post->ID, 0, $value );
				echo esc_attr( $counts['votes_net'] );
			}
		}
		die();
	}

	/**
	 * Ajax callback to get all answers. Used in wp-admin post edit screen to show
	 * all answers of a question.
	 *
	 * @since 4.0
	 */
	public static function get_all_answers() {
		global $answers;

		$question_id = asqa_sanitize_unslash( 'question_id', 'p' );
		$answers_arr = array();
		$answers     = asqa_get_answers( array( 'question_id' => $question_id ) );

		while ( asqa_have_answers() ) :
			asqa_the_answer();
			global $post, $wp_post_statuses;
			if ( asqa_user_can_view_post() ) :
				$answers_arr[] = array(
					'ID'        => get_the_ID(),
					'content'   => get_the_content(),
					'avatar'    => asqa_get_author_avatar( 30 ),
					'author'    => asqa_user_display_name( $post->post_author ),
					'activity'  => asqa_get_recent_post_activity(),
					'editLink'  => esc_url_raw( get_edit_post_link() ),
					'trashLink' => esc_url_raw( get_delete_post_link() ),
					'status'    => esc_attr( $wp_post_statuses[ $post->post_status ]->label ),
					'selected'  => asqa_get_post_field( 'selected' ),
				);
			endif;
		endwhile;

		wp_send_json( $answers_arr );

		wp_die();
	}

	/**
	 * Uninstall actions.
	 *
	 * @since 4.0.0
	 */
	public static function asqa_uninstall_data() {
		check_ajax_referer( 'asqa_uninstall_data', '__nonce' );

		$data_type  = asqa_sanitize_unslash( 'data_type', 'r' );
		$valid_data = array( 'qa', 'answers', 'options', 'userdata', 'terms', 'tables' );

		global $wpdb;

		// Only allow super admin to delete data.
		if ( is_super_admin() && in_array( $data_type, $valid_data, true ) ) {
			$done = 0;

			if ( 'qa' === $data_type ) {
				$count = $wpdb->get_var( "SELECT count(*) FROM $wpdb->posts WHERE post_type='question' OR post_type='answer'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				$ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type='question' OR post_type='answer' LIMIT 30" );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				foreach ( (array) $ids as $id ) {
					if ( false !== wp_delete_post( $id, true ) ) {
						$done++;
					}
				}

				wp_send_json(
					array(
						'done'  => (int) $done,
						'total' => (int) $count,
					)
				);
			} elseif ( 'answers' === $data_type ) {
				$count = $wpdb->get_var( "SELECT count(*) FROM $wpdb->posts WHERE post_type='answer'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$ids   = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type='answer' LIMIT 30" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				foreach ( (array) $ids as $id ) {
					if ( false !== wp_delete_post( $id, true ) ) {
						$done++;
					}
				}

				wp_send_json(
					array(
						'done'  => (int) $done,
						'total' => (int) $count,
					)
				);
			} elseif ( 'userdata' === $data_type ) {
				$upload_dir = wp_upload_dir();

				// Delete avatar folder.
				wp_delete_file( $upload_dir['baseurl'] . '/asqa_avatars' );

				// Remove user roles.
				ASQA_Roles::remove_roles();

				// Delete vote meta.
				$wpdb->delete( $wpdb->usermeta, [ 'meta_key' => '__up_vote_casted' ], array( '%s' ) ); // @codingStandardsIgnoreLine
				$wpdb->delete( $wpdb->usermeta, [ 'meta_key' => '__down_vote_casted' ], array( '%s' ) ); // @codingStandardsIgnoreLine

				wp_send_json(
					array(
						'done'  => 1,
						'total' => 0,
					)
				);
			} elseif ( 'options' === $data_type ) {
				delete_option( 'smartqa_opt' );
				delete_option( 'smartqa_reputation_events' );
				delete_option( 'smartqa_addons' );

				wp_send_json(
					array(
						'done'  => 1,
						'total' => 0,
					)
				);
			} elseif ( 'terms' === $data_type ) {
				$question_taxo = (array) get_object_taxonomies( 'question', 'names' );
				$answer_taxo   = (array) get_object_taxonomies( 'answer', 'names' );

				$taxos = $question_taxo + $answer_taxo;

				foreach ( (array) $taxos as $tax ) {
					$terms = get_terms(
						array(
							'taxonomy'   => $tax,
							'hide_empty' => false,
							'fields'     => 'ids',
						)
					);

					foreach ( (array) $terms as $t ) {
						wp_delete_term( $t, $tax );
					}
				}

				wp_send_json(
					array(
						'done'  => 1,
						'total' => 0,
					)
				);
			} elseif ( 'tables' === $data_type ) {
				$tables = array( $wpdb->asqa_qameta, $wpdb->asqa_votes, $wpdb->asqa_views, $wpdb->asqa_reputations, $wpdb->asqa_subscribers, $wpdb->prefix . 'asqa_notifications' );

				foreach ( $tables as $table ) {
					$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB
				}

				wp_send_json(
					array(
						'done'  => 1,
						'total' => 0,
					)
				);
			}
		}

		// Send empty JSON if nothing done.
		wp_send_json( array() );
	}

	/**
	 * Toggle addons.
	 */
	public static function asqa_toggle_addon() {
		check_ajax_referer( 'toggle_addon', '__nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Sorry, you do not have permission!', 'smart-question-answer' ) ),
				)
			);
		}

		$addon_id = asqa_sanitize_unslash( 'addon_id', 'r' );
		if ( asqa_is_addon_active( $addon_id ) ) {
			asqa_deactivate_addon( $addon_id );
		} else {
			asqa_activate_addon( $addon_id );
		}

		// Delete page check transient.
		delete_transient( 'asqa_pages_check' );

		asqa_ajax_json(
			array(
				'success'  => true,
				'addon_id' => $addon_id,
				'snackbar' => array( 'message' => __( 'Successfully enabled addon. Redirecting!', 'smart-question-answer' ) ),
				'cb'       => 'toggleAddon',
			)
		);
	}

	/**
	 * Ajax callback for 'asqa_recount_votes` which recounting votes of posts.
	 *
	 * @return void
	 * @since 4.0.5
	 */
	public static function recount_votes() {
		if ( ! asqa_verify_nonce( 'recount_votes' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$paged  = (int) asqa_sanitize_unslash( 'paged', 'r', 0 );
		$offset = absint( $paged * 100 );

		global $wpdb;

		$ids = $wpdb->get_col( "SELECT SQL_CALC_FOUND_ROWS ID FROM {$wpdb->posts} WHERE post_type IN ('question', 'answer') LIMIT {$offset},100" ); // @codingStandardsIgnoreLine.

		$total_found = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // DB call okay, Db cache okay.

		foreach ( (array) $ids as $id ) {
			asqa_update_votes_count( $id );
		}

		$done   = $offset + count( $ids );
		$remain = $total_found - ( $offset + count( $ids ) );

		$json = array(
			'success' => true,
			'total'   => $total_found,
			'remain'  => $remain,
			'el'      => '.asqa-recount-votes',
			// translators: %1 is total completed, %2 is total found count.
			'msg'     => sprintf( __( '%1$d done out of %2$d', 'smart-question-answer' ), $done, $total_found ),
		);

		if ( $remain > 0 ) {
			$json['q'] = array(
				'action'  => 'asqa_recount_votes',
				'__nonce' => wp_create_nonce( 'recount_votes' ),
				'paged'   => $paged + 1,
			);
		}

		asqa_send_json( $json );
	}

	/**
	 * Ajax callback for 'asqa_recount_answers` which recounting answers of questions.
	 *
	 * @return void
	 * @since 4.0.5
	 */
	public static function recount_answers() {
		if ( ! asqa_verify_nonce( 'recount_answers' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$paged  = (int) asqa_sanitize_unslash( 'paged', 'r', 0 );
		$offset = absint( $paged * 100 );

		global $wpdb;

		$ids = $wpdb->get_col( "SELECT SQL_CALC_FOUND_ROWS ID FROM {$wpdb->posts} WHERE post_type = 'question' LIMIT {$offset},100" ); // phpcs:ignore WordPress.DB

		// @todo Do not use FOUND_ROWS().
		$total_found = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // phpcs:ignore WordPress.DB

		foreach ( (array) $ids as $id ) {
			asqa_update_answers_count( $id, false, false );
		}

		$done   = $offset + count( $ids );
		$remain = $total_found - ( $offset + count( $ids ) );

		$json = array(
			'success' => true,
			'total'   => $total_found,
			'remain'  => $remain,
			'el'      => '.asqa-recount-answers',
			// translators: %1 is total completed, %2 is total found count.
			'msg'     => sprintf( __( '%1$d done out of %2$d', 'smart-question-answer' ), $done, $total_found ),
		);

		if ( $remain > 0 ) {
			$json['q'] = array(
				'action'  => 'asqa_recount_answers',
				'__nonce' => wp_create_nonce( 'recount_answers' ),
				'paged'   => $paged + 1,
			);
		}

		asqa_send_json( $json );
	}

	/**
	 * Recount flags of posts.
	 *
	 * @return void
	 * @since 4.0.5
	 */
	public static function recount_flagged() {
		if ( ! asqa_verify_nonce( 'recount_flagged' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		global $wpdb;

		$paged  = (int) asqa_sanitize_unslash( 'paged', 'r', 0 );
		$offset = absint( $paged * 100 );

		$ids = $wpdb->get_col( "SELECT SQL_CALC_FOUND_ROWS ID FROM {$wpdb->posts} WHERE post_type IN ('question', 'answer') LIMIT {$offset},100" ); // @codingStandardsIgnoreLine.

		$total_found = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // DB call okay, Db cache okay.

		foreach ( (array) $ids as $id ) {
			asqa_update_flags_count( $id );
		}

		$done   = $offset + count( $ids );
		$remain = $total_found - ( $offset + count( $ids ) );

		$json = array(
			'success' => true,
			'total'   => $total_found,
			'remain'  => $remain,
			'el'      => '.asqa-recount-flagged',
			// translators: %1 is total completed, %2 is total found count.
			'msg'     => sprintf( __( '%1$d done out of %2$d', 'smart-question-answer' ), $done, $total_found ),
		);

		if ( $remain > 0 ) {
			$json['q'] = array(
				'action'  => 'asqa_recount_flagged',
				'__nonce' => wp_create_nonce( 'recount_flagged' ),
				'paged'   => $paged + 1,
			);
		}

		asqa_send_json( $json );
	}

	/**
	 * Recount question subscribers.
	 *
	 * @return void
	 * @since 4.0.5
	 */
	public static function recount_subscribers() {
		if ( ! asqa_verify_nonce( 'recount_subscribers' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		global $wpdb;

		$paged  = (int) asqa_sanitize_unslash( 'paged', 'r', 0 );
		$offset = absint( $paged * 100 );

		$ids = $wpdb->get_col( "SELECT SQL_CALC_FOUND_ROWS ID FROM {$wpdb->posts} WHERE post_type = 'question' LIMIT {$offset},100" ); // @codingStandardsIgnoreLine.

		$total_found = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // DB call okay, Db cache okay.

		foreach ( (array) $ids as $id ) {
			asqa_update_subscribers_count( $id );
		}

		$done   = $offset + count( $ids );
		$remain = $total_found - ( $offset + count( $ids ) );

		$json = array(
			'success' => true,
			'total'   => $total_found,
			'remain'  => $remain,
			'el'      => '.asqa-recount-subscribers',
			// translators: %1 is total completed, %2 is total found count.
			'msg'     => sprintf( __( '%1$d done out of %2$d', 'smart-question-answer' ), $done, $total_found ),
		);

		if ( $remain > 0 ) {
			$json['q'] = array(
				'action'  => 'asqa_recount_subscribers',
				'__nonce' => wp_create_nonce( 'recount_subscribers' ),
				'paged'   => $paged + 1,
			);
		}

		asqa_send_json( $json );
	}

	/**
	 * Recount users reputation.
	 *
	 * @return void
	 * @since 4.0.5
	 */
	public static function recount_reputation() {
		if ( ! asqa_verify_nonce( 'recount_reputation' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		global $wpdb;

		$paged  = (int) asqa_sanitize_unslash( 'paged', 'r', 0 );
		$offset = absint( $paged * 100 );

		$ids = $wpdb->get_col( "SELECT SQL_CALC_FOUND_ROWS ID FROM {$wpdb->users} LIMIT {$offset},100" ); // @codingStandardsIgnoreLine.

		$total_found = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // DB call okay, Db cache okay.

		foreach ( (array) $ids as $id ) {
			asqa_update_user_reputation_meta( $id );
		}

		$done   = $offset + count( $ids );
		$remain = $total_found - ( $offset + count( $ids ) );

		$json = array(
			'success' => true,
			'total'   => $total_found,
			'remain'  => $remain,
			'el'      => '.asqa-recount-reputation',
			// translators: %1 is total completed, %2 is total found count.
			'msg'     => sprintf( __( '%1$d done out of %2$d', 'smart-question-answer' ), $done, $total_found ),
		);

		if ( $remain > 0 ) {
			$json['q'] = array(
				'action'  => 'asqa_recount_reputation',
				'__nonce' => wp_create_nonce( 'recount_reputation' ),
				'paged'   => $paged + 1,
			);
		}

		asqa_send_json( $json );
	}

	/**
	 * Recount question views.
	 *
	 * @return void
	 * @since 4.0.5
	 */
	public static function recount_views() {
		if ( ! asqa_verify_nonce( 'recount_views' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		global $wpdb;

		$args = wp_parse_args(
			asqa_sanitize_unslash( 'args', 'r', '' ),
			array(
				'fake_views' => false,
				'min_views'  => 100,
				'max_views'  => 200,
			)
		);

		$paged  = (int) asqa_sanitize_unslash( 'paged', 'r', 0 );
		$offset = absint( $paged * 100 );

		$ids = $wpdb->get_col( "SELECT SQL_CALC_FOUND_ROWS ID FROM {$wpdb->posts} WHERE post_type = 'question' LIMIT {$offset},100" ); // @codingStandardsIgnoreLine.

		$total_found = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // DB call okay, Db cache okay.

		foreach ( (array) $ids as $id ) {
			$table_views  = (int) asqa_get_views( $id );
			$qameta_views = (int) asqa_get_post_field( 'views', $id );

			if ( $qameta_views < $table_views ) {
				$views = $table_views + $qameta_views;
			} else {
				$views = $qameta_views;
			}

			if ( $args['fake_views'] ) {
				$views = $views + asqa_rand( $args['min_views'], $args['max_views'], 0.5 );
			}

			asqa_update_views_count( $id, $views );
		}

		$done   = $offset + count( $ids );
		$remain = $total_found - ( $offset + count( $ids ) );

		$json = array(
			'success' => true,
			'total'   => $total_found,
			'remain'  => $remain,
			'el'      => '.asqa-recount-views',
			// translators: %1 is total completed, %2 is total found count.
			'msg'     => sprintf( __( '%1$d done out of %2$d', 'smart-question-answer' ), $done, $total_found ),
		);

		if ( $remain > 0 ) {
			$json['q'] = array(
				'action'  => 'asqa_recount_views',
				'__nonce' => wp_create_nonce( 'recount_views' ),
				'paged'   => $paged + 1,
			);
		}

		asqa_send_json( $json );
	}
}
