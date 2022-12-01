<?php
/**
 * View licenses page for SmartQa.
 *
 * @package SmartQa
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright 2014 - Peter Mertzlin
 */

// Save license key if form is submitted.
ASQA_License::asqa_product_license();

$fields   = asqa_product_license_fields();
$licenses = get_option( 'smartqa_license' );
?>

<div class="wrap">
	<h2>
		<?php esc_html_e( 'Licenses', 'smart-question-answer' ); ?>
	</h2>
	<p class="lead"><?php esc_attr_e( 'License keys for SmartQa products, i.e. extensions and themes.', 'smart-question-answer' ); ?></p>

	<?php if ( ! empty( $fields ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=smartqa_licenses' ) ); ?>">
			<table class="form-table">
				<tbody>
				<?php foreach ( $fields as $slug => $prod ) : ?>
					<?php
						$label = sprintf(
							// translators: Placeholder contains name of product.
							__( 'Enter license key for %s', 'smart-question-answer' ),
							$prod['name']
						);

						$key = ! empty( $licenses[ $slug ] ) && ! empty( $licenses[ $slug ]['key'] ) ? $licenses[ $slug ]['key'] : '';
					?>
					<tr valign="top">
						<th scope="row" valign="top"><?php echo esc_html( $prod['name'] ); ?></th>
						<td>
							<input id="asqa_license_<?php echo esc_attr( $slug ); ?>" name="asqa_license_<?php echo esc_attr( $slug ); ?>" type="text" class="regular-text" value="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $label ); ?>" />

							<?php if ( ! empty( $key ) ) { ?>
								<?php if ( false !== $licenses[ $slug ]['status'] && 'valid' === $licenses[ $slug ]['status'] ) { ?>
									<span class="asqa-license-check"><i class="apicon-check"></i><?php esc_attr_e( 'active', 'smart-question-answer' ); ?></span><br />
									<input type="submit" class="button-secondary" name="asqa_license_deactivate_<?php echo esc_attr( $slug ); ?>" value="<?php esc_attr_e( 'Deactivate License', 'smart-question-answer' ); ?>"/>
								<?php } else { ?>
									<input type="submit" class="button-secondary" name="asqa_license_activate_<?php echo esc_attr( $slug ); ?>" value="<?php esc_attr_e( 'Activate License', 'smart-question-answer' ); ?>"/>
								<?php } ?>
							<?php } ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<input type="hidden" name="action" value="asqa_product_license">
			<input type="submit" name="save_licenses" class="button button-primary" value="<?php esc_attr_e( 'Save', 'smart-question-answer' ); ?>" />
			<?php wp_nonce_field( 'asqa_licenses_nonce', '__nonce' ); ?>
		</form>
	<?php else : ?>
		<?php esc_attr_e( 'No license yet.', 'smart-question-answer' ); ?>
	<?php endif; ?>
</div>
