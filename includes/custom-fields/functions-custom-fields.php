<?php
/**
 * Submission Form Functions.
 *
 * This file contains all the functions related to the ticket submission form.
 * Those functions are being used on the front-end only and aren't used anywhere
 * else than the submission form.
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

	return $field->get_field_value( $default, $post_id );
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

	$result = 0;

	// No time spent on this ticket
	if ( ! isset ($_POST['wpas_ttl_calculated_time_spent_on_ticket']) ) {
		return $result;
	}

	$hours = $minutes = $seconds = 0;

	// Time spent on ticket (hh:mm:ss)
	sscanf( $_POST['wpas_ttl_calculated_time_spent_on_ticket'], "%d:%d:%d", $hours, $minutes, $seconds);

	// Convert to seconds
	$calculated_time = $hours * 3600 + $minutes * 60 + $seconds;

	// Calculate time adjustment
	if( isset ( $_POST['wpas_ttl_adjustments_to_time_spent_on_ticket'] )
		&& ! empty( $_POST['wpas_ttl_adjustments_to_time_spent_on_ticket'] )
	) {
		sscanf( $_POST['wpas_ttl_adjustments_to_time_spent_on_ticket'], "%d:%d:%d", $hours, $minutes, $seconds);
		$adjustment_time = $hours * 3600 + $minutes * 60 + $seconds;

		if( '+' === $_POST['wpas_time_adjustments_pos_or_neg'] ) {
			$seconds = $calculated_time + $adjustment_time;
		}
		else {
			$seconds = $calculated_time - $adjustment_time;
		}
	}

	// No adjustment
	else {
		$seconds = $calculated_time;
	}

	$value = sprintf("%02d:%02d:%02d", floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);

	/**
	 * Get the current field value.
	 */
	$current = get_post_meta( $post_id, $field_id, true );

	/* Action: Update post meta */
	if ( ( ! empty( $current ) || is_null( $current ) ) && ! empty( $value ) ) {
		if ( $current !== $value ) {
			if ( false !== update_post_meta( $post_id, $field_id, $value, $current ) ) {
				$result = 2;
			}
		}
	}

	/* Action: Add post meta */
	elseif ( empty( $current ) && ! empty( $value ) ) {
		if ( false !== add_post_meta( $post_id, $field_id, $value, true ) ) {
			$result = 1;
		}
	}

	return array( 'result' => $result, 'value' => $value );

}


