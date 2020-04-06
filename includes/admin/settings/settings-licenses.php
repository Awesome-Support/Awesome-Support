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

	$gdpr_notice[0] = 	array(
				'name' => __( 'Data Sharing Disclosure', 'awesome-support' ),
				'type' => 'Note',
				'desc' => __( 'Adding one or more license keys will send information about your domain, system and license status to our servers. By adding a license key you CONSENT to transmitting this information to us in order for us to provide the automatic updates and other services that you are entitled to under your license.  To remove consent, simply remove your license key.  If you choose not to add your license keys you can update your software manually by downloading the latest files from your account dashboard on our website.', 'awesome-support' ),
			);
			
	$licenses = array_merge( $gdpr_notice, $licenses );
	
	$settings = array(
		'licenses' => array(
			'name'    => __( 'Licenses', 'awesome-support' ),
			'options' => $licenses,
			

		),
	);

	return array_merge( $def, $settings );

}