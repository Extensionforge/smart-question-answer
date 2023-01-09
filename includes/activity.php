<?php
/**
 * SmartQa activity helper functions.
 *
 * @package      SmartQa
 * @subpackage   Activity
 * @copyright    Copyright (c) 2013, Peter Mertzlin
 * @author       Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license      GPL-3.0+
 * @since        1.0.0
 * @since        1.0.0 Fixed: CS bugs.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Get the global SmartQa activity instance.
 *
 * @return Object Return instance of @see SmartQa\Activity_Helper().
 * @since 1.0.0
 */
function asqa_activity_object() {
	if ( ! smartqa()->activity ) {
		smartqa()->activity = SmartQa\Activity_Helper::get_instance();
	}

	return smartqa()->activity;
}

/**
 * Insert activity into database. This function is an alias  of @see SmartQa\Activity_Helper::insert().
 *
 * @param array $args Arguments for insert. All list of arguments can be seen at @see SmartQa\Activity_Helper::insert().
 * @return WP_Error|integer Returns last inserted id or `WP_Error` on fail.
 *
 * @since 1.0.0 Introduced
 */
function asqa_activity_add( $args = array() ) {
	return asqa_activity_object()->insert( $args );
}

/**
 * Delete all activities related to a post.
 *
 * If given post is a question then it delete all activities by column `activity_q_id` else
 * by `activity_a_id`. More detail about activity delete can be found here @see SmartQa\Activity_Helper::delete()
 *
 * @param  WP_Post|integer $post_id WordPress post object or post ID.
 * @return WP_Error|integer Return numbers of rows deleted on success.
 * @since 1.0.0
 */
function asqa_delete_post_activity( $post_id ) {
	$_post = asqa_get_post( $post_id );

	// Check if SmartQa posts.
	if ( ! asqa_is_cpt( $_post ) ) {
		return new WP_Error( 'not_cpt', __( 'Not SmartQa posts', 'smart-question-answer' ) );
	}

	$where = array();

	if ( 'question' === $_post->post_type ) {
		$where['q_id'] = $_post->ID;
	} else {
		$where['a_id'] = $_post->ID;
	}

	// Delete all activities by post id.
	return asqa_activity_object()->delete( $where );
}

/**
 * Delete all activities related to a comment.
 *
 * More detail about activity delete can be found here @see SmartQa\Activity_Helper::delete()
 *
 * @param Comment|integer $comment_id WordPress comment object or comment ID.
 * @return WP_Error|integer Return numbers of rows deleted on success.
 * @since  1.0.0
 */
function asqa_delete_comment_activity( $comment_id ) {
	if ( 'smartqa' !== get_comment_type( $comment_id ) ) {
		return;
	}

	// Delete all activities by post id.
	return asqa_activity_object()->delete( array( 'c_id' => $comment_id ) );
}

/**
 * Delete all activities related to a user.
 *
 * More detail about activity delete can be found here @see SmartQa\Activity_Helper::delete()
 *
 * @param User|integer $user_id WordPress user object or user ID.
 * @return WP_Error|integer Return numbers of rows deleted on success.
 * @since  1.0.0
 */
function asqa_delete_user_activity( $user_id ) {
	// Delete all activities by post id.
	return asqa_activity_object()->delete( array( 'user_id' => $user_id ) );
}

/**
 * Parse raw activity returned from database. Rename column name
 * append action data.
 *
 * @param object $activity Activity object returned from database.
 * @return object|false
 * @since 1.0.0
 */
function asqa_activity_parse( $activity ) {
	if ( ! is_object( $activity ) ) {
		return false;
	}

	$new = array();

	// Rename keys.
	foreach ( $activity as $key => $value ) {
		$new[ str_replace( 'activity_', '', $key ) ] = $value;
	}

	$new = (object) $new;

	// Append actions data if exists.
	if ( asqa_activity_object()->action_exists( $new->action ) ) {
		$new->action = asqa_activity_object()->get_action( $new->action );
	}

	return $new;
}

/**
 * Return recent activity of question or answer.
 *
 * @param Wp_Post|integer|false $_post       WordPress post object or false for global post.
 * @param null                  $deprecated  Deprecated.
 * @return object|false Return parsed activity on success else false.
 * @since 1.0.0
 * @since 1.0.0 Deprecated argument `$get_cached`.
 */
