<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Taxonomy extends WPAS_Custom_Field {

	/**
	 * The taxonomy terms.
	 *
	 * @since 3.2.0
	 * @var $terms array
	 */
	protected $terms;

	/**
	 * The taxonomy terms ordered hierarchically.
	 *
	 * @since 3.2.0
	 * @var $ordered_terms array
	 */
	protected $ordered_terms;

	public function __construct() {

		$args = func_get_args();

		call_user_func_array( array( $this, 'parent::__construct' ), $args );

		$this->terms         = get_terms( $this->field_id, array( 'hide_empty' => 0 ) );
		$this->ordered_terms = array();

		if ( ! is_wp_error( $this->terms ) ) {
			/**
			 * Re-order the terms hierarchically.
			 */
			wpas_sort_terms_hierarchicaly( $this->terms, $this->ordered_terms );
		}

	}

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {

		ob_start();

		foreach ( $this->ordered_terms as $term ) {
			wpas_hierarchical_taxonomy_dropdown_options( $term, $this->get_field_value() );
		}

		$options = ob_get_contents();

		ob_end_clean();

		return sprintf( '<label {{label_atts}}>{{label}}</label><select {{atts}}><option value="">%s</option>%s</select>', __( 'Please select', 'wpas' ), $options );

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
		return sprintf( '<p id="%s">%s</p>', $this->get_field_id(), $this->get_field_value() );
	}

}