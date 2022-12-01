<?php
/**
 * SmartQa Group type field object.
 *
 * @package    SmartQa
 * @subpackage Fields
 * @since      4.1.0
 * @author     Peter Mertzlin<support@extensionforge.com>
 * @copyright  Copyright (c) 2017, Peter Mertzlin
 * @license    http://opensource.org/licenses/gpl-3.0.php GNU Public License
 */

namespace SmartQa\Form\Field;

use SmartQa\Form as Form;
use SmartQa\Form\Field as Field;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SmartQa Group type field object.
 *
 * @since 4.1.0
 */
class Group extends Field {
	/**
	 * The field type.
	 *
	 * @var string
	 */
	public $type = 'group';

	/**
	 * The child fields.
	 *
	 * @var SmartQa\Form
	 */
	public $child;

	/**
	 * Prepare field.
	 *
	 * @return void
	 */
	protected function prepare() {
		$this->args = wp_parse_args(
			$this->args,
			array(
				'label'         => __( 'SmartQa Group Field', 'smart-question-answer' ),
				'toggleable'    => false,
				'delete_button' => false, // Used for repeatable fields.
			)
		);

		$this->child = new Form( $this->form_name, $this->args );
		$this->child->prepare();

		// Call parent prepare().
		parent::prepare();
	}

	/**
	 * Order of HTML markup.
	 *
	 * @return void
	 */
	protected function html_order() {
		$this->output_order = array( 'wrapper_start', 'label', 'field_wrasqa_start', 'desc', 'errors', 'field_markup', 'field_wrasqa_end', 'wrapper_end' );
	}

	/**
	 * Output label of field.
	 *
	 * @return void
	 */
	public function label() {
		$this->add_html( '<label class="asqa-form-label" for="' . sanitize_html_class( $this->field_name ) . '">' . esc_html( $this->get( 'label' ) ) );

		// Shows delete button for repeatable fields.
		if ( true === $this->get( 'delete_button', false ) ) {
			$this->add_html( '<button class="asqa-btn asqa-repeatable-delete">' . __( 'Delete', 'smart-question-answer' ) . '</button>' );
		}

		$this->add_html( '</label>' );
	}

	/**
	 * Field markup.
	 *
	 * @return void
	 */
	public function field_markup() {
		parent::field_markup();

		$checked = true;

		if ( $this->get( 'toggleable' ) ) {
			// Show toggle group if child fields have errors.
			$value   = $this->have_errors() ? 1 : ! empty( array_filter( (array) $this->value() ) );
			$checked = checked( $value, 1, false );

			$this->add_html( '<label for="' . sanitize_html_class( $this->field_name ) . '"><input' . $this->common_attr() . ' ' . $checked . ' type="checkbox" value="1" onchange="SmartQa.Helper.toggleNextClass(this);" />' . esc_html( $this->get( 'toggleable.label', $this->get( 'label' ) ) ) );
			$this->add_html( '</label>' );
		}

		$this->add_html( '<div class="asqa-fieldgroup-c' . ( $checked ? ' show' : '' ) . '">' );
		$this->add_html( $this->child->generate_fields() );
		$this->add_html( '</div>' );

		/** This action is documented in lib/form/class-input.php */
		do_action_ref_array( 'asqa_after_field_markup', array( &$this ) );
	}

}