function asqa_get_recent_activity( $_post = false, $deprecated = null ) {
	if ( null !== $deprecated ) {
		_deprecated_argument( __FUNCTION__, '1.0.0' );
	}

	global $wpdb;
	$_post = asqa_get_post( $_post );

	// Return if not smartqa posts.
	if ( ! asqa_is_cpt( $_post ) ) {
		return;
	}

	$type   = $_post->post_type;
	$column = 'answer' === $type ? 'a_id' : 'q_id';

	$q_where = '';

	if ( 'q_id' === $column && is_question() ) {
		$q_where = " AND (activity_a_id = 0 OR activity_action IN('new_a', 'unselected','selected') )";
	}

	$activity = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->asqa_activity} WHERE activity_{$column} = %d$q_where ORDER BY activity_date DESC LIMIT 1", $_post->ID ) ); // phpcs:ignore WordPress.DB

	// Parse.
	if ( $activity ) {
		$activity = asqa_activity_parse( $activity );
	}

	return $activity;
}





function TimeAgo ($oldTime, $newTime) {
$timeCalc = strtotime($newTime) - strtotime($oldTime);
if ($timeCalc >= (60*60*24*30*12*2)){
	$timeCalc = "vor " . intval($timeCalc/60/60/24/30/12) . " Jahren";
	}else if ($timeCalc >= (60*60*24*30*12)){
		$timeCalc = "vor " . intval($timeCalc/60/60/24/30/12) . " Jahr";
	}else if ($timeCalc >= (60*60*24*30*2)){
		$timeCalc = "vor " . intval($timeCalc/60/60/24/30) . " Monaten";
	}else if ($timeCalc >= (60*60*24*30)){
		$timeCalc = "vor " . intval($timeCalc/60/60/24/30) . " Monat";
	}else if ($timeCalc >= (60*60*24*2)){
		$timeCalc = "vor " . intval($timeCalc/60/60/24) . " Tagen";
	}else if ($timeCalc >= (60*60*24)){
		$timeCalc = " gestern";
	}else if ($timeCalc >= (60*60*2)){
		$timeCalc = "vor " . intval($timeCalc/60/60) . " Stunden";
	}else if ($timeCalc >= (60*60)){
		$timeCalc = "vor " . intval($timeCalc/60/60) . " Stunde";
	}else if ($timeCalc >= 60*2){
		$timeCalc = "vor " . intval($timeCalc/60) . " Minuten";
	}else if ($timeCalc >= 60){
		$timeCalc = "vor " . intval($timeCalc/60) . " Minute";
	}else if ($timeCalc > 0){
		$timeCalc = "vor " . intval($timeCalc) . " Sekunden";
	}
return $timeCalc;
}


function TimeAgoo ($oldTime, $newTime) {
$timeCalc = strtotime($newTime) - strtotime($oldTime);

return $timeCalc;
}







/**
 * Output recent activities of a post.
 *
 * @param Wp_Post|integer|null $_post WordPress post object or null for global post.
 * @param boolean              $echo  Echo or return. Default is `echo`.
 * @param boolean              $query_db  Get rows from database. Default is `false`.
 * @return void|string
 */
function asqa_recent_activity_ago( $_post = null, $echo = true, $query_db = null ) {
	$html     = '';
	$_post    = asqa_get_post( $_post );
	$activity = asqa_get_recent_activity( $_post );


	if ( $activity ) {
		
		$html .= ' ' . esc_html( $activity->action['verb'] );
	
		if ( 'answer' === $activity->action['ref_type'] ) {
			$link = asqa_get_short_link( array( 'asqa_a' => $activity->a_id ) );
		} elseif ( 'comment' === $activity->action['ref_type'] ) {
			$link = asqa_get_short_link( array( 'asqa_c' => $activity->c_id ) );
		} else {
			$link = asqa_get_short_link( array( 'asqa_q' => $activity->q_id ) );
		}

		
		$html .= ' am <time itemprop="dateModified" datetime="' . mysql2date( 'c', $activity->date ) . '">' . asqa_human_time( $activity->date, false ) . '</time>';
		
		
	} else {
		$post_id = false;

		// Fallback to old activities.
		$html = asqa_latest_post_activity_html( $post_id, ! is_question() );
	}

	/**
	 * Filter recent post activity html.
	 *
	 * @param string $html HTML wrapped activity.
	 * @since 1.0.0
	 */
	$html = apply_filters( 'asqa_recent_activity', $html );

	if ( false === $echo ) {
		return $html;
	}


	//echo wp_kses_post( $html );
	echo "Letzte Aktualisierung ".TimeAgo($activity->date ,date("Y-m-d H:i:s"));
}



