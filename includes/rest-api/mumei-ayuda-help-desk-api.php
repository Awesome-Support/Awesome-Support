<?php
/**
 * Plugin Name: Ayuda – Help Desk: REST API
 * Plugin URI: https://mumei.io/
 * Description: REST API add-on for Ayuda – Help Desk
 * Author: Mumei
 * Author URI: https://mumei.io/
 * Version: 1.0.4
 * Text Domain: ayuda-help-desk
 * Domain Path: /languages/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Modified by Mumei (2026)
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'plugins_loaded',  'mumei_ayuda_rest_api_load' );

/**
 * Load REST API
 *
 * @return void
 */
function mumei_ayuda_rest_api_load() {
	/// Check if user has REST API addon
	if ( mumei_ayuda_rest_api_addon_check() ) {

		add_action( 'admin_notices', 'mumei_ayuda_rest_api_addon_notice' );

	} else {

		// Add options page
		if ( is_admin() ) {

			/**
			 * Load the rest-api settings
			 */
			require_once( MUMEI_AYUDA_PATH . 'includes/rest-api/includes/settings-api.php' );

		}

		if ( boolval( mumei_ayuda_get_option( 'enable_rest_api' ) ) ) {
			// Load API Class
			require( __DIR__ . '/includes/api.php' );
			// Initialize
			return mumei_ayuda_api();
		}

	}

}

/**
 * Notify user
 *
 * @return void
 */
function mumei_ayuda_rest_api_addon_notice() {
	printf( '<div class="notice notice-error"><p>Ayuda – Help Desk: ' . esc_html__( 'REST API Addon is now part of Ayuda – Help Desk core. Please deactivate and delete the REST API addon.', 'ayuda-help-desk' ) . '</p></div>' );
}


/**
 * Check if user has REST API addon
 *
 * @return boolean
 */
function mumei_ayuda_rest_api_addon_check() {

	if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

	foreach( get_plugins() as $basename => $data ) {
		if ( stristr( $basename, 'mumei-ayuda-help-desk-api.php' ) ) {
			return true;
		}
	}

	return false;
}