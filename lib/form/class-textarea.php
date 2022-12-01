<?php
/**
 * SmartQa Textarea type field object.
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
 * The Textarea type field object.
 *
 * @since 4.1.0
 */
class Textarea extends Field {
	/**
	 * The field type.
	 *
	 * @var string
	 */
	public $type = 'textarea';

	/**
	 * Prepare field.
	 *
	 * @return void
	 */
	protected function prepare() {
		$this->args = wp_parse_args(
			$this->args,
			array(
				'label' => __( 'SmartQa Textarea Field', 'smart-question-answer' ),
				'attr'  => array(
					'rows' => 8,
				),
			)
		);

		// Call parent prepare().
		parent::prepare();
		$this->sanitize_cb = array_merge( array( 'textarea_field' ), $this->sanitize_cb );
	}

	/**
	 * Field markup.
	 *
	 * @return void
	 */
	public function field_markup() {
		parent::field_markup();

		$this->add_html( '<textarea' . $this->common_attr() . $this->custom_attr() . '>' );
		$this->add_html( esc_textarea( $this->value() ) );
		$this->add_html( '</textarea>' );

		/** This action is documented in lib/form/class-input.php */
		do_action_ref_array( 'asqa_after_field_markup', array( &$this ) );
	}
}
