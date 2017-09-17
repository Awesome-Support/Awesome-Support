<?php
/**
 * @package   Awesome Support/Admin/Functions/Addons Installer
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2017 Awesome Support
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check if the user has set his API credentials to connect to the EDD Mini API.
 *
 * @since 4.1
 * @return bool
 */
function wpas_edd_api_credentials_set() {
	$wpas_addons = new WPAS_Addons_Installer();

	return $wpas_addons->load_api_credentials();
}

/**
 * Get the user purchased addons.
 *
 * @since 4.1
 * @return array
 */
function wpas_get_user_purchased_addons() {

	$downloads = get_transient( 'wpas_get_user_purchased_addons' );

	if ( false === $downloads ) {
		$wpas_addons = new WPAS_Addons_Installer();
		$downloads   = (array) $wpas_addons->get_downloads();
		set_transient( 'wpas_get_user_purchased_addons', $downloads, DAY_IN_SECONDS );
	}

	return $downloads;

}
