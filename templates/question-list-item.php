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

			
			<?php $author = 0 == $thepost->post_author ? 'anonymous_' . $thepost->ID : $thepost->post_author; // @codingStandardsIgnoreLine

	// @codingStandardsIgnoreLine
	if ( false !== strpos( $author, 'anonymous' ) && is_array( $thepost->fields ) && ! empty( $thepost->fields['anonymous_name'] ) ) {
		$author = $thepost->fields['anonymous_name'];
	}

	$verify = get_user_meta( $author, "bp_verified_member", true );

	$verifybadge = '<span class="bp-verified-badge mainpage"></span>';

	if($verify==1){
			$addon = '<div class="asqa_profile_container"><div  style="float:left;"  class="asqa_profile_userlink">'.bp_core_get_userlink($author).'</div><div  style="float:left;"  class="asqa_profile_badge">'.$verifybadge.'</div></div>';
	}else {

	$addon =  '<div class="asqa_profile_container"><div  style="float:left;"  class="asqa_profile_userlink">'.bp_core_get_userlink($author).'</div></div>';
} ?>
			<div class="asqa-display-question-meta">
				<span style="float:left;" class="asqa_username_addon_container"><?php echo $addon ?></span>
				<?php asqa_question_metas(); 
// check if and how many attachments exist
global $wpdb;
$tb_posts = $wpdb->prefix . 'posts';
$attachments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tb_posts WHERE `post_type` = 'attachment' AND `post_parent` = '%d'",$post_id));
	$anzahl = 0;
	foreach ($attachments as $att) { $anzahl++; } 
	
	if ($anzahl>0) {
		$anhangtext = "Dieser Beitrag enthält einen Anhang.";
		if($anzahl>1){$anhangtext = "Dieser Beitrag enthält ".$anzahl." Anhänge.";}
?>
			
<span style="float:right; color:#888; line-height:2em; font-size:12px" class="asqa_username_addon_container_bottom">
	
<span asqa-data-tooltip="<?php echo $anhangtext ?>" data-flow="top"><i class="apicon-file-archive-o"></i>&nbsp;x&nbsp;<?php echo $anzahl ?></span>
	<span>
	<?php } ?>
			</div>
	
		</div>
		</div>
		
	</div>	
</div><!-- list item -->