<?php
/**
 * Base class for singleton.
 *
 * @author     Peter Mertzlin <support@rahularyan.com>
 * @copyright  2014 extensionforge.com & Peter Mertzlin
 * @license    GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link       https://extensionforge.com
 * @package    SmartQa
 * @since      1.0.0
 */

namespace SmartQa;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * A class to be used as a base for all singleton classes.
 *
 * @since 1.0.0
 */
abstract class Singleton {

	/**
	 * Cloning is forbidden.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', 'smart-question-answer' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 * @since 1.0.0 Fixed: warning `__wakeup must be public`.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', 'smart-question-answer' ), '1.0.0' );
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return SmartQa\Singleton A single instance of this class.
	 * @since 1.0.0
	 */
	public static function init() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
			static::$instance->run_once();
		}

		return static::$instance;
	}

	/**
	 * Placeholder function which is called only once.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function run_once() {
	}
}
