<?php
/**
 * Activities template.
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
<div class="asqa-activities">
	<?php if ( $activities->have() ) : ?>

		<?php
		// Loop for getting activities.
		while ( $activities->have() ) :
			$activities->the_object();
			// Shows date and time for timeline.
			$activities->the_when();

			/**
			 * Load activity item. Here we are not using `get_template_part()` because
			 * we wants to let template easily access PHP variables.
			 */
			include asqa_get_theme_location( 'activities/activity.php' );
		endwhile;
		?>

		<?php
		// Wether to show load more button or not.
		if ( ! $activities->have_pages() ) :
			?>
			<div class="asqa-activity-end asqa-activity-item">
				<div class="asqa-activity-icon">
					<i class="apicon-check"></i>
				</div>
				<p><?php esc_attr_e( 'That&sbquo;s all!', 'smart-question-answer' ); ?></p>
			</div>
		<?php else : ?>
			<div class="asqa-activity-more asqa-activity-item">
				<div class="asqa-activity-icon">
					<i class="apicon-dots"></i>
				</div>
				<div>
					<?php $activities->more_button(); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
	else :
		// When no activities found.
		?>
		<p><?php esc_attr_e( 'No activities found!', 'smart-question-answer' ); ?></p>
	<?php endif; ?>

</div>
