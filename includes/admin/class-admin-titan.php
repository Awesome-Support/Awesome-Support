<?php
/**
 * Titan Framework.
 *
 * @package   Admin/Titan
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

class WPAS_Titan {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_titan_framework' ), 12 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     3.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load Titan Framework.
	 *
	 * @link   http://www.titanframework.net/embedding-titan-framework-in-your-project/
	 * @since  3.0.0
	 */
	public function load_titan_framework() {

		/*
		 * When using the embedded framework, use it only if the framework
		 * plugin isn't activated.
		 */
		 
		// Don't do anything when we're activating a plugin to prevent errors
		// on redeclaring Titan classes
		if ( ! empty( $_GET['action'] ) && ! empty( $_GET['plugin'] ) ) {
		    if ( $_GET['action'] == 'activate' ) {
		        return;
		    }
		}

		// Check if the framework plugin is activated
		$useEmbeddedFramework = true;
		$activePlugins = get_option('active_plugins');
		if ( is_array( $activePlugins ) ) {
		    foreach ( $activePlugins as $plugin ) {
		        if ( is_string( $plugin ) ) {
		            if ( stripos( $plugin, '/titan-framework.php' ) !== false ) {
		                $useEmbeddedFramework = false;
		                break;
		            }
		        }
		    }
		}

		// Use the embedded Titan Framework
		if ( $useEmbeddedFramework && ! class_exists( 'TitanFramework' ) ) {
		    require_once( WPAS_PATH . 'vendor/gambitph/titan-framework/titan-framework.php' );
		}
		 
		/*
		 * Start your Titan code below
		 */
		$titan = TitanFramework::getInstance( 'wpas' );

		$settings = $titan->createAdminPanel( array(
				'name'       => __( 'Settings', 'wpas' ),
				'parent'     => 'edit.php?post_type=ticket',
				'capability' => 'settings_tickets'
			)
		);

		/**
		 * Get plugin core options
		 * 
		 * @var (array)
		 * @see  admin/includes/settings.php
		 */
		$options = apply_filters( 'wpas_plugin_settings', array() );

		/* Parse options */
		foreach ( $options as $tab => $content ) {

			/* Add a new tab */
			$tab = $settings->createTab( array(
				'name'  => $content['name'],
				'title' => isset( $content['title'] ) ? $content['title'] : $content['name'],
				'id'    => $tab
				)
			);

			/* Add all options to current tab */
			foreach( $content['options'] as $option ) {
				$tab->createOption( $option );
			}

			$tab->createOption( array( 'type' => 'save', ) );
			
		}

	}

}