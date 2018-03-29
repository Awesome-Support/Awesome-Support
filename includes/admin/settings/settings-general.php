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
					'name' => __( 'General', 'awesome-support' ),
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
                        'name'    => __( 'Use SELECT2 For Staff Drop-downs', 'awesome-support' ),
                        'id'      => "support_staff_select2_enabled",
                        'type'    => 'checkbox',
                        'default' => false,
                        'desc'    => __( 'On ticket screen turn the staff dropdown into select2 box.', 'awesome-support' )
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
					'name'    => __( 'Hide Closed (Admin)', 'awesome-support' ),
					'id'      => 'hide_closed',
					'type'    => 'checkbox',
					'desc'    => __( 'Only show open tickets when agents click the "All Tickets" link.', 'awesome-support' ),
					'default' => true
				),
				array(
					'name'    => __( 'Hide Closed (Front End)', 'awesome-support' ),
					'id'      => 'hide_closed_fe',
					'type'    => 'checkbox',
					'desc'    => __( 'Only show open tickets to clients on the front-end.', 'awesome-support' ),
					'default' => false
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
					'name' => __( 'Ticket List Tabs', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Control whether certain tabs are visible at the top of the admin ticket list', 'awesome-support' ),					
				),
				array(
					'name'    => __( 'Show Documentation Tab', 'awesome-support' ),
					'id'      => 'ticket_list_show_doc_tab',
					'type'    => 'checkbox',
					'default' => true
				),
				array(
					'name'    => __( 'Show Bulk Actions Tab', 'awesome-support' ),
					'id'      => 'ticket_list_show_bulk_actions_tab',
					'type'    => 'checkbox',
					'default' => true
				),
				array(
					'name'    => __( 'Show Preferences Tab', 'awesome-support' ),
					'id'      => 'ticket_list_show_preferences_tab',
					'type'    => 'checkbox',
					'default' => true
				),
				
				array(
					'name' => __( 'Ticket Details Tabs And Metaboxes', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Control who can view certain ticket tabs on the ticket detail screen in wp-admin', 'awesome-support' ),					
				),
				array(
					'name'    => __( 'Roles That Are NOT Allowed Access To The Custom Fields Tab', 'awesome-support' ),
					'id'      => 'hide_cf_tab_roles',
					'type'    => 'text',
					'desc'    => __( 'Enter a comma separated list of roles that should not see the CUSTOM FIELDS tab. Roles should be the internal WordPress role id such as wpas_support_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
					'default' => ''
				),
				array(
					'name'    => __( 'Roles That Are NOT Allowed Access To The Additional Interested Parties Tab', 'awesome-support' ),
					'id'      => 'hide_ai_tab_roles',
					'type'    => 'text',
					'desc'    => __( 'Enter a comma separated list of roles that should not see the ADDITIONAL INTERESTED PARTIES tab. Roles should be the internal WordPress role id such as wpas_support_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
					'default' => ''
				),
				array(
					'name'    => __( 'Roles That Are NOT Allowed Access To The Tags Metabox', 'awesome-support' ),
					'id'      => 'hide_tags_mb_roles',
					'type'    => 'text',
					'desc'    => __( 'Enter a comma separated list of roles that should not see the tags metabox. Roles should be the internal WordPress role id such as wpas_support_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
					'default' => ''
				),				
				
				array(
					'name' => __( 'Redirects', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Configure where the user should be sent after certain actions', 'awesome-support' ),					
				),
				array(
					'name'    => __( 'Logout Redirect', 'awesome-support' ),
					'id'      => 'logout_redirect_fe',
					'type'    => 'text',
					'desc' 	  => __( 'When the user clicks the logout button on an Awesome Support page, where should they be redirected to?  Enter the FULL url starting with http or https.', 'awesome-support' ),
				),
				array(
					'name'    => __( 'New Ticket Redirect', 'awesome-support' ),
					'id'      => 'new_ticket_redirect_fe',
					'type'    => 'text',
					'desc' 	  => __( 'When the user enters a new ticket they are usually taken to the newly entered ticket.  But, if you would like to redirect them someplace else, enter that location here. Enter the FULL url starting with http or https.', 'awesome-support' ),
				),				
				
				
				array(
					'name' => __( 'Toolbars', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Control whether certain toolbars are visible', 'awesome-support' ),					
				),
				array(
					'name'    => __( 'Show Ticket Details Toolbar', 'awesome-support' ),
					'id'      => 'ticket_detail_show_toolbar',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Show the toolbar on the ticket detail screen when an agent is viewing the ticket?', 'awesome-support' ),
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
				array(
					'name'    => __( 'Admin Rating Request', 'awesome-support' ),
					'id'      => 'remove_admin_ratings_request',
					'type'    => 'checkbox',
					'desc'    => __( 'Remove the rating request footer in the admin screen.', 'awesome-support' ),
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