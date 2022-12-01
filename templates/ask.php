<?php
/**
 * Ask question page
 *
 * @link https://extensionforge.com
 * @since 0.1
 *
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="asqa-ask-page" class="clearfix">
	<?php if ( asqa_user_can_ask() ) : ?>
		<?php asqa_ask_form(); ?>
	<?php elseif ( is_user_logged_in() ) : ?>
		<div class="asqa-no-permission">
			<?php esc_attr_e( 'You do not have permission to ask a question.', 'smart-question-answer' ); ?>
		</div>
	<?php endif; ?>

	<?php asqa_get_template_part( 'login-signup' ); ?>
</div>
