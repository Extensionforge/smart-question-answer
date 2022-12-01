<?php
/**
 * Award reputation to user based on activities.
 *
 * @author       Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright    2014 extensionforge.com & Peter Mertzlin
 * @license      GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link         https://extensionforge.com
 * @package      SmartQa
 * @subpackage   Reputation addon
 */

namespace Smartqa\Addons;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Reputation hooks.
 */
class Reputation extends \SmartQa\Singleton {

	/**
	 * Instance of this class.
	 *
	 * @var     object
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Init class.
	 *
	 * @since unknown
	 * @since 1.0.0 Added hook `asqa_settings_menu_features_groups`.
	 * @since 1.0.0 Renamed hook `asqa_form_addon-reputation` to `asqa_form_options_features_tag`.
	 */
	protected function __construct() {
		$this->register_default_events();

		asqa_add_default_options(
			array(
				'user_page_title_reputations' => __( 'Reputations', 'smart-question-answer' ),
				'user_page_slug_reputations'  => 'reputations',
			)
		);

		smartqa()->add_action( 'asqa_settings_menu_features_groups', $this, 'add_to_settings_page' );
		smartqa()->add_action( 'asqa_form_options_features_reputation', $this, 'load_options', 20 );
		smartqa()->add_action( 'wp_ajax_asqa_save_events', $this, 'asqa_save_events' );
		smartqa()->add_action( 'asqa_after_new_question', $this, 'new_question', 10, 2 );
		smartqa()->add_action( 'asqa_after_new_answer', $this, 'new_answer', 10, 2 );
		smartqa()->add_action( 'asqa_untrash_question', $this, 'new_question', 10, 2 );
		smartqa()->add_action( 'asqa_trash_question', $this, 'trash_question', 10, 2 );
		smartqa()->add_action( 'asqa_before_delete_question', $this, 'trash_question', 10, 2 );
		smartqa()->add_action( 'asqa_untrash_answer', $this, 'new_answer', 10, 2 );
		smartqa()->add_action( 'asqa_trash_answer', $this, 'trash_answer', 10, 2 );
		smartqa()->add_action( 'asqa_before_delete_answer', $this, 'trash_answer', 10, 2 );
		smartqa()->add_action( 'asqa_select_answer', $this, 'select_answer' );
		smartqa()->add_action( 'asqa_unselect_answer', $this, 'unselect_answer' );
		smartqa()->add_action( 'asqa_vote_up', $this, 'vote_up' );
		smartqa()->add_action( 'asqa_vote_down', $this, 'vote_down' );
		smartqa()->add_action( 'asqa_undo_vote_up', $this, 'undo_vote_up' );
		smartqa()->add_action( 'asqa_undo_vote_down', $this, 'undo_vote_down' );
		smartqa()->add_action( 'asqa_publish_comment', $this, 'new_comment' );
		smartqa()->add_action( 'asqa_unpublish_comment', $this, 'delete_comment' );
		smartqa()->add_filter( 'user_register', $this, 'user_register' );
		smartqa()->add_action( 'delete_user', $this, 'delete_user' );
		smartqa()->add_filter( 'asqa_user_display_name', $this, 'display_name', 10, 2 );
		smartqa()->add_filter( 'asqa_pre_fetch_question_data', $this, 'pre_fetch_post' );
		smartqa()->add_filter( 'asqa_pre_fetch_answer_data', $this, 'pre_fetch_post' );
		smartqa()->add_filter( 'bp_before_member_header_meta', $this, 'bp_profile_header_meta' );
		smartqa()->add_filter( 'asqa_user_pages', $this, 'asqa_user_pages' );
		smartqa()->add_filter( 'asqa_ajax_load_more_reputation', $this, 'load_more_reputation' );
		smartqa()->add_filter( 'asqa_bp_nav', $this, 'asqa_bp_nav' );
		smartqa()->add_filter( 'asqa_bp_page', $this, 'asqa_bp_page', 10, 2 );
		smartqa()->add_filter( 'asqa_all_options', $this, 'asqa_all_options', 10, 2 );
	}

