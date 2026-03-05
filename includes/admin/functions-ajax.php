<?php
/**
 * @package   Ayuda – Help Desk/Admin/Functions/Ajax
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wp_ajax_mumei_ayuda_dismiss_free_addon_page', 'mumei_ayuda_dismiss_free_addon_page' );
/**
 * Hide the free addon page from the menu
 *
 * @since 3.3.3
 * @return bool
 */
function mumei_ayuda_dismiss_free_addon_page() {
	check_ajax_referer('mumei_ayuda_admin_optin', 'nonce');
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array('message' => __('Unauthorized action. You do not have permission to hide the free addon page from the menu.', 'ayuda-help-desk') ), 403);		
    }
	return add_option( 'mumei_ayuda_dismiss_free_addon_page', true );
}

add_action( 'wp_ajax_mumei_ayuda_skip_wizard_setup', 'mumei_ayuda_skip_wizard_setup' );
/**
 * Skip Setup Wizard
 *
 * @since 3.3.3
 * @return bool
 */
function mumei_ayuda_skip_wizard_setup() {
	check_ajax_referer('mumei_ayuda_admin_wizard', 'nonce');
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array('message' => __('Unauthorized action. You do not have permission to skip Setup Wizard.', 'ayuda-help-desk') ), 403);		
    }
	add_option( 'mumei_ayuda_skip_wizard_setup', true );
	wp_die();
}

add_action( 'wp_ajax_mumei_ayuda_get_ticket_for_print', 'mumei_ayuda_get_ticket_for_print_ajax' );
/**
 * Get ticket for print
 *
 * @since 5.1.1
 *
 * @return void
 */
function mumei_ayuda_get_ticket_for_print_ajax() {

	check_ajax_referer( 'mumei_ayuda_print_ticket', 'nonce' );
	if ( ! current_user_can( 'edit_ticket' ) ) {
		wp_send_json_error( array('message' => __('Unauthorized action. You do not have permission to get ticket for print.', 'ayuda-help-desk') ), 403);		
    }
	$ticket = isset( $_POST['id'] ) ? mumei_ayuda_get_ticket_by_id( sanitize_text_field( wp_unslash( $_POST['id'] ) ) ) : null;

	if ( ! empty( $ticket ) ) {

		$replies = mumei_ayuda_get_replies( $ticket->ID, 'any', [
            'posts_per_page' => - 1,
            'orderby'        => 'post_date',
            'order'          => mumei_ayuda_get_option( 'replies_order', 'ASC' ),
            'post_type'      => apply_filters( 'mumei_ayuda_replies_post_type', [
                'ticket_history',
                'ticket_reply'
			 ] ),
            'post_parent'    => $ticket->ID,
            'post_status'    => apply_filters( 'mumei_ayuda_replies_post_status', [
                'publish',
                'inherit',
                'private',
                'trash',
                'read',
                'unread'
			 ] )
		] );

		include MUMEI_AYUDA_PATH . 'includes/admin/views/print-ticket.php';

	} else {

		esc_html_e( 'Ticket not found', 'ayuda-help-desk' );

	}

	wp_die();

}

add_action( 'wp_ajax_mumei_ayuda_get_tickets_for_print', 'mumei_ayuda_get_tickets_for_print_ajax' );
/**
 * Get tickets for print
 *
 * @since 5.1.1
 *
 * @return void
 */
function mumei_ayuda_get_tickets_for_print_ajax() {

	check_ajax_referer( 'mumei_ayuda_print_ticket', 'nonce' );
	if ( ! current_user_can( 'edit_ticket' ) ) {
		wp_send_json_error( array('message' => __('Unauthorized action. You do not have permission to get tickets for print.', 'ayuda-help-desk') ), 403);		
    }
	$ids = isset( $_POST['ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['ids'] ) ) : array();
	
	foreach( $ids as $id ) {

		if ( ! empty( $ticket = mumei_ayuda_get_ticket_by_id( $id ) ) ) {

			$replies = mumei_ayuda_get_replies( $ticket->ID, 'any', [
				'posts_per_page' => - 1,
				'orderby'        => 'post_date',
				'order'          => mumei_ayuda_get_option( 'replies_order', 'ASC' ),
				'post_type'      => apply_filters( 'mumei_ayuda_replies_post_type', [
					'ticket_history',
					'ticket_reply'
				] ),
				'post_parent'    => $ticket->ID,
				'post_status'    => apply_filters( 'mumei_ayuda_replies_post_status', [
					'publish',
					'inherit',
					'private',
					'trash',
					'read',
					'unread'
				] )
			] );

			include MUMEI_AYUDA_PATH . 'includes/admin/views/print-ticket.php';

		} else {

			esc_html_e( 'Ticket not found', 'ayuda-help-desk' );

		}

	}

	wp_die();

}


add_action( 'wp_ajax_mumei_ayuda_close_ticket_prevent_client_notification', 'mumei_ayuda_ajax_close_ticket_prevent_client_notification' );
/**
 * Handle request to set client notification flag about ticket close
 */
function mumei_ayuda_ajax_close_ticket_prevent_client_notification() {

	$prevent_client_notification = filter_input( INPUT_POST, 'prevent',   FILTER_SANITIZE_NUMBER_INT );
	$ticket_id					 = filter_input( INPUT_POST, 'ticket_id', FILTER_SANITIZE_NUMBER_INT );


	if( !check_ajax_referer( 'prevent_client_notification', 'nonce', false ) || !current_user_can( 'edit_ticket' ) || !$ticket_id ) {
		wp_send_json_error( array( 'message' => "You don't have access to perform this action." ) );
		die();
	}

	update_post_meta( $ticket_id, 'mumei_ayuda_close_ticket_prevent_client_notification', $prevent_client_notification );

	wp_send_json_success();
}
