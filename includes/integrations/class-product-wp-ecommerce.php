<?php
/**
 * WP eCommerce Product Integration.
 *
 * This class will, if WP eCommerce is enabled, synchronize the WP eCommerce products
 * with the product taxonomy of Awesome Support and make the management
 * of products completely transparent.
 *
 * @package   Awesome Support/Integrations
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 * 
 */
class WPAS_Product_WP_Ecommerce {

	/**
	 * Global instance of the product sync object
	 *
	 * @since 3.3
	 * @var WPAS_Product_Sync
	 */
	public $wpas_product_sync;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {

		if ( $this->is_enabled() ) {

			global $wpas_product_sync;

			$wpas_product_sync = new WPAS_Product_Sync( 'wpsc-product', 'product', true );
			add_filter( 'wpas_taxonomy_locked_msg', array( $this, 'locked_message' ) );
		}

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
	 * Check if WP eCommerce is present and enabled.
	 *
	 * @since  3.0.2
	 * @return boolean True if WP eCommerce is in use, false otherwise
	 */
	protected function is_enabled() {

		if ( !class_exists( 'WP_eCommerce' ) ) {
			return false;
		}

		$plugins = get_option( 'active_plugins', array() );
		$active  = false;

		foreach ( $plugins as $plugin ) {
			if ( strpos( $plugin, 'wp-shopping-cart.php' ) !== false) {
				$active = true;
			}
		}

		return $active;
	}

	public function locked_message( $message ) {
		return sprintf( __( 'You cannot edit this term from here because it is linked to a WP eCommerce product. <a href="%s">Please edit the product directly</a>.', 'awesome-support' ), add_query_arg( 'post_type', 'wpsc-product', admin_url( 'edit.php' ) ) );
	}

}