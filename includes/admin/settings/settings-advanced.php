<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_advanced', 95, 1 );
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
					'desc'    => sprintf( __( 'Only use this option if you know how to create your own registration page, otherwise you might create an infinite redirect. If you need help on creating a registration page you should <a href="%s" target="_blank">start by reading this guide</a>.', 'wpas' ), esc_url( 'http://codex.wordpress.org/Customizing_the_Registration_Form' ) ),
					'default' => '',
					'options' => wpas_list_pages()
				),
				array(
					'name'    => __( 'Admins See All', 'wpas' ),
					'id'      => 'admin_see_all',
					'type'    => 'checkbox',
					'desc'    => __( 'Administrators can see all tickets in the tickets list. If unchecked admins will only see tickets assigned to them.', 'wpas' ),
					'default' => true
				),
				array(
					'name'    => __( 'Agent See All', 'wpas' ),
					'id'      => 'agent_see_all',
					'type'    => 'checkbox',
					'desc'    => __( 'Agents can see all tickets in the tickets list. If unchecked agents will only see tickets assigned to them.', 'wpas' ),
					'default' => false
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