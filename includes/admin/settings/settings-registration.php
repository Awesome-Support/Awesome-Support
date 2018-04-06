<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_registration', 5, 1 );
/**
 * Add plugin core settings for registration.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_registration( $def ) {

	$user_registration = boolval( get_option( 'users_can_register' ) );
	$registration_lbl  = ( true === $user_registration ) ? _x( 'allowed', 'User registration is allowed', 'awesome-support' ) : _x( 'not allowed', 'User registration is not allowed', 'awesome-support' );

	$settings = array(
		'registration' => array(
			'name'    => __( 'Registration', 'awesome-support' ),
			'options' => array(
				array(
					'name' => __( 'Registration', 'awesome-support' ),
					'type' => 'heading',
				),

				array(
					'name'    => __( 'Allow Registrations', 'awesome-support' ),
					'id'      => 'allow_registrations',
					'type'    => 'radio',
					'desc'    => sprintf( __( 'Allow users to register on the support page. This setting can be enabled even though the WordPress setting is disabled. Currently, registrations are %s by WordPress.', 'awesome-support' ),  "<strong>$registration_lbl</strong>" ),
					'default' => 'allow',
					'options' => array(
						'allow'           => __( 'Allow registrations', 'awesome-support' ),
						'disallow'        => __( 'Disallow registrations', 'awesome-support' ),
						'disallow_silent' => __( 'Disallow registrations without notice (just show the login form)', 'awesome-support' ),
					)
				),
				
				array(
					'name' => __( 'Registration Alerts', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'Who should receive the standard WordPress notifications when a new user registers on the site?', 'awesome-support' ),
				),
				array(
					'name'    => __( 'Who Should Receive New User Notifications?', 'awesome-support' ),
					'id'      => 'reg_notify_users',
					'type'    => 'radio',
					'options' => array( 'none' => __('No One','awesome-support'), 'user' => __('Only The Customer','awesome-support'), 'admin' => __('Only The Site Admin','awesome-support'), 'both' => __('Customer and Admin','awesome-support') ),
					'default' => 'both,',
				),
				
				array(
					'name' => __( 'User Names', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'How should user names be determined?', 'awesome-support' ),
				),												
				array(
					'name'    => __( 'User Name Construction', 'awesome-support' ),
					'id'      => 'reg_user_name_construction',
					'type'    => 'radio',
					'default' => 6,
					'desc'    => __( 'How should we construct the user name?', 'awesome-support' ),
					'options' => array(
						'0' => __( 'Default - Uses the first part of email address', 'awesome-support' ),
						'1' => __( 'Use the entire email address', 'awesome-support' ),
						'2' => __( 'Use a random number', 'awesome-support' ),
						'3' => __( 'Use a GUID', 'awesome-support' ),
						'4' => __( 'Use the first name', 'awesome-support' ),
						'5' => __( 'Use the last name', 'awesome-support' ),
						'6' => __( 'Concatenate the first and last name', 'awesome-support' ),
					),
				),				
				
				array(
					'name' => __( 'Registration Field Descriptions', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'Add a description to each of the registration fields which will appear beneath the field. You can use this to add GDPR related information indicating what each field is used for.', 'awesome-support' ),
				),
				array(
					'name'     => __( 'First name description', 'awesome-support' ),
					'id'       => 'reg_first_name_desc',
					'type'     => 'text',
					'default'  => '',
				),
				array(
					'name'     => __( 'Last name description', 'awesome-support' ),
					'id'       => 'reg_last_name_desc',
					'type'     => 'text',
					'default'  => '',
				),
				array(
					'name'     => __( 'Email address description', 'awesome-support' ),
					'id'       => 'reg_email_desc',
					'type'     => 'text',
					'default'  => '',
				),				

				array(
					'name' => __( 'GDPR Notice #1', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'The GDPR requires that you provide explicit notice about what you will do with the data you collect from users. This section allows you to describe how any personal data collected will be used and require consent from the user before they can register.', 'awesome-support' ),
				),
				array(
					'name'     => __( 'GDPR Notice: Short Description', 'awesome-support' ),
					'id'       => 'gdpr_notice_short_desc_01',
					'type'     => 'text',
					'default'  => '',
					'desc'     => __( 'If you fill this in, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t tick the checkbox.  It is best to keep this brief - eg: Receive Emails? or Join Email List?', 'awesome-support' ),
				),				
				array(
					'name'     => __( 'GDPR Notice: Long Description', 'awesome-support' ),
					'id'       => 'gdpr_notice_long_desc_01',
					'type'     => 'editor',
					'default'  => '',
					'desc'     => __( 'If you add notice terms in this box, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept these notice terms.  It is best to keep this notice to one or two lines.', 'awesome-support' ),
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				
				array(
					'name' => __( 'GDPR Notice #2', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'If personal data will be used in any additional context add that here. It is best to keep this brief.', 'awesome-support' ),
				),
				array(
					'name'     => __( 'GDPR Notice: Short Description', 'awesome-support' ),
					'id'       => 'gdpr_notice_short_desc_02',
					'type'     => 'text',
					'default'  => '',
					'desc'     => __( 'If you fill this in, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t tick the checkbox.  It is best to keep this brief - eg: Receive Emails? or Join Email List?', 'awesome-support' ),
				),				
				array(
					'name'     => __( 'GDPR Notice: Long Description', 'awesome-support' ),
					'id'       => 'gdpr_notice_long_desc_02',
					'type'     => 'editor',
					'default'  => '',
					'desc'     => __( 'If you add notice terms in this box, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept these notice terms.  It is best to keep this notice to one or two lines.', 'awesome-support' ),
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				
				array(
					'name' => __( 'GDPR Notice #3', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'If personal data will be used in any additional context add that here. It is best to keep this brief.', 'awesome-support' ),
				),
				array(
					'name'     => __( 'GDPR Notice: Short Description', 'awesome-support' ),
					'id'       => 'gdpr_notice_short_desc_03',
					'type'     => 'text',
					'default'  => '',
					'desc'     => __( 'If you fill this in, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t tick the checkbox.  It is best to keep this brief - eg: Receive Emails? or Join Email List?', 'awesome-support' ),
				),				
				array(
					'name'     => __( 'GDPR Notice: Long Description', 'awesome-support' ),
					'id'       => 'gdpr_notice_long_desc_03',
					'type'     => 'editor',
					'default'  => '',
					'desc'     => __( 'If you add notice terms in this box, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept these notice terms.  It is best to keep this notice to one or two lines.', 'awesome-support' ),
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),				
				
				array(
					'name' => __( 'Terms & Conditions', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'terms_conditions',
					'type'     => 'editor',
					'default'  => '',
					'desc'     => __( 'Terms & conditions are not mandatory. If you add terms, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept your terms', 'awesome-support' ),
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),

				array(
					'name' => __( 'Other', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Default Role For New Users', 'awesome-support' ),
					'id'      => 'new_user_role',
					'type'    => 'text',
					'desc'    => __( 'The role should be the internal WordPress role id such as wpas_user and is case sensitive.  Do not leave this blank!  This role should have the 5 core Awesome Support capabilities in order for users to be able to submit tickets. Check our documentation for more information.', 'awesome-support' ),
					'default' => 'wpas_user'
				),

			)
		),
	);

	return array_merge( $def, $settings );

}