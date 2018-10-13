<?php

/**
 * List of default ticket types
 * @return array
 */
function wpas_default_ticket_types() {
	$ticket_types = array(
		'Refund Request',
		'Service Request',
		'Get Personal Data Request',
		'Delete Personal Data Request',
		'Bug Report',
		'Sales Question',
		'Pre-sales Question',
		'Technical Issue',
		'Order Related Question',
		'Shipping Inquiry',
		'Delivery Inquiry',
		'Product Availability Question'
	);
	
	return apply_filters( 'wpas_default_ticket_types', $ticket_types );
}

/**
 * add ticket types
 * @param boolean $reset
 * @return boolean
 */
function wpas_add_default_ticket_types($reset = false) {
	
	if (!$reset) {
		
		$added_before = boolval( get_option( 'wpas_default_ticket_types_added', false ) );
		
		if ( true ===  $added_before) {
			return;
		}
	}
	
	if ( true === taxonomy_exists('ticket_type') ) {
		
		$ticket_types = wpas_default_ticket_types();
		
		foreach($ticket_types as $ticket_type) {
			wp_insert_term($ticket_type, 'ticket_type');
		}
		
		update_option('wpas_default_ticket_types_added', true);
		
	}
	return true;
	
}


add_action( 'tf_admin_options_saved_wpas', 'wpas_add_default_ticket_types' );