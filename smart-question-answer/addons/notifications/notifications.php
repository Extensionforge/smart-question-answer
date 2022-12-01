<?php
/**
 * Addon for user notifications.
 *
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright  2017 extensionforge.com & Peter Mertzlin
 * @license    GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link       https://extensionforge.com
 * @package    SmartQa
 * @subpackage Notifications Addon
 * @since      1.0.0
 */

namespace SmartQa\Addons;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Require functions.
require_once SMARTQA_ADDONS_DIR . '/notifications/functions.php';
require_once SMARTQA_ADDONS_DIR . '/notifications/query.php';

/**
 * SmartQa notifications hooks.
 *
 * @package SmartQa
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 * @since   4.0.0
 */
class Notifications extends \SmartQa\Singleton {

	/**
	 * Instance of this class.
	 *
	 * @var     object
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		asqa_add_default_options(
			array(
				'user_page_title_notifications' => __( 'Notifications', 'smart-question-answer' ),
				'user_page_slug_notifications'  => 'notifications',
			)
		);

		smartqa()->add_filter( 'asqa_settings_menu_features_groups', $this, 'add_to_settings_page' );
		smartqa()->add_filter( 'asqa_form_options_features_notification', $this, 'load_options' );

		// Activate SmartQa notifications only if buddypress not active.
		if ( asqa_is_addon_active( 'buddypress.php' ) ) {
			return;
		}

		asqa_register_page( 'notifications', __( 'Notifications', 'smart-question-answer' ), '', true, true );
		smartqa()->add_filter( 'asqa_menu_object', $this, 'asqa_menu_object' );
		smartqa()->add_action( 'asqa_notification_verbs', $this, 'register_verbs' );
		smartqa()->add_action( 'asqa_user_pages', $this, 'asqa_user_pages' );
		smartqa()->add_action( 'asqa_after_new_answer', $this, 'new_answer', 10, 2 );
		smartqa()->add_action( 'asqa_trash_question', $this, 'trash_question', 10, 2 );
		smartqa()->add_action( 'asqa_before_delete_question', $this, 'trash_question', 10, 2 );
		smartqa()->add_action( 'asqa_trash_answer', $this, 'trash_answer', 10, 2 );
		smartqa()->add_action( 'asqa_before_delete_answer', $this, 'trash_answer', 10, 2 );
		smartqa()->add_action( 'asqa_untrash_answer', $this, 'new_answer', 10, 2 );
		smartqa()->add_action( 'asqa_select_answer', $this, 'select_answer' );
		smartqa()->add_action( 'asqa_unselect_answer', $this, 'unselect_answer' );
		smartqa()->add_action( 'asqa_publish_comment', $this, 'new_comment' );
		smartqa()->add_action( 'asqa_unpublish_comment', $this, 'delete_comment' );
		smartqa()->add_action( 'asqa_vote_up', $this, 'vote_up' );
		smartqa()->add_action( 'asqa_vote_down', $this, 'vote_down' );
		smartqa()->add_action( 'asqa_undo_vote_up', $this, 'undo_vote_up' );
		smartqa()->add_action( 'asqa_undo_vote_down', $this, 'undo_vote_down' );
		smartqa()->add_action( 'asqa_insert_reputation', $this, 'insert_reputation', 10, 4 );
		smartqa()->add_action( 'asqa_delete_reputation', $this, 'delete_reputation', 10, 3 );
		smartqa()->add_action( 'asqa_ajax_mark_notifications_seen', $this, 'mark_notifications_seen' );
		smartqa()->add_action( 'asqa_ajax_load_more_notifications', $this, 'load_more_notifications' );
		smartqa()->add_action( 'asqa_ajax_get_notifications', $this, 'get_notifications' );
	}

	/**
	 * Add tags settings to features settings page.
	 *
	 * @param array $groups Features settings group.
	 * @return array
	 * @since 1.0.0
	 */
	public function add_to_settings_page( $groups ) {
		$groups['notification'] = array(
			'label' => __( 'Notification', 'smart-question-answer' ),
		);

		return $groups;
	}

