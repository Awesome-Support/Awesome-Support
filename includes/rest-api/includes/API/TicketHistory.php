<?php

namespace WPAS_API\API;

use WPAS_API\API\TicketBase;
use WP_REST_Server;
use WP_REST_Posts_Controller;
use WP_Error;

class TicketHistory extends TicketBase {


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
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		$schema = $this->get_item_schema();
		$get_item_args = array(
			'context'  => $this->get_context_param( array( 'default' => 'view' ) ),
		);
		if ( isset( $schema['properties']['password'] ) ) {
			$get_item_args['password'] = array(
				'description' => __( 'The password for the post if it is password protected.' ),
				'type'        => 'string',
			);
		}

		register_rest_route($this->namespace, '/' . $ticket->rest_base . '/(?P<ticket_id>[\d]+)/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			'args' => array(
				'ticket_id' => array(
					'description' => __( 'Unique identifier for the ticket.' ),
					'type'        => 'integer',
				),
				'id' => array(
					'description' => __( 'Unique identifier for the history.' ),
					'type'        => 'integer',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => $get_item_args,
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$remove_from_schema = array( 'slug', 'link', 'template', 'title', 'status' );

		foreach( $remove_from_schema as $remove ) {
			unset( $schema['properties'][ $remove ] );
		}

		$schema['properties']['content'] = array(
			'description' => __( 'The content for the object.' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit', 'embed' ),
			'arg_options' => array(
				'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database()
			),
			'properties'  => array(
				'raw'       => array(
					'description' => __( 'Content for the object, as it exists in the database.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'rendered'  => array(
					'description' => __( 'HTML content for the object, transformed for display.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'protected' => array(
					'description' => __( 'Whether the content is protected with a password.' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
		);

		return apply_filters( "wpas_api_{$this->rest_base}_get_item_schema", $schema, $this );
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
					rest_url( 'wp/v2/' . $tax_base )
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

}