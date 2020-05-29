<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_fields', 5, 1 );
/**
 * Add plugin core settings for fields and custom fields.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_fields( $def ) {

	$settings = array(
		'Fields' => array(
			'name'    => __( 'Fields', 'awesome-support' ),
			'options' => array(
				array(
					'name' => __( 'Departments', 'awesome-support' ),
					'type' => 'heading',
				),
			array(
					'name'    => __( 'Departments', 'awesome-support' ),
					'id'      => 'departments',
					'type'    => 'checkbox',
					'desc'    => __( 'Enable departments management.', 'awesome-support' ),
					'default' => false
				),
				array(
					'name' => __( 'Priority Management', 'awesome-support' ),
					'type' => 'heading',
					'desc' => 'Use these options to control how the priority field is used and shown.  To change the labels used for this field please see our POWER-PACK add-on.',
					'options' => wpas_get_priority_options()
				),
				array(
					'name' => __( 'Ticket Type Management', 'awesome-support' ),
					'type' => 'heading',
					'desc' => 'Use these options to control how the Ticket Type options are used and shown.  To change the labels used for this field please see our POWER-PACK add-on.',
					'options' => wpas_get_ticket_type_options()
				),				
				
				array(
					'name' => __( 'Multiple Agents', 'awesome-support' ),
					'type' => 'heading',
					'desc' => 'Use these options to control whether multiple agents can actively handle a single ticket. To change the labels please see our POWER-PACK add-on.'
				),
				array(
					'name'    => __( 'Enable Multiple Agents Per Ticket', 'awesome-support' ),
					'id'      => 'multiple_agents_per_ticket',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the two extra agent fields on the ticket?', 'awesome-support' ),
					'default' => false
				),
				
				array(
					'name'    => __( 'Show Secondary Agent In Ticket List', 'awesome-support' ),
					'id'      => 'show_secondary_agent_in_ticket_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the secondary agent in the ticket list?', 'awesome-support' ),
					'default' => false
				),
				
				array(
					'name'    => __( 'Show Tertiary Agent In Ticket List', 'awesome-support' ),
					'id'      => 'show_tertiary_agent_in_ticket_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the Tertiary agent in the ticket list?', 'awesome-support' ),
					'default' => false
				),				
				
				array(
					'name' => __( 'Third Parties', 'awesome-support' ),
					'type' => 'heading',
					'desc' => 'Use these options to control whether third parties show in the ticket list.  To change the labels for 3rd party fields please see our POWER-PACK add-on.'
				),
				
				array(
					'name'    => __( 'Enable Third Party Fields', 'awesome-support' ),
					'id'      => 'show_third_party_fields',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the third party fields on the ticket?', 'awesome-support' ),
					'default' => false
				),				

				array(
					'name'    => __( 'Show Third Party #1 in Ticket List', 'awesome-support' ),
					'id'      => 'show_third_party_01_in_ticket_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Show Third Party #1 data in the ticket list?', 'awesome-support' ),
					'default' => false
				),
				
				array(
					'name'    => __( 'Show Third Party #2 in Ticket List', 'awesome-support' ),
					'id'      => 'show_third_party_02_in_ticket_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Show Third Party #2 data in the ticket list?', 'awesome-support' ),
					'default' => false
				),				

				array(
					'name' => __( 'Show Date Fields in the Activity Column', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'The settings below control which dates show up in the activity column in the ticket list. The more fields you turn on the taller the row. Tall rows mean you can view fewer tickets on one screen. Sometimes, though, seeing all these dates can help with troubleshooting issues especially those related to reporting.', 'awesome-support' ),
				),
				array(
					'name'    => __( 'Show Open Date ', 'awesome-support' ),
					'id'      => 'show_open_date_in_activity_column',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the open date in the activity column?', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'Show Open Date in GMT', 'awesome-support' ),
					'id'      => 'show_open_date_gmt_in_activity_column',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the open date in GMT in the activity column?', 'awesome-support' ),
					'default' => false
				),				
				array(
					'name'    => __( 'Show Close Date in GMT', 'awesome-support' ),
					'id'      => 'show_close_date_gmt_in_activity_column',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the close date in GMT in the activity column?', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'Show Length Of Time Ticket Was Opened', 'awesome-support' ),
					'id'      => 'show_length_of_time_ticket_was_opened',
					'type'    => 'checkbox',
					'desc'    => __( 'Show how long the ticket was opened?  Note that this applies to closed tickets only.', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'Show Extended Date In Replies', 'awesome-support' ),
					'id'      => 'show_extended_date_in_replies',
					'type'    => 'checkbox',
					'desc'    => __( 'Hovering over replies can show a short date or a full date-time stamp.  Turn this on to show the full date-time stamp as well as a human-readable number indicating the age of the reply.', 'awesome-support' ),
					'default' => false
				),				
				
				
				array(
					'name' => __( 'Other Field Settings', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Show Channel Field', 'awesome-support' ),
					'id'      => 'channel_show_in_ticket_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Show Channel field in the ticket list? (Channel allows you to select where a ticket originated - web, email, facebook etc.)', 'awesome-support' ),
					'default' => false
				),
				
				array(
					'name' => __( 'Custom Fields', 'awesome-support' ),
					'type' => 'heading',
				),

				array(
					'type' => 'Note',
					'desc'    => sprintf( __( 'We have two options that allow you to create custom fields. Please read <b><u><a %s>this article</a></b></u> on our website to learn about them.', 'awesome-support' ), 'href="https://getawesomesupport.com/documentation/awesome-support/custom-fields/" target="_blank" ' )
				),								
			)
		),
	);

	return array_merge( $def, apply_filters('wpas_settings_fields', $settings )  );

}

/**
 * Prepare the available options for priority
 *
 * @since 3.3.5
 * @return array
 */
