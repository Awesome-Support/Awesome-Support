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
		update_user_meta( $agent_id, 'wpas_can_be_assigned', 'yes' );
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