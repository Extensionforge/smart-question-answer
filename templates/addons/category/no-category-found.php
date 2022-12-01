<?php
/**
 * When visitor try to browse category page without setting query_var then
 * this is show.
 *
 * @link http://extensionforge.com
 * @since 4.0
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="asqa-no-category-found asqa-404">
	<p class="asqa-notice asqa-yellow"><?php esc_attr_e( 'No category is set!', 'smart-question-answer' ); ?></p>
</div>
