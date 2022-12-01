<?php
/**
 * SmartQa admin features page.
 *
 * @link       https://extensionforge.com
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package    SmartQa
 * @subpackage Admin Views
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$form_name = asqa_sanitize_unslash( 'asqa_form_name', 'r' );

/**
 * Internal function for sorting features array.
 *
 * @param array $a Feature array.
 * @return int
 * @since 1.0.0
 */
function _asqa_short_addons_list( $a ) { // phpcs:ignore
	return $a['active'] ? 0 : 1;
}
?>


<div class="asqa-addons">
	<div class="asqa-addons-list">
		<?php
		/**
		 * Action hook called before SmartQa addons list in wp-admin addons page.
		 *
		 * @since 1.0.0
		 */
		do_action( 'asqa_before_addons_list' );

		$i              = 0;
		$addons         = asqa_get_addons();
		$active_addons  = count( wp_list_filter( $addons, array( 'active' => true ) ) );
		$first_disabled = '';

		usort( $addons, '_asqa_short_addons_list' );

		foreach ( (array) $addons as $file => $data ) {
			if ( $active_addons > 0 && empty( $first_disabled ) && ! $data['active'] ) {
				$first_disabled = $file;
			}
			?>

			<?php if ( $file === $first_disabled ) : ?>
				<div class="asqa-addon-sep"></div>
			<?php endif; ?>

			<div class="asqa-addon<?php echo $data['active'] ? ' active' : ''; ?> <?php echo esc_attr( $data['class'] ); ?>">
				<div class="asqa-addon-image">
					<?php $image = asqa_get_addon_image( $data['id'] ); ?>

					<?php if ( $image ) : ?>
						<img src="<?php echo esc_url( $image ); ?>" />
					<?php endif; ?>
				</div>
				<div class="asqa-addon-detail">
					<h4>
						<?php echo esc_attr( $data['name'] ); ?>

						<div class="asqa-addon-tags">
							<?php if ( $data['active'] ) : ?>
								<span class="asqa-addon-status"><?php esc_attr_e( 'Active', 'smart-question-answer' ); ?> </span>
							<?php endif; ?>
							<?php echo $data['pro'] ? '<span class="asqa-addon-pro">PRO</span>' : ''; ?>
						</div>
					</h4>
					<p><?php echo esc_html( $data['description'] ); ?></p>

					<?php
						$args = wp_json_encode(
							array(
								'action'   => 'asqa_toggle_addon',
								'__nonce'  => wp_create_nonce( 'toggle_addon' ),
								'addon_id' => $data['id'],
							)
						);
					?>

					<?php if ( $data['active'] ) : ?>
						<button class="button button-small button-primary asqa-addon-toggle" apajaxbtn apquery="<?php echo esc_js( $args ); ?>"><?php esc_attr_e( 'Disable', 'smart-question-answer' ); ?></button>
					<?php else : ?>
						<button class="button button-small button-primary asqa-addon-toggle" apajaxbtn apquery="<?php echo esc_js( $args ); ?>"><?php esc_attr_e( 'Enable', 'smart-question-answer' ); ?></button>
					<?php endif; ?>
				</div>
			</div>

			<?php
			$i++;
		}

		/**
		 * Action hook called after SmartQa addons list in wp-admin addons page.
		 *
		 * @since 1.0.0
		 */
		do_action( 'asqa_after_addons_list' );

		?>
	</div>
</div>

<script type="text/javascript">
	(function($){
		SmartQa.on('toggleAddon', function(data){
			window.location.reload();
		})
	})(jQuery)
</script>

