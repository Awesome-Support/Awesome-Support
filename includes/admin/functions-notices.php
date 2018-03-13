<?php
/**
 * Get all dismissed notices.
 *
 * @since  3.1.5
 * @return array Array of dismissed notices
 */
function wpas_dismissed_notices() {

	global $current_user;

	$user_notices = (array) get_user_option( 'wpas_dismissed_notices', $current_user->ID );

	return $user_notices;

}

/**
 * Check if a specific notice has been dismissed.
 *
 * @since  3.1.5
 * @param  string $notice Notice to check
 * @return boolean        Whether or not the notice has been dismissed
 */
function wpas_is_notice_dismissed( $notice ) {

	$dismissed = wpas_dismissed_notices();

	if ( array_key_exists( $notice, $dismissed ) ) {
		return true;
	} else {
		return false;
	}

}

/**
 * Dismiss a notice.
 *
 * @since  3.1.5
 * @param  string          $notice Notice to dismiss
 * @return boolean|integer         True on success, false on failure, meta ID if it didn't exist yet
 */
function wpas_dismiss_notice( $notice ) {

	global $current_user;

	$dismissed_notices = $new = (array) wpas_dismissed_notices();

	if ( ! array_key_exists( $notice, $dismissed_notices ) ) {
		$new[$notice] = 'true';
	}

	$update = update_user_option( $current_user->ID, 'wpas_dismissed_notices', $new );

	return $update;

}

/**
 * Restore a dismissed notice.
 *
 * @since  3.1.5
 * @param  string          $notice Notice to restore
 * @return boolean|integer         True on success, false on failure, meta ID if it didn't exist yet
 */
function wpas_restore_notice( $notice ) {

	global $current_user;

	$dismissed_notices = (array) wpas_dismissed_notices();

	if ( array_key_exists( $notice, $dismissed_notices ) ) {
		unset( $dismissed_notices[$notice] );
	}

	$update = update_user_option( $current_user->ID, 'wpas_dismissed_notices', $dismissed_notices );

	return $update;

}

add_action( 'wpas_do_dismiss_notice', 'wpas_grab_notice_dismiss' );
/**
 * Check if there is a notice to dismiss.
 *
 * @since  3.1.5
 *
 * @param array $data Contains the notice ID
 *
 * @return void
 */
function wpas_grab_notice_dismiss( $data ) {

	$notice_id = isset( $data['notice_id'] ) ? $data['notice_id'] : false;

	if ( false === $notice_id ) {
		return;
	}

	wpas_dismiss_notice( $notice_id );

}

class AS_Admin_Notices {

	/**
	 * Holds all our custom admin notices
	 *
	 * @since 3.2.5
	 * @var array
	 */
	protected $notices;

	public function __construct() {
		$this->init();
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since  3.2.5
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'awesome-support' ), '3.2.5' );
	}

	/**
	 * Instantiate the object
	 *
	 * @since 3.1.5
	 * @return void
	 */
	protected function init() {
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	/**
	 * List the allowed notice types
	 *
	 * @since 3.1.5
	 * @return array
	 */
	protected function notice_types() {

		$types = array(
			'updated',
			'error'
		);

		return $types;

	}

	/**
	 * Add a new admin notice
	 *
	 * @since 3.1.5
	 *
	 * @param string $type    Notice type (see notice_types())
	 * @param string $id      Notice unique ID
	 * @param string $message Notice message
	 *
	 * @return void
	 */
	public function add_notice( $type, $id, $message ) {
		
		/* If running in SAAS mode and the notice is something about a license then don't bother! */
		if ( true === $this->is_license_notice( $type, $id, $message ) && ( true === is_saas() )  ) {
			return ;
		}		

		if ( ! in_array( $type, $this->notice_types() ) ) {
			$type = 'updated';
		}

		$id      = sanitize_key( $id );
		$message = wp_kses_post( $message );

		$this->notices[ $id ] = array( $type, $message );

	}
	
	/**
	 * Check to see if the notice is a license notice by inspecting the $ID.
	 *
	 * @since 4.4.0
	 *
	 * @param string $type    Notice type (see notice_types())
	 * @param string $id      Notice unique ID
	 * @param string $message Notice message
	 *
	 * @return boolean
	 */	
	public function is_license_notice( $type, $id, $message ){
		if ( ( strpos( 'xxx'.$id, 'lincense_' ) >= 0 ) || ( strpos( 'xxx'.$id, 'license_' ) >= 0 ) ) {			
			return true ;
		}
		
		return false ;
	}	

	/**
	 * Get all custom notices registered
	 *
	 * @return null|array
	 */
	protected function get_notices() {
		return apply_filters( 'wpas_admin_notices', $this->notices );
	}

	/**
	 * Display all the custom notices
	 *
	 * @since 3.1.5
	 * @return void
	 */
	public function display_notices() {

		$notices = $this->get_notices();

		if ( empty( $notices ) || is_null( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice_id => $notice ) {

			if ( wpas_is_notice_dismissed( $notice_id ) ) {
				continue;
			}

			$url = wpas_do_url( add_query_arg( $_GET, '' ), 'dismiss_notice', array( 'notice_id' => $notice_id ) );

			printf( '<div class="%s"><p>%s <a href="%s"><small>(%s)</small></a></p></div>', $notice[0], $notice[1], esc_url( $url ), _x( 'Dismiss', 'Dismiss link for admin notices', 'awesome-support' ) );

		}

	}

}