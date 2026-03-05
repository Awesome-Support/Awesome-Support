<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_settings_notifications', 5, 1 );
/**
 * Add plugin notifications settings.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function mumei_ayuda_core_settings_notifications( $def ) {
	
	// translators: %1$s is the URL to the email template, %2$s is the target attribute for the link (e.g., "_blank").
	$desc = __( 'Please note that the <a href="%1$s" target="%2$s">e-mail template we use</a> is optimized for all e-mail clients and devices. If you add additional fancy styling through the editors you should do so with caution in order to avoid breaking the layouts on some devices.', 'ayuda-help-desk' );

	// translators: %s is the URL to the documentation.
	$desc1 = __( 'We include a default set of designs for the six core emails below.  You can use these designs in other email templates by simply copying them to the target email template editor and modifying them there.  You can also install new designs from the TICKETS->TOOLS->CLEANUP area. Samples of some of the email templates can be found in our <a href="%s">documentation</a>.', 'ayuda-help-desk' );
	
	$settings = array(
		'email' => array(
			'name'    => __( 'E-Mails', 'ayuda-help-desk' ),
			'options' => array(
				array(
					'type' => 'note',
					'desc' => __( 'For more information about the template tags that can be used in e-mail templates please click the &laquo;Help&raquo; button in the top right hand corner of this screen.', 'ayuda-help-desk' )
				),
				array(
					'name' => __( 'Global E-Mail Template', 'ayuda-help-desk' ),
					'desc' => __( 'The HTML E-Mail Template options below controls how the logo, header and footer appear in ALL your outgoing Ayuda – Help Desk emails.  You can completely disable this global template if necessary.', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Use HTML Template', 'ayuda-help-desk' ),
					'id'      => 'use_email_template',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Outgoing notifications are styled with a built-in html template. If you are using an e-mail templating plugin you should deactivate this option.', 'ayuda-help-desk' )
				),
				array(
					'type' => 'note',
					'desc' => wp_kses( sprintf( $desc, 'https://github.com/mailgun/transactional-email-templates', '_blank' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) )
				),
				array(
					'name'    => __( 'Logo', 'ayuda-help-desk' ),
					'id'      => 'email_template_logo',
					'type'    => 'upload',
					'default' => '',
					'desc'    => __( 'A logo that displays at the top of the e-mail notification.', 'ayuda-help-desk' )
				),
				array(
					'name'     => __( 'Header', 'ayuda-help-desk' ),
					'id'       => 'email_template_header',
					'type'     => 'editor',
					'default'  => '<p>' . get_bloginfo( 'site_name' ) . '</p>',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				array(
					'name'     => __( 'Footer', 'ayuda-help-desk' ),
					'id'       => 'email_template_footer',
					'type'     => 'editor',
					'default'  => '',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				array(
					'name' => __( 'E-Mail Defaults', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Sender Name', 'ayuda-help-desk' ),
					'id'      => 'sender_name',
					'type'    => 'text',
					'default' => get_bloginfo( 'name' )
				),
				array(
					'name'    => __( 'Sender E-Mail', 'ayuda-help-desk' ),
					'id'      => 'sender_email',
					'type'    => 'text',
					'default' => get_bloginfo( 'admin_email' )
				),
				array(
					'name'    => __( 'Reply-To E-Mail', 'ayuda-help-desk' ),
					'id'      => 'reply_email',
					'type'    => 'text',
					'default' => get_bloginfo( 'admin_email' )
				),
				
				array(
					'name' => __( 'Email Contents', 'ayuda-help-desk' ),
					'desc' => __( 'Configure the contents of your emails below.', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Design Notes', 'ayuda-help-desk' ),
					'id'      => 'reply_design_notes',
					'type'    => 'note',
					'desc' => sprintf( $desc1, 'https://getawesomesupport.com/documentation/ayuda-help-desk/admin-email-template-sets/' ),
				),				
				array(				
					'name'    => __( 'Template Tags', 'ayuda-help-desk' ),				
					'type' => 'note',
					'desc' => __( 'Email template tags allow you to insert real-time information into your outgoing emails.  For example you can use tags to refer to the users name or the agent name.  For more information about the template tags that can be used in e-mail templates please click the &laquo;Help&raquo; button in the top right hand corner of this screen.  You can also click on the <b>{ }</b> icon in each of the editors for a pop-up list.', 'ayuda-help-desk' )
				),
				/* Submission confirmation */
				array(
					'name' => __( 'Submission Confirmation', 'ayuda-help-desk' ),
					'desc' => __( 'This is sent to the user when they open a new ticket.', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'ayuda-help-desk' ),
					'id'      => 'enable_confirmation',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Subject', 'ayuda-help-desk' ),
					'id'      => 'subject_confirmation',
					'type'    => 'text',
					'default' => __( 'Request received: {ticket_title}', 'ayuda-help-desk' )
				),
				array(
					'name'     => __( 'Content', 'ayuda-help-desk' ),
					'id'       => 'content_confirmation',
					'type'     => 'editor',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New assignment */
				array(
					'name' => __( 'New Assignment', 'ayuda-help-desk' ),
					'desc' => __( 'This is sent to the agent when they are assigned to a ticket.', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'ayuda-help-desk' ),
					'id'      => 'enable_assignment',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Subject', 'ayuda-help-desk' ),
					'id'      => 'subject_assignment',
					'type'    => 'text',
					'default' => __( 'Ticket #{ticket_id} assigned', 'ayuda-help-desk' )
				),
				array(
					'name'     => __( 'Content', 'ayuda-help-desk' ),
					'id'       => 'content_assignment',
					'type'     => 'editor',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New reply from agent */
				array(
					'name' => __( 'New Reply from Agent', 'ayuda-help-desk' ),
					'desc' => __( 'This is sent to the user when an agent replies to a ticket.', 'ayuda-help-desk' ),					
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'ayuda-help-desk' ),
					'id'      => 'enable_reply_agent',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Subject', 'ayuda-help-desk' ),
					'id'      => 'subject_reply_agent',
					'type'    => 'text',
					'default' => __( 'New reply to: {ticket_title}', 'ayuda-help-desk' )
				),
				array(
					'name'     => __( 'Content', 'ayuda-help-desk' ),
					'id'       => 'content_reply_agent',
					'type'     => 'editor',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* New reply from client */
				array(
					'name' => __( 'New Reply from Client', 'ayuda-help-desk' ),
					'desc' => __( 'This is sent to agents when a new reply is received from a user.', 'ayuda-help-desk' ),					
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'ayuda-help-desk' ),
					'id'      => 'enable_reply_client',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Subject', 'ayuda-help-desk' ),
					'id'      => 'subject_reply_client',
					'type'    => 'text',
					'default' => __( 'Ticket #{ticket_id}', 'ayuda-help-desk' )
				),
				array(
					'name'     => __( 'Content', 'ayuda-help-desk' ),
					'id'       => 'content_reply_client',
					'type'     => 'editor',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				/* Ticket will close */
				array(
					'name' => __( 'Ticket Will Be Closed', 'ayuda-help-desk' ),
					'desc' => __( 'These are sent as alerts to the user before a ticket is automatically closed (requires auto-close add-on otherwise this section is blank).', 'ayuda-help-desk' ),					
					'type' => 'heading',
				),
				/* Ticket closed by agent */
				array(
					'name' => __( 'Ticket Closed (by Agent)', 'ayuda-help-desk' ),
					'desc' => __( 'This is sent to the user when an agent closes a ticket.', 'ayuda-help-desk' ),					
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'ayuda-help-desk' ),
					'id'      => 'enable_closed',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Subject', 'ayuda-help-desk' ),
					'id'      => 'subject_closed',
					'type'    => 'text',
					'default' => __( 'Request closed: {ticket_title}', 'ayuda-help-desk' )
				),
				array(
					'name'     => __( 'Content', 'ayuda-help-desk' ),
					'id'       => 'content_closed',
					'type'     => 'editor',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				array(
					'name'    => __( 'Agents Can Suppress?', 'ayuda-help-desk' ),
					'id'      => 'agents_can_suppress_closing_emails',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => '<em>' . __( 'Can agents prevent a closing confirmation email from being sent to a customer? Note: If this enabled it only applies to the emails defined in the core plugin and not to emails created by add-ons such as our Notification or Business Rules Engine add-ons', 'ayuda-help-desk' ) . '</em>'
				),
				/* Ticket closed by client*/				
				array(
					'name' => __( 'Ticket Closed (by client)', 'ayuda-help-desk' ),
					'desc' => __( 'This is sent to the agent when a user closes a ticket.', 'ayuda-help-desk' ),					
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Enable', 'ayuda-help-desk' ),
					'id'      => 'enable_closed_client',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to activate this e-mail template?', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Subject', 'ayuda-help-desk' ),
					'id'      => 'subject_closed_client',
					'type'    => 'text',
					'default' => __( 'Request closed: {ticket_title}', 'ayuda-help-desk' )
				),
				array(
					'name'     => __( 'Content', 'ayuda-help-desk' ),
					'id'       => 'content_closed_client',
					'type'     => 'editor',
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
			)
		),
	);

	return array_merge( $def, apply_filters('mumei_ayuda_settings_notifications', $settings )  );

}