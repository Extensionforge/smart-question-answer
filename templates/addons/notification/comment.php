<?php
/**
 * Template used to display comment item in notification.
 *
 * @link        http://extensionforge.com
 * @since       4.0
 * @package     SmartQa
 * @subpackage  Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="asqa-noti-item clearfix">
	<div class="asqa-noti-avatar"><?php $this->the_actor_avatar(); ?></div>
	<a class="asqa-noti-inner" href="<?php $this->the_permalink(); ?>">
		<strong class="asqa-not-actor"><?php $this->the_actor(); ?></strong> <?php $this->the_verb(); ?>
		<strong class="asqa-not-ref"><?php $this->the_ref_title(); ?></strong>
		<time class="asqa-noti-date"><?php $this->the_date(); ?></time>
	</a>
</div>
