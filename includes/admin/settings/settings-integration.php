<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_integration', 95, 1 );
/**
 * Add plugin integration settings
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_core_settings_integration( $def ) {

	$settings = array(
		'integration' => array(
			'name'    => __( 'Integrations', 'awesome-support' ),
			'options' => array(
			
				array(
					'name'    => __( 'WooCommerce', 'awesome-support' ),
					'id'      => 'simple_wc',
					'type'    => 'heading',
					'desc'    => sprintf( __( 'Add the MY TICKETS and OPEN A TICKET links to the MY ACCOUNT page in WooCommerce. For more advanced integration features checkout our %s', 'awesome-support' ), '<a href="https://getawesomesupport.com/addons/woocommerce/" target="_blank">' . 'WooCommerce Addon' . '</a>' ),
				),
				array(
					'name'    => __( 'Enable WooCommerce Integration', 'awesome-support' ),
					'id'      => 'enable_simple_wc',
					'type'    => 'checkbox',
					'desc'    => __( 'Add the Awesome Support MY TICKETS and OPEN A TICKET page links on the WooCommerce MY ACCOUNT page', 'awesome-support' ),					
					'default' => false,
				),
				array(
					'name'    => __( 'Permalinks Warning:', 'awesome-support' ),
					'type'	  => 'note',
					'desc'    => __( 'Note: You must go to your Permalinks page and save the settings there again if you turn on or off the WooCommerce option above.', 'awesome-support' ),
				),
				array(
					'name'    => __( 'Label for MY TICKETS link', 'awesome-support' ),
					'id'      => 'simple_wc_my_tickets_label',
					'type'    => 'text',
					'desc'    => __( 'If left blank, the label will be "My tickets". Leave blank if you are creating a multi-language website', 'awesome-support' ),					
				),
				array(
					'name'    => __( 'Label for SUBMIT TICKET link', 'awesome-support' ),
					'id'      => 'simple_wc_submit_ticket_label',
					'type'    => 'text',
					'desc'    => __( 'If left blank, the label will be "Open a support ticket". Leave blank if you are creating a multi-language website', 'awesome-support' ),					
				),

				array(
					'name'    => __( 'myCRED', 'awesome-support' ),
					'id'      => 'my_cred',
					'type'    => 'heading',
					'desc'    => sprintf( __( 'Integration options with MY CRED.  You must have the MY CRED plugin installed and activated before enabling anythign in this section. Get myCRED from here: %s', 'awesome-support' ), '<a href="https://wordpress.org/plugins/mycred/" target="_blank">' . 'myCRED' . '</a>' ),
				),
				array(
					'name'    => __( 'Enable myCRED Integration', 'awesome-support' ),
					'id'      => 'enable_my_cred',
					'type'    => 'checkbox',
					'desc'    => __( 'Send ticket data to myCRED so agents and users can earn badges, ranks and other points.', 'awesome-support' ),					
					'default' => false,
				),
				array(
					'name'    => __( 'myCRED Agent Point Type', 'awesome-support' ),
					'id'      => 'myCRED_agent_point_type',
					'type'    => 'text',
					'desc'    => __( 'What is the myCRED Meta Key that should be used to record points for agents?  The default is <em>mycred_default</em>.  See the myCRED settings screen and documentation for more information.', 'awesome-support' ),					
					'default' => 'mycred_default',
				),
				array(
					'name'    => __( 'myCRED Agent Points: Submit Ticket', 'awesome-support' ),
					'id'      => 'myCRED_agent_points_ticket_submit',
					'type'    => 'number',
					'desc'    => __( 'How many points should an agent get when they submit or open a ticket?', 'awesome-support' ),					
					'default' => 1,
				),				
				array(
					'name'    => __( 'myCRED Agent Points: Closing Tickets', 'awesome-support' ),
					'id'      => 'myCRED_agent_points_ticket_close',
					'type'    => 'number',
					'desc'    => __( 'How many points should an agent get when they close a ticket?', 'awesome-support' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'myCRED Agent Points: Replies', 'awesome-support' ),
					'id'      => 'myCRED_agent_points_ticket_reply',
					'type'    => 'number',
					'desc'    => __( 'How many points should an agent get when they add a reply to a ticket?', 'awesome-support' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'myCRED User Point Type', 'awesome-support' ),
					'id'      => 'myCRED_user_point_type',
					'type'    => 'text',
					'desc'    => __( 'What is the myCRED Meta Key that should be used to record points for users?  The default is <em>mycred_default</em>. See the myCRED settings screen and documentation for more information.', 'awesome-support' ),					
					'default' => 'mycred_default',
				),				
				array(
					'name'    => __( 'myCRED User Points: Submit Ticket', 'awesome-support' ),
					'id'      => 'myCRED_user_points_ticket_submit',
					'type'    => 'number',
					'desc'    => __( 'How many points should a user get when they submit or open a ticket?', 'awesome-support' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'myCRED User Points: Replies', 'awesome-support' ),
					'id'      => 'myCRED_user_points_ticket_reply',
					'type'    => 'number',
					'desc'    => __( 'How many points should a user get when they reply to a ticket?', 'awesome-support' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'myCRED User Points: Closing Tickets', 'awesome-support' ),
					'id'      => 'myCRED_user_points_ticket_close',
					'type'    => 'number',
					'desc'    => __( 'How many points should a user get when they close a ticket?', 'awesome-support' ),					
					'default' => 1,
				),
				array(
					'name'    => __( 'Agent Gets Credit For User Closing Ticket', 'awesome-support' ),
					'id'      => 'myCRED_agent_gets_points_user_close',
					'type'    => 'checkbox',
					'desc'    => __( 'If the user closes a ticket should the agent get points for it anyway?', 'awesome-support' ),					
					'default' => false,
					),
				
				array(
					'name'    => __( 'Teamviewer', 'awesome-support' ),
					'id'      => 'simple_tv',
					'type'    => 'heading',
				),				
				array(
					'name'    => __( 'Enable Teamviewer Chat', 'awesome-support' ),
					'id'      => 'enable_teamviewer_chat',
					'type'    => 'checkbox',
					'desc'    => __( 'If your team is licensed to use teamviewer in a multi-user environment you can use teamviewer chat right inside the Awesome Support ticket screens!', 'awesome-support' ),
					'default' => false
				),
			)
		),
	);

	return array_merge( $def, apply_filters('wpas_settings_integration', $settings )  );

}