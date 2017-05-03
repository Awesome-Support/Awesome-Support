<?php
/**
 * Awesome Support Upgrade.
 *
 * @package   Awesome_Support_Admin
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 * @since     3.2.0
 */
class WPAS_Upgrade {

	/**
	 * Instance of this class.
	 *
	 * @since    3.2.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Version number stored in the database.
	 *
	 * @since 3.2.0
	 * @var $db_version string
	 */
	public $db_version = '';

	/**
	 * Version number declared in the plugin main file.
	 *
	 * @since 3.2.0
	 * @var $current_version string
	 */
	public $current_version = '';

	/**
	 * Type of routine required.
	 *
	 * @since 3.2.0
	 * @var $routine string
	 */
	public $routine = '';

	public function __construct() {

		$this->db_version      = get_option( 'wpas_version', '3.0.0' );
		$this->current_version = WPAS_VERSION;
		$this->routine         = version_compare( $this->db_version, $this->current_version, '<' ) ? 'upgrade' : 'downgrade';

		if ( $this->db_version !== $this->current_version ) {

			require_once( WPAS_PATH . 'includes/admin/upgrade/functions-upgrade.php' );

			/* Run the upgrade methods */
			$this->upgrade_from_to();
			$this->upgrade_current();

			/* Whatever the routing, we update the version number if needed. */
			$this->update_db_version();

		}

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
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
	 * Update the version number in the database.
	 *
	 * @since 3.2.0
	 * @return void
	 */
	protected function update_db_version() {
		update_option( 'wpas_version', $this->current_version );
	}

	/**
	 * Upgrade from a specific version to the current one
	 *
	 * @since 3.2.0
	 * @return void
	 */
	protected function upgrade_from_to() {

		$from          = str_replace( '.', '', $this->db_version );
		$to            = str_replace( '.', '', $this->current_version );
		$function_name = "wpas_upgrade_{$from}_{$to}";

		if ( function_exists( $function_name ) ) {
			call_user_func( $function_name );
		}

	}

	/**
	 * Update the current version whatever the previous one was
	 *
	 * @since 3.2.0
	 * @return void
	 */
	protected function upgrade_current() {

		$version       = str_replace( '.', '', $this->current_version );
		$function_name = "wpas_upgrade_{$version}";

		if ( function_exists( $function_name ) ) {
			call_user_func( $function_name );
		}

	}

}