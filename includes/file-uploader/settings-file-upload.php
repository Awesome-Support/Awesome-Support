<?php
add_filter( 'wpas_plugin_settings', 'wpas_addon_settings_file_upload', 10, 1 );
/**
 * Add plugin file upload settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_addon_settings_file_upload( $def ) {

	$settings = array(
		'file_upload' => array(
			'name'    => __( 'File Upload', 'awesome-support' ),
			'options' => array(
				array(
					'name'    => __( 'Enable File Upload', 'awesome-support' ),
					'id'      => 'enable_attachments',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to allow your users (and agents) to upload attachments to tickets and replies?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Maximum Files', 'awesome-support' ),
					'id'      => 'attachments_max',
					'type'    => 'text',
					'default' => 2,
					'desc'    => __( 'How many files can a user attach to a ticket or a reply?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Maximum File Size', 'awesome-support' ),
					'id'      => 'filesize_max',
					'type'    => 'text',
					'default' => 2,
					'desc'    => sprintf( __( 'What is the maximum size allowed for one file (in <code>MB</code>)? Your server allows up to %s', 'awesome-support' ), ini_get('upload_max_filesize') )
				),
				array(
					'name'    => __( 'Allowed Files Types', 'awesome-support' ),
					'id'      => 'attachments_filetypes',
					'type'    => 'textarea',
					'default' => 'jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,pps,ppsx,odt,xls,xlsx,mp3,m4a,ogg,wav,mp4,m4v,mov,wmv,avi,mpg,ogv,3gp,3g2,zip',
					'desc'    => sprintf( __( 'Which file types do you allow your users to attach? Please separate each extension by a comma (%s)', 'awesome-support' ), '<code>,</code>' )
				),
				
				array(
					'name' => __( 'Drag and Drop Uploads', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Enable or disable drag-and-drop uploads as well as pasting of images', 'awesome-support' ),					
				),				
				array(
					'name'    => __( 'Enable', 'awesome-support' ),
					'id'      => 'ajax_upload',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Enable drag-n-drop uploader for ticket form', 'awesome-support' )
				),
				array(
					'name'    => __( 'Enable For All', 'awesome-support' ),
					'id'      => 'ajax_upload_all',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Enable drag-n-drop uploader for all custom upload fields', 'awesome-support' )
				),
				array(
					'name'    => __( 'Enable Image Paste', 'awesome-support' ),
					'id'      => 'ajax_upload_paste_image',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Enable pasting of images into drag-n-drop uploader for ticket form when the drag-n-drop uploader is enabled', 'awesome-support' )
				),
				array(
					'name'    => __( 'Enable Image Paste For All', 'awesome-support' ),
					'id'      => 'ajax_upload_paste_image_all',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Enable pasting of images into drag-n-drop uploader for all custom upload fields when the drag-n-drop uploader is enabled', 'awesome-support' )
				),
				
				array(
					'name' => __( 'Permissions', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Control who can delete ticket or reply attachments', 'awesome-support' ),					
				),
				
				array(
					'name'    => __( 'Agents Can Delete?', 'awesome-support' ),
					'id'      => 'agents_can_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Check this to allow agents to delete attachments', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'Users Can Delete?', 'awesome-support' ),
					'id'      => 'users_can_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Check this to allow users to delete attachments', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'Auto-delete On Close?', 'awesome-support' ),
					'id'      => 'auto_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Automatically delete ALL attachments on a ticket when the ticket is closed', 'awesome-support' ),
					'default' => false
				),
				array(
					'name'    => __( 'User Controls Auto-delete Flag?', 'awesome-support' ),
					'id'      => 'user_can_set_auto_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Can the user control whether or not attachments should be automatically deleted on close?', 'awesome-support' ),					
					'default' => false
				),
				array(
					'name'    => __( 'Agent Controls Auto-delete Flag?', 'awesome-support' ),
					'id'      => 'agent_can_set_auto_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Can agents control whether or not attachments should be automatically deleted on close?', 'awesome-support' ),					
					'default' => true
				),				
				
			)
		),
	);

	return array_merge( $def, $settings );

}