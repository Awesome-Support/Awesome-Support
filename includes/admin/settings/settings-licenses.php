<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_licenses', 99, 1 );
/**
 * Add plugin core settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_core_settings_licenses( $def ) {

	/* If current user cannot view licenses then just return. */
	if ( ! current_user_can('manage_licenses_for_awesome_support') ) {
		return $def;
	}	

	$licenses = apply_filters( 'wpas_addons_licenses', array() );

	if ( empty( $licenses ) ) {
		return $def;
	}


	
	$settings = array(
		'licenses' => array(
			'name'    => __( 'Licenses', 'awesome-support' ),
			'options' => $licenses
		),
	);

	return array_merge( $def, $settings );

}