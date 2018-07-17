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
					'desc'    => __( 'Option to anonymize the user data instead of deleting it to keep record.', 'awesome-support' ),
				),
				array(
					'name'    => __( 'User Can Submit Additional Information', 'awesome-support' ),
					'id'      => 'delete_existing_data_add_information',
					'type'    => 'checkbox',
					'default' => true,
					'desc'    => __( 'Do you want to allow the user to enter a longer description related to their request to delete their data?', 'awesome-support' )
				),

			)
		),
	);

	return array_merge( $def, $settings );

}