<?php
/**
 * Tools page
 *
 * @link https://extensionforge.com
 * @since 2.0.0
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 * @since 1.0.0 Fixed: CS bugs.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wp_roles;
$asqa_roles = new ASQA_Roles();

$class = 'is-dismissible';

if (
	asqa_sanitize_unslash( 'role_name', 'p' ) &&
	asqa_verify_nonce( 'asqa_role_' . asqa_sanitize_unslash( 'role_name', 'p' ) . '_update' ) &&
	is_super_admin()
	) {
	$caps = asqa_sanitize_unslash( 'c', 'p' ) ? asqa_sanitize_unslash( 'c', 'p' ) : array();
	$caps = array_map( 'sanitize_text_field', $caps );

	asqa_update_caps_for_role( asqa_sanitize_unslash( 'role_name', 'p' ), $caps );
} elseif ( asqa_sanitize_unslash( 'new_role', 'p' ) && asqa_verify_nonce( 'asqa_new_role' ) ) {
	$role_name = asqa_sanitize_unslash( 'role_name', 'p' );
	$role_slug = sanitize_title_with_dashes( asqa_sanitize_unslash( 'role_slug', 'p' ) );

	if ( ! isset( $wp_roles->roles[ $role_slug ] ) ) {
		$role_caps = asqa_sanitize_unslash( 'role_caps', 'p' );
		$caps      = ( 'moderator_caps' === $role_caps ? asqa_role_caps( 'moderator' ) : asqa_role_caps( 'participant' ) );
		add_role( $role_slug, $role_name, $caps );

		// translators: %s contain role name.
		$message = sprintf( esc_attr__( 'New role %s added successfully .', 'smart-question-answer' ), $role_name );
		$class  .= ' notice notice-success';
	} else {
		// translators: %s contain role name.
		$message = sprintf( esc_attr__( 'Its look like %s role already exists .', 'smart-question-answer' ), $role_name );
		$class  .= ' notice notice-error';
	}
}


if ( ! empty( $message ) ) {
	echo wp_kses_post( sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ) );
}
?>

<div class="wrap">
	<div class="white-bg">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top">
						<label><?php esc_attr_e( 'Add new role', 'smart-question-answer' ); ?>:</label>
					</th>
					<td>
						<p class="description"><?php esc_attr_e( 'Add a new user role.', 'smart-question-answer' ); ?></p>
						<br />
						<form action="" method="POST">
							<input type="text" name="role_name" value="" placeholder="<?php esc_attr_e( 'Role name', 'smart-question-answer' ); ?>" class="regular-text">
							<input type="text" name="role_slug" value="" placeholder="<?php esc_attr_e( 'Role slug, without any space', 'smart-question-answer' ); ?>" class="regular-text">
							<br />
							<br />
							<label>
								<input type="radio" name="role_caps" value="participant_caps">
								<?php esc_attr_e( 'Basic Capabilities', 'smart-question-answer' ); ?>
							</label>

							<label>
								<input type="radio" name="role_caps" value="moderator_caps">
								<?php esc_attr_e( 'Moderator Capabilities', 'smart-question-answer' ); ?>
							</label>
							<br />
							<br />
							<?php wp_nonce_field( 'asqa_new_role', '__nonce' ); ?>
							<input name="new_role" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Add role', 'smart-question-answer' ); ?>" />
						</form>

					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">
						<label><?php esc_attr_e( 'SmartQa capabilities', 'smart-question-answer' ); ?>:</label>
						<p class="description"><?php esc_attr_e( 'Add SmartQa capabilities to 3rd party roles.', 'smart-question-answer' ); ?></p>
					</th>

					<td>
						<div class="asqa-tools-roles">
							<label for="asqa-tools-selectroles">
								<?php esc_attr_e( 'Select user role', 'smart-question-answer' ); ?>
							</label>

							<select id="asqa-tools-selectroles">
								<?php $selected = asqa_sanitize_unslash( 'role_name', 'request', 'administrator' ); ?>
								<?php foreach ( $wp_roles->roles as $key => $role ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
									<option value="role_<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>>
										<?php echo esc_attr( $role['name'] ); ?>
									</option>
								<?php } ?>
							</select>

							<?php foreach ( $wp_roles->roles as $key => $role ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
								<form id="role_<?php echo esc_attr( $key ); ?>" class="asqa-tools-roleitem" style="display:none" method="POST" action="">
									<strong class="asqa-tools-roletitle">
										<?php echo esc_attr( $role['name'] ); ?>
									</strong>
									<div class="asqa-tools-basecaps asqa-tools-ck">
										<strong><?php esc_attr_e( 'Basic Capabilities', 'smart-question-answer' ); ?><input type="checkbox" class="checkall" /></strong>

										<?php foreach ( $asqa_roles->base_caps as $cap => $val ) { ?>
											<label for="<?php echo esc_attr( $key . '_' . $cap ); ?>">
												<input id="<?php echo esc_attr( $key . '_' . $cap ); ?>" type="checkbox" name="c[<?php echo esc_attr( $cap ); ?>]" <?php echo isset( $role['capabilities'][ $cap ] ) && $role['capabilities'][ $cap ] ? ' checked="checked"' : ''; ?> />
												<?php echo esc_attr( $cap ); ?>
											</label>
										<?php } ?>
									</div>
									<div class="asqa-tools-modcaps asqa-tools-ck">
										<strong><?php esc_attr_e( 'Moderator Capabilities', 'smart-question-answer' ); ?><input type="checkbox" class="checkall" /></strong>

										<?php foreach ( $asqa_roles->mod_caps as $cap => $val ) { ?>
											<label for="<?php echo esc_attr( $key . '_' . $cap ); ?>">
												<input id="<?php echo esc_attr( $key . '_' . $cap ); ?>" type="checkbox" name="c[<?php echo esc_attr( $cap ); ?>]" <?php echo ( isset( $role['capabilities'][ $cap ] ) && $role['capabilities'][ $cap ] ? ' checked="checked"' : '' ); ?> />
												<?php echo esc_attr( $cap ); ?>
											</label>
										<?php } ?>

									</div>

									<input type="hidden" name="asqa_admin_form" value="role_update" />
									<input type="hidden" name="role_name" value="<?php echo esc_attr( $key ); ?>" />
									<?php wp_nonce_field( 'asqa_role_' . $key . '_update', '__nonce' ); ?>
									<input id="save-options" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save Role', 'smart-question-answer' ); ?>" name="save">
								</form>
							<?php } ?>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

