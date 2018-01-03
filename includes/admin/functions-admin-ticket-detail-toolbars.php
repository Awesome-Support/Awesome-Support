<?php
/**
 * @package   Awesome Support/Admin/Functions/ticket-detail/toolbars
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2018 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** 
 * Status action link.
 * This is used by the details.php metabox file as well as functions in this file.
 * 
 * @params none
 *
 * @see admin/class-awesome-support-admin.php
 */
function get_ticket_details_action_link( $post ) {

	/* Current status */
	$ticket_status = get_post_meta( get_the_ID(), '_wpas_status', true );

	$base_url = add_query_arg( array( 'action' => 'edit', 'post' => $post->ID ), admin_url( 'post.php' ) );
	
	$action = ( in_array( $ticket_status, array( 'closed', '' ) ) ) ? wpas_do_url( $base_url, 'admin_open_ticket' ) : wpas_do_url( $base_url, 'admin_close_ticket' );
	
	return $action ;
}

add_action( 'wpas_backend_middle_toolbar_before', 'wpas_add_close_ticket_item_to_middle_toolbar', 10, 1 );
/**
 * Add a CLOSE TICKET button to the ticket detail toolbar
 * 
 * Action Hook: wpas_backend_middle_toolbar_before
 *
 * @params post $post the current post/ticket being worked on
 */
function wpas_add_close_ticket_item_to_middle_toolbar( $post ) {
	
	/* Current status of ticket */
	$ticket_status = get_post_meta( get_the_ID(), '_wpas_status', true );
	
	/* Status action link close/reopen etc. */
	$action = get_ticket_details_action_link( $post );
	
	if ( 'closed' === $ticket_status ) {
		wpas_add_ticket_detail_toolbar_item( 'a', 'wpas-close-ticket-top', __( 'Re-open Ticket', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/re-open-ticket.png", $action );
	} elseif( '' === $ticket_status ) {
		// do nothing...
	} else {
		wpas_add_ticket_detail_toolbar_item( 'a', 'wpas-close-ticket-top', __( 'Close Ticket', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/close-ticket.png", $action );
	}	
}


