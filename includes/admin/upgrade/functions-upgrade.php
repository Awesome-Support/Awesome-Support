<?php
/**
 * All upgrade related functions.
 *
 * @since 3.2.0
 */

/**
 * Upgrade function for version 3.2.0
 *
 * @since 3.2.0
 * @return void
 */
function wpas_upgrade_320() {

	$registrations = (bool) wpas_get_option( 'allow_registrations', true );

	if ( true === $registrations ) {
		wpas_update_option( 'allow_registrations', 'allow' );
	} else {
		wpas_update_option( 'allow_registrations', 'disallow' );
	}

}

/**
 * Upgrade routine for 3.2.1
 *
 * @since 3.2.1
 * @return void
 */
function wpas_upgrade_321() {

	$agents = wpas_list_users( 'edit_ticket' );

	foreach ( $agents as $agent_id => $agent_name ) {
		update_user_option( $agent_id, 'wpas_can_be_assigned', 'yes' );
	}

}

/**
 * Upgrade routine for 3.2.8
 *
 * @since 3.2.8
 * @return void
 */
function wpas_upgrade_328() {

	// Clear agents metas in order to apply the fix for incorrect open tickets counts
	if ( function_exists( 'wpas_clear_agents_metas' ) ) {
		wpas_clear_agents_metas();
	}

}

/**
 * Upgrade routine for 3.3.0
 *
 * @since 3.3.0
 * @return void
 */
function wpas_upgrade_330() {

	// Add default values for e-mail template when client closes own ticket
	wpas_update_option( 'enable_closed_client', get_settings_defaults( 'enable_closed_client' ) );
	wpas_update_option( 'subject_closed_client', get_settings_defaults( 'subject_closed_client' ) );
	wpas_update_option( 'content_closed_client', get_settings_defaults( 'content_closed_client' ) );

}

/**
 * Upgrade function for version 3.3.3
 *
 * A new option was added in this version so we need to set its default value on upgrade.
 *
 * @since 3.3.3
 * @return void
 */
function wpas_upgrade_333() {
	wpas_update_option( 'use_email_template', true, true );
	wpas_update_option( 'email_template_logo', '', true );
	wpas_update_option( 'email_template_header', get_settings_defaults( 'email_template_header' ), true );
	wpas_update_option( 'email_template_footer', get_settings_defaults( 'email_template_footer' ), true );
}

/**
 * Upgrade function for version 4.0.0
 *
 * New capabilities need to be added to all roles.
 *
 * @since 4.0.0
 * @return void
 */