function wpas_get_priority_options() {

	$priority = array(
		array(
			'name'    => __( 'Use Priority Field', 'awesome-support' ),
			'id'      => 'support_priority',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to use the priority field in your tickets?', 'awesome-support' ),
			'default' => false
		),

		array(
			'name'    => __( 'Mandatory?', 'awesome-support' ),
			'id'      => 'support_priority_mandatory',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to make the priority field mandatory in your tickets?', 'awesome-support' ),
			'default' => false
		),

		array(
			'name'    => __( 'Show On Front End?', 'awesome-support' ),
			'id'      => 'support_priority_show_fe',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to show the field to the end user (unchecked restricts it to agent/admin use only)?', 'awesome-support' ),
			'default' => true
		),		

		array(
			'name'    => __( 'Show In Column List?', 'awesome-support' ),
			'id'      => 'support_priority_show_in_ticket_list',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to show the field in the ticket listing?', 'awesome-support' ),
			'default' => false
		),
		
		array(
			'name'    => __( 'Color-code Ticket Header?', 'awesome-support' ),
			'id'      => 'support_priority_color_code_ticket_header',
			'type'    => 'checkbox',
			'desc'    => __( 'Checking this box will color the top border of the opening post to match the priority color', 'awesome-support' ),
			'default' => false
		),				
		
	);
	
	return $priority;
}

/**
 * Prepare the available options for ticket-type
 *
 * @since 5.8.1
 * @return array
 */
function wpas_get_ticket_type_options() {

	$ticket_type = array(
		array(
			'name'    => __( 'Enable Ticket Types', 'awesome-support' ),
			'id'      => 'support_ticket_type',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to show the Ticket Type options in your tickets?', 'awesome-support' ),
			'default' => false
		),

		array(
			'name'    => __( 'Mandatory?', 'awesome-support' ),
			'id'      => 'support_ticket_type_mandatory',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to make the Ticket Type options mandatory in your tickets?', 'awesome-support' ),
			'default' => false
		),

		array(
			'name'    => __( 'Show On Front End?', 'awesome-support' ),
			'id'      => 'support_ticket_type_show_fe',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to show the field to the end user (unchecked restricts it to agent/admin use only)?', 'awesome-support' ),
			'default' => true
		),		

		array(
			'name'    => __( 'Show In Column List?', 'awesome-support' ),
			'id'      => 'support_ticket_type_show_in_ticket_list',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to show the field in the ticket listing?', 'awesome-support' ),
			'default' => false
		),
		
		array(
			'name'    => __( 'Color-code Bottom Border?', 'awesome-support' ),
			'id'      => 'support_ticket_type_color_code_ticket',
			'type'    => 'checkbox',
			'desc'    => __( 'Checking this box will color the bottom border of the opening post to match the Ticket Type color', 'awesome-support' ),
			'default' => false
		),				
		
	);
	
	return apply_filters( 'wpas_ticket_type_options', $ticket_type );
}