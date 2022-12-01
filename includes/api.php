<?php
/**
 * Api helper.
 *
 * @todo This file require doc comment.
 * @since unknown
 * @package SmartQa
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SmartQa REST endpoint class.
 *
 * @since unknown
 */
class SmartQa_API {
	/**
	 * Register REST route.
	 */
	public static function register() {
		register_rest_route(
			'smartqa',
			'/user/avatar',
			array(
				'methods'  => 'GET',
				'callback' => array( 'SmartQa_API', 'avatar' ),
			)
		);
	}

	/**
	 * Callback for route `/smartqa/user/avatar/`.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function avatar( $request ) {
		$args = $request->get_query_params();
		if ( isset( $args['id'] ) ) {
			$size   = isset( $args['size'] ) ? (int) $args['size'] : 90;
			$avatar = get_avatar_url( (int) $args['id'], $size );
			return new WP_REST_Response( $avatar, 200 );
		}
		return new WP_Error( 'wrongData', __( 'Wrong data supplied', 'smart-question-answer' ) );
	}
}
