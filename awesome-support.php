<?php
/**
 * @package   Awesome Support
 * @author    Awesome Support Team <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link       https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 *
 * @wordpress-plugin
 * Plugin Name:       Awesome Support
 * Plugin URI:        https://getawesomesupport.com
 * Description:       Awesome Support is a great ticketing system that will help you improve your customer satisfaction by providing a unique customer support experience.
 * Version:           5.1.1
 * Author:            Awesome Support Team
 * Author URI:         https://getawesomesupport.com
 * Text Domain:       awesome-support
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check to see if we're even allowed to load Awesome Support
$load_allowed = apply_filters( 'wpas_allow_loading', true ) ;
if ( ! $load_allowed ) {
	die;
}

// Check to see if we're allowed to load Awesome Support.
// With this filter we allow other scripts to run by returning instead of 
// dieing.
$soft_load_allowed = apply_filters( 'wpas_allow_soft_loading', true ) ;
if ( ! $soft_load_allowed ) {
	return;
}

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
		public $php_version_required = '5.6';

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
		 * Products synchronization object
		 *
		 * Only used if there is a compatible e-commerce plugin active
		 *
		 * @since 3.3
		 * @var null|WPAS_Product_Sync
		 */
		public $products_sync;

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
				self::$instance->add_error( sprintf( __( 'Awesome Support requires PHP version %s or above. Read more information about <a %s>how you can update</a>.', 'awesome-support' ), self::$instance->php_version_required, 'a href="http://www.wpupdatephp.com/update/" target="_blank"' ) );
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
			self::$instance->maybe_setup();			

			if ( is_admin() ) {

				self::$instance->includes_admin();
				self::$instance->admin_notices = new AS_Admin_Notices();

				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

					add_action( 'plugins_loaded', array( 'WPAS_Upgrade', 'get_instance' ), 11, 0 );
					add_action( 'plugins_loaded', array( 'WPAS_Tickets_List', 'get_instance' ), 11, 0 );
					add_action( 'plugins_loaded', array( 'WPAS_User', 'get_instance' ), 11, 0 );
					add_action( 'plugins_loaded', array( 'WPAS_Titan', 'get_instance' ), 11, 0 );
					add_action( 'plugins_loaded', array( 'WPAS_Help', 'get_instance' ), 11, 0 );
					
					/* User stats tracking from the Wisdom plugin */
					add_action( 'plugins_loaded', array( self::$instance, 'awesome_support_start_plugin_tracking' ), 11, 0);
					add_filter( 'wisdom_notice_text_' . basename( __FILE__, '.php' ), array( self::$instance, 'awesome_support_tracking_notification_text' ) ); 
					add_filter( 'wisdom_delay_notification_' . basename( __FILE__, '.php' ), array( self::$instance, 'awesome_support_tracking_delay_notification' ) );

				}

			}

			add_action( 'plugins_loaded', array( 'WPAS_File_Upload', 'get_instance' ), 11, 0 );
			add_action( 'plugins_loaded', array( self::$instance, 'load_plugin_textdomain' ) );
			add_action( 'init', array( self::$instance, 'load_theme_functions' ) );
			add_action( 'plugins_loaded', array( self::$instance, 'remote_notifications' ), 15, 0 );

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
			define( 'WPAS_VERSION',           '5.1.1' );
			define( 'WPAS_DB_VERSION',        '1' );
			define( 'WPAS_URL',               trailingslashit( plugin_dir_url( __FILE__ ) ) );
			define( 'WPAS_PATH',              trailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'WPAS_ROOT',              trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
			define( 'WPAS_TEMPLATE_PATH',     'awesome-support/' );
			define( 'WPAS_ADMIN_ASSETS_URL',  trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/admin/' ) );
			define( 'WPAS_ADMIN_ASSETS_PATH', trailingslashit( plugin_dir_path( __FILE__ ) . 'assets/admin/' ) );
			define( 'WPAS_PLUGIN_FILE',       __FILE__ );
			define( 'WPAS_PLUGIN_BASENAME',   plugin_basename( __FILE__ ) );
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
			require( WPAS_PATH . 'includes/integrations/ecommerce.php' );
			require( WPAS_PATH . 'includes/scripts.php' );
			require( WPAS_PATH . 'includes/shortcodes/shortcode-tickets.php' );
			require( WPAS_PATH . 'includes/shortcodes/shortcode-submit.php' );
			require( WPAS_PATH . 'includes/file-uploader/class-file-uploader.php' );
			require( WPAS_PATH . 'includes/class-mailgun-email-check.php' );
			require( WPAS_PATH . 'includes/custom-fields/class-custom-field.php' );
			require( WPAS_PATH . 'includes/custom-fields/class-custom-fields.php' );
			require( WPAS_PATH . 'includes/custom-fields/functions-custom-fields.php' );
			require( WPAS_PATH . 'includes/functions-actions.php' );
			require( WPAS_PATH . 'includes/functions-post.php' );
			require( WPAS_PATH . 'includes/functions-user.php' );
			require( WPAS_PATH . 'includes/functions-addons.php' );
			require( WPAS_PATH . 'includes/functions-deprecated.php' );
			require( WPAS_PATH . 'includes/class-log-history.php' );
			require( WPAS_PATH . 'includes/class-email-notifications.php' );
			require( WPAS_PATH . 'includes/functions-general.php' );
			require( WPAS_PATH . 'includes/functions-error.php' );
			require( WPAS_PATH . 'includes/functions-notification.php' );
			require( WPAS_PATH . 'includes/functions-email-notifications.php' );
			require( WPAS_PATH . 'includes/functions-templating.php' );
			require( WPAS_PATH . 'includes/functions-post-type.php' );
			require( WPAS_PATH . 'includes/class-product-sync.php' );
			require( WPAS_PATH . 'includes/class-gist.php' );
			require( WPAS_PATH . 'includes/class-wpas-editor-ajax.php' );
			require( WPAS_PATH . 'includes/class-member-query.php' );
			require( WPAS_PATH . 'includes/class-member.php' );
			require( WPAS_PATH . 'includes/class-member-agent.php' );
			require( WPAS_PATH . 'includes/class-member-user.php' );
			require( WPAS_PATH . 'includes/class-wpas-session.php' );
			require( WPAS_PATH . 'includes/functions-reply.php' );
			require( WPAS_PATH . 'includes/functions-channels.php' );
			require( WPAS_PATH . 'includes/functions-priority.php' );
			require( WPAS_PATH . 'includes/admin/settings/functions-settings.php' );			
			require( WPAS_PATH . 'includes/install.php' );

			/* Simple WooCommerce Integration */
			require( WPAS_PATH . 'includes/integrations/wc-account.php' );

			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

				require( WPAS_PATH . 'includes/functions-admin-bar.php' );

				// Compatibility functions
				require( 'includes/compatibility/sensei.php' );
				require( 'includes/compatibility/acf-pro.php' );
				require( 'includes/compatibility/wpml.php' );
				require( 'includes/compatibility/divi.php' );
				require( 'includes/compatibility/wc.php' );

			}

		}

		/**
		 * Include all files used in admin only
		 *
		 * @since 3.2.5
		 * @return void
		 */
		private function includes_admin() {

			require( WPAS_PATH . 'includes/admin/functions-notices.php' );
			require( WPAS_PATH . 'includes/admin/functions-ajax.php' );
			require( WPAS_PATH . 'includes/admin/functions-log-viewer.php' );
			require( WPAS_PATH . 'includes/admin/functions-admin-ticket-detail-toolbars.php' );
			
			if ( ! class_exists( 'TAV_Remote_Notification_Client' ) ) {
				if ( ! defined( 'WPAS_REMOTE_NOTIFICATIONS_OFF' ) || true !== WPAS_REMOTE_NOTIFICATIONS_OFF ) {
					require( WPAS_PATH . 'includes/class-remote-notification-client.php' );
				}
			}			

			// We don't need all this during Ajax processing
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

				require( WPAS_PATH . 'includes/admin/functions-menu.php' );
				require( WPAS_PATH . 'includes/admin/functions-post.php' );
				require( WPAS_PATH . 'includes/admin/functions-tools.php' );
				require( WPAS_PATH . 'includes/admin/functions-list-table.php' );
				require( WPAS_PATH . 'includes/admin/functions-metaboxes.php' );
				require( WPAS_PATH . 'includes/admin/functions-user-profile.php' );
				require( WPAS_PATH . 'includes/admin/functions-admin-actions.php' );
				require( WPAS_PATH . 'includes/admin/functions-misc.php' );
				require( WPAS_PATH . 'includes/admin/functions-editor.php' );
				require( WPAS_PATH . 'includes/admin/functions-agent-chat.php' );				
				require( WPAS_PATH . 'includes/admin/class-admin-tickets-list.php' );
				require( WPAS_PATH . 'includes/admin/class-admin-user.php' );
				require( WPAS_PATH . 'includes/admin/class-as-admin-setup-wizard.php' );
				require( WPAS_PATH . 'includes/admin/class-admin-titan.php' );
				require( WPAS_PATH . 'includes/admin/class-admin-help.php' );
				require( WPAS_PATH . 'includes/admin/upgrade/class-upgrade.php' );

				/* Load settings files */
				require( WPAS_PATH . 'includes/admin/settings/settings-general.php' );
				require( WPAS_PATH . 'includes/admin/settings/settings-registration.php' );
				require( WPAS_PATH . 'includes/admin/settings/settings-style.php' );
				require( WPAS_PATH . 'includes/admin/settings/settings-notifications.php' );
				require( WPAS_PATH . 'includes/admin/settings/settings-advanced.php' );
				require( WPAS_PATH . 'includes/admin/settings/settings-licenses.php' );
				require( WPAS_PATH . 'includes/admin/settings/settings-products-management.php' );
				require( WPAS_PATH . 'includes/admin/settings/settings-basic-time-tracking.php' );
				require( WPAS_PATH . 'includes/admin/settings/settings-integration.php' );
				
				/* Load Gutenberg related files */
				require( WPAS_PATH . 'includes/admin/gutenberg/functions-gutenberg.php' );
				
				/* Wisdom Tracking */
				require( WPAS_PATH . '/tracking/class-plugin-usage-tracker.php' );
				
			}

		}

		/**
		 * Plugin setup.
		 *
		 * If the plugin has just been installed we need to set a couple of things.
		 * We will automatically create the "special" pages: tickets list and
		 * ticket submission.
		 */
		private function maybe_setup() {

			if ( 'pending' === get_option( 'wpas_setup', false ) ) {
				add_action( 'admin_init', 'wpas_create_pages', 11, 0 );
				add_action( 'admin_init', 'wpas_flush_rewrite_rules', 11, 0 );
			}

			/**
			 * Ask for setup plugin using Setup wizard.
			 * Proceed only if both 'wpas_plugin_setup' & 'wpas_skip_wizard_setup' = false
			 * 'wpas_plugin_setup' will be added at the end of wizard steps
			 * 'wpas_skip_wizard_setup' will be set to true if user choose to skip wizrd from admin notice
			 */
			if ( ! get_option( 'wpas_plugin_setup', false ) && ! get_option( 'wpas_skip_wizard_setup', false ) ) {
				add_action( 'admin_notices', 'wpas_ask_setup_wizard', 1 );
			}

		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * With the introduction of plugins language packs in WordPress loading the textdomain is slightly more complex.
		 *
		 * We now have 3 steps:
		 *
		 * 1. Check for the language pack in the WordPress core directory
		 * 2. Check for the translation file in the plugin's language directory
		 * 3. Fallback to loading the textdomain the classic way
		 *
		 * @since    1.0.0
		 * @return boolean True if the language file was loaded, false otherwise
		 */
		public function load_plugin_textdomain() {

			$lang_dir       = WPAS_ROOT . 'languages/';
			$lang_path      = WPAS_PATH . 'languages/';
			$locale         = apply_filters( 'plugin_locale', get_locale(), 'awesome-support' );
			$mofile         = "awesome-support-$locale.mo";
			$glotpress_file = WP_LANG_DIR . '/plugins/awesome-support/' . $mofile;

			// Look for the GlotPress language pack first of all
			if ( file_exists( $glotpress_file ) ) {
				$language = load_textdomain( 'awesome-support', $glotpress_file );
			} elseif ( file_exists( $lang_path . $mofile ) ) {
				$language = load_textdomain( 'awesome-support', $lang_path . $mofile );
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

		/**
		 * Check for remote notifications.
		 *
		 * Use the Remote Dashboard Notifications plugin
		 * to check for possible notifications from
		 * http://getawesomesupport.com
		 *
		 * @since  3.0.0
		 * @link   https://wordpress.org/plugins/remote-dashboard-notifications/
		 * @return void
		 */
		public function remote_notifications() {
			if ( wpas_is_asadmin() && function_exists( 'rdnc_add_notification' ) && ( ! defined( 'WPAS_REMOTE_NOTIFICATIONS_OFF' ) || true !== WPAS_REMOTE_NOTIFICATIONS_OFF ) ) {
				rdnc_add_notification( 2, '77a8b884c6e778b4', 'https://notifications.getawesomesupport.com' );
			}
		}
		
		/**
		 * Start application statistics tracking
		 *
		 * Use the WISDOM Tracking plugin to track
		 * application usage.
		 * https://wisdomplugin.com/support/#getting-started
		 *
		 * Filter: plugins_loaded
		 *
		 * @since  4.4.0
		 * @return void
		 */		
		public function awesome_support_start_plugin_tracking() {
			$wisdom = new Plugin_Usage_Tracker(
				__FILE__,
				'https://tracking.getawesomesupport.com',
				array(),
				true,
				true,
				1
			);
		}
		
		/**
		 * Application statistics tracking opt-in text
		 *
		 * We use the WISDOM Tracking plugin to track
		 * application usage.  This allows us to set the 
		 * opt-in text shown to the user when they activate the plugin.
		 *
		 * https://wisdomplugin.com/support/#getting-started
		 *
		 * Filter: wisdom_notice_text_
		 *
		 * @param text default notice text.
		 *
		 * @since  4.4.0
		 *
		 * @return text the notice text to be shown to the user
		 */		
		function awesome_support_tracking_notification_text( $notice_text ) {
			$notice_text = __( 'Would you like a discount on your next Awesome Support purchase?  Help us make a better product for you by allowing us to collect some anonymous statistics and adding you to our email list for important updates. We won’t record any sensitive data, only information regarding the WordPress environment and product settings, which we will use to help us make improvements to the product. <b>Tracking is completely optional</b>.  To show our appreciation for helping make Awesome Support better, <b>when you opt-in we will send you a discount code good towards your next purchase</b>. And, opting in would allow us to send you any critical security related information directly - which, in most instances, would be much faster than receiving it from other sources.', 'awesome-support' );
			return $notice_text;
		}
		
		/**
		 * Application statistics tracking opt-in text
		 *
		 * We use the WISDOM Tracking plugin to track
		 * application usage.  This allows us to set the 
		 * time delay before the opt-in notice shows up.
		 *
		 * https://wisdomplugin.com/support/#getting-started
		 *
		 * Filter: wisdom_delay_notification_
		 *
		 * @param text default notice text.
		 *
		 * @since  4.4.0
		 *
		 * @return text the notice text to be shown to the user
		 */		
		function awesome_support_tracking_delay_notification( $delay ) {
			return 900; // 15 mins
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