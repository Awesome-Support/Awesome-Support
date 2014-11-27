<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_advanced', 5, 1 );
/**
 * Add plugin advanced settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_core_settings_advanced( $def ) {

	$settings = array(
		'advanced' => array(
			'name'    => __( 'Advanced', 'wpas' ),
			'options' => array(
				array(
					'name'    => __( 'Custom Login / Registration Page', 'wpas' ),
					'id'      => 'login_page',
					'type'    => 'select',
					'desc'    => __( 'Only use this option if you know how to create your own registration page, otherwise you might create an infinite redirect.', 'wpas' ),
					'default' => '',
					'options' => wpas_list_pages()
				),
				array(
					'name' => __( 'Danger Zone', 'wpas' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Delete Data', 'wpas' ),
					'id'      => 'delete_data',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Delete ALL plugin data on uninstall? This cannot be undone.', 'wpas' )
				),
			)
		),
	);

	return array_merge( $def, $settings );

}