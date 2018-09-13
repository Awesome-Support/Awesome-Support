<?php

namespace WPAS_API\API;

use WPAS_API\API\TicketBase;
use WP_REST_Posts_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPAS_Custom_Field;

class Tickets extends TicketBase {

	/**
	 * Store log information
	 *
	 * @var array
	 */
	protected $log = array();

	/**
	 * Prepares a single post output for response.
	 *
	 * @param \WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$post = get_post( $post->ID );
		return parent::prepare_item_for_response( $post, $request );
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
		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $schema['properties'][ $base ] ) || 'ticket-tag' == $base ) {
				continue;
			}

			unset( $schema['properties'][ $base ]['items'] );
			$schema['properties'][ $base ]['type'] = 'string';
		}

		// unset properties
		foreach( array( 'template', 'password' ) as $key ) {
			unset( $schema['properties'][ $key ] );
		}

		$schema['properties']['title']['arg_options']['required'] = true;
		$schema['properties']['content']['arg_options']['required'] = true;
		$schema['properties']['status']['arg_options']['default'] = 'queued';
		$schema['properties']['ticket_channel']['arg_options']['default'] = 'web-service-api';

		foreach( $this->get_additional_ticket_fields() as $key => $data ) {
			$schema['properties'][ $key ] = $data;
		}

		return parent::add_additional_fields_schema( $schema );
	}

	/**
	 * Adds the values from additional fields to a data object.
	 *
	 * @param array           $data  Data object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Modified data object with additional fields.
	 */
	protected function add_additional_fields_to_object( $data, $request ) {
		$taxonomies    = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );
		$schema        =    $this->get_item_schema();
		$custom_fields = WPAS()->custom_fields->get_custom_fields();

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( empty( $schema['properties'][ $base ] ) || empty( $custom_fields[ $base ] ) || 'ticket-tag' == $base ) {
				continue;
			}

