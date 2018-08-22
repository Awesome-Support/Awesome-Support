<?php

namespace WPAS_API\API;

use WPAS_API\API\TicketBase;
use WP_REST_Server;
use WP_REST_Posts_Controller;
use WP_Error;

class TicketReplies extends TicketBase {


	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		$ticket = get_post_type_object( 'ticket' );

		register_rest_route( $this->namespace, '/' . $ticket->rest_base . '/(?P<ticket_id>[\d]+)/' . $this->rest_base, array(
			'args' => array(
				'ticket_id' => array(
					'description' => __( 'Unique identifier for the ticket.' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		$get_item_args = array(
			'context'  => $this->get_context_param( array( 'default' => 'view' ) ),
		);

		register_rest_route($this->namespace, '/' . $ticket->rest_base . '/(?P<ticket_id>[\d]+)/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			'args' => array(
				'ticket_id' => array(
					'description' => __( 'Unique identifier for the ticket.' ),
					'type'        => 'integer',
					'required'    => true,
				),
				'id' => array(
					'description' => __( 'Unique identifier for the reply.' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => $get_item_args,
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Whether to bypass trash and force deletion.' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Adds the schema from additional fields to a schema array.
	 *
	 * The type of object is inferred from the passed schema.
	 *
	 * @param array $schema Schema array.
	 * @return array Modified Schema array.
	 */
	protected function add_additional_fields_schema( $schema ) {

		$remove_from_schema = array( 'link', 'template', 'password' );

		foreach ( $remove_from_schema as $remove ) {
			unset( $schema['properties'][ $remove ] );
		}

//		$schema['properties']['author'] = array(
//			'description' => __( 'The ID for the author of the object.' ),
//			'type'        => 'integer',
//			'context'     => array( 'view', 'edit', 'embed', 'template' ),
//		);
//
//		$schema['properties']['content'] = array(
//			'description' => __( 'The content for the object.' ),
//			'type'        => 'object',
//			'context'     => array( 'view', 'edit', 'embed' ),
//			'arg_options' => array(
//				'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database()
//			),
//			'properties'  => array(
//				'raw'       => array(
//					'description' => __( 'Content for the object, as it exists in the database.' ),
//					'type'        => 'string',
//					'context'     => array( 'edit' ),
//				),
//				'rendered'  => array(
//					'description' => __( 'HTML content for the object, transformed for display.' ),
//					'type'        => 'string',
//					'context'     => array( 'view', 'edit', 'embed' ),
//					'readonly'    => true,
//				),
//				'protected' => array(
//					'description' => __( 'Whether the content is protected with a password.' ),
//					'type'        => 'boolean',
//					'context'     => array( 'view', 'edit', 'embed' ),
//					'readonly'    => true,
//				),
//			),
//		);

		$schema['properties']['author'] = array(
			'description' => __( 'The ID for the author of the object.' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit', 'embed' ),
			'arg_options' => array(
				'default' => get_current_user_id(),
			),
		);

		$schema['properties']['parent'] = array(
			'description' => __( 'The ID for the ticket of the reply.' ),
			'type'        => 'integer',
			'readonly'    => true,
			'context'     => array( 'view', 'edit' ),
		);

		$schema['properties']['title']['readonly'] = true;

		$schema['properties']['slug']['readonly'] = true;
		$schema['properties']['content']['arg_options']['required'] = true;

		$schema['properties']['status']['enum'] = array( 'read', 'unread' );
		$schema['properties']['status']['arg_options']['default'] = 'unread';


		return parent::add_additional_fields_schema( $schema );
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['status']['items']['enum'] = array( 'read', 'unread', 'any' );
		$query_params['per_page']['default'] = 100;

		/**
		 * Filter collection parameters for the posts controller.
		 *
		 * @param array   $query_params JSON Schema-formatted collection parameters.
		 * @param object  Tickets
		 */
		return apply_filters( "wpas_api_{$this->rest_base}_collection_params", $query_params, $this );
	}

	/**
	 * Updates the values of additional fields added to a data object.
	 *
	 * @param \WP_Post         $object  Data Object.
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True on success, WP_Error object if a field cannot be updated.
	 */
	protected function update_additional_fields_for_object( $object, $request ) {

		$data = get_post( $object->ID, 'ARRAY_A' );

		/**
		 * Delete the activity transient.
		 */
		delete_transient( "wpas_activity_meta_post_" . $object->ID );

		/**
		 * Fire wpas_add_reply_after after the reply was successfully added.
		 */
		do_action( 'wpas_add_reply_after', $object->ID, $data );

		/**
		 * Fire wpas_add_reply_complete after the reply and attachments was successfully added.
		 */
		do_action( 'wpas_add_reply_complete', $object->ID, $data );

		return parent::update_additional_fields_for_object( $object, $request );
	}

	/**
	 * Prepares a single post for create or update.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \stdClass|WP_Error Post object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$defaults = $request->get_default_params();

		$defaults['parent'] = $request['ticket_id'];
		$defaults['title']  = sprintf( __( 'Reply to ticket %s', 'awesome-support' ), "#" . $request['ticket_id'] );
		$defaults['slug']  = sprintf( __( 'Reply to ticket %s', 'awesome-support' ), "#" . $request['ticket_id'] );

		$request->set_default_params( $defaults );

		return parent::prepare_item_for_database( $request );
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 4.7.0
	 * @access protected
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $post ) {
		$ticket          = get_post_type_object( 'ticket' );
		$ticket_id       = $post->post_parent;
		$base            = $this->namespace . '/' . $ticket->rest_base . '/' . $ticket_id . '/' . $this->rest_base;
		$attachments_url = rest_url( 'wp/v2/media' );
		$attachments_url = add_query_arg( 'parent', $post->ID, $attachments_url );

		// Entity meta.
		$links = array(
			'self'                         => array(
				'href' => rest_url( trailingslashit( $base ) . $post->ID ),
			),
			'collection'                   => array(
				'href' => rest_url( $base ),
			),
			'author'                       => array(
				'href'       => rest_url( $this->namespace . '/users/' . $post->post_author ),
				'embeddable' => true,
			),
			'https://api.w.org/attachment' => array(
				'href'       => $attachments_url,
				'embeddable' => true,
			),
			'about'                        => array(
				'href' => rest_url( 'wp/v2/types/' . $this->post_type ),
			),
		);

		return apply_filters( "wpas_api_{$this->rest_base}_prepare_links", $links, $post, $this );
	}


}