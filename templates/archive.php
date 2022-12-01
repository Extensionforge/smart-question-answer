<?php
/**
 * Display question archive
 *
 * Template for rendering base of SmartQa.
 *
 * @link https://extensionforge.com
 * @since 4.1.0
 *
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php dynamic_sidebar( 'asqa-top' ); ?>

<div class="asqa-row">
	<div id="asqa-lists" class="<?php echo is_active_sidebar( 'asqa-sidebar' ) && is_smartqa() ? 'asqa-col-9' : 'asqa-col-12'; ?>">
		<?php asqa_get_template_part( 'question-list' ); ?>
	</div>

	<?php if ( is_active_sidebar( 'asqa-sidebar' ) && is_smartqa() ) { ?>
		<div class="asqa-question-right asqa-col-3">
			<?php dynamic_sidebar( 'asqa-sidebar' ); ?>
		</div>
	<?php } ?>

</div>
