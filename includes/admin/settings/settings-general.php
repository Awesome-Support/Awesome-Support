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
					'name' => __( 'General Admin and Agent Options', 'awesome-support' ),
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
					'name'    => __( 'Hide Closed Tickets', 'awesome-support' ),
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
					'name' => __( 'Front-end Options', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'These settings control the user experience when they submit or view their tickets', 'awesome-support' ),
				),
				array(
					'name'    => __( 'Tickets Per Page', 'awesome-support' ),
					'id'      => 'tickets_per_page_front_end',
					'type'    => 'text',
					'default' => 5,
					'desc'    => __( 'How many tickets per page should be displayed to the customer/client/end-user?', 'awesome-support' ),
				),								
				array(
					'name'    => __( 'Hide Closed Tickets', 'awesome-support' ),
					'id'      => 'hide_closed_fe',
					'type'    => 'checkbox',
					'desc'    => __( 'Only show open tickets to clients on the front-end.', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'Hide Ticket ID', 'awesome-support' ),
					'id'      => 'hide_ticket_id_title_fe',
					'type'    => 'checkbox',
					'desc'    => __( 'Do not show the ticket id in the title when viewing the ticket list', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'Show Close Ticket Checkbox', 'awesome-support' ),
					'id'      => 'allow_user_to_close_tickets',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the checkbox that allow users to close tickets. This affects ALL users. (If you would like to restrict closing tickets to only some users, use WordPress roles and the close_ticket capability instead.)', 'awesome-support' ),
					'default' => true
				),				
				
				/* Notification buttons */
				array(
					'name' => __( 'Notification Button', 'awesome-support' ),
					'desc' => __( 'Options for the notification button at the top of the single ticket screen on the front-end', 'awesome-support' ),					
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'awesome-support' ),
					'id'      => 'enable_notification_button',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Show the notification button on the front-end?', 'awesome-support' )
				),
				array(
					'name'     => __( 'Button Label', 'awesome-support' ),
					'desc'    => __( 'This is the label for the button', 'awesome-support' ),										
					'id'       => 'notifications_button_label',
					'type'     => 'text',
					'default'  => __( 'Notifications', 'awesome-support' ),					
				),				
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'desc'    => __( 'This is the message that the user will see when they click the notifications button', 'awesome-support' ),										
					'id'       => 'notifications_button_msg',
					'type'     => 'editor',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 ),
					'default'  => __( 'You are receiving the default standard notifications for this ticket. Among others, they include replies from agents, a notification when the ticket is closed, a notification if the ticket is reopened by the agent and a confirmation when the ticket was first submitted. ', 'awesome-support' ),					
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
					'desc' 	  => __( 'After the user enters a new ticket they are usually taken to the newly entered ticket.  But, if you would like to redirect them someplace else, enter that location here. Enter the FULL url starting with http or https.', 'awesome-support' ),
				),
				array(
					'name'    => __( 'New Ticket Form Redirect', 'awesome-support' ),
					'id'      => 'new_ticket_form_redirect_fe',
					'type'    => 'text',
					'desc' 	  => __( 'If you would like to use a custom form for your new ticket form but still use our login screen then enter the full URL to the custom form. An example where this would be useful would be if you are using a Gravity Form in conjunction with our Gravity Form bridge. Enter the FULL url starting with http or https. Note that if you use this option you will never be able to see or use our standard ticket form! ', 'awesome-support' ),
				),				
				
				array(
					'name' => __( 'Toolbars', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Control whether certain toolbars are visible to agents', 'awesome-support' ),					
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
					'desc' => __( 'Configure pages where tickets will be displayed - we take special actions when these pages are viewed by the user', 'awesome-support' ),
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
					'name' => __( 'Misc', 'awesome-support' ),
					'type' => 'heading',
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

	return array_merge( $def, apply_filters('wpas_settings_general', $settings )  );

}