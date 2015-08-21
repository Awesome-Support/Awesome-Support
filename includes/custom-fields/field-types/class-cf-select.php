<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Select extends WPAS_Custom_Field {

	public $options = array();

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {

		if ( ! isset( $this->field_args['options'] ) || empty( $this->field_args['options'] ) ) {
			return '<!-- No options declared -->';
		}

		$output        = '<label {{label_atts}}>{{label}}</label><select {{atts}}>';
		$this->options = $this->field_args['options'];
		$value         = $this->populate();

		foreach ( $this->options as $option_id => $option_label ) {
			$selected = $option_id == $value ? 'selected' : '';
			$output .= sprintf( "<option value='%s' %s>%s</option>", $option_id, $selected, $option_label );
		}

		/* Close the select */
		$output .= '</select>';

		return $output;

	}

	/**
	 * Return the field markup for the admin.
	 *
	 * This method is only used if the current user
	 * has the capability to edit the field.
	 */
	public function display_admin() {
		return $this->display();
	}

	/**
	 * Return the field markup for the admin.
	 *
	 * This method is only used if the current user
	 * doesn't have the capability to edit the field.
	 */
	public function display_no_edit() {
		return sprintf( '<div class="wpas-cf-noedit-wrapper"><div id="%s-label" class="wpas-cf-label">%s</div><div id="%s-value" class="wpas-cf-value">%s</div></div>', $this->get_field_id(), $this->get_field_title(), $this->get_field_id(), $this->get_field_value() );
	}

}