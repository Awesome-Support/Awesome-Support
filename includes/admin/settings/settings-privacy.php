<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_settings_privacy', 5, 1 );
/**
 * Add plugin core settings for privacy options.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function mumei_ayuda_core_settings_privacy( $def ) {

	$settings = array(
		'privacy' => array(
			'name'    => __( 'Privacy', 'ayuda-help-desk' ),
			'options' => array(

				array(
					'name' => __( 'Privacy', 'ayuda-help-desk' ),
					'desc'    => __( 'Control how the PRIVACY button appears to the user and the options available with it', 'ayuda-help-desk' ),					
					'type' => 'heading',
				),
				
				array(
					'name'    => __( 'Show Button', 'ayuda-help-desk' ),
					'id'      => 'privacy_show_button',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the PRIVACY button on the front-end where users can request deletion of their data, export their data and modify their opt-in selections', 'ayuda-help-desk' ),
					'default' => true,
				),								
				array(
					'name'    => __( 'Button Label', 'ayuda-help-desk' ),
					'id'      => 'privacy_button_label',
					'type'    => 'text',
					'desc'    => __( 'Enter the label for the Privacy button in My Tickets page.', 'ayuda-help-desk' ),
					'default' => 'Privacy'
				),
				array(
					'name'    => __( 'Privacy Popup Heading', 'ayuda-help-desk' ),
					'id'      => 'privacy_popup_header',
					'type'    => 'editor',
					'desc'    => __( 'Enter content that the user should see at the top of the popup when the Privacy button in the My Tickets page is clicked.', 'ayuda-help-desk' ),
					'default' => ''
				),
				array(
					'name'    => __( 'Privacy Popup Footer', 'ayuda-help-desk' ),
					'id'      => 'privacy_popup_footer',
					'type'    => 'editor',
					'desc'    => __( 'Enter content that the user should see at the bottom of the popup when the Privacy button in the My Tickets page is clicked.', 'ayuda-help-desk' ),
					'default' => ''
				),
				
				array(
					'name' => __( 'Tabs', 'ayuda-help-desk' ),
					'desc' => __( 'Enable or disable the tabs that will show in the privacy popup', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Show Consent Tab', 'ayuda-help-desk' ),
					'id'      => 'privacy_show_consent_tab',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the tab that allows users to modify their existing consent', 'ayuda-help-desk' ),
					'default' => true,
				),
				array(
					'name'    => __( 'Show Delete Existing Data Tab', 'ayuda-help-desk' ),
					'id'      => 'privacy_show_delete_data_tab',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the tab that allows users to request deletion of their data', 'ayuda-help-desk' ),
					'default' => true,
				),
				array(
					'name'    => __( 'Show Export Data Tab', 'ayuda-help-desk' ),
					'id'      => 'privacy_show_export_data_tab',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the tab that allows users to request export of their data', 'ayuda-help-desk' ),
					'default' => true,
				),
				array(
					'name'    => __( 'Show Export Tab', 'ayuda-help-desk' ),
					'id'      => 'privacy_show_export_tab',
					'type'    => 'checkbox',
					'desc'    => __( 'Show the tab that allows users to export their ticket data', 'ayuda-help-desk' ),
					'default' => true,
				),

				array(
					'name' => __( 'Delete Existing Data', 'ayuda-help-desk' ),
					'desc' => __( 'Options when allowing users to request deletion of their data', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Subject', 'ayuda-help-desk' ),
					'id'      => 'delete_existing_data_subject',
					'type'    => 'text',
					'desc'    => __( 'This is the subject of the ticket that will be submitted when the user opens a ticket to request deletion of their data', 'ayuda-help-desk' ),
					'default' => __( 'Official Request: Please Delete My Existing Data ("Right To Be Forgotten").', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'Anonymize instead of Delete', 'ayuda-help-desk' ),
					'id'      => 'anonymize_existing_data',
					'type'    => 'checkbox',
					'desc'    => __( 'Option to anonymize the user data instead of deleting it - this helps to keep statistics accurate.  New anonymous users will be created and attached to existing tickets.', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'User Can Submit Additional Information', 'ayuda-help-desk' ),
					'id'      => 'delete_existing_data_add_information',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to allow the user to enter a longer description related to their request to delete their data?', 'ayuda-help-desk' )
				),

				array(
					'name' => __( 'Export All Data', 'ayuda-help-desk' ),
					'desc' => __( 'Options when allowing users to request export of their tickets data', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Subject', 'ayuda-help-desk' ),
					'id'      => 'export_existing_data_subject',
					'type'    => 'text',
					'desc'    => __( 'This is the subject of the ticket that will be submitted when the user opens a ticket to request export of their data', 'ayuda-help-desk' ),
					'default' => __( 'Official Request: Please Export My Existing Data.', 'ayuda-help-desk' )
				),
				array(
					'name'    => __( 'User Can Submit Additional Information', 'ayuda-help-desk' ),
					'id'      => 'export_existing_data_add_information',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to allow the user to enter a longer description related to their request to export their data?', 'ayuda-help-desk' )
				),
				
				array(
					'name' => __( 'Periodic Anonymization', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __( 'Use the options in this section to enable a periodic cron process to anonymize or delete old tickets', 'ayuda-help-desk' ),
				),
				array(
					'name'    => __( 'Enable Periodic Anonymization', 'ayuda-help-desk' ),
					'id'      => 'anonymize_cron_job',
					'type'    => 'checkbox',
					'desc'    => __( 'Enable the periodic process that will anonymize or delete old tickets', 'ayuda-help-desk' ),
					'default' => false
				),	
				array(
					'name'    => __( 'Cron Job Interval', 'ayuda-help-desk' ),
					'id'      => 'anonymize_cronjob_trigger_time',
					'type'    => 'number',
					'desc'    => __( 'How often should we run the anonymization process?  The value entered here is in minutes - 1440 minutes is one day.', 'ayuda-help-desk' ),
					'max'	  => 10000,
					'default' => 1440
				),
				array(
					'name'    => __( 'Ticket Age', 'ayuda-help-desk' ),
					'id'      => 'anonymize_cronjob_max_age',
					'type'    => 'number',
					'desc'    => __( 'How old should tickets be before they are anonymized or deleted?  Enter a value in days - default is 180 days or approximately six months.', 'ayuda-help-desk' ),
					'default' => 180
				),
				array(
					'name'    => __( 'Delete Tickets', 'ayuda-help-desk' ),
					'id'      => 'anonymize_cronjob_delete_tickets',
					'type'    => 'checkbox',
					'default' => false,
					'desc'    => __( 'Delete tickets instead of anonymizing them.  WARNING: Deleted tickets CANNOT be recovered!', 'ayuda-help-desk' )
				),
				
				array(
					'name' => __( 'Tickets to Anonymize', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Closed Tickets', 'ayuda-help-desk' ),
					'id'      => 'closed_tickets_anonmyize',
					'type'    => 'checkbox',
					'desc'    => __( 'Anonymize CLOSED tickets', 'ayuda-help-desk' ),
					'default' => true
				),	
				array(
					'name'    => __( 'Open Tickets', 'ayuda-help-desk' ),
					'id'      => 'open_tickets_anonmyize',
					'type'    => 'checkbox',
					'desc'    => __( 'Anonymize OPEN tickets', 'ayuda-help-desk' ),
					'default' => false
				),
				
				array(
					'name' => __( 'Anonymized User ID Options', 'ayuda-help-desk' ),
					'type' => 'heading',
					'desc' => __( 'When anonymizing tickets a random user is created to replace the existing user.  These options control how you create the anonymized user names', 'ayuda-help-desk' ),
				),

				array(
					'name'    => __( 'How should we create the anonymized user?', 'ayuda-help-desk' ),
					'id'      => 'anonmyize_user_creation_method',
					'type'    => 'radio',
					'default' => '1',
					'options' => array(
						'1' => __( 'Default', 'ayuda-help-desk' ),
						'2' => __( 'Random one-way hash', 'ayuda-help-desk' ),
						'3' => __( 'Use the user id below', 'ayuda-help-desk' ),
					)
				),
				array(
					'name'    => __( 'Anonymized User ID', 'ayuda-help-desk' ),
					'id'      => 'anonmyize_user_id',
					'type'    => 'text',
					'desc'    => __( 'Use this user id for all anonymized tickets. Warning: This should be a VALID user id otherwise you will corrupt your database.  This only applies if the above option is set to USE THE USER ID BELOW.', 'ayuda-help-desk' ),
				),				

			)
		),
	);

	return array_merge( $def, apply_filters('mumei_ayuda_settings_privacy', $settings )  );

}