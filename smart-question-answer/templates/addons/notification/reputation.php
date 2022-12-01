<?php
/**
 * Notification reputation type template.
 *
 * Render notification item if ref_type is reputation.
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
<div class="asqa-noti-item clearfix">
	<div class="asqa-noti-rep"><?php $this->the_reputation_points(); ?></div>
	<a class="asqa-noti-inner" href="<?php $this->the_permalink(); ?>">
		<?php $this->the_verb(); ?>
		<time class="asqa-noti-date"><?php $this->the_date(); ?></time>
	</a>
</div>
