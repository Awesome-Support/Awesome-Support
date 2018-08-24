<?php
/**
 * Plugin Name: Awesome Support: REST API
 * Plugin URI: https://getawesomesupport.com/addons/awesome-support-rest-api/
 * Description: REST API add-on for Awesome Support
 * Author: Awesome Support
 * Author URI: https://getawesomesupport.com/
 * Version: 1.0.4
 * Text Domain: awesome-support-api
 * Domain Path: /languages/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'plugins_loaded',  'wpas_rest_api_load' );

/**
 * Load REST API
 *
 * @return void
 */
function wpas_rest_api_load() {
	/// Check if user has REST API addon
	if ( wpas_rest_api_addon_check() ) {

		add_action( 'admin_notices', 'wpas_rest_api_addon_notice' );

	} else {

		// Add options page
		if ( is_admin() ) {

			/**
			 * Load the rest-api settings
			 */
			require_once( WPAS_PATH . 'includes/rest-api/includes/settings-api.php' );

		}

		if ( boolval( wpas_get_option( 'enable_rest_api' ) ) ) {
			// Load API Class
			require( __DIR__ . '/includes/api.php' );
			// Initialize 
			return wpas_api();
		}

	}

}

/**
 * Notify user 
 *
 * @return void
 */
function wpas_rest_api_addon_notice() {
	printf( '<div class="notice notice-error"><p>Awesome Support: ' . __( 'REST API Addon is now part of Awesome Support core. Please deactivate and delete the REST API addon.', 'awesome-support' ) . '</p></div>' );
}


/**
 * Check if user has REST API addon
 *
 * @return boolean
 */
function wpas_rest_api_addon_check() {

	if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

	foreach( get_plugins() as $basename => $data ) {
		if ( stristr( $basename, 'awesome-support-api.php' ) ) {
			return true;
		}
	}

	return false;
}