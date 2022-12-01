<?php
/**
 * Display login signup form
 *
 * @package SmartQa
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( ! is_user_logged_in() ) : ?>
	<div class="asqa-login">
		<?php
			// Load WSL buttons if available.
			do_action( 'wordpress_social_login' );
		?>

		<div class="asqa-login-buttons">
			<a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_attr_e( 'Register', 'smart-question-answer' ); ?></a>
			<span class="asqa-login-sep"><?php esc_attr_e( 'or', 'smart-question-answer' ); ?></span>
			<a href="<?php echo esc_url( wp_login_url( get_the_permalink() ) ); ?>"><?php esc_attr_e( 'Login', 'smart-question-answer' ); ?></a>
		</div>
	</div>

<?php endif; ?>
