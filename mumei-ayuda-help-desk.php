<?php
/**
 * @package   Ayuda – Help Desk
 * @author    Mumei <contact@mumei.io>
 * @license   GPL-2.0+
 * @link       https://mumei.io
 * @copyright 2014-2017 AwesomeSupport
 * Modified by Mumei (2026)
 *
 * @wordpress-plugin
 * Plugin Name:       Ayuda – Help Desk
 * Plugin URI:        https://mumei.io
 * Description:       Ayuda – Help Desk is a great ticketing system that will help you improve your customer satisfaction by providing a unique customer support experience.
 * Version:           6.3.7
 * Author:            Mumei
 * Author URI:         https://mumei.io
 * Text Domain:       ayuda-help-desk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check to see if we're even allowed to load Ayuda – Help Desk
$load_allowed = apply_filters( 'mumei_ayuda_allow_loading', true ) ;
if ( ! $load_allowed ) {
	die;
}

// Check to see if we're allowed to load Ayuda – Help Desk.
// With this filter we allow other scripts to run by returning instead of
// dieing.
$soft_load_allowed = apply_filters( 'mumei_ayuda_allow_soft_loading', true ) ;
if ( ! $soft_load_allowed ) {
	return;
}

if ( ! class_exists( 'Mumei_Ayuda_Support' ) ):

	/**
	 * Main Ayuda – Help Desk class
	 *
	 * This class is the one and only instance of the plugin. It is used
	 * to load the core and all its components.
	 *
	 * @since 3.2.5
	 */
	final class Mumei_Ayuda_Support {

		/**
		 * @var Mumei_Ayuda_Support Holds the unique instance of Ayuda – Help Desk
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
		public $php_version_required = '7.1';

		/**
		 * Holds the MUMEI_AYUDA_Custom_Fields instance
		 *
		 * @since 3.3
		 * @var MUMEI_AYUDA_Custom_Fields
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
		 * @var MUMEI_AYUDA_Session $session
		 */
		public $session;

		/**
		 * Products synchronization object
		 *
		 * Only used if there is a compatible e-commerce plugin active
		 *
		 * @since 3.3
		 * @var null|MUMEI_AYUDA_Product_Sync
		 */
		public $products_sync;

		/**
		 * Instantiate and return the unique Ayuda – Help Desk object
		 *
		 * @since     3.2.5
		 * @return object Mumei_Ayuda_Support Unique instance of Ayuda – Help Desk
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Mumei_Ayuda_Support ) ) {
				self::$instance = new Mumei_Ayuda_Support;
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
				// translators: %s is the minimum required WordPress version.
				self::$instance->add_error( sprintf( esc_html__( 'Ayuda – Help Desk requires WordPress version %s or above. Please update WordPress to run this plugin.', 'ayuda-help-desk' ), self::$instance->wordpress_version_required ) );
			}

			// Make sure we have a version of PHP that's not too old
			if ( ! self::$instance->is_php_version_enough() ) {
				// translators: %s is the minimum required PHP version.
				self::$instance->add_error( sprintf( esc_html__( 'Ayuda – Help Desk requires PHP version %s or above. Read more information about ', 'ayuda-help-desk' ).'<a %s>'. esc_html__( 'how you can update', 'ayuda-help-desk' ).'</a>.', self::$instance->php_version_required, 'href="http://www.wpupdatephp.com/update/" target="_blank"' ) );

			}
			
			// Check that the vendor directory is present
			if ( ! self::$instance->dependencies_loaded() ) {
				// translators: %s is the required version.
				self::$instance->add_error( sprintf( esc_html__( 'Ayuda – Help Desk dependencies are missing. The plugin can’t be loaded properly. Please run %s before anything else. If you don’t know what this is you should','ayuda-help-desk').' <a href="%s" class="thickbox">'.esc_html__('install the production version', 'ayuda-help-desk' ).'</a>'.esc_html__(' of this plugin instead.', 'ayuda-help-desk' ), '<a href="https://getcomposer.org/doc/00-intro.md#using-composer" target="_blank"><code>composer install</code></a>', esc_url( add_query_arg( array(
						'tab'       => 'plugin-information',
						'plugin'    => 'ayuda-help-desk',
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
			self::$instance->session = new MUMEI_AYUDA_Session();
			self::$instance->custom_fields = new MUMEI_AYUDA_Custom_Fields;
			self::$instance->maybe_setup();

			if ( is_admin() ) {

				self::$instance->includes_admin();
				self::$instance->admin_notices = new AS_Admin_Notices();

				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

					add_action( 'plugins_loaded', array( 'MUMEI_AYUDA_Upgrade', 'get_instance' ), 11, 0 );
					add_action( 'plugins_loaded', array( 'MUMEI_AYUDA_Tickets_List', 'get_instance' ), 11, 0 );
					add_action( 'plugins_loaded', array( 'MUMEI_AYUDA_User', 'get_instance' ), 11, 0 );
					add_action( 'plugins_loaded', array( 'MUMEI_AYUDA_Gas', 'get_instance' ), 11, 0 );
					add_action( 'plugins_loaded', array( 'MUMEI_AYUDA_Help', 'get_instance' ), 11, 0 );					
				}
				
				/* User stats tracking from the Wisdom plugin */
				add_action( 'plugins_loaded', array( self::$instance, 'awesome_support_start_plugin_tracking' ), 11, 0);
				add_filter( 'wisdom_notice_text_' . basename( __FILE__, '.php' ), array( self::$instance, 'awesome_support_tracking_notification_text' ) );
				add_filter( 'wisdom_delay_notification_' . basename( __FILE__, '.php' ), array( self::$instance, 'awesome_support_tracking_delay_notification' ) );


			}

			add_action( 'plugins_loaded', array( 'MUMEI_AYUDA_File_Upload', 'get_instance' ), 11, 0 );
			add_action( 'plugins_loaded', array( 'MUMEI_AYUDA_Privacy_Option', 'get_instance' ), 11, 0 );
			add_action( 'plugins_loaded', array( 'MUMEI_AYUDA_GDPR_User_Profile', 'get_instance' ), 11, 0 );
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
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'ayuda-help-desk' ), '3.2.5' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 3.2.5
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'ayuda-help-desk' ), '3.2.5' );
		}

		/**
		 * Setup all plugin constants
		 *
		 * @since 3.2.5
		 * @return void
		 */
		private function setup_constants() {
			define( 'MUMEI_AYUDA_VERSION',           '6.3.7' );
			define( 'MUMEI_AYUDA_DB_VERSION',        '1' );
			define( 'MUMEI_AYUDA_URL',               trailingslashit( plugin_dir_url( __FILE__ ) ) );
			define( 'MUMEI_AYUDA_PATH',              trailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'MUMEI_AYUDA_ROOT',              trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
			define( 'MUMEI_AYUDA_TEMPLATE_PATH',     'ayuda-help-desk/' );
			define( 'MUMEI_AYUDA_ADMIN_ASSETS_URL',  trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/admin/' ) );
			define( 'MUMEI_AYUDA_ADMIN_ASSETS_PATH', trailingslashit( plugin_dir_path( __FILE__ ) . 'assets/admin/' ) );
			define( 'MUMEI_AYUDA_PLUGIN_FILE',       __FILE__ );
			define( 'MUMEI_AYUDA_PLUGIN_BASENAME',   plugin_basename( __FILE__ ) );
		}

		/**
		 * Check if plugin dependencies are present.
		 *
		 * @since  3.0.2
		 * @return boolean True of dependencies are here, false otherwise
		 */
		private function dependencies_loaded() {

			if ( ! is_dir( MUMEI_AYUDA_PATH . 'vendor' ) ) {
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
							echo '<li>' . wp_kses_post($msg) . '</li>';
						}
						echo '</ul>';
					} else {
						echo wp_kses_post($message[0]);
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

			require( MUMEI_AYUDA_PATH . 'includes/functions-fallback.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-logger.php' );
			require( MUMEI_AYUDA_PATH . 'includes/integrations/ecommerce.php' );
			require( MUMEI_AYUDA_PATH . 'includes/scripts.php' );
			require( MUMEI_AYUDA_PATH . 'includes/shortcodes/shortcode-tickets.php' );
			require( MUMEI_AYUDA_PATH . 'includes/shortcodes/shortcode-submit.php' );
			require( MUMEI_AYUDA_PATH . 'includes/file-uploader/class-file-uploader.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-mailgun-email-check.php' );
			require( MUMEI_AYUDA_PATH . 'includes/custom-fields/class-custom-field.php' );
			require( MUMEI_AYUDA_PATH . 'includes/custom-fields/class-custom-fields.php' );
			require( MUMEI_AYUDA_PATH . 'includes/custom-fields/functions-custom-fields.php' );
			require( MUMEI_AYUDA_PATH . 'includes/gdpr-integration/gdpr-privacy-options.php' );
			require( MUMEI_AYUDA_PATH . 'includes/gdpr-integration/gdpr-user-profile.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-actions.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-post.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-user.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-addons.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-deprecated.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-log-history.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-email-notifications.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-user-email-notification.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-general.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-error.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-notification.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-email-notifications.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-templating.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-post-type.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-product-sync.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-gist.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-wpas-editor-ajax.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-member-query.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-member.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-member-agent.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-member-user.php' );
			require( MUMEI_AYUDA_PATH . 'includes/class-wpas-session.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-reply.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-channels.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-ticket-type.php' );
			require( MUMEI_AYUDA_PATH . 'includes/functions-priority.php' );
			require( MUMEI_AYUDA_PATH . 'includes/admin/settings/functions-settings.php' );
			require( MUMEI_AYUDA_PATH . 'includes/install.php' );

			/* Composer autoload */
			require( MUMEI_AYUDA_PATH . 'vendor/autoload.php' );

			// GAS Framework
			require( MUMEI_AYUDA_PATH . 'includes/gas-framework/gas-framework.php' );

			/* Load Rest API */
			require( MUMEI_AYUDA_PATH . 'includes/rest-api/mumei-ayuda-help-desk-api.php' );

			/* Simple WooCommerce Integration */
			require( MUMEI_AYUDA_PATH . 'includes/integrations/wc-account.php' );

			/* myCRED Integration */
			require( MUMEI_AYUDA_PATH . 'includes/integrations/my-cred/my-cred.php' );

			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

				require( MUMEI_AYUDA_PATH . 'includes/functions-admin-bar.php' );

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

			require( MUMEI_AYUDA_PATH . 'includes/admin/functions-notices.php' );
			require( MUMEI_AYUDA_PATH . 'includes/admin/functions-ajax.php' );
			require( MUMEI_AYUDA_PATH . 'includes/admin/functions-log-viewer.php' );
			require( MUMEI_AYUDA_PATH . 'includes/admin/functions-admin-ticket-detail-toolbars.php' );
			require( MUMEI_AYUDA_PATH . 'includes/admin/functions-toolbar.php' );

			if ( ! class_exists( 'TAV_Remote_Notification_Client' ) ) {
				if ( ! defined( 'MUMEI_AYUDA_REMOTE_NOTIFICATIONS_OFF' ) || true !== MUMEI_AYUDA_REMOTE_NOTIFICATIONS_OFF ) {
					require( MUMEI_AYUDA_PATH . 'includes/class-remote-notification-client.php' );
				}
			}

			// We don't need all this during Ajax processing
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-menu.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-post.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-tools.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-list-table.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-metaboxes.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-user-profile.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-admin-actions.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-misc.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-editor.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/functions-agent-chat.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/class-admin-tickets-list.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/class-admin-user.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/class-as-admin-setup-wizard.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/class-admin-gas.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/class-admin-help.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/upgrade/class-upgrade.php' );

				/* Load settings files */
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-general.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-registration.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-moderated-registration.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-privacy.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-fields.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-permissions.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-style.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-notifications.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-advanced.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-licenses.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-products-management.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-basic-time-tracking.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-language.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/settings/settings-integration.php' );

				/* Load Gutenberg related files */
				require( MUMEI_AYUDA_PATH . 'includes/admin/gutenberg/functions-gutenberg-post-type.php' );
				require( MUMEI_AYUDA_PATH . 'includes/admin/gutenberg/functions-gutenberg.php' );
			}		
			
			/* Wisdom Tracking */
			require( MUMEI_AYUDA_PATH . '/tracking/class-plugin-usage-tracker.php' );

		}

		/**
		 * Plugin setup.
		 *
		 * If the plugin has just been installed we need to set a couple of things.
		 * We will automatically create the "special" pages: tickets list and
		 * ticket submission.
		 */
		private function maybe_setup() {

			if ( 'pending' === get_option( 'mumei_ayuda_setup', false ) ) {
				add_action( 'admin_init', 'mumei_ayuda_create_pages', 11, 0 );
				add_action( 'admin_init', 'mumei_ayuda_flush_rewrite_rules', 11, 0 );
				add_action( 'admin_init', 'mumei_ayuda_install_default_email_templates', 11, 0 );
			}

			/**
			 * Ask for setup plugin using Setup wizard.
			 * Proceed only if both 'mumei_ayuda_plugin_setup' & 'mumei_ayuda_skip_wizard_setup' = false
			 * 'mumei_ayuda_plugin_setup' will be added at the end of wizard steps
			 * 'mumei_ayuda_skip_wizard_setup' will be set to true if user choose to skip wizrd from admin notice
			 */
			if ( ! get_option( 'mumei_ayuda_plugin_setup', false ) && ! get_option( 'mumei_ayuda_skip_wizard_setup', false ) ) {
				add_action( 'admin_notices', 'mumei_ayuda_ask_setup_wizard', 1 );
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

			$lang_dir       = MUMEI_AYUDA_ROOT . 'languages/';
			$lang_path      = MUMEI_AYUDA_PATH . 'languages/';
			$locale         = apply_filters( 'plugin_locale', get_locale(), 'ayuda-help-desk' );
			$mofile         = "awesome-support-$locale.mo";
			$glotpress_file = WP_LANG_DIR . '/plugins/ayuda-help-desk/' . $mofile;

			// Look for the GlotPress language pack first of all
			if ( file_exists( $glotpress_file ) ) {
				$language = load_textdomain( 'ayuda-help-desk', $glotpress_file );
			} elseif ( file_exists( $lang_path . $mofile ) ) {
				$language = load_textdomain( 'ayuda-help-desk', $lang_path . $mofile );
			} else {
				$language = load_plugin_textdomain( 'ayuda-help-desk', false, $lang_dir );
			}

			return $language;

		}

		/**
		 * Load Ayuda – Help Desk's theme functions if any
		 *
		 * @since 3.2.0
		 * @return void
		 */
		public function load_theme_functions() {
			mumei_ayuda_get_template( 'functions' );
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
			if ( mumei_ayuda_is_asadmin() && function_exists( 'rdnc_add_notification' ) && ( ! defined( 'MUMEI_AYUDA_REMOTE_NOTIFICATIONS_OFF' ) || true !== MUMEI_AYUDA_REMOTE_NOTIFICATIONS_OFF ) ) {
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
			$notice_text = '<b>'.esc_html__( 'Would you like a discount on your next Ayuda – Help Desk purchase?', 'ayuda-help-desk').'</b>'.  esc_html__('Help us make a better product for you by allowing us to collect some system statistics and adding you to our email list for important updates. We won’t record any sensitive data, only information regarding the WordPress environment and product settings, which we will use to help us make improvements to the product. ', 'ayuda-help-desk').'<b>'.  esc_html__('Tracking is completely optional', 'ayuda-help-desk').'</b>'. esc_html__('.  To show our appreciation for helping make Ayuda – Help Desk better,', 'ayuda-help-desk').' <b>'. esc_html__('when you opt-in we will send you a discount code good towards your next purchase', 'ayuda-help-desk').'</b>'. esc_html__('. And, opting in would allow us to send you any critical security related information directly - which, in most instances, would be much faster than receiving it from other sources.', 'ayuda-help-desk' );
			$notice_text = $notice_text . sprintf(  ' <a %s>'. esc_html__('Find out more about what data we collect and how its used.'  , 'ayuda-help-desk').'</a> <a %s>'.esc_html__('View our privacy policy.','ayuda-help-desk').'</a>', 'a href="https://getawesomesupport.com/legal/tracking-statistics/" target="_blank"', 'a href="https://getawesomesupport.com/legal/privacy-policy/" target="_blank"' );
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
 * The main function responsible for returning the unique Ayuda – Help Desk instance
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 3.1.5
 * @return object Mumei_Ayuda_Support
 */
function WPAS() {
	return Mumei_Ayuda_Support::instance();
}

// Get Ayuda – Help Desk Running
WPAS();