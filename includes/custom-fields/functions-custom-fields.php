<?php
/**
 * Custom fields functions that are at a higher level than the classes the define the custom fields.
 *
 * Many of these functions are used in the front-end submission forms.
 *
 */

/**
 * Custom callback for updating terms count.
 *
 * The function is based on the original WordPress function
 * _update_post_term_count but adapted to work with the plugin
 * custom status.
 *
 * @since  3.0.0
 * @param  array  $terms    List of terms attached to the post
 * @param  object $taxonomy Taxonomy of update
 * @return void
 */
function wpas_update_ticket_tag_terms_count( $terms, $taxonomy ) {

	global $wpdb;

	$object_types   = (array) $taxonomy->object_type;
	$post_status    = wpas_get_post_status();
	$allowed_status = array();

	foreach ( $post_status as $status => $label ) {
		if ( !in_array( $status, $allowed_status ) ) {
			array_push( $allowed_status, $status );
		}
	}

	foreach ( $object_types as &$object_type ) {
		list( $object_type ) = explode( ':', $object_type );
	}

	$object_types = array_unique( $object_types );

	if ( false !== ( $check_attachments = array_search( 'attachment', $object_types ) ) ) {
		unset( $object_types[ $check_attachments ] );
		$check_attachments = true;
	}

	if ( $object_types ) {
		$object_types = esc_sql( array_filter( $object_types, 'post_type_exists' ) );
	}

	foreach ( (array) $terms as $term ) {
		$count = 0;

		// Attachments can be 'inherit' status, we need to base count off the parent's status if so
		if ( $check_attachments ) {
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status = 'publish' OR ( post_status = 'inherit' AND post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) = 'publish' ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term ) );
		}

		if ( $object_types ) {
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status IN ('" . implode( "', '", $allowed_status ) . "') AND post_type IN ('" . implode( "', '", $object_types ) . "') AND term_taxonomy_id = %d", $term ) );
		}

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edit_term_taxonomy', $term, $taxonomy );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edited_term_taxonomy', $term, $taxonomy );
	}

}

/**
 * Return a custom field value.
 *
 * @param  string  $name    Option name
 * @param  integer $post_id Post ID
 * @param  mixed   $default Default value
 *
 * @return mixed            Meta value
 * @since  3.0.0
 */
function wpas_get_cf_value( $name, $post_id, $default = false ) {

	$field = new WPAS_Custom_Field( $name );

	$cf_value = $field->get_field_value( $default, $post_id );

	/*
	 * Some custom fields have multiple values. For example,
	 * checkboxe custom fields. These are stored as an array.
	 *
	 */
	if ( is_array( $cf_value )) {
		$cf_value = implode(', ', $cf_value );
	}

	return $cf_value;
}

/**
 * Echo a custom field value.
 *
 * This function is just a wrapper function for wpas_get_cf_value()
 * that echoes the result instead of returning it.
 *
 * @param  string  $name    Option name
 * @param  integer $post_id Post ID
 * @param  mixed   $default Default value
 *
 * @return mixed            Meta value
 * @since  3.0.0
 */
function wpas_cf_value( $name, $post_id, $default = false ) {
	echo wpas_get_cf_value( $name, $post_id, $default );
}

/**
 * Add a new custom field.
 *
 * @since  3.0.0
 *
 * @param  string $name The ID of the custom field to add
 * @param  array  $args Additional arguments for the custom field
 *
 * @return boolean        Returns true on success or false on failure
 */
function wpas_add_custom_field( $name, $args = array() ) {
	return WPAS()->custom_fields->add_field( $name, $args );
}

/**
 * Checks to see if a custom field already exists
 *
 * @since  4.3.6
 *
 * @param  string $name The ID of the custom field to check for
 *
 * @return boolean        Returns true if it exists of false otherwise
 */
function wpas_custom_field_exists( $name ) {
	
	if ( isset( WPAS()->custom_fields->get_custom_fields()[ $name ] ) ) {
		return true ;		
	}
	
	return false ;
}


/**
 * Add a new custom taxonomy.
 *
 * @since  3.0.0
 *
 * @param  string $name The ID of the custom field to add
 * @param  array  $args Additional arguments for the custom field
 *
 * @return boolean        Returns true on success or false on failure
 */
function wpas_add_custom_taxonomy( $name, $args = array() ) {

	/* Force the custom fields type to be a taxonomy. */
	$args['field_type']      = 'taxonomy';
	$args['column_callback'] = 'wpas_show_taxonomy_column';

	/* Add the taxonomy. */
	WPAS()->custom_fields->add_field( $name, $args );

	return true;

}

