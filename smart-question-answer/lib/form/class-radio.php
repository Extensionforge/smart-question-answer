<?php
/**
 * SmartQa Radio type field object.
 *
 * @package    SmartQa
 * @subpackage Fields
 * @since      4.1.0
 * @author     Peter Mertzlin<support@extensionforge.com>
 * @copyright  Copyright (c) 2017, Peter Mertzlin
 * @license    http://opensource.org/licenses/gpl-3.0.php GNU Public License
 */

namespace SmartQa\Form\Field;

use SmartQa\Form\Field as Field;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Radio type field object.
 *
 * @since 4.1.0
 */
class Radio extends Field {
	/**
	 * The field type.
	 *
	 * @var string
	 */
	public $type = 'radio';

	/**
	 * Prepare field.
	 *
	 * @return void
	 */
	protected function prepare() {
		$this->args = wp_parse_args(
			$this->args,
			array(
				'label' => __( 'SmartQa Radio Field', 'smart-question-answer' ),
			)
		);

		// Call parent prepare().
		parent::prepare();

		// Make sure checkbox value are sanitized.
		$this->sanitize_cb = array_merge( array( 'text_field' ), $this->sanitize_cb );
	}

	/**
	 * Field markup.
	 *
	 * @return void
	 */
	public function field_markup() {
		parent::field_markup();

		$value = $this->value();

		if ( $this->get( 'options' ) ) {
			foreach ( $this->get( 'options' ) as $val => $label ) {
				$checked = checked( $value, $val, false );
				$this->add_html( '<label>' );
				$this->add_html( '<input type="radio" value="' . esc_attr( $val ) . '" name="' . esc_attr( $this->field_name ) . '" id="' . sanitize_html_class( $this->field_name . $val ) . '" class="asqa-form-control" ' . $checked . $this->custom_attr() . '/>' );
				$this->add_html( $label . '</label>' );
			}
		}

		/** This action is documented in lib/form/class-input.php */
		do_action_ref_array( 'asqa_after_field_markup', array( &$this ) );
	}

}
