<?php
/**
 * Email templates.
 *
 * @link       https://extensionforge.com
 * @since      4.0.1
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package    SmartQa
 * @subpackage Admin Views
 * @since 1.0.0 Fixed: CS bugs.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;
$i = 1;
?>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row" valign="top">
				<label><?php esc_attr_e( 'More options', 'smart-question-answer' ); ?>:</label>
			</th>
			<td>
				<p>
					<?php esc_attr_e( 'More email options can be found in addon options', 'smart-question-answer' ); ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=smartqa_addons&active_addon=free%2Femail.php' ) ); ?>">
						<?php esc_attr_e( 'More email options', 'smart-question-answer' ); ?>
					</a>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">
				<label><?php esc_attr_e( 'Select Template', 'smart-question-answer' ); ?>:</label>
			</th>
			<td>
				<?php
					$active    = asqa_isset_post_value( 'active_template', 'new_question' );
					$templates = array(
						'new_question'  => __( 'New Question', 'smart-question-answer' ),
						'new_answer'    => __( 'New Answer', 'smart-question-answer' ),
						'new_comment'   => __( 'New Comment', 'smart-question-answer' ),
						'edit_question' => __( 'Edit Question', 'smart-question-answer' ),
						'edit_answer'   => __( 'Edit Answer', 'smart-question-answer' ),
					);
					?>

				<select id="select-templates" name="email_templates">
					<?php foreach ( $templates as $template => $label ) : ?>
						<option value="<?php echo esc_attr( $template ); ?>" <?php selected( $template, $active ); ?>><?php echo esc_attr( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<p><?php esc_attr_e( 'The template selected here will appear below.', 'smart-question-answer' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">
				<label><?php esc_attr_e( 'Edit Template', 'smart-question-answer' ); ?>:</label>
			</th>
			<td>
				<div id="template-holder">
					<?php SmartQa\Addons\Email::init()->template_form( $active ); ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<script type="text/javascript">
	jQuery(document).ready(function($){
		$('#select-templates').on('change', function(){
			var self = this;
			SmartQa.showLoading(self);

			SmartQa.ajax({
				data: {
					action: 'asqa_email_template',
					__nonce: '<?php echo esc_attr( wp_create_nonce( 'asqa_email_template' ) ); ?>',
					template: $(self).val()
				},
				success: function(data){
					tinymce.execCommand('mceRemoveEditor',true, 'form_email_template-body');
					SmartQa.hideLoading(self);
					$('#template-holder').html(data);
					tinymce.execCommand('mceAddEditor',true, 'form_email_template-body');
				}
			});
		});
	});
</script>
<style>
	.asqa-email-allowed-tags pre{
	display: inline;
	background: #eee;
	margin-right: 15px;
	}
</style>
