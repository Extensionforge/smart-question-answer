<?php
/**
 * This file is responsible for displaying question page
 * This file can be overridden by creating a smartqa directory in active theme folder.
 *
 * @package    SmartQa
 * @subpackage Templates
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GNU Public License
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 *
 * @since      0.0.1
 * @since      4.1.0 Renamed file from question.php.
 * @since      4.1.2 Removed @see asqa_recent_post_activity().
 * @since      4.1.5 Fixed date grammar when post is not published.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( asqa_have_answer_selected() ) { ?> 
<script>
	document.getElementsByClassName("main_title")[0].innerHTML = "<span class='o-label o-label--success-o asqa-state-resolved'>Gelöst</span>" + document.getElementsByClassName("main_title")[0].innerHTML;
</script>
<?php }
?>
<div id="asqa-single" class="asqa-q clearfix" itemscope itemtype="https://schema.org/QAPage">
	<div class="asqa-question-lr asqa-row" itemscope itemtype="https://schema.org/Question" itemprop="mainEntity">
		<meta itemprop="@id" content="<?php the_ID(); ?>" /> <!-- This is for structured data, do not delete. -->
		<meta itemprop="name" content="<?php the_title(); ?>" /> <!-- This is for structured data, do not delete. -->
		<div class="asqa-q-left <?php echo ( is_active_sidebar( 'asqa-qsidebar' ) ) ? 'asqa-col-8' : 'asqa-col-12'; ?>">
			<?php
				/**
				 * Action hook triggered before question meta in single question.
				 *
				 * @since 4.1.2
				 */
				do_action( 'asqa_before_question_meta' );
			?>
			<div class="asqa-question-meta clearfix">
				<?php asqa_question_metas(); ?>
			</div>
			<?php
				/**
				 * Action hook triggered after single question meta.
				 *
				 * @since 4.1.5
				 */
				do_action( 'asqa_after_question_meta' );
			?>
			<div ap="question" apid="<?php the_ID(); ?>">
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
								<span class="asqa-author" itemprop="author" itemscope itemtype="http://schema.org/Person">
									<?php
										asqa_user_display_name(
											array(
												'html' => true,
												'echo' => true,
											)
										);
										?>
								</span>
								<a href="<?php the_permalink(); ?>" class="asqa-posted">
									<?php
									$posted = 'future' === get_post_status() ? __( 'Scheduled for', 'smart-question-answer' ) : __( 'Published', 'smart-question-answer' );

									$time = asqa_get_time( get_the_ID(), 'U' );

									if ( 'future' !== get_post_status() ) {
										$time = asqa_human_time( $time );
									}
									?>
									<time itemprop="datePublished" datetime="<?php echo esc_attr( asqa_get_time( get_the_ID(), 'c' ) ); ?>"><?php echo esc_attr( $time ); ?></time>
								</a>
								<span class="asqa-comments-count">
									<?php $comment_count = get_comments_number(); ?>
									<?php
										// translators: %s comments count.
										echo wp_kses_post( sprintf( _n( '%s Comment', '%s Comments', $comment_count, 'smart-question-answer' ), '<span itemprop="commentCount">' . (int) $comment_count . '</span>' ) );
									?>
								</span>
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

							<div class="asqa-answer-content asqa-q-content" itemprop="text">
							
						<?php the_content(); 
												$media = get_attached_media( '' );
									//var_dump($media);
									if (count($media)>0){
										?>	<div id="asqa-display-attachments" class="asqa-display-attachments">
											
											<div class="asqa-display-attachments-header">
												<strong>Anhänge</strong>
											</div>
											
											<div class="asqa-display-attachments-list">
										<?php
										foreach ($media as $anhang) { 
										$has_permission = false;
										if ( is_user_logged_in() ) {

											$post_id = $anhang->ID;
											$authorid = $anhang->post_author;
											$current_userid = get_current_user_id();
											if($authorid==$current_userid){$has_permission = true;}

											if(current_user_can('administrator')){
												$has_permission = true;
											}

										}

?>
											
	<div class="asqa-attachment-item" id="asqa-attachment-item-id-<?php echo $anhang->ID; ?>">

	<a download="download" class="asqa-attachment-item__link" title="Anhang '<?php echo $anhang->post_title; ?>' herunterladen" href="<?php echo $anhang->guid; ?>" rel="postid-<?php echo $anhang->post_parent; ?>" target="_blank">
					
		<span class="dashicons dashicons-download"></span></a>
		<a title="Link in neuem Tab öffnen"href="<?php echo $anhang->guid; ?>"><span class="asqa-attachment-item__caption"><?php echo $anhang->post_title; ?></span></a>
	<?php
	if($has_permission==true) { ?>
		<a id="remove-asqa-attach-<?php echo $anhang->ID; ?>"   title="<?php echo $anhang->post_title; ?>" onclick="asqa_del_attachment(this.id, this.title)" href="javascript:void(0);" class="asqa-attachment-item__btn-del"><span class="dashicons dashicons-remove asqa_remove"></span></a><?php 
	}
		?>
</div> 
<?php } ?> 

</div></div> <?php } ?>
								
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

							<div class="asqa-post-footer clearfix">
								<?php asqa_post_actions_buttons(); ?>
								<?php do_action( 'asqa_post_footer' ); ?>
							</div>
						</div>

						<?php asqa_post_comments(); ?>
					</div>
				</div>
			</div>

			<?php
				/**
				 * Action triggered before answers.
				 *
				 * @since   4.1.8
				 */
				do_action( 'asqa_before_answers' );
			?>

			<?php
				// Get answers.
				asqa_answers();

				// Get answer form.
				asqa_get_template_part( 'answer-form' );
			?>
		</div>

		<?php if ( is_active_sidebar( 'asqa-qsidebar' ) ) { ?>
			<div class="asqa-question-right asqa-col-4">
				<div class="asqa-question-info">
					<?php dynamic_sidebar( 'asqa-qsidebar' ); ?>
				</div>
			</div>
		<?php } ?>

	</div>
</div>
