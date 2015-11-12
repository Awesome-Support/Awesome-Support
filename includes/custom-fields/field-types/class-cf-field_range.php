<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Field_Range extends WPAS_Custom_Field {

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {
		$min = $this->get_field_arg( 'range_min' );
		$max = $this->get_field_arg( 'range_max' );
		$step = $this->get_field_arg( 'range_step' );
		$label_left = $this->get_field_arg( 'label_left' );
		$label_right = $this->get_field_arg( 'label_right' );
		return sprintf( '<label {{label_atts}}>{{label}}</label><div class="wpas_range_label"><span>'.$label_left.'</span><span>'.$label_right.'</span></div><input type="range" value="%s" min="'.$min.'" max="'.$max.'" step="'.$step.'">', $this->populate() );
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