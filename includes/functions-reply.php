<?php
/**
 * Fetch ticket replies count based on user id
 * @global object $wpdb
 * @param int $ticket_id
 * @param int $user_id
 * 
 * @return int
 */
function wpas_count_user_replies( $ticket_id, $user_id ) {
	
	global $wpdb;
	$count = 0;
	if($user_id) {
		$query = $wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}posts WHERE post_author = %d AND post_parent = %d AND post_type = %s AND post_status != 'trash'", $user_id, $ticket_id, 'ticket_reply');
		$count = $wpdb->get_var($query);
	}
	
	return $count;
	
}

/**
 * Fetch ticket total replies count
 * @global object $wpdb
 * @param int $ticket_id
 * 
 * @return int
 */
function wpas_count_total_replies( $ticket_id ) {
	
	global $wpdb;
	$count = 0;
	
	$query = $wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}posts WHERE post_parent = %d AND post_type = %s AND post_status != 'trash'", $ticket_id, 'ticket_reply');
	$count = $wpdb->get_var($query);
	
	
	return $count;
	
}

/**
 * Get ticket agent replies count
 * @param int $ticket_id
 * 
 * @return int
 */
function wpas_num_agent_replies( $ticket_id ) {
	
	$count = wpas_get_cf_value( 'ttl_replies_by_agent', $ticket_id );
	return ( $count ? $count : 0 );
	
}


/**
 * Get ticket customer replies count
 * @param int $ticket_id
 * 
 * @return int
 */
function wpas_num_customer_replies( $ticket_id ) {
	
	$count = wpas_get_cf_value( 'ttl_replies_by_customer', $ticket_id );
	return ( $count ? $count : 0 );
	
}

/**
 * Get Ticket total replies
 * @param int $ticket_id
 * 
 * @return int
 */
function wpas_num_total_replies( $ticket_id ) {
	
	$count = wpas_get_cf_value( 'ttl_replies', $ticket_id );
	return ( $count ? $count : 0 );
	
}

/**
 * Calculate and store ticket replies count
 * @param int $ticket_id
 */
function wpas_count_replies( $ticket_id ) {
	
	$ticket = get_post( $ticket_id );
	$agent_id = (int) get_post_meta( $ticket_id, '_wpas_assignee', true );
	$customer_id = (int) $ticket->post_author;
	
	$total_replies_count = wpas_count_total_replies($ticket_id);
	$customer_replies_count = wpas_count_user_replies($ticket_id, $customer_id);
	$agent_replies_count = $total_replies_count - $customer_replies_count;
	
	update_post_meta( $ticket_id, '_wpas_ttl_replies_by_customer', $customer_replies_count );
	update_post_meta( $ticket_id, '_wpas_ttl_replies_by_agent', $agent_replies_count );
	update_post_meta( $ticket_id, '_wpas_ttl_replies', $total_replies_count );
	
}


add_action( 'wpas_add_reply_after', 'wpas_ticket_reset_replies_count', 10, 2 );
add_action( 'wpas_admin_reply_trashed', 'wpas_ticket_reset_replies_count' );

/**
 * Reset replies count
 * @param int $reply_id
 * @param array $data
 */
function wpas_ticket_reset_replies_count( $reply_id, $data = array()) {
	
	if(isset($data['post_parent'])) {
		$ticket_id = $data['post_parent'];
	} else {
		$ticket_id = wp_get_post_parent_id($reply_id);
	}
	
	wpas_count_replies( $ticket_id );
}