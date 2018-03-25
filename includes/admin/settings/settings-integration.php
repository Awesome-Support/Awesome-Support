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
					'name'    => __( 'Teamviewer', 'awesome-support' ),
					'id'      => 'simple_tv',
					'type'    => 'heading',
				),				
				array(
					'name'    => __( 'Enable Teamviewer Chat', 'awesome-support' ),
					'id'      => 'enable_teamviewer_chat',
					'type'    => 'checkbox',
					'desc'    => __( 'If your team is licensed to user teamviewer in a multi-user environment you can use teamviewer chat right inside the Awesome Support ticket screens!', 'awesome-support' ),
					'default' => false
				),
			)
		),
	);

	return array_merge( $def, $settings );

}