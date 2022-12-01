<?php
/**
 * Holds class responsible for upgrading 3.x data to 4.x.
 *
 * @package SmartQa
 * @since 4.0.5
 */

/**
 * SmartQa upgrader class.
 *
 * @since 4.0.5
 */
class SmartQa_Upgrader {
	/**
	 * Question ids.
	 *
	 * @var array
	 */
	private $question_ids;

	/**
	 * Answer ids.
	 *
	 * @var array
	 */
	private $answer_ids;

	/**
	 * Checks if old meta table exists.
	 *
	 * @var boolean
	 */
	private $meta_table_exists = false;

	/**
	 * Singleton instance.
	 *
	 * @return SmartQa_Upgrader
	 */
	public static function get_instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new SmartQa_Upgrader();
		}

		return $instance;
	}

	/**
	 * Private ctor so nobody else can instance it
	 */
	private function __construct() {
		$this->check_tables();
		// Enable required addons.
		asqa_activate_addon( 'tag.php' );
		asqa_activate_addon( 'category.php' );
		asqa_activate_addon( 'reputation.php' );

		// Disable sending email while upgrading.
		define( 'ASQA_DISABLE_EMAIL', true );

		// Also disable inserting of reputations and notifications.
		define( 'ASQA_DISABLE_INSERT_NOTI', true );

		$this->check_old_meta_table_exists();
		$this->get_question_ids();

		foreach ( (array) $this->question_ids as $id ) {
			// translators: %s is question ID.
			echo esc_attr( "\n\r" . sprintf( __( 'Migrating question: %d', 'smart-question-answer' ), $id ) . "\n\r" );
			$this->question_tasks( $id );
		}

		$this->migrate_reputations();
		$this->migrate_category_data();
	}

	/**
	 * Check if tables are updated, if not create it first.
	 */
	public function check_tables() {
		if ( get_option( 'smartqa_db_version' ) !== SMARTQA_DB_VERSION ) {
			$activate = ASQA_Activate::get_instance();
			$activate->insert_tables();
			update_option( 'smartqa_db_version', SMARTQA_DB_VERSION );
		}
	}

	/**
	 * Check if old asqa_meta table exists.
	 */
	public function check_old_meta_table_exists() {
		global $wpdb;

		$table_name = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}asqa_meta'" ); // phpcs:ignore WordPress.DB

		if ( $wpdb->prefix . 'asqa_meta' === $table_name ) {
			$this->meta_table_exists = true;
		}
	}

	/**
	 * Get all question ids.
	 *
	 * @return void
	 */
	public function get_question_ids() {
		global $wpdb;

		$this->question_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->asqa_qameta} q ON q.post_id = p.ID WHERE q.post_id IS NULL AND post_type = 'question' ORDER BY ID ASC" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Process question tasks.
	 *
	 * @param integer $id Question ID.
	 * @return void
	 */
	private function question_tasks( $id ) {
		global $wpdb;

		$question = get_post( $id );

		$last_active = get_post_meta( $id, '_asqa_updated', true );
		$views       = get_post_meta( $id, '_views', true );

		// Get all answers associated with current question.
		$this->answer_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} p WHERE post_type = 'answer' AND post_parent = %d ORDER BY post_date ASC", $id ) ); // phpcs:ignore WordPress.DB

		foreach ( (array) $this->answer_ids as $answer_id ) {
			$this->answer_tasks( $answer_id );
		}

		$answers_counts     = asqa_count_published_answers( $id );
		$answer_id          = (int) get_post_meta( $id, '_asqa_selected', true );
		$featured_questions = (array) get_option( 'featured_questions' );

		asqa_insert_qameta(
			$id,
			array(
				'answers'      => $answers_counts,
				'views'        => (int) get_post_meta( $id, '_views', true ),
				'subscribers'  => (int) get_post_meta( $id, '_asqa_subscriber', true ),
				'closed'       => ( 'closed' === $question->post_status ? 1 : 0 ),
				'flags'        => (int) get_post_meta( $id, '_asqa_flag', true ),
				'selected_id'  => $answer_id,
				'featured'     => in_array( $id, $featured_questions ), // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				'last_updated' => empty( $last_active ) ? $question->post_date : $last_active,
			)
		);

		asqa_update_qameta_terms( $id );
		asqa_update_post_attach_ids( $id );

		$this->migrate_votes( $id );
		$this->restore_last_activity( $id );

		delete_post_meta( $id, '_asqa_answers' );
		delete_post_meta( $id, '_asqa_participants' );
		delete_post_meta( $id, '_views' );
		delete_post_meta( $id, '_asqa_subscriber' );
		delete_post_meta( $id, '_asqa_selected' );
		delete_post_meta( $id, '_asqa_vote' );
		delete_post_meta( $id, '_asqa_flag' );
		delete_post_meta( $id, '_asqa_selected' );

		$this->delete_question_metatables( $id );
	}

	/**
	 * Migrate votes to new qameta table.
	 *
	 * @param integer $post_id Post ID.
	 * @return void
	 */
	public function migrate_votes( $post_id ) {
		global $wpdb;

		if ( ! $this->meta_table_exists ) {
			return;
		}

		$post_id   = (int) $post_id;
		$old_votes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}asqa_meta WHERE apmeta_type IN ('vote_up', 'vote_down') AND apmeta_actionid = {$post_id}" ); // @codingStandardsIgnoreLine

		$apmeta_to_delete = array();
		foreach ( (array) $old_votes as $vote ) {
			asqa_add_post_vote( $post_id, $vote->apmeta_userid, 'vote_up' === $vote->apmeta_type, $vote->apmeta_value );
			$apmeta_to_delete[] = $vote->apmeta_id;
		}

		// Delete all migrated data.
		$apmeta_to_delete = sanitize_comma_delimited( $apmeta_to_delete, 'int' );

		if ( ! empty( $apmeta_to_delete ) ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}asqa_meta WHERE apmeta_id IN ({$apmeta_to_delete})" ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Delete old meta.
	 *
	 * @param integer $id Question ID.
	 */
	public function delete_question_metatables( $id ) {
		global $wpdb;

		if ( ! $this->meta_table_exists ) {
			return;
		}

		$old_views = $wpdb->query( "DELETE FROM {$wpdb->prefix}asqa_meta WHERE apmeta_type = 'post_view' AND apmeta_actionid = {$id}" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Process answers tasks.
	 *
	 * @param integer $answer_id Answer ID.
	 * @return void
	 */
	private function answer_tasks( $answer_id ) {
		$answer      = get_post( $answer_id );
		$last_active = get_post_meta( $answer_id, '_asqa_updated', true );
		$best_answer = get_post_meta( $answer_id, '_asqa_best_answer', true );
		$flags       = (int) get_post_meta( $answer_id, '_asqa_flag', true );

		$args = array(
			'flags'        => $flags,
			'last_updated' => empty( $last_active ) ? $answer->post_date : $last_active,
		);

		if ( '1' === $best_answer ) {
			$args['selected'] = 1;
		}

		asqa_insert_qameta( $answer_id, $args );
		$this->migrate_votes( $answer_id );

		delete_post_meta( $answer_id, '_asqa_updated' );
		delete_post_meta( $answer_id, '_asqa_best_answer' );
		delete_post_meta( $answer_id, '_asqa_subscriber' );
		delete_post_meta( $answer_id, '_asqa_participants' );
		delete_post_meta( $answer_id, '_asqa_close' );
		delete_post_meta( $answer_id, '_asqa_vote' );
		delete_post_meta( $answer_id, '_asqa_flag' );
		delete_post_meta( $answer_id, '_asqa_selected' );

		$this->restore_last_activity( $answer_id );
	}

	/**
	 * Restore last activity of a post.
	 *
	 * @param integer $post_id Post ID.
	 * @return void
	 */
	public function restore_last_activity( $post_id ) {
		$activity = get_post_meta( $post_id, '__asqa_activity', true );

		// Restore last activity.
		if ( ! empty( $activity ) ) {
			asqa_insert_qameta( $post_id, array( 'activities' => $activity ) );
		}

		delete_post_meta( $post_id, '__asqa_activity' );
	}

	/**
	 * Migrate migration data to new table.
	 */
	public function migrate_reputations() {
		if ( ! $this->meta_table_exists ) {
			esc_attr_e( 'Successfully migrated all reputations', 'smart-question-answer' );
			return;
		}

		global $wpdb;
		$old_reputations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}asqa_meta WHERE apmeta_type = 'reputation'" ); // phpcs:ignore WordPress.DB

		if ( empty( $old_reputations ) ) {
			esc_attr_e( 'Successfully migrated all reputations', 'smart-question-answer' );
			return;
		}

		$apmeta_to_delete = array();

		foreach ( (array) $old_reputations as $rep ) {
			$event = $this->replace_old_reputation_event( $rep->apmeta_param );
			asqa_insert_reputation( $event, $rep->apmeta_actionid, $rep->apmeta_userid );
			$apmeta_to_delete[] = $rep->apmeta_id;

			// Delete user meta.
			delete_user_meta( $rep->apmeta_userid, 'asqa_reputation' ); // @codingStandardsIgnoreLine.
		}

		// Delete all migrated data.
		$apmeta_to_delete = sanitize_comma_delimited( $apmeta_to_delete, 'int' );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}asqa_meta WHERE apmeta_id IN ({$apmeta_to_delete})" ); // phpcs:ignore WordPress.DB
		esc_attr_e( 'Migrated all reputations', 'smart-question-answer' );
	}

	/**
	 * Return new reputation event alternative.
	 *
	 * @param  string $old_event Old event.
	 * @return string
	 */
	public function replace_old_reputation_event( $old_event ) {
		$events = array(
			'ask'                => array( 'new_question', 'question' ),
			'answer'             => array( 'new_answer', 'answer' ),
			'received_vote_up'   => array( 'vote_up', 'question_upvote', 'answer_upvote' ),
			'received_vote_down' => array( 'vote_down', 'question_downvote', 'answer_downvote' ),
			'given_vote_up'      => array( 'voted_up', 'question_upvoted', 'answer_upvoted' ),
			'given_vote_down'    => array( 'voted_down', 'question_downvoted', 'answer_downvoted' ),
			'selecting_answer'   => 'select_answer',
			'select_answer'      => 'best_answer',
			'comment'            => 'new_comment',
		);

		$found = false;

		foreach ( $events as $new_event => $olds ) {
			if ( is_array( $olds ) && in_array( $old_event, $olds, true ) ) {
				$found = $new_event;
				break;
			} elseif ( $old_event === $olds ) {
				$found = $new_event;
				break;
			}
		}

		if ( false !== $found ) {
			return $found;
		}

		return $old_event;
	}

	/**
	 * Migrate old category options from option table to term meta table.
	 */
	public function migrate_category_data() {
		global $wpdb;

		$terms = $wpdb->get_results( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('question_category') ORDER BY t.name ASC" ); // @codingStandardsIgnoreLine.

		foreach ( (array) $terms as $term ) {
			$term_meta = get_option( 'asqa_cat_' . $term->term_id );

			if ( isset( $term_meta['asqa_image'] ) ) {
				$term_meta['image'] = $term_meta['asqa_image'];
				unset( $term_meta['asqa_image'] );
			}

			if ( isset( $term_meta['asqa_icon'] ) ) {
				$term_meta['icon'] = $term_meta['asqa_icon'];
				unset( $term_meta['asqa_icon'] );
			}

			if ( isset( $term_meta['asqa_color'] ) ) {
				$term_meta['color'] = $term_meta['asqa_color'];
				unset( $term_meta['asqa_color'] );
			}

			update_term_meta( $term->term_id, 'asqa_category', $term_meta );
			delete_option( 'asqa_cat_' . $term->term_id );
		}

		print( esc_attr__( 'Categories data migrated', 'smart-question-answer' ) );
	}
}
