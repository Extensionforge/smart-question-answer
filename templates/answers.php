<?php
/**
 * Answers content
 * Control the output of answers.
 *
 * @link https://extensionforge.com/smartqa
 * @since 2.0.1
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$count = ( '' !== get_query_var( 'answer_id' ) ? asqa_get_answers_count() : asqa_total_answers_found() );
?>
<apanswersw style="<?php echo ! asqa_have_answers() ? 'display:none' : ''; ?>">

	<div id="asqa-answers-c">
		<div class="asqa-sorting-tab clearfix">
			<h3 class="asqa-answers-label asqa-pull-left" ap="answers_count_t">
				<span itemprop="answerCount"><?php echo (int) $count; ?></span>
				<?php $a1 = __( 'Answer', 'smart-question-answer' ); $a2 = __( 'Answers', 'smart-question-answer' ); ?>
				<?php echo esc_attr( _n( $a1, $a2, $count, 'smart-question-answer' ) ); ?>
			</h3>

			<?php asqa_answers_tab( get_the_permalink() ); ?>
		</div>

		<?php
		if ( '' === get_query_var( 'answer_id' ) && asqa_have_answers() ) {
			asqa_answers_the_pagination();
		}
		?>

		<div id="answers">
			<apanswers>
				<?php if ( asqa_have_answers() ) : ?>

					<?php
					while ( asqa_have_answers() ) :
						asqa_the_answer();
						?>
						<?php include asqa_get_theme_location( 'answer.php' ); ?>
					<?php endwhile; ?>

				<?php endif; ?>
			</apanswers>

		</div>

		<?php if ( asqa_have_answers() ) : ?>
			<?php asqa_answers_the_pagination(); ?>
		<?php endif; ?>
	</div>
</apanswersw>
