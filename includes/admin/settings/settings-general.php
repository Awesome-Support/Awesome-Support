<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_general', 5, 1 );
/**
 * Add plugin core settings.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_general( $def ) {

	$user_registration = boolval( get_option( 'users_can_register' ) );
	$registration_lbl  = ( true === $user_registration ) ? _x( 'allowed', 'User registration is allowed', 'awesome-support' ) : _x( 'not allowed', 'User registration is not allowed', 'awesome-support' );

	$settings = array(
		'general' => array(
			'name'    => __( 'General', 'awesome-support' ),
			'options' => array(
				array(
					'name' => __( 'Misc', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Default Assignee', 'awesome-support' ),
					'id'      => 'assignee_default',
					'type'    => 'select',
					'desc'    => __( 'Who to assign tickets to in the case that auto-assignment wouldn&#039;t work. This does NOT mean that all tickets will be assigned to this user. This is a fallback option. To enable/disable auto assignment for an agent, please do so in the user profile settings.', 'awesome-support' ),
					'options' => isset( $_GET['post_type'] ) && 'ticket' === $_GET['post_type'] && isset( $_GET['page'] ) && 'wpas-settings' === $_GET['page'] ? wpas_list_users( 'edit_ticket' ) : array(),
					'default' => ''
				),
				array(
					'name'    => __( 'Allow Registrations', 'awesome-support' ),
					'id'      => 'allow_registrations',
					'type'    => 'radio',
					'desc'    => sprintf( __( 'Allow users to register on the support page. This setting can be enabled even though the WordPress setting is disabled. Currently, registrations are %s by WordPress.', 'awesome-support' ),  "<strong>$registration_lbl</strong>" ),
					'default' => 'allow',
					'options' => array(
						'allow'           => __( 'Allow registrations', 'awesome-support' ),
						'disallow'        => __( 'Disallow registrations', 'awesome-support' ),
						'disallow_silent' => __( 'Disallow registrations without notice (just show the login form)', 'awesome-support' ),
					)
				),
				array(
					'name'    => __( 'Tickets Per Page (Front End)', 'awesome-support' ),
					'id'      => 'tickets_per_page_front_end',
					'type'    => 'text',
					'default' => 5,
					'desc'    => __( 'How many tickets per page should be displayed to the customer/client/end-user?', 'awesome-support' ),
				),				
				array(
					'name'    => __( 'Replies Order', 'awesome-support' ),
					'id'      => 'replies_order',
					'type'    => 'radio',
					'desc'    => __( 'In which order should the replies be displayed (for both client and admin side)?', 'awesome-support' ),
					'options' => array( 'ASC' => __( 'Old to New', 'awesome-support' ), 'DESC' => __( 'New to Old', 'awesome-support' ) ),
					'default' => 'ASC'
				),
				array(
					'name'    => __( 'Replies Per Page', 'awesome-support' ),
					'id'      => 'replies_per_page',
					'type'    => 'text',
					'default' => 10,
					'desc'    => __( 'How many replies should be displayed per page on a ticket details screen?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Hide Closed', 'awesome-support' ),
					'id'      => 'hide_closed',
					'type'    => 'checkbox',
					'desc'    => __( 'Only show open tickets when agents click the "All Tickets" link.', 'awesome-support' ),
					'default' => true
				),
				array(
					'name'    => __( 'Show Count', 'awesome-support' ),
					'id'      => 'show_count',
					'type'    => 'checkbox',
					'desc'    => __( 'Display the number of open tickets in the admin menu.', 'awesome-support' ),
					'default' => true
				),
				array(
					'name'    => __( 'Old Tickets', 'awesome-support' ),
					'id'      => 'old_ticket',
					'type'    => 'text',
					'default' => 10,
					'desc'    => __( 'After how many days should a ticket be considered &laquo;old&raquo;?', 'awesome-support' )
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
					'desc' => 'Use these options to control how the priority field is used and shown.  To change the labels used for this field please see our PRODUCTIVITY add-on.',
					'options' => wpas_get_priority_options()
				),
				
				array(
					'name' => __( 'Multiple Agents', 'awesome-support' ),
					'type' => 'heading',
					'desc' => 'Use these options to control whether multiple agents can actively handle a single ticket. To change the labels please see our PRODUCTIVITY add-on.'
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
					'desc' => 'Use these options to control whether third parties show in the ticket list.  To change the labels for 3rd party fields please see our PRODUCTIVITY add-on.'
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
					'desc'    => __( 'Show Third Party #1 Data in the Ticket List?', 'awesome-support' ),
					'default' => false
				),
				
				array(
					'name'    => __( 'Show Third Party #2 in Ticket List', 'awesome-support' ),
					'id'      => 'show_third_party_02_in_ticket_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Show Third Party #2 Data in the Ticket List?', 'awesome-support' ),
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
					'name' => __( 'Other Field Settings', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Show Channel Field', 'awesome-support' ),
					'id'      => 'channel_show_in_ticket_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Show Channel Field In Ticket List? (Channel allows you to select where a ticket originated - web, email, facebook etc.)', 'awesome-support' ),
					'default' => false
				),
				
				

				
				array(
					'name' => __( 'Plugin Pages', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'     => __( 'Ticket Submission', 'awesome-support' ),
					'id'       => 'ticket_submit',
					'type'     => 'select',
					'multiple' => true,
					'desc'     => sprintf( __( 'The page used for ticket submission. This page should contain the shortcode %s', 'awesome-support' ), '<code>[ticket-submit]</code>' ),
					'options'  => wpas_list_pages(),
					'default'  => ''
				),
				array(
					'name'     => __( 'Tickets List', 'awesome-support' ),
					'id'       => 'ticket_list',
					'type'     => 'select',
					'multiple' => false,
					'desc'     => sprintf( __( 'The page that will list all tickets for a client. This page should contain the shortcode %s', 'awesome-support' ), '<code>[tickets]</code>' ),
					'options'  => wpas_list_pages(),
					'default'  => ''
				),
				array(
					'name' => __( 'Terms & Conditions', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'terms_conditions',
					'type'     => 'editor',
					'default'  => '',
					'desc'     => __( 'Terms & conditions are not mandatory. If you add terms, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept your terms', 'awesome-support' ),
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				array(
					'name' => __( 'Credit', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Show Credit', 'awesome-support' ),
					'id'      => 'credit_link',
					'type'    => 'checkbox',
					'desc'    => __( 'Do you like this plugin? Please help us spread the word by displaying a credit link at the bottom of your ticket submission page.', 'awesome-support' ),
					'default' => false
				),
			)
		),
	);

	return array_merge( $def, $settings );

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
			'desc'    => __( 'Would you like to show the field to the end user (unchecked restricts it to admin use only)?', 'awesome-support' ),
			'default' => true
		),		

		array(
			'name'    => __( 'Show In Column List?', 'awesome-support' ),
			'id'      => 'support_priority_show_in_ticket_list',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like to show the field in the ticket listing?', 'awesome-support' ),
			'default' => false
		)		
		
	);
		
	
	return $priority;
}