function wpas_upgrade_406() {

	/* Add new capabilities to these roles and all users assigned these roles:
	 *
	 *  WordPress Administrator
	 *  AS Support Manager
	 *
	 */
	$admin_caps = array(
		'view_unassigned_tickets',
		'manage_licenses_for_awesome_support',
		'administer_awesome_support',
		'view_all_tickets',
		'ticket_manage_tags',
		'ticket_edit_tags',
		'ticket_delete_tags',
		'ticket_manage_products',
		'ticket_edit_products',
		'ticket_delete_products',
		'ticket_manage_departments',
		'ticket_edit_departments',
		'ticket_delete_departments',
		'ticket_manage_priorities',
		'ticket_edit_priorities',
		'ticket_delete_priorities',
		'ticket_manage_channels',
		'ticket_edit_channels',
		'ticket_delete_channels'
	);
	
	$agent_caps = array(
		'ticket_manage_tags',
		'ticket_manage_products',
		'ticket_manage_departments',
		'ticket_manage_priorities',
		'ticket_manage_channels'		
	);
	
	$manager_caps = array(
		'ticket_manage_tags',
		'ticket_edit_tags',
		'ticket_delete_tags',
		'ticket_manage_products',
		'ticket_edit_products',
		'ticket_delete_products',
		'ticket_manage_departments',
		'ticket_edit_departments',
		'ticket_delete_departments',
		'ticket_manage_priorities',
		'ticket_edit_priorities',
		'ticket_delete_priorities',
		'ticket_manage_channels',
		'ticket_edit_channels',
		'ticket_delete_channels'
	);
	
	$supportmanager_caps = array(
		'ticket_manage_tags',
		'ticket_edit_tags',
		'ticket_delete_tags',
		'ticket_manage_products',
		'ticket_edit_products',
		'ticket_delete_products',
		'ticket_manage_departments',
		'ticket_edit_departments',
		'ticket_delete_departments',
		'ticket_manage_priorities',
		'ticket_edit_priorities',
		'ticket_delete_priorities',
		'ticket_manage_channels',
		'ticket_edit_channels',
		'ticket_delete_channels'
	);	

	$manager 		= get_role( 'wpas_manager' );  //aka support supervisors
	$supportmanager = get_role( 'wpas_support_manager' );
	$admin   		= get_role( 'administrator' );
	$as_admin  		= get_role( 'as_admin' );	
	$agent	 		= get_role( 'wpas_agent' );
	

	/**
	 * Add new capacities to admin roles
	 */
	foreach ( $admin_caps as $cap ) {

		// Add all the capacities to admin in addition to full WP capacities
		if ( null != $admin )
			$admin->add_cap( $cap );
		
		// Add all the capacities to as_admin in addition to full WP capacities
		if ( null != $as_admin )
			$as_admin->add_cap( $cap );
		

		// Add full plugin capacities to manager in addition to the editor capacities
		if ( null != $manager )
			$manager->add_cap( $cap );

	}
	
	/**
	 * Add certain new capacities to agents
	 */
	foreach ( $agent_caps as $cap ) {
		if ( null != $agent ) {
			$agent->add_cap( $cap );
		}
	}
	
	/**
	 * Add certain new capacities to support managers
	 */
	foreach ( $supportmanager_caps as $cap ) {
		if ( null != $supportmanager ) {
			$supportmanager->add_cap( $cap );
		}
	}
	

	// Now, remove the "view_all_tickets" capability from admin.
	// We need to do this because this capability will override the
	// settings for administrators in TICKETS->SETTINGS->ADVANCED.
	// We don't want to do that!
	if ( null != $admin ) {
		$admin->remove_cap('view_all_tickets');
	}
}

/**
 * Upgrade functions for version 4.1.0
 *
 * Need to update tickets to add values to new fields that were added to the Tickets CPT.
 *
 * @since 4.1.0
 * @return void
 */
function wpas_upgrade_410() {
	wpas_update_last_reply();
}

/**
 * Upgrade function for version 4.4.0
 *
 * New capabilities need to be added to all roles.
 *
 * @since 4.4.0
 * @return void
 */
function wpas_upgrade_440() {
	wpas_upgrade_511();
}

/**
 * Upgrade function for version 5.1.1
 *
 * New capabilities need to be added to all roles.
 *
 * @since 5.1.1
 * @return void
 */
function wpas_upgrade_511() {

	/* Add new capabilities to these roles and all users assigned these roles:
	 *
	 *  WordPress Administrator
	 *  AS Support Manager
	 *
	 */
	$admin_caps = array(
		'assign_ticket_creator'
	);
	
	$agent_caps = array(
		'assign_ticket_creator'
	);
	
	$manager_caps = array(
		'assign_ticket_creator'
	);
	
	$supportmanager_caps = array(
		'assign_ticket_creator'
	);	

	$manager 		= get_role( 'wpas_manager' );  //aka support supervisors
	$supportmanager = get_role( 'wpas_support_manager' );
	$admin   		= get_role( 'administrator' );
	$as_admin		= get_role( 'as_admin' );
	$agent	 		= get_role( 'wpas_agent' );
	

	/**
	 * Add new capacities to admin roles
	 */
	foreach ( $admin_caps as $cap ) {

		// Add all the capacities to admin in addition to full WP capacities
		if ( null != $admin )
			$admin->add_cap( $cap );
		
		// Add all the capacities to as_admin in addition to full WP capacities
		if ( null != $as_admin )
			$as_admin->add_cap( $cap );

		// Add full plugin capacities to manager in addition to the editor capacities
		if ( null != $manager )
			$manager->add_cap( $cap );

	}
	
	/**
	 * Add certain new capacities to agents
	 */
	foreach ( $agent_caps as $cap ) {
		if ( null != $agent ) {
			$agent->add_cap( $cap );
		}
	}
	
	/**
	 * Add certain new capacities to support managers
	 */
	foreach ( $supportmanager_caps as $cap ) {
		if ( null != $supportmanager ) {
			$supportmanager->add_cap( $cap );
		}
	}

}


