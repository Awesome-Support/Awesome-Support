<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_addon_settings_file_upload', 10, 1 );
/**
 * Add plugin file upload settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function mumei_ayuda_addon_settings_file_upload( $def ) {

	// translators: %s is the maximum size allowed for one file.
	$desc = __( 'What is the maximum size allowed for one file (in <code>MB</code>)? Your server allows up to %s', 'ayuda-help-desk' );

	// translators: %s is the file types.
	$desc1 = __( 'Which file types do you allow your users to attach? Please separate each extension by a comma (%s)', 'ayuda-help-desk' );

	$settings = array(
		'file_upload' => array(
			'name'    => __( 'File Upload', 'ayuda-help-desk' ),
			'options' => array(
				array(
					'name'    => __( 'Enable File Upload', 'ayuda-help-desk' ),
					'id'      => 'enable_attachments',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to allow your users (and agents) to upload attachments to tickets and replies?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Maximum Files', 'ayuda-help-desk' ),
					'id'      => 'attachments_max',
					'type'    => 'text',
					'default' => 2,
					'desc'    => __( 'How many files can a user attach to a ticket or a reply?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Maximum File Size', 'ayuda-help-desk' ),
					'id'      => 'filesize_max',
					'type'    => 'text',
					'default' => 2,
					'desc'    => sprintf( $desc, ini_get('upload_max_filesize') )
				),
				array(
					'name'    => __( 'Allowed Files Types', 'ayuda-help-desk' ),
					'id'      => 'attachments_filetypes',
					'type'    => 'textarea',
					'default' => 'jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,pps,ppsx,odt,xls,xlsx,mp3,m4a,ogg,wav,mp4,m4v,mov,wmv,avi,mpg,ogv,3gp,3g2,zip',
					'desc'    => sprintf( $desc1, '<code>,</code>' )
				),
				
				array(
					'name' => __( 'Drag and Drop Uploads', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc'    => __( 'Enable or disable drag-and-drop uploads as well as pasting of images', 'ayuda-help-desk' ),					
				),				
				array(
					'name'    => __( 'Enable', 'ayuda-help-desk' ),
					'id'      => 'ajax_upload',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Enable drag-n-drop uploader for ticket form', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Enable For All', 'ayuda-help-desk' ),
					'id'      => 'ajax_upload_all',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Enable drag-n-drop uploader for all custom upload fields', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Enable Image Paste', 'ayuda-help-desk' ),
					'id'      => 'ajax_upload_paste_image',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Enable pasting of images into drag-n-drop uploader for ticket form when the drag-n-drop uploader is enabled', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Enable Image Paste For All', 'ayuda-help-desk' ),
					'id'      => 'ajax_upload_paste_image_all',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Enable pasting of images into drag-n-drop uploader for all custom upload fields when the drag-n-drop uploader is enabled', 'ayuda-help-desk' )
				),
				
				array(
					'name' => __( 'Permissions', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc'    => __( 'Control who can delete ticket or reply attachments', 'ayuda-help-desk' ),					
				),
				
				array(
					'name'    => __( 'Agents Can Delete?', 'ayuda-help-desk' ),
					'id'      => 'agents_can_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Check this to allow agents to delete attachments', 'ayuda-help-desk' ),
					'default' => false
				),
				array(
					'name'    => __( 'Users Can Delete?', 'ayuda-help-desk' ),
					'id'      => 'users_can_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Check this to allow users to delete attachments', 'ayuda-help-desk' ),
					'default' => false
				),
				array(
					'name'    => __( 'Auto-delete On Close?', 'ayuda-help-desk' ),
					'id'      => 'auto_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Automatically delete ALL attachments on a ticket when the ticket is closed', 'ayuda-help-desk' ),
					'default' => false
				),
				array(
					'name'    => __( 'User Controls Auto-delete Flag?', 'ayuda-help-desk' ),
					'id'      => 'user_can_set_auto_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Can the user control whether or not attachments should be automatically deleted on close?', 'ayuda-help-desk' ),					
					'default' => false
				),
				array(
					'name'    => __( 'Agent Controls Auto-delete Flag?', 'ayuda-help-desk' ),
					'id'      => 'agent_can_set_auto_delete_attachments',
					'type'    => 'checkbox',
					'desc'    => __( 'Can agents control whether or not attachments should be automatically deleted on close?', 'ayuda-help-desk' ),					
					'default' => true
				),				
				
			)
		),
	);

	return array_merge( $def, $settings );

}