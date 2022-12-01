<?php
/**
 * Template used for generating single answer item.
 *
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @link https://extensionforge.com/smartqa
 * @package SmartQa
 * @subpackage Templates
 * @since 0.1
 * @since 4.1.2 Removed @see asqa_recent_post_activity().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( asqa_user_can_read_answer() ) :

	?>

<div id="post-<?php the_ID(); ?>" class="answer<?php echo asqa_is_selected() ? ' best-answer' : ''; ?>" apid="<?php the_ID(); ?>" ap="answer">
	<div class="asqa-content" itemprop="suggestedAnswer<?php echo asqa_is_selected() ? ' acceptedAnswer' : ''; ?>" itemscope itemtype="https://schema.org/Answer">
		<div class="asqa-single-vote"><?php asqa_vote_btn(); ?></div>
		<div class="asqa-avatar">
			<a href="<?php asqa_profile_link(); ?>">
				<?php asqa_author_avatar( asqa_opt( 'avatar_size_qanswer' ) ); ?>
			</a>
		</div>
		<div class="asqa-cell clearfix">
			<meta itemprop="@id" content="<?php the_ID(); ?>" /> <!-- This is for structured data, do not delete. -->
			<meta itemprop="url" content="<?php the_permalink(); ?>" /> <!-- This is for structured data, do not delete. -->
			<div class="asqa-cell-inner">
				<div class="asqa-q-metas">
					<?php echo wp_kses_post( asqa_user_display_name( array( 'html' => true ) ) ); ?>
					<a href="<?php the_permalink(); ?>" class="asqa-posted">
						<time itemprop="datePublished" datetime="<?php echo esc_attr( asqa_get_time( get_the_ID(), 'c' ) ); ?>">
							<?php
							echo esc_attr(
								sprintf(
									// translators: %s is time.
									__( 'Posted %s', 'smart-question-answer' ),
									asqa_human_time( asqa_get_time( get_the_ID(), 'U' ) )
								)
							);
							?>
						</time>
					</a>
					<span class="asqa-comments-count">
						<?php $comment_count = get_comments_number(); ?>
						<span itemprop="commentCount"><?php echo (int) $comment_count; ?></span>
						<?php
							echo esc_attr( sprintf( _n( 'Comment', 'Comments', $comment_count, 'smart-question-answer' ) ) );
						?>
					</span>
				</div>

				<div class="asqa-q-inner">
					<?php
					/**
					 * Action triggered before answer content.
					 *
					 * @since   3.0.0
					 */
					do_action( 'asqa_before_answer_content' );
					?>

					<div class="asqa-answer-content asqa-q-content" itemprop="text" asqa-content>
						<?php the_content(); ?>
					</div>

					<?php
					/**
					 * Action triggered after answer content.
					 *
					 * @since   3.0.0
					 */
					do_action( 'asqa_after_answer_content' );
					?>

				</div>

				<div class="asqa-post-footer clearfix">
					<?php echo asqa_select_answer_btn_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php asqa_post_actions_buttons(); ?>
					<?php do_action( 'asqa_answer_footer' ); ?>
				</div>

			</div>
			<?php asqa_post_comments(); ?>
		</div>

	</div>
</div>

	<?php
endif;
