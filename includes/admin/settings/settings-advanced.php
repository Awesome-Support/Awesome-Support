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
					'name' => __( 'Importer Integration', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => sprintf( __( 'If you use the <a href="%s" target="_blank">Awesome Support SAAS Importer</a> to import data from Zendesk, Helpscout or Ticksy, there is a reference field that contains the original ticket ID.  The options below control what to do with that field. ' , 'awesome-support' ), esc_url( 'https://getawesomesupport.com/addons/awesome-support-importer/' ) ),					
				),
				array(
					'name'    => __( 'Enable the Original Ticket ID Field?', 'awesome-support' ),
					'id'      => 'importer_id_enable',
					'type'    => 'checkbox',
					'desc'    => __( 'Show this field in the admin screen?', 'awesome-support' ),
					'default' => false,
				),
				array(
					'name'    => __( 'Show Original Ticket ID In Column List?', 'awesome-support' ),
					'id'      => 'importer_id_show_in_tkt_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Would you like to show the Original Ticket ID field in the ticket listing?', 'awesome-support' ),
					'default' => false,
				),
				array(
					'name'    => __( 'Label', 'awesome-support' ),
					'id'      => 'importer_id_label',
					'type'    => 'text',
					'desc'    => __( 'What should the field be named on the screen?', 'awesome-support' ),
					'default' => 'Help Desk SaaS Ticket ID',
				),				
				
				
				array(
					'name' => __( 'Attachment Overrides', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __('Modifications to this section has major security implications so be careful!','awesome-support' ),
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
					'name' => __( 'Compatibility', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __('Settings in this section might help with compatibility with certain themes and plugins - please experiment to see if one of these settings work for you before contacting our support center. NOTE: Changing ANYTHING in this section will require ALL CACHES to be flushed including CDNs, BROWSER CACHES.  Also, its possible that you would need a restart of the PHP service or your WEB SERVER in order to become fully activated!', 'awesome-support' ),
				),
				
				array(
					'name'    => __( 'Select2 JS File', 'awesome-support' ),
					'id'      => 'select2_js_file',
					'type'    => 'radio',
					'desc'    => __( 'Which select2 file should be loaded? Minimized version of files will load faster but cannot be used for debugging.', 'awesome-support' ),					
					'options' => array( 'full' => __( 'Full', 'awesome-support' ), 'full-min' => __( 'Full - Minimized', 'awesome-support' ), 'partial' => __( 'Partial', 'awesome-support' ), 'partial-min' => __( 'Partial Minimized', 'awesome-support' )   ),
					'default' => 'partial-min'
				),
				
				array(
					'name'    => __( 'Select2 CSS File', 'awesome-support' ),
					'id'      => 'select2_css_file',
					'type'    => 'radio',
					'desc'    => __( 'Which select2 css file should be loaded? Minimized version of files will load faster but cannot be used for debugging.', 'awesome-support' ),					
					'options' => array( 'min' => __( 'Minimized', 'awesome-support' ), 'full' => __( 'Full', 'awesome-support' )  ),
					'default' => 'min'
				),
				
				array(
					'name' => __( 'Bootstrap Support (Experimental)', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __('Options for loading bootstrap files. While you can enable this now please be aware that not all elements in the plugin renders properly.', 'awesome-support' ),
				),
				array(
					'name'    => __( 'Load BootStrap Files on Front-end?', 'awesome-support' ),
					'id'      => 'load_bs4_files_fe',
					'type'    => 'radio',					
					'options' => array( '0' => __( 'Do not load bootstrap files', 'awesome-support' ), '1' => __( 'Load bootstrap 4 files located on maxcdn', 'awesome-support'), '2' => __( 'Load bootstrap 3 files located on maxcdn', 'awesome-support' )  ),
					'default' => '0'
				),
				array(
					'name'    => __( 'Load BootStrap Files on Back-end?', 'awesome-support' ),
					'id'      => 'load_bs4_files_be',
					'type'    => 'radio',					
					'options' => array( '0' => __( 'Do not load bootstrap files', 'awesome-support' ), '1' => __( 'Load bootstrap 4 files located on maxcdn', 'awesome-support'), '2' => __( 'Load bootstrap 3 files located on maxcdn', 'awesome-support' )  ),
					'default' => '0'
				),
				
				array(
					'name'    => __( 'Bootstrap 4 Theme', 'awesome-support' ),
					'id'      => 'bs4_theme',
					'type'    => 'select',
					'options' => array( 'default' => __( 'Default', 'awesome-support' ),
										'awesome'=> __( 'Future Awesome Support BS4 Theme', 'awesome-support' ), 
										'cerulean' => __( 'Cerulean', 'awesome-support' ), 
										'cosmo' => __( 'Cosmo', 'awesome-support' ),
										'cyborg' => __( 'Cyborg', 'awesome-support' ),
										'darkly' => __( 'Darkly', 'awesome-support' ),										
										'flatly' => __( 'Flatly', 'awesome-support'), 
										'journal' => __( 'Journal', 'awesome-support'), 										
										'litera' => __( 'Litera', 'awesome-support'), 
										'lumen' => __( 'Lumen', 'awesome-support'), 
										'lux' => __( 'Lux', 'awesome-support'), 
										'materia' => __( 'Materia', 'awesome-support'), 
										'minty' => __( 'Minty', 'awesome-support'), 
										'pulse' => __( 'Pulse', 'awesome-support' ),
										'sandstone' => __( 'Sandstone', 'awesome-support' ), 
										'simplex' => __( 'Simplex', 'awesome-support' ),
										'slate' => __( 'Slate', 'awesome-support' ),
										'solar' => __( 'Solar', 'awesome-support' ), 
										'spacelab' => __( 'spacelab', 'awesome-support' ), 
										'united' => __( 'United', 'awesome-support' ), 
										'yeti' => __( 'Yeti', 'awesome-support' ), 
										'custom' => __( 'Custom theme', 'awesome-support' ), 
										),
					'default' => 'default'
				),				
				
				array(
					'name' => __( 'Sessions and Cookie Management', 'awesome-support' ),
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
					'name'    => __( 'Expired Sessions Cleanup Batch Size', 'awesome-support' ),
					'id'      => 'session_delete_batch_size',
					'type'    => 'number',
					'default' => 1000,
					'min'	  => 1000,
					'max'	  => 100000,
				),				
				
				array(
					'name' => __( 'Log Files', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Awesome Support creates log files for certain events.  These are different from WordPress, PHP and your webserver log files.  Please tell us where you would like these files to be placed.', 'awesome-support' ),
				),

				array(
					'name'    => __( 'Where Should Log Files Be Stored?', 'awesome-support' ),
					'id'      => 'log_file_location',
					'type'    => 'radio',					
					'options' => array( '0' => __( 'Default', 'awesome-support' ), '1' => __( 'WordPress Uploads Folder', 'awesome-support'), '2' => __( 'In The Absolute Path Specified Below', 'awesome-support')  ),
					'default' => '0'
				),
				array(
					'name'    => __( 'None Of The Above - Place Log Files Here ', 'awesome-support' ),
					'id'      => 'log_file_location_absolute',
					'type'    => 'text',
					'desc'    => __( 'The absolute path to the log file location.  This must be relative to the server user account and must NOT end in a forward slash!', 'awesome-support' )
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