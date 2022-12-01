<?php
/**
 * SmartQa tinymce translations.
 *
 * @package   SmartQa
 * @license   GPL-3.0+
 * @link      https://extensionforge.com
 * @since     4.1.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '_WP_Editors' ) ) {
	require ABSPATH . WPINC . '/class-wp-editor.php';
}

/**
 * Tinymce translations.
 *
 * @return array
 * @since 4.1.5
 */
function asqa_tinymce_translations() {
	$strings = array(
		'i18n_insert_image'         => __( 'Insert image', 'smart-question-answer' ),
		'i18n_insert_media'         => __( 'Insert Media (SmartQa)', 'smart-question-answer' ),
		'i18n_close'                => __( 'Close', 'smart-question-answer' ),
		'i18n_select_file'          => __( 'Select File', 'smart-question-answer' ),
		'i18n_browse_from_computer' => __( 'Browse from computer', 'smart-question-answer' ),
		'i18n_image_title'          => __( 'Image title', 'smart-question-answer' ),
		'i18n_media_preview'        => __( 'Media preview', 'smart-question-answer' ),
		'i18n_insert_code'          => __( 'Insert code', 'smart-question-answer' ),
		'i18n_insert_codes'         => __( 'Insert codes (SmartQa)', 'smart-question-answer' ),
		'i18n_insert'               => __( 'Insert', 'smart-question-answer' ),
		'i18n_inline'               => __( 'Inline?', 'smart-question-answer' ),
		'i18n_insert_your_code'     => __( 'Insert your code.', 'smart-question-answer' ),
	);

	$locale     = _WP_Editors::$mce_locale;
	$translated = 'tinyMCE.addI18n("' . $locale . '.smartqa", ' . wp_json_encode( $strings ) . ");\n";

	return $translated;
}

$strings = asqa_tinymce_translations();
