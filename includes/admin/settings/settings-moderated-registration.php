<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_moderated_registration', 5, 1 );
/**
 * Add plugin core settings for moderated registration.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_moderated_registration( $def ) {

	$settings = array(
		'modregistration' => array(
			'name'    => __( 'Moderated Registration', 'awesome-support' ),
			'options' => array(

				array(
					'name' => __( 'Moderated Registration', 'awesome-support' ),
					'type' => 'heading'
				),						
				array(
					'type' => 'note',
					'desc' => __( 'Moderated registrations allow you to approve each user before they can use the ticketing system. <br />If moderated registration is turned on in the Registration Settings tab then the options shown below will allow you to fine-tune the behavior of registrations.', 'awesome-support' ),
				),			
			
				array(
					'name'    => __( 'Registration Submission Message', 'awesome-support' ),
					'id'      => 'mr_success_message',
					'type'    => 'editor',
					'desc'    => __( 'This is the message that we show to the user after they submit their registration request.', 'awesome-support' ),
					'default' => __( 'Your account has been created and submitted for review. The administrator will review it and notify you when it has been approved.', 'awesome-support' )
				),
				
				array(
					'name' => __( 'Roles', 'awesome-support' ),
					'type' => 'heading'
				),
				
				array(
					'name'    => __( 'Pending Registration Role', 'awesome-support' ),
					'id'      => 'moderated_pending_user_role',
					'type'    => 'text',
					'desc'    => __( 'This is the role that the user will have while a registration is waiting for admin approval. The role should be the internal WordPress role id such as wpas_user and is case sensitive. If you leave this blank the user will have no role on the site while waiting for registration approval.', 'awesome-support' ),
					'default' => ''
				),
				
				array(
					'name'    => __( 'Approved User Role', 'awesome-support' ),
					'id'      => 'moderated_activated_user_role',
					'type'    => 'text',
					'desc'    => __( 'This is the role the user will receive after a user registration request is approved. The role should be the internal WordPress role id such as wpas_user and is case sensitive.  Do not leave this blank.', 'awesome-support' ),
					'default' => 'wpas_user'
				),
				
				array(
					'name' => __( 'Moderated Registration Email Templates', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'Notify admins and users about pending and approved registrations', 'awesome-support' ),
				),
				
                array(
                        'name'    => __( 'Email to admin when a user registers', 'awesome-support' ),
                        'type'    => 'heading'
                ),
				
				array(
                        'name'    => __( 'Enable', 'awesome-support' ),
                        'id'      => "enable_moderated_registration_admin_email",
                        'type'    => 'checkbox',
                        'default' => true,
                        'desc'    => __( 'Send email to admin when a new user makes a registration request', 'awesome-support' )
                ),
				
                array(
                        'name'    => __( 'Subject', 'awesome-support' ),
                        'id'      => "moderated_registration_admin_email__subject",
                        'type'    => 'text',
                        'default' => 'New registration is waiting for approval.'
                ),

                array(
                        'name'    => __( 'Content', 'awesome-support' ),
                        'id'      => "moderated_registration_admin_email__content",
                        'type'    => 'editor',
                        'default' => 'You have received a new registration from your Awesome Support registration screen. <br /><br /> User Name: {first_name} {last_name} <br />User Email: {email} <br /> User Profile: {user_profile_link}<br /><br />You can click on the user profile link shown above to go directly to the user profile where you can approve or deny the registration.',
                        'desc'    => __( 'Email Content', 'awesome-support' )
                ),
				array(
                        'name'    => __( 'Email to users when they register', 'awesome-support' ),
                        'type'    => 'heading'
                ),

                array(
                        'name'    => __( 'Enable', 'awesome-support' ),
                        'id'      => "enable_moderated_registration_user_email",
                        'type'    => 'checkbox',
                        'default' => true,
                        'desc'    => __( 'Send email to users when their regisration request is waiting for approval', 'awesome-support' )
                ),
				
                array(
                        'name'    => __( 'Subject', 'awesome-support' ),
                        'id'      => "moderated_registration_user_email__subject",
                        'type'    => 'text',
                        'default' => 'Your registration on {site_name} has been submitted and is waiting for approval'
                ),

                array(
                        'name'    => __( 'Content', 'awesome-support' ),
                        'id'      => "moderated_registration_user_email__content",
                        'type'    => 'editor',
                        'default' => 'Hello {first_name}: <br /><br />We just wanted to let you know that your registration request has been successfully submitted and is waiting for approval.<br /><br /> - Your friends at {site_name} ',
                        'desc'    => __( 'Email Content', 'awesome-support' )
                ),
				
				
				array(
                        'name'    => __( 'Email to users when registration has been approved', 'awesome-support' ),
                        'type'    => 'heading'
                ),

                array(
                        'name'    => __( 'Enable', 'awesome-support' ),
                        'id'      => "enable_moderated_registration_approved_user_email",
                        'type'    => 'checkbox',
                        'default' => true,
                        'desc'    => __( 'Send email to user if their registration request is approved', 'awesome-support' )
                ),
				
                array(
                        'name'    => __( 'Subject', 'awesome-support' ),
                        'id'      => "moderated_registration_approved_user_email__subject",
                        'type'    => 'text',
                        'default' => 'Your registration on {site_name} has been approved'
                ),

                array(
                        'name'    => __( 'Content', 'awesome-support' ),
                        'id'      => "moderated_registration_approved_user_email__content",
                        'type'    => 'editor',
                        'default' => 'Hello {first_name}: <br /><br />We just wanted to let you know that your registration request has been approved. You can now log in and submit your first ticket.<br /><br /> - Your friends at {site_name} ',
                        'desc'    => __( 'Email Content', 'awesome-support' )
                ),
				
				
				array(
                        'name'    => __( 'Email to users when registration is denied', 'awesome-support' ),
                        'type'    => 'heading'
                ),

                array(
                        'name'    => __( 'Enable', 'awesome-support' ),
                        'id'      => "enable_moderated_registration_denied_user_email",
                        'type'    => 'checkbox',
                        'default' => true,
						'desc'    => __( 'Send email to user if their registration request is denied', 'awesome-support' )
                ),
				
                array(
                        'name'    => __( 'Subject', 'awesome-support' ),
                        'id'      => "moderated_registration_denied_user_email__subject",
                        'type'    => 'text',
                        'default' => 'Your registration on {site_name} has been denied'
                ),

                array(
                        'name'    => __( 'Content', 'awesome-support' ),
                        'id'      => "moderated_registration_denied_user_email__content",
                        'type'    => 'editor',
                        'default' => 'Hello {first_name}: <br /><br />We just wanted to let you know that your registration request has not been approved. If you have questions about this decision please use our contact form to follow up.<br /><br /> - Your friends at {site_name} ',
                        'desc'    => __( 'Email Content', 'awesome-support' )
                ),
			)
		),
	);

	return array_merge( $def, apply_filters('wpas_settings_mod_registration', $settings )  );

}