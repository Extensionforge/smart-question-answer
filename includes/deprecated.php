<?php
/**
 * Contain list of function which are deprecated
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-3.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( '_deprecated_function' ) ) {
	require_once ABSPATH . WPINC . '/functions.php';
}

/**
 * Return hover card attributes.
 *
 * @param  mixed $_post Post ID, Object or null.
 * @return void
 *
 * @deprecated 4.1.13
 */
function asqa_get_hover_card_attr( $_post = null ) {
	_deprecated_function( __FUNCTION__, '4.1.13' );
}

/**
 * Echo hover card attributes.
 *
 * @param  mixed $_post Post ID, Object or null.
 * @deprecated 4.1.13
 */
function asqa_hover_card_attr( $_post = null ) {
	_deprecated_function( __FUNCTION__, '4.1.13' );
}
