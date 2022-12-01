<?php
/**
 * Display question list header
 * Shows sorting, search, tags, category filter form. Also shows a ask button.
 *
 * @package SmartQa
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="asqa-list-head clearfix">
	<div class="pull-right">
		<?php asqa_ask_btn(); ?>
	</div>

	<?php asqa_get_template_part( 'search-form' ); ?>
	<?php asqa_list_filters(); ?>
</div>


<?php
/**
 * Display an alert showing count for unpublished questions.
 *
 * @since 4.1.13
 */

$questions_count = (int) get_user_meta( get_current_user_id(), '__asqa_unpublished_questions', true );

if ( $questions_count > 0 ) {
	// translators: %d is question count.
	$text = sprintf( _n( '%d question is', '%d questions are', $questions_count, 'smart-question-answer' ), $questions_count );

	echo '<div class="asqa-unpublished-alert asqa-alert warning"><i class="apicon-pin"></i>';
	printf(
		// Translators: Placeholder contain link to unpublished questions.
		esc_html__( 'Your %s unpublished. ', 'smart-question-answer' ),
		'<a href="' . esc_url( asqa_get_link_to( '/' ) ) . '?unpublished=true">' . esc_attr( $text ) . '</a>'
	);
	echo '</div>';
}
?>
