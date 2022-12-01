<?php
/**
 * Tag page
 * Display list of question of a tag
 *
 * @package SmartQa
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php dynamic_sidebar( 'asqa-top' ); ?>

<div class="row">

	<div id="asqa-lists" class="<?php echo is_active_sidebar( 'asqa-tag' ) ? 'asqa-col-9' : 'asqa-col-12'; ?>">
		<div class="asqa-taxo-detail clearfix">

			<h2 class="entry-title">
				<?php echo esc_html( $question_tag->name ); ?>
				<span class="asqa-tax-item-count">
					<?php
						// translators: %d is count of question.
						echo esc_attr( sprintf( _n( '%d Question', '%d Questions', $question_tag->count, 'smart-question-answer' ), $question_tag->count ) );
					?>
				</span>
			</h2>

			<?php if ( ! empty( $question_tag->description ) ) : ?>
				<p class="asqa-taxo-description"><?php echo wp_kses_post( $question_tag->description ); ?></p>
			<?php endif; ?>

		</div>

		<?php asqa_get_template_part( 'question-list' ); ?>
	</div>

	<?php if ( is_active_sidebar( 'asqa-tag' ) && is_smartqa() ) : ?>

		<div class="asqa-question-right asqa-col-3">
			<?php dynamic_sidebar( 'asqa-tag' ); ?>
		</div>

	<?php endif; ?>

</div>
