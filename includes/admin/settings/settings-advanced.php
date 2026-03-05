<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_settings_advanced', 95, 1 );
/**
 * Add plugin advanced settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function mumei_ayuda_core_settings_advanced( $def ) {

	// translators: %s is the link to the guide.
	$desc = __( 'Only use this option if you know how to create your own registration page, otherwise you might create an infinite redirect. If you need help on creating a registration page you should <a href="%s" target="_blank">start by reading this guide</a>.', 'ayuda-help-desk' );
	
	// translators: %s is the link to the Ayuda – Help Desk SAAS Importer.
	$desc1 = __( 'If you use the <a href="%s" target="_blank">Ayuda – Help Desk SAAS Importer</a> to import data from Zendesk, Helpscout or Ticksy, there is a reference field that contains the original ticket ID.  The options below control what to do with that field. ' , 'ayuda-help-desk' );

	$settings = array(
		'advanced' => array(
			'name'    => __( 'Advanced', 'ayuda-help-desk' ),
			'options' => array(
				array(
					'name'    => __( 'Custom Login / Registration Page', 'ayuda-help-desk' ),
					'id'      => 'login_page',
					'type'    => 'select',
					'desc'    => sprintf( $desc, esc_url( 'http://codex.wordpress.org/Customizing_the_Registration_Form' ) ),
					'default' => '',
					'options' => mumei_ayuda_list_pages()
				),
				array(
					'name'    => __( 'Admins See All', 'ayuda-help-desk' ),
					'id'      => 'admin_see_all',
					'type'    => 'checkbox',
					'desc'    => __( 'Administrators can see all tickets in the tickets list. If unchecked admins will only see tickets assigned to them.', 'ayuda-help-desk' ),
					'default' => true
				),
				array(
					'name'    => __( 'Agent See All', 'ayuda-help-desk' ),
					'id'      => 'agent_see_all',
					'type'    => 'checkbox',
					'desc'    => __( 'Agents can see all tickets in the tickets list. If unchecked agents will only see tickets assigned to them.', 'ayuda-help-desk' ),
					'default' => false
				),
				array(
					'name'    => __( 'Ticket Topic Slug', 'ayuda-help-desk' ),
					'id'      => 'ticket_topic_slug',
					'type'    => 'radio',
					'desc'    => __( 'What to use for the indivdual ticket slug.  The default is the ticket topic transformed into a slug.', 'ayuda-help-desk' ),					
					'options' => array( 'default' => __( 'Default', 'ayuda-help-desk' ), 'ticketid' => __( 'Ticket ID', 'ayuda-help-desk' ), 'randomnumber' => __( 'Random Number', 'ayuda-help-desk' ), 'guid' => __( 'GUID', 'ayuda-help-desk' )   ),
					'default' => 'ASC'
				),
				
				array(
					'name' => __( 'Importer Integration', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc'    => sprintf( $desc1, esc_url( 'https://getawesomesupport.com/addons/awesome-support-importer/' ) ),					
				),
				array(
					'name'    => __( 'Enable the Original Ticket ID Field?', 'ayuda-help-desk' ),
					'id'      => 'importer_id_enable',
					'type'    => 'checkbox',
					'desc'    => __( 'Show this field in the admin screen?', 'ayuda-help-desk' ),
					'default' => false,
				),
				array(
					'name'    => __( 'Show Original Ticket ID In Column List?', 'ayuda-help-desk' ),
					'id'      => 'importer_id_show_in_tkt_list',
					'type'    => 'checkbox',
					'desc'    => __( 'Would you like to show the Original Ticket ID field in the ticket listing?', 'ayuda-help-desk' ),
					'default' => false,
				),
				array(
					'name'    => __( 'Label', 'ayuda-help-desk' ),
					'id'      => 'importer_id_label',
					'type'    => 'text',
					'desc'    => __( 'What should the field be named on the screen?', 'ayuda-help-desk' ),
					'default' => 'Help Desk SaaS Ticket ID',
				),				
				
				
				array(
					'name' => __( 'Attachment Overrides', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __('Modifications to this section has major security implications so be careful!','ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Do Not Mask Attachment Links', 'ayuda-help-desk' ),
					'id'      => 'unmask_attachment_links',
					'type'    => 'checkbox',
					'desc'    => __( 'There are some server configurations that do not work with our masked links. Try checking this box to make them work. This only works on NEW tickets.  Old tickets retain their prior links!', 'ayuda-help-desk' ),
					'default' => false
				),
				array(
					'name'    => __( 'Render Method', 'ayuda-help-desk' ),
					'id'      => 'attachment_render_method',
					'type'    => 'radio',
					'desc'    => __( 'How should the attachment be rendered when attachments are using masked links? In-line will try to show attachments in the browser. Download will always download the attachment, regardless of what type it is.', 'ayuda-help-desk' ),
					'default' => 'inline',
					'options' => array(
						'inline'           => __( 'Inline', 'ayuda-help-desk' ),
						'attachment'       => __( 'Download', 'ayuda-help-desk' ),
					),
				),
				
				array(
					'name'    => __( '.htaccess Contents', 'ayuda-help-desk' ),
					'id'      => 'htaccess_contents_for_attachment_folders',
					'type'    => 'textarea',
					'desc'    => __( 'The contents of your ticket uploads folder can be protected by an htaccess file on apache servers. <br />If this is left empty then the value of <b>options -Indexes</b> is automatically added to the file to prevent others from browsing the directory. <br />Add to this only if you are a super-duper apache server expert! <br /> No technical support is available for modifications to this setting!', 'ayuda-help-desk' )
				),
				
				array(
					'name' => __( 'Compatibility', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __('Settings in this section might help with compatibility with certain themes and plugins - please experiment to see if one of these settings work for you before contacting our support center. NOTE: Changing ANYTHING in this section will require ALL CACHES to be flushed including CDNs, BROWSER CACHES.  Also, its possible that you would need a restart of the PHP service or your WEB SERVER in order to become fully activated!', 'ayuda-help-desk' ),
				),
				
				array(
					'name'    => __( 'Select2 Version', 'ayuda-help-desk' ),
					'id'      => 'select2_version',
					'type'    => 'radio',
					'desc'    => __( 'Which version of the select2 library should we load?', 'ayuda-help-desk' ),					
					'options' => array( 'select2-4-0-5' => __( '4.0.5', 'ayuda-help-desk' ), 'select2-4-0-4' => __( '4.0.4', 'ayuda-help-desk' ), 'select2-4-0-3' => __( '4.0.3', 'ayuda-help-desk' ), 'select2-4-0-2' => __( '4.0.2', 'ayuda-help-desk' )   ),
					'default' => 'select2-4-0-5'
				),				
				
				array(
					'name'    => __( 'Select2 JS File', 'ayuda-help-desk' ),
					'id'      => 'select2_js_file',
					'type'    => 'radio',
					'desc'    => __( 'Which select2 file should be loaded? Minimized version of files will load faster but cannot be used for debugging.', 'ayuda-help-desk' ),					
					'options' => array( 'full' => __( 'Full', 'ayuda-help-desk' ), 'full-min' => __( 'Full - Minimized', 'ayuda-help-desk' ), 'partial' => __( 'Partial', 'ayuda-help-desk' ), 'partial-min' => __( 'Partial Minimized', 'ayuda-help-desk' )   ),
					'default' => 'partial-min'
				),
				
				array(
					'name'    => __( 'Select2 CSS File', 'ayuda-help-desk' ),
					'id'      => 'select2_css_file',
					'type'    => 'radio',
					'desc'    => __( 'Which select2 css file should be loaded? Minimized version of files will load faster but cannot be used for debugging.', 'ayuda-help-desk' ),					
					'options' => array( 'min' => __( 'Minimized', 'ayuda-help-desk' ), 'full' => __( 'Full', 'ayuda-help-desk' )  ),
					'default' => 'min'
				),
				
				array(
					'name' => __( 'Bootstrap Support (Experimental)', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __('Options for loading bootstrap files. While you can enable this now please be aware that not all elements in the plugin renders properly.', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Load BootStrap Files on Front-end?', 'ayuda-help-desk' ),
					'id'      => 'load_bs4_files_fe',
					'type'    => 'radio',					
					'options' => array( '0' => __( 'Do not load bootstrap files', 'ayuda-help-desk' ), '1' => __( 'Load bootstrap 4 files located on maxcdn', 'ayuda-help-desk'), '2' => __( 'Load bootstrap 3 files located on maxcdn', 'ayuda-help-desk' )  ),
					'default' => '0'
				),
				array(
					'name'    => __( 'Load BootStrap Files on Back-end?', 'ayuda-help-desk' ),
					'id'      => 'load_bs4_files_be',
					'type'    => 'radio',					
					'options' => array( '0' => __( 'Do not load bootstrap files', 'ayuda-help-desk' ), '1' => __( 'Load bootstrap 4 files located on maxcdn', 'ayuda-help-desk'), '2' => __( 'Load bootstrap 3 files located on maxcdn', 'ayuda-help-desk' )  ),
					'default' => '0'
				),
				
				array(
					'name'    => __( 'Bootstrap 4 Theme', 'ayuda-help-desk' ),
					'id'      => 'bs4_theme',
					'type'    => 'select',
					'options' => array( 'default' => __( 'Default', 'ayuda-help-desk' ),
										'awesome'=> __( 'Future Ayuda – Help Desk BS4 Theme', 'ayuda-help-desk' ), 
										'cerulean' => __( 'Cerulean', 'ayuda-help-desk' ), 
										'cosmo' => __( 'Cosmo', 'ayuda-help-desk' ),
										'cyborg' => __( 'Cyborg', 'ayuda-help-desk' ),
										'darkly' => __( 'Darkly', 'ayuda-help-desk' ),										
										'flatly' => __( 'Flatly', 'ayuda-help-desk'), 
										'journal' => __( 'Journal', 'ayuda-help-desk'), 										
										'litera' => __( 'Litera', 'ayuda-help-desk'), 
										'lumen' => __( 'Lumen', 'ayuda-help-desk'), 
										'lux' => __( 'Lux', 'ayuda-help-desk'), 
										'materia' => __( 'Materia', 'ayuda-help-desk'), 
										'minty' => __( 'Minty', 'ayuda-help-desk'), 
										'pulse' => __( 'Pulse', 'ayuda-help-desk' ),
										'sandstone' => __( 'Sandstone', 'ayuda-help-desk' ), 
										'simplex' => __( 'Simplex', 'ayuda-help-desk' ),
										'slate' => __( 'Slate', 'ayuda-help-desk' ),
										'solar' => __( 'Solar', 'ayuda-help-desk' ), 
										'spacelab' => __( 'spacelab', 'ayuda-help-desk' ), 
										'united' => __( 'United', 'ayuda-help-desk' ), 
										'yeti' => __( 'Yeti', 'ayuda-help-desk' ), 
										'custom' => __( 'Custom theme', 'ayuda-help-desk' ), 
										),
					'default' => 'default'
				),				
				
				array(
					'name' => __( 'Sessions and Cookie Management', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Secure Cookies', 'ayuda-help-desk' ),
					'id'      => 'secure_cookies',
					'type'    => 'checkbox',
					'default' => false
				),
				array(
					'name'    => __( 'HTTP Only', 'ayuda-help-desk' ),
					'id'      => 'cookie_http_only',
					'type'    => 'checkbox',
					'default' => false
				),
				array(
					'name'    => __( 'Expired Sessions Cleanup Batch Size', 'ayuda-help-desk' ),
					'id'      => 'session_delete_batch_size',
					'type'    => 'number',
					'default' => 1000,
					'min'	  => 1000,
					'max'	  => 100000,
				),				
				
				array(
					'name' => __( 'Log Files', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc'    => __( 'Ayuda – Help Desk creates log files for certain events.  These are different from WordPress, PHP and your webserver log files.  Please tell us where you would like these files to be placed.', 'ayuda-help-desk' ),
				),

				array(
					'name'    => __( 'Where Should Log Files Be Stored?', 'ayuda-help-desk' ),
					'id'      => 'log_file_location',
					'type'    => 'radio',					
					'options' => array( '0' => __( 'Default', 'ayuda-help-desk' ), '1' => __( 'WordPress Uploads Folder', 'ayuda-help-desk'), '2' => __( 'In The Absolute Path Specified Below', 'ayuda-help-desk')  ),
					'default' => '0'
				),
				array(
					'name'    => __( 'None Of The Above - Place Log Files Here ', 'ayuda-help-desk' ),
					'id'      => 'log_file_location_absolute',
					'type'    => 'text',
					'desc'    => __( 'The absolute path to the log file location.  This must be relative to the server user account and must NOT end in a forward slash!', 'ayuda-help-desk' )
				),
				
				array(
					'name' => __( 'Danger Zone', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Delete Data', 'ayuda-help-desk' ),
					'id'      => 'delete_data',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Delete ALL plugin data on uninstall? This cannot be undone.', 'ayuda-help-desk' )
				),
			)
		),
	);

	return array_merge( $def, apply_filters('mumei_ayuda_settings_advanced', $settings ) );

}