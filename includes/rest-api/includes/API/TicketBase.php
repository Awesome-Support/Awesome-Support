<?php

namespace WPAS_API\API;

use WP_REST_Server;
use WP_REST_Posts_Controller;
use WP_Error;
use WP_REST_Post_Meta_Fields;
use JsonSerializable;

class TicketBase extends WP_REST_Posts_Controller {


	public function __construct( $post_type ) {
		parent::__construct( $post_type );

		$this->meta = new WP_REST_Post_Meta_Fields( $this->post_type );
		$this->namespace = wpas_api()->get_api_namespace();
	}

	/**
	 * Prepare item query and add ticket ID if it exists
	 *
	 * @param array $prepared_args
	 * @param null  $request
	 *
	 * @return array
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = parent::prepare_items_query( $prepared_args, $request );

		if ( $request['ticket_id'] ) {
			$query_args['post_parent'] = absint( $request['ticket_id'] );
		}

		return apply_filters( "wpas_api_{$this->rest_base}_prepare_items_query", $query_args, $prepared_args, $request, $this );
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to items assigned one or more statuses.', 'awesome-support' ),
			'type'              => 'array',
			'items'             => array(
				'enum'          =>  array( 'read', 'unread' ),
				'type'          => 'string',
			),
			'sanitize_callback' => array( $this, 'sanitize_ticket_param' ),
		);

		/**
		 * Filter collection parameters for the posts controller.
		 *
		 * @param array   $query_params JSON Schema-formatted collection parameters.
		 * @param object  Tickets
		 */
		return apply_filters( "wpas_api_{$this->rest_base}_collection_params", $query_params, $this );
	}

	/**
	 * Sanitizes and validates a list of arguments against the provided attributes.
	 *
	 * @param  string|array    $value  One or more post statuses.
	 * @param  \WP_REST_Request $request   Full details about the request.
	 * @param  string          $parameter Additional parameter to pass to validation.
	 * @return array|WP_Error A list of valid statuses, otherwise WP_Error object.
	 */
	public function sanitize_ticket_param( $value, $request, $parameter ) {
		// The default status is different in WP_REST_Attachments_Controller
		$attributes = $request->get_attributes();
		$default    = isset( $attributes['args'][ $parameter ]['default'] ) ? $attributes['args'][ $parameter ]['default'] : '' ;

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $this->sanitize_ticket_param( $value, $request, $parameter );
			}
		} else {
			if ( $value !== $default ) {
				$result = rest_validate_request_arg( $value, $request, $parameter );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		return apply_filters( "wpas_api_{$this->rest_base}_sanitize_ticket_param", $value, $request, $parameter, $this );
	}

	/**
	 * Checks if a ticket can be read by the current user
	 *
	 * Correctly handles posts with the inherit status.
	 *
	 * @param \WP_Post $post
	 * @return bool Whether the post can be read.
	 */
	public function check_read_permission( $post ) {

		// make sure we are dealing with the ticket
		if ( 'ticket' != $post->post_type ) {
			$post = get_post( $post->post_parent );
		}

		$return = wpas_can_view_ticket( $post->ID );

		if ( 'public' === get_post_meta( $post->ID , '_wpas_pbtk_flag', true ) ) {
			$return = true;
		}

		return apply_filters( 'wpas_api_check_ticket_read_permission', $return, $post, $this );
	}

	/**
	 * Checks if a given request has access to create a post.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'rest_post_exists', __( 'Cannot create existing post.' ), array( 'status' => 400 ) );
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( ! empty( $request['author'] ) && get_current_user_id() !== $request['author'] && ! current_user_can( $post_type->cap->edit_others_posts ) ) {
			return new WP_Error( 'rest_cannot_edit_others', __( 'Sorry, you are not allowed to create tickets as this user.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( ! current_user_can( 'create_ticket' ) ) {
			return new WP_Error( 'rest_cannot_create', __( 'Sorry, you are not allowed to create tickets as this user.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( ! $this->check_assign_terms_permission( $request ) ) {
			return new WP_Error( 'rest_cannot_assign_term', __( 'Sorry, you are not allowed to assign the provided terms.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Are we creating a brand new item?
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function is_item_new( $request ) {
		return ( null === $request['id'] );
	}

	/**
	 * Prepares a meta value for output.
	 *
	 * Default preparation for meta fields. Override by passing the
	 * `prepare_callback` in your `show_in_rest` options.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param mixed           $value   Meta value from the database.
	 * @param array           $args    REST-specific options for the meta key.
	 * @return mixed Value prepared for output. If a non-JsonSerializable object, null.
	 */
	public static function prepare_value( $value, $args ) {
		$type = $args['type'];

		// For multi-value fields, check the item type instead.
		if ( 'array' === $type && ! empty( $args['items']['type'] ) ) {
			$type = $args['schema']['items']['type'];
		}

		switch ( $type ) {
			case 'string':
				$value = (string) $value;
				break;
			case 'integer':
				$value = (int) $value;
				break;
			case 'number':
				$value = (float) $value;
				break;
			case 'boolean':
				$value = (bool) $value;
				break;
		}

		// Don't allow objects to be output.
		if ( is_object( $value ) && ! ( $value instanceof JsonSerializable ) ) {
			return null;
		}

		return apply_filters( 'wpas_api_tickets_prepare_value', $value, $args );
	}

}