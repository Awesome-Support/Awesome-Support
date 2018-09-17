<?php

namespace WPAS_API\API;

use WP_REST_Controller;
use WP_REST_Users_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class used to get user id by user name
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class UserData extends WP_REST_Users_Controller {

	public function __construct() {

		parent::__construct();
		$this->namespace = wpas_api()->get_api_namespace();
		$this->rest_base = 'users';
    }

	
	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/username', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_user' ),
				'permission_callback' => array( $this, 'get_user_permissions_check' ),
				'args' => array(
					'username' => array(
						'type'        => 'string',
						'description' =>  __( 'User name', 'awesome-support' ),
						'required'    => true
					)
				)
			)
        ) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/check', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'check_credentials' ),
				'permission_callback' => array( $this, 'get_user_permissions_check' ),
				'args' => array(
					'username' => array(
						'type'        => 'string',
						'description' => __( 'User name', 'awesome-support' ),
						'required'    => true
					),
					'password' => array(
						'type'        => 'string',
						'description' => __( 'User password', 'awesome-support' ),
						'required'    => true
					)
				)
			)
		) );

    }
    
	/**
	 * Checks if a given request has access to list users or create ticket
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_user_permissions_check( $request ) {

        if ( current_user_can( 'list_users' ) or current_user_can( 'create_ticket' ) ) {
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
	public function get_user( $request ) {

		// Check if username is set
		if ( ! isset( $request[ 'username' ] ) ) {
			return new WP_Error( 'invalid_username', __( 'Invalid username.', 'awesome-support' ), array( 'status' => 400 ) );
		}

		$user = get_user_by( 'login',  $request[ 'username' ] );
		
		// Check result
        if ( ! $user ) {
            return new WP_Error( 'invalid_username', __( 'Invalid username.', 'awesome-support' ), array( 'status' => 400 ) );
		}

		// Check user ID
		if ( $user->ID != get_current_user_id() ) {
            return new WP_Error( 'invalid_username_access', __( 'You are not allowed to get user data', 'awesome-support' ), array( 'status' => 400 ) );
		}
		
		return array(
			'id' => $user->ID
		);

	}


	/**
	 * Check user credentials
	 */
	public function check_credentials( $request ) {

		// Check if username and password are set
		if ( ! isset( $request[ 'username' ] ) || ! isset( $request[ 'password' ] ) ) {
			return new WP_Error( 'invalid_user_credentials', __( 'Invalid username or password.', 'awesome-support' ), array( 'status' => 400 ) );
		}

		// Get user by username
		$user = get_user_by( 'login', $request[ 'username' ] );

		// Check the password for current logged in user
		if ( ! $user || ! wp_check_password( $request[ 'password' ], $user->data->user_pass, get_current_user_id() ) ) {
			return new WP_Error( 'invalid_user_credentials', __( 'Invalid username or password.', 'awesome-support' ), array( 'status' => 400 ) );
		}

		// Return user ID on success
		return array(
			'id' => $user->ID
		);

	}
    
}