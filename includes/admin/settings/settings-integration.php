<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_settings_integration', 95, 1 );
/**
 * Add plugin integration settings
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function mumei_ayuda_core_settings_integration( $def ) {

	// translators: %s is the link to the addons.
	$desc = __( 'Add the MY TICKETS and OPEN A TICKET links to the MY ACCOUNT page in WooCommerce. For more advanced integration features checkout our %s', 'ayuda-help-desk' );

	// translators: %s is the link to the addons.
	$desc1 = __( 'Integration options with MY CRED.  You must have the MY CRED plugin installed and activated before enabling anythign in this section. Get myCRED from here: %s', 'ayuda-help-desk' );
	$settings = array(
		'integration' => array(
			'name'    => __( 'Integrations', 'ayuda-help-desk' ),
			'options' => array(
			
				array(
					'name'    => __( 'WooCommerce', 'ayuda-help-desk' ),
					'id'      => 'simple_wc',
					'type'    => 'heading',
					'desc'    => sprintf( $desc, '<a href="https://getawesomesupport.com/addons/woocommerce/" target="_blank">' . 'WooCommerce Addon' . '</a>' ),
				),
				array(
					'name'    => __( 'Enable WooCommerce Integration', 'ayuda-help-desk' ),
					'id'      => 'enable_simple_wc',
					'type'    => 'checkbox',
					'desc'    => __( 'Add the Ayuda – Help Desk MY TICKETS and OPEN A TICKET page links on the WooCommerce MY ACCOUNT page', 'ayuda-help-desk' ),					
					'default' => false,
				),
				array(
					'name'    => __( 'Permalinks Warning:', 'ayuda-help-desk' ),
					'type'	  => 'note',
					'desc'    => __( 'Note: You must go to your Permalinks page and save the settings there again if you turn on or off the WooCommerce option above.', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Label for MY TICKETS link', 'ayuda-help-desk' ),
					'id'      => 'simple_wc_my_tickets_label',
					'type'    => 'text',
					'desc'    => __( 'If left blank, the label will be "My tickets". Leave blank if you are creating a multi-language website', 'ayuda-help-desk' ),					
				),
				array(
					'name'    => __( 'Label for SUBMIT TICKET link', 'ayuda-help-desk' ),
					'id'      => 'simple_wc_submit_ticket_label',
					'type'    => 'text',
					'desc'    => __( 'If left blank, the label will be "Open a support ticket". Leave blank if you are creating a multi-language website', 'ayuda-help-desk' ),					
				),

				array(
					'name'    => __( 'myCRED', 'ayuda-help-desk' ),
					'id'      => 'my_cred',
					'type'    => 'heading',
					'desc'    => sprintf( $desc1, '<a href="https://wordpress.org/plugins/mycred/" target="_blank">' . 'myCRED' . '</a>' ),
				),
				array(
					'name'    => __( 'Enable myCRED Integration', 'ayuda-help-desk' ),
					'id'      => 'enable_my_cred',
					'type'    => 'checkbox',
					'desc'    => __( 'Send ticket data to myCRED so agents and users can earn badges, ranks and other points.', 'ayuda-help-desk' ),					
					'default' => false,
				),
				array(
					'name'    => __( 'myCRED Agent Point Type', 'ayuda-help-desk' ),
					'id'      => 'myCRED_agent_point_type',
					'type'    => 'text',
					'desc'    => __( 'What is the myCRED Meta Key that should be used to record points for agents?  The default is <em>mycred_default</em>.  See the myCRED settings screen and documentation for more information.', 'ayuda-help-desk' ),					
					'default' => 'mycred_default',
				),
				array(
					'name'    => __( 'myCRED Agent Points: Submit Ticket', 'ayuda-help-desk' ),
					'id'      => 'myCRED_agent_points_ticket_submit',
					'type'    => 'number',
					'desc'    => __( 'How many points should an agent get when they submit or open a ticket?', 'ayuda-help-desk' ),					
					'default' => 1,
				),				
				array(
					'name'    => __( 'myCRED Agent Points: Closing Tickets', 'ayuda-help-desk' ),
					'id'      => 'myCRED_agent_points_ticket_close',
					'type'    => 'number',
					'desc'    => __( 'How many points should an agent get when they close a ticket?', 'ayuda-help-desk' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'myCRED Agent Points: Replies', 'ayuda-help-desk' ),
					'id'      => 'myCRED_agent_points_ticket_reply',
					'type'    => 'number',
					'desc'    => __( 'How many points should an agent get when they add a reply to a ticket?', 'ayuda-help-desk' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'myCRED User Point Type', 'ayuda-help-desk' ),
					'id'      => 'myCRED_user_point_type',
					'type'    => 'text',
					'desc'    => __( 'What is the myCRED Meta Key that should be used to record points for users?  The default is <em>mycred_default</em>. See the myCRED settings screen and documentation for more information.', 'ayuda-help-desk' ),					
					'default' => 'mycred_default',
				),				
				array(
					'name'    => __( 'myCRED User Points: Submit Ticket', 'ayuda-help-desk' ),
					'id'      => 'myCRED_user_points_ticket_submit',
					'type'    => 'number',
					'desc'    => __( 'How many points should a user get when they submit or open a ticket?', 'ayuda-help-desk' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'myCRED User Points: Replies', 'ayuda-help-desk' ),
					'id'      => 'myCRED_user_points_ticket_reply',
					'type'    => 'number',
					'desc'    => __( 'How many points should a user get when they reply to a ticket?', 'ayuda-help-desk' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'myCRED User Points: Closing Tickets', 'ayuda-help-desk' ),
					'id'      => 'myCRED_user_points_ticket_close',
					'type'    => 'number',
					'desc'    => __( 'How many points should a user get when they close a ticket?', 'ayuda-help-desk' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'Agent Gets Credit For User Closing Ticket', 'ayuda-help-desk' ),
					'id'      => 'myCRED_agent_gets_points_user_close',
					'type'    => 'checkbox',
					'desc'    => __( 'If the user closes a ticket should the agent get points for it anyway?', 'ayuda-help-desk' ),					
					'default' => false,
					),
				
				array(
					'name'    => __( 'Teamviewer', 'ayuda-help-desk' ),
					'id'      => 'simple_tv',
					'type'    => 'heading',
				),				
				array(
					'name'    => __( 'Enable Teamviewer Chat', 'ayuda-help-desk' ),
					'id'      => 'enable_teamviewer_chat',
					'type'    => 'checkbox',
					'desc'    => __( 'If your team is licensed to use teamviewer in a multi-user environment you can use teamviewer chat right inside the Ayuda – Help Desk ticket screens!', 'ayuda-help-desk' ),
					'default' => false
				),
			)
		),
	);

	return array_merge( $def, apply_filters('mumei_ayuda_settings_integration', $settings )  );

}