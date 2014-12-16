<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_general', 5, 1 );
/**
 * Add plugin core settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_core_settings_general( $def ) {

	$user_registration = boolval( get_option( 'users_can_register' ) );
	$registration_lbl  = ( true === $user_registration ) ? _x( 'allowed', 'User registration is allowed', 'wpas' ) : _x( 'not allowed', 'User registration is not allowed', 'wpas' );

	$settings = array(
		'general' => array(
			'name'    => __( 'General', 'wpas' ),
			'options' => array(
				array(
					'name'    => __( 'Multiple Products', 'wpas' ),
					'id'      => 'support_products',
					'type'    => 'checkbox',
					'desc'    => __( 'If you need to provide support for multiple products, please enable this option. You will then be able to add your products.', 'wpas' ),
					'default' => false
				),
				array(
					'name'    => __( 'Default Assignee', 'wpas' ),
					'id'      => 'assignee_default',
					'type'    => 'select',
					'desc'    => __( 'Who to assign tickets to by default (if auto-assignment is enabled, this will only be used in case an assignment rule is incorrect).', 'wpas' ),
					'options' => wpas_list_users( 'edit_ticket' ),
					'default' => ''
				),
				array(
					'name'    => __( 'Allow Registrations', 'wpas' ),
					'id'      => 'allow_registrations',
					'type'    => 'checkbox',
					'desc'    => sprintf( __( 'Allow users to register on the support. This setting can be enabled even though the WordPress setting is disabled. Currently, registrations are %s by WordPress.', 'wpas' ),  "<strong>$registration_lbl</strong>" ),
					'default' => true
				),
				array(
					'name'    => __( 'Replies Order', 'wpas' ),
					'id'      => 'replies_order',
					'type'    => 'radio',
					'desc'    => __( 'In which order should the replie be displayed (for both client and admin side)?', 'wpas' ),
					'options' => array( 'ASC' => __( 'Old to New', 'wpas' ), 'DESC' => __( 'New to Old', 'wpas' ) ),
					'default' => 'ASC'
				),
				array(
					'name'    => __( 'Hide Closed', 'wpas' ),
					'id'      => 'hide_closed',
					'type'    => 'checkbox',
					'desc'    => __( 'Only show open tickets when clicking the "All Tickets" link.', 'wpas' ),
					'default' => true
				),
				array(
					'name'    => __( 'Show Count', 'wpas' ),
					'id'      => 'show_count',
					'type'    => 'checkbox',
					'desc'    => __( 'Display the number of open tickets in the admin menu.', 'wpas' ),
					'default' => true
				),
				array(
					'name'    => __( 'Old Tickets', 'wpas' ),
					'id'      => 'old_ticket',
					'type'    => 'text',
					'default' => 10,
					'desc'    => __( 'After how many days should a ticket be considered &laquo;old&raquo;?', 'wpas' )
				),
				array(
					'name' => __( 'Plugin Pages', 'wpas' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Ticket Submission', 'wpas' ),
					'id'      => 'ticket_submit',
					'type'    => 'select',
					'desc'    => __( 'The page used for ticket submission.', 'wpas' ),
					'options' => wpas_list_pages(),
					'default' => ''
				),
				array(
					'name'    => __( 'Tickets List', 'wpas' ),
					'id'      => 'ticket_list',
					'type'    => 'select',
					'desc'    => __( 'The page that will list all tickets for a client.', 'wpas' ),
					'options' => wpas_list_pages(),
					'default' => ''
				),
				array(
					'name' => __( 'Terms & Conditions', 'wpas' ),
					'type' => 'heading',
				),
				array(
					'name'     => __( 'Content', 'wpas' ),
					'id'       => 'terms_conditions',
					'type'     => 'editor',
					'default'  => '',
					'desc'     => __( 'Terms & conditions are not mandatory. If you add terms, a mendatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept your terms', 'wpas' ),
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
			)
		),
	);

	return array_merge( $def, $settings );

}