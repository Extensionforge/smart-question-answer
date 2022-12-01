<?php
/**
 * Tags page layout
 *
 * @link http://extensionforge.com
 * @since 1.0
 *
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $question_tags;
?>

<?php dynamic_sidebar( 'asqa-top' ); ?>

<div id="asqa-tags" class="row">
	<div class="<?php echo is_active_sidebar( 'asqa-tags' ) && is_smartqa() ? 'asqa-col-9' : 'asqa-col-12'; ?>">

		<div class="asqa-list-head clearfix">
			<form id="asqa-search-form" class="asqa-search-form">
				<button class="asqa-btn asqa-search-btn" type="submit"><?php esc_attr_e( 'Search', 'smart-question-answer' ); ?></button>
				<div class="asqa-search-inner no-overflow">
					<input name="asqa_s" type="text" class="asqa-search-input asqa-form-input" placeholder="<?php esc_attr_e( 'Search tags', 'smart-question-answer' ); ?>" value="<?php echo esc_attr( get_query_var( 'asqa_s' ) ); ?>" />
				</div>
			</form>

			<?php asqa_list_filters(); ?>
		</div><!-- close .asqa-list-head.clearfix -->

		<ul class="asqa-term-tag-box clearfix">
			<?php foreach ( $question_tags as $key => $question_tag ) : ?>
				<li class="clearfix">
					<div class="asqa-tags-item">
						<a class="asqa-term-title" href="<?php echo esc_url( get_tag_link( $question_tag ) ); ?>">
							<?php echo esc_html( $question_tag->name ); ?>
						</a>
						<span class="asqa-tagq-count">
							<?php
								echo esc_attr(
									sprintf(
										// translators: %d is question count.
										_n( '%d Question', '%d Questions', $question_tag->count, 'smart-question-answer' ),
										$question_tag->count
									)
								);
							?>
						</span>
					</div>
				</li>
			<?php endforeach; ?>
		</ul><!-- close .asqa-term-tag-box.clearfix -->

		<?php asqa_pagination(); ?>
	</div><!-- close #asqa-tags -->

	<?php if ( is_active_sidebar( 'asqa-tags' ) && is_smartqa() ) { ?>
		<div class="asqa-tags-sidebar asqa-col-3">
			<?php dynamic_sidebar( 'asqa-tags' ); ?>
		</div>
	<?php } ?>

</div><!-- close .row -->

