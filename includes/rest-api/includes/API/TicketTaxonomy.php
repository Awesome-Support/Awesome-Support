<?php

namespace WPAS_API\API;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Terms_Controller;
use WP_REST_Term_Meta_Fields;

/**
 * Core class used to managed terms associated with a taxonomy via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class TicketTaxonomy extends WP_REST_Terms_Controller {

	/**
	 * Constructor.
	 *
	 * @param string $taxonomy Taxonomy key.
	 */
	public function __construct( $taxonomy ) {
		parent::__construct( $taxonomy );

		$this->namespace = wpas_api()->get_api_namespace();
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
		if ( 'ticket_priority' === $this->taxonomy ) {
			$schema['properties']['color'] = array(
				'descriptions' => __( 'The color for this priority', 'awesome-support' ),
				'type'         => 'string',
				'context'      => array( 'view', 'embed', 'edit' )
			);
		}

		return parent::add_additional_fields_schema( $schema );
	}

	/**
	 * Adds the values from additional fields to a data object.
	 *
	 * @param array           $object  Data object.
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return array Modified data object with additional fields.
	 */
	protected function add_additional_fields_to_object( $object, $request ) {
		if ( 'ticket_priority' === $this->taxonomy ) {
			$object['color'] = get_term_meta( $object['id'], 'color', true );
		}

		return parent::add_additional_fields_to_object( $object, $request );
	}

	/**
	 * Updates the values of additional fields added to a data object.
	 *
	 * @param array           $object  Data Object.
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True on success, WP_Error object if a field cannot be updated.
	 */
	protected function update_additional_fields_for_object( $object, $request ) {
		if ( isset( $request['color'] ) ) {
			update_term_meta( $object->term_id, 'color', $request['color'] );
		}

		return parent::update_additional_fields_for_object( $object, $request );
	}

	/**
	 * reset post type links to use the correct namespace
	 *
	 * @since 4.7.0
	 * @access protected
	 *
	 * @param object $term Term object.
	 * @return array Links for the given term.
	 */
	protected function prepare_links( $term ) {
		$links = parent::prepare_links( $term );

		$taxonomy_obj = get_taxonomy( $term->taxonomy );

		if ( empty( $taxonomy_obj->object_type ) ) {
			return $links;
		}

		$post_type_links = array();

		foreach ( $taxonomy_obj->object_type as $type ) {
			$post_type_object = get_post_type_object( $type );

			if ( empty( $post_type_object->show_in_rest ) ) {
				continue;
			}

			$rest_base = ! empty( $post_type_object->rest_base ) ? $post_type_object->rest_base : $post_type_object->name;
			$post_type_links[] = array(
				'href' => add_query_arg( $this->rest_base, $term->term_id, rest_url( sprintf( $this->namespace . '/%s', $rest_base ) ) ),
			);
		}

		if ( ! empty( $post_type_links ) ) {
			$links['https://api.w.org/post_type'] = $post_type_links;
		}

		return apply_filters( "wpas_api_taxonomy_prepare_links", $links, $term, $this );
	}

}
