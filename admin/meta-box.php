<?php
/**
 * SmartQas admin meta boxes.
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-3.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Meta box class.
 * Registers meta box for admin post edit screen.
 */
class ASQA_Question_Meta_Box {
	/**
	 * Initialize the class.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Hook meta boxes in post edit screen.
	 *
	 * @param string $post_type Post type.
	 */
	public function add_meta_box( $post_type ) {
		if ( 'question' === $post_type ) {
			add_meta_box(
				'asqa_answers_meta_box',
				// translators: %d is answers count of a question.
				sprintf( __( ' %d Answers', 'smart-question-answer' ), asqa_get_answers_count() ),
				array( $this, 'answers_meta_box_content' ),
				$post_type,
				'normal',
				'high'
			);
		}

		if ( 'question' === $post_type || 'answer' === $post_type ) {
			add_meta_box( 'asqa_question_meta_box', __( 'Question', 'smart-question-answer' ), array( $this, 'question_meta_box_content' ), $post_type, 'side', 'high' );
		}
	}

	/**
	 * Render Meta Box content.
	 */
	public function answers_meta_box_content() {
		?>
		<div id="answers-list" data-questionid="<?php the_ID(); ?>">


		</div>
		<br />
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=answer&post_parent=' . get_the_ID() ) ); ?>" class="button add-answer"><?php esc_html_e( 'Add an answer', 'smart-question-answer' ); ?></a>

		<script type="text/html" id="asqa-answer-template">
			<div class="author">
				<a href="#" class="asqa-ansm-avatar">{{{avatar}}}</a>
				<strong class="asqa-ansm-name">{{author}}</strong>
			</div>
			<div class="asqa-ansm-inner">
				<div class="asqa-ansm-meta">
					<span class="post-status">{{status}}</span>
					{{{activity}}}
				</div>
				<div class="asqa-ansm-content">{{{content}}}</div>
				<div class="answer-actions">
					<span><a href="{{{editLink}}}"><?php esc_attr_e( 'Edit', 'smart-question-answer' ); ?></a></span>
					<span class="delete vim-d vim-destructive"> | <a href="{{{trashLink}}}"><?php esc_attr_e( 'Trash', 'smart-question-answer' ); ?></a></span>
				</div>
			</div>
		</script>
		<?php
	}

	/**
	 * Question meta box.
	 *
	 * @param object|integer|null $_post Post.
	 */
	public function question_meta_box_content( $_post ) {
		$ans_count  = asqa_get_answers_count( $_post->ID );
		$vote_count = asqa_get_votes_net( $_post );
		?>
			<ul class="asqa-meta-list">

				<?php if ( 'answer' !== $_post->post_type ) : ?>
					<li>
						<i class="apicon-answer"></i>
						<?php
							echo wp_kses_post(
								sprintf(
									// translators: %d is answers count of a question.
									_n( '<strong>%d</strong> Answer', '<strong>%d</strong> Answers', $ans_count, 'smart-question-answer' ),
									$ans_count
								)
							);
						?>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=answer&post_parent=' . get_the_ID() ) ); ?>" class="add-answer"><?php esc_attr_e( 'Add an answer', 'smart-question-answer' ); ?></a>
					</li>
				<?php endif; ?>

				<li>
					<?php $nonce = wp_create_nonce( 'admin_vote' ); ?>
					<i class="apicon-thumb-up"></i>
					<?php
						echo wp_kses_post(
							// translators: %d is answers count of a question.
							sprintf( _n( '<strong>%d</strong> Vote', '<strong>%d</strong> Votes', $vote_count, 'smart-question-answer' ), $vote_count )
						);
					?>

					<a id="asqa-vote-down" href="#" class="vote button button-small asqa-ajax-btn" data-query="asqa_admin_vote::<?php echo esc_attr( $nonce ); ?>::<?php echo esc_attr( $_post->ID ); ?>::down" data-cb="replaceText">
						<?php esc_html_e( '-', 'smart-question-answer' ); ?>
					</a>

					<a id="asqa-vote-up" href="#" class="vote button button-small asqa-ajax-btn" data-query="asqa_admin_vote::<?php echo esc_attr( $nonce ); ?>::<?php echo esc_attr( $_post->ID ); ?>::up" data-cb="replaceText">
						<?php esc_attr_e( '+', 'smart-question-answer' ); ?>
					</a>
				</li>
				<li><?php $this->flag_meta_box( $_post ); ?> </li>
			</ul>
		<?php
	}

	/**
	 * Show flags and clear flag button in post edit screen.
	 *
	 * @param object $post Post.
	 */
	public function flag_meta_box( $post ) {
		$args = array(
			'action'         => 'asqa_ajax',
			'asqa_ajax_action' => 'asqa_clear_flag',
			'__nonce'        => wp_create_nonce( 'clear_flag_' . $post->ID ),
			'post_id'        => $post->ID,
		);

		?>
			<i class="apicon-flag"></i>
			<strong class="asqa-question-flag-count"><?php asqa_post_field( 'flags', $post ); ?></strong> <?php esc_attr_e( 'Flag', 'smart-question-answer' ); ?>
			<a id="asqa-clear-flag" href="#" data-query="<?php echo esc_js( wp_json_encode( $args ) ); ?>" class="flag-clear" data-cb="afterFlagClear"><?php esc_attr_e( 'Clear flag', 'smart-question-answer' ); ?></a>

			<script type="text/javascript">
				jQuery(document).ready(function($){
					$('#asqa-clear-flag').on( 'click', function(e){
						e.preventDefault();
						var self = this;
						var q = JSON.parse($(self).attr('data-query'));

						$.ajax({
							url: ajaxurl,
							data: q,
							type: 'POST',
							success: function(data){
								$('.asqa-question-flag-count').text('0');
								$('.column-flag .flag-count').removeClass('flagged');
								$(self).remove();
							}
						});
					})
				});
			</script>
		<?php
	}
}
