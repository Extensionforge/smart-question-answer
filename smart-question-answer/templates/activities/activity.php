<?php
/**
 * Activity item template.
 *
 * @link       https://extensionforge.com
 * @since      4.1.2
 * @license    GPL3+
 * @package    SmartQa
 * @subpackage Templates
 *
 * @global object $activities Activity query.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="asqa-activity-item">

	<?php if ( $activities->have_group_items() ) : ?>
		<div class="asqa-activity-icon">
			<i class="<?php $activities->the_icon(); ?>"></i>
		</div>
	<?php else : ?>
		<div class="asqa-activity-avatar">
			<?php echo $activities->get_avatar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>

	<div class="asqa-activity-right">

		<?php if ( $activities->have_group_items() ) : ?>

			<div class="asqa-activity-content">
				<div class="asqa-activity-header">
					<?php
					echo wp_kses_post(
						asqa_user_display_name(
							array(
								'user_id'      => $activities->get_user_id(),
								'html'         => true,
								'full_details' => true,
							)
						)
					);
					?>
					<span class="asqa-activity-verb"><?php $activities->the_verb(); ?></span>
					<span>
					<?php
						$count = $activities->count_group();

						echo esc_attr(
							sprintf(
								// translators: %d is activity count.
								_n( 'with other activity', 'with other %d activities', $count, 'smart-question-answer' ), // phpcs:ignore WordPress.WP.I18n
								(int) $count
							)
						);
					?>
					</span>
				</div>

				<div class="asqa-activity-ref">
					<a href="<?php echo esc_url( get_permalink( $activities->get_q_id() ) ); ?>"><?php echo esc_html( get_the_title( $activities->get_q_id() ) ); ?></a>
				</div>

				<div class="asqa-activities-same">
					<?php $activities->group_start(); ?>

					<?php
					while ( $activities->have_group() ) :
						$activities->the_object();
						?>
						<div class="asqa-activity-same">
							<div class="asqa-activity-avatar">
								<?php echo wp_kses_post( $activities->get_avatar( 35 ) ); ?>
							</div>

							<div class="asqa-activity-right">
								<div class="asqa-activity-header">
									<?php
									echo wp_kses_post(
										asqa_user_display_name(
											array(
												'user_id' => $activities->get_user_id(),
												'html'    => true,
												'full_details' => true,
											)
										)
									);
									?>
								</div>

								<div class="asqa-activity-ref">
									<span class="asqa-activity-verb"><?php $activities->the_verb(); ?></span> <time class="asqa-activity-date"><?php echo esc_html( asqa_human_time( $activities->get_the_date(), false ) ); ?></time>
								</div>

								<div class="asqa-activity-ref">
									<?php $activities->the_ref_content(); ?>
								</div>
							</div>

						</div>
					<?php endwhile; ?>

					<?php $activities->group_end(); ?>
				</div>

			</div>

		<?php else : ?>
			<div class="asqa-activity-content">

				<div class="asqa-activity-header">
					<?php
					echo wp_kses_post(
						asqa_user_display_name(
							array(
								'user_id'      => $activities->get_user_id(),
								'html'         => true,
								'full_details' => true,
							)
						)
					);
					?>
					<span class="asqa-activity-verb"><?php $activities->the_verb(); ?></span>
					<time class="asqa-activity-date"><?php echo esc_html( asqa_human_time( $activities->get_the_date(), false ) ); ?></time>
				</div>

				<div class="asqa-activity-ref">
					<?php $activities->the_ref_content(); ?>
				</div>

			</div>
		<?php endif; ?>

	</div>
</div>
