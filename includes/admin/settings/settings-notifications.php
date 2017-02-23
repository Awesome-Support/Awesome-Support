<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_notifications', 5, 1 );
/**
 * Add plugin notifications settings.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_notifications( $def ) {

	$settings = array(
		'email' => array(
			'name'    => __( 'E-Mails', 'awesome-support' ),
			'options' => array(
				array(
					'type' => 'note',
					'desc' => __( 'For more information about the template tags that can be used in e-mail templates please click the &laquo;Help&raquo; button in the top right hand corner of this screen.', 'awesome-support' )
				),
				array(
					'name' => __( 'E-Mail Template', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Use Template', 'awesome-support' ),
					'id'      => 'use_email_template',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Outgoing notifications are styled with a built-in template. If you are using an e-mail templating plugin you should deactivate this option.', 'awesome-support' )
				),
				array(
					'type' => 'note',
					'desc' => wp_kses( sprintf( __( 'Please note that the <a href="%1$s" target="%2$s">e-mail template we use</a> is optimized for all e-mail clients and devices. If you add fancy styling through the editors hereafter, we cannot guarantee full compatibility anymore.', 'awesome-support' ), 'https://github.com/mailgun/transactional-email-templates', '_blank' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) )
				),
				array(
					'name'    => __( 'Logo', 'awesome-support' ),
					'id'      => 'email_template_logo',
					'type'    => 'upload',
					'default' => '',
					'desc'    => __( 'A logo that displays at the top of the e-mail notification.', 'awesome-support' )
				),
				array(
					'name'     => __( 'Header', 'awesome-support' ),
					'id'       => 'email_template_header',
					'type'     => 'editor',
					'default'  => '<p>' . get_bloginfo( 'site_name' ) . '</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				array(
					'name'     => __( 'Footer', 'awesome-support' ),
					'id'       => 'email_template_footer',
					'type'     => 'editor',
					'default'  => '',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				array(
					'name' => __( 'E-Mail Defaults', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Sender Name', 'awesome-support' ),
					'id'      => 'sender_name',
					'type'    => 'text',
					'default' => get_bloginfo( 'name' )
				),
				array(
					'name'    => __( 'Sender E-Mail', 'awesome-support' ),
					'id'      => 'sender_email',
					'type'    => 'text',
					'default' => get_bloginfo( 'admin_email' )
				),
				array(
					'name'    => __( 'Reply-To E-Mail', 'awesome-support' ),
					'id'      => 'reply_email',
					'type'    => 'text',
					'default' => get_bloginfo( 'admin_email' )
				),
				/* Submission confirmation */
				array(
					'name' => __( 'Submission Confirmation', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'awesome-support' ),
					'id'      => 'enable_confirmation',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Subject', 'awesome-support' ),
					'id'      => 'subject_confirmation',
					'type'    => 'text',
					'default' => __( 'Request received: {ticket_title}', 'awesome-support' )
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'content_confirmation',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{client_name}</em>,</strong></p><p>Your request (<a href="{ticket_url}">#{ticket_id}</a>) has been received, and is being reviewed by our support staff.</p><p>To add additional comments, follow the link below:</p><h2><a href="{ticket_url}">View Ticket</a></h2><p>or follow this link: {ticket_link}</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New assignment */
				array(
					'name' => __( 'New Assignment', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'awesome-support' ),
					'id'      => 'enable_assignment',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Subject', 'awesome-support' ),
					'id'      => 'subject_assignment',
					'type'    => 'text',
					'default' => __( 'Ticket #{ticket_id} assigned', 'awesome-support' )
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'content_assignment',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{agent_name},</em></strong></p><p>The request <strong>{ticket_title}</strong> (<a href="{ticket_admin_url}">#{ticket_id}</a>) has been assigned to you.</p><h2><a href="{ticket_admin_url}">View  Ticket</a></h2><p>or follow this link: {ticket_admin_link}</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New reply from agent */
				array(
					'name' => __( 'New Reply from Agent', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'awesome-support' ),
					'id'      => 'enable_reply_agent',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Subject', 'awesome-support' ),
					'id'      => 'subject_reply_agent',
					'type'    => 'text',
					'default' => __( 'New reply to: {ticket_title}', 'awesome-support' )
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'content_reply_agent',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{client_name}</em>,</strong></p><p>An agent just replied to your ticket "<strong>{ticket_title}</strong>" (<a href="{ticket_url}">#{ticket_id}</a>). To view his reply or add additional comments, click the button below:</p><h2><a href="{ticket_url}">View Ticket</a></h2><p>or follow this link: {ticket_link}</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New reply from client */
				array(
					'name' => __( 'New Reply from Client', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'awesome-support' ),
					'id'      => 'enable_reply_client',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Subject', 'awesome-support' ),
					'id'      => 'subject_reply_client',
					'type'    => 'text',
					'default' => __( 'Ticket #{ticket_id}', 'awesome-support' )
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'content_reply_client',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{agent_name},</em></strong></p><p>A client you are in charge of just posted a new reply to his ticket "<strong>{ticket_title}</strong>".</p><h2><a href="{ticket_admin_url}">View  Ticket</a></h2><p>or follow this link: {ticket_admin_link}</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* Ticket will close */
				array(
					'name' => __( 'Ticket Will Be Closed', 'awesome-support' ),
					'type' => 'heading',
				),

				/* Ticket closed */
				array(
					'name' => __( 'Ticket Closed (by agent)', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'awesome-support' ),
					'id'      => 'enable_closed',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Subject', 'awesome-support' ),
					'id'      => 'subject_closed',
					'type'    => 'text',
					'default' => __( 'Request closed: {ticket_title}', 'awesome-support' )
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'content_closed',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{client_name},</em></strong></p>Your request (<a href="{ticket_url}">#{ticket_id}</a>) has been closed by <strong>{agent_name}</strong>.</p><hr><p>Regards,<br>{site_name}</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				array(
					'name' => __( 'Ticket Closed (by client)', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'awesome-support' ),
					'id'      => 'enable_closed_client',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Subject', 'awesome-support' ),
					'id'      => 'subject_closed_client',
					'type'    => 'text',
					'default' => __( 'Request closed: {ticket_title}', 'awesome-support' )
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'content_closed_client',
					'type'     => 'editor',
					'default'  => '<p>Hi <strong><em>{agent_name},</em></strong></p>The ticket (<a href="{ticket_admin_url}">#{ticket_id}</a>) has been closed by <strong>{client_name}</strong>.</p><p>Good job!</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
			)
		),
	);

	return array_merge( $def, $settings );

}