<?php
/**
 * Display question list
 *
 * This template is used in base page, category, tag , etc
 *
 * @link https://extensionforge.com
 * @since unknown
 *
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( ! get_query_var( 'asqa_hide_list_head' ) ) : ?>
	<?php asqa_get_template_part( 'list-head' ); ?>
<?php endif; ?>

<?php if ( asqa_have_questions() ) : ?>

	<div class="asqa-questions">
		<?php
			/* Start the Loop */
		while ( asqa_have_questions() ) :
			asqa_the_question();
			asqa_get_template_part( 'question-list-item' );
			endwhile;
		?>
	</div>
	<?php asqa_questions_the_pagination(); ?>

<?php else : ?>

	<p class="asqa-no-questions">
		<?php esc_attr_e( 'There are no questions matching your query or you do not have permission to read them.', 'smart-question-answer' ); ?>
	</p>

	<?php asqa_get_template_part( 'login-signup' ); ?>
<?php endif; ?>
