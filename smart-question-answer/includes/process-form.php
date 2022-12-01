<?php
/**
 * SmartQa process form.
 *
 * @link     https://extensionforge.com
 * @since    1.0.0
 * @license  GPL 3+
 * @package  SmartQa
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Process SmartQa forms.
 *
 * @since unknown
 * @since 1.0.0 Fixed: CS bugs.
 */
class SmartQa_Process_Form {
	/**
	 * Results to send in ajax callback.
	 *
	 * @var array
	 */
	private $result;

	/**
	 * Link to redirect.
	 *
	 * @var string
	 */
	private $redirect;

	/**
	 * Initialize the class
	 */
	public function __construct() {
		add_action( 'wp_ajax_asqa_ajax', array( $this, 'asqa_ajax' ) );
		add_action( 'wp_ajax_nopriv_asqa_ajax', array( $this, 'asqa_ajax' ) );
	}

	/**
	 * For non ajax form.
	 *
	 * @return void
	 */
	public function non_ajax_form() {
		$form_action = asqa_isset_post_value( 'asqa_form_action' );
		$ajax_action = asqa_isset_post_value( 'asqa_ajax_action' );

		// return if asqa_form_action is not set, probably its not our form.
		if ( ! $form_action || $ajax_action ) {
			return;
		}

		$this->request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->process_form();

		if ( ! empty( $this->redirect ) ) {
			wp_redirect( $this->redirect ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}
	}

	/**
	 * Handle all smartqa ajax requests.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function asqa_ajax() {
		$ajax_action = asqa_isset_post_value( 'asqa_ajax_action' );
		$form_action = asqa_isset_post_value( 'asqa_form_action' );

		if ( ! $ajax_action ) {
			exit;
		}

		$this->request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $form_action ) {
			$this->is_ajax = true;
			$this->process_form();
			asqa_ajax_json( $this->result );
		} else {
			$action = asqa_sanitize_unslash( 'asqa_ajax_action', 'r' );

			/**
				* ACTION: asqa_ajax_[$action]
				* Action for processing Ajax requests
			 *
				* @since 1.0.0
				*/
			do_action( 'asqa_ajax_' . $action );
		}

		// If reached to this point then there is something wrong.
		asqa_ajax_json( 'something_wrong' );
	}


	/**
	 * Process form based on action value.
	 *
	 * @return void
	 * @since 1.0.0
	 * @deprecated 1.0.0
	 */
	public function process_form() {
		$form_action = asqa_isset_post_value( 'asqa_form_action' );
		$action      = sanitize_text_field( $form_action );

		/**
		 * ACTION: asqa_process_form_[action]
		 * process form
		 *
		 * @since 1.0.0
		 * @deprecated 1.0.0
		 */
		do_action( 'asqa_process_form_' . $action );
	}
}
