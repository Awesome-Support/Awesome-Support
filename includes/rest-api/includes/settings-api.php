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
			
		),
	);

	return array_merge ( $def, $settings );

}