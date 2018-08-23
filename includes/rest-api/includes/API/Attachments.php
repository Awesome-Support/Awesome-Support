<?php

namespace WPAS_API\API;

use WP_REST_Request;
use WP_Error;
use WP_REST_Attachments_Controller;
use WPAS_File_Upload;

/**
 * Core class used to manage a site's settings via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Attachments_Controller
 */
class Attachments extends WP_REST_Attachments_Controller {

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct( 'attachment' );

		$this->namespace = wpas_api()->get_api_namespace();
		$this->rest_base = 'attachments';
	}

	/**
	 * Checks if a given request has access to create an attachment.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|true Boolean true if the attachment may be created, or a WP_Error if not.
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! current_user_can( 'create_ticket' ) ) {
			return new WP_Error( 'rest_cannot_create', __( 'Sorry, you are not allowed to upload media on this site.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
		}

		/*
		// Attaching media to a post requires ability to edit said post.
		if ( empty( $request['post'] ) ) {
			return new WP_Error( 'rest_cannot_create', __( 'Sorry, you are only allowed to upload media to a ticket.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
		}
		*/

		if ( ! empty( $request[ 'post' ] ) ) {

			$parent = get_post( (int) $request['post'] );

			if ( $parent->post_author != get_current_user_id() ) {
				return new WP_Error( 'rest_cannot_create', __( 'Sorry, you are not allowed to upload media to this ticket.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
			}

		} 


		return true;
	}

	public function create_item( $request ) {
		$upload = new WPAS_File_Upload();
		$upload->post_id = $request['post'];

		return parent::create_item( $request );
	}

}