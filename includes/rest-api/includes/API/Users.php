<?php

namespace WPAS_API\API;

use WPAS_API\Auth\User;
use WP_REST_Controller;
use WP_REST_Users_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Core class used to manage users via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class Users extends WP_REST_Users_Controller {

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct();
		$this->namespace = wpas_api()->get_api_namespace();
		$this->rest_base = 'users';
	}

	/**
	 * Retrieves all of the registered additional fields for a given object-type.
	 *
	 * @since 4.7.0
	 * @access protected
	 *
	 * @param  string $object_type Optional. The object type.
	 * @return array Registered additional fields (if any), empty array if none or if the object type could
	 *               not be inferred.
	 */
	protected function get_additional_fields( $object_type = null ) {
		$fields = parent::get_additional_fields( $object_type );

		$args = array(
			'get_callback'    => array( $this, 'get_field_callback' ),
			'update_callback' => array( $this, 'update_field_callback' ),
			'schema'          => array(
				'type'    => 'boolean',
				'context' => array( 'embed', 'view', 'edit' ),
			),
		);

		$args['schema']['description']  = __( 'Can the system assign new tickets to this user?', 'awesome-support' );
		$fields['wpas_can_be_assigned'] = $args;

		$args['schema']['description']      = __( 'If Smart Tickets Order is enabled, Awesome Support will display tickets that need immediate attention at the top.', 'awesome-support' );
		$fields['wpas_smart_tickets_order'] = $args;

		$args['schema']['description'] = __( 'If Smart Tickets Order is enabled, Awesome Support will display tickets that need immediate attention at the top.', 'awesome-support' );
		$args['schema']['enum']        = array( 'stay', 'back', 'ask' );
		$args['schema']['type']        = 'string';
		$fields['wpas_after_reply']    = $args;

		return $fields;
	}

	/**
	 * @param $object
	 * @param $field_name
	 * @param $request
	 * @param $object_type
	 *
	 * @return mixed
	 */
	public function get_field_callback( $object, $field_name, $request, $object_type ) {

		if ( 'wpas_has_smart_tickets_order' == $field_name ) {
			$value = wpas_has_smart_tickets_order( $object['id'] );
		} else {
			$value = get_user_meta( $object['id'], $field_name, true );
		}

		return $this->prepare_field_value( $value, $field_name );
	}

	public function update_field_callback( $arg, $object, $field_name, $request, $object_type ) {
		$value = $this->prepare_field_value( $arg, $field_name );
		return update_user_meta( $object->ID, $field_name, $value );
	}

	/**
	 * Cast the value with the correct type
	 *
	 * @param $value
	 * @param $field_name
	 *
	 * @return bool|float|int|null|string
	 */
	protected function prepare_field_value( $value, $field_name ) {
		$schema = $this->get_item_schema();

		if ( empty( $schema['properties'][ $field_name ]['type'] ) ) {
			$type = 'string';
		} else {
			$type = $schema['properties'][ $field_name ]['type'];
		}

		switch ( $type ) {
			case 'string':
				return (string) $value;
			case 'integer':
				return (int) $value;
			case 'number':
				return (float) $value;
			case 'boolean':
				return (bool) $value;
			default:
				return null;
		}

	}

}
