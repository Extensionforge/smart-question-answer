<?php
/**
 * Answer form template.
 *
 * @link https://extensionforge.com
 * @since unknown
 * @license GPL3+
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ajax_query = wp_json_encode(
	array(
		'asqa_ajax_action' => 'load_tinymce',
		'question_id'    => get_question_id(),
	)
);
?>

<?php if ( asqa_user_can_answer( get_question_id() ) ) : ?>
	<div id="answer-form-c" class="asqa-minimal-editor">
		<div class="asqa-avatar asqa-pull-left">
			<a href="<?php echo esc_url( asqa_user_link( get_current_user_id() ) ); ?>">
				<?php echo get_avatar( get_current_user_id(), asqa_opt( 'avatar_size_qquestion' ) ); ?>
			</a>
		</div>
		<div id="asqa-drop-area" class="asqa-cell asqa-form-c clearfix">
			<div class="asqa-cell-inner">
				<div class="asqa-minimal-placeholder">
					<div class="asqa-dummy-editor"></div>
					<div class="asqa-dummy-placeholder"><?php esc_attr_e( 'Write your answer.', 'smart-question-answer' ); ?></div>
					<div class="asqa-editor-fade" ap="loadEditor" data-apquery="<?php echo esc_js( $ajax_query ); ?>"></div>
				</div>
				<div id="asqa-form-main">
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php asqa_get_template_part( 'login-signup' ); ?>
