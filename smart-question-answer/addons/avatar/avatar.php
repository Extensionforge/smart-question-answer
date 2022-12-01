<?php
/**
 * Dynamic addon avatar.
 *
 * An SmartQa add-on to check and filter bad words in
 * question, answer and comments. Add restricted words
 * after activating addon.
 *
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright  2014 extensionforge.com & Peter Mertzlin
 * @license    GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link       https://extensionforge.com
 * @package    SmartQa
 * @subpackage Dynamic Avatar Addon
 *
 * @smartqa-addon
 * Addon Name:    Dynamic Avatar
 * Addon URI:     https://extensionforge.com
 * Description:   Generate user avatar dynamically.
 * Author:        Peter Mertzlin
 * Author URI:    https://extensionforge.com
 */

namespace Smartqa\Addons;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once 'class-generator.php';

/**
 * SmartQa avatar hook class.
 *
 * @since 1.0.0
 */
class Avatar extends \SmartQa\Singleton {
	/**
	 * Refers to a single instance of this class.
	 *
	 * @var null|object
	 * @since 1.0.0
	 */
	public static $instance = null;

	/**
	 * Initialize the class.
	 *
	 * @since unknown
	 * @since 1.0.0 Added hook `asqa_settings_menu_features_groups`.
	 * @since 1.0.0 Renamed `asqa_form_addon-avatar` to `asqa_form_options_features_avatar`.
	 */
	protected function __construct() {
		asqa_add_default_options(
			array(
				'avatar_font'  => 'Pacifico',
				'avatar_force' => false,
			)
		);

		smartqa()->add_filter( 'asqa_settings_menu_features_groups', __CLASS__, 'add_to_settings_page' );
		smartqa()->add_action( 'asqa_form_options_features_avatar', __CLASS__, 'option_form' );
		smartqa()->add_filter( 'pre_get_avatar_data', __CLASS__, 'get_avatar', 1000, 3 );
		smartqa()->add_action( 'wp_ajax_asqa_clear_avatar_cache', __CLASS__, 'clear_avatar_cache' );
	}

	/**
	 * Add tags settings to features settings page.
	 *
	 * @param array $groups Features settings group.
	 * @return array
	 * @since 1.0.0
	 */
	public static function add_to_settings_page( $groups ) {
		$groups['avatar'] = array(
			'label' => __( 'Dynamic Avatar', 'smart-question-answer' ),
		);

		return $groups;
	}

	/**
	 * Register options of Avatar addon.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public static function option_form() {
		$opt = asqa_opt();

		ob_start();
		?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$('#asqa-clear-avatar').on('click', function(e){
						e.preventDefault();
						$.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							data: {
								action: 'asqa_clear_avatar_cache',
								__nonce: '<?php echo esc_attr( wp_create_nonce( 'clear_avatar_cache' ) ); ?>'
							},
							success: function(data){
								if(data==='success') alert('All avatar deleted');
							}
						});
					});
				});
			</script>
		<?php
		$js = ob_get_clean();

		$form = array(
			'submit_label' => __( 'Save add-on options', 'smart-question-answer' ),
			'fields'       => array(
				'clear_avatar_cache' => array(
					'label' => __( 'Clear Cache', 'smart-question-answer' ),
					'html'  => '<div class="asqa-form-fields-in"><a id="asqa-clear-avatar" href="#" class="button">' . __( 'Clear avatar cache', 'smart-question-answer' ) . '</a></div>' . $js,
				),
				'avatar_font'        => array(
					'label'   => __( 'Font family', 'smart-question-answer' ),
					'desc'    => __( 'Select font family for avatar letters.', 'smart-question-answer' ),
					'type'    => 'select',
					'options' => array(
						'calibri'         => 'Calibri',
						'Pacifico'        => 'Pacifico',
						'OpenSans'        => 'Open Sans',
						'Glegoo-Bold'     => 'Glegoo Bold',
						'DeliusSwashCaps' => 'Delius Swash Caps',
					),
					'value'   => $opt['avatar_font'],
				),
				'avatar_force'       => array(
					'label' => __( 'Force avatar', 'smart-question-answer' ),
					'desc'  => __( 'Show SmartQa avatars by default instead of gravatar fallback. Useful in localhost development.', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['avatar_force'],
				),
			),
		);

		return $form;
	}

	/**
	 * Override get_avatar.
	 *
	 * @param  string         $args         Avatar image.
	 * @param  integer|string $id_or_email  User id or email.
	 * @return string
	 */
	public static function get_avatar( $args, $id_or_email ) {
		$override = apply_filters( 'asqa_pre_avatar_url', false, $args, $id_or_email );

		// Return if override is not false.
		if ( false !== $override ) {
			return $override;
		}

		$args['default'] = asqa_generate_avatar( $id_or_email );

		// Set default avatar url.
		if ( asqa_opt( 'avatar_force' ) ) {
			$args['url'] = asqa_generate_avatar( $id_or_email );
		}

		return $args;
	}

	/**
	 * Ajax callback for clearing avatar cache.
	 */
	public static function clear_avatar_cache() {
		check_ajax_referer( 'clear_avatar_cache', '__nonce' );

		if ( current_user_can( 'manage_options' ) ) {
			WP_Filesystem();
			global $wp_filesystem;
			$upload_dir = wp_upload_dir();
			$wp_filesystem->rmdir( $upload_dir['basedir'] . '/asqa_avatars', true );
			wp_die( 'success' );
		}

		wp_die( 'failed' );
	}
}

/**
 * Check if avatar exists already.
 *
 * @param integer $user_id User ID or name.
 * @return boolean
 */
function asqa_is_avatar_exists( $user_id ) {
	$filename   = md5( $user_id );
	$upload_dir = wp_upload_dir();
	$avatar_dir = $upload_dir['basedir'] . '/asqa_avatars';

	return file_exists( $avatar_dir . $filename . '.jpg' );
}

/**
 * Generate avatar.
 *
 * @param integer|string $user_id User ID or name.
 * @return string Link to generated avatar.
 */
function asqa_generate_avatar( $user_id ) {
	$avatar = new Avatar\Generator( $user_id );
	$avatar->generate();

	return $avatar->fileurl();
}

// Init class.
Avatar::init();