	/**
	 * Add tags settings to features settings page.
	 *
	 * @param array $groups Features settings group.
	 * @return array
	 * @since 1.0.0
	 */
	public function add_to_settings_page( $groups ) {
		$groups['reputation'] = array(
			'label' => __( 'Reputation', 'smart-question-answer' ),
			'info'  => __( 'Reputation event points can be adjusted here :', 'smart-question-answer' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=smartqa_options&active_tab=reputations' ) ) . '">' . __( 'Reputation Points', 'smart-question-answer' ) . '</a>',
		);

		return $groups;
	}

	/**
	 * Register reputation options
	 */
	public function load_options() {
		$opt  = asqa_opt();
		$form = array(
			'fields' => array(
				'user_page_title_reputations' => array(
					'label' => __( 'Reputations page title', 'smart-question-answer' ),
					'desc'  => __( 'Custom title for user profile reputations page', 'smart-question-answer' ),
					'value' => $opt['user_page_title_reputations'],
				),
				'user_page_slug_reputations'  => array(
					'label' => __( 'Reputations page slug', 'smart-question-answer' ),
					'desc'  => __( 'Custom slug for user profile reputations page', 'smart-question-answer' ),
					'value' => $opt['user_page_slug_reputations'],
				),
			),
		);

		return $form;
	}

	/**
	 * Register default reputation events.
	 */
	public function register_default_events() {
		$events_db = wp_cache_get( 'all', 'asqa_get_all_reputation_events' );

		if ( false === $events_db ) {
			$events_db = asqa_get_all_reputation_events();
		}

		// If already in DB return from here.
		if ( ! $events_db ) {
			$events = array(
				array(
					'slug'        => 'register',
					'label'       => __( 'Registration', 'smart-question-answer' ),
					'description' => __( 'Points awarded when user account is created', 'smart-question-answer' ),
					'icon'        => 'apicon-question',
					'activity'    => __( 'Registered', 'smart-question-answer' ),
					'parent'      => 'question',
					'points'      => 10,
				),
				array(
					'slug'        => 'ask',
					'points'      => 2,
					'label'       => __( 'Asking', 'smart-question-answer' ),
					'description' => __( 'Points awarded when user asks a question', 'smart-question-answer' ),
					'icon'        => 'apicon-question',
					'activity'    => __( 'Asked a question', 'smart-question-answer' ),
					'parent'      => 'question',
				),
				array(
					'slug'        => 'answer',
					'points'      => 5,
					'label'       => __( 'Answering', 'smart-question-answer' ),
					'description' => __( 'Points awarded when user answers a question', 'smart-question-answer' ),
					'icon'        => 'apicon-answer',
					'activity'    => __( 'Posted an answer', 'smart-question-answer' ),
					'parent'      => 'answer',
				),
				array(
					'slug'        => 'comment',
					'points'      => 2,
					'label'       => __( 'Commenting', 'smart-question-answer' ),
					'description' => __( 'Points awarded when user comments on question or answer', 'smart-question-answer' ),
					'icon'        => 'apicon-comments',
					'activity'    => __( 'Commented on a post', 'smart-question-answer' ),
					'parent'      => 'comment',
				),
				array(
					'slug'        => 'select_answer',
					'points'      => 2,
					'label'       => __( 'Selecting an Answer', 'smart-question-answer' ),
					'description' => __( 'Points awarded when user selects an answer for their question', 'smart-question-answer' ),
					'icon'        => 'apicon-check',
					'activity'    => __( 'Selected an answer as best', 'smart-question-answer' ),
					'parent'      => 'question',
				),
				array(
					'slug'        => 'best_answer',
					'points'      => 10,
					'label'       => __( 'Answer selected as best', 'smart-question-answer' ),
					'description' => __( 'Points awarded when user\'s answer is selected as best', 'smart-question-answer' ),
					'icon'        => 'apicon-check',
					'activity'    => __( 'Answer was selected as best', 'smart-question-answer' ),
					'parent'      => 'answer',
				),
				array(
					'slug'        => 'received_vote_up',
					'points'      => 10,
					'label'       => __( 'Received up vote', 'smart-question-answer' ),
					'description' => __( 'Points awarded when user receives an upvote', 'smart-question-answer' ),
					'icon'        => 'apicon-thumb-up',
					'activity'    => __( 'Received an upvote', 'smart-question-answer' ),
				),
				array(
					'slug'        => 'received_vote_down',
					'points'      => -2,
					'label'       => __( 'Received down vote', 'smart-question-answer' ),
					'description' => __( 'Points awarded when user receives a down vote', 'smart-question-answer' ),
					'icon'        => 'apicon-thumb-down',
					'activity'    => __( 'Received a down vote', 'smart-question-answer' ),
				),
				array(
					'slug'        => 'given_vote_up',
					'points'      => 0,
					'label'       => __( 'Gives an up vote', 'smart-question-answer' ),
					'description' => __( 'Points taken from user when they give an up vote', 'smart-question-answer' ),
					'icon'        => 'apicon-thumb-up',
					'activity'    => __( 'Given an up vote', 'smart-question-answer' ),
				),
				array(
					'slug'        => 'given_vote_down',
					'points'      => 0,
					'label'       => __( 'Gives down vote', 'smart-question-answer' ),
					'description' => __( 'Points taken from user when they give a down vote', 'smart-question-answer' ),
					'icon'        => 'apicon-thumb-down',
					'activity'    => __( 'Given a down vote', 'smart-question-answer' ),
				),
			);

			$custom_points = get_option( 'smartqa_reputation_events', array() );

			foreach ( $events as $event ) {
				$points = isset( $custom_points[ $event['slug'] ] ) ? (int) $custom_points[ $event['slug'] ] : (int) $event['points'];

				asqa_insert_reputation_event(
					$event['slug'],
					$event['label'],
					$event['description'],
					$points,
					! empty( $event['activity'] ) ? $event['activity'] : '',
					! empty( $event['parent'] ) ? $event['parent'] : ''
				);
			}

			$events_db = asqa_get_all_reputation_events();

			wp_cache_set( 'all', $events_db, 'asqa_get_all_reputation_events' );
		}

		if ( empty( $events_db ) ) {
			return;
		}

		// Fallback for register events.
		foreach ( $events_db as $event ) {
			$args = (array) $event;

			unset( $args['slug'] );

			asqa_register_reputation_event( $event->slug, $args );
		}
	}

	/**
	 * Save reputation events.
	 */
	public function asqa_save_events() {
		check_ajax_referer( 'asqa-save-events', '__nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$events_point = asqa_isset_post_value( 'events', 'r' );
		$points       = array();

		foreach ( asqa_get_reputation_events() as $slug => $event ) {
			if ( isset( $events_point[ $slug ] ) ) {
				$points[ sanitize_text_field( $slug ) ] = (int) $events_point[ $slug ];
			}
		}

		if ( ! empty( $points ) ) {
			update_option( 'smartqa_reputation_events', $points );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_attr__( 'Successfully updated reputation points!', 'smart-question-answer' ) . '</p></div>';

		wp_die();
	}

	/**
	 * Add reputation for user for new question.
	 *
	 * @param integer  $post_id Post ID.
	 * @param \WP_Post $_post Post object.
	 */
	public function new_question( $post_id, $_post ) {
		asqa_insert_reputation( 'ask', $post_id, $_post->post_author );
	}

	/**
	 * Add reputation for new answer.
	 *
	 * @param integer  $post_id Post ID.
	 * @param \WP_Post $_post Post object.
	 */
	public function new_answer( $post_id, $_post ) {
		asqa_insert_reputation( 'answer', $post_id, $_post->post_author );
	}

	/**
	 * Update reputation when a question is deleted.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post Post object.
	 */
	public function trash_question( $post_id, $_post ) {
		asqa_delete_reputation( 'ask', $post_id, $_post->post_author );
	}

	/**
	 * Update reputation when a answer is deleted.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post   Post object.
	 */
	public function trash_answer( $post_id, $_post ) {
		asqa_delete_reputation( 'answer', $post_id, $_post->post_author );
	}

	/**
	 * Award reputation when best answer selected.
	 *
	 * @param object $_post Post object.
	 */
	public function select_answer( $_post ) {
		asqa_insert_reputation( 'best_answer', $_post->ID, $_post->post_author );
		$question = get_post( $_post->post_parent );

		// Award select answer points to question author only.
		if ( get_current_user_id() === (int) $_post->post_author ) {
			asqa_insert_reputation( 'select_answer', $_post->ID );
		}
	}

	/**
	 * Award reputation when user get an upvote.
	 *
	 * @param object $_post Post object.
	 */
	public function unselect_answer( $_post ) {
		asqa_delete_reputation( 'best_answer', $_post->ID, $_post->post_author );
		$question = get_post( $_post->post_parent );
		asqa_delete_reputation( 'select_answer', $_post->ID, $question->post_author );
	}

	/**
	 * Award reputation when user receive an up vote.
	 *
	 * @param integer $post_id Post ID.
	 */
	public function vote_up( $post_id ) {
		$_post = get_post( $post_id );
		asqa_insert_reputation( 'received_vote_up', $_post->ID, $_post->post_author );
		asqa_insert_reputation( 'given_vote_up', $_post->ID );
	}

	/**
	 * Award reputation when user receive an down vote.
	 *
	 * @param integer $post_id Post ID.
	 */
	public function vote_down( $post_id ) {
		$_post = get_post( $post_id );
		asqa_insert_reputation( 'received_vote_down', $_post->ID, $_post->post_author );
		asqa_insert_reputation( 'given_vote_down', $_post->ID );
	}

	/**
	 * Award reputation when user recive an up vote.
	 *
	 * @param integer $post_id Post ID.
	 */
	public function undo_vote_up( $post_id ) {
		$_post = get_post( $post_id );
		asqa_delete_reputation( 'received_vote_up', $_post->ID, $_post->post_author );
		asqa_delete_reputation( 'given_vote_up', $_post->ID );
	}

	/**
	 * Award reputation when user recive an down vote.
	 *
	 * @param integer $post_id Post ID.
	 */
	public function undo_vote_down( $post_id ) {
		$_post = get_post( $post_id );
		asqa_delete_reputation( 'received_vote_down', $_post->ID, $_post->post_author );
		asqa_delete_reputation( 'given_vote_down', $_post->ID );
	}

	/**
	 * Award reputation on new comment.
	 *
	 * @param  object $comment WordPress comment object.
	 */
	public function new_comment( $comment ) {
		asqa_insert_reputation( 'comment', $comment->comment_ID, $comment->user_id );
	}

	/**
	 * Undo reputation on deleting comment.
	 *
	 * @param  object $comment Comment object.
	 */
	public function delete_comment( $comment ) {
		asqa_delete_reputation( 'comment', $comment->comment_ID, $comment->user_id );
	}

	/**
	 * Award reputation when user register.
	 *
	 * @param integer $user_id User Id.
	 */
	public function user_register( $user_id ) {
		asqa_insert_reputation( 'register', $user_id, $user_id );
	}

	/**
	 * Delete all reputation of user when user get deleted.
	 *
	 * @param integer $user_id User ID.
	 */
	public function delete_user( $user_id ) {
		global $wpdb;
		$delete = $wpdb->delete( $wpdb->asqa_reputations, array( 'repu_user_id' => $user_id ), array( '%d' ) ); // WPCS: db call okay, db cache okay.

		if ( false !== $delete ) {
			do_action( 'asqa_bulk_delete_reputations_of_user', $user_id );
		}
	}

	/**
	 * Append user reputations in display name.
	 *
	 * @param string $name User display name.
	 * @param array  $args Arguments.
	 * @return string
	 */
	public function display_name( $name, $args ) {
		if ( $args['user_id'] > 0 ) {
			if ( $args['html'] ) {
				$reputation = asqa_get_user_reputation_meta( $args['user_id'] );

				if ( asqa_is_addon_active( 'buddypress.php' ) && function_exists( 'bp_core_get_userlink' ) ) {
					return $name . '<a href="' . asqa_user_link( $args['user_id'] ) . 'qa/reputations/" class="asqa-user-reputation" title="' . __( 'Reputation', 'smart-question-answer' ) . '">' . $reputation . '</a>';
				} else {
					return $name . '<a href="' . asqa_user_link( $args['user_id'] ) . 'reputations/" class="asqa-user-reputation" title="' . __( 'Reputation', 'smart-question-answer' ) . '">' . $reputation . '</a>';
				}
			}
		}

		return $name;
	}

	/**
	 * Pre fetch user reputations.
	 *
	 * @param array $ids Pre fetching ids.
	 */
	public function pre_fetch_post( $ids ) {
		if ( ! empty( $ids['user_ids'] ) ) {
			asqa_get_users_reputation( $ids['user_ids'] );
		}
	}

	/**
	 * Show reputation points of user in BuddyPress profile meta.
	 */
	public function bp_profile_header_meta() {
		echo wp_kses_post(
			'<span class="asqa-user-meta asqa-user-meta-reputation">' .
			sprintf(
				// translators: Placeholder contains reputation points.
				__( '%s Reputation', 'smart-question-answer' ),
				asqa_get_user_reputation_meta( bp_displayed_user_id() )
			) .
			'</span>'
		);
	}

	/**
	 * Adds reputations tab in SmartQa authors page.
	 */
	public function asqa_user_pages() {
		smartqa()->user_pages[] = array(
			'slug'  => 'reputations',
			'label' => __( 'Reputations', 'smart-question-answer' ),
			'icon'  => 'apicon-reputation',
			'cb'    => array( $this, 'reputation_page' ),
			'order' => 5,
		);
	}

	/**
	 * Display reputation tab content in SmartQa author page.
	 */
	public function reputation_page() {
		$user_id = get_queried_object_id();

		$reputations = new \SmartQa_Reputation_Query( array( 'user_id' => $user_id ) );
		include asqa_get_theme_location( 'addons/reputation/index.php' );
	}

	/**
	 * Ajax callback for loading more reputations.
	 */
	public function load_more_reputation() {
		check_admin_referer( 'load_more_reputation', '__nonce' );

		$user_id = asqa_sanitize_unslash( 'user_id', 'r' );
		$paged   = asqa_sanitize_unslash( 'current', 'r', 1 ) + 1;

		ob_start();
		$reputations = new \SmartQa_Reputation_Query(
			array(
				'user_id' => $user_id,
				'paged'   => $paged,
			)
		);
		while ( $reputations->have() ) :
			$reputations->the_reputation();
			include asqa_get_theme_location( 'addons/reputation/item.php' );
		endwhile;
		$html = ob_get_clean();

		$paged = $reputations->total_pages > $paged ? $paged : 0;

		asqa_ajax_json(
			array(
				'success' => true,
				'args'    => array(
					'asqa_ajax_action' => 'load_more_reputation',
					'__nonce'        => wp_create_nonce( 'load_more_reputation' ),
					'current'        => (int) $paged,
					'user_id'        => $user_id,
				),
				'html'    => $html,
				'element' => '.asqa-reputations tbody',
			)
		);
	}

	/**
	 * Add reputations nav link in BuddyPress profile.
	 *
	 * @param array $nav Nav menu.
	 * @return array
	 */
	public function asqa_bp_nav( $nav ) {
		$nav[] = array(
			'name' => __( 'Reputations', 'smart-question-answer' ),
			'slug' => 'reputations',
		);
		return $nav;
	}

	/**
	 * Add BuddyPress reputation page callback.
	 *
	 * @param array  $cb       Callback function.
	 * @param string $template Template.
	 * @return array
	 */
	public function asqa_bp_page( $cb, $template ) {
		if ( 'reputations' === $template ) {
			return array( $this, 'bp_reputation_page' );
		}
		return $cb;
	}

	/**
	 * Display reputation on buddypress page.
	 *
	 * @since unknown
	 */
	public function bp_reputation_page() {
		$user_id = bp_displayed_user_id();

		$reputations = new \SmartQa_Reputation_Query( array( 'user_id' => $user_id ) );
		include asqa_get_theme_location( 'addons/reputation/index.php' );
	}

	/**
	 * Add reputation events option in SmartQa options.
	 *
	 * @param array $all_options Options.
	 * @return array
	 * @since 1.0.0
	 */
	public function asqa_all_options( $all_options ) {
		$all_options['reputations'] = array(
			'label'    => __( '⚙ Reputations', 'smart-question-answer' ),
			'template' => 'reputation-events.php',
		);

		return $all_options;
	}
}

// Initialize addon.
Reputation::init();
