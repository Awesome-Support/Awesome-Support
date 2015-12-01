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
 * Version:           3.2.8
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

/**
 * Get an instance of the plugin
 */
add_action( 'plugins_loaded', array( 'Awesome_Support_Old', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Load theme's functions
 *----------------------------------------------------------------------------*/


if ( ! class_exists( 'Awesome_Support' ) ):

	/**
	 * Main Awesome Support class
	 *
	 * This class is the one and only instance of the plugin. It is used
	 * to load the core and all its components.
	 *
	 * @since 3.2.5
	 */
	final class Awesome_Support {

		/**
		 * @var Awesome_Support Holds the unique instance of Awesome Support
		 * @since 3.2.5
		 */
		private static $instance;

		/**
		 * Possible error message.
		 *
		 * @since 3.3
		 * @var null|WP_Error
		 */
		protected $error = null;

		/**
		 * Minimum version of WordPress required ot run the plugin
		 *
		 * @since 3.3
		 * @var string
		 */
		public $wordpress_version_required = '3.8';

		/**
		 * Required version of PHP.
		 *
		 * Follow WordPress latest requirements and require
		 * PHP version 5.2 at least.
		 *
		 * @since 3.3
		 * @var string
		 */
		public $php_version_required = '5.2';

		/**
		 * Holds the WPAS_Custom_Fields instance
		 *
		 * @since 3.3
		 * @var WPAS_Custom_Fields
		 */
		public $custom_fields;

		/**
		 * List of registered addons
		 *
		 * @since 3.3
		 * @var array
		 */
		public $addons = array();

		/**
		 * Admin Notices object
		 *
		 * @var object AS_Admin_Notices
		 * @since 3.1.5
		 */
		public $admin_notices;

		/**
		 * Session object
		 *
		 * @since 3.2.6
		 * @var WPAS_Session $session
		 */
		public $session;

		/**
		 * Instantiate and return the unique Awesome Support object
		 *
		 * @since     3.2.5
		 * @return object Awesome_Support Unique instance of Awesome Support
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Awesome_Support ) ) {
				self::$instance = new Awesome_Support;
				self::$instance->init();
			}

			return self::$instance;

		}

		/**
		 * Instantiate the plugin
		 *
		 * @since 3.3
		 * @return void
		 */
		private function init() {

			// First of all we need the constants
			self::$instance->setup_constants();

			// Make sure the WordPress version is recent enough
			if ( ! self::$instance->is_version_compatible() ) {
				self::$instance->add_error( sprintf( __( 'Awesome Support requires WordPress version %s or above. Please update WordPress to run this plugin.', 'awesome-support' ), self::$instance->wordpress_version_required ) );
			}

			// Make sure we have a version of PHP that's not too old
			if ( ! self::$instance->is_php_version_enough() ) {
				self::$instance->add_error( sprintf( __( 'Awesome Support requires PHP version %s or above. Read more information about <a %s>how you can update</a>.', 'awesome-support' ), self::$instance->wordpress_version_required, 'a href="http://www.wpupdatephp.com/update/" target="_blank"' ) );
			}

			// Check that the vendor directory is present
			if ( ! self::$instance->dependencies_loaded() ) {
				self::$instance->add_error( sprintf( __( 'Awesome Support dependencies are missing. The plugin can’t be loaded properly. Please run %s before anything else. If you don’t know what this is you should <a href="%s" class="thickbox">install the production version</a> of this plugin instead.', 'awesome-support' ), '<a href="https://getcomposer.org/doc/00-intro.md#using-composer" target="_blank"><code>composer install</code></a>', esc_url( add_query_arg( array(
						'tab'       => 'plugin-information',
						'plugin'    => 'awesome-support',
						'TB_iframe' => 'true',
						'width'     => '772',
						'height'    => '935'
				), admin_url( 'plugin-install.php' ) ) ) ) );
			}

			// If we have any error, don't load the plugin
			if ( is_a( self::$instance->error, 'WP_Error' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'display_error' ), 10, 0 );
				return;
			}

			self::$instance->includes();
			self::$instance->session = new WPAS_Session();
			self::$instance->custom_fields = new WPAS_Custom_Fields;

			if ( is_admin() ) {
				self::$instance->includes_admin();
				self::$instance->admin_notices = new AS_Admin_Notices();
			}

			add_action( 'plugins_loaded', array( self::$instance, 'load_plugin_textdomain' ) );
			add_action( 'init', array( self::$instance, 'load_theme_functions' ) );

		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 3.2.5
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'awesome-support' ), '3.2.5' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 3.2.5
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'awesome-support' ), '3.2.5' );
		}

		/**
		 * Setup all plugin constants
		 *
		 * @since 3.2.5
		 * @return void
		 */
		private function setup_constants() {
			define( 'WPAS_VERSION',           '3.2.8' );
			define( 'WPAS_DB_VERSION',        '1' );
			define( 'WPAS_URL',               trailingslashit( plugin_dir_url( __FILE__ ) ) );
			define( 'WPAS_PATH',              trailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'WPAS_ROOT',              trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
			define( 'WPAS_TEMPLATE_PATH',     'awesome-support/' );
			define( 'WPAS_ADMIN_ASSETS_URL',  trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/admin/' ) );
			define( 'WPAS_ADMIN_ASSETS_PATH', trailingslashit( plugin_dir_path( __FILE__ ) . 'assets/admin/' ) );
			define( 'WPAS_PLUGIN_FILE',       __FILE__ );
		}

		/**
		 * Check if plugin dependencies are present.
		 *
		 * @since  3.0.2
		 * @return boolean True of dependencies are here, false otherwise
		 */
		private function dependencies_loaded() {

			if ( ! is_dir( WPAS_PATH . 'vendor' ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Check if the core version is compatible with this addon.
		 *
		 * @since  3.3
		 * @return boolean
		 */
		private function is_version_compatible() {

			if ( empty( self::$instance->wordpress_version_required ) ) {
				return true;
			}

			if ( version_compare( get_bloginfo( 'version' ), self::$instance->wordpress_version_required, '<' ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Check if the version of PHP is compatible with this addon.
		 *
		 * @since  3.3
		 * @return boolean
		 */
		private function is_php_version_enough() {

			/**
			 * No version set, we assume everything is fine.
			 */
			if ( empty( self::$instance->php_version_required ) ) {
				return true;
			}

			if ( version_compare( phpversion(), self::$instance->php_version_required, '<' ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Add error.
		 *
		 * Add a new error to the WP_Error object
		 * and create the object if it doesn't exist yet.
		 *
		 * @since  3.3
		 *
		 * @param string $message Error message to add
		 *
		 * @return void
		 */
		private function add_error( $message ) {

			if ( ! is_object( $this->error ) || ! is_a( $this->error, 'WP_Error' ) ) {
				$this->error = new WP_Error();
			}

			$this->error->add( 'addon_error', $message );

		}

		/**
		 * Display error.
		 *
		 * Get all the error messages and display them
		 * in the admin notices.
		 *
		 * @since  3.3
		 * @return void
		 */
		public function display_error() {

			if ( ! is_a( $this->error, 'WP_Error' ) ) {
				return;
			}

			$message = self::$instance->error->get_error_messages(); ?>

			<div class="error">
				<p>
					<?php
					if ( count( $message ) > 1 ) {
						echo '<ul>';
						foreach ( $message as $msg ) {
							echo "<li>$msg</li>";
						}
						echo '</li>';
					} else {
						echo $message[0];
					}
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Include all files used sitewide
		 *
		 * @since 3.2.5
		 * @return void
		 */
		private function includes() {

			require( WPAS_PATH . 'includes/functions-fallback.php' );
			require( WPAS_PATH . 'includes/class-logger.php' );
			require( WPAS_PATH . 'includes/class-awesome-support.php' );
			require( WPAS_PATH . 'includes/integrations/loader.php' );
			require( WPAS_PATH . 'includes/scripts.php' );
			require( WPAS_PATH . 'includes/shortcodes/shortcode-tickets.php' ); // The plugin main shortcodes
			require( WPAS_PATH . 'includes/shortcodes/shortcode-submit.php' );  // The plugin main shortcode-submit
			require( WPAS_PATH . 'includes/file-uploader/class-file-uploader.php' );
			require( WPAS_PATH . 'includes/class-mailgun-email-check.php' );
			require( WPAS_PATH . 'includes/custom-fields/class-custom-field.php' );
			require( WPAS_PATH . 'includes/custom-fields/class-custom-fields.php' );
			require( WPAS_PATH . 'includes/custom-fields/functions-custom-fields.php' );   // Submission form related functions
			require( WPAS_PATH . 'includes/functions-actions.php' );            // All the functions related to opening a ticket and submitting replies
			require( WPAS_PATH . 'includes/functions-post.php' );            // All the functions related to opening a ticket and submitting replies
			require( WPAS_PATH . 'includes/functions-user.php' );            // Everything related to user login, registration and capabilities
			require( WPAS_PATH . 'includes/functions-addons.php' );          // Addons functions and autoloader
			require( WPAS_PATH . 'includes/functions-deprecated.php' );      // Load deprecated functions
			require( WPAS_PATH . 'includes/class-log-history.php' );         // Logging class
			require( WPAS_PATH . 'includes/class-email-notifications.php' ); // E-mail notification class
			require( WPAS_PATH . 'includes/functions-general.php' );         // Functions that are used both in back-end and front-end
			require( WPAS_PATH . 'includes/functions-error.php' );           // Error handling
			require( WPAS_PATH . 'includes/functions-notification.php' );    // Notification handling
			require( WPAS_PATH . 'includes/functions-templating.php' );      // Templating function
			require( WPAS_PATH . 'includes/functions-post-type.php' );           // Register post types and related functions
			require( WPAS_PATH . 'includes/class-product-sync.php' );        // Keep the product taxonomy in sync with e-commerce products
			require( WPAS_PATH . 'includes/class-gist.php' );                // Add oEmbed support for Gists
			require( WPAS_PATH . 'includes/class-wpas-editor-ajax.php' );    // Helper class to load a wp_editor instance via Ajax
			require( WPAS_PATH . 'includes/class-agent.php' );               // Support agent class
			require( WPAS_PATH . 'includes/class-wpas-session.php' );
			require( WPAS_PATH . 'includes/install.php' );

		}

		/**
		 * Include all files used in admin only
		 *
		 * @since 3.2.5
		 * @return void
		 */
		private function includes_admin() {

			require( WPAS_PATH . 'includes/admin/functions-notices.php' );

			/* Load main admin class */
			require( WPAS_PATH . 'includes/admin/class-admin.php' );
			add_action( 'plugins_loaded', array( 'Awesome_Support_Admin', 'get_instance' ) );

			/**
			 * Add link ot settings tab
			 */
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( 'Awesome_Support_Admin', 'settings_page_link' ) );

		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @return boolean True if the language file was loaded, false otherwise
		 * @since    1.0.0
		 */
		public function load_plugin_textdomain() {

			$lang_dir  = WPAS_ROOT . 'languages/';
			$land_path = WPAS_PATH . 'languages/';
			$locale    = apply_filters( 'plugin_locale', get_locale(), 'awesome-support' );
			$mofile    = "awesome-support-$locale.mo";

			if ( file_exists( $land_path . $mofile ) ) {
				$language = load_textdomain( 'awesome-support', $land_path . $mofile );
			} else {
				$language = load_plugin_textdomain( 'awesome-support', false, $lang_dir );
			}

			return $language;

		}

		/**
		 * Load Awesome Support's theme functions if any
		 *
		 * @since 3.2.0
		 * @return void
		 */
		public function load_theme_functions() {
			wpas_get_template( 'functions' );
		}

	}

endif;

/**
 * The main function responsible for returning the unique Awesome Support instance
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 3.1.5
 * @return object Awesome_Support
 */
function WPAS() {
	return Awesome_Support::instance();
}

// Get Awesome Support Running
WPAS();