add_action( 'init', 'wpas_register_core_fields' );
/**
 * Register the cure custom fields.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_register_core_fields() {

	/** Determine if assignee column is shown in tickets list */
	$show_assignee = current_user_can( 'administrator' )
	&& true === boolval( wpas_get_option( 'admin_see_all' ) )
	|| current_user_can( 'edit_ticket' )
	&& !current_user_can( 'administrator' )
	&& true === boolval( wpas_get_option( 'agent_see_all' ) )
		? true : false;

	wpas_add_custom_field( 'assignee', array(
		'core'            => true,
		'show_column'     => $show_assignee,
		'sortable_column' => $show_assignee,
		'filterable'      => $show_assignee,
		'column_callback' => 'wpas_show_assignee_column',
		'log'             => true,
		'title'           => __( 'Agent', 'awesome-support' )
	) );

	wpas_add_custom_field( 'status', array(
		'core'            => true,
		'show_column'     => true,
		'log'             => false,
		'field_type'      => false,
		'sortable_column' => true,
		'column_callback' => 'wpas_cf_display_status',
		'save_callback'   => null,
		'title'           => __( 'Status', 'awesome-support' )		
	) );

	wpas_add_custom_field( 'ticket-tag', array(
		'core'                  => true,
		'show_column'           => true,
		'log'                   => true,
		'field_type'            => 'taxonomy',
		'sortable_column'       => true,
		'taxo_std'              => false,
//		'column_callback'       => 'wpas_cf_display_status',
		'column_callback'       => 'wpas_show_taxonomy_column',
		'save_callback'         => null,
		'label'                 => __( 'Tag', 'awesome-support' ),
		'name'                  => __( 'Tag', 'awesome-support' ),
		'label_plural'          => __( 'Tags', 'awesome-support' ),
		'taxo_hierarchical'     => false,
		'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
		'select2'               => false,
		'title'           		=> __( 'Tag', 'awesome-support' )		
	) );

	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

	if ( isset( $options[ 'support_products' ] ) && true === boolval( $options[ 'support_products' ] ) ) {

		$slug = defined( 'WPAS_PRODUCT_SLUG' ) ? WPAS_PRODUCT_SLUG : 'product';

		/* Filter the taxonomy labels */
		$labels = apply_filters( 'wpas_product_taxonomy_labels', array(
				'label'        => __( 'Product', 'awesome-support' ),
				'name'         => __( 'Product', 'awesome-support' ),
				'label_plural' => __( 'Products', 'awesome-support' )
			)
		);

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
			'title'           		=> __( 'Product', 'awesome-support' )			
		) );

	}

	/* Add Department fields */
	if ( isset( $options[ 'departments' ] ) && true === boolval( $options[ 'departments' ] ) ) {

		$slug = defined( 'WPAS_DEPARTMENT_SLUG' ) ? WPAS_DEPARTMENT_SLUG : 'department';

		/* Filter the taxonomy labels */
		$labels = apply_filters( 'wpas_department_taxonomy_labels', array(
			'label'        => __( 'Department', 'awesome-support' ),
			'name'         => __( 'Department', 'awesome-support' ),
			'label_plural' => __( 'Departments', 'awesome-support' )
		) );

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
			'title'           		=> __( 'Department', 'awesome-support' )			
		) );

	}

	/* Add priority fields */
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


		/* Filter the taxonomy labels */
		$labels = apply_filters( 'wpas_priority_taxonomy_labels', array(
				'label'        => __( 'Priority', 'awesome-support' ),
				'name'         => __( 'Priority', 'awesome-support' ),
				'label_plural' => __( 'Priorities', 'awesome-support' )
			)
		);

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
			'filterable'            => true,
			'required'              => $show_priority_required,
			'title'           		=> __( 'Priority', 'awesome-support' )			
		) );

	}

	/* Add ticket channel field (where did the ticket originate from?) */
	$slug = defined( 'WPAS_CHANNEL_SLUG' ) ? WPAS_CHANNEL_SLUG : 'ticket_channel';

	$labels = apply_filters( 'wpas_channel_taxonomy_labels', array(
			'label'        => __( 'Channel', 'awesome-support' ),
			'name'         => __( 'Channel', 'awesome-support' ),
			'label_plural' => __( 'Channels', 'awesome-support' )
		)
	);

	$show_channel_column_in_list = ( isset( $options[ 'channel_show_in_ticket_list' ] ) && true === boolval( $options[ 'channel_show_in_ticket_list' ] ) );

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
		'filterable'            => $show_channel_column_in_list,
		'default'               => 'standard ticket form',
		'title'           		=> __( 'Channel', 'awesome-support' )		
	) );

	/* Add additional assignees to ticket */
	if ( isset( $options[ 'multiple_agents_per_ticket' ] ) && true === boolval( $options[ 'multiple_agents_per_ticket' ] ) ) {
		wpas_add_custom_field( 'secondary_assignee', array(
			'core'           => false,
			'show_column'    => false,
			'hide_front_end' => true,
			'log'            => true,
			'title'          => __( 'Additional Support Staff #1', 'awesome-support' )
		) );

		wpas_add_custom_field( 'tertiary_assignee', array(
			'core'           => false,
			'hide_front_end' => true,
			'show_column'    => false,
			'log'            => true,
			'title'          => __( 'Additional Support Staff #2', 'awesome-support' )
		) );
	}

	/* Add fields to store the number of replies on a ticket. 				*/
	/* These will be used for reporting purposes in a new reporting add-on 	*/
	wpas_add_custom_field( 'ttl_replies_by_agent', array(
		'core'        => true,
		'show_column' => false,
		'log'         => false,
		'title'       => __( 'Number of Replies By Agent', 'awesome-support' )
	) );

	wpas_add_custom_field( 'ttl_replies_by_customer', array(
		'core'        => true,
		'show_column' => false,
		'log'         => false,
		'title'       => __( 'Number of Replies By Customer', 'awesome-support' )
	) );

	wpas_add_custom_field( 'ttl_replies', array(
		'core'        => true,
		'show_column' => false,
		'log'         => false,
		'title'       => __( 'Total Replies On Ticket', 'awesome-support' )
	) );

	/* Add fields to store time spent working on a ticket. */
	$audit_log_for_time_tracking_fields = false ;
	$audit_log_for_time_tracking_fields = ( isset( $options[ 'keep_audit_log_time_tracking' ] ) && true === boolval( $options[ 'keep_audit_log_time_tracking' ] ) );
	
	
	$show_total_time_in_list = false;
	$show_total_time_in_list = ( isset( $options[ 'show_total_time_in_ticket_list' ] ) && true === boolval( $options[ 'show_total_time_in_ticket_list' ] ) );
	
	$show_total_time_adj_in_list = false;
	$show_total_time_adj_in_list = ( isset( $options[ 'show_total_time_adj_in_ticket_list' ] ) && true === boolval( $options[ 'show_total_time_adj_in_ticket_list' ] ) );
	
	$show_final_time_in_list = false;
	$show_final_time_in_list = ( isset( $options[ 'show_final_time_in_ticket_list' ] ) && true === boolval( $options[ 'show_final_time_in_ticket_list' ] ) );

	
	wpas_add_custom_field( 'ttl_calculated_time_spent_on_ticket', array(
		'core'        		=> false,
		'show_column' 		=> $show_total_time_in_list,
		'log'         		=> $audit_log_for_time_tracking_fields,
		'html5_pattern'		=> '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){2}',
		'placeholder'		=> 'hh:mm:ss',
		'hide_front_end'	=> true,
		'backend_only'		=> true,
		'backend_display_type'	=> 'custom',
		'sortable_column'	=> true,
		'title'       		=> __( 'Time Spent on Ticket', 'awesome-support' )
	) );

	wpas_add_custom_field( 'ttl_adjustments_to_time_spent_on_ticket', array(
		'core'        		=> false,
		'show_column' 		=> $show_total_time_adj_in_list,
		'log'         		=> $audit_log_for_time_tracking_fields,
		'html5_pattern'		=> '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){2}',		
		'placeholder'		=> 'hh:mm:ss',	
		'hide_front_end'	=> true,
		'backend_only'		=> true,
		'backend_display_type'	=> 'custom',
		'column_callback'   => 'wpas_cf_display_time_adjustment_column',
		'sortable_column'	=> true,
		'title'       		=> __( 'Adjustments For Time Spent On Ticket', 'awesome-support' )
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
		'title'       		=> __( '+ive or -ive Adj?', 'awesome-support' )		
	) );		

	wpas_add_custom_field( 'final_time_spent_on_ticket', array(
		'core'        		=> false,
		'show_column' 		=> $show_final_time_in_list,
		'log'         		=> $audit_log_for_time_tracking_fields,
		'html5_pattern'		=> '(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){2}',		
		'placeholder'		=> 'hh:mm:ss',	
		'hide_front_end'	=> true,		
		'backend_only'		=> true,
		'backend_display_type'	=> 'custom',
		'sortable_column'	=> true,
		'title'       		=> __( 'Final Amount Of Time Spent On Ticket', 'awesome-support' ),
		'save_callback'     => 'wpas_update_time_spent_on_ticket',
		'readonly'          => true,
	) );

	/* Add fields for other "free-form" interested parties */
	wpas_add_custom_field( 'first_addl_interested_party_name', array(
		'core'           => false,
		'show_column'    => false,
		'hide_front_end' => true,
		'log'            => false,
		'title'          => __( 'Name Of Additional Interested Party (#1)', 'awesome-support' )
	) );
	wpas_add_custom_field( 'first_addl_interested_party_email', array(
		'core'           => false,
		'show_column'    => false,
		'hide_front_end' => true,
		'log'            => false,
		'title'          => __( 'Additional Interested Party Email (#1)', 'awesome-support' )
	) );
	wpas_add_custom_field( 'second_addl_interested_party_name', array(
		'core'           => false,
		'show_column'    => false,
		'hide_front_end' => true,
		'log'            => false,
		'title'          => __( 'Name Of Additional Interested Party (#2)', 'awesome-support' )
	) );
	wpas_add_custom_field( 'second_addl_interested_party_email', array(
		'core'           => false,
		'show_column'    => false,
		'hide_front_end' => true,
		'log'            => false,
		'title'          => __( 'Additional Interested Party Email (#2)', 'awesome-support' )
	) );

	/* Trigger backend custom ticket list columns */
	if ( is_admin() ) {
		apply_filters( 'wpas_add_custom_fields', array() );
	}

}