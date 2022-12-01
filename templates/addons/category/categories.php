<?php
/**
 * Categories page.
 *
 * Display categories page
 *
 * @link        http://extensionforge.com
 * @since       4.0
 * @package     SmartQa
 * @subpackage  Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $question_categories;
?>

<?php dynamic_sidebar( 'asqa-top' ); ?>

<div class="asqa-row">
	<div class="<?php echo is_active_sidebar( 'asqa-category' ) && is_smartqa() ? 'asqa-col-9' : 'asqa-col-12'; ?>">
		<div id="asqa-categories" class="clearfix">
			<ul class="asqa-term-category-box clearfix">

				<?php foreach ( (array) $question_categories as $key => $category ) : ?>
					<li class="clearfix">
						<div class="asqa-category-item">
							<div class="asqa-cat-img-c">

								<?php asqa_category_icon( $category->term_id ); ?>

								<span class="asqa-term-count">
									<?php
										echo esc_attr(
											sprintf(
												// translators: %d is category question count.
												_n( '%d Question', '%d Questions', $category->count, 'smart-question-answer' ),
												(int) $category->count
											)
										);
									?>
								</span>

								<a class="asqa-categories-feat" style="height:<?php echo (int) asqa_opt( 'categories_image_height' ); ?>px" href="<?php echo esc_url( get_category_link( $category ) ); ?>">
									<?php echo wp_kses_post( asqa_get_category_image( $category->term_id, asqa_opt( 'categories_image_height' ) ) ); ?>
								</a>
							</div>

							<div class="asqa-term-title">
								<a class="term-title" href="<?php echo esc_url( get_category_link( $category ) ); ?>">
									<?php echo esc_html( $category->name ); ?>
								</a>

								<?php $sub_cat_count = count( get_term_children( $category->term_id, 'question_category' ) ); ?>

								<?php if ( $sub_cat_count > 0 ) : ?>
									<span class="asqa-sub-category">
										<?php
											echo esc_attr(
												sprintf(
													// Translators: %d contains count of sub category.
													_n( '%d Sub category', '%d Sub categories', (int) $sub_cat_count, 'smart-question-answer' ),
													(int) $sub_cat_count
												)
											);
										?>
									</span>
								<?php endif; ?>

							</div>

							<?php if ( ! empty( $category->description ) ) : ?>
								<div class="asqa-taxo-description">
									<?php echo esc_html( asqa_truncate_chars( $category->description, 120 ) ); ?>
								</div>
							<?php endif; ?>

						</div>
					</li>
				<?php endforeach; ?>

			</ul>
		</div>
		<?php asqa_pagination(); ?>
	</div>

	<?php if ( is_active_sidebar( 'asqa-category' ) && is_smartqa() ) : ?>
		<div class="asqa-question-right asqa-col-3">
			<?php dynamic_sidebar( 'asqa-category' ); ?>
		</div>
	<?php endif; ?>
</div>
