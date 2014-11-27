<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_notifications', 5, 1 );
/**
 * Add plugin notifications settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_core_settings_notifications( $def ) {

	$settings = array(
		'email' => array(
			'name'    => __( 'E-Mails', 'wpas' ),
			'options' => array(
				array(
					'type' => 'note',
					'desc' => __( 'For more information about the template tags that can be used in e-mail templates please click the &laquo;Help&raquo; button in the top right hand corner ofthis screen.', 'wpas' )
				),
				array(
					'name'    => __( 'Sender Name', 'wpas' ),
					'id'      => 'sender_name',
					'type'    => 'text',
					'default' => get_bloginfo( 'name' )
				),
				array(
					'name'    => __( 'Sender E-Mail', 'wpas' ),
					'id'      => 'sender_email',
					'type'    => 'text',
					'default' => get_bloginfo( 'admin_email' )
				),
				/* Submission confirmation */
				array(
					'name' => __( 'Submission Confirmation', 'wpas' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'wpas' ),
					'id'      => 'enable_confirmation',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'wpas' )
				),
				array(
					'name'    => __( 'Subject', 'wpas' ),
					'id'      => 'subject_confirmation',
					'type'    => 'text',
					'default' => __( 'Request received: {ticket_title}', 'wpas' )
				),
				array(
					'name'     => __( 'Content', 'wpas' ),
					'id'       => 'content_confirmation',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{client_name}</em>,</strong></p><p>Your request (<a href="{ticket_url}">#{ticket_id}</a>) has been received, and is being reviewed by our support staff.</p><p>To add additional comments, follow the link below:</p><h2><a href="{ticket_url}">View Ticket</a></h2><p>or follow this link: {ticket_link}</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New assignment */
				array(
					'name' => __( 'New Assignment', 'wpas' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'wpas' ),
					'id'      => 'enable_assignment',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'wpas' )
				),
				array(
					'name'    => __( 'Subject', 'wpas' ),
					'id'      => 'subject_assignment',
					'type'    => 'text',
					'default' => __( 'Ticket #{ticket_id} assigned', 'wpas' )
				),
				array(
					'name'     => __( 'Content', 'wpas' ),
					'id'       => 'content_assignment',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{agent_name},</em></strong></p><p>The request <strong>{ticket_title}</strong> (<a href="{ticket_admin_url}">#{ticket_id}</a>) has been assigned to you.</p><h2><a href="{ticket_admin_url}">View  Ticket</a></h2><p>or follow this link: {ticket_admin_link}</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New reply from agent */
				array(
					'name' => __( 'New Reply from Agent', 'wpas' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'wpas' ),
					'id'      => 'enable_reply_agent',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'wpas' )
				),
				array(
					'name'    => __( 'Subject', 'wpas' ),
					'id'      => 'subject_reply_agent',
					'type'    => 'text',
					'default' => __( 'New reply to: {ticket_title}', 'wpas' )
				),
				array(
					'name'     => __( 'Content', 'wpas' ),
					'id'       => 'content_reply_agent',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{client_name}</em>,</strong></p><p>An agent just replied to your ticket "<strong>{ticket_title}</strong>" (<a href="{ticket_url}">#{ticket_id}</a>). To view his reply or add additional comments, click the button below:</p><h2><a href="{ticket_url}">View Ticket</a></h2><p>or follow this link: {ticket_link}</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New reply from client */
				array(
					'name' => __( 'New Reply from Client', 'wpas' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'wpas' ),
					'id'      => 'enable_reply_client',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'wpas' )
				),
				array(
					'name'    => __( 'Subject', 'wpas' ),
					'id'      => 'subject_reply_client',
					'type'    => 'text',
					'default' => __( 'Ticket #{ticket_id}', 'wpas' )
				),
				array(
					'name'     => __( 'Content', 'wpas' ),
					'id'       => 'content_reply_client',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{agent_name},</em></strong></p><p>A client you are in charge of just posted a new reply to his ticket "<strong>{ticket_title}</strong>".</p><h2><a href="{ticket_admin_url}">View  Ticket</a></h2><p>or follow this link: {ticket_admin_link}</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* Ticket will close */
				array(
					'name' => __( 'Ticket Will Be Closed', 'wpas' ),
					'type' => 'heading',
				),
				/*array(
					'name'    => __( 'Enable', 'wpas' ),
					'id'      => 'enable_will_close',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'wpas' )
				),
				array(
					'name'    => __( 'Subject', 'wpas' ),
					'id'      => 'subject_will_close',
					'type'    => 'text',
					'default' => ''
				),
				array(
					'name'     => __( 'Content', 'wpas' ),
					'id'       => 'content_will_close',
					'type'     => 'editor',
					'default'  => '',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),*/
				/* Ticket closed */
				array(
					'name' => __( 'Ticket Closed', 'wpas' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'wpas' ),
					'id'      => 'enable_closed',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'wpas' )
				),
				array(
					'name'    => __( 'Subject', 'wpas' ),
					'id'      => 'subject_closed',
					'type'    => 'text',
					'default' => __( 'Request closed: {ticket_title}', 'wpas' )
				),
				array(
					'name'     => __( 'Content', 'wpas' ),
					'id'       => 'content_closed',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{client_name},</em></strong></p>Your request (<a href="{ticket_url}">#{ticket_id}</a>) has been closed by <strong>{agent_name}</strong>.</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
			)
		),
	);

	return array_merge( $def, $settings );

}