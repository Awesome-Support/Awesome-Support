<?php

namespace WPAS_API\API;

use WPAS_API\Auth\User;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class used to manage a user's API Passwords via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class Passwords extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 * @access public
	 */
	public function __construct() {
		$this->namespace = wpas_api()->get_api_namespace();
		$this->rest_base = 'passwords';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/users/(?P<user_id>[\d]+)/' . $this->rest_base, array(
			'args' => array(
				'user_id' => array(
					'description' => __( 'The ID of the requested user.', 'awesome-support' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_passwords_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				'permission_callback' => array( $this, 'get_passwords_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_all_items' ),
				'permission_callback' => array( $this, 'get_passwords_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/users/(?P<user_id>[\d]+)/' . $this->rest_base . '/(?P<slug>[\da-fA-F]{12})', array(
			'args' => array(
				'user_id' => array(
					'description' => __( 'The ID of the requested user.', 'awesome-support' ),
					'type'        => 'integer',
					'required'    => true,
				),
				'slug' => array(
					'description' => __( 'The slug of the password to delete.', 'awesome-support' ),
					'type'        => 'string',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'get_passwords_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		// Some hosts that run PHP in FastCGI mode won't be given the Authentication header.
		register_rest_route( $this->namespace, '/test-basic-authorization-header/', array(
			array(
				'methods' => WP_REST_Server::READABLE . ', ' . WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'test_basic_authorization_header' ),
			),
			'schema' => array( $this, 'test_schema' ),
		) );

	}

	/**
	 * Checks if a given request has access to read and manage the user's passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_passwords_permissions_check( $request ) {
		$check = empty( $request['user_id'] ) ? current_user_can( 'edit_users' ) : current_user_can( 'edit_user', $request['user_id'] );
		return apply_filters( 'wpas_api_get_password_permissions_check', $check, $request );
	}

	/**
	 * Retrieves the passwords.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$user = new User( $request['user_id'] );
		$api_passwords = $user->get_api_passwords();
		$response = array();

		foreach ( $api_passwords as $item ) {
			$item['slug'] = User::password_unique_slug( $item );
			unset( $item['raw'] );
			unset( $item['password'] );

			$item['created'] = date( get_option( 'date_format', 'r' ), $item['created'] );

			if ( empty( $item['last_used'] ) ) {
				$item['last_used'] =  '—';
			} else {
				$item['last_used'] = date( get_option( 'date_format', 'r' ), $item['last_used'] );
			}

			if ( empty( $item['last_ip'] ) ) {
				$item['last_ip'] =  '—';
			}

			$response[ $item['slug'] ] = $item;
		}

		return $response;
	}

	/**
	 * Create a password for the provided user
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or error object on failure.
	 */
	public function create_item( $request ) {
		$user = new User( $request['user_id'] );

		if ( empty( $request['name'] ) ) {
			return new WP_Error( 'no-name', __( 'Please provide a name to use for the new password.', 'awesome-support' ), array( 'status' => 404 ) );
		}

		$new_item = $user->create_new_api_password( $request['name'] );

		// Some tidying before we return it.
		$new_item['created']   = date( get_option( 'date_format', 'r' ), $new_item['created'] );
		$new_item['last_used'] = '—';
		$new_item['last_ip']   = '—';

		return $new_item;
	}

	/**
	 * Delete a password for the provided user
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or error object on failure.
	 */
	public function delete_item( $request ) {
		$user = new User( $request['user_id'] );
		$slug = $request['slug'];

		if ( ! $item = $user->get_api_password( $slug ) ) {
			return new WP_Error( 'no-item-found', __( 'No password was found with that slug.', 'awesome-support' ), array( 'status' => 404 ) );
		}

		if ( $user->delete_api_password( $slug ) ) {
			return array( 'deleted' => true, 'previous' => $item );
		} else {
			return new WP_Error( 'no-item-found', __( 'No password was found with that slug.', 'awesome-support' ), array( 'status' => 404 ) );
		}

	}

	/**
	 * Delete all api passwords for the provided user
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Array on success
	 */
	public function delete_all_items( $request ) {
		$user  = new User( $request['user_id'] );
		$items = $this->get_items( $request );

		$user->delete_all_api_passwords();

		return array( 'deleted' => true, 'previous' => $items );
	}

	/**
	 * Retrieves the site setting schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/schema#',
			'title'      => 'password',
			'type'       => 'object',
			'properties' => array(
				'name'      => array(
					'description' => __( "The name of the new password" ),
					'required'    => true,
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'password'  => array(
					'description' => __( "The hashed password that was created" ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'created'   => array(
					'description' => __( 'The date the password was created' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'last_used' => array(
					'description' => __( 'The date the password was last used' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'last_ip'   => array(
					'description' => __( 'The IP address that the password was last used from' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'      => array(
					'description' => __( 'The password\'s unique slug' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Schema for Basic Auth test
	 *
	 * @return array
	 */
	public function test_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/schema#',
			'title'      => 'authentication-test',
			'type'       => 'object',
			'properties' => array(
				'PHP_AUTH_USER' => array(
					'description' => __( 'The user to be authenticated', 'awesome-support' ),
					'type'        => 'string'
				),
				'PHP_AUTH_PW'   => array(
					'description' => __( 'The authentication password', 'awesome-support' ),
					'type'        => 'string'
				),
			),
		);
	}

	/**
	 * Test whether PHP can see Basic Authorization headers passed to the web server.
	 *
	 * @return WP_Error|array
	 */

	public function test_basic_authorization_header() {
		$response = array();

		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			$response['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
		}

		if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$response['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
		}

		if ( empty( $response ) ) {
			return new WP_Error( 'no-credentials', __( 'No HTTP Basic Authorization credentials were found submitted with this request.', 'awesome-support' ), array( 'status' => 404 ) );
		}

		return $response;
	}
}
