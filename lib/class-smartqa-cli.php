<?php
/**
 * This file holds all custom wp cli commands of SmartQa.
 *
 * @since 4.0.5
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements custom wp-cli commands.
 */
class SmartQa_Cli extends WP_CLI_Command {

	/**
	 * Prints current version of SmartQa.
	 *
	 * ## EXAMPLES
	 *
	 *     wp smartqa version
	 *
	 * @when after_wp_load
	 */
	public function version() {
		WP_CLI::success( 'Currently installed version of SmartQa is ' . ASQA_VERSION );
	}

	/**
	 * Upgrade SmartQa 3.x data to 4.x.
	 *
	 * Warning! This will delete/edit/rename lots of mysql rows and
	 * tables, so make sure to do a full backup before running this command.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Passing this will not ask for backup confirm.
	 *
	 * ## EXAMPLES
	 *
	 *     wp smartqa upgrade --yes
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Arguments.
	 * @param mixed $assoc_args Options.
	 */
	public function upgrade( $args, $assoc_args ) {
		print( "=== Starting upgrade process ===\n\r" );

		// Confirm before proceeding.
		WP_CLI::confirm( 'Make sure you had backed up your website before starting upgrade process. Do you wish to proceed further?', $assoc_args );

		SmartQa_Upgrader::get_instance();

		print( "\n\r=== Upgrade process completed ===\n\r" );
	}

	/**
	 * Activate an addon.
	 *
	 * ## OPTIONS
	 *
	 * <addon>
	 * : Addon file name to activate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp smartqa activate_addon free/avatar.php
	 *
	 * @when after_wp_load
	 * @param array $args       Arguments.
	 * @param mixed $assoc_args Options.
	 */
	public function activate_addon( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			return WP_CLI::error( __( 'You must pass a addon name i.e. free/avatar.php', 'smart-question-answer' ) );
		}

		$addon_name = $args[0];
		$ret        = asqa_activate_addon( $args[0] );

		if ( false === $ret ) {
			return WP_CLI::error( __( 'Unable to activate addon. May be its already active or wrong name passed.', 'smart-question-answer' ) );
		}

		WP_CLI::success( __( 'Successfully enabled addon', 'smart-question-answer' ) );
	}
}
