<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_basic_time_tracking', 5, 1 );
/**
 * Add plugin core settings for basic time tracking
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function mumei_ayuda_core_basic_time_tracking( $def ) {

	$settings = array(
		'basictimetracking' => array(
			'name'    => __( 'Basic Time Tracking', 'ayuda-help-desk' ),
			'options' => array(
				array(
					'name' => __( 'Basic Time Tracking', 'ayuda-help-desk' ),
					'type' => 'heading',
					'options' => mumei_ayuda_get_basic_time_tracking_options()
				),
				array(
					'name' => __( 'Front-end Options', 'ayuda-help-desk' ),
					'id' => 'basic-time-tracking-front-end-options',
					'type' => 'heading',
					'options' => mumei_ayuda_get_basic_time_tracking_fe_options()
				),				
				
			)
		),
	);

	return array_merge( $def, apply_filters('mumei_ayuda_settings_basic_time_tracking', $settings )  );

}

/**
 * Prepare the available options for basic time tracking...
 *
 * @since 3.3.5
 * @return array
 */
function mumei_ayuda_get_basic_time_tracking_options() {

	$basic_time_tracking_options = array(
		array(
			'name'    => __( 'Show Basic Time Tracking Fields', 'ayuda-help-desk' ),
			'id'      => 'show_basic_time_tracking_fields',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to show the basic time tracking fields?', 'ayuda-help-desk' ),
			'default' => true
		),

		array(
			'name'    => __( 'Allow Agents To Enter Time', 'ayuda-help-desk' ),
			'id'      => 'allow_agents_to_enter_time',
			'type'    => 'checkbox',
			'desc'    => __( 'Can agents enter time?  If unchecked, agents cannot enter or adjust time and it is assumed that another add-on will do time tracking and update these fields', 'ayuda-help-desk' ),
			'default' => true
		),
		
		array(
			'name'    => __( 'Recalculate Final Time On Save', 'ayuda-help-desk' ),
			'id'      => 'recalculate_final_time_on_save',
			'type'    => 'checkbox',
			'desc'    => __( 'Recalculate the final time when the ticket is saved? This should be checked for manual time tracking not handled by another add-on.  It takes the original time, adds or subtracts the adjustments and enters the new amount in the final time field. This should be unchecked if another add-on is handling the time tracking and updates!', 'ayuda-help-desk' ),
			'default' => true
		),		
		
		array(
			'name'    => __( 'Keep Audit Log', 'ayuda-help-desk' ),
			'id'      => 'keep_audit_log_time_tracking',
			'type'    => 'checkbox',
			'desc'    => __( 'Adds an internal note to the ticket every time someone updates the basic time tracking fields', 'ayuda-help-desk' ),
			'default' => true
		),
		
		array(
			'name'    => __( 'Show Total Time In Ticket List', 'ayuda-help-desk' ),
			'id'      => 'show_total_time_in_ticket_list',
			'type'    => 'checkbox',
			'desc'    => __( 'Adds a column to the ticket list to show the total original time recorded for the ticket', 'ayuda-help-desk' ),
			'default' => false
		),
		
		array(
			'name'    => __( 'Show Total Time Adjustments In Ticket List', 'ayuda-help-desk' ),
			'id'      => 'show_total_time_adj_in_ticket_list',
			'type'    => 'checkbox',
			'desc'    => __( 'Adds a column to the ticket list to show the time adjustments recorded for the ticket', 'ayuda-help-desk' ),
			'default' => false
		),
		
		array(
			'name'    => __( 'Show Final Recorded Time In Ticket List', 'ayuda-help-desk' ),
			'id'      => 'show_final_time_in_ticket_list',
			'type'    => 'checkbox',
			'desc'    => __( 'Adds a column to the ticket list to show the final time recorded for the ticket', 'ayuda-help-desk' ),
			'default' => false
		)		
	);
		
	
	return $basic_time_tracking_options;
}

/**
 * Prepare the available options for basic time tracking - front-end options...
 *
 * @since 5.8.1
 * @return array
 */
function mumei_ayuda_get_basic_time_tracking_fe_options() {

	$basic_time_tracking_options = array(
		array(
			'name'    => __( 'Show Final Time On Front-end Ticket List', 'ayuda-help-desk' ),
			'id'      => 'show_final_time_in_fe_ticket_list',
			'type'    => 'checkbox',
			'desc'    => __( 'Turn this on to allow the client to see the final time recorded in the front-end ticket list', 'ayuda-help-desk' ),
			'default' => false
		),

		array(
			'name'    => __( 'Show Final Time On Front-end Ticket', 'ayuda-help-desk' ),
			'id'      => 'show_final_time_in_fe_ticket',
			'type'    => 'checkbox',
			'desc'    => __( 'Turn this on to allow the client to see the final time recorded in the front-end ticket', 'ayuda-help-desk' ),
			'default' => false
		),		

	);
		
	
	return $basic_time_tracking_options;
}