	/**
	 * Register notification addon options.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function load_options() {
		$opt = asqa_opt();

		$form = array(
			'fields' => array(
				'user_page_title_notifications' => array(
					'label' => __( 'Notifications page title', 'smart-question-answer' ),
					'desc'  => __( 'Custom title for user profile notifications page', 'smart-question-answer' ),
					'value' => $opt['user_page_title_notifications'],
				),
				'user_page_slug_notifications'  => array(
					'label' => __( 'Notifications page slug', 'smart-question-answer' ),
					'desc'  => __( 'Custom slug for user profile notifications page', 'smart-question-answer' ),
					'value' => $opt['user_page_slug_notifications'],
				),
			),
		);

		return $form;
	}

	/**
	 * Filter notification menu title.
	 *
	 * @param  object $items Menu item object.
	 * @return array
	 */
	public function asqa_menu_object( $items ) {
		foreach ( $items as $k => $i ) {
			if ( isset( $i->object ) && 'notifications' === $i->object ) {
				$items[ $k ]->url  = '#apNotifications';
				$items[ $k ]->type = 'custom';
			}
		}

		return $items;
	}

	/**
	 * Register notifications verbs.
	 *
	 * @since unknown
	 */
	public function register_verbs() {
		asqa_register_notification_verb(
			'new_answer',
			array(
				'label' => __( 'posted an answer on your question', 'smart-question-answer' ),
			)
		);

		asqa_register_notification_verb(
			'new_comment',
			array(
				'ref_type' => 'comment',
				// translators: %cpt% is SmartQa placeholder not gettext.
				'label'    => __( 'commented on your %cpt%', 'smart-question-answer' ),
			)
		);

		asqa_register_notification_verb(
			'vote_up',
			array(
				'ref_type' => 'post',
				// translators: %cpt% is SmartQa placeholder not gettext.
				'label'    => __( 'up voted your %cpt%', 'smart-question-answer' ),
			)
		);

		asqa_register_notification_verb(
			'vote_down',
			array(
				'ref_type'   => 'post',
				'hide_actor' => true,
				'icon'       => 'apicon-thumb-down',
				// translators: %cpt% is SmartQa placeholder not gettext.
				'label'      => __( 'down voted your %cpt%', 'smart-question-answer' ),
			)
		);

		asqa_register_notification_verb(
			'best_answer',
			array(
				'ref_type' => 'post',
				'label'    => __( 'selected your answer', 'smart-question-answer' ),
			)
		);

		asqa_register_notification_verb(
			'new_points',
			array(
				'ref_type' => 'reputation',
				'label'    => __( 'You have earned %points% points', 'smart-question-answer' ),
			)
		);

		asqa_register_notification_verb(
			'lost_points',
			array(
				'ref_type' => 'reputation',
				'label'    => __( 'You lose %points% points', 'smart-question-answer' ),
			)
		);
	}

	/**
	 * Adds reputations tab in SmartQa authors page.
	 */
	public function asqa_user_pages() {
		smartqa()->user_pages[] = array(
			'slug'    => 'notifications',
			'label'   => __( 'Notifications', 'smart-question-answer' ),
			'count'   => asqa_count_unseen_notifications(),
			'icon'    => 'apicon-globe',
			'cb'      => array( $this, 'notification_page' ),
			'private' => true,
		);
	}

	/**
	 * Display reputation tab content in SmartQa author page.
	 */
	public function notification_page() {
		$user_id = asqa_current_user_id();
		$seen    = asqa_sanitize_unslash( 'seen', 'r', 'all' );

		if ( get_current_user_id() === $user_id ) {
			$seen          = 'all' === $seen ? null : (int) $seen;
			$notifications = new \SmartQa\Notifications(
				array(
					'user_id' => $user_id,
					'seen'    => $seen,
				)
			);

			do_action( 'asqa_before_notification_page', $notifications );

			include asqa_get_theme_location( 'addons/notification/index.php' );
		} else {
			esc_attr_e( 'You do not have permission to view this page', 'smart-question-answer' );
		}
	}

	/**
	 * Remove all notifications related to question when its get deleted.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post Post object.
	 */
	public function trash_question( $post_id, $_post ) {
		asqa_delete_notifications(
			array(
				'parent'   => $post_id,
				'ref_type' => array( 'answer', 'vote_up', 'vote_down', 'post' ),
			)
		);
	}

	/**
	 * Add notification for new answer.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post Post object.
	 */
	public function new_answer( $post_id, $_post ) {
		$_question = get_post( $_post->post_parent );
		asqa_insert_notification(
			array(
				'user_id'  => $_question->post_author,
				'actor'    => $_post->post_author,
				'parent'   => $_post->post_parent,
				'ref_id'   => $_post->ID,
				'ref_type' => 'answer',
				'verb'     => 'new_answer',
			)
		);
	}

	/**
	 * Remove all notifications related to answer when its get deleted.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post Post object.
	 */
	public function trash_answer( $post_id, $_post ) {
		asqa_delete_notifications(
			array(
				'ref_id'   => $post_id,
				'ref_type' => array( 'answer', 'vote_up', 'vote_down', 'post' ),
			)
		);
	}

