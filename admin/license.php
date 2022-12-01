<?php
/**
 * SmartQa product license
 * Handle licence of SmartQa products.
 *
 * @link https://extensionforge.com
 * @since 2.4.5
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load updater.
require_once dirname( __FILE__ ) . '/updater.php';


/**
 * SmartQa license
 *
 * @ignore
 */
class ASQA_License {

	/**
	 * Initialize class.
	 */
	public function __construct() {
		add_action( 'asqa_admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'asqa_plugin_updater' ), 0 );
	}

	/**
	 * Show license menu if license field is registered.
	 */
	public function menu() {
		$fields = asqa_product_license_fields();
		if ( ! empty( $fields ) ) {
			$count = ' <span class="update-plugins count smartqa-license-count"><span class="plugin-count">' . number_format_i18n( count( $fields ) ) . '</span></span>';
			add_submenu_page( 'smartqa', __( 'Licenses', 'smart-question-answer' ), __( 'Licenses', 'smart-question-answer' ) . $count, 'manage_options', 'smartqa_licenses', array( $this, 'display_plugin_licenses' ) );
		}
	}

	/**
	 * Display license page.
	 */
	public function display_plugin_licenses() {
		include_once 'views/licenses.php';
	}

	/**
	 * SmartQa license form.
	 */
	public static function asqa_product_license() {
		if ( ! current_user_can( 'manage_options' ) || ! asqa_verify_nonce( 'asqa_licenses_nonce' ) ) {
			return;
		}

		$licenses = get_option( 'smartqa_license', array() );
		$fields   = asqa_product_license_fields();

		if ( empty( $fields ) ) {
			return;
		}

		if ( asqa_isset_post_value( 'save_licenses' ) ) {
			foreach ( (array) $fields as $slug => $prod ) {
				$prod_license = asqa_isset_post_value( 'asqa_license_' . $slug, '' );
				if ( ! empty( $prod_license ) && ! isset( $licenses[ $slug ] ) || $prod_license !== $licenses[ $slug ]['key'] ) {
					$licenses[ $slug ] = array(
						'key'    => trim( asqa_sanitize_unslash( 'asqa_license_' . $slug, 'g', '' ) ),
						'status' => false,
					);

					update_option( 'smartqa_license', $licenses );
				}
			}
		}

		foreach ( (array) $fields as $slug => $prod ) {

			// Data to send in our API request.
			$api_params = array(
				'license'      => $licenses[ $slug ]['key'],
				'item_name'    => rawurlencode( $prod['name'] ),
				'url'          => home_url(),
				'smartqa_ver' => ASQA_VERSION,
			);

			// Check if activate is clicked.
			if ( asqa_isset_post_value( 'asqa_license_activate_' . $slug ) ) {
				$api_params['edd_action'] = 'activate_license';

				// Call the custom API.
				$response = wp_remote_post(
					'https://extensionforge.com',
					array(
						'timeout'   => 15,
						'sslverify' => true,
						'body'      => $api_params,
					)
				);

				// Make sure the response came back okay.
				if ( ! is_wp_error( $response ) ) {
					// Decode the license data.
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );

					$licenses[ $slug ]['status'] = sanitize_text_field( $license_data->license );
					update_option( 'smartqa_license', $licenses );
				}
			}

			// Check if deactivate is clicked.
			if ( asqa_isset_post_value( 'asqa_license_deactivate_' . $slug ) ) {
				$api_params['edd_action'] = 'deactivate_license';

				// Call the custom API.
				$response = wp_remote_post(
					'https://extensionforge.com',
					array(
						'timeout'   => 15,
						'sslverify' => true,
						'body'      => $api_params,
					)
				);

				// Make sure the response came back okay.
				if ( ! is_wp_error( $response ) ) {
					// Decode the license data.
					$license_data                = json_decode( wp_remote_retrieve_body( $response ) );
					$licenses[ $slug ]['status'] = sanitize_text_field( $license_data->license );
					update_option( 'smartqa_license', $licenses );
				}
			}
		}
	}

	/**
	 * Initiate product updater.
	 */
	public function asqa_plugin_updater() {
		$fields   = asqa_product_license_fields();
		$licenses = get_option( 'smartqa_license', array() );

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $slug => $prod ) {
				if ( isset( $licenses[ $slug ] ) && ! empty( $licenses[ $slug ]['key'] ) ) {
					new SmartQa_Prod_Updater(
						$prod['file'],
						array(
							'version'   => ! empty( $prod['version'] ) ? $prod['version'] : '',
							'license'   => $licenses[ $slug ]['key'],
							'item_name' => ! empty( $prod['name'] ) ? $prod['name'] : '',
							'author'    => ! empty( $prod['author'] ) ? $prod['author'] : '',
							'slug'      => $slug,
						),
						isset( $prod['is_plugin'] ) ? $prod['is_plugin'] : true
					);
				}
			}
		}
	}

}

/**
 * SmartQa product licenses.
 *
 * @return array
 * @since 2.4.5
 */
function asqa_product_license_fields() {
	return apply_filters( 'smartqa_license_fields', array() );
}

