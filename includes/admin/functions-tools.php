<?php
/**
 * Clear the activity meta for a given ticket.
 *
 * Deletes the activity meta transient from the database
 * for one given ticket.
 *
 * @since  3.0.0
 * @param  integer $ticket_id ID of the ticket to clear the meta from
 * @return boolean            True if meta was cleared, false otherwise
 * 
 */
function wpas_clear_ticket_activity_meta( $ticket_id ) {
	return delete_transient( "wpas_activity_meta_post_$ticket_id" );
}

/**
 * Clear all tickets metas.
 *
 * Gets all the existing tickets from the system
 * and clear their metas one by one.
 *
 * @since 3.0.0
 * @return  True if some metas were cleared, false otherwise
 * 
 */
function wpas_clear_tickets_metas() {

	$args = array(
		'post_type'              => 'ticket',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);

	$query   = new WP_Query( $args );
	$cleared = false;
	
	if ( 0 == $query->post_count ) {
		return false;
	}

	foreach( $query->posts as $post ) {
		if ( wpas_clear_ticket_activity_meta( $post->ID ) && false === $cleared ) {
			$cleared = true;
		}
	}

	return $cleared;

}

/**
 * Clear all terms for a given taxonomy.
 *
 * @since  3.0.0
 * @param  string $taxonomy Taxonomy name
 * @return boolean          True if terms were deleted, false otherwise
 */
function wpas_clear_taxonomy( $taxonomy ) {

	$terms  = get_terms( $taxonomy, array( 'hide_empty' => false ) );
	$delete = false;

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return false;
	}

	foreach ( $terms as $term ) {
		if ( wp_delete_term( $term->term_id, $taxonomy ) && false === $delete ) {
			$delete = true;
		}
	}

	return $delete;

}

/**
 * Clear all custom taxonomies terms.
 *
 * @since  3.0.0
 * @return boolean True if terms were deleted, false otherwise
 */
function wpas_clear_taxonomies() {

	global $wpas_cf;

	$taxonomies = (array) $wpas_cf->get_custom_fields();
	$deleted    = false;

	if ( empty( $taxonomies ) ) {
		return false;
	}

	foreach ( $taxonomies as $taxonomy ) {

		if ( 'taxonomy' !== $taxonomy['args']['callback'] ) {
			continue;
		}

		if ( wpas_clear_taxonomy( $taxonomy['name'] ) && false === $deleted ) {
			$deleted = true;
		}

	}

	return $deleted;

}

/**
 * Delete the synchronized e-commerce products.
 *
 * The function goes through all the available products
 * and deletes the associated synchronized terms along with
 * the term taxonomy and term relationship. It also deleted
 * the post metas where the taxonomy ID is stored.
 *
 * @param $resync boolean Whether or not to re-synchronize the products after deleting them
 *
 * @return        boolean True if the operation completed, false otherwise
 * @since 3.1.7
 */
function wpas_delete_synced_products( $resync = false ) {

	$post_type = filter_input( INPUT_GET, 'pt', FILTER_SANITIZE_STRING );

	if ( empty( $post_type ) ) {
		return false;
	}

	$sync  = new WPAS_Product_Sync( '', 'product' );
	$posts = new WP_Query( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => 'any' ) );
	$sync->set_post_type( $post_type );

	if ( ! empty( $posts->posts ) ) {

		/* Remove all terms and post metas */
		foreach ( $posts->posts as $post ) {
			$sync->unsync_term( $post->ID );
		}

	}

	/* Now let's make sure we don't have some orphan post metas left */
	global $wpdb;

	$metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = '%s'", '_wpas_product_term' ) );

	if ( ! empty( $metas ) ) {

		foreach ( $metas as $meta ) {

			$value = unserialize( $meta->meta_value );
			$term = get_term_by( 'id', $value['term_id'], 'product' );

			if ( empty( $term ) ) {
				delete_post_meta( $meta->post_id, '_wpas_product_term' );
			}

		}

	}

	if ( true === $resync ) {

		/* Delete the initial synchronization marker so that it's done again */
		delete_option( "wpas_sync_$post_type" );

		/* Synchronize */
		$sync->run_initial_sync();

	}

	return true;

}