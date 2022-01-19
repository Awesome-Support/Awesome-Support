<?php
/**
 * Gas Framework.
 *
 * @package   Admin/Gas
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

class WPAS_Gas {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {

		add_action( 'wp_loaded', array( $this, 'load_gas_framework' ), 12 );

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
	 * Load Gas Framework.
	 *
	 * @link
	 * @since  3.0.0
	 */
	public function load_gas_framework() {

		/*
		 * When using the embedded framework, use it only if the framework
		 * plugin isn't activated.
		 */

		// Don't do anything when we're activating a plugin to prevent errors
		// on redeclaring Gas classes
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
		            if ( stripos( $plugin, '/gas-framework.php' ) !== false ) {
		                $useEmbeddedFramework = false;
		                break;
		            }
		        }
		    }
		}

		// Use the embedded Gas Framework
		if ( $useEmbeddedFramework && ! class_exists( 'GASFramework' ) ) {
		    require_once( WPAS_PATH . 'includes/gas-framework/gas-framework.php' );
		}

		/*
		 * Start your Gas code below
		 */
		$gas = GASFramework::getInstance( 'wpas' );

		$settings = $gas->createContainer( array(
						'type'       => 'admin-page',
						'name'       => __( 'Settings', 'awesome-support' ),
						'title'      => __( 'Settings', 'awesome-support' ),
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
