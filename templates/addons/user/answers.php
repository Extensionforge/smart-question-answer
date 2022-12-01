<?php
/**
 * Display answers list
 *
 * This template is used in base page, category, tag , etc
 *
 * @link https://extensionforge.com
 * @since 4.0.0
 *
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $answers;
?>

	<?php if ( asqa_have_answers() ) : ?>
		<div id="asqa-bp-answers">
		<?php
			/* Start the Loop */
		while ( asqa_have_answers() ) :
			asqa_the_answer();
			asqa_get_template_part( 'addons/user/answer-item' );
			endwhile;
		?>
		</div>
		<?php
		if ( $answers->max_num_pages > 1 ) {
			$args = wp_json_encode(
				array(
					'asqa_ajax_action' => 'user_more_answers',
					'__nonce'        => wp_create_nonce( 'loadmore-answers' ),
					'type'           => 'answers',
					'current'        => 1,
					'user_id'        => get_queried_object_id(),
				)
			);

			echo '<a href="#" class="asqa-bp-loadmore asqa-btn" asqa-loadmore="' . esc_js( $args ) . '">' . esc_attr__( 'Load more answers', 'smart-question-answer' ) . '</a>';
		}
		?>

		<?php
		else :
			esc_attr_e( 'No answer posted by this user.', 'smart-question-answer' );
		endif;
		?>
