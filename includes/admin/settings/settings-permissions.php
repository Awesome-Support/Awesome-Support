<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_permissions', 5, 1 );
/**
 * Add plugin core permissions settings.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_permissions( $def ) {

	$settings = array(
		'permissions' => array(
			'name'    => __( 'Permissions', 'awesome-support' ),
			'options' => array(
				array(
					'name' => __( 'Permissions', 'awesome-support' ),
					'type' => 'heading',
				),

				array(
					'type' => 'Note',
					'desc'    => sprintf( __( 'Basic user and agent permissions are handled by WordPress ROLES and CAPABILITIES. <br />
											   When this plugin was installed, we automatically included roles for agents and users named SUPPORT AGENT and SUPPORT USER.  <br />
											   You can use these when you set up new users and they are automatically assigned to users that register on our login page.  <br />
											   BUT, if you have existing users with existing roles that need to open tickets please read <b><u><a %s>this article</a></b></u> on our website. <br />
											   The rest of this page helps you to control the appearance of some items on your ticket screens.', 'awesome-support' ), 'href="https://getawesomesupport.com/documentation/awesome-support/admin-handling-existing-users-after-installation/" target="_blank" ' )
				),

				array(
					'name' => __( 'Ticket List Tabs', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Control whether certain tabs are visible at the top of the admin ticket list', 'awesome-support' ),					
				),
				array(
					'name'    => __( 'Show Documentation Tab', 'awesome-support' ),
					'id'      => 'ticket_list_show_doc_tab',
					'type'    => 'checkbox',
					'default' => true
				),
				array(
					'name'    => __( 'Show Bulk Actions Tab', 'awesome-support' ),
					'id'      => 'ticket_list_show_bulk_actions_tab',
					'type'    => 'checkbox',
					'default' => true
				),
				array(
					'name'    => __( 'Show Preferences Tab', 'awesome-support' ),
					'id'      => 'ticket_list_show_preferences_tab',
					'type'    => 'checkbox',
					'default' => true
				),
				
				array(
					'name' => __( 'Ticket Templates', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Ticket templates are tickets that can be copied by add-ons to make new tickets. The options in this section offers control of this feature.', 'awesome-support' ),					
				),
				array(
					'name'    => __( 'Enable Ticket Templates', 'awesome-support' ),
					'id'      => 'enable_ticket_templates',
					'type'    => 'checkbox',
					'default' => false
				),
				array(
					'name'    => __( 'Show Flag In Ticket List', 'awesome-support' ),
					'id'      => 'show_ticket_template_in_ticket_list',
					'type'    => 'checkbox',
					'default' => false
				),				
				
				array(
					'name' => __( 'Ticket Details Tabs And Metaboxes', 'awesome-support' ),
					'type' => 'heading',
					'desc'    => __( 'Control who can view certain ticket tabs on the ticket detail screen in wp-admin', 'awesome-support' ),					
				),
				array(
					'name'    => __( 'Roles That Are NOT Allowed Access To The Custom Fields Tab', 'awesome-support' ),
					'id'      => 'hide_cf_tab_roles',
					'type'    => 'text',
					'desc'    => __( 'Enter a comma separated list of roles that should not see the CUSTOM FIELDS tab. Roles should be the internal WordPress role id such as wpas_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
					'default' => ''
				),
				array(
					'name'    => __( 'Roles That Are NOT Allowed Access To The Additional Interested Parties Tab', 'awesome-support' ),
					'id'      => 'hide_ai_tab_roles',
					'type'    => 'text',
					'desc'    => __( 'Enter a comma separated list of roles that should not see the ADDITIONAL INTERESTED PARTIES tab. Roles should be the internal WordPress role id such as wpas_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
					'default' => ''
				),
				array(
					'name'    => __( 'Roles That Are NOT Allowed Access To The Tags Metabox', 'awesome-support' ),
					'id'      => 'hide_tags_mb_roles',
					'type'    => 'text',
					'desc'    => __( 'Enter a comma separated list of roles that should not see the tags metabox. Roles should be the internal WordPress role id such as wpas_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
					'default' => ''
				),

				array(
					'name' => __( 'Editing History', 'awesome-support' ),
					'type' => 'heading',
					'desc' => 'This section allows you to control whether ticket and ticket replies can be edited by agents. Allowing this can distort the ticket history but might be necessary to comply with certain privacy regulations - for example removing user ids, passwords or other sensitive data.',
					'options' => wpas_get_editing_history_options()
				),
			)
		),
	);

	return array_merge( $def, apply_filters('wpas_settings_permission', $settings )  );

}

/**
 * Prepare an array that shows editing history options
 *
 * @since 5.2.0
 * @return array
 */
function wpas_get_editing_history_options() {

	$security = array(
		array(
			'name'    => __( 'Roles That Are NOT Allowed to Edit Ticket Content', 'awesome-support' ),
			'id'      => 'roles_edit_ticket_content',
			'type'    => 'text',
			'desc'    => __( 'Enter a comma separated list of roles that should NOT be able to edit ticket content. Roles should be the internal WordPress role id such as wpas_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
			'default' => '',
		),

		array(
			'name'    => __( 'Allow Agents To Edit Their Own Replies', 'awesome-support' ),
			'id'      => 'agent_edit_own_reply',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like agents to be able to edit their own replies?', 'awesome-support' ),
			'default' => false
		),

		array(
			'name'    => __( 'Roles That Can Edit All Replies', 'awesome-support' ),
			'id'      => 'roles_edit_all_replies',
			'type'    => 'text',
			'desc'    => __( 'Enter a comma separated list of roles that should be able to edit any agent and user reply. Roles should be the internal WordPress role id such as wpas_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
			'default' => '',
		),
		
		array(
			'name'    => __( 'Allow Agents To Delete Their Own Replies', 'awesome-support' ),
			'id'      => 'agent_delete_own_reply',
			'type'    => 'checkbox',
			'desc'    => __( 'Would you like agents to be able to delete their own replies? (FYI: We really do not recommend allowing deletes but you do have the option if you want it!)', 'awesome-support' ),
			'default' => false
		),		
		
		array(
			'name'    => __( 'Roles That Can Delete All Replies', 'awesome-support' ),
			'id'      => 'roles_delete_all_replies',
			'type'    => 'text',
			'desc'    => __( 'Enter a comma separated list of roles that should be able to delete any agent and user reply. Roles should be the internal WordPress role id such as wpas_agent and are case sensitive. There should be no spaces between the commas and role names when entering multiple roles.', 'awesome-support' ),
			'default' => '',
		),

		array(
			'name'    => __( 'Permanently Delete Replies', 'awesome-support' ),
			'id'      => 'permanently_trash_replies',
			'type'    => 'checkbox',
			'desc'    => __( 'Check this to allow replies to be permanently deleted. If you do not check this option, deleted items will end up in the WordPress trash bin which will allow you to see the deletion in-line.  They can be permanently deleted later with most third-party clean-up plugins.', 'awesome-support' ),
			'default' => false
		),
		
		array(
			'name'    => __( 'Log content edits', 'awesome-support' ),
			'id'      => 'log_content_edit_level',
			'type'    => 'radio',
			'desc'    => __( 'What level should edits to ticket content and replies be logged?', 'awesome-support' ),
			'options' => array( 'low' => __( 'Low - only logs whether the ticket or reply was edited. This prevents additional sensitive data from being stored in the log when the intention is to edit out sensitive information.', 'awesome-support' ), 
								'high' => __( 'High - log the original contents before the reply was edited so that a full history could be maintained', 'awesome-support' ) ),
			'default' => 'low'
		),		
		
	);
		
	return $security;
}