/**
 * Calculate and save time spent on ticket
 *
 * @todo if you update this functionality, be sure to do the same in the Rest-API plugin in /includes/API/Tickets.php on line 595
 *
 * @since  3.3.5
 *
 * @param  string   $value      Not used
 * @param  int      $post_id    Ticket ID
 * @param  string   $field_id   Field ID
 * @param  array    $field      Custom field
 *
 * @return  int|array           Returns result of add/update post meta
 */
function wpas_update_time_spent_on_ticket( $value, $post_id, $field_id, $field ) {

	// Default to saved value unchanged
	$result = 0;

	// No time spent on this ticket
	if ( ! isset ($_POST['wpas_ttl_calculated_time_spent_on_ticket']) ) {
		return $result;
	}

	$hours = $minutes = $adj_hours = $adj_minutes = 0;

	// Time spent on ticket (hh:mm:ss)
	sscanf( $_POST['wpas_ttl_calculated_time_spent_on_ticket'], "%d:%d", $hours, $minutes );

	// Convert to seconds
	$minutes = $hours * 60 + $minutes;

	// Calculate time adjustment
	if( isset ( $_POST['wpas_ttl_adjustments_to_time_spent_on_ticket'] )
		&& ! empty( $_POST['wpas_ttl_adjustments_to_time_spent_on_ticket'] )
	) {
		sscanf( $_POST['wpas_ttl_adjustments_to_time_spent_on_ticket'], "%d:%d", $adj_hours, $adj_minutes );
		$adjustment_time = $adj_hours * 60 + $adj_minutes;

		if( '+' === $_POST['wpas_time_adjustments_pos_or_neg'] ) {
			$minutes += $adjustment_time;
		}
		else {
			$minutes -= $adjustment_time;
		}
	}

	/**
	 * Get the current field value.
	 */
	$current = get_post_meta( $post_id, $field_id, true );

	/* Action: Update post meta */
	if ( ( ! empty( $current ) || is_null( $current ) ) && ! empty( $minutes ) ) {
		if ( $current !== $minutes ) {
			if ( false !== update_post_meta( $post_id, $field_id, $minutes, $current ) ) {
				$result = 2;
			}
		}
	}

	/* Action: Add post meta */
	elseif ( empty( $current ) && ! empty( $minutes ) ) {
		if ( false !== add_post_meta( $post_id, $field_id, $minutes, true ) ) {
			$result = 1;
		}
	}

	return array( 'result' => $result, 'value' => $minutes );

}

/**
 * Custom Save Callback - save user entered hh:mm time as integer in minutes
 *
 * @since 3.3.5
 *
 * @param $value
 *
 * @param $post_id
 *
 * @param $field_id
 *
 * @param $field
 */
function wpas_cf_save_time_hhmm( $value, $post_id, $field_id, $field ) {

	$hours = $minutes = 0;

	// Time spent on ticket (hh:mm:ss)
	sscanf( $value, "%d:%d", $hours, $minutes );

	// Convert to minutes
	$minutes = $hours * 60 + $minutes;

	/**
	 * Get the current field value.
	 */
	$current = get_post_meta( $post_id, $field_id, true );

	/* Action: Update post meta */
	if ( ( ! empty( $current ) || is_null( $current ) ) && ! empty( $minutes ) ) {
		if ( $current !== $minutes ) {
			if ( false !== update_post_meta( $post_id, $field_id, $minutes, $current ) ) {
				$result = 2;
			}
		}
	}

	/* Action: Add post meta */
	elseif ( empty( $current ) && ! empty( $minutes ) ) {
		if ( false !== add_post_meta( $post_id, $field_id, $minutes, true ) ) {
			$result = 1;
		}
	}

}


