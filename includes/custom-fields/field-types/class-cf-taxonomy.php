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
		
		$term_args = array( 'hide_empty' => 0 );		
		
		$sort_order = isset( $this->field_args['taxo_sortorder'] ) ? $this->field_args['taxo_sortorder'] : '';
		$order_types = array( 'asc', 'desc' );
		if( $sort_order && in_array( $sort_order, $order_types ) ) {
			$term_args['order'] = $sort_order;
		}

		$term_args['taxonomy']       = $this->field_id;
		$this->terms                 = get_terms( $term_args );
		$this->ordered_terms         = array();
		$this->field_args['select2'] = isset( $this->field_args['select2'] ) ? (bool) $this->field_args['select2'] : false;

		if ( ! is_wp_error( $this->terms ) ) {

			/**
			 * Custom term_order sorting: if any term has a term_order meta set,
			 * sort by that value. Terms without term_order go to the end,
			 * preserving their relative name-based order among themselves.
			 * If NO term has term_order, fallback to existing alphabetical logic.
			 *
			 * @since 6.2.0
			 */
			$has_custom_order = false;
			foreach ( $this->terms as $term ) {
				$order = get_term_meta( $term->term_id, 'term_order', true );
				if ( $order !== '' && $order !== false ) {
					$has_custom_order = true;
					break;
				}
			}

			if ( $has_custom_order ) {
				// Sort by term_order meta; terms without order go to end
				usort( $this->terms, array( $this, 'sortByTermOrder' ) );
			} elseif( $sort_order && in_array( $sort_order, $order_types ) ) {
				// Fallback: existing alphabetical sort
				if( 'asc' === strtolower( $sort_order ) ) {
					usort( $this->terms, array( $this, 'sortByNameASC' ) );
				} else {
					usort( $this->terms, array( $this, 'sortByNameDESC' ) );
				}
			}
			
			/**
			 * Re-order the terms hierarchically.
			 */
			wpas_sort_terms_hierarchicaly( $this->terms, $this->ordered_terms );

			// Filter the terms to allow manipulation
			$this->ordered_terms = apply_filters( 'wpas_cf_taxonomy_ordered_terms', $this->ordered_terms );
		}

		if ( true === $this->field_args['select2'] ) {
			add_filter( 'wpas_cf_field_class', array( $this, 'add_select2_class' ), 10, 2 );
		}

	}
	
	/**
	 * Ascending order terms by term name 
	 * 
	 * @param WP_Term $termA
	 * @param WP_Term $termB
	 * 
	 * @return boolean
	 */
	function sortByNameASC( $termA, $termB ) {
		return strcasecmp( $termA->name, $termB->name );
	}
	
	/**
	 * Descending order terms by name 
	 * 
	 * @param WP_Term $termA
	 * @param WP_Term $termB
	 * 
	 * @return boolean
	 */
	function sortByNameDESC( $termA, $termB ) {
		return strcasecmp( $termB->name, $termA->name );
	}

	/**
	 * Sort terms by custom term_order meta value.
	 *
	 * Terms with a term_order meta are sorted numerically (ascending).
	 * Terms without term_order are placed at the end, sorted alphabetically.
	 *
	 * @since 6.2.0
	 *
	 * @param WP_Term $termA
	 * @param WP_Term $termB
	 *
	 * @return int
	 */
	function sortByTermOrder( $termA, $termB ) {
		$orderA = get_term_meta( $termA->term_id, 'term_order', true );
		$orderB = get_term_meta( $termB->term_id, 'term_order', true );

		$hasA = ( $orderA !== '' && $orderA !== false );
		$hasB = ( $orderB !== '' && $orderB !== false );

		// Both have order: sort numerically
		if ( $hasA && $hasB ) {
			return intval( $orderA ) - intval( $orderB );
		}

		// Only A has order: A comes first
		if ( $hasA && ! $hasB ) {
			return -1;
		}

		// Only B has order: B comes first
		if ( ! $hasA && $hasB ) {
			return 1;
		}

		// Neither has order: sort alphabetically
		return strcasecmp( $termA->name, $termB->name );
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

				wp_set_object_terms( $post_id, array(), $this->field_id );

				return 3;

			}

		}

		/* Clean object term cache first to avoid stale data from Master Cache */
		clean_object_term_cache( $post_id, $this->field_id );

		/* Get current terms directly from DB (bypasses object cache) */
		$terms = wp_get_object_terms( $post_id, $this->field_id, array( 'fields' => 'ids' ) );

		$the_term = 0;
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$the_term = (int) end( $terms );
		}

		/* Find the target term by ID, slug, or name */
		$target_term = false;
		if ( is_numeric( $value ) ) {
			$target_term = get_term_by( 'id', (int) $value, $this->field_id );
		}
		if ( ! $target_term && ! empty( $value ) ) {
			$target_term = get_term_by( 'slug', (string) $value, $this->field_id );
		}
		if ( ! $target_term && ! empty( $value ) ) {
			$target_term = get_term_by( 'name', (string) $value, $this->field_id );
		}

		/* If the target term does not exist we cannot update */
		if ( false === $target_term || is_wp_error( $target_term ) ) {
			return 0;
		}

		/* Save the new term if changed or if there are multiple terms assigned */
		if ( count( $terms ) !== 1 || $the_term !== (int) $target_term->term_id ) {

			/*
			 * Use wp_set_object_terms with append=false to atomically REPLACE
			 * all existing terms with the single new term. This ensures no stale
			 * terms remain and all WP caches are properly updated.
			 */
			wp_set_object_terms( $post_id, array( (int) $target_term->term_id ), $this->field_id, false );

			clean_object_term_cache( $post_id, $this->field_id );
			clean_post_cache( $post_id );

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