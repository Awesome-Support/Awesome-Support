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