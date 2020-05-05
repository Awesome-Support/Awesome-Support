<?php

namespace WPAS_API\API;

use WPAS_API\Auth\User;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class used to manage a Ticket status
 *
 *
 * @see WP_REST_Controller
 */
class TicketStatus extends TicketBase {

	public function __construct() {
		$this->namespace = wpas_api()->get_api_namespace();
    }

	
	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */

	public function register_routes() {

		$ticket = get_post_type_object( 'ticket' );

		register_rest_route( $this->namespace, '/' . $ticket->rest_base . '/(?P<ticket_id>[\d]+)/status', array(
			'args' => array(
				'ticket_id' => array(
					'description' => __( 'Unique identifier for the ticket.' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_status' ),
				'permission_callback' => array( $this, 'update_status_user_permissions_check' ),
				'args' => array(
					'status' => array(
						'type'        => 'string',
						'description' => __( 'Ticket status', 'awesome-support' ),
						'required'    => true
					)
				)
			)

		) );


	}

    
	/**
	 * Checks if a given request has access to create ticket
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function update_status_user_permissions_check( $request ) {

        if ( current_user_can( 'create_ticket' ) && current_user_can( 'close_ticket' ) ) {
			return true;
		}

		return false;
    }

	/*
	* Retrieves the user ID.
	*
	* @param WP_REST_Request $request Full details about the request.
	* @return array on success, or WP_Error object on failure.
	*/
	public function update_status( $request ) {

		if ( ! isset( $request['status'] ) ) {
			return new WP_Error( 'invalid_status_parameter', __( 'Invalid status parameter', 'awesome-support' ), array( 'status' => 400 ) );
		}

		$post = get_post( intval( $request['ticket_id'] ) );

		if ( $post->post_author != get_current_user_id() ) {
			return new WP_Error( 'rest_cannot_create', __( 'Sorry, you are not allowed to update status of this ticket.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$status = ( $request['status'] == 'open' ) 
				? wpas_reopen_ticket( $request[ 'ticket_id' ] ) 
				: wpas_close_ticket( $request[ 'ticket_id' ] );

		if ( ! $status ) {
			return new WP_Error( 'ticket_status_error', __( 'Cannot change ticket status', 'awesome-support' ), array( 'status' => 400 ) );
		}

		return true;
	}

    
}