<?php
/**
 * BuddyPress question item.
 *
 * Template used to render question item in BuddyPress
 * profile questions page.
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
				<time itemprop="datePublished" datetime="<?php echo esc_attr( asqa_get_time( get_the_ID(), 'c' ) ); ?>" class="asqa-bpsingle-published">
					<?php
						// translators: %s is human time difference.
						echo esc_attr( sprintf( 'Posted %s', asqa_human_time( asqa_get_time( get_the_ID(), 'U' ) ) ) );
					?>
				</time>
				<a href="<?php the_permalink(); ?>" class="apicon-answer asqa-bpsingle-acount">
					<?php
						// translators: %d is total answer count.
						echo esc_attr( printf( _n( '%d Answer', '%d Answers', asqa_get_answers_count(), 'smart-question-answer' ), asqa_get_answers_count() ) );
					?>
				</a>

				<p><?php echo esc_html( wp_trim_words( get_the_content(), 30, '...' ) ); ?></p>
				<a href="<?php the_permalink(); ?>" class="asqa-view-question"><?php esc_html_e( 'View Question', 'smart-question-answer' ); ?></a>
			</div>
		</div>

		<div class="asqa-bpsingle-meta">

			<span class="apicon-thumb-up">
				<?php
					// translators: %d is net votes count.
					echo esc_attr( sprintf( _n( '%d Vote', '%d Votes', asqa_get_votes_net(), 'smart-question-answer' ), asqa_get_votes_net() ) );
				?>
			</span>

			<?php asqa_question_metas(); ?>
		</div>

	</div>
</div>