/**
 * Upgrade function for version 5.2.0
 *
 * New capabilities need to be added to certain roles.
 *
 * @since 5.2.0
 * @return void
 */
function wpas_upgrade_520() {

	/* Add new capabilities to these roles and all users assigned these roles:
	 *
	 *  WordPress Administrator
	 *  AS Support Manager
	 *
	 */
	$admin_caps = array(
		'ticket_manage_privacy'
	);
	
	$agent_caps = array(
		'ticket_manage_privacy'
	);
	
	$manager_caps = array(
		'ticket_manage_privacy'
	);
	
	$supportmanager_caps = array(
		'ticket_manage_privacy'
	);	

	$manager 		= get_role( 'wpas_manager' );  //aka support supervisors
	$supportmanager = get_role( 'wpas_support_manager' );
	$admin   		= get_role( 'administrator' );
	$as_admin		= get_role( 'as_admin' );
	$agent	 		= get_role( 'wpas_agent' );
	

	/**
	 * Add new capacities to admin roles
	 */
	foreach ( $admin_caps as $cap ) {

		// Add all the capacities to admin in addition to full WP capacities
		if ( null != $admin )
			$admin->add_cap( $cap );
		
		// Add all the capacities to as_admin in addition to full WP capacities
		if ( null != $as_admin )
			$as_admin->add_cap( $cap );

		// Add full plugin capacities to manager in addition to the editor capacities
		if ( null != $manager )
			$manager->add_cap( $cap );

	}
	
	/**
	 * Add certain new capacities to agents
	 */
	foreach ( $agent_caps as $cap ) {
		if ( null != $agent ) {
			$agent->add_cap( $cap );
		}
	}
	
	/**
	 * Add certain new capacities to support managers
	 */
	foreach ( $supportmanager_caps as $cap ) {
		if ( null != $supportmanager ) {
			$supportmanager->add_cap( $cap );
		}
	}
	
	
	/**
	 * Add moderated registration settings
	 */
	$morerated_registration_settings = array(
		
		'mr_success_message',
		'moderated_pending_user_role',
		'moderated_activated_user_role',
		
		'enable_moderated_registration_admin_email',
		'moderated_registration_admin_email__subject',
		'moderated_registration_admin_email__content',
		
		'enable_moderated_registration_user_email',
		'moderated_registration_user_email__subject',
		'moderated_registration_user_email__content',
		
		'enable_moderated_registration_approved_user_email',
        'moderated_registration_approved_user_email__subject',
        'moderated_registration_approved_user_email__content',
        
		'enable_moderated_registration_denied_user_email',
		'moderated_registration_denied_user_email__subject',
        'moderated_registration_denied_user_email__content'
	);
	
	
	foreach ( $morerated_registration_settings as $mr_setting_name ) {
		wpas_update_option( $mr_setting_name, get_settings_defaults( $mr_setting_name ), true );
	}

}

/**
 * Upgrade function for version 5.5.0
 *
 * New capabilities need to be added to certain roles.
 *
 * @since 5.5.0
 * @return void
 */
function wpas_upgrade_550() {
	// Run the 520 upgrade option for version 550.
	// The 520 upgrade was the internal upgrade option during testing of the 550 release.
	// Therefore the two routines are the same and there is no reason to write a separate 550 routine.
	// But we do want early 520 adopters to get the later changes to the update routine.  So
	// we create this 550 routine to make sure it runs for early 520 adopters.
	wpas_upgrade_520();
}