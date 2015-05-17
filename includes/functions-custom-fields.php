<?php
/**
 * Submission Form Functions.
 *
 * This file contains all the functions related to the ticket submission form.
 * Those functions are being used on the front-end only and aren't used anywhere
 * else than the submission form.
 */

/**
 * Get temporary field value.
 *
 * Once a form is submitted, all values are kept
 * in session in case the ticket submission fails.
 * Once the submission form reloads we can pre-popupate fields
 * and avoid the pain of re-typing everything for the user.
 * When a submission is valid, the session is destroyed.
 * 
 * @param  string $field_name The name of the field to get the value for
 * @return string             The temporary value for this field
 * @since  3.0.0
 */
function wpas_get_field_value( $field_name ) {

	$meta = get_post_meta( get_the_ID(), '_wpas_' . $field_name, true );

	if ( isset( $_SESSION['wpas_submission_form'] ) && is_array( $_SESSION['wpas_submission_form'] ) && array_key_exists( $field_name, $_SESSION['wpas_submission_form'] ) ) {
		$value = $_SESSION['wpas_submission_form'][$field_name];
	} elseif ( !empty( $meta ) ) {
		$value = $meta;
	} else {
		$value = '';
	}

	return apply_filters( 'wpas_get_field_value', esc_attr( wp_unslash( $value ) ), $field_name );

}

/**
 * Get field container class.
 *
 * @since  3.0.0
 * @param  string $field_name Name of the field we're getting the container class for
 * @param  string $extra      Extra classes to pass to the function
 * @return string             The class tag with appropriate classes
 */
function wpas_get_field_container_class( $field_name = false, $extra = '' ) {

	$class = 'wpas-form-group';

	if ( isset( $_SESSION['wpas_submission_error'] ) && is_array( $_SESSION['wpas_submission_error'] ) && in_array( $field_name, $_SESSION['wpas_submission_error'] ) ) {
		$class .= ' has-error';
	}

	if ( '' != $extra ) {
		$class .= " $extra";
	}

	echo "class='$class'";

}

/**
 * Get field class.
 *
 * @since  3.0.0
 * @param  string $field_name Name of the field we're getting the class for
 * @param  string $extra      Extra classes to pass to the function
 * @return string             The class tag with appropriate classes
 */
function wpas_get_field_class( $field_name = false, $extra = '', $echo = true ) {

	$class = 'wpas-form-control';

	if ( '' != $extra ) {
		$class .= " $extra";
	}

	if ( true === $echo ) {
		echo "class='$class'";
	} else {
		return $class;
	}

}

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