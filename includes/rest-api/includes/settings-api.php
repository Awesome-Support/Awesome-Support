<?php
add_filter( 'wpas_plugin_settings', 'wpas_rest_api_settings', 10, 1 );

/**
 * Add plugin file upload settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_rest_api_settings( $def ) {

	$settings  = array();

	$settings['rest-api'] = array(
		'name'    => __( 'REST API', 'awesome-support' ),
		'options' => array(
			array(
				'name'    => __( 'REST API', 'awesome-support' ),
				'id'      => 'as_rest_api',
				'type'    => 'heading',
				'desc'    => __( 'Some premium add-ons require that this API be enabled.  This includes REMOTE TICKETS, CLIENT TICKETS and others. The documentation for each add-on will let you know if you need to enable this option.', 'awesome-support' ),
			),					
			array(
				'name'    => __( 'Enable REST API', 'awesome-support' ),
				'id'      => 'enable_rest_api',
				'type'    => 'checkbox',
				'default' => false,
				'desc'    => __( 'Enable the Awesome Support REST API', 'awesome-support' ),
			),
			array(
				'name'    => __( 'Support Notice', 'awesome-support' ),
				'id'      => 'rest_api_support_notice',
				'type'    => 'note',
				'desc'    => __( 'Free support is not provided for custom programs that use the REST API. To obtain support for customizations using the REST API please open a special-projects contract with us!', 'awesome-support' ),
			),
			array(
				'name'    => __( 'Documentation', 'awesome-support' ),
				'id'      => 'rest_api_doc',
				'type'    => 'note',
				'desc'    => sprintf( __( 'You can find documentation for this api in our developer portal located here: %s', 'awesome-support' ), '<a href="https://developer.getawesomesupport.com" target="_blank">' . 'Developer Portal' . '</a>' ),
			),
			
			array(
				'name'    => __( 'Add-ons Using The REST API', 'awesome-support' ),
				'id'      => 'add_ons_using_rest_api',
				'type'    => 'heading',
				'desc'    => __( 'The premium-addons for Awesome Support listed below all use this REST API. Developers can use them as examples of how the REST API can be used to create production level applications', 'awesome-support' ),
			),
			array(
				'name'    => __( 'Remote Tickets', 'awesome-support' ),
				'id'      => 'rest_api_remote_tickets',
				'type'    => 'note',
				'desc'    => sprintf( __( '%s: This add-on uses the rest api to create tickets, send attachments, login users and pull configuration from a special remote tickets configuration post-type', 'awesome-support' ), '<a href="https://getawesomesupport.com/addons/remote-tickets/" target="_blank">' . 'Remote Tickets' . '</a>' ),
			),
			array(
				'name'    => __( 'Client Tickets', 'awesome-support' ),
				'id'      => 'rest_api_client_tickets',
				'type'    => 'note',
				'desc'    => sprintf( __( '%s: This add-on uses the rest api to create tickets, list tickets, send attachments, login users, update custom fields and pull configuration from a special configuration post-type', 'awesome-support' ), '<a href="https://getawesomesupport.com/addons/client-tickets-for-web-agencies-and-developers/" target="_blank">' . 'Client Tickets For Developers And Web Agencies' . '</a>' ),
			),			
			
		),
	);

	return array_merge ( $def, $settings );

}