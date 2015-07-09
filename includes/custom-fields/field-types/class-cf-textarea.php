<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Textarea extends WPAS_Custom_Field {

	public $cols = 20;
	public $rows = 8;

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {

		$cols = isset( $this->field_args['cols'] ) ? (int) $this->field_args['cols'] : $this->cols;
		$rows = isset( $this->field_args['rows'] ) ? (int) $this->field_args['rows'] : $this->rows;

		return sprintf( '<label {{label_atts}}>{{label}}</label><textarea cols="%d" rows="%d" {{atts}}>%s</textarea>', $cols, $rows, $this->populate() );

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