<?php
/**
 * Template for question list item.
 *
 * @link    https://extensionforge.com
 * @since   0.1
 * @license GPL 2+
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! asqa_user_can_view_post( get_the_ID() ) ) {
	return;
}

$clearfix_class = array( 'asqa-questions-item clearfix' );

?>
<div id="question-<?php the_ID(); ?>" <?php post_class( $clearfix_class ); ?> itemtype="https://schema.org/Question" itemscope="">
	<div class="asqa-questions-inner">
		<div class="asqa-avatar asqa-pull-left">
			<a href="<?php asqa_profile_link(); ?>">
				<?php asqa_author_avatar( asqa_opt( 'avatar_size_list' ) ); ?>
			</a>
		</div>
		<div class="asqa-list-counts">
			<!-- Votes count -->
			<?php if ( ! asqa_opt( 'disable_voting_on_question' ) ) : ?>
				<span class="asqa-questions-count asqa-questions-vcount">
					<span itemprop="upvoteCount"><?php asqa_votes_net(); ?></span>
					<?php esc_attr_e( 'Votes', 'smart-question-answer' ); ?>
				</span>
			<?php endif; ?>

			<!-- Answer Count -->
			<a class="asqa-questions-count asqa-questions-acount" href="<?php echo esc_url( asqa_answers_link() ); ?>">
				<span itemprop="answerCount"><?php asqa_answers_count(); ?></span>
				<?php esc_attr_e( 'Ans', 'smart-question-answer' ); ?>
			</a>
		</div>

		<div class="asqa-questions-summery">
			<span class="asqa-questions-title" itemprop="name">
				<?php asqa_question_status(); ?>
				<a class="asqa-questions-hyperlink" itemprop="url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a>
			</span>
			<div class="asqa-display-question-meta">
				<?php asqa_question_metas(); ?>
			</div>


	<div class="asqa-display-question-previewtext"><?php
	$post_id = get_the_ID();
	$post_content = apply_filters('the_content', get_post_field('post_content', $post_id)); echo strip_tags($post_content);
	?></div>


		</div>
		

	</div>
	
</div><!-- list item -->
