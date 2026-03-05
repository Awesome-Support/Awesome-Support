<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_settings_general', 5, 1 );
/**
 * Add plugin core settings.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function mumei_ayuda_core_settings_general( $def ) {

	$user_registration = boolval( get_option( 'users_can_register' ) );
	$registration_lbl  = ( true === $user_registration ) ? _x( 'allowed', 'User registration is allowed', 'ayuda-help-desk' ) : _x( 'not allowed', 'User registration is not allowed', 'ayuda-help-desk' );

	// translators: %s is the shortcode value.
	$desc = __( 'The page used for ticket submission. This page should contain the shortcode %s', 'ayuda-help-desk' );

	// translators: %s is the shortcode value.
	$desc1 = __( 'The page that will list all tickets for a client. This page should contain the shortcode %s', 'ayuda-help-desk' );
	
	$settings = array(
		'general' => array(
			'name'    => __( 'General', 'ayuda-help-desk' ),
			'options' => array(
				array(
					'name' => __( 'General Admin and Agent Options', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Default Assignee', 'ayuda-help-desk' ),
					'id'      => 'assignee_default',
					'type'    => 'select',
					'desc'    => __( 'Who to assign tickets to in the case that auto-assignment wouldn&#039;t work. This does NOT mean that all tickets will be assigned to this user. This is a fallback option. To enable/disable auto assignment for an agent, please do so in the user profile settings.', 'ayuda-help-desk' ),
					'options' => isset( $_GET['post_type'] ) && 'ticket' === $_GET['post_type'] && isset( $_GET['page'] ) && 'wpas-settings' === $_GET['page'] ? mumei_ayuda_list_users( 'edit_ticket' ) : array(),
					'default' => ''
				),
				array(
                        'name'    => __( 'Use SELECT2 For Staff Drop-downs', 'ayuda-help-desk' ),
                        'id'      => "support_staff_select2_enabled",
                        'type'    => 'checkbox',
                        'default' => false,
                        'desc'    => __( 'On ticket screen turn the staff dropdown into select2 box.', 'ayuda-help-desk' )
                ),
				array(
					'name'    => __( 'Replies Order', 'ayuda-help-desk' ),
					'id'      => 'replies_order',
					'type'    => 'radio',
					'desc'    => __( 'In which order should the replies be displayed (for both client and admin side)?', 'ayuda-help-desk' ),
					'options' => array( 'ASC' => __( 'Old to New', 'ayuda-help-desk' ), 'DESC' => __( 'New to Old', 'ayuda-help-desk' ) ),
					'default' => 'ASC'
				),
				array(
					'name'    => __( 'Replies Per Page', 'ayuda-help-desk' ),
					'id'      => 'replies_per_page',
					'type'    => 'text',
					'default' => 10,
					'desc'    => __( 'How many replies should be displayed per page on a ticket details screen?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Hide Closed Tickets', 'ayuda-help-desk' ),
					'id'      => 'hide_closed',
					'type'    => 'checkbox',
					'desc'    => __( 'Only show open tickets when agents click the "All Tickets" link.', 'ayuda-help-desk' ),
					'default' => true
				),
				array(
					'name'    => __( 'Show Count', 'ayuda-help-desk' ),
					'id'      => 'show_count',
					'type'    => 'checkbox',
					'desc'    => __( 'Display the number of open tickets in the admin menu.', 'ayuda-help-desk' ),
					'default' => true
				),
				array(
					'name'    => __( 'Old Tickets', 'ayuda-help-desk' ),
					'id'      => 'old_ticket',
					'type'    => 'text',
					'default' => 10,
					'desc'    => __( 'After how many days should a ticket be considered &laquo;old&raquo;?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Automatically change ticket status when assigned', 'ayuda-help-desk' ),
					'id'      => 'turn_auto_change_status',
					'type'    => 'checkbox',
					'desc'    => __( 'When the ticket asignee is changed, the status is change to `In Progress`.', 'ayuda-help-desk' ),
					'default' => true
				),

				array(
					'name' => __( 'Front-end Options', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __( 'These settings control the user experience when they submit or view their tickets', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Tickets Per Page', 'ayuda-help-desk' ),
					'id'      => 'tickets_per_page_front_end',
					'type'    => 'text',
					'default' => 5,
					'desc'    => __( 'How many tickets per page should be displayed to the customer/client/end-user?', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Hide Closed Tickets', 'ayuda-help-desk' ),
					'id'      => 'hide_closed_fe',
					'type'    => 'checkbox',
					'desc'    => __( 'Only show open tickets to clients on the front-end.', 'ayuda-help-desk' ),
					'default' => false
				),
				array(
					'name'    => __( 'Hide Ticket ID', 'ayuda-help-desk' ),
					'id'      => 'hide_ticket_id_title_fe',
					'type'    => 'checkbox',
					'desc'    => __( 'Do not show the ticket id in the title when viewing the ticket list', 'ayuda-help-desk' ),
					'default' => false
				),
				array(
					'name'    => __( 'Show Close Ticket Checkbox', 'ayuda-help-desk' ),
					'id'      => 'allow_user_to_close_tickets',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the checkbox that allow users to close tickets. This affects ALL users. (If you would like to restrict closing tickets to only some users, use WordPress roles and the close_ticket capability instead.)', 'ayuda-help-desk' ),
					'default' => true
				),
				array(
					'name'    => __( 'Maximum Lenght in the Ticket Subject', 'ayuda-help-desk' ),
					'id'      => 'maximum_ticket_subject_front_end',
					'type'    => 'text',
					'default' => 0,
					'desc'    => __( 'Set the Maximum Lenght in the Ticket Subject field of the Submit Ticket Form on the front-end.', 'ayuda-help-desk' ),
				),

				/* Notification buttons */
				array(
					'name' => __( 'Notification Button', 'ayuda-help-desk' ),
					'desc' => __( 'Options for the notification button at the top of the single ticket screen on the front-end', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'ayuda-help-desk' ),
					'id'      => 'enable_notification_button',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Show the notification button on the front-end?', 'ayuda-help-desk' )
				),
				array(
					'name'     => __( 'Button Label', 'ayuda-help-desk' ),
					'desc'    => __( 'This is the label for the button', 'ayuda-help-desk' ),
					'id'       => 'notifications_button_label',
					'type'     => 'text',
					'default'  => __( 'Notifications', 'ayuda-help-desk' ),
				),
				array(
					'name'     => __( 'Content', 'ayuda-help-desk' ),
					'desc'    => __( 'This is the message that the user will see when they click the notifications button', 'ayuda-help-desk' ),
					'id'       => 'notifications_button_msg',
					'type'     => 'editor',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 ),
					'default'  => __( 'You are receiving the default standard notifications for this ticket. Among others, they include replies from agents, a notification when the ticket is closed, a notification if the ticket is reopened by the agent and a confirmation when the ticket was first submitted. ', 'ayuda-help-desk' ),
				),

				array(
					'name' => __( 'Redirects', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc'    => __( 'Configure where the user should be sent after certain actions', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Logout Redirect', 'ayuda-help-desk' ),
					'id'      => 'logout_redirect_fe',
					'type'    => 'text',
					'desc' 	  => __( 'When the user clicks the logout button on an Ayuda – Help Desk page, where should they be redirected to?  Enter the FULL url starting with http or https.', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'New Ticket Redirect', 'ayuda-help-desk' ),
					'id'      => 'new_ticket_redirect_fe',
					'type'    => 'text',
					'desc' 	  => __( 'After the user enters a new ticket they are usually taken to the newly entered ticket.  But, if you would like to redirect them someplace else, enter that location here. Enter the FULL url starting with http or https.', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'New Ticket Form Redirect', 'ayuda-help-desk' ),
					'id'      => 'new_ticket_form_redirect_fe',
					'type'    => 'text',
					'desc' 	  => __( 'If you would like to use a custom form for your new ticket form but still use our login screen then enter the full URL to the custom form. An example where this would be useful would be if you are using a Gravity Form in conjunction with our Gravity Form bridge. Enter the FULL url starting with http or https. Note that if you use this option you will never be able to see or use our standard ticket form! ', 'ayuda-help-desk' ),
				),

				array(
					'name' => __( 'Toolbars', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc'    => __( 'Control whether certain toolbars are visible to agents', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Show Ticket Details Toolbar', 'ayuda-help-desk' ),
					'id'      => 'ticket_detail_show_toolbar',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Show the toolbar on the ticket detail screen when an agent is viewing the ticket?', 'ayuda-help-desk' ),
				),

				array(
					'name' => __( 'Plugin Pages', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __( 'Configure pages where tickets will be displayed - we take special actions when these pages are viewed by the user', 'ayuda-help-desk' ),
				),
				array(
					'name'     => __( 'Ticket Submission', 'ayuda-help-desk' ),
					'id'       => 'ticket_submit',
					'type'     => 'select',
					'multiple' => true,
					'desc'     => sprintf( $desc, '<code>[ticket-submit]</code>' ),
					'options'  => mumei_ayuda_list_pages(),
					'default'  => ''
				),
				array(
					'name'     => __( 'Tickets List', 'ayuda-help-desk' ),
					'id'       => 'ticket_list',
					'type'     => 'select',
					'multiple' => false,
					'desc'     => sprintf( $desc1, '<code>[tickets]</code>' ),
					'options'  => mumei_ayuda_list_pages(),
					'default'  => ''
				),

				array(
					'name' => __( 'Misc', 'ayuda-help-desk' ),
					'type' => 'heading',
				),

				array(
					'name' => __( 'Credit', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Show Credit', 'ayuda-help-desk' ),
					'id'      => 'credit_link',
					'type'    => 'checkbox',
					'desc'    => __( 'Do you like this plugin? Please help us spread the word by displaying a credit link at the bottom of your ticket submission page.', 'ayuda-help-desk' ),
					'default' => false
				),
				array(
					'name'    => __( 'Admin Rating Request', 'ayuda-help-desk' ),
					'id'      => 'remove_admin_ratings_request',
					'type'    => 'checkbox',
					'desc'    => __( 'Remove the rating request footer in the admin screen.', 'ayuda-help-desk' ),
					'default' => false
				),
			)
		),
	);

	return array_merge( $def, apply_filters('mumei_ayuda_settings_general', $settings )  );

}
