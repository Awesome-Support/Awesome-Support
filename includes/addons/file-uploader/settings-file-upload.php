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
			'name'    => __( 'File Upload', 'wpas' ),
			'options' => array(
				array(
					'name'    => __( 'Enable File Upload', 'wpas' ),
					'id'      => 'enable_attachments',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to allow your users (and agents) to upload attachments to tickets and replies?', 'wpas' )
				),
				array(
					'name'    => __( 'Maximum Files', 'wpas' ),
					'id'      => 'attachments_max',
					'type'    => 'text',
					'default' => 2,
					'desc'    => __( 'How many files can a user attach to a ticket or a reply?', 'wpas' )
				),
				array(
					'name'    => __( 'Maximum File Size', 'wpas' ),
					'id'      => 'filesize_max',
					'type'    => 'text',
					'default' => 2,
					'desc'    => sprintf( __( 'What is the maximum size allowed for one file (in <code>Mo</code>)? Your server allows up to %s', 'wpas' ), ini_get('upload_max_filesize') )
				),
				array(
					'name'    => __( 'Allowed Files Types', 'wpas' ),
					'id'      => 'attachments_filetypes',
					'type'    => 'textarea',
					'default' => 'jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,pps,ppsx,odt,xls,xlsx,mp3,m4a,ogg,wav,mp4,m4v,mov,wmv,avi,mpg,ogv,3gp,3g2,zip',
					'desc'    => sprintf( __( 'Which file types do you allow your users to attach? Please separate each extension by a comma (%s)', 'wpas' ), '<code>,</code>' )
				),
			)
		),
	);

	return array_merge( $def, $settings );

}