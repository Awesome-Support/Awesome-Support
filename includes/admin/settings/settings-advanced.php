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
			'name'    => __( 'Advanced', 'awesome-support' ),
			'options' => array(
				array(
					'name'    => __( 'Custom Login / Registration Page', 'awesome-support' ),
					'id'      => 'login_page',
					'type'    => 'select',
					'desc'    => sprintf( __( 'Only use this option if you know how to create your own registration page, otherwise you might create an infinite redirect. If you need help on creating a registration page you should <a href="%s" target="_blank">start by reading this guide</a>.', 'awesome-support' ), esc_url( 'http://codex.wordpress.org/Customizing_the_Registration_Form' ) ),
					'default' => '',
					'options' => wpas_list_pages()
				),
				array(
					'name'    => __( 'Admins See All', 'awesome-support' ),
					'id'      => 'admin_see_all',
					'type'    => 'checkbox',
					'desc'    => __( 'Administrators can see all tickets in the tickets list. If unchecked admins will only see tickets assigned to them.', 'awesome-support' ),
					'default' => true
				),
				array(
					'name'    => __( 'Agent See All', 'awesome-support' ),
					'id'      => 'agent_see_all',
					'type'    => 'checkbox',
					'desc'    => __( 'Agents can see all tickets in the tickets list. If unchecked agents will only see tickets assigned to them.', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'Ticket Topic Slug', 'awesome-support' ),
					'id'      => 'ticket_topic_slug',
					'type'    => 'radio',
					'desc'    => __( 'What to use for the indivdual ticket slug.  The default is the ticket topic transformed into a slug.', 'awesome-support' ),					
					'options' => array( 'default' => __( 'Default', 'awesome-support' ), 'ticketid' => __( 'Ticket ID', 'awesome-support' ), 'randomnumber' => __( 'Random Number', 'awesome-support' ), 'guid' => __( 'GUID', 'awesome-support' )   ),
					'default' => 'ASC'
				),
				
				array(
					'name' => __( 'Attachment Overrides', 'awesome-support' ),
					'type' => 'heading',
					'desc' => 'Modifications to this section has major security implications so be careful!',
				),
				array(
					'name'    => __( 'Do Not Mask Attachment Links', 'awesome-support' ),
					'id'      => 'unmask_attachment_links',
					'type'    => 'checkbox',
					'desc'    => __( 'There are some server configurations that do not work with our masked links. Try checking this box to make them work. This only works on NEW tickets.  Old tickets retain their prior links!', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( '.htaccess Contents', 'awesome-support' ),
					'id'      => 'htaccess_contents_for_attachment_folders',
					'type'    => 'textarea',
					'desc'    => __( 'The contents of your ticket uploads folder can be protected by an htaccess file on apache servers. <br />If this is left empty then the value of <b>options -Indexes</b> is automatically added to the file to prevent others from browsing the directory. <br />Add to this only if you are a super-duper apache server expert! <br /> No technical support is available for modifications to this setting!', 'awesome-support' )
				),				
				
				array(
					'name' => __( 'Cookie Management', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Secure Cookies', 'awesome-support' ),
					'id'      => 'secure_cookies',
					'type'    => 'checkbox',
					'default' => false
				),
				array(
					'name'    => __( 'HTTP Only', 'awesome-support' ),
					'id'      => 'cookie_http_only',
					'type'    => 'checkbox',
					'default' => false
				),
				
				array(
					'name' => __( 'Danger Zone', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Delete Data', 'awesome-support' ),
					'id'      => 'delete_data',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Delete ALL plugin data on uninstall? This cannot be undone.', 'awesome-support' )
				),
			)
		),
	);

	return array_merge( $def, $settings );

}