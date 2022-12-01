<?php
/**
 * Reputation events.
 *
 * @link    https://extensionforge.com
 * @since   4.0
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;
$i = 1;
?>

<form id="reputation_events" method="POST">
	<table class="asqa-events">
		<tbody>
			<?php foreach ( (array) asqa_get_reputation_events() as $slug => $event ) { ?>
				<tr class="asqa-event">
					<td class="col-id"><span><?php echo esc_attr( $i ); ?></span></td>
					<td class="col-label"><?php echo esc_attr( $event['label'] ); ?></td>
					<td class="col-description"><?php echo esc_attr( $event['description'] ); ?></td>
					<td class="col-points"><input type="number" value="<?php echo esc_attr( $event['points'] ); ?>" name="events[<?php echo esc_attr( $slug ); ?>]"/></td>
				</tr>
				<?php $i++; ?>
			<?php } ?>
		</tbody>
	</table>
	<button class="button button-primary"><?php esc_attr_e( 'Save Events Points', 'smart-question-answer' ); ?></button>
	<input name="action" type="hidden" value="asqa_save_events" />
	<input name="__nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'asqa-save-events' ) ); ?>" />
</form>

<script type="text/javascript">
	jQuery(document).ready(function($){
		$('#reputation_events').on('submit', function(){
			$.ajax({
				url: ajaxurl,
				data: $(this).serialize(),
				success: function(data){
					if('' !== data){
						$('.postbox.events').before(data);
					}
				}
			})
			return false;
		});
	});
</script>