			$field         = new WPAS_Custom_Field( $base, $custom_fields[ $base ] );
			$data[ $base ] = $field->get_field_value( '', $data['id'] );
		}

		foreach( $this->get_additional_ticket_fields() as $key => $field_data ) {
			if ( empty( $field_data['field_key'] ) ) {
				continue;
			}

			$value = wpas_get_cf_value( $field_data['field_key'], $data['id'] );

			$data[ $key ] = self::prepare_value( $value, $field_data );
		}

		$data = parent::add_additional_fields_to_object( $data, $request );

		return $data;
	}

	/**
	 * Determines the allowed query_vars for a get_items() response and prepares
	 * them for WP_Query.
	 *
	 * @param array           $prepared_args Optional. Prepared WP_Query arguments. Default empty array.
	 * @param \WP_REST_Request $request       Optional. Full details about the request.
	 * @return array Items query arguments.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = parent::prepare_items_query( $prepared_args, $request );

		if ( empty( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
		}

		foreach ( $this->get_additional_ticket_fields() as $field ) {
			if ( isset( $field['query_cb'] ) && ! is_bool( $field['query_cb'] ) ) {
				$query_args = call_user_func( $field['query_cb'], $query_args, $request );
			}
		}

		if ( ! isset( $meta_query['relation'] ) ) {
			$meta_query['relation'] = 'AND';
		}

		return apply_filters( "wpas_api_{$this->rest_base}_prepare_items_query", $query_args, $prepared_args, $request, $this );
	}

	/**
	 * Retrieves the query params for the tickets collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		$user         = wp_get_current_user();

		foreach( $this->get_additional_ticket_fields() as $key => $data ) {
			if ( ! isset( $data['query_cb'] ) ) {
				continue;
			}

			$query_params[ $key ] = $data;
		}

		// if the user is logged in then set the assignee to the user ID if the user is an agent
		// if the user is not an agent then set the default author to the user ID to show the user's tickets
		if ( $user->has_cap( 'edit_ticket' ) ) {
			$query_params['assignee']['default'] = $user->ID;
		} elseif ( $user->ID ) {
			$query_params['author']['default'] = $user->ID;
		}

		if ( isset( $_GET['context'] ) && 'help' == $_GET['context'] ) {
			$query_params['assignee']['default'] = __( 'The ID of the current logged in agent if applicable.', 'awesome-support' );
			$query_params['author']['default'] = __( 'The ID of the current logged in client if applicable.', 'awesome-support' );
		}

		$query_params['status']['items']['enum'] = array_merge( array_keys( wpas_get_post_status() ), array( 'read', 'unread', 'any' ) );

		/**
		 * Filter collection parameters for the posts controller.
		 *
		 * @param array   $query_params JSON Schema-formatted collection parameters.
		 * @param object  Tickets
		 */
		return apply_filters( "wpas_api_{$this->rest_base}_get_collection_params", $query_params, $this );
	}

	/**
	 * Updates the post's terms from a REST request.
	 *
	 * @param int             $post_id The post ID to update the terms form.
	 * @param WP_REST_Request $request The request object with post and terms data.
	 * @return null|WP_Error WP_Error on an error assigning any of the terms, otherwise null.
	 */
	protected function handle_terms( $post_id, $request ) {
		$taxonomies    = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );
		$custom_fields = WPAS()->custom_fields->get_custom_fields();

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $custom_fields[ $base ] ) || null === $request[ $base ] ) {
				continue;
			}

			if ( 'ticket-tag' == $base ) {
				$result = wp_set_object_terms( $post_id, $request[ $base ], $taxonomy->name );

				if ( is_wp_error( $result ) ) {
					return $result;
				}
			} else {
				$term      = $request[ $base ];
				$get_field = is_numeric( $term ) ? 'id' : 'slug';

				if ( ! $term = get_term_by( $get_field, $request[ $base ], $base ) ) {
					return new WP_Error( 'invalid_term', sprintf( __( 'That %s term does not exist.', 'awesome-support' ), $base ) );
				}

				$field  = new WPAS_Custom_Field( $base, $custom_fields[ $base ] );
				$result = $field->update_value( $term->term_id, $post_id );

				if ( 4 == $result ) {
					return new WP_Error( 'rest_cannot_edit', __( 'Sorry, you are not allowed to edit this post.', 'awesome-support' ), array( 'status' => 401 ) );
				}

				$this->maybe_update_log( $field, $result, $post_id );
			}

		}

		return null;
	}

	/**
	 * Checks whether current user can assign all terms sent with the current request.
	 *
	 * @param WP_REST_Request $request The request object with post and terms data.
	 * @return bool Whether the current user can assign the provided terms.
	 */
	protected function check_assign_terms_permission( $request ) {
		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );
		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! isset( $request[ $base ] ) ) {
				continue;
			}

			$get_field = is_int( $request[ $base ] ) ? 'id' : 'slug';
			if ( ! $term = get_term_by( $get_field, $request[ $base ], $base ) ) {
				continue;
			}

			if ( ! current_user_can( 'assign_term', (int) $term->term_id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Updates the values of additional fields added to a data object.
	 *
	 * @param \WP_Post         $object  Data Object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True on success, WP_Error object if a field cannot be updated.
	 */
	protected function update_additional_fields_for_object( $object, $request ) {
		$additional_fields = $this->get_additional_ticket_fields();

		// if we just created a new ticket, set the slug
		if ( $this->is_item_new( $request ) ) {
			wpas_set_ticket_slug( $object->ID );
		}

		foreach ( $additional_fields as $field_name => $field_options ) {

			// Don't run the update callbacks if the data wasn't passed in the request.
			if ( null === $request[ $field_name ] ) {
				continue;
			}

			if ( ! isset( $field_options['field_key'] ) ) {
				continue;
			}

			$value = $request[ $field_name ];

			if ( isset( $field_options['sanitize_cb'] ) ) {
				$value = call_user_func( $field_options['sanitize_cb'], $request[ $field_name ], $object, $field_name, $request );
			}

			if ( isset( $field_options['update_cb'] ) ) {
				$result = call_user_func( $field_options['update_cb'], $request[ $field_name ], $object, $field_name, $request );
			} else {
				$result = $this->update_custom_field( $field_options['field_key'], $value, $object->ID );
			}

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		do_action( 'wpas_api_tickets_update_additional_fields_after', $object, $request );

		if ( $this->is_item_new( $request ) ) {
			do_action( 'wpas_open_ticket_after', $object->ID, get_post( $object->ID, 'ARRAY_A' ) );
		}

		return parent::update_additional_fields_for_object( $object, $request );
	}

	/**
	 * Prepares links for the request.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $post ) {
		$history         = get_post_type_object( 'ticket_history' );
		$replies         = get_post_type_object( 'ticket_reply' );
		$base            = sprintf( '%s/%s', $this->namespace, $this->rest_base );
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
			'replies'                      => array(
				'href'       => rest_url( trailingslashit( $base ) . $post->ID . '/' . $replies->rest_base ),
				'embeddable' => true,
			),
			'history'                      => array(
				'href'       => rest_url( trailingslashit( $base ) . $post->ID . '/' . $history->rest_base ),
				'embeddable' => true,
			),
			'author'                       => array(
				'href'       => rest_url( $this->namespace . '/users/' . $post->post_author ),
				'embeddable' => true,
			),
			'assignee'                     => array(
				'href'       => rest_url( $this->namespace . '/users/' . get_post_meta( $post->ID, '_wpas_assignee', true ) ),
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

		$taxonomies = get_object_taxonomies( $post->post_type );

		if ( ! empty( $taxonomies ) ) {
			$links['https://api.w.org/term'] = array();

			foreach ( $taxonomies as $tax ) {
				$taxonomy_obj = get_taxonomy( $tax );

				// Skip taxonomies that are not public.
				if ( empty( $taxonomy_obj->show_in_rest ) ) {
					continue;
				}

				$tax_base = ! empty( $taxonomy_obj->rest_base ) ? $taxonomy_obj->rest_base : $tax;

				$terms_url = add_query_arg(
					'post',
					$post->ID,
					rest_url( $this->namespace . '/' . $tax_base )
				);

				$links['https://api.w.org/term'][] = array(
					'href'       => $terms_url,
					'taxonomy'   => $tax,
					'embeddable' => true,
				);
			}
		}

		return apply_filters( "wpas_api_{$this->rest_base}_prepare_links", $links, $post, $this );
	}

	/** Callback Functions **************************/

	/**
	 * Add state query params
	 *
	 * @param $query_args
	 * @param $request
	 *
	 * @return mixed
	 */
	public function query_state( $query_args, $request ) {
		$state = empty( $request['state'] ) ? 'open' : $request['state'];
		$meta_query = array();

		if ( in_array( $state, array( 'any', 'open' ) ) ) {
			$meta_query[] = array(
				'key'     => '_wpas_status',
				'value'   => 'open',
				'compare' => '=',
				'type'    => 'CHAR',
			);
		}

		if ( in_array( $state, array( 'any', 'closed' ) ) ) {
			$meta_query[] = array(
				'key'     => '_wpas_status',
				'value'   => 'closed',
				'compare' => '=',
				'type'    => 'CHAR',
			);
		}

		if ( 'any' === $state ) {
			$meta_query['relation'] = 'OR';
			$query_args['meta_query'][] = $meta_query;
		} else {
			$query_args['meta_query'] += $meta_query;
		}

		return $query_args;
	}

	/**
	 * Add assignee query params
	 *
	 * @param $query_args
	 * @param $request
	 *
	 * @return mixed
	 */
	public function query_assignee( $query_args, $request ) {

		if ( empty( $request['assignee'] ) ) {
			return $query_args;
		}

		// set assignee
		$query_args['meta_query'][] = array(
			'key'     => '_wpas_assignee',
			'value'   => absint( $request['assignee'] ),
			'compare' => '=',
			'type'    => 'NUMERIC',
		);

		return $query_args;
	}

	/**
	 * Format the time in preparation for the native handling of the time
	 *
	 * @param $value
	 *
	 * @return string $value
	 */
	public function format_time( $value ) {
		$value = sprintf( '%s:%s', intval( $value / 60 ), $value % 60 );

		add_action( 'wpas_api_tickets_update_additional_fields_after', array( $this, 'update_time_spent_on_ticket' ) );

		return $value;
	}

	/**
	 * Setup time calculation for ticket
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	public function setup_time_calculate( $value ) {
		add_action( 'wpas_api_tickets_update_additional_fields_after', array( $this, 'update_time_spent_on_ticket' ) );
		return $value;
	}

	/**
	 * Add action to post_status transition for log
	 *
	 * @param string $post_status Post status.
	 * @param object $post_type   Post type.
	 *
	 * @return string Post status
	 */
	protected function handle_status_param( $post_status, $post_type ) {
		add_action( 'transition_post_status', '\WPAS_API\API\Tickets::record_post_status_transition', 10, 3 );

		return $post_status;
	}

	/**
	 * Update Ticket state
	 *
	 * @param $value
	 * @param \WP_Post $object
	 *
	 * @return bool|int|string|WP_Error
	 */
	public function update_state( $value, $object, $field_name, $request ) {
		$return = '';

		// if this is a new ticket, just save the field
		if ( $this->is_item_new( $request ) ) {
			return update_post_meta( $object->ID, '_wpas_status', $value );
		}

		$state = wpas_get_ticket_status( $object->ID );

		// break early if there is nothing to change
		if ( $state == $value ) {
			return $return;
		}

		if ( 'open' == $value ) {
			if ( ! current_user_can( 'edit_ticket' ) && ! wpas_can_submit_ticket( $object->ID ) ) {
				return new WP_Error( 'cannot_open_ticket', __( 'You do not have the capacity to open this ticket', 'awesome-support' ) );
			}

			$return = wpas_reopen_ticket( $object->ID );
		} elseif( 'closed' == $value ) {
			if ( ! current_user_can( 'close_ticket' ) ) {
				return new WP_Error( 'cannot_close_ticket', __( 'You do not have the capacity to close this ticket', 'awesome-support' ) );
			}

			$return = wpas_close_ticket( $object->ID );
		}

		return $return;
	}

	/**
	 * Use prebuilt function to update assignee
	 *
	 * @param $value
	 * @param \WP_Post $object
	 *
	 * @return bool|int|object
	 */
	public function update_assignee( $value, $object, $field_name, $request ) {
		$log = true;

		if ( $this->is_item_new( $request ) ) {

			if ( ! $value ) {
				$value = wpas_find_agent();
			}

			$value = apply_filters( 'wpas_new_ticket_agent_id', $value, $object->ID, $value );
			$log = false;
		}

		return wpas_assign_ticket( $object->ID, (string) $value, $log );
	}

	/** Helper Functions **************************/

	/**
	 * Update custom field value
	 *
	 * @param $field_key
	 * @param $value
	 * @param $ticket_id
	 *
	 * @return bool|int|WP_Error
	 */
	public function update_custom_field( $field_key, $value, $ticket_id ) {
		$custom_fields = WPAS()->custom_fields->get_custom_fields();

		if ( empty( $custom_fields[ $field_key ] ) ) {
			return false;
		}

		$field  = new WPAS_Custom_Field( $field_key, $custom_fields[ $field_key ] );
		$result = $field->update_value( $value, $ticket_id );

		$this->maybe_update_log( $field, $result, $ticket_id );

		if ( 4 == $result ) {
			$result = new WP_Error( 'rest_cannot_edit', __( 'Sorry, you are not allowed to edit this ticket.', 'awesome-support' ), array( 'status' => 401 ) );
		}

		return $result;
	}

	/**
	 * Update the total time of a ticket
	 *
	 * @todo if you update this functionality, be sure to do the same in the core plugin plugin in /includes/custom-fields/functions-custom-fields.php on line 169
	 *
	 * @param $ticket
	 */
	public function update_time_spent_on_ticket( $ticket ) {
		$custom_fields = WPAS()->custom_fields->get_custom_fields();
		$calculated    = wpas_get_cf_value( 'ttl_calculated_time_spent_on_ticket', $ticket->ID );
		$adjustment    = wpas_get_cf_value( 'ttl_adjustments_to_time_spent_on_ticket', $ticket->ID );
		$adj_type      = wpas_get_cf_value( 'time_adjustments_pos_or_neg', $ticket->ID );
		$final         = new WPAS_Custom_Field( 'final_time_spent_on_ticket', $custom_fields['final_time_spent_on_ticket'] );

		// Calculate time adjustment
		if ( ! empty( $adjustment ) ) {
			if ( '+' === $adj_type ) {
				$calculated += $adjustment;
			} else {
				$calculated -= $adjustment;
			}
		}

		if ( $final->get_field_value( '', $ticket->ID ) != $calculated ) {
			update_post_meta( $ticket->ID, '_wpas_final_time_spent_on_ticket', $calculated );
			$this->maybe_update_log( $final, 2, $ticket->ID );
		}

	}

	/**
	 * Log the ticket status transition
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 */
	public static function record_post_status_transition( $new_status, $old_status, $post ) {
		if ( $new_status == $old_status || 'ticket' != $post->post_type || 'new' === $old_status) {
			return;
		}

		$custom_status = wpas_get_post_status();

		wpas_log( $post->ID, sprintf( __( 'Ticket state changed to %s', 'awesome-support' ), $custom_status[ $new_status ] ) );

		remove_action( 'transition_post_status', '\WPAS_API\API\Tickets::record_post_status_transition', 10 );

		do_action( 'wpas_ticket_status_updated', $post->ID, $new_status, $post->ID );
	}

	/**
	 * Add the provided field to the ticket history log
	 *
	 * @todo if you update this functionality, be sure to do the same in the core plugin plugin in /includes/custom-fields/class-custom-fields.php on line 422
	 * @see \WPAS_Custom_Fields::save_custom_fields()
	 *
	 * @param WPAS_Custom_Field $field
	 * @param integer           $result
	 * @param integer           $post_id
	 */
	public function maybe_update_log( $field, $result, $post_id ) {

		if ( true !== $field->field_args['log'] ) {
			return;
		}

		$value = $field->get_field_value( '', $post_id );

		/**
		 * If the custom field is a taxonomy we need to convert the term ID into its name.
		 *
		 * By checking if $result is different from 0 we make sure that the term actually exists.
		 * If the term didn't exist the save function would have seen it and returned 0.
		 */
		if ( 'taxonomy' === $field->field_args['field_type'] && 0 !== $result ) {
			if ( $term  = get_term_by( 'slug', $value, $field->field_id ) ) {
				$value = $term->name;
			}
		}

		/**
		 * If the "options" parameter is set for this field, we assume it is because
		 * the field type has multiple options. In order to make is more readable,
		 * we try to replace the field value by the value label.
		 *
		 * This process is based on the fact that field types options always follow
		 * the schema option_id => option_label.
		 */
		if ( isset( $field->field_args['options'] ) && is_array( $field->field_args['options'] ) ) {

			/* Make sure arrays are still readable */
			if ( is_array( $value ) ) {

				$new_values = array();

				foreach ( $value as $val ) {
					if ( array_key_exists( $val, $field->field_args['options'] ) ) {
						array_push( $new_values, $field->field_args['options'][ $val ] );
					}
				}

				/* Only if all original values were replaced we update the $value var. */
				if ( count( $new_values ) === count( $value ) ) {
					$value = $new_values;
				}

				$value = implode( ', ', $value );

			} else {

				if ( array_key_exists( $value, $field->field_args['options'] ) ) {
					$value = $field->field_args['options'][ $value ];
				}

			}

		}

		$tmp = array(
			'action'   => '',
			'label'    => wpas_get_field_title( $field->field ),
			'value'    => $value,
			'field_id' => $field->field_id,
		);

		switch ( (int) $result ) {

			case 1:
				$tmp['action'] = 'added';
				break;

			case 2:
				$tmp['action'] = 'updated';
				break;

			case 3:
				$tmp['action'] = 'deleted';
				break;

		}

		/* Only add this to the log if something was done to the field value */
		if ( ! empty( $tmp['action'] ) ) {
			$this->log[] = $tmp;
			add_action( 'wpas_api_tickets_update_additional_fields_after', array( $this, 'log_history' ), 100, 2 );
		}

	}

	/**
	 * Log this ticket history
	 *
	 * @param \WP_Post        $object
	 * @param WP_REST_Request $request
	 */
	public function log_history( $object, $request ) {
		if ( empty( $this->log ) || ! is_int( $request['id'] ) ) {
			return;
		}

		wpas_log( $request['id'], $this->log );
	}

	/**
	 * Define extra fields
	 *
	 * @return array
	 */
	public function get_additional_ticket_fields() {
		$fields = array();

		$fields['state'] = array(
			'default'     => 'open',
			'description' => __( 'Limit result set to tickets in the specified state.' ),
			'type'        => 'string',
			'query_cb'    => array( $this, 'query_state' ),
			'update_cb'   => array( $this, 'update_state' ),
			'field_key'   => 'status',
			'items'       => array(
				'enum' => array( 'open', 'closed', 'any' ),
			),
			'arg_options' => array(
				'default' => 'open',
			),
		);

		$fields['author'] = array(
			'description' => __( 'The ID for the author of the object.' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit', 'embed' ),
			'query_cb'    => true,
		);

		$fields['assignee'] = array(
			'description' => __( 'The agent assigned to this ticket', 'awesome-support' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit', 'embed' ),
			'field_key'   => 'assignee',
			'update_cb'   => array( $this, 'update_assignee' ),
			'query_cb'    => array( $this, 'query_assignee' ),
			'arg_options' => array(
				'default' => wpas_find_agent(),
			),
		);

		$fields['secondary-assignee'] = array(
			'description' => __( 'The secondary assignee for this ticket', 'awesome-support' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'field_key'   => 'secondary_assignee',
		);

		$fields['tertiary-assignee'] = array(
			'description' => __( 'The tertiary assignee for this ticket', 'awesome-support' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'field_key'   => 'tertiary_assignee',
		);

		$fields['customer-reply-count'] = array(
			'description' => __( 'The number of customer replies to this ticket', 'awesome-support' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'field_key'   => 'ttl_replies_by_customer',
		);

		$fields['agent-reply-count'] = array(
			'description' => __( 'The number of agent replies to this ticket', 'awesome-support' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'field_key'   => 'ttl_replies_by_agent',
		);

		$fields['total-reply-count'] = array(
			'description' => __( 'The number of total replies to this ticket', 'awesome-support' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'field_key'   => 'ttl_replies',
		);

		$fields['time-calculated'] = array(
			'description'       => __( 'The gross time calculated for ticket in minutes', 'awesome-support' ),
			'type'              => 'integer',
			'context'           => array( 'view', 'edit' ),
			'field_key'         => 'ttl_calculated_time_spent_on_ticket',
			'sanitize_cb' => array( $this, 'format_time' ),
		);

		$fields['time-adjustments'] = array(
			'description' => __( 'The time adjustments for ticket in minutes', 'awesome-support' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'field_key'   => 'ttl_adjustments_to_time_spent_on_ticket',
			'sanitize_cb' => array( $this, 'format_time' ),
		);

		$fields['time-final'] = array(
			'description' => __( 'The final adjusted time for ticket in minutes', 'awesome-support' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'field_key'   => 'final_time_spent_on_ticket',
		);

		$fields['time-adjustments-type'] = array(
			'description' => __( 'The type of time adjustment, positive or negative.', 'awesome-support' ),
			'type'        => 'string',
			'items'       => array(
				'enum' => array( '+', '-' ),
			),
			'context'     => array( 'view', 'edit' ),
			'field_key'   => 'time_adjustments_pos_or_neg',
			'sanitize_cb' => array( $this, 'setup_time_calculate' ),
		);

		$fields['time-notes'] = array(
			'description' => __( 'The notes for the time', 'awesome-support' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'field_key'   => 'time_notes',
		);

		return apply_filters( 'wpas_api_additional_ticket_fields', $fields );
	}

}