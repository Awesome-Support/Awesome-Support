<?php
/**
 * @package   Awesome Support
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 *
 * @wordpress-plugin
 * Plugin Name:       Awesome Support
 * Plugin URI:        http://getawesomesupport.com
 * Description:       Awesome Support is a great ticketing system that will help you improve your customer satisfaction by providing a unique customer support experience.
 * Version:           3.0.0
 * Author:            ThemeAvenue
 * Author URI:        http://themeavenue.net
 * Text Domain:       wpas
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Shortcuts
 *----------------------------------------------------------------------------*/

define( 'WPAS_VERSION',           '3.0.0' );
define( 'WPAS_DB_VERSION',        '1' );
define( 'WPAS_URL',               trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WPAS_PATH',              trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WPAS_TEMPLATE_PATH',     'awesome-support/' );
define( 'WPAS_ADMIN_ASSETS_URL',  trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/admin/' ) );
define( 'WPAS_ADMIN_ASSETS_PATH', trailingslashit( plugin_dir_path( __FILE__ ) . 'assets/admin/' ) );

/*----------------------------------------------------------------------------*
 * Settings
 *----------------------------------------------------------------------------*/

define( 'WPAS_FIELDS_DESC', apply_filters( 'wpas_fields_descriptions', true ) );

/*----------------------------------------------------------------------------*
 * Shared Functionalities
 *----------------------------------------------------------------------------*/

require_once( WPAS_PATH . 'includes/functions-fallback.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-awesome-support.php' );

/**
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Awesome_Support', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Awesome_Support', 'deactivate' ) );

/**
 * Get an instance of the plugin
 */
add_action( 'plugins_loaded', array( 'Awesome_Support', 'get_instance' ) );

/**
 * Load addons.
 *
 * A couple of addons are built in the plugin.
 * We load them here.
 */
require_once( WPAS_PATH . 'includes/addons/custom-fields/class-custom-fields.php' );
require_once( WPAS_PATH . 'includes/addons/file-uploader/class-file-uploader.php' );

/**
 * Call all classes and functions files that are shared
 * through the backend and the frontend. The files only used
 * by the backend or the frontend are loaded
 * by their respective classes.
 */
require_once( WPAS_PATH . 'includes/functions-post.php' );            // All the functions related to opening a ticket and submitting replies
require_once( WPAS_PATH . 'includes/functions-user.php' );            // Everything related to user login, registration and capabilities
require_once( WPAS_PATH . 'includes/class-log-history.php' );         // Logging class
require_once( WPAS_PATH . 'includes/class-email-notifications.php' ); // E-mail notification class
require_once( WPAS_PATH . 'includes/functions-general.php' );         // Functions that are used both in back-end and front-end
require_once( WPAS_PATH . 'includes/functions-custom-fields.php' );   // Submission form related functions
require_once( WPAS_PATH . 'includes/functions-templating.php' );      // Templating function
require_once( WPAS_PATH . 'includes/class-post-type.php' );           // Register post types and related functions

/*----------------------------------------------------------------------------*
 * Public-Facing Only Functionality
 *----------------------------------------------------------------------------*/
if( !is_admin() ) {
	require_once( WPAS_PATH . 'includes/class-notification.php' ); // Load notifications class
	require_once( WPAS_PATH . 'includes/shortcodes/shortcode-tickets.php' ); // The plugin main shortcodes
	require_once( WPAS_PATH . 'includes/shortcodes/shortcode-submit.php' );  // The plugin main shortcode-submit
}

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/**
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() ) {

	/* Load main admin class */
	require_once( WPAS_PATH . 'includes/admin/class-admin.php' );
	add_action( 'plugins_loaded', array( 'Awesome_Support_Admin', 'get_instance' ) );

}

/**
 * Start the session if needed.
 */
if ( !session_id() && !headers_sent() ) {
    session_start();
}