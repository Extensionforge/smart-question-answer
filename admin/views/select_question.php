<?php
/**
 * Control the output of question select
 *
 * @link https://extensionforge.com
 * @since 2.0.0
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 * @since 1.0.0 Fixed: CS bugs.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>
<div id="asqa-admin-dashboard" class="wrap">
	<?php do_action( 'asqa_before_admin_page_title' ); ?>

	<h2><?php esc_attr_e( 'Select a question for new answer', 'smart-question-answer' ); ?></h2>
	<p><?php esc_attr_e( 'Slowly type for question suggestion and then click select button right to question title.', 'smart-question-answer' ); ?></p>

	<?php do_action( 'asqa_after_admin_page_title' ); ?>

	<div class="asqa-admin-container">
		<form class="question-selection">
			<input type="text" name="question_id" class="asqa-select-question" id="select-question-for-answer" />
			<input type="hidden" name="is_admin" value="true" />
		</form>
		<div id="similar_suggestions">
			<?php
				$questions = new Question_Query(
					array(
						'post_status' => array( 'publish', 'private_post' ),
					)
				);
				?>
			<?php if ( $questions->have_questions() ) : ?>
				<h3><?php esc_attr_e( 'Recently active questions', 'smart-question-answer' ); ?></h3>
				<div class="asqa-similar-questions">
					<?php
					while ( $questions->have_questions() ) :
						$questions->the_question();

						$url = add_query_arg(
							array(
								'post_type'   => 'answer',
								'post_parent' => get_the_ID(),
							),
							admin_url( 'post-new.php' )
						);
						?>
						<div class="asqa-q-suggestion-item clearfix">
							<a class="select-question-button button button-primary button-small" href="<?php echo esc_url( $url ); ?>"><?php esc_attr_e( 'Select', 'smart-question-answer' ); ?></a>

							<span class="question-title"><?php the_title(); ?>></span>
							<span class="acount">
								<?php
									echo esc_attr(
										sprintf(
											// translators: %d contain total answer of the question.
											_n( '%d Answer', '%d Answers', asqa_get_answers_count(), 'smart-question-answer' ),
											asqa_get_answers_count()
										)
									);
								?>

							</span>
						</div>
					<?php endwhile; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
