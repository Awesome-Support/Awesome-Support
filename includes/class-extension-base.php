<?php
/**
 * @package   Awesome Support Extension Base Class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


abstract class WPAS_Extension_Base {

	/**
	 * ID of the item.
	 *
	 * The item ID must match the post ID on the e-commerce site.
	 * Using the item ID instead of its name has the huge advantage of
	 * allowing changes in the item name.
	 *
	 * If the ID is not set the class will fall back on the plugin name instead.
	 *
	 * @since 0.1.3
	 * @var int
	 */
	protected $item_id;

	/**
	 * Required version of the core.
	 *
	 * The minimum version of the core that's required
	 * to properly run this addon. If the minimum version
	 * requirement isn't met an error message is displayed
	 * and the addon isn't registered.
	 *
	 * @since  0.1.0
	 * @var    string
	 */
	protected $version_required = '3.2.5';

	/**
	 * Required version of PHP.
	 *
	 * Follow WordPress latest requirements and require
	 * PHP version 5.4 at least.
	 * 
	 * @var string
	 */
	protected $php_version_required = '5.6';

	/**
	 * Plugin slug.
	 *
	 * @since  0.1.0
	 * @var    string
	 */
	protected $slug = '';

	
	/**
	 * Instance of the addon itself.
	 *
	 * @since  0.1.0
	 * @var    object
	 */
	public $addon = null;

	/**
	 * Possible error message.
	 * 
	 * @var null|WP_Error
	 */
	protected $error = null;
	
	
	/**
	 * Addon text domain for retrieving translated strings
	 * 
	 * @var string 
	 */
	protected $text_domain;
	
	
	/**
	 * Short unique id
	 * 
	 * @var string
	 */
	protected $uid = '';
	
	/**
	 * Addon version
	 * 
	 * @var string
	 */
	protected $version = '1.0.0';
	
	/**
	 * Addon file path
	 * 
	 * @var string
	 */
	protected $addon_file;
	
	/**
	 * Addon directory url 
	 * 
	 * @var string
	 */
	protected $addon_url;
	
	/**
	 * Addon directory path
	 * 
	 * @var string
	 */
	protected $addon_path;
	
	/**
	 *	Addon directory relative path
	 * 
	 * @var string
	 */
	protected $addon_root;
	
	/**
	 * Addon name
	 * 
	 * @var string
	 */
	protected $name;
	
	
	public function __construct() {
		
		$this->addon_file = $this->get_addon_path();
		
		$this->name = $this->get_addon_name();
		
		$this->declare_constants();
		
		$this->init();
	}
	

	/**
	 * Return an instance of this class.
	 *
	 * @since     3.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		
		$addon_class = get_called_class();
		
		if ( null == $addon_class::$instance ) {
			$addon_class::$instance = new $addon_class;
		}

		return $addon_class::$instance;
	}

	/**
	 * Return an instance of the addon.
	 *
	 * @since  0.1.0
	 * @return object
	 */
	public function scope() {
		return $this->addon;
	}

	/**
	 * Return Path of addon base file
	 * 
	 * @return string
	 */
	protected static function get_addon_path() {
		$reflector = new ReflectionClass( get_called_class() );
		
		return  $reflector->getFileName();
	}
	
	
	/**
	 * Declare addon constants
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function declare_constants() {
		
		
		if( $this->uid ) {
			
			$this->addon_url  = trailingslashit( plugin_dir_url( $this->addon_file ) ) ;
			$this->addon_path = trailingslashit( plugin_dir_path( $this->addon_file ) ) ;
			$this->addon_root = trailingslashit( dirname( plugin_basename( $this->addon_file ) ) );
			
			
			define( "WPAS_{$this->uid}_VERSION", $this->version );
			define( "WPAS_{$this->uid}_URL",     $this->addon_url );
			define( "WPAS_{$this->uid}_PATH",    $this->addon_path );
			define( "WPAS_{$this->uid}_ROOT",    $this->addon_root );
			
		}
	}

	/**
	 * Activate the plugin.
	 *
	 * The activation method just checks if the main plugin
	 * Awesome Support is installed (active or inactive) on the site.
	 * If not, the addon installation is aborted and an error message is displayed.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public static function activate() {
		
		if ( ! class_exists( 'Awesome_Support' ) ) {
			deactivate_plugins( basename( self::get_addon_path() ) );
			wp_die(
				sprintf( __( 'You need Awesome Support to activate this addon. Please <a href="%s" target="_blank">install Awesome Support</a> before continuing.', 'awesome-support' ), esc_url( 'http://getawesomesupport.com/?utm_source=internal&utm_medium=addon_loader&utm_campaign=Addons' ) )
			);
		}

	}

	/**
	 * Initialize the addon.
	 *
	 * This method is the one running the checks and
	 * registering the addon to the core.
	 *
	 * @since  0.1.0
	 * @return boolean Whether or not the addon was registered
	 */
	public function init() {

		$plugin_name = $this->get_addon_name( false );
		
		
		if ( ! $this->is_core_active() ) {
			$this->add_error( sprintf( __( '%s requires Awesome Support to be active. Please activate the core plugin first.', 'awesome-support' ), $plugin_name ) );
		}

		if ( ! $this->is_php_version_enough() ) {
			$this->add_error( sprintf( __( 'Unfortunately, %s can not run on PHP versions older than %s. Read more information about <a href="%s" target="_blank">how you can update</a>.', 'awesome-support' ), $plugin_name, $this->php_version_required, esc_url( 'http://www.wpupdatephp.com/update/' ) ) );
		}

		if ( ! $this->is_version_compatible() ) {
			$this->add_error( sprintf( __( '%s requires Awesome Support version %s or greater. Please update the core plugin first.', 'awesome-support' ), $plugin_name, $this->version_required ) );
		}
		
		if ( ! $this->dependencies_available() ) {
			$this->add_error( sprintf( __( '%s requires some dependencies that aren&#039;t loaded. Please contact support.', 'awesome-support' ), $plugin_name ) );
		}

		// Load the plugin translation.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ), 15 );
		
		if ( is_a( $this->error, 'WP_Error' ) ) {
			add_action( 'admin_notices', array( $this, 'display_error' ), 10, 0 );
			add_action( 'admin_init',    array( $this, 'deactivate' ),    10, 0 );
			return false;
		}

		/**
		 * Add the addon license field
		 */
		if ( is_admin() ) {

			// Add the license admin notice
			$this->add_license_notice();

			add_filter( 'wpas_addons_licenses', array( $this, 'addon_license' ),       10, 1 );
			add_filter( 'plugin_row_meta',      array( $this, 'license_notice_meta' ), 10, 4 );
		}
		
		$this->after_init();

		/**
		 * Register the addon
		 */
		wpas_register_addon( $this->slug, array( $this, 'load' ) );
		
		register_deactivation_hook( $this->addon_file, array( $this, 'deactivate' ) ) ;
		

		return true;

	}
	
	/**
	 * Call this method after addon successfully initialized
	 * 
	 * @return type
	 */
	protected function after_init() {
		
	}

	/**
	 * Get the plugin data.
	 *
	 * @since  0.1.0
	 * @param  string $data Plugin data to retrieve
	 * @return string       Data value
	 */
	protected function plugin_data( $data ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			
			$site_url = get_site_url() . '/';

			if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN && 'http://' === substr( $site_url, 0, 7 ) ) {
				$site_url = str_replace( 'http://', 'https://', $site_url );
			}

			$admin_path = str_replace( $site_url, ABSPATH, get_admin_url() );

			require_once( $admin_path . 'includes/plugin.php' );
			
		}
		
		
		$plugin = get_plugin_data( $this->addon_file, false, false );
		
		
		if ( array_key_exists( $data, $plugin ) ) {
			return $plugin[$data];
		} else {
			return '';
		}

	}

	/**
	 * Check if core is active.
	 *
	 * Checks if the core plugin is listed in the acitve
	 * plugins in the WordPress database.
	 *
	 * @since  0.1.0
	 * @return boolean Whether or not the core is active
	 */
	protected function is_core_active() {
		if ( in_array( 'awesome-support/awesome-support.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the core version is compatible with this addon.
	 *
	 * @since  0.1.0
	 * @return boolean
	 */
	protected function is_version_compatible() {

		/**
		 * Return true if the core is not active so that this message won't show.
		 * We already have the error saying the plugin is disabled, no need to add this one.
		 */
		if ( ! $this->is_core_active() ) {
			return true;
		}

		if ( empty( $this->version_required ) ) {
			return true;
		}

		if ( ! defined( 'WPAS_VERSION' ) ) {
			return false;
		}

		if ( version_compare( WPAS_VERSION, $this->version_required, '<' ) ) {
			return false;
		}

		return true;

	}
	
	
	/**
	 * Load vendor dependencies
	 * 
	 * @return boolean
	 */
	protected function dependencies_available() {
		return true;
	}

	/**
	 * Check if the version of PHP is compatible with this addon.
	 *
	 * @since  0.1.0
	 * @return boolean
	 */
	protected function is_php_version_enough() {

		/**
		 * No version set, we assume everything is fine.
		 */
		if ( empty( $this->php_version_required ) ) {
			return true;
		}

		if ( version_compare( phpversion(), $this->php_version_required, '<' ) ) {
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
	 * @since  0.1.0
	 * @param string $message Error message to add
	 * @return void
	 */
	public function add_error( $message ) {

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
	 * @since  0.1.0
	 * @return void
	 */
	public function display_error() {

		if ( ! is_a( $this->error, 'WP_Error' ) ) {
			return;
		}

		$message = $this->error->get_error_messages(); ?>
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
	 * Deactivate the addon.
	 *
	 * If the requirements aren't met we try to
	 * deactivate the addon completely.
	 * 
	 * @return void
	 */
	public function deactivate() {
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( basename( $this->addon_file ) );
			
			
			if( method_exists( $this, 'after_deactivated') ) {
				$this->after_deactivated();
			}
		}
		
	}
	
	
	/**
	 * Get addon name
	 * 
	 * @param boolean $trim_as
	 * 
	 * @return boolean
	 */
	protected function get_addon_name( $trim_as = true ) {
		
		$plugin_name = $this->plugin_data( 'Name' );
		
		if( $trim_as ) {
			$plugin_name = trim( str_replace( 'Awesome Support:', '', $plugin_name ) ); // Remove the Awesome Support prefix from the addon name
		}
		
		return $plugin_name;
		
	}

	/**
	 * Add license option.
	 *
	 * @since  0.1.0
	 * @param  array $licenses List of addons licenses
	 * @return array           Updated list of licenses
	 */
	public function addon_license( $licenses ) {

		$licenses[] = array(
			'name'      => $this->name,
			'id'        => "license_{$this->slug}",
			'type'      => 'edd-license',
			'default'   => '',
			'server'    => esc_url( 'https://getawesomesupport.com' ),
			'item_name' => $this->name,
			'item_id'   => $this->item_id,
			'file'      => $this->addon_file
		);

		return $licenses;
	}

	/**
	 * Display notice if user didn't set his Envato license code
	 *
	 * @since 0.1.4
	 * @return void
	 */
	public function add_license_notice() {

		/**
		 * We only want to display the notice to the site admin.
		 */
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		$license = wpas_get_option( "license_{$this->slug}", '' );

		/**
		 * Do not show the notice if the license key has already been entered.
		 */
		if ( ! empty( $license ) ) {
			return;
		}

		$link = wpas_get_settings_page_url( 'licenses' );
		WPAS()->admin_notices->add_notice( 'error', "license_{$this->slug}", sprintf( __( 'Please <a href="%s">fill-in your product license</a> now. If you don\'t, your copy of <strong>%s</strong> will <strong>never be updated</strong>.', 'awesome-support' ), $link, $this->get_addon_name( false ) ) );

	}

	/**
	 * Add license warning in the plugin meta row
	 *
	 * @since 0.1.0
	 *
	 * @param array  $plugin_meta The current plugin meta row
	 * @param string $plugin_file The plugin file path
	 *
	 * @return array Updated plugin meta
	 */
	public function license_notice_meta( $plugin_meta, $plugin_file ) {

		$license   = wpas_get_option( "license_{$this->slug}", '' );

		if( ! empty( $license ) ) {
			return $plugin_meta;
		}

		$license_page = wpas_get_settings_page_url( 'licenses' );

		if ( plugin_basename( $this->addon_file ) === $plugin_file ) {
			$plugin_meta[] = '<strong>' . sprintf( __( 'You must fill-in your product license in order to get future plugin updates. <a href="%s">Click here to do it</a>.', 'awesome-support' ), $license_page ) . '</strong>';
		}
		
		return $plugin_meta;
	}

	/**
	 * Load the addon.
	 *
	 * Include all necessary files and instantiate the addon.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function load() {
		// Load the addon here.
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
	 * @since   1.0.4
	 * @return boolean True if the language file was loaded, false otherwise
	 */
	public function load_plugin_textdomain() {

		$lang_dir       = $this->addon_root . 'languages/';
		$lang_path      = $this->addon_path . 'languages/';
		$locale         = apply_filters( 'plugin_locale', get_locale(), $this->text_domain );
		$mofile         = "{$this->text_domain}-{$locale}.mo";
		$glotpress_file = $this->addon_path . $mofile;
		
		
		// Look for the GlotPress language pack first of all
		if ( file_exists( $glotpress_file ) ) {
			$language = load_textdomain( $this->text_domain, $glotpress_file );
		} elseif ( file_exists( $lang_path . $mofile ) ) {
			$language = load_textdomain( $this->text_domain, $lang_path . $mofile );
		} else {
			$language = load_plugin_textdomain( $this->text_domain, false, $lang_dir );
		}

		return $language;

	}
	
	/**
	 * Return addon item id
	 * 
	 * @return int
	 */
	public function getItemId() {
		return $this->item_id;
	}
	
	/**
	 * Set addon item id
	 * 
	 * @param int $item_id
	 */
	public function setItemId( $item_id ) {
		$this->item_id = $item_id;
	}
	
	/**
	 * Return required version of core
	 * 
	 * @return float
	 */
	public function getVersionRequired() {
		return $this->version_required;
	}
	
	/**
	 * Set required version of core
	 * 
	 * @param float $version_required
	 */
	public function setVersionRequired( $version_required ) {
		$this->version_required = $version_required;
	}
	
	/**
	 * Return required version of php
	 * 
	 * @return float
	 */
	public function getPhpVersionRequired() {
		return $this->php_version_required;
	}
	
	/**
	 * Set required version of php
	 * 
	 * @param float $php_version_required
	 */
	public function setPhpVersionRequired( $php_version_required ) {
		$this->php_version_required = $php_version_required;
	}
	
	/**
	 * Return addon slug
	 * 
	 * @return string
	 */
	public function getSlug() {
		return $this->slug;
	}
	
	/**
	 * Set addon slug
	 * 
	 * @param string $slug
	 */
	public function setSlug( $slug ) {
		$this->slug = $slug;
	}
	
	/**
	 * Return text domain for translation
	 * 
	 * @return string
	 */
	public function getTextDomain() {
		return $this->text_domain;
	}
	
	/**
	 * Set text domain for translation
	 * 
	 * @param string $text_domain
	 */
	public function setTextDomain( $text_domain ) {
		$this->text_domain = $text_domain;
	}
	
	/**
	 * Return short unique id
	 * 
	 * @return string
	 */
	public function getUid() {
		return $this->uid;
	}
	
	/**
	 * Set short unique id
	 * 
	 * @param string $uid
	 */
	public function setUid( $uid ) {
		$this->uid = $uid;
	}
	
	/**
	 * Return addon version
	 * 
	 * @return float
	 */
	public function getVersion() {
		return $this->version;
	}
	
	/**
	 * Set addon version
	 * 
	 * @param float $version
	 */
	public function setVersion( $version ) {
		$this->version = $version;
	}
	
	/**
	 * Return addon main file path
	 * 
	 * @return string
	 */
	public function getAddonFile() {
		return $this->addon_file;
	}
	
	/**
	 * Set addon main file path
	 * 
	 * @param string $addon_file
	 */
	public function setAddonFile( $addon_file ) {
		$this->addon_file = $addon_file;
	}
	
	/**
	 * Return url of addon's main directory
	 * 
	 * @return string
	 */
	public function getAddonUrl() {
		return $this->addon_url;
	}
	
	/**
	 * Set url of addon's main directory
	 * 
	 * @param string $addon_url
	 */
	public function setAddonUrl( $addon_url ) {
		$this->addon_url = $addon_url;
	}
	
	/**
	 * Return path of addon's main directory
	 * 
	 * @return string
	 */
	public function getAddonPath() {
		return $this->addon_path;
	}
	
	/**
	 * Set path of addon's main directory
	 * 
	 * @param string $addon_path
	 */
	public function setAddonPath( $addon_path ) {
		$this->addon_path = $addon_path;
	}
	
	/**
	 * Return addon's directory name with trailing slash
	 * 
	 * @return string
	 */
	public function getAddonRoot() {
		return $this->addon_root;
	}
	
	/**
	 * Set addon's directory name with trailing slash
	 * 
	 * @param string $addon_root
	 */
	public function setAddonRoot( $addon_root ) {
		$this->addon_root = $addon_root;
	}
	
	/**
	 * Return addon name
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Set addon name
	 * 
	 * @param string $name
	 */
	public function setName( $name ) {
		$this->name = $name;
	}

}