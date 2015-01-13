<?php
/**
 * WooCommerce Product Integration.
 *
 * This class will, if WooCommerce is enabled, synchronize the WooCommerce products
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
class WPAS_Product_WooCommerce {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {

		if ( $this->is_woocommerce_enabled() ) {
			$sync = new WPAS_Product_Sync( 'product' );
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
	 * Check if WooCommerce is present and enabled.
	 *
	 * @since  3.0.2
	 * @return boolean True if WooCommerce is in use, false otherwise
	 */
	protected function is_woocommerce_enabled() {

		if ( !class_exists( 'WC_Integration' ) ) {
			return false;
		}

		return true;
	}

}