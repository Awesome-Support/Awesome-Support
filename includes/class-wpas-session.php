<?php
/**
 * Awesome Support Session.
 *
 * @package   Awesome Support/Session
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPAS_Session
 *
 * @since 3.2
 */
class WPAS_Session {

	/**
	 * Holds the session
	 *
	 * @since 3.2
	 * @var array
	 */
	private $session;

	public function __construct() {


		if ( ! defined( 'WP_SESSION_COOKIE' ) ) {
			define( 'WP_SESSION_COOKIE', '_wpas_session' );
		}

		require_once( WPAS_PATH . 'vendor/ericmann/wp-session-manager/wp-session-manager.php' );
		
		add_filter( 'wp_session_cookie_secure',     array( $this, 'wpas_set_cookie_secure_flag' ), 10, 1 );	// Set the SECURE flag on the cookie
		add_filter( 'wp_session_cookie_httponly',   array( $this, 'wpas_set_http_only_flag' ), 10, 1 );	// Set the SECURE flag on the cookie
		add_filter( 'wp_session_delete_batch_size', array( $this, 'wpas_set_session_delete_batch_Size' ), 10, 1 );	// Set the number of expired session objects to delete on every clean-up pass

		// Instantiate the session
		$this->init();

	}

	/**
	 * Instantiate the session
	 *
	 * You can use the wpas_initiate_session_flag filter to disable creating the session.
	 * This would be useful when the traffic is coming from bot sources such as pingdom or uptimerobot
	 *
	 * @since 3.2
	 * @return void
	 */
	public function init() {
		$open_session = apply_filters( 'wpas_initiate_session_flag', true ) ;
		
		if ( true === $open_session ) {
			$this->session = WP_Session::get_instance();
		}
	}

	/**
	 * Add new session variable
	 *
	 * @since 3.2
	 *
	 * @param string $key   Name of the session to add
	 * @param mixed  $value Session value
	 * @param bool   $add   Whether to add the new value to the previous one or just update
	 *
	 * @return void
	 */
	public function add( $key, $value, $add = false ) {

		$key   = sanitize_text_field( $key );
		$value = $this->sanitize( $value );

		if ( true === $this->session->offsetExists( $key ) && true === $add ) {

			$old = $this->get( $key );

			if ( ! is_array( $old ) ) {
				$old = (array) $old;
			}

			$new                   = array_push( $old, $value );
			$this->session[ $key ] = serialize( $new );

		} else {
			$this->session[ $key ] = $value;
		}

	}

	/**
	 * Get session value
	 *
	 * @since 3.2
	 *
	 * @param string $key     Session key to retrieve the value for
	 * @param mixed  $default Value to return if the key doesn't exist
	 *
	 * @return mixed
	 */
	public function get( $key, $default = false ) {

		$value = $default;
		$key   = sanitize_text_field( $key );

		if ( true === $this->session->offsetExists( $key ) ) {
			$value = $this->session[ $key ];
		}

		return maybe_unserialize( $value );

	}

	/**
	 * Get current session superglobal
	 *
	 * @since 3.2
	 * @return array
	 */
	public function get_session() {
		return $this->session;
	}

	/**
	 * Clean a session
	 *
	 * @since 3.2
	 *
	 * @param string $key Name of the session to clean
	 *
	 * @return bool True if the session was cleaned, false otherwise
	 */
	public function clean( $key ) {

		$key     = sanitize_text_field( $key );
		$cleaned = false;

		if ( true === $this->session->offsetExists( $key ) ) {
			unset( $this->session[ $key ] );
			$cleaned = true;
		}

		return $cleaned;

	}

	/**
	 * Reset the entire wpas session
	 *
	 * @since 3.2
	 * @return void
	 */
	public function reset() {
		$this->session = array();
	}

	/**
	 * Sanitize session value
	 *
	 * @since 3.2
	 *
	 * @param mixed $value Value to sanitize
	 *
	 * @return string Sanitized value
	 */
	public function sanitize( $value ) {

		if ( is_array( $value ) || is_object( $value ) ) {
			$value = serialize( $value );
		}

		return $value;

	}
	
	
	/**
	 * Set the secure flag on the cookie
	 *
	 * Filter: wp_session_cookie_secure
	 *
	 * @param boolean $secure_flag
	 *
	 * @since 4.0.4
	 *
	 * @return boolean flag - true or false, default false
	 */
	public function wpas_set_cookie_secure_flag ( $secure_flag ) {
		
		$secure_flag = boolval( wpas_get_option( 'secure_cookies', false) );
		
		return $secure_flag;
	}
	
	/**
	 * Set the httponly flag on the cookie
	 *
	 * Filter: wp_session_cookie_httponly
	 *
	 * @param boolean $http_only_flag
	 *
	 * @since 4.0.4
	 *
	 * @return boolean flag - true or false, default false
	 */
	public function wpas_set_http_only_flag ( $http_only_flag ) {
		
		$http_only_flag = boolval( wpas_get_option( 'cookie_http_only', false) );
		
		return $http_only_flag;
	}

	/**
	 * Set the amount of expired sessions to delete in one pass
	 *
	 * Filter: wp_session_delete_batch_size
	 *
	 * @param boolean $batch_size
	 *
	 * @since 4.2.0
	 *
	 * @return number - number of expired sessions to delete in every call
	 */	
	public function wpas_set_session_delete_batch_Size ( $batch_size ) {
		
		$batch_size = intval( wpas_get_option( 'session_delete_batch_size', 1000 ) ) ;
		
		return $batch_size;
		
	}
	
}