	/**
	 * Notify user when their answer is selected.
	 *
	 * @param object $_post Post object.
	 */
	public function select_answer( $_post ) {
		// Award select answer points to question author only.
		if ( get_current_user_id() !== $_post->post_author ) {
			asqa_insert_notification(
				array(
					'user_id'  => $_post->post_author,
					'actor'    => get_current_user_id(),
					'parent'   => $_post->post_parent,
					'ref_id'   => $_post->ID,
					'ref_type' => 'answer',
					'verb'     => 'best_answer',
				)
			);
		}
	}

	/**
	 * Remove notification when users answer get unselected.
	 *
	 * @param object $_post Post object.
	 */
	public function unselect_answer( $_post ) {
		asqa_delete_notifications(
			array(
				'parent'   => $_post->post_parent,
				'ref_type' => 'answer',
				'verb'     => 'best_answer',
			)
		);
	}

	/**
	 * Notify user on new comment.
	 *
	 * @param  object $comment WordPress comment object.
	 */
	public function new_comment( $comment ) {
		$_post = get_post( $comment->comment_post_ID );

		if ( get_current_user_id() !== $_post->post_author ) {
			asqa_insert_notification(
				array(
					'user_id'  => $_post->post_author,
					'actor'    => $comment->user_id,
					'parent'   => $comment->comment_post_ID,
					'ref_id'   => $comment->comment_ID,
					'ref_type' => 'comment',
					'verb'     => 'new_comment',
				)
			);
		}
	}

	/**
	 * Remove notification on deleting comment.
	 *
	 * @param  object $comment Comment object.
	 */
	public function delete_comment( $comment ) {
		asqa_delete_notifications(
			array(
				'actor'    => $comment->user_id,
				'parent'   => $comment->comment_post_ID,
				'ref_type' => 'comment',
			)
		);
	}

	/**
	 * Award reputation when user recive an up vote.
	 *
	 * @param integer $post_id Post ID.
	 */
	public function vote_up( $post_id ) {
		$_post = get_post( $post_id );

		if ( get_current_user_id() !== $_post->post_author ) {
			asqa_insert_notification(
				array(
					'user_id'  => $_post->post_author,
					'actor'    => get_current_user_id(),
					'parent'   => $_post->ID,
					'ref_id'   => $_post->ID,
					'ref_type' => $_post->post_type,
					'verb'     => 'vote_up',
				)
			);
		}
	}

	/**
	 * Notify when user recive an down vote.
	 *
	 * @param integer $post_id Post ID.
	 */
	public function vote_down( $post_id ) {
		$_post = get_post( $post_id );

		if ( get_current_user_id() !== $_post->post_author ) {
			asqa_insert_notification(
				array(
					'user_id'  => $_post->post_author,
					'actor'    => get_current_user_id(),
					'parent'   => $_post->ID,
					'ref_id'   => $_post->ID,
					'ref_type' => $_post->post_type,
					'verb'     => 'vote_down',
				)
			);
		}
	}

	/**
	 * Notify when user recive an up vote.
	 *
	 * @param integer $post_id Post ID.
	 */
	public function undo_vote_up( $post_id ) {
		asqa_delete_notifications(
			array(
				'ref_id' => $post_id,
				'actor'  => get_current_user_id(),
				'verb'   => 'vote_up',
			)
		);
	}

	/**
	 * Notify when user recive an down vote.
	 *
	 * @param integer $post_id Post ID.
	 */
	public function undo_vote_down( $post_id ) {
		asqa_delete_notifications(
			array(
				'ref_id' => $post_id,
				'actor'  => get_current_user_id(),
				'verb'   => 'vote_down',
			)
		);
	}

	/**
	 * Notify user on new reputation.
	 *
	 * @param integer $reputation_id Reputation id.
	 * @param integer $user_id User id.
	 * @param string  $event Reputation event.
	 * @param integer $ref_id Reputation reference id.
	 */
	public function insert_reputation( $reputation_id, $user_id, $event, $ref_id ) {
		asqa_insert_notification(
			array(
				'user_id'  => $user_id,
				'ref_id'   => $reputation_id,
				'ref_type' => 'reputation',
				'verb'     => asqa_get_reputation_event_points( $event ) > 0 ? 'new_points' : 'lost_points',
			)
		);
	}

