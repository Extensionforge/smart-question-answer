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
if(asqa_is_selected()){
	echo "<span class='asqa_accepted_answer'>Akzeptierte Antwort</span><br>";
}
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

				<div class="asqa-q-inner <?php if(asqa_is_selected()){
	echo "asqa_right_answer-background_inner";
} ?>">
					<?php
					/**
					 * Action triggered before answer content.
					 *
					 * @since   3.0.0
					 */
					do_action( 'asqa_before_answer_content' );
					?>

					<div class="asqa-answer-content asqa-q-content" itemprop="text" asqa-content>
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
					 * Action triggered after answer content.
					 *
					 * @since   3.0.0
					 */
					do_action( 'asqa_after_answer_content' );
					?>

				</div>
				<div class="accepted-answer-bewertung ph-10">
			<a href="https://www.computerwissen.de/live.html?_ga=2.135663223.712027186.1675330128-1326490809.1672965027" target="_blank"><img src="<?php echo plugin_dir_url( __FILE__ ).'images/cw-vsp-header.png';?>" alt="Webinar Bild"></a>
			<br><br>
			<p><strong>Schön, dass Ihre Frage beantwortet wurde!</strong></p>
			<p>Um weiterhin auf dem Laufenden zum Thema Technik und PC zu bleiben, nehmen Sie doch kostenlos teil an unseren monatlichen LIVE-Webinaren: <a href="https://www.computerwissen.de/live.html" target="_blank">hier</a> klicken für Registrierungsseite. Bei diesen Online-Shows können Sie uns <strong>all Ihre Fragen</strong> rund um das Thema <strong>Computer</strong> stellen und lernen jeden Monat etwas Neues.</p>
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
