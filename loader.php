<?php
/**
 * SmartQa class auto loader.
 *
 * @link         https://extensionforge.com/smartqa
 * @since        1.0.0
 * @author       Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package      SmartQaPro
 */

namespace SmartQa;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Callback function for auto loading class on demand.
 *
 * @param string $class Name of class.
 * @return boolean True if files is included.
 * @since 4.1.8
 */
function autoloader( $class ) {
	if ( false === strpos( $class, 'SmartQa\\' ) ) {
		return;
	}

	// Replace SmartQa\Pro\ and change to lowercase to fix WPCS warning.
	$class    = strtolower( str_replace( 'SmartQa\\', '', $class ) );
	$filename = SMARTQA_DIR . str_replace( '_', '-', str_replace( '\\', '/', $class ) ) . '.php';

	// Check if file exists before including.
	if ( file_exists( $filename ) ) {
		require_once $filename;

		// Check class exists.
		if ( class_exists( $class ) ) {
			return true;
		}
	}

	return false;
}

spl_autoload_register( __NAMESPACE__ . '\\autoloader' );
