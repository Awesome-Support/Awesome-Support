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