<?php
/**
 * When visitor try to browse tag page without setting query_var then
 * this is show.
 *
 * @link http://extensionforge.com
 * @since 1.0
 * @package SmartQa
 * @subpackage Tags for SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="asqa-no-category-found asqa-404">
	<p class="asqa-notice asqa-yellow"><?php esc_attr_e( 'No tags is set!', 'smart-question-answer' ); ?></p>
</div>


