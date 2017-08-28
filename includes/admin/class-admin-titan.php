<?php
/**
 * Titan Framework.
 *
 * @package   Admin/Titan
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
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

		add_action( 'wp_loaded', array( $this, 'load_titan_framework' ), 12 );

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
		if( 'activate' === filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING )
			&& ! empty( filter_input( INPUT_GET, 'plugin' ) ) ) {
				return;
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

		$settings = $titan->createContainer( array(
						'type'       => 'admin-page',
						'name'       => __( 'Settings', 'awesome-support' ),
						'title'      => __( 'Awesome Support Settings', 'awesome-support' ),
						'id'         => 'wpas-settings',
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
		$options = wpas_get_settings();

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

				if ( isset( $option['type'] ) && 'heading' === $option['type'] && isset( $option['options'] ) && is_array( $option['options'] ) ) {

					foreach ( $option['options'] as $opt ) {
						$tab->createOption( $opt );
					}

				}


			}

			$tab->createOption( array( 'type' => 'save', ) );
			
		}

	}

}