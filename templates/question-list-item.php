<?php
/**
 * Template for question list item.
 *
 * @link    https://extensionforge.com
 * @since   0.1
 * @license GPL 2+
 * @package SmartQa
 */
if ( ! defined( 'ABSPATH' ) ) {	exit;}
if ( ! asqa_user_can_view_post( get_the_ID() ) ) {	return;}
$clearfix_class = array( 'asqa-questions-item clearfix' );



?>
<div id="question-<?php the_ID(); ?>" <?php post_class( $clearfix_class ); ?> itemtype="https://schema.org/Question" itemscope="">
	<div class="asqa-questions-inner">
		<div class="asqa-vnr-title">
			<div class="asqa-vnr-titletext">
				<h2><a class="asqa-questions-hyperlink" itemprop="url" href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h2>
			</div>
			<div class="asqa-vnr-titleanswers">
					<!-- Answer Count -->
			<a class="asqa-questions-count asqa-questions-acount" href="<?php echo esc_url( asqa_answers_link() ); ?>">
				<span itemprop="answerCount"><?php asqa_answers_count(); ?></span>
				Antworten
			</a>
			</div>
			<div class="asqa-vnr-titleviews">
						<!-- Views Count -->
			<a class="asqa-questions-count asqa-questions-acount" href="<?php echo esc_url( asqa_answers_link() ); ?>">
				<span itemprop="answerCount"><?php asqa_question_views(); ?></span>
				Ansichten
			</a>
			</div>
		</div>
		<div class="asqa-vnr-status">
			<?php if ( asqa_have_answer_selected() ) { ?>
		<div class="asqa-vnr-solvedicon">Gelöst</div>
	<?php } 
	$post_id = get_the_ID();
	$thepost    = get_post($post_id);
	$activity = asqa_get_recent_activity( $thepost );
	$neutest = intval(TimeAgoo($activity->date ,date("Y-m-d H:i:s")));
	if ($neutest<86400) { ?>
			<div class="asqa-vnr-newicon">Neu</div> <?php

	}
	?>

		</div>
		<div class="asqa-vnr-lastupdate"><?php asqa_recent_activity_ago(); ?></div>

		<div style="clear:both;"></div>
		<div class="asqa-vnr-intro">
			<div class="asqa-display-question-previewtext"><?php
				
				$post_content = apply_filters('the_content', get_post_field('post_content', $post_id)); echo strip_tags($post_content);?>	
			</div>
		</div>
		<div class="asqa-vnr-bottom">
			<div class="asqa-avatar asqa-pull-left asqa-avatar-mini-post">
			<a class="asqausertooltip" data-tooltip="<?php asqa_user_tooltip(); ?>" href="<?php asqa_profile_link(); ?>">
				<?php asqa_author_avatar( asqa_opt( 'avatar_size_list' ) ); ?>

			</a>
		</div>
		
		
		<div class="asqa-questions-summery">
			<span class="asqa-questions-title" itemprop="name">
				<?php asqa_question_status(); ?>
			
			</span>
			<div class="asqa-display-question-meta">
				<?php asqa_question_metas(); ?>
			</div>
	
		</div>
		</div>
		
	</div>	
</div><!-- list item -->