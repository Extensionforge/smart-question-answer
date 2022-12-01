<?php
/**
 * Template for user notification loop.
 *
 * Render notifications in user's page.
 *
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 * @link    https://extensionforge.com/
 * @since   1.0.0
 * @package SmartQa-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_query_var( 'asqa_user_id' );
?>

<?php if ( asqa_count_unseen_notifications() > 0 ) : ?>
	<?php
		$btn_args = wp_json_encode(
			array(
				'asqa_ajax_action' => 'mark_notifications_seen',
				'__nonce'        => wp_create_nonce( 'mark_notifications_seen' ),
			)
		);
	?>
	<a href="#" class="asqa-btn asqa-btn-markall-read asqa-btn-small" apajaxbtn apquery="<?php echo esc_js( $btn_args ); ?>">
		<?php _e( 'Mark all as seen', 'smart-question-answer' ); // xss okay. ?>
	</a>
<?php endif; ?>

<div class="asqa-noti-sub">
	<a href="<?php echo asqa_user_link( $user_id, 'notifications' ); ?>?seen=all"><?php _e( 'All', 'smart-question-answer' ); ?></a>
	<a href="<?php echo asqa_user_link( $user_id, 'notifications' ); ?>?seen=0"><?php _e( 'Unseen', 'smart-question-answer' ); ?></a>
	<a href="<?php echo asqa_user_link( $user_id, 'notifications' ); ?>?seen=1"><?php _e( 'Seen', 'smart-question-answer' ); ?></a>
</div>

<?php if ( $notifications->have() ) : ?>
	<div class="asqa-noti">
		<?php
		while ( $notifications->have() ) :
			$notifications->the_notification();
?>
			<?php $notifications->item_template(); ?>
		<?php endwhile; ?>
	</div>
<?php else : ?>
	<h3><?php _e( 'No notification', 'smart-question-answer' ); // xss ok. ?></h3>
<?php endif; ?>


<?php if ( $notifications->total_pages > 1 ) : ?>
	<a href="#" asqa-loadmore="
	<?php
	echo esc_js(
		wp_json_encode(
			array(
				'asqa_ajax_action' => 'load_more_notifications',
				'__nonce'        => wp_create_nonce( 'load_more_notifications' ),
				'current'        => 1,
				'user_id'        => $notifications->args['user_id'],
			)
		)
	);
?>
" class="asqa-loadmore asqa-btn" ><?php esc_attr_e( 'Load More', 'smart-question-answer' ); ?></a>
<?php endif; ?>
