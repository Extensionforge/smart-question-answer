<?php
/**
 * SmartQa questions widget template.
 *
 * @link https://extensionforge.com/smartqa
 * @since 2.0.1
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="asqa-questions-widget clearfix">
	<?php if ( asqa_have_questions() ) : ?>
		<?php
		while ( asqa_have_questions() ) :
			asqa_the_question();
			?>
			<div class="asqa-question-item">
				<a class="asqa-question-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				<div class="asqa-question-item-meta">
					<span class="asqa-ans-count">
						<?php
							// translators: %d is total answer count of a question.
							echo esc_attr( sprintf( _n( '%d Answer', '%d Answers', asqa_get_answers_count(), 'smart-question-answer' ), asqa_get_answers_count() ) );
						?>
					</span>
					|
					<span class="asqa-vote-count">
						<?php
							// translators: %d is total votes count.
							echo esc_attr( sprintf( _n( '%d Vote', '%d Votes', asqa_get_votes_net(), 'smart-question-answer' ), asqa_get_votes_net() ) );
						?>
					</span>
				</div>
			</div>
		<?php endwhile; ?>
	<?php else : ?>
		<?php esc_attr_e( 'No questions found.', 'smart-question-answer' ); ?>
	<?php endif; ?>
</div>
