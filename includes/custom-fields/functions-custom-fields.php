<?php
/**
 * Submission Form Functions.
 *
 * This file contains all the functions related to the ticket submission form.
 * Those functions are being used on the front-end only and aren't used anywhere
 * else than the submission form.
 */

/**
 * Custom callback for updating terms count.
 *
 * The function is based on the original WordPress function
 * _update_post_term_count but adapted to work with the plugin
 * custom status.
 *
 * @since  3.0.0
 * @param  array  $terms    List of terms attached to the post
 * @param  object $taxonomy Taxonomy of update
 * @return void
 */
function wpas_update_ticket_tag_terms_count( $terms, $taxonomy ) {

	global $wpdb;

	$object_types   = (array) $taxonomy->object_type;
	$post_status    = wpas_get_post_status();
	$allowed_status = array();

	foreach ( $post_status as $status => $label ) {
		if ( !in_array( $status, $allowed_status ) ) {
			array_push( $allowed_status, $status );
		}
	}

	foreach ( $object_types as &$object_type ) {
		list( $object_type ) = explode( ':', $object_type );
	}

	$object_types = array_unique( $object_types );

	if ( false !== ( $check_attachments = array_search( 'attachment', $object_types ) ) ) {
		unset( $object_types[ $check_attachments ] );
		$check_attachments = true;
	}

	if ( $object_types ) {
		$object_types = esc_sql( array_filter( $object_types, 'post_type_exists' ) );
	}

	foreach ( (array) $terms as $term ) {
		$count = 0;

		// Attachments can be 'inherit' status, we need to base count off the parent's status if so
		if ( $check_attachments ) {
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status = 'publish' OR ( post_status = 'inherit' AND post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) = 'publish' ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term ) );
		}

		if ( $object_types ) {
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status IN ('" . implode( "', '", $allowed_status ) . "') AND post_type IN ('" . implode( "', '", $object_types ) . "') AND term_taxonomy_id = %d", $term ) );
		}

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edit_term_taxonomy', $term, $taxonomy );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edited_term_taxonomy', $term, $taxonomy );
	}

}

/**
 * Return a custom field value.
 *
 * @param  string  $name    Option name
 * @param  integer $post_id Post ID
 * @param  mixed   $default Default value
 *
 * @return mixed            Meta value
 * @since  3.0.0
 */
function wpas_get_cf_value( $name, $post_id, $default = false ) {

	$field = new WPAS_Custom_Field( $name );

	return $field->get_field_value( $default, $post_id );
}

/**
 * Echo a custom field value.
 *
 * This function is just a wrapper function for wpas_get_cf_value()
 * that echoes the result instead of returning it.
 *
 * @param  string  $name    Option name
 * @param  integer $post_id Post ID
 * @param  mixed   $default Default value
 *
 * @return mixed            Meta value
 * @since  3.0.0
 */
function wpas_cf_value( $name, $post_id, $default = false ) {
	echo wpas_get_cf_value( $name, $post_id, $default );
}

/**
 * Add a new custom field.
 *
 * @since  3.0.0
 *
 * @param  string $name The ID of the custom field to add
 * @param  array  $args Additional arguments for the custom field
 *
 * @return boolean        Returns true on success or false on failure
 */
function wpas_add_custom_field( $name, $args = array() ) {
	return WPAS()->custom_fields->add_field( $name, $args );
}

/**
 * Add a new custom taxonomy.
 *
 * @since  3.0.0
 *
 * @param  string $name The ID of the custom field to add
 * @param  array  $args Additional arguments for the custom field
 *
 * @return boolean        Returns true on success or false on failure
 */
function wpas_add_custom_taxonomy( $name, $args = array() ) {

	/* Force the custom fields type to be a taxonomy. */
	$args['field_type']      = 'taxonomy';
	$args['column_callback'] = 'wpas_show_taxonomy_column';

	/* Add the taxonomy. */
	WPAS()->custom_fields->add_field( $name, $args );

	return true;

}

add_action( 'init', 'wpas_register_core_fields' );
/**
 * Register the cure custom fields.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_register_core_fields() {

	wpas_add_custom_field( 'assignee', array(
		'core'        => true,
		'show_column' => false,
		'log'         => true,
		'title'       => __( 'Support Staff', 'awesome-support' )
	) );

	wpas_add_custom_field( 'status', array(
		'core'            => true,
		'show_column'     => true,
		'log'             => false,
		'field_type'      => false,
		'column_callback' => 'wpas_cf_display_status',
		'save_callback'   => null
	) );

	wpas_add_custom_field( 'ticket-tag', array(
		'core'                  => true,
		'show_column'           => true,
		'log'                   => true,
		'field_type'            => 'taxonomy',
		'taxo_std'              => true,
		'column_callback'       => 'wpas_cf_display_status',
		'save_callback'         => null,
		'label'                 => __( 'Tag', 'awesome-support' ),
		'name'                  => __( 'Tag', 'awesome-support' ),
		'label_plural'          => __( 'Tags', 'awesome-support' ),
		'taxo_hierarchical'     => false,
		'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
		'select2'               => false
	) );

	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

	if ( isset( $options['support_products'] ) && true === boolval( $options['support_products'] ) ) {

		$slug = defined( 'WPAS_PRODUCT_SLUG' ) ? WPAS_PRODUCT_SLUG : 'product';

		/* Filter the taxonomy labels */
		$labels = apply_filters( 'wpas_product_taxonomy_labels', array(
				'label'        => __( 'Product', 'awesome-support' ),
				'name'         => __( 'Product', 'awesome-support' ),
				'label_plural' => __( 'Products', 'awesome-support' )
			)
		);

		wpas_add_custom_field( 'product', array(
			'core'                  => false,
			'show_column'           => true,
			'log'                   => true,
			'field_type'            => 'taxonomy',
			'taxo_std'              => false,
			'column_callback'       => 'wpas_show_taxonomy_column',
			'label'                 => $labels['label'],
			'name'                  => $labels['name'],
			'label_plural'          => $labels['label_plural'],
			'taxo_hierarchical'     => true,
			'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
			'rewrite'               => array( 'slug' => $slug ),
			'select2'               => false
		) );

	}

	if ( isset( $options['departments'] ) && true === boolval( $options['departments'] ) ) {

		$slug = defined( 'WPAS_DEPARTMENT_SLUG' ) ? WPAS_DEPARTMENT_SLUG : 'department';

		/* Filter the taxonomy labels */
		$labels = apply_filters( 'wpas_product_taxonomy_labels', array(
				'label'        => __( 'Department', 'awesome-support' ),
				'name'         => __( 'Department', 'awesome-support' ),
				'label_plural' => __( 'Departments', 'awesome-support' )
			)
		);

		wpas_add_custom_field( 'department', array(
			'core'                  => false,
			'show_column'           => true,
			'log'                   => true,
			'field_type'            => 'taxonomy',
			'taxo_std'              => false,
			'column_callback'       => 'wpas_show_taxonomy_column',
			'label'                 => $labels['label'],
			'name'                  => $labels['name'],
			'label_plural'          => $labels['label_plural'],
			'taxo_hierarchical'     => true,
			'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
			'rewrite'               => array( 'slug' => $slug ),
			'select2'               => false
		) );

	}

}