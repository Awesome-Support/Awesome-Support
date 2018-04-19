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

/* Execute upgrade functions from 3.3.4 to 5.1.1. */
function wpas_upgrade_334_511() {
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.3.3 to 5.1.1. */
function wpas_upgrade_333_511() {
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.3.2 to 5.1.1. */
function wpas_upgrade_332_511() {
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.3.1 to 5.1.1. */
function wpas_upgrade_331_511() {
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.3.0 to 5.1.1. */
function wpas_upgrade_330_511() {
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.9 to 5.1.1. */
function wpas_upgrade_329_511() {
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.8 to 5.1.1. */
function wpas_upgrade_328_511() {
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.7 to 5.1.1. */
function wpas_upgrade_327_511() {
	wpas_upgrade_328();	
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.6 to 5.1.1. */
function wpas_upgrade_326_511() {
	wpas_upgrade_328();	
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.5 to 5.1.1. */
function wpas_upgrade_325_511() {
	wpas_upgrade_328();	
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.4 to 5.1.1. */
function wpas_upgrade_324_511() {
	wpas_upgrade_328();	
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.3 to 5.1.1. */
function wpas_upgrade_323_511() {
	wpas_upgrade_328();	
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.2 to 5.1.1. */
function wpas_upgrade_322_511() {
	wpas_upgrade_328();	
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.1 to 5.1.1. */
function wpas_upgrade_321_511() {
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.2.0 to 5.1.1. */
function wpas_upgrade_320_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.12 to 5.1.1. */
function wpas_upgrade_3112_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.11 to 5.1.1. */
function wpas_upgrade_3111_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.10 to 5.1.1. */
function wpas_upgrade_3110_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.9 to 5.1.1. */
function wpas_upgrade_319_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.8 to 5.1.1. */
function wpas_upgrade_318_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.7 to 5.1.1. */
function wpas_upgrade_317_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.6 to 5.1.1. */
function wpas_upgrade_316_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.5 to 5.1.1. */
function wpas_upgrade_315_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.4 to 5.1.1. */
function wpas_upgrade_314_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.3 to 5.1.1. */
function wpas_upgrade_313_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.2 to 5.1.1. */
function wpas_upgrade_312_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.1 to 5.1.1. */
function wpas_upgrade_311_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.1.0 to 5.1.1. */
function wpas_upgrade_310_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.0.1 to 5.1.1. */
function wpas_upgrade_301_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/* Execute upgrade functions from 3.0.0 to 5.1.1. */
function wpas_upgrade_300_511() {
	wpas_upgrade_321();
	wpas_upgrade_328();
	wpas_upgrade_333();
	wpas_upgrade_406();
	wpas_update_last_reply();
}

/**
 * Execute upgrade functions from 4.0.x to 5.1.1.
 *
 * Normally we would have just a single function called wpas_upgrade_511.  
 * But because the wpas_update_last_reply function is so intensive it is probably best to make sure
 * it only runs when absolutely necessary instead of running on every upgrade.  
 * Upgrades from 4.1.0 are not necessary for it to run. So, we have to make upgrade routines 
 * for all the 4.x to 4.2 versions - gah.
 * 
 */
function wpas_upgrade_400_511() {
	wpas_update_last_reply();	
}
function wpas_upgrade_401_511() {
	wpas_update_last_reply();	
}
function wpas_upgrade_402_511() {
	wpas_update_last_reply();	
}
function wpas_upgrade_403_511() {
	wpas_update_last_reply();	
}
function wpas_upgrade_404_511() {
	wpas_update_last_reply();	
}
function wpas_upgrade_405_511() {
	wpas_update_last_reply();	
}
function wpas_upgrade_406_511() {
	wpas_update_last_reply();	
}

function wpas_upgrade_421_511() {
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