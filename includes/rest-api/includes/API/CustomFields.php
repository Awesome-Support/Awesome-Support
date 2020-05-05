<?php

namespace WPAS_API\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WPAS_Custom_Field;


/**
 * Class used to get custom fields
 * 
 */
class CustomFields extends WP_REST_Controller {

	public function __construct() {

		$this->namespace = wpas_api()->get_api_namespace();
		$this->rest_base = 'tickets';
    }

	
	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

        // Get all custom fields
        register_rest_route( $this->namespace, '/custom-fields', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_custom_fields' ),
                'permission_callback' => array( $this, 'get_custom_fields_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::READABLE ) 
            ),
            'schema' => array( $this, 'get_public_item_schema' )
        ) );


        // Get ticket custom fields
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<ticket_id>[\d]+)/custom-fields', array(
            'args' => array(
				'ticket_id' => array(
					'description' => __( 'Unique ticket identifier.' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_custom_fields' ),
                'permission_callback' => array( $this, 'get_custom_fields_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::READABLE ) 
            ),
            'schema' => array( $this, 'get_public_item_schema' ) 
        ) );

        // Update custom fields
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<ticket_id>[\d]+)/custom-fields', array(
            'args' => array(
				'ticket_id' => array(
					'description' => __( 'Unique ticket identifier.' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'update_custom_fields' ),
                'permission_callback' => array( $this, 'get_custom_fields_permissions_check' ),
                'args' => array( 
                    'custom_fields' => array(
                        'required'    => true,
                        'description' => 'List of custom fields',
                        'type'        => 'array'
                    )
                )
            )
        ) );

    }
    
	/**
	 * Checks if a given request has access to create a ticket
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_custom_fields_permissions_check( $request ) {
        return current_user_can( 'create_ticket' );
    }

    /**
     * Get custom fields
     *
     * @return array
     */
    protected function get_fields() {

        $fields = array();

        // custom fields to skip
        // these fields are used in the admin part only
        $skip = array(
            'id',
            'status',
            'assignee',
            'wpas-client',
            'time_adjustments_pos_or_neg',
            'wpas-activity',
            'ttl_replies_by_agent',
            'ttl_calculated_time_spent_on_ticket',
            'ttl_adjustments_to_time_spent_on_ticket',
            'final_time_spent_on_ticket',
            'first_addl_interested_party_name',
            'first_addl_interested_party_email',
            'second_addl_interested_party_name',
            'second_addl_interested_party_email'
        );

        $skip_fields   = apply_filters( 'wpas_api_custom_fields_filter', $skip );
        $custom_fields = WPAS()->custom_fields->get_custom_fields(); 

        foreach ( $custom_fields as $field => $data ) {
            // check field
            if ( in_array( $field, $skip_fields ) ) {
                continue;
            }

            switch( $data[ 'args' ][ 'field_type' ] ) {
				case 'text' :
				case 'url' :
				case 'email' :
				case 'number' :
				case 'date-field' :
				case 'password' :
				case 'upload' :
				case 'select' :
				case 'radio' :
				case 'textarea' :
				case 'wysiwyg' :
				case 'taxonomy' :
					$type = 'string';
					break;
				case 'checkbox' :
					$type = 'boolean';
					break;
				default:
					$type = false;
            }
            

            $data[ 'schema' ] = array(
				'type'        => $type,
				'description' => empty( $data[ 'args' ]['desc'] ) ? '' : $data[ 'args' ]['desc'],
				'default'     => isset( $data[ 'args' ]['default']  ) ? $data[ 'args' ]['default']  : null,
			);

            $fields[ $field ] = $data;
        }

        return $fields;

    }


	/*
	* Retrieves the custom fields
	*
	* @param WP_REST_Request $request Full details about the request.
	* @return array on success, or WP_Error object on failure.
	*/
	public function get_custom_fields( $request ) {

        // Check for ticket id
        if ( isset( $request['ticket_id'] ) ) {

            if ( ! $this->is_user_ticket(  $request[ 'ticket_id' ] ) ) {
                return new WP_Error( 'rest_cannot_create', __( 'Sorry, you are not allowed to get custom fields of this ticket.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
            }    

        } else {

            $request['ticket_id'] = 0;

        }

        foreach ( $this->get_fields() as $field => $data ) {

            $custom_field = new WPAS_Custom_Field( $field, $data );

            $fields[ $field ] = array(
                'name'   => $custom_field->get_field_title(),
                'value'  => $custom_field->get_field_value( '', $request['ticket_id'] ),
                'markup' => $custom_field->get_output()
            );

        }

        return $fields;

    }


    /**
     * Update custom fields
     *
    * @param WP_REST_Request $request Full details about the request.
    * @return array on success, or WP_Error object on failure.
    */
    public function update_custom_fields( $request ) {

        if ( ! isset( $request[ 'custom_fields' ] ) || empty( $request[ 'custom_fields' ] ) ) {
            return new WP_Error( 'invalid_post_parameter', __( 'Custom fields parameter cannot be empty.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
        }

        if ( ! $this->is_user_ticket( $request[ 'ticket_id' ] ) ) {
            return new WP_Error( 'rest_cannot_create', __( 'Sorry, you are not allowed to update custom fields for this ticket.', 'awesome-support' ), array( 'status' => rest_authorization_required_code() ) );
        } 

        foreach ( $this->get_fields() as $field => $data ) {

            if ( array_key_exists( 'wpas_' . $field, $request[ 'custom_fields' ] ) ) {

                $custom_field = new WPAS_Custom_Field( $field, $data );
                $custom_field->update_value( $request[ 'custom_fields' ][ 'wpas_' . $field ], $request[ 'ticket_id' ] );
                
            }
    
        }

    }


    /**
     * Check if ticket author is current logged in user
     *
     * @param int $ticket_id
     * @return boolean
     */
    private function is_user_ticket( $ticket_id ) {

        $post = get_post( intval( $ticket_id ) );

        if ( $post ) {
            return ( $post->post_author == get_current_user_id() ) ? true : false;
        }

        return false;

    }
    

    /**
	 * Retrieves the custom fields schema
	 *
	 * @return array.
	 */
	public function get_item_schema() {

		$fields = $this->get_fields();

		$schema = array(
			'$schema'    => 'http://json-schema.org/schema#',
			'title'      => 'custom-field',
			'type'       => 'object',
			'properties' => array(),
		);

		foreach ( $fields as $field_name => $option ) {

			$schema['properties'][ $field_name ] = $option['schema'];
			$schema['properties'][ $field_name ]['arg_options'] = array(
				'sanitize_callback' => array( $this, 'sanitize_callback' ),
			);
		}

		return $this->add_additional_fields_schema( $schema );
	}
    
	/**
	 * Custom sanitize callback used for all options to allow the use of 'null'.
	 *
	 * By default, the schema of settings will throw an error if a value is set to
	 * `null` as it's not a valid value for something like "type => string". We
	 * provide a wrapper sanitizer to whitelist the use of `null`.
	 *
	 * @param  mixed           $value   The value for the setting.
	 * @param  WP_REST_Request $request The request object.
	 * @param  string          $param   The parameter name.
	 * @return mixed|WP_Error
	 */
	public function sanitize_callback( $value, $request, $param ) {

		if ( is_null( $value ) ) {
			return $value;
		}

		return rest_parse_request_arg( $value, $request, $param );
	}
    
}