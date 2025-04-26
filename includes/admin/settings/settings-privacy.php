<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_privacy', 5, 1 );
/**
 * Add plugin core settings for privacy options.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_privacy( $def ) {

	$settings = array(
		'privacy' => array(
			'name'    => __( 'Privacy', 'awesome-support' ),
			'options' => array(

				array(
					'name' => __( 'Privacy', 'awesome-support' ),
					'desc'    => __( 'Control how the PRIVACY button appears to the user and the options available with it', 'awesome-support' ),					
					'type' => 'heading',
				),
				
				array(
					'name'    => __( 'Show Button', 'awesome-support' ),
					'id'      => 'privacy_show_button',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the PRIVACY button on the front-end where users can request deletion of their data, export their data and modify their opt-in selections', 'awesome-support' ),
					'default' => true,
				),								
				array(
					'name'    => __( 'Button Label', 'awesome-support' ),
					'id'      => 'privacy_button_label',
					'type'    => 'text',
					'desc'    => __( 'Enter the label for the Privacy button in My Tickets page.', 'awesome-support' ),
					'default' => 'Privacy'
				),
				array(
					'name'    => __( 'Privacy Popup Heading', 'awesome-support' ),
					'id'      => 'privacy_popup_header',
					'type'    => 'editor',
					'desc'    => __( 'Enter content that the user should see at the top of the popup when the Privacy button in the My Tickets page is clicked.', 'awesome-support' ),
					'default' => ''
				),
				array(
					'name'    => __( 'Privacy Popup Footer', 'awesome-support' ),
					'id'      => 'privacy_popup_footer',
					'type'    => 'editor',
					'desc'    => __( 'Enter content that the user should see at the bottom of the popup when the Privacy button in the My Tickets page is clicked.', 'awesome-support' ),
					'default' => ''
				),
				
				array(
					'name' => __( 'Tabs', 'awesome-support' ),
					'desc' => __( 'Enable or disable the tabs that will show in the privacy popup', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Show Consent Tab', 'awesome-support' ),
					'id'      => 'privacy_show_consent_tab',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the tab that allows users to modify their existing consent', 'awesome-support' ),
					'default' => true,
				),
				array(
					'name'    => __( 'Show Delete Existing Data Tab', 'awesome-support' ),
					'id'      => 'privacy_show_delete_data_tab',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the tab that allows users to request deletion of their data', 'awesome-support' ),
					'default' => true,
				),
				array(
					'name'    => __( 'Show Export Data Tab', 'awesome-support' ),
					'id'      => 'privacy_show_export_data_tab',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the tab that allows users to request export of their data', 'awesome-support' ),
					'default' => true,
				),
				array(
					'name'    => __( 'Show Export Tab', 'awesome-support' ),
					'id'      => 'privacy_show_export_tab',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the tab that allows users to export their ticket data', 'awesome-support' ),
					'default' => true,
				),

				array(
					'name' => __( 'Delete Existing Data', 'awesome-support' ),
					'desc' => __( 'Options when allowing users to request deletion of their data', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Subject', 'awesome-support' ),
					'id'      => 'delete_existing_data_subject',
					'type'    => 'text',
					'desc'    => __( 'This is the subject of the ticket that will be submitted when the user opens a ticket to request deletion of their data', 'awesome-support' ),
					'default' => __( 'Official Request: Please Delete My Existing Data ("Right To Be Forgotten").', 'awesome-support' )
				),
				array(
					'name'    => __( 'Anonymize instead of Delete', 'awesome-support' ),
					'id'      => 'anonymize_existing_data',
					'type'    => 'checkbox',
					'desc'    => __( 'Option to anonymize the user data instead of deleting it - this helps to keep statistics accurate.  New anonymous users will be created and attached to existing tickets.', 'awesome-support' ),
				),
				array(
					'name'    => __( 'User Can Submit Additional Information', 'awesome-support' ),
					'id'      => 'delete_existing_data_add_information',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to allow the user to enter a longer description related to their request to delete their data?', 'awesome-support' )
				),

				array(
					'name' => __( 'Export All Data', 'awesome-support' ),
					'desc' => __( 'Options when allowing users to request export of their tickets data', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Subject', 'awesome-support' ),
					'id'      => 'export_existing_data_subject',
					'type'    => 'text',
					'desc'    => __( 'This is the subject of the ticket that will be submitted when the user opens a ticket to request export of their data', 'awesome-support' ),
					'default' => __( 'Official Request: Please Export My Existing Data.', 'awesome-support' )
				),
				array(
					'name'    => __( 'User Can Submit Additional Information', 'awesome-support' ),
					'id'      => 'export_existing_data_add_information',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to allow the user to enter a longer description related to their request to export their data?', 'awesome-support' )
				),
				
				array(
					'name' => __( 'Periodic Anonymization', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'Use the options in this section to enable a periodic cron process to anonymize or delete old tickets', 'awesome-support' ),
				),
				array(
					'name'    => __( 'Enable Periodic Anonymization', 'awesome-support' ),
					'id'      => 'anonymize_cron_job',
					'type'    => 'checkbox',
					'desc'    => __( 'Enable the periodic process that will anonymize or delete old tickets', 'awesome-support' ),
					'default' => false
				),	
				array(
					'name'    => __( 'Cron Job Interval', 'awesome-support' ),
					'id'      => 'anonymize_cronjob_trigger_time',
					'type'    => 'number',
					'desc'    => __( 'How often should we run the anonymization process?  The value entered here is in minutes - 1440 minutes is one day.', 'awesome-support' ),
					'max'	  => 10000,
					'default' => 1440
				),
				array(
					'name'    => __( 'Ticket Age', 'awesome-support' ),
					'id'      => 'anonymize_cronjob_max_age',
					'type'    => 'number',
					'desc'    => __( 'How old should tickets be before they are anonymized or deleted?  Enter a value in days - default is 180 days or approximately six months.', 'awesome-support' ),
					'default' => 180
				),
				array(
					'name'    => __( 'Delete Tickets', 'awesome-support' ),
					'id'      => 'anonymize_cronjob_delete_tickets',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Delete tickets instead of anonymizing them.  WARNING: Deleted tickets CANNOT be recovered!', 'awesome-support' )
				),
				
				array(
					'name' => __( 'Tickets to Anonymize', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Closed Tickets', 'awesome-support' ),
					'id'      => 'closed_tickets_anonmyize',
					'type'    => 'checkbox',
					'desc'    => __( 'Anonymize CLOSED tickets', 'awesome-support' ),
					'default' => true
				),	
				array(
					'name'    => __( 'Open Tickets', 'awesome-support' ),
					'id'      => 'open_tickets_anonmyize',
					'type'    => 'checkbox',
					'desc'    => __( 'Anonymize OPEN tickets', 'awesome-support' ),
					'default' => false
				),
				
				array(
					'name' => __( 'Anonymized User ID Options', 'awesome-support' ),
					'type' => 'heading',
					'desc' => __( 'When anonymizing tickets a random user is created to replace the existing user.  These options control how you create the anonymized user names', 'awesome-support' ),
				),

				array(
					'name'    => __( 'How should we create the anonymized user?', 'awesome-support' ),
					'id'      => 'anonmyize_user_creation_method',
					'type'    => 'radio',
					'default' => '1',
					'options' => array(
						'1' => __( 'Default', 'awesome-support' ),
						'2' => __( 'Random one-way hash', 'awesome-support' ),
						'3' => __( 'Use the user id below', 'awesome-support' ),
					)
				),
				array(
					'name'    => __( 'Anonymized User ID', 'awesome-support' ),
					'id'      => 'anonmyize_user_id',
					'type'    => 'text',
					'desc'    => __( 'Use this user id for all anonymized tickets. Warning: This should be a VALID user id otherwise you will corrupt your database.  This only applies if the above option is set to USE THE USER ID BELOW.', 'awesome-support' ),
				),				

			)
		),
	);

	return array_merge( $def, apply_filters('wpas_settings_privacy', $settings )  );

}