/**
 * USer tooltip to display.
 *
 */
function asqa_user_tooltip( $_post = null, $echo = true, $query_db = null  ) {
	$_post    = asqa_get_post( $_post );
	$activity = asqa_get_recent_activity( $_post );
	$isvip = array("");
	if(isset($activity->user_id)){
	$user_id = $activity->user_id;
	$user = get_user_by( 'id', $user_id );
	echo $user->user_login." (Neu hier) - X Fragen - X Antworten"; 

} else { echo "hideme";}

	
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}



/**
 * Output recent activities of a post.
 *
 * @param Wp_Post|integer|null $_post WordPress post object or null for global post.
 * @param boolean              $echo  Echo or return. Default is `echo`.
 * @param boolean              $query_db  Get rows from database. Default is `false`.
 * @return void|string
 */
function asqa_recent_activity( $_post = null, $echo = true, $query_db = null ) {
	$html     = '';
	$_post    = asqa_get_post( $_post );
	$activity = asqa_get_recent_activity( $_post );
	$isvip = array("");
	$user_id = $activity->user_id;

	if ( $activity ) {
		$html .= '<span class="asqa-post-history">';
		$html .= ' ' . esc_html( $activity->action['verb'] );
		$html .= ' von <a href="' . asqa_user_link( $activity->user_id ) . '" itemprop="author" itemscope itemtype="http://schema.org/Person"><span itemprop="name">' . asqa_user_display_name( $activity->user_id ) . '</span></a>';
		

		if ( 'answer' === $activity->action['ref_type'] ) {
			$link = asqa_get_short_link( array( 'asqa_a' => $activity->a_id ) );
		} elseif ( 'comment' === $activity->action['ref_type'] ) {
			$link = asqa_get_short_link( array( 'asqa_c' => $activity->c_id ) );
		} else {
			$link = asqa_get_short_link( array( 'asqa_q' => $activity->q_id ) );
		}

		$html .= ' <a href="' . esc_url( $link ) . '">';

		$isvip = get_user_meta($user_id,"bp_verified_member"); 
		if(isset($isvip[0])) { 
		if($isvip[0]!="") { 

		$html .= '<span class="bp-verified-badge"></span>'; 

		}}

		$html .= ' am <time itemprop="dateModified" datetime="' . mysql2date( 'c', $activity->date ) . '">' . asqa_human_time( $activity->date, false ) . '</time>';
		$html .= '</a>';
		$html .= '</span>';
	} else {
		$post_id = false;

		// Fallback to old activities.
		$html = asqa_latest_post_activity_html( $post_id, ! is_question() );
	}

	/**
	 * Filter recent post activity html.
	 *
	 * @param string $html HTML wrapped activity.
	 * @since 1.0.0
	 */
	$html = apply_filters( 'asqa_recent_activity', $html );

	if ( false === $echo ) {
		return $html;
	}

	echo wp_kses_post( $html );
}

/**
 * Prefetch activities of posts.
 *
 * @param array  $ids Array of post ids.
 * @param string $col Column.
 * @return object|false
 * @since 1.0.0
 */
function asqa_prefetch_recent_activities( $ids, $col = 'q_id' ) {
	global $wpdb;

	$ids_string = esc_sql( sanitize_comma_delimited( $ids ) );
	$col        = 'q_id' === $col ? 'q_id' : 'a_id';

	if ( empty( $ids_string ) ) {
		return;
	}

	$q_where = '';

	if ( 'q_id' === $col && is_question() ) {
		$q_where = " AND (activity_a_id = 0 OR activity_action IN('new_a', 'unselected','selected') )";
	}

	$query = "SELECT t1.* FROM {$wpdb->asqa_activity} t1 NATURAL JOIN (SELECT max(activity_date) AS activity_date FROM {$wpdb->asqa_activity} WHERE activity_{$col} IN({$ids_string})$q_where GROUP BY activity_{$col}) t2 ORDER BY t2.activity_date";

	$activity = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB

	foreach ( $activity as $a ) {
		$a = asqa_activity_parse( $a );
	}

	return $activity;
}
