<?php
/**
 * Display single question category page.
 *
 * Display category page.
 *
 * @link        http://extensionforge.com
 * @since       4.0
 * @package     SmartQa
 * @subpackage  Templates
 * @since       4.1.1 Renamed file from category.php to single-category.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$icon = asqa_get_category_icon( $question_category->term_id );
?>

<?php dynamic_sidebar( 'asqa-top' ); ?>

<div class="asqa-row">
	<div id="asqa-category" class="<?php echo is_active_sidebar( 'asqa-category' ) && is_smartqa() ? 'asqa-col-9' : 'asqa-col-12'; ?>">

		<?php if ( asqa_category_have_image( $question_category->term_id ) ) : ?>
			<div class="asqa-category-feat" style="height: 300px;">
				<?php asqa_category_image( $question_category->term_id, 300 ); ?>
			</div>
		<?php endif; ?>

		<div class="asqa-taxo-detail">
			<?php if ( ! empty( $icon ) ) : ?>
				<div class="asqa-pull-left">
					<?php asqa_category_icon( $question_category->term_id ); ?>
				</div>
			<?php endif; ?>

			<div class="no-overflow">
				<div>
					<a class="entry-title" href="<?php echo esc_url( get_category_link( $question_category ) ); ?>">
						<?php echo esc_html( $question_category->name ); ?>
					</a>
					<span class="asqa-tax-count">
						<?php
							echo esc_attr(
								sprintf(
									// translators: %s is total question count of category.
									_n( '%d Question', '%d Questions', (int) $question_category->count, 'smart-question-answer' ),
									(int) $question_category->count
								)
							);
							?>
					</span>
				</div>


				<?php if ( '' !== $question_category->description ) : ?>
					<p class="asqa-taxo-description">
						<?php echo wp_kses_post( $question_category->description ); ?>
					</p>
				<?php endif; ?>

				<?php
					$sub_cat_count = count( get_term_children( $question_category->term_id, 'question_category' ) );

				if ( $sub_cat_count > 0 ) {
					echo '<div class="asqa-term-sub">';
					echo '<div class="sub-taxo-label">' . (int) $sub_cat_count . ' ' . esc_attr__( 'Sub Categories', 'smart-question-answer' ) . '</div>';
					asqa_sub_category_list( $question_category->term_id );
					echo '</div>';
				}
				?>
			</div>
		</div><!-- close .asqa-taxo-detail -->

		<?php asqa_get_template_part( 'question-list' ); ?>


	</div><!-- close #asqa-lists -->

	<?php if ( is_active_sidebar( 'asqa-category' ) && is_smartqa() ) { ?>
		<div class="asqa-question-right asqa-col-3">
			<?php dynamic_sidebar( 'asqa-category' ); ?>
		</div>
	<?php } ?>
</div><!-- close .row -->
