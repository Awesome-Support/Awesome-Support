<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Date_Field extends WPAS_Custom_Field {

	public function __construct( $field_id, $field ) {

		/* Call the parent constructor */
		parent::__construct( $field_id, $field );

		add_filter( 'wpas_cf_field_class', array( $this, 'add_date_class' ), 10, 2 );

	}

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {
		return sprintf( '<label {{label_atts}}>{{label}}</label><input type="date" value="%s" {{atts}}>', $this->populate() );
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

	/**
	 * Add the date class to the input
	 *
	 * @since 4.0
	 *
	 * @param array $classes Input classes
	 * @param array $field   Array of params of the field being processed
	 *
	 * @return array
	 */
	public function add_date_class( $classes, $field ) {

		if ( $field['name'] !== $this->field_id ) {
			return $classes;
		}

		$classes[] = 'wpas-date';

		return $classes;

	}

}