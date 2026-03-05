<?php
/**
 * @package   Ayuda – Help Desk/E-Mail Notifications
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'mumei_ayuda_open_ticket_after', 'mumei_ayuda_notify_confirmation', 11, 2 );
/**
 * Send e-mail confirmation.
 *
 * Sends an e-mail confirmation to the client.
 *
 * @since  3.0.0
 *
 * @param  integer $ticket_id ID of the new ticket
 * @param  array   $data      Ticket data
 *
 * @return void
 */
function mumei_ayuda_notify_confirmation( $ticket_id, $data ) {
	mumei_ayuda_email_notify( $ticket_id, 'submission_confirmation' );
}

add_action( 'mumei_ayuda_open_ticket_after', 'mumei_ayuda_notify_assignment', 12, 2 );
/**
 * Send e-mail assignment notification.
 *
 * Sends an e-mail to the agent that a new ticket has been assigned.
 *
 * @since  3.1.3
 *
 * @param  integer $ticket_id ID of the new ticket
 * @param  integer $agent_id  ID of the agent who's assigned
 *
 * @return void
 */
function mumei_ayuda_notify_assignment( $ticket_id, $agent_id ) {
	mumei_ayuda_email_notify( $ticket_id, 'new_ticket_assigned' );
}

add_action( 'mumei_ayuda_ticket_after_update_admin_success', 'mumei_ayuda_notify_admin_assignment', 12, 3 );
/**
 * Send e-mail assignment notification when ticket is updated from back-end or admin pannel.
 *
 * Sends an e-mail to the agent that they were added to an existing ticket.
 *
 * @since  4.0.0
 *
 * @param  integer $ticket_id ID of the new ticket
 * @param  numeric $old_assignee id of old assignee before ticket was updated...
 * @param  array   $current_ticket contents of current post / ticket
 *
 * @return void
 */
function mumei_ayuda_notify_admin_assignment( $ticket_id, $old_assignee, $current_ticket ) {
	
	if ( (int) $current_ticket['mumei_ayuda_assignee'] <> (int) $old_assignee ) {
		mumei_ayuda_email_notify( $ticket_id, 'new_ticket_assigned' );
	}
}

add_action( 'mumei_ayuda_post_new_ticket_admin', 'mumei_ayuda_notify_admin_new_ticket', 12, 1 );
/**
 * Send a couple of e-mail notifications to agent and client
 *
 * Sends an e-mail to the agent that a new ticket has been opened on the back end/wp-admin
 * Sends an e-mail to the client confirming that a new ticket has been opened on their behalf
 *
 * @since  4.0.0
 *
 * @param  integer $ticket_id ID of the new ticket
 *
 * @return void
 */
function mumei_ayuda_notify_admin_new_ticket( $ticket_id) {
	mumei_ayuda_email_notify( $ticket_id, 'submission_confirmation' );
	mumei_ayuda_email_notify( $ticket_id, 'new_ticket_assigned' );
}

add_action( 'mumei_ayuda_insert_reply_admin_success', 'mumei_ayuda_notify_admin_reply', 10, 3 );
/**
 * Send a notification to client after a reply is posted on the backend/wp-admin
 *
 *
 * @since  4.0.0
 *
 * @param  integer $ticket_id ID of the new ticket
 * @param  array $data Ticket data
 * @param  array $reply Reply data
 *
 * @return void
 */
function mumei_ayuda_notify_admin_reply( $reply_id, $data, $reply ) {

	mumei_ayuda_email_notify( $reply_id, 'reply_agent' );
}

add_action( 'mumei_ayuda_ticket_closed_by_agent', 'mumei_ayuda_notify_ticket_closed_by_agent', 12, 1 );
/**
 * Send an email to client after ticket is closed
 *
 *
 * @since  4.0.0
 *
 * @param  integer $ticket_id ID of the closed ticket
 *
 * @return void
 */
function mumei_ayuda_notify_ticket_closed_by_agent( $ticket_id) {
	
	$prevent = get_post_meta( $ticket_id, 'mumei_ayuda_close_ticket_prevent_client_notification', true );
	
	if( !$prevent ) {
		mumei_ayuda_email_notify( $ticket_id, 'closed' );
	}
}

add_action( 'mumei_ayuda_add_reply_complete', 'mumei_ayuda_notify_reply', 10, 2 );
function mumei_ayuda_notify_reply( $reply_id, $data ) {

	/* If the ID is set it means we're updating a post and NOT creating. In this case no notification. */
	if ( isset( $data['ID'] ) ) {
		return;
	}

	$ticket_author = $data['post_author'] ;
	if(isset( $data['post_type'] ) &&  $data['post_type'] == 'ticket_reply' && isset( $data['post_parent'] ) )
	{
		$ticket = get_post( $data['post_parent'] );		
		$ticket_author = (int) $ticket->post_author;
	}

	$case = ( user_can( $data['post_author'], 'edit_ticket' ) && $ticket_author != $data['post_author'] ) ? 'agent_reply' : 'client_reply';
	
	mumei_ayuda_email_notify( $reply_id, $case );
}


add_action( 'mumei_ayuda_after_close_ticket', 'mumei_ayuda_notify_close', 10, 3 );
function mumei_ayuda_notify_close( $ticket_id, $update, $user_id ) {

	if ( user_can( $user_id, 'edit_ticket' ) ) {
		$case = 'ticket_closed_agent';
	} elseif ( user_can( $user_id, 'create_ticket' ) ) {
		$case = 'ticket_closed_client';
	} else {
		$case = 'ticket_closed';
	}
	
	$prevent = get_post_meta( $ticket_id, 'mumei_ayuda_close_ticket_prevent_client_notification', true );
	if( $prevent && 'ticket_closed_agent' === $case ) {
		return;
	}
	
	mumei_ayuda_email_notify( $ticket_id, $case );

}


add_action('mumei_ayuda_custom_field_updated', 'mumei_ayuda_additional_agents_new_assignment_notify', 10, 3);
/**
 * Notify additional agent about new ticket assignment
 * @param string $field_id
 * @param int $post_id
 * @param string $value
 */
function mumei_ayuda_additional_agents_new_assignment_notify($field_id ,$post_id, $value) {
	
	if( $field_id == 'secondary_assignee' ) {
		mumei_ayuda_email_notify($post_id, 'new_ticket_assigned_secondary');
	}
	
	elseif( $field_id == 'tertiary_assignee' ) {
		mumei_ayuda_email_notify($post_id, 'new_ticket_assigned_tertiary');
	}	
}