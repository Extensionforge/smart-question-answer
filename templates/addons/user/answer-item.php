<?php
/**
 * BuddyPress answer item.
 *
 * Template used to render answer item in loop
 *
 * @link     https://extensionforge.com
 * @since    4.0.0
 * @license  GPL 3+
 * @package  WordPress/SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! asqa_user_can_view_post( get_the_ID() ) ) {
	return;
}

?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="asqa-answer-single asqa-bpsingle">
		<div class="asqa-bpsingle-title entry-title" itemprop="title">
			<?php asqa_answer_status(); ?>
			<a class="asqa-bpsingle-hyperlink" itemprop="url" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a>
		</div>

		<div class="asqa-bpsingle-content clearfix">
			<div class="asqa-avatar asqa-pull-left">
				<a href="<?php asqa_profile_link(); ?>">
					<?php asqa_author_avatar( 40 ); ?>
				</a>
			</div>
			<div class="asqa-bpsingle-desc no-overflow">
				<a href="<?php the_permalink(); ?>" class="asqa-bpsingle-published">
					<time itemprop="datePublished" datetime="<?php echo esc_attr( asqa_get_time( get_the_ID(), 'c' ) ); ?>">
						<?php
							echo esc_html(
								sprintf(
									// Translators: %s contain human readable time.
									__( 'Posted %s', 'smart-question-answer' ),
									asqa_human_time( asqa_get_time( get_the_ID(), 'U' ) )
								)
							);
							?>
					</time>
				</a>
				<p><?php echo esc_html( asqa_truncate_chars( get_the_content(), 200 ) ); ?></p>
				<a href="<?php the_permalink(); ?>" class="asqa-view-question"><?php esc_html_e( 'View Question', 'smart-question-answer' ); ?></a>
			</div>
		</div>

		<div class="asqa-bpsingle-meta">
			<span class="apicon-thumb-up">
				<?php
					// translators: %d is count of net votes.
					echo esc_attr( sprintf( _n( '%d Vote', '%d Votes', asqa_get_votes_net(), 'smart-question-answer' ), asqa_get_votes_net() ) );
				?>
			</span>
			<?php if ( asqa_is_selected( get_the_ID() ) ) : ?>
				<span class="asqa-bpsingle-selected apicon-check" title="<?php esc_attr_e( 'This answer is selected as best', 'smart-question-answer' ); ?>"><?php esc_attr_e( 'Selected', 'smart-question-answer' ); ?></span>
			<?php endif; ?>
			<?php asqa_recent_post_activity(); ?>
		</div>

	</div>
</div>
