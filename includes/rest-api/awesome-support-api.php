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
			add_filter( 'wpas_plugin_settings', 'wpas_rest_api_add_settings_options' );
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
 * Add REST API tab to Awesome Support Settings
 * 
 * @param  array $defaults Array of existing settings
 *
 * @return array Updated settings
 */
function wpas_rest_api_add_settings_options ( $defaults ) {

	$settings  = array();

	$settings['rest-api'] = array(
		'name'    => __( 'REST API', 'awesome-support' ),
		'options' => array(
			array(
				'name'    => __( 'Enable REST API', 'awesome-support' ),
				'id'      => 'enable_rest_api',
				'type'    => 'checkbox',
				'default' => true,
				'desc'    => __( 'Enable Awesome Support REST API', 'awesome-support' ),
			)
		),
	);

	return array_merge ( $defaults, $settings );

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

	foreach( get_plugins() as $basename => $data ) {
		if ( stristr( $basename, 'awesome-support-api.php' ) ) {
			return true;
		}
	}

	return false;
}