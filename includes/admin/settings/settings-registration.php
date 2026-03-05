<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_settings_registration', 5, 1 );
/**
 * Add plugin core settings for registration.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function mumei_ayuda_core_settings_registration( $def ) {

	$user_registration = boolval( get_option( 'users_can_register' ) );
	$registration_lbl  = ( true === $user_registration ) ? _x( 'allowed', 'User registration is allowed', 'ayuda-help-desk' ) : _x( 'not allowed', 'User registration is not allowed', 'ayuda-help-desk' );

	// translators: %s is the registration text.
	$desc = __( 'Allow users to register on the support page. This setting can be enabled even though the WordPress setting is disabled. Currently, registrations are %s by WordPress.', 'ayuda-help-desk' );
	
	$settings = array(
		'registration' => array(
			'name'    => __( 'Registration', 'ayuda-help-desk' ),
			'options' => array(
				array(
					'name' => __( 'Registration', 'ayuda-help-desk' ),
					'type' => 'heading',
				),

				array(
					'name'    => __( 'Allow Registrations', 'ayuda-help-desk' ),
					'id'      => 'allow_registrations',
					'type'    => 'radio',
					'desc'    => sprintf( $desc,  "<strong>$registration_lbl</strong>" ),
					'default' => 'allow',
					'options' => array(
						'allow'           => __( 'Allow registrations', 'ayuda-help-desk' ),
						'disallow'        => __( 'Disallow registrations', 'ayuda-help-desk' ),
						'disallow_silent' => __( 'Disallow registrations without notice (just show the login form)', 'ayuda-help-desk' ),
						'moderated'		  => __( 'Moderated registrations', 'ayuda-help-desk' ),
					)
				),
				
				array(
					'name' => __( 'Registration Alerts', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __( 'Who should receive the standard WordPress notifications when a new user registers on the site?', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Who Should Receive New User Notifications?', 'ayuda-help-desk' ),
					'id'      => 'reg_notify_users',
					'type'    => 'radio',
					'options' => array( 'none' => __('No One','ayuda-help-desk'), 'user' => __('Only The Customer','ayuda-help-desk'), 'admin' => __('Only The Site Admin','ayuda-help-desk'), 'both' => __('Customer and Admin','ayuda-help-desk') ),
					'default' => 'both,',
				),
				
				array(
					'name' => __( 'User Names', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __( 'How should user names be determined?', 'ayuda-help-desk' ),
				),												
				array(
					'name'    => __( 'User Name Construction', 'ayuda-help-desk' ),
					'id'      => 'reg_user_name_construction',
					'type'    => 'radio',
					'default' => 6,
					'desc'    => __( 'How should we construct the user name?', 'ayuda-help-desk' ),
					'options' => array(
						'0' => __( 'Default - Uses the first part of email address', 'ayuda-help-desk' ),
						'1' => __( 'Use the entire email address', 'ayuda-help-desk' ),
						'2' => __( 'Use a random number', 'ayuda-help-desk' ),
						'3' => __( 'Use a GUID', 'ayuda-help-desk' ),
						'4' => __( 'Use the first name', 'ayuda-help-desk' ),
						'5' => __( 'Use the last name', 'ayuda-help-desk' ),
						'6' => __( 'Concatenate the first and last name', 'ayuda-help-desk' ),
					),
				),				
				
				array(
					'name' => __( 'Registration Field Descriptions', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __( 'Add a description to each of the registration fields which will appear beneath the field. You can use this to add GDPR related information indicating what each field is used for.', 'ayuda-help-desk' ),
				),
				array(
					'name'     => __( 'First name description', 'ayuda-help-desk' ),
					'id'       => 'reg_first_name_desc',
					'type'     => 'text',
					'default'  => '',
				),
				array(
					'name'     => __( 'Last name description', 'ayuda-help-desk' ),
					'id'       => 'reg_last_name_desc',
					'type'     => 'text',
					'default'  => '',
				),
				array(
					'name'     => __( 'Email address description', 'ayuda-help-desk' ),
					'id'       => 'reg_email_desc',
					'type'     => 'text',
					'default'  => '',
				),				

				array(
					'name' => __( 'Other', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Default Role For New Users', 'ayuda-help-desk' ),
					'id'      => 'new_user_role',
					'type'    => 'text',
					'desc'    => __( 'The role should be the internal WordPress role id such as mumei_ayuda_user and is case sensitive.  Do not leave this blank!  This role should have the 5 core Ayuda – Help Desk capabilities in order for users to be able to submit tickets. Check our documentation for more information.', 'ayuda-help-desk' ),
					'default' => 'mumei_ayuda_user'
				),	

			)
		),
	);
	
	$gdpr_consent_options = array( 
			array(
			'name' => __( 'GDPR Notice #1', 'ayuda-help-desk' ),
			'type' => 'heading',
			'desc' => __( 'The GDPR requires that you provide explicit notice about what you will do with the data you collect from users. This section allows you to describe how any personal data collected will be used and require consent from the user before they can register.', 'ayuda-help-desk' ),
			),
			array(
			'name'     => __( 'GDPR Notice: Short Description', 'ayuda-help-desk' ),
			'id'       => 'gdpr_notice_short_desc_01',
			'type'     => 'text',
			'default'  => '',
			'desc'     => __( 'If you fill this in, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t tick the checkbox.  It is best to keep this brief - eg: Receive Emails? or Join Email List?', 'ayuda-help-desk' ),
			),				
			array(
			'name'     => __( 'GDPR Notice: Long Description', 'ayuda-help-desk' ),
			'id'       => 'gdpr_notice_long_desc_01',
			'type'     => 'editor',
			'default'  => '',
			'desc'     => __( 'If you add notice terms in this box, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept these notice terms.  It is best to keep this notice to one or two lines.', 'ayuda-help-desk' ),
			'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
			),
			array(
			    'name'    => __( 'Mandatory', 'ayuda-help-desk' ),
			    'id'      => "gdpr_notice_mandatory_01",
			    'type'    => 'checkbox',
			    'default' => true,
			    'desc'    => __( 'Does the user need to check this option before being able to register?', 'ayuda-help-desk' )
			),
			array(
			    'name'    => __( 'Can Opt Out', 'ayuda-help-desk' ),
			    'id'      => "gdpr_notice_opt_out_ok_01",
			    'type'    => 'checkbox',
			    'default' => false,
			    'desc'    => __( 'Is this an option that the user can opt-out from after granting consent?', 'ayuda-help-desk' )
			),				

			array(
			'name' => __( 'GDPR Notice #2', 'ayuda-help-desk' ),
			'type' => 'heading',
			'desc' => __( 'If personal data will be used in any additional context add that here. It is best to keep this brief.', 'ayuda-help-desk' ),
			),
			array(
			'name'     => __( 'GDPR Notice: Short Description', 'ayuda-help-desk' ),
			'id'       => 'gdpr_notice_short_desc_02',
			'type'     => 'text',
			'default'  => '',
			'desc'     => __( 'If you fill this in, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t tick the checkbox.  It is best to keep this brief - eg: Receive Emails? or Join Email List?', 'ayuda-help-desk' ),
			),				
			array(
			'name'     => __( 'GDPR Notice: Long Description', 'ayuda-help-desk' ),
			'id'       => 'gdpr_notice_long_desc_02',
			'type'     => 'editor',
			'default'  => '',
			'desc'     => __( 'If you add notice terms in this box, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept these notice terms.  It is best to keep this notice to one or two lines.', 'ayuda-help-desk' ),
			'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
			),
			array(
			    'name'    => __( 'Mandatory', 'ayuda-help-desk' ),
			    'id'      => "gdpr_notice_mandatory_02",
			    'type'    => 'checkbox',
			    'default' => true,
			    'desc'    => __( 'Does the user need to check this option before being able to register?', 'ayuda-help-desk' )
			),
			array(
			    'name'    => __( 'Can Opt Out', 'ayuda-help-desk' ),
			    'id'      => "gdpr_notice_opt_out_ok_02",
			    'type'    => 'checkbox',
			    'default' => false,
			    'desc'    => __( 'Is this an option that the user can opt-out from after granting consent?', 'ayuda-help-desk' )
			),								


			array(
			'name' => __( 'GDPR Notice #3', 'ayuda-help-desk' ),
			'type' => 'heading',
			'desc' => __( 'If personal data will be used in any additional context add that here. It is best to keep this brief.', 'ayuda-help-desk' ),
			),
			array(
			'name'     => __( 'GDPR Notice: Short Description', 'ayuda-help-desk' ),
			'id'       => 'gdpr_notice_short_desc_03',
			'type'     => 'text',
			'default'  => '',
			'desc'     => __( 'If you fill this in, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t tick the checkbox.  It is best to keep this brief - eg: Receive Emails? or Join Email List?', 'ayuda-help-desk' ),
			),				
			array(
			'name'     => __( 'GDPR Notice: Long Description', 'ayuda-help-desk' ),
			'id'       => 'gdpr_notice_long_desc_03',
			'type'     => 'editor',
			'default'  => '',
			'desc'     => __( 'If you add notice terms in this box, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept these notice terms.  It is best to keep this notice to one or two lines.', 'ayuda-help-desk' ),
			'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
			),
			array(
			    'name'    => __( 'Mandatory', 'ayuda-help-desk' ),
			    'id'      => "gdpr_notice_mandatory_03",
			    'type'    => 'checkbox',
			    'default' => true,
			    'desc'    => __( 'Does the user need to check this option before being able to register?', 'ayuda-help-desk' )
			),
			array(
			    'name'    => __( 'Can Opt Out', 'ayuda-help-desk' ),
			    'id'      => "gdpr_notice_opt_out_ok_03",
			    'type'    => 'checkbox',
			    'default' => false,
			    'desc'    => __( 'Is this an option that the user can opt-out from after granting consent?', 'ayuda-help-desk' )
			),								


			array(
			'name' => __( 'Terms & Conditions', 'ayuda-help-desk' ),
			'type' => 'heading',
			),
			array(
			'name'     => __( 'Content', 'ayuda-help-desk' ),
			'id'       => 'terms_conditions',
			'type'     => 'editor',
			'default'  => '',
			'desc'     => __( 'Terms & conditions are not mandatory. If you add terms, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept your terms', 'ayuda-help-desk' ),
			'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
			),	
		);

	$gdpr_consent_options = apply_filters('mumei_ayuda_gdpr_consents', $gdpr_consent_options );

	foreach ($gdpr_consent_options as $key => $gdpr_option ) {
		$settings['registration']['options'][] = $gdpr_option;
	}

	return array_merge( $def, apply_filters('mumei_ayuda_settings_registration', $settings )  );

}
