<?php
/**
 * Jigoshop Product Integration.
 *
 * This class will, if Jigoshop is enabled, synchronize the EDD products
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
class WPAS_Product_Jigoshop {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {

		if ( $this->is_enabled() ) {
			$sync = new WPAS_Product_Sync( 'product', 'product', true );
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
	 * Check if Jigoshop is present and enabled.
	 *
	 * @since  3.0.2
	 * @return boolean True if Jigoshop is in use, false otherwise
	 */
	protected function is_enabled() {

		if ( !class_exists( 'Jigoshop_Base' ) ) {
			return false;
		}

		$plugins = get_option( 'active_plugins', array() );
		$active  = false;

		foreach ( $plugins as $plugin ) {
			if ( strpos( $plugin, 'jigoshop.php' ) !== false) {
				$active = true;
			}
		}

		return $active;
	}

	public function locked_message( $message ) {
		return sprintf( __( 'You cannot edit this term from here because it is linked to a Jigoshop product. <a href="%s">Please edit the product directly</a>.', 'awesome-support' ), add_query_arg( 'post_type', 'product', admin_url( 'edit.php' ) ) );
	}

}