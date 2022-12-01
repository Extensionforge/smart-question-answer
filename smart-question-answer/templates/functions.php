<?php
/**
 * This file contains theme script, styles and other theme related functions.
 * This file can be overridden by creating a smartqa directory in active theme folder.
 *
 * @license   https://www.gnu.org/licenses/gpl-2.0.txt GNU Public License
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package   SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue scripts.
 */
function asqa_scripts_front() {
	asqa_assets();

	if ( ! is_smartqa() && asqa_opt( 'load_assets_in_smartqa_only' ) ) {
		return;
	}

	asqa_enqueue_scripts();

	$custom_css = '
		#smartqa .asqa-q-cells{
				margin-' . ( is_rtl() ? 'right' : 'left' ) . ': ' . ( asqa_opt( 'avatar_size_qquestion' ) + 10 ) . 'px;
		}
		#smartqa .asqa-a-cells{
				margin-' . ( is_rtl() ? 'right' : 'left' ) . ': ' . ( asqa_opt( 'avatar_size_qanswer' ) + 10 ) . 'px;
		}';

	wp_add_inline_style( 'smartqa-main', $custom_css );
	do_action( 'asqa_enqueue' );

	wp_enqueue_style( 'asqa-overrides', asqa_get_theme_url( 'css/overrides.css' ), array( 'smartqa-main' ), ASQA_VERSION );

	$aplang = array(
		'loading'                => __( 'Loading..', 'smart-question-answer' ),
		'sending'                => __( 'Sending request', 'smart-question-answer' ),
		// translators: %s is file size in MB.
		'file_size_error'        => esc_attr( sprintf( __( 'File size is bigger than %s MB', 'smart-question-answer' ), round( asqa_opt( 'max_upload_size' ) / ( 1024 * 1024 ), 2 ) ) ),
		'attached_max'           => __( 'You have already attached maximum numbers of allowed attachments', 'smart-question-answer' ),
		'commented'              => __( 'commented', 'smart-question-answer' ),
		'comment'                => __( 'Comment', 'smart-question-answer' ),
		'cancel'                 => __( 'Cancel', 'smart-question-answer' ),
		'update'                 => __( 'Update', 'smart-question-answer' ),
		'your_comment'           => __( 'Write your comment...', 'smart-question-answer' ),
		'notifications'          => __( 'Notifications', 'smart-question-answer' ),
		'mark_all_seen'          => __( 'Mark all as seen', 'smart-question-answer' ),
		'search'                 => __( 'Search', 'smart-question-answer' ),
		'no_permission_comments' => __( 'Sorry, you don\'t have permission to read comments.', 'smart-question-answer' ),
	);

	echo '<script type="text/javascript">';
	echo 'var ajaxurl = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '",';
	echo 'asqa_nonce 	= "' . esc_attr( wp_create_nonce( 'asqa_ajax_nonce' ) ) . '",';
	echo 'apTemplateUrl = "' . esc_url( asqa_get_theme_url( 'js-template', false, false ) ) . '";';
	echo 'apQuestionID = "' . (int) get_question_id() . '";';
	echo 'aplang = ' . wp_json_encode( $aplang ) . ';';
	echo 'disable_q_suggestion = "' . (bool) asqa_opt( 'disable_q_suggestion' ) . '";';
	echo '</script>';
}
add_action( 'wp_enqueue_scripts', 'asqa_scripts_front', 1 );

/**
 * Register widget positions.
 */
function asqa_widgets_positions() {
	register_sidebar(
		array(
			'name'          => __( '(SmartQa) Before', 'smart-question-answer' ),
			'id'            => 'asqa-before',
			'before_widget' => '<div id="%1$s" class="asqa-widget-pos %2$s">',
			'after_widget'  => '</div>',
			'description'   => __( 'Widgets in this area will be shown before smartqa body.', 'smart-question-answer' ),
			'before_title'  => '<h3 class="asqa-widget-title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( '(SmartQa) Question List Top', 'smart-question-answer' ),
			'id'            => 'asqa-top',
			'before_widget' => '<div id="%1$s" class="asqa-widget-pos %2$s">',
			'after_widget'  => '</div>',
			'description'   => __( 'Widgets in this area will be shown before questions list.', 'smart-question-answer' ),
			'before_title'  => '<h3 class="asqa-widget-title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( '(SmartQa) Sidebar', 'smart-question-answer' ),
			'id'            => 'asqa-sidebar',
			'before_widget' => '<div id="%1$s" class="asqa-widget-pos %2$s">',
			'after_widget'  => '</div>',
			'description'   => __( 'Widgets in this area will be shown in SmartQa sidebar except single question page.', 'smart-question-answer' ),
			'before_title'  => '<h3 class="asqa-widget-title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( '(SmartQa) Question Sidebar', 'smart-question-answer' ),
			'id'            => 'asqa-qsidebar',
			'before_widget' => '<div id="%1$s" class="asqa-widget-pos %2$s">',
			'after_widget'  => '</div>',
			'description'   => __( 'Widgets in this area will be shown in single question page sidebar.', 'smart-question-answer' ),
			'before_title'  => '<h3 class="asqa-widget-title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( '(SmartQa) Category Page', 'smart-question-answer' ),
			'id'            => 'asqa-category',
			'before_widget' => '<div id="%1$s" class="asqa-widget-pos %2$s">',
			'after_widget'  => '</div>',
			'description'   => __( 'Widgets in this area will be shown in category listing page.', 'smart-question-answer' ),
			'before_title'  => '<h3 class="asqa-widget-title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( '(SmartQa) Tag page', 'smart-question-answer' ),
			'id'            => 'asqa-tag',
			'before_widget' => '<div id="%1$s" class="asqa-widget-pos %2$s">',
			'after_widget'  => '</div>',
			'description'   => __( 'Widgets in this area will be shown in tag listing page.', 'smart-question-answer' ),
			'before_title'  => '<h3 class="asqa-widget-title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( '(SmartQa) Author page', 'smart-question-answer' ),
			'id'            => 'asqa-author',
			'before_widget' => '<div id="%1$s" class="asqa-widget-pos %2$s">',
			'after_widget'  => '</div>',
			'description'   => __( 'Widgets in this area will be shown in authors page.', 'smart-question-answer' ),
			'before_title'  => '<h3 class="asqa-widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'asqa_widgets_positions' );
