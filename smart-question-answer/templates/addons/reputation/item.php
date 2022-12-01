<?php
/**
 * Template for user reputation item.
 *
 * Render reputation item in authors page.
 *
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 * @link    https://extensionforge.com/
 * @since   4.0.0
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<tr class="asqa-reputation-item">
	<td class="col-icon"><i class="<?php $reputations->the_icon(); ?> <?php $reputations->the_event(); ?>"></i></td>
	<td class="col-event asqa-reputation-event">
		<div class="asqa-reputation-activity"><?php $reputations->the_activity(); ?></div>
		<?php $reputations->the_ref_content(); ?>
	</td>
	<td class="col-date asqa-reputation-date"><?php $reputations->the_date(); ?></td>
	<td class="col-points asqa-reputation-points"><span><?php $reputations->the_points(); ?></span></td>
</tr>