	/**
	 * Notify user on new reputation.
	 *
	 * @param integer|false $deleted NUmbers of rows deleted.
	 * @param integer       $user_id User id.
	 * @param string        $event Reputation event.
	 */
	public function delete_reputation( $deleted, $user_id, $event ) {
		asqa_delete_notifications(
			array(
				'ref_type' => 'reputation',
				'user_id'  => $user_id,
			)
		);
	}

	/**
	 * Ajax callback for marking all notification of current user
	 * as seen.
	 */
	public function mark_notifications_seen() {
		if ( ! is_user_logged_in() || ! asqa_verify_nonce( 'mark_notifications_seen' ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'There was a problem processing your request', 'smart-question-answer' ) ),
				)
			);
		}

		// Mark all notifications as seen.
		asqa_set_notifications_as_seen( get_current_user_id() );

		asqa_ajax_json(
			array(
				'success'  => true,
				'btn'      => array( 'hide' => true ),
				'snackbar' => array( 'message' => __( 'Successfully updated all notifications', 'smart-question-answer' ) ),
				'cb'       => 'notificationAllRead',
			)
		);

		wp_die();
	}

	/**
	 * Ajax callback for loading more notifications.
	 */
	public function load_more_notifications() {
		check_admin_referer( 'load_more_notifications', '__nonce' );

		$user_id = asqa_sanitize_unslash( 'user_id', 'r' );
		$paged   = asqa_sanitize_unslash( 'current', 'r', 1 ) + 1;

		ob_start();
		$notifications = new \SmartQa\Notifications(
			array(
				'user_id' => $user_id,
				'paged'   => $paged,
			)
		);

		while ( $notifications->have() ) :
			$notifications->the_notification();
			$notifications->item_template();
		endwhile;

		$html = ob_get_clean();

		$paged = $notifications->total_pages > $paged ? $paged : 0;

		asqa_ajax_json(
			array(
				'success' => true,
				'args'    => array(
					'asqa_ajax_action' => 'load_more_notifications',
					'__nonce'        => wp_create_nonce( 'load_more_notifications' ),
					'current'        => (int) $paged,
					'user_id'        => $user_id,
				),
				'html'    => $html,
				'element' => '.asqa-noti',
			)
		);
	}

	/**
	 * Ajax callback for loading user notifications dropdown.
	 */
	public function get_notifications() {
		if ( ! is_user_logged_in() ) {
			wp_die();
		}

		$notifications = new \SmartQa\Notifications( array( 'user_id' => get_current_user_id() ) );

		$items = array();
		while ( $notifications->have() ) :
			$notifications->the_notification();

			$items[] = array(
				'ID'         => $notifications->object->noti_id,
				'verb'       => $notifications->object->noti_verb,
				'verb_label' => $notifications->get_verb(),
				'icon'       => $notifications->get_icon(),
				'avatar'     => $notifications->actor_avatar(),
				'hide_actor' => $notifications->hide_actor(),
				'actor'      => $notifications->get_actor(),
				'ref_title'  => $notifications->get_ref_title(),
				'ref_type'   => $notifications->object->noti_ref_type,
				'points'     => $notifications->get_reputation_points(),
				'date'       => asqa_human_time( $notifications->get_date(), false ),
				'permalink'  => $notifications->get_permalink(),
				'seen'       => $notifications->object->noti_seen,
			);
		endwhile;

		asqa_ajax_json(
			array(
				'success'       => true,
				'notifications' => $items,
				'total'         => asqa_count_unseen_notifications(),
				'mark_args'     => array(
					'asqa_ajax_action' => 'mark_notifications_seen',
					'__nonce'        => wp_create_nonce( 'mark_notifications_seen' ),
				),
			)
		);
	}

}

/**
 * Insert table when addon is activated.
 */
function asqa_notification_addon_activation() {
	global $wpdb;
	$charset_collate = ! empty( $wpdb->charset ) ? 'DEFAULT CHARACTER SET ' . $wpdb->charset : '';

	$table = 'CREATE TABLE `' . $wpdb->prefix . 'asqa_notifications` (
			`noti_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`noti_user_id` bigint(20) NOT NULL,
			`noti_actor` bigint(20) NOT NULL,
			`noti_parent` bigint(20) NOT NULL,
			`noti_ref_id` bigint(20) NOT NULL,
			`noti_ref_type` varchar(100) NOT NULL,
			`noti_verb` varchar(100) NOT NULL,
			`noti_date` timestamp NULL DEFAULT NULL,
			`noti_seen` tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (`noti_id`)
		)' . $charset_collate . ';';

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $table );
}
asqa_addon_activation_hook( basename( __FILE__ ), __NAMESPACE__ . '\asqa_notification_addon_activation' );

// Init class.
Notifications::init();
