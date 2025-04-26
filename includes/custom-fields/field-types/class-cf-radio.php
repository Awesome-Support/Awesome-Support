<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Radio extends WPAS_Custom_Field {

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

		$output        = '<label class="wpas-label-radio">{{label}}</label>';
		$this->options = $this->field_args['options'];

		// Radio buttons cannot be set to readonly. (A missing HTML spec??) To overcome this
		// we set the selected radio button option to 'checked' and all others to 'disabled'.
		$readonly = wpas_cf_field_markup_time_tracking_readonly( $this->get_field_arg( 'readonly', false ), $this->field ) ? 'disabled' : '';

        $index = 1;
        foreach ( $this->options as $option_id => $option_label ) {
            $selected = $option_id === $this->populate() ? 'checked' : $readonly;
            $output .= sprintf( '<div class="wpas-radio"><span><input type="radio" name="%1$s" id="%1$s-option%5$s" value="%2$s" %3$s > <label for="%1$s-option%5$s">%4$s</label></span></div>', $this->get_field_id(), $option_id, $selected, $option_label, $index );
            $index++;
        }

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