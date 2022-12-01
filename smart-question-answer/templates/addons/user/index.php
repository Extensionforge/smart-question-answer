<?php
/**
 * User profile template.
 * User profile index template.
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GNU Public License
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 *
 * @link       https://extensionforge.com
 * @since      4.0.0
 * @package    SmartQa
 * @subpackage Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id     = asqa_current_user_id();
$current_tab = asqa_sanitize_unslash( 'tab', 'r', 'questions' );
?>

<div id="asqa-user" class="asqa-user <?php echo is_active_sidebar( 'asqa-user' ) && is_smartqa() ? 'asqa-col-9' : 'asqa-col-12'; ?>">

	<?php if ( '0' == $user_id && ! is_user_logged_in() ) : ?>

		<h1><?php _e( 'Please login to view your profile', 'smart-question-answer' ); ?></h1>

	<?php else : ?>

		<div class="asqa-user-bio">
			<div class="asqa-user-avatar asqa-pull-left">
				<?php echo get_avatar( $user_id, 80 ); ?>
			</div>
			<div class="no-overflow">
				<div class="asqa-user-name">
					<?php
					echo asqa_user_display_name(
						[
							'user_id' => $user_id,
							'html'    => true,
						]
					);
?>
				</div>
				<div class="asqa-user-about">
					<?php echo get_user_meta( $user_id, 'description', true ); ?>
				</div>
			</div>
		</div>
		<?php self::user_menu(); ?>
		<?php self::sub_page_template(); ?>

	<?php endif; ?>

</div>

<?php if ( is_active_sidebar( 'asqa-user' ) && is_smartqa() ) : ?>
	<div class="asqa-question-right asqa-col-3">
		<?php dynamic_sidebar( 'asqa-user' ); ?>
	</div>
<?php endif; ?>
