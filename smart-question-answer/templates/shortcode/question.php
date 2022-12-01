<?php
/**
 * This file is responsible for displaying question page
 * This file can be overridden by creating a smartqa directory in active theme folder.
 *
 * @package    SmartQa
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GNU Public License
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="asqa-single" class="asqa-q clearfix" itemtype="https://schema.org/Question" itemscope="">

	<h1 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
	<div class="asqa-question-lr">
		<div class="asqa-q-left">
			<div class="asqa-question-meta clearfix">
			<?php asqa_question_metas(); ?>
		</div>

		<div ap="question" apId="<?php the_ID(); ?>">
			<div id="question" role="main" class="asqa-content">
				<div class="asqa-single-vote"><?php asqa_vote_btn(); ?></div>
				<?php
				/**
				 * Action triggered before question title.
				 *
				 * @since   2.0
				 */
				do_action( 'asqa_before_question_title' );
				?>
				<div class="asqa-avatar">
					<a href="<?php asqa_profile_link(); ?>">
						<?php asqa_author_avatar( asqa_opt( 'avatar_size_qquestion' ) ); ?>
					</a>
				</div>
				<div class="asqa-cell clearfix">
					<div class="asqa-cell-inner">
						<div class="asqa-q-metas">
							<?php
								asqa_user_display_name(
									array(
										'html' => true,
										'echo' => true,
									)
								);
								?>
							<a href="<?php the_permalink(); ?>" class="asqa-posted">
								<?php
									echo esc_attr(
										sprintf(
											'<time itemprop="datePublished" datetime="%1$s">%2$s</time>',
											asqa_get_time( get_the_ID(), 'c' ),
											sprintf(
												// translators: %s is human readable time difference.
												__( 'Posted %s', 'smart-question-answer' ),
												asqa_human_time( asqa_get_time( get_the_ID(), 'U' ) )
											)
										)
									);
									?>
							</a>
							<?php asqa_recent_post_activity(); ?>
							<?php echo asqa_post_status_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<!-- Start asqa-content-inner -->
						<div class="asqa-q-inner">
							<?php
							/**
							 * Action triggered before question content.
							 *
							 * @since   2.0.0
							 */
							do_action( 'asqa_before_question_content' );
							?>

							<div class="question-content asqa-q-content" itemprop="text">
								<?php the_content(); ?>
							</div>

							<?php
								/**
								 * Action triggered after question content.
								 *
								 * @since   2.0.0
								 */
								do_action( 'asqa_after_question_content' );
							?>

						</div>
					</div>
				</div>
			</div>
		</div>
			<a class="asqa-eq-view-ans" href="<?php the_permalink(); ?>">
				<?php esc_attr_e( 'View all answers', 'smart-question-answer' ); ?>
			</a>
		</div>

	</div>
</div>
