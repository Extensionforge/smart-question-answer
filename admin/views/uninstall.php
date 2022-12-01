<?php
/**
 * Tools page
 *
 * @link    https://extensionforge.com
 * @since   4.0
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 * @since   1.0.0 Fixed: CS errors.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
global $wpdb;
?>

<div class="wrap">
	<div class="asqa-uninstall-warning">
		<?php esc_attr_e( 'If you are unsure about this section please do not use any of these options below.', 'smart-question-answer' ); ?>
	</div>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top">
					<label><?php esc_attr_e( 'Permanently delete all questions and answers?', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<?php
						$total_qa = $wpdb->get_var( "SELECT count(*) FROM $wpdb->posts WHERE post_type='question' OR post_type='answer'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					?>
					<a href="#" class="button asqa-uninstall-btn" data-id="qa" data-total="<?php echo esc_attr( $total_qa ); ?>">
						<?php
							// translators: %d is total numbers of question and answer.
							echo esc_attr( sprintf( __( 'Delete %d Q&A', 'smart-question-answer' ), $total_qa ) );
						?>
					</a>
					<p class="description"><?php esc_attr_e( 'Clicking this button will delete all questions and answers data from database', 'smart-question-answer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label><?php esc_attr_e( 'Permanently delete all answers?', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<?php
						$total_answers = $wpdb->get_var( "SELECT count(*) FROM $wpdb->posts WHERE post_type='answer'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					?>
					<a href="#" class="button asqa-uninstall-btn" data-id="answers" data-total="<?php echo esc_attr( $total_answers ); ?>">
						<?php
							// translators: %d is total numbers of answers.
							echo esc_attr( sprintf( __( 'Delete %d answers', 'smart-question-answer' ), $total_answers ) );
						?>
					</a>
					<p class="description"><?php esc_attr_e( 'Clicking this button will delete all answers and its related data from database', 'smart-question-answer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label><?php esc_attr_e( 'Permanently delete all SmartQa user data?', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<a href="#" class="button asqa-uninstall-btn" data-id="userdata" data-total="1"><?php esc_attr_e( 'Delete all user data', 'smart-question-answer' ); ?></a>
					<p class="description"><?php esc_attr_e( 'Clicking this button will delete all user data added by SmartQa', 'smart-question-answer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label><?php esc_attr_e( 'Permanently delete all SmartQa options?', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<a href="#" class="button asqa-uninstall-btn" data-id="options" data-total="1"><?php esc_attr_e( 'Delete all options', 'smart-question-answer' ); ?></a>
					<p class="description"><?php esc_attr_e( 'Clicking this button will delete all SmartQa options', 'smart-question-answer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label><?php esc_attr_e( 'Permanently delete all SmartQa terms?', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<a href="#" class="button asqa-uninstall-btn" data-id="terms" data-total="1"><?php esc_attr_e( 'Delete all terms', 'smart-question-answer' ); ?></a>
					<p class="description"><?php esc_attr_e( 'Clicking this button will delete all SmartQa terms data', 'smart-question-answer' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label><?php esc_attr_e( 'Permanently delete all SmartQa tables?', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<a href="#" class="button asqa-uninstall-btn" data-id="tables" data-total="1"><?php esc_attr_e( 'Delete all database tables', 'smart-question-answer' ); ?></a>
					<p class="description"><?php esc_attr_e( 'Clicking this button will remove all SmartQa DB tables', 'smart-question-answer' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	function asqa_ajax_uninstall_data(el, done){
		done = done||0;
		var action = jQuery(el).attr('data-id');
		var total = jQuery(el).attr('data-total');
		var __nonce = '<?php echo esc_attr( wp_create_nonce( 'asqa_uninstall_data' ) ); ?>';

		jQuery.ajax({
			url: ajaxurl,
			method: 'POST',
			data: { __nonce: __nonce, action: 'asqa_uninstall_data', data_type: action },
			success: function(data){
				if(data.done > 0){
					done = done + data.done;
					jQuery(el).attr('data-total', data.total);
					jQuery(el).next().find('span').animate({width: (done/data.total)*100 + '%'}, 300);
					asqa_ajax_uninstall_data(el, done);
				}

			}
		});
	}
	jQuery(document).ready(function($){
		$('.asqa-uninstall-btn').on('click',function(e){
			e.preventDefault();
			if (confirm('<?php esc_attr_e( 'Do you wish to proceed? This cannot be undone.', 'smart-question-answer' ); ?>') == true) {
				asqa_ajax_uninstall_data(this);

				if(!$(this).next().is('.asqa-progress'))
					$(this).after('<div class="asqa-progress"><span></span></div>');
			}
		})
	});
</script>