add_action( 'init', 'wpas_register_core_fields' );
/**
 * Register the cure custom fields.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_register_core_fields() {

	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

	/*******************************************************************/
	/* Add Agent/Assignee field                                        */
	/*******************************************************************/

	/** Determine if assignee column is shown in tickets list */
	$show_assignee = true ;

	/** Get the label for the agent field if one is provided */
	$as_label_for_agent_singular = isset( $options[ 'label_for_agent_singular' ] ) ? $options[ 'label_for_agent_singular' ] : __( 'Agent', 'awesome-support' );

	/** Create the custom field for agents */
	wpas_add_custom_field( 'assignee', array(
		'core'            => true,
		'show_column'     => $show_assignee,
		'sortable_column' => $show_assignee,
		'filterable'      => $show_assignee,
		'column_callback' => 'wpas_show_assignee_column',
		'log'             => true,
		'title'           => $as_label_for_agent_singular
	) );

	/*******************************************************************/
	/* Add Status/state field                                          */
	/*******************************************************************/

	/** Get the label for the status field if one is provided */
	$as_label_for_status_singular = isset( $options[ 'label_for_status_singular' ] ) ? $options[ 'label_for_status_singular' ] : __( 'Status', 'awesome-support' );

	/** Create the custom field for status */
	wpas_add_custom_field( 'status', array(
		'core'            => true,
		'show_column'     => true,
		'log'             => false,
		'field_type'      => false,
		'sortable_column' => true,
		'column_callback' => 'wpas_cf_display_status',
		'save_callback'   => null,
		'title'           => $as_label_for_status_singular
	) );


	/*******************************************************************/
	/* Add Tag fields                                                  */
	/*******************************************************************/

	/** Get the labels for the ticket tags field if they are provided */
	$as_label_for_ticket_tag_singular 	= isset( $options[ 'label_for_ticket_tag_singular' ] ) ? $options[ 'label_for_ticket_tag_singular' ] : __( 'Tag', 'awesome-support' );
	$as_label_for_ticket_tag_plural 	= isset( $options[ 'label_for_ticket_tag_plural' ] ) ? $options[ 'label_for_ticket_tag_plural' ] : __( 'Tags', 'awesome-support' );

	/** Create the custom field for ticket tags */
	wpas_add_custom_field( 'ticket-tag', array(
		'core'                  => true,
		'show_column'           => true,
		'log'                   => true,
		'field_type'            => 'taxonomy',
		'sortable_column'       => true,
		'taxo_std'              => false,
		'column_callback'       => 'wpas_show_taxonomy_column',
		'save_callback'         => null,
		'label'                 => $as_label_for_ticket_tag_singular,
		'name'                  => $as_label_for_ticket_tag_singular,
		'label_plural'          => $as_label_for_ticket_tag_plural,
		'taxo_hierarchical'     => false,
		'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
		'select2'               => false,
		'taxo_manage_terms' 	=> 'ticket_manage_tags',
		'taxo_edit_terms'   	=> 'ticket_edit_tags',
		'taxo_delete_terms' 	=> 'ticket_delete_tags',
		'title'           		=> $as_label_for_ticket_tag_singular
	) );


	/*******************************************************************/
	/* Add Product fields                                              */
	/*******************************************************************/

	if ( isset( $options[ 'support_products' ] ) && true === boolval( $options[ 'support_products' ] ) ) {

		$slug = defined( 'WPAS_PRODUCT_SLUG' ) ? WPAS_PRODUCT_SLUG : wpas_get_option( 'products_slug', 'product');

		/** Get the labels for the products field if they are provided */
		$as_label_for_product_singular 	= isset( $options[ 'label_for_product_singular' ] ) ? $options[ 'label_for_product_singular' ] : __( 'Product', 'awesome-support' );
		$as_label_for_product_plural 	= isset( $options[ 'label_for_product_plural' ] ) ? $options[ 'label_for_product_plural' ] : __( 'Products', 'awesome-support' );

		/* Filter the product taxonomy labels */
		$labels = apply_filters( 'wpas_product_taxonomy_labels', array(
				'label'        => $as_label_for_product_singular,
				'name'         => $as_label_for_product_singular,
				'label_plural' => $as_label_for_product_plural
			)
		);

		/** Create the custom field for products */
		wpas_add_custom_field( 'product', array(
			'core'                  => false,
			'show_column'           => true,
			'log'                   => true,
			'field_type'            => 'taxonomy',
			'taxo_std'              => false,
			'column_callback'       => 'wpas_show_taxonomy_column',
			'label'                 => $labels[ 'label' ],
			'name'                  => $labels[ 'name' ],
			'label_plural'          => $labels[ 'label_plural' ],
			'taxo_hierarchical'     => true,
			'sortable_column'       => true,
			'filterable'            => false,
			'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
			'rewrite'               => array( 'slug' => $slug ),
			'select2'               => false,
			'taxo_manage_terms' 	=> 'ticket_manage_products',
			'taxo_edit_terms'   	=> 'ticket_edit_products',
			'taxo_delete_terms' 	=> 'ticket_delete_products',
			'title'           		=> $as_label_for_product_singular
		) );

	}

	/*******************************************************************/
	/* Add Department fields                                           */
	/*******************************************************************/
	if ( isset( $options[ 'departments' ] ) && true === boolval( $options[ 'departments' ] ) ) {

		$slug = defined( 'WPAS_DEPARTMENT_SLUG' ) ? WPAS_DEPARTMENT_SLUG : 'department';

		/** Get the labels for the department field if they are provided */
		$as_label_for_department_singular 	= isset( $options[ 'label_for_department_singular' ] ) ? $options[ 'label_for_department_singular' ] : __( 'Department', 'awesome-support' );
		$as_label_for_department_plural 	= isset( $options[ 'label_for_department_plural' ] ) ? $options[ 'label_for_department_plural' ] : __( 'Departments', 'awesome-support' );

		/* Filter the department taxonomy labels */
		$labels = apply_filters( 'wpas_department_taxonomy_labels', array(
			'label'        => $as_label_for_department_singular,
			'name'         => $as_label_for_department_singular,
			'label_plural' => $as_label_for_department_plural
		) );

		/** Create the custom field for department */
		wpas_add_custom_field( 'department', array(
			'core'                  => false,
			'show_column'           => true,
			'log'                   => true,
			'field_type'            => 'taxonomy',
			'taxo_std'              => false,
			'column_callback'       => 'wpas_show_taxonomy_column',
			'label'                 => $labels[ 'label' ],
			'name'                  => $labels[ 'name' ],
			'label_plural'          => $labels[ 'label_plural' ],
			'taxo_hierarchical'     => true,
			'sortable_column'       => true,
			'filterable'            => true,
			'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
			'rewrite'               => array( 'slug' => $slug ),
			'select2'               => false,
			'taxo_manage_terms' 	=> 'ticket_manage_departments',
			'taxo_edit_terms'   	=> 'ticket_edit_departments',
			'taxo_delete_terms' 	=> 'ticket_delete_departments',			
			'title'           		=> $as_label_for_department_singular
		) );

	}

	/*******************************************************************/
	/* Add priority fields                                             */
	/*******************************************************************/
	if ( isset( $options[ 'support_priority' ] ) && true === boolval( $options[ 'support_priority' ] ) ) {

		$slug = defined( 'WPAS_PRIORITY_SLUG' ) ? WPAS_PRIORITY_SLUG : 'ticket_priority';

		$show_priority_column_in_list = false;
		$show_priority_column_in_list = ( isset( $options[ 'support_priority_show_in_ticket_list' ] ) && true === boolval( $options[ 'support_priority_show_in_ticket_list' ] ) );

		$show_priority_required = false;
		$show_priority_required = ( isset( $options[ 'support_priority_mandatory' ] ) && true === boolval( $options[ 'support_priority_mandatory' ] ) );

		$show_priority_on_front_end = false;
		$show_priority_on_front_end = ( isset( $options[ 'support_priority_show_fe' ] ) && true === boolval( $options[ 'support_priority_show_fe' ] ) );

		/* Now, depending on if the user specifies whether to show the field on the front end or not, we'll set a flag for the back-end only attribute of the custom field. */
		/* This way the field always show up in the back-end.  It will show up as an admin only field if the user elects not to turn it on for the front end. Otherwise		*/
		/* if turned on for the front-end it will show up in the regular custom fields metabox.																				*/
		$show_priority_on_back_end_only = false;
		if ( false === $show_priority_on_front_end ) {

			$show_priority_on_back_end_only = true;
		}

		/** Get the labels for the priority field if they are provided */
		$as_label_for_priority_singular 	= isset( $options[ 'label_for_priority_singular' ] ) ? $options[ 'label_for_priority_singular' ] : __( 'Priority', 'awesome-support' );
		$as_label_for_priority_plural 	= isset( $options[ 'label_for_priority_plural' ] ) ? $options[ 'label_for_priority_plural' ] : __( 'Priorities', 'awesome-support' );


		/* Filter the priority taxonomy labels */
		$labels = apply_filters( 'wpas_priority_taxonomy_labels', array(
				'label'        => $as_label_for_priority_singular,
				'name'         => $as_label_for_priority_singular,
				'label_plural' => $as_label_for_priority_plural
		) );


		/** Create the custom field for priority */
		wpas_add_custom_field( 'ticket_priority', array(
			'core'                  => false,
			'show_column'           => $show_priority_column_in_list,
			'hide_front_end'        => !$show_priority_on_front_end,  //inverse of what the user specificed in settings because of how this attribute works...
			'backend_only'          => $show_priority_on_back_end_only,
			'log'                   => true,
			'field_type'            => 'taxonomy',
			'taxo_std'              => false,
			'column_callback'       => 'wpas_cf_display_priority',
			'label'                 => $labels[ 'label' ],
			'name'                  => $labels[ 'name' ],
			'label_plural'          => $labels[ 'label_plural' ],
			'taxo_hierarchical'     => true,
			'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
			'rewrite'               => array( 'slug' => $slug ),
			'sortable_column'       => true,
			'select2'               => false,
			'taxo_manage_terms' 	=> 'ticket_manage_priorities',
			'taxo_edit_terms'   	=> 'ticket_edit_priorities',
			'taxo_delete_terms' 	=> 'ticket_delete_priorities',			
			'filterable'            => true,
			'required'              => $show_priority_required,
			'title'           		=> $as_label_for_priority_singular
		) );

	}

	/*******************************************************************/
	/* Add ticket channel field (where did the ticket originate from?) */
	/*******************************************************************/
	$slug = defined( 'WPAS_CHANNEL_SLUG' ) ? WPAS_CHANNEL_SLUG : 'ticket_channel';

	/** Get the labels for the channel field if they are provided */
	$as_label_for_channel_singular 	= isset( $options[ 'label_for_channel_singular' ] ) ? $options[ 'label_for_channel_singular' ] : __( 'Channel', 'awesome-support' );
	$as_label_for_channel_plural 	= isset( $options[ 'label_for_channel_plural' ] ) ? $options[ 'label_for_channel_plural' ] : __( 'Channels', 'awesome-support' );


	/* Filter the channel taxonomy labels */
	$labels = apply_filters( 'wpas_channel_taxonomy_labels', array(
			'label'        => $as_label_for_channel_singular,
			'name'         => $as_label_for_channel_singular,
			'label_plural' => $as_label_for_channel_plural
		)
	);

	$show_channel_column_in_list = ( isset( $options[ 'channel_show_in_ticket_list' ] ) && true === boolval( $options[ 'channel_show_in_ticket_list' ] ) );


	/** Create the custom field for channel */
	wpas_add_custom_field( 'ticket_channel', array(
		'core'                  => false,
		'show_column'           => $show_channel_column_in_list,
		'hide_front_end'        => true,
		'backend_only'          => true,
		'log'                   => true,
		'field_type'            => 'taxonomy',
		'taxo_std'              => false,
		'column_callback'       => 'wpas_show_taxonomy_column',
		'label'                 => $labels[ 'label' ],
		'name'                  => $labels[ 'name' ],
		'label_plural'          => $labels[ 'label_plural' ],
		'taxo_hierarchical'     => true,
		'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
		'rewrite'               => array( 'slug' => $slug ),
		'sortable_column'       => $show_channel_column_in_list,
		'select2'               => false,
		'taxo_manage_terms' 	=> 'ticket_manage_channels',
		'taxo_edit_terms'   	=> 'ticket_edit_channels',
		'taxo_delete_terms' 	=> 'ticket_delete_channels',		
		'filterable'            => $show_channel_column_in_list,
		'default'               => 'standard ticket form',
		'title'           		=> $as_label_for_channel_singular
	) );
	
	/*******************************************************************/
	/* Add additional assignees to ticket                              */
	/*******************************************************************/
	if ( isset( $options[ 'multiple_agents_per_ticket' ] ) && true === boolval( $options[ 'multiple_agents_per_ticket' ] ) ) {

		/** Get the flag that controls whther to show these fields in the ticket list */
		$show_secondary_agent_in_list = false;
		$show_secondary_agent_in_list = ( isset( $options[ 'show_secondary_agent_in_ticket_list' ] ) && true === boolval( $options[ 'show_secondary_agent_in_ticket_list' ] ) );

		$show_tertiary_agent_in_list = false;
		$show_tertiary_agent_in_list = ( isset( $options[ 'show_tertiary_agent_in_ticket_list' ] ) && true === boolval( $options[ 'show_tertiary_agent_in_ticket_list' ] ) );


		/** Get the label for the secondary agent field if one is provided */
		$as_label_for_secondary_agent_singular = isset( $options[ 'label_for_secondary_agent_singular' ] ) ? $options[ 'label_for_secondary_agent_singular' ] : __( 'Additional Support Staff #1', 'awesome-support' );

		/*** Create the secondary assignee custom field */
		wpas_add_custom_field( 'secondary_assignee', array(
			'core'           	=> false,
			'show_column'    	=> $show_secondary_agent_in_list,
			'sortable_column'	=> $show_secondary_agent_in_list,
			'filterable'        => $show_secondary_agent_in_list,
			'hide_front_end' 	=> true,
			'log'            	=> true,
			'column_callback' 	=> 'wpas_show_secondary_assignee_column',
			'title'          	=> $as_label_for_secondary_agent_singular
		) );

		/** Get the label for the tertiary agent field if one is provided */
		$as_label_for_tertiary_agent_singular = isset( $options[ 'label_for_tertiary_agent_singular' ] ) ? $options[ 'label_for_tertiary_agent_singular' ] : __( 'Additional Support Staff #2', 'awesome-support' );

		/*** Create the tertiary assignee custom field */
		wpas_add_custom_field( 'tertiary_assignee', array(
			'core'           	=> false,
			'hide_front_end' 	=> true,
			'show_column'    	=> $show_tertiary_agent_in_list,
			'sortable_column'	=> $show_tertiary_agent_in_list,
			'filterable'        => $show_tertiary_agent_in_list,
			'log'            	=> true,
			'column_callback' 	=> 'wpas_show_tertiary_assignee_column',
			'title'          	=> $as_label_for_tertiary_agent_singular
		) );
	}

	/************************************************************************/
	/* Add fields to store the number of replies on a ticket. 				*/
	/* These will be used for reporting purposes in a new reporting add-on 	*/
	/************************************************************************/

	/** Get the labels for these replies statistic fields if they are provided */

	$as_label_for_ttl_replies_by_agent_singular 	= isset( $options[ 'label_for_ttl_replies_by_agent_singular' ] ) ? $options[ 'label_for_ttl_replies_by_agent_singular' ] : __( 'Number of Replies By Agent', 'awesome-support' );
	$as_label_for_ttl_replies_by_customer_singular 	= isset( $options[ 'label_for_ttl_replies_by_customer_singular' ] ) ? $options[ 'label_for_ttl_replies_by_customer_singular' ] : __( 'Number of Replies By Customer', 'awesome-support' );
	$as_label_for_ttl_replies_singular 				= isset( $options[ 'label_for_ttl_replies_singular' ] ) ? $options[ 'label_for_ttl_replies_singular' ] : __( 'Total Replies On Ticket', 'awesome-support' );

	/** Now create the replies statistics fields */
	wpas_add_custom_field( 'ttl_replies_by_agent', array(
		'core'        => true,
		'show_column' => false,
		'log'         => false,
		'readonly'    => true,
		'title'       => $as_label_for_ttl_replies_by_agent_singular,
	) );

	wpas_add_custom_field( 'ttl_replies_by_customer', array(
		'core'        => true,
		'show_column' => false,
		'log'         => false,
		'readonly'    => true,
		'title'       => $as_label_for_ttl_replies_by_customer_singular
	) );

	wpas_add_custom_field( 'ttl_replies', array(
		'core'        => true,
		'show_column' => false,
		'log'         => false,
		'readonly'    => true,
		'title'       => $as_label_for_ttl_replies_singular
	) );

	/*******************************************************************/
	/* Add fields to store time spent working on a ticket.             */
	/*******************************************************************/
	$audit_log_for_time_tracking_fields = false ;
	$audit_log_for_time_tracking_fields = ( isset( $options[ 'keep_audit_log_time_tracking' ] ) && true === boolval( $options[ 'keep_audit_log_time_tracking' ] ) );
	
	
	$show_total_time_in_list = false;
	$show_total_time_in_list = ( isset( $options[ 'show_total_time_in_ticket_list' ] ) && true === boolval( $options[ 'show_total_time_in_ticket_list' ] ) );
	
	$show_total_time_adj_in_list = false;
	$show_total_time_adj_in_list = ( isset( $options[ 'show_total_time_adj_in_ticket_list' ] ) && true === boolval( $options[ 'show_total_time_adj_in_ticket_list' ] ) );
	
	$show_final_time_in_list = false;
	$show_final_time_in_list = ( isset( $options[ 'show_final_time_in_ticket_list' ] ) && true === boolval( $options[ 'show_final_time_in_ticket_list' ] ) );
	
	$allow_agents_to_enter_time = true;
	$allow_agents_to_enter_time = ! ( isset( $options[ 'allow_agents_to_enter_time' ] ) && true === boolval( $options[ 'allow_agents_to_enter_time' ] ) );

	/** Get the labels for these time related fields if they are provided */
	$as_label_for_gross_time_singular 			= isset( $options[ 'label_for_gross_time_singular' ] ) ? $options[ 'label_for_gross_time_singular' ] : __( 'Gross Time', 'awesome-support' );
	$as_label_for_time_adjustments_singular 	= isset( $options[ 'label_for_time_adjustments_singular' ] ) ? $options[ 'label_for_time_adjustments_singular' ] : __( 'Time Adjustments', 'awesome-support' );
	$as_label_for_time_adjustments_dir_singular = isset( $options[ 'label_for_time_adjustments_dir_singular' ] ) ? $options[ 'label_for_time_adjustments_dir_singular' ] : __( '+ive or -ive Adj?', 'awesome-support' );
	$as_label_for_final_time_singular 			= isset( $options[ 'label_for_final_time_singular' ] ) ? $options[ 'label_for_final_time_singular' ] : __( 'Final Time', 'awesome-support' );
	$as_label_for_time_notes_singular 			= isset( $options[ 'label_for_time_notes_singular' ] ) ? $options[ 'label_for_time_notes_singular' ] : __( 'Notes', 'awesome-support' );

	wpas_add_custom_field( 'ttl_calculated_time_spent_on_ticket', array(
		'core'        		=> false,
		'show_column' 		=> $show_total_time_in_list,
		'log'         		=> $audit_log_for_time_tracking_fields,
		'html5_pattern'		=> '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){1}',
		'placeholder'		=> 'hh:mm',
		'hide_front_end'	=> true,
		'backend_only'		=> true,
		'backend_display_type'	=> 'custom',
		'column_callback'   => 'wpas_cf_display_time_hhmm',
		'save_callback'     => 'wpas_cf_save_time_hhmm',
		'sortable_column'	=> true,
		'title'       		=> $as_label_for_gross_time_singular,
		'desc'       		=> __( 'Enter the cummulative time spent on ticket by the agent', 'awesome-support' ),
		'readonly'			=> $allow_agents_to_enter_time
	) );

	wpas_add_custom_field( 'ttl_adjustments_to_time_spent_on_ticket', array(
		'core'        		=> false,
		'show_column' 		=> $show_total_time_adj_in_list,
		'log'         		=> $audit_log_for_time_tracking_fields,
		'html5_pattern'		=> '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){1}',
		'placeholder'		=> 'hh:mm',
		'hide_front_end'	=> true,
		'backend_only'		=> true,
		'backend_display_type'	=> 'custom',
		//'column_callback'   => 'wpas_cf_display_time_hhmm',
		'column_callback'   => 'wpas_cf_display_time_adjustment_column',
		'save_callback'     => 'wpas_cf_save_time_hhmm',
		'sortable_column'	=> true,
		'title'       		=> $as_label_for_time_adjustments_singular,
		'desc'       		=> __( 'Enter any adjustments or credits granted to the customer - generally filled in by a supervisor or admin.', 'awesome-support' ),
		'readonly'			=> $allow_agents_to_enter_time
	) );
	
	wpas_add_custom_field( 'time_adjustments_pos_or_neg', array(
		'core'        		=> false,
		'field_type'		=> 'radio',
		'options' 			=> array( '+' => '+ive', '-' => '-ive' ),
		'show_column' 		=> false,
		'log'         		=> false,
		'hide_front_end'	=> true,
		'backend_only'		=> true,
		'backend_display_type'	=> 'custom',
		'title'       		=> $as_label_for_time_adjustments_dir_singular,
		'readonly'			=> $allow_agents_to_enter_time
	) );		

	wpas_add_custom_field( 'final_time_spent_on_ticket', array(
		'core'        		=> false,
		'show_column' 		=> $show_final_time_in_list,
		'log'         		=> $audit_log_for_time_tracking_fields,
		'html5_pattern'		=> '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){1}',
		'placeholder'		=> 'hh:mm',
		'hide_front_end'	=> true,		
		'backend_only'		=> true,
		'backend_display_type'	=> 'custom',
		'column_callback'   => 'wpas_cf_display_time_hhmm',
		'sortable_column'	=> true,
		'title'       		=> $as_label_for_final_time_singular,
		'desc'       		=> __( 'This is the time calculated by the system - a sum of gross time and adjustments/credits granted.', 'awesome-support' ),						
		'save_callback'     => 'wpas_update_time_spent_on_ticket',
		'readonly'          => true,
	) );
	
	wpas_add_custom_field( 'time_notes', array(
		'field_type'		=> 'wysiwyg',
		'core'        		=> false,
		'show_column' 		=> false,
		'log'         		=> false,
		'hide_front_end'	=> true,		
		'backend_only'		=> true,
		'backend_display_type'	=> 'custom',
		'title'       		=> $as_label_for_time_notes_singular,
		'readonly'			=> $allow_agents_to_enter_time		
	) );
	
	/*******************************************************************/
	/* Add fields for other "free-form" interested parties             */
	/*******************************************************************/

	/** Get the flag that controls whther to show these fields in the ticket list */
	$show_thirdparty01_in_list = false;
	$show_thirdparty01_in_list = ( isset( $options[ 'show_third_party_01_in_ticket_list' ] ) && true === boolval( $options[ 'show_third_party_01_in_ticket_list' ] ) );
	$show_thirdparty02_in_list = false;
	$show_thirdparty02_in_list = ( isset( $options[ 'show_third_party_02_in_ticket_list' ] ) && true === boolval( $options[ 'show_third_party_02_in_ticket_list' ] ) );

	/** Get the labels for these additional interested party fields if they are provided */
	$as_label_for_first_addl_interested_party_name_singular 			= isset( $options[ 'label_for_first_addl_interested_party_name_singular' ] ) ? $options[ 'label_for_first_addl_interested_party_name_singular' ] : __( 'Name Of Additional Interested Party #1', 'awesome-support' );
	$as_label_for_first_addl_interested_party_email_singular 			= isset( $options[ 'label_for_first_addl_interested_party_email_singular' ] ) ? $options[ 'label_for_first_addl_interested_party_email_singular' ] : __( 'Additional Interested Party Email #1', 'awesome-support' );
	$as_label_for_second_addl_interested_party_name_singular 			= isset( $options[ 'label_for_second_addl_interested_party_name_singular' ] ) ? $options[ 'label_for_second_addl_interested_party_name_singular' ] : __( 'Name Of Additional Interested Party #2', 'awesome-support' );
	$as_label_for_second_addl_interested_party_email_singular 			= isset( $options[ 'label_for_second_addl_interested_party_email_singular' ] ) ? $options[ 'label_for_second_addl_interested_party_email_singular' ] : __( 'Additional Interested Party Email #2', 'awesome-support' );

	wpas_add_custom_field( 'first_addl_interested_party_name', array(
		'core'           	=> false,
		'show_column'    	=> $show_thirdparty01_in_list,
		'sortable_column'	=> $show_thirdparty01_in_list,
		'filterable'        => $show_thirdparty01_in_list,
		'column_callback'	=> 'wpas_show_3rd_party01_column',
		'hide_front_end' 	=> true,
		'log'            	=> false,
		'title'          	=> $as_label_for_first_addl_interested_party_name_singular
	) );
	wpas_add_custom_field( 'first_addl_interested_party_email', array(
		'core'           	=> false,
		'show_column'    	=> false,  // set to false because this is handled by the callback function on the name field above
		'sortable_column'	=> false,
		'filterable'        => false,
		'hide_front_end' 	=> true,
		'log'            	=> false,
		'title'          	=> $as_label_for_first_addl_interested_party_email_singular
	) );
	wpas_add_custom_field( 'second_addl_interested_party_name', array(
		'core'           	=> false,
		'show_column'    	=> $show_thirdparty02_in_list,
		'sortable_column'	=> $show_thirdparty02_in_list,
		'filterable'        => $show_thirdparty02_in_list,
		'column_callback'	=> 'wpas_show_3rd_party02_column',
		'hide_front_end' 	=> true,
		'log'            	=> false,
		'title'          	=> $as_label_for_second_addl_interested_party_name_singular
	) );
	wpas_add_custom_field( 'second_addl_interested_party_email', array(
		'core'           	=> false,
		'show_column'    	=> false,  // set to false because this is handled by the callback function on the name field above
		'sortable_column'	=> false,
		'filterable'        => false,
		'hide_front_end' 	=> true,
		'log'            	=> false,
		'title'          	=> $as_label_for_second_addl_interested_party_email_singular
	) );
	
	
	/*******************************************************************/
	/* Add the IMPORTER fields - in this case only one.                */
	/*******************************************************************/	
	//if ( true === ( isset( $options[ 'importer_id_enable' ] ) && ( true === boolval( $options[ 'importer_id_enable' ] ) ) ) ) {
	$show_saas_id = false;
	$show_saas_id = ( isset( $options[ 'importer_id_enable' ] ) && ( true === boolval( $options[ 'importer_id_enable' ] ) ) ) ;
	
	if ( true === $show_saas_id ) {
		
		$show_saas_id_in_list = false;
		$show_saas_id_in_list = ( isset( $options[ 'importer_id_show_in_tkt_list' ] ) && true === boolval( $options[ 'importer_id_show_in_tkt_list' ] ) );	
		
		$saas_id_label = 'Help Desk SaaS Ticket ID';
		$saas_id_label = isset( $options[ 'importer_id_label' ] ) ? $options[ 'importer_id_label' ] : __( 'Help Desk SaaS Ticket ID', 'awesome-support' );

		wpas_add_custom_field( 'help_desk_ticket_id', array(
			'core'           	=> false,
			'show_column'    	=> $show_saas_id_in_list,
			'sortable_column'	=> true,
			'filterable'        => true,
			'backend_only' 		=> true,
			'log'            	=> true,
			'title'          	=> $saas_id_label,
		) );	
	}
	
	/* Trigger backend custom ticket list columns */
	if ( is_admin() ) {
		apply_filters( 'wpas_add_custom_fields', array() );
	}

}

add_action( 'admin_init', 'insert_channel_terms' );
/**
 * Make sure the channel terms are registered.  
 *
 * @since  3.6.0
 * @return void
 */
function insert_channel_terms() {
	wpas_add_default_channel_terms(false);
}