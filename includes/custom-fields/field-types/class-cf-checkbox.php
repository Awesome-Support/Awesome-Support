<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Checkbox extends WPAS_Custom_Field {

	public $options = array();

	public function __construct( $field_id, $field ) {

		// Call parent constructor
		parent::__construct( $field_id, $field );

		// Checkboxes need a custom wrapper class
		add_filter( 'wpas_cf_wrapper_class', array( $this, 'wrapper_class' ), 10, 2 );

	}

	/**
	 * Add a specific wrapper class for checkboxes and remove the default one
	 *
	 * @since 3.3
	 *
	 * @param array $classes Wrapper classes
	 * @param array $field   Field data
	 *
	 * @return array
	 */
	public function wrapper_class( $classes, $field ) {

		if ( 'checkbox' !== $field['args']['field_type'] ) {
			return $classes;
		}

		// Remove the default wrapper class if it's here
		$key = array_search( 'wpas-form-group', $classes );

		if ( false !== $key ) {
			unset( $classes[ $key ] );
		}

		if ( ! in_array( 'wpas-checkbox', $classes ) ) {
			$classes[] = 'wpas-checkbox';
		}

		return $classes;

	}

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {

		if ( ! isset( $this->field_args['options'] ) || empty( $this->field_args['options'] ) ) {
			return '<!-- No options declared -->';
		}

		$output        = '';
		$name_attr     = $this->get_field_id() . '[]';
		$this->options = $this->field_args['options'];
		$values        = $this->populate();

		/* Make sure our $values var is an array */
		if ( ! is_array( $values ) ) {
			$values = (array) $values;
		}

		foreach ( $this->options as $option_id => $option_label ) {
			$selected = in_array( $option_id, $values ) ? 'checked="checked"' : '';
			$output .= sprintf( "<label><input type='checkbox' name='%s' value='%s' %s> %s</label>", $name_attr, $option_id, $selected, $option_label );
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

		$list = '<ul>';
		$values = $this->get_field_value();

		foreach ( $values as $value ) {
			$list .= "<li>$value</li>";
		}

		$list .= '</ul>';

		return sprintf( '<p id="%s">%s</p>%s', $this->get_field_id(), $this->get_field_title(), $list );

	}

}