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

	public function __construct( $field_id, $field ) {

		/* Call the parent constructor */
		parent::__construct( $field_id, $field );

		$args = func_get_args();

		call_user_func_array( array( $this, 'parent::__construct' ), $args );

		$this->terms                 = get_terms( $this->field_id, array( 'hide_empty' => 0 ) );
		$this->ordered_terms         = array();
		$this->field_args['select2'] = isset( $this->field_args['select2'] ) ? (bool) $this->field_args['select2'] : false;

		if ( ! is_wp_error( $this->terms ) ) {
			/**
			 * Re-order the terms hierarchically.
			 */
			wpas_sort_terms_hierarchicaly( $this->terms, $this->ordered_terms );

			// Filter the terms to allow manipulation
			$this->ordered_terms = apply_filters( 'wpas_cf_taxonomy_oredered_terms', $this->ordered_terms );
		}

		if ( true === $this->field_args['select2'] ) {
			add_filter( 'wpas_cf_field_class', array( $this, 'add_select2_class' ), 10, 2 );
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
			wpas_hierarchical_taxonomy_dropdown_options( $term, $this->populate() );
		}

		$options = ob_get_contents();

		ob_end_clean();

		return sprintf( '<label {{label_atts}}>{{label}}</label><select {{atts}}><option value="">%s</option>%s</select>', __( 'Please select', 'awesome-support' ), $options );

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
		return sprintf( '<div class="wpas-cf-noedit-wrapper"><div id="%s-label" class="wpas-cf-label">%s</div><div id="%s-value" class="wpas-cf-value">%s</div></div>', $this->get_field_id(), $this->get_field_label(), $this->get_field_id(), $this->get_field_value() );
	}

	/**
	 * Save function.
	 *
	 * Taxonomies are saved differently as they are
	 * not stored as post metas but actual taxonomy terms.
	 *
	 * @since 3.2.0
	 *
	 * @param int $value   New value
	 * @param int $post_id ID of the post being saved
	 *
	 * @return int Result of the update
	 */
	public function update( $value, $post_id ) {

		/* If this is a standard taxonomy we don't do anything and let WordPress take care of it. */
		if ( true === $this->field['args']['taxo_std'] ) {
			return 0;
		}

		/* If no value is submitted we delete the term relationship */
		if ( empty( $value ) ) {

			$terms = wp_get_post_terms( $post_id, $this->field_id );

			if ( ! empty( $terms ) ) {

				wp_delete_object_term_relationships( $post_id, $this->field_id );

				return 3;

			}

		}

		/* Get all the terms for this ticket / taxo (we should have only one term) */
		$terms = get_the_terms( $post_id, $this->field_id );

		/**
		 * As the taxonomy is handled like a select, we should have only one value. At least
		 * that's what we want. Hence, we loop through the possible multiple terms (which
		 * shouldn't happen) and only keep the last one.
		 */
		$the_term = '';

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$the_term = $term->term_id;
			}
		}

		/* Finally we save the new terms if changed */
		if ( $the_term !== (int) $value ) {

			$term = get_term_by( 'id', (int) $value, $this->field_id );

			/* If the term does not exist we can't do anything. */
			if ( false === $term ) {
				return 0;
			}

			wp_set_object_terms( $post_id, (int) $value, $this->field_id, false );

			return empty( $the_term ) ? 1 : 2;

		}

		return 0;

	}

	/**
	 * Add the select2 class to the input
	 *
	 * @since 3.3
	 *
	 * @param array $classes Input classes
	 * @param array $field   Array of params of the field being processed
	 *
	 * @return array
	 */
	public function add_select2_class( $classes, $field ) {

		if ( $field['name'] !== $this->field_id ) {
			return $classes;
		}

		if ( true !== $this->field_args['select2'] ) {
			return $classes;
		}

		$classes[] = 'wpas-select2';

		return $classes;

	}

}