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
			call_user_func( $function_name );  // we have a very specific routine for this from/to combination
		} else {
			$this->run_upgrade_sequence( (int) $from, (int) $to ); // run all upgrades between from and to versions.
		}

	}

	/**
	 * Update the current version whatever the previous one was
	 *
	 * @since 3.2.0
	 * @return void
	 *
	 * This routine is no longer used and can be removed.
	 */
	protected function upgrade_current() {

		$version       = str_replace( '.', '', $this->current_version );
		$function_name = "wpas_upgrade_{$version}";

		if ( function_exists( $function_name ) ) {
			call_user_func( $function_name );
		}

	}
	
	/**
	 * Run all update routines between the 'old' version (from) and the current version ('to')
	 *
	 * @since 5.2.0
	 *
	 * @param int $from From version
	 * @param int $to   To version
	 *
	 * @return void
	 *
	 */
	protected function run_upgrade_sequence( $from, $to ) {
		
		if ( $from > 0 && $to > 0 ) {
			
			// Setup an array with list of upgrade functions...
			// Very important - set up this array in sequential order 
			// since we're not going to sort it before running through it!
			// Also, note that we only support 3 digit versions.  If you
			// go to 4 digits (eg: 4.1.11) you'll need to redo this 
			// logic.
			$upgrade_functions[320] = 'wpas_upgrade_320';
			$upgrade_functions[321] = 'wpas_upgrade_321';
			$upgrade_functions[328] = 'wpas_upgrade_328';
			$upgrade_functions[330] = 'wpas_upgrade_330';
			$upgrade_functions[333] = 'wpas_upgrade_333';
			$upgrade_functions[406] = 'wpas_upgrade_406';
			$upgrade_functions[410] = 'wpas_upgrade_410';
			$upgrade_functions[440] = 'wpas_upgrade_440';
			$upgrade_functions[511] = 'wpas_upgrade_511';
			$upgrade_functions[520] = 'wpas_upgrade_520';			
			$upgrade_functions[550] = 'wpas_upgrade_550';
			$upgrade_functions[581] = 'wpas_upgrade_581';
			$upgrade_functions[590] = 'wpas_upgrade_590';
			$upgrade_functions[600] = 'wpas_upgrade_600';
			$upgrade_functions[605] = 'wpas_upgrade_605';
			
			foreach(  $upgrade_functions as $version => $function_name ) {
				
				error_log( 'Awesome Support: evaluating conditions for upgrade process from version: ' . (string) $version . ' ' . $function_name ) ;				
				
				if ( $version > $from and $version <= $to ) {
					
					if ( function_exists( $function_name ) ) {
				
						error_log( 'Awesome Support: executing upgrade function: ' . $function_name );						
						call_user_func( $function_name );
						
					}
					
				}
				
			}
		}

	}	

}