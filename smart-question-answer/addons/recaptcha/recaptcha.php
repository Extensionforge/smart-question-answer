<?php
/**
 * An SmartQa add-on to check and filter bad words in
 * question, answer and comments. Add restricted words
 * after activating addon.
 *
 * @author     Peter Mertzlin <support@rahularyan.com>
 * @copyright  2014 extensionforge.com & Peter Mertzlin
 * @license    GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link       https://extensionforge.com
 * @package    SmartQa
 * @subpackage reCaptcha Addon
 */

namespace Smartqa\Addons;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use ReCaptcha\ReCaptcha;

/**
 * Include captcha field.
 */
require_once SMARTQA_ADDONS_DIR . '/recaptcha/recaptcha/class-captcha.php';

/**
 * The reCaptcha class.
 */
class Captcha extends \SmartQa\Singleton {
	/**
	 * Instance of this class.
	 *
	 * @var     object
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since 2.4.8 Removed `$ap` args.
	 */
	protected function __construct() {
		asqa_add_default_options(
			array(
				'recaptcha_method'        => 'post',
				'recaptcha_exclude_roles' => array( 'asqa_moderator' => 1 ),
			)
		);

		smartqa()->add_filter( 'asqa_settings_menu_features_groups', $this, 'add_to_settings_page' );
		smartqa()->add_action( 'asqa_form_options_features_recaptcha', $this, 'options' );
		smartqa()->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );
		smartqa()->add_action( 'asqa_question_form_fields', $this, 'asqa_question_form_fields', 10, 2 );
		smartqa()->add_action( 'asqa_answer_form_fields', $this, 'asqa_question_form_fields', 10, 2 );
		smartqa()->add_action( 'asqa_comment_form_fields', $this, 'asqa_question_form_fields', 10, 2 );
	}

	/**
	 * Enqueue script.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'asqa-recaptcha', SMARTQA_URL . 'addons/recaptcha/script.js', array(), ASQA_VERSION, true );
	}

	/**
	 * Add tags settings to features settings page.
	 *
	 * @param array $groups Features settings group.
	 * @return array
	 * @since 1.0.0
	 */
	public function add_to_settings_page( $groups ) {
		$groups['recaptcha'] = array(
			'label' => __( 'reCaptcha', 'smart-question-answer' ),
		);

		return $groups;
	}

	/**
	 * Register Categories options
	 */
	public function options() {
		global $wp_roles;
		$opt = asqa_opt();

		$roles = array();
		foreach ( $wp_roles->roles as $key => $role ) {
			$roles[ $key ] = $role['name'];
		}

		$form = array(
			'fields' => array(
				'recaptcha_site_key'      => array(
					'label' => __( 'Recaptcha site key', 'smart-question-answer' ),
					'desc'  => __( 'Enter your site key, if you dont have it get it from here https://www.google.com/recaptcha/admin', 'smart-question-answer' ),
					'value' => $opt['recaptcha_site_key'],
				),
				'recaptcha_secret_key'    => array(
					'label' => __( 'Recaptcha secret key', 'smart-question-answer' ),
					'desc'  => __( 'Enter your secret key', 'smart-question-answer' ),
					'value' => $opt['recaptcha_secret_key'],
				),
				'recaptcha_method'        => array(
					'label'   => __( 'Recaptcha Method', 'smart-question-answer' ),
					'desc'    => __( 'Select method to use when verification keeps failing', 'smart-question-answer' ),
					'type'    => 'select',
					'options' => array(
						'curl' => 'CURL',
						'post' => 'POST',
					),
					'value'   => $opt['recaptcha_method'],
				),
				'recaptcha_exclude_roles' => array(
					'label'   => __( 'Hide reCaptcha for roles', 'smart-question-answer' ),
					'desc'    => __( 'Select roles for which reCaptcha will be hidden.', 'smart-question-answer' ),
					'type'    => 'checkbox',
					'options' => $roles,
					'value'   => $opt['recaptcha_exclude_roles'],
				),
			),
		);

		return $form;
	}

	/**
	 * Add captcha field in question and answer form.
	 *
	 * @param array $form Form arguments.
	 * @return array
	 * @since 1.0.0
	 */
	public function asqa_question_form_fields( $form ) {
		if ( asqa_show_captcha_to_user() ) {
			$form['fields']['captcha'] = array(
				'label' => __( 'Prove that you are a human', 'smart-question-answer' ),
				'type'  => 'captcha',
				'order' => 100,
			);
		}

		return $form;
	}

}

// Initialize the class.
Captcha::init();
