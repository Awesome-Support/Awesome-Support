<?php
/**
 * Awesome Support Session.
 *
 * @package   Awesome Support/Session
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
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

	/**
	 * Session prefix used with PHP sessions
	 *
	 * @since 3.2
	 * @var string
	 */
	private $prefix;

	public function __construct() {

		if ( $this->can_php_session() ) {

			$this->maybe_start_session();

			add_action( 'plugins_loaded', array( $this, 'init' ) );

		} else {

			if ( ! defined( 'WP_SESSION_COOKIE' ) ) {
				define( 'WP_SESSION_COOKIE', '_wpas_session' );
			}

			require_once( WPAS_PATH . 'vendor/ericmann/wp-session-manager/wp-session-manager.php' );

		}

	}

	/**
	 * Instantiate the session
	 *
	 * @since 3.2
	 * @return void
	 */
	public function init() {

		if ( $this->can_php_session() ) {

			$key = 'wpas' . $this->prefix;

			// Set the session if necessary
			if ( ! array_key_exists( $key, $_SESSION ) ) {
				$_SESSION[ $key ] = array();
			}

			$this->session = $_SESSION[ $key ];

		} else {
			$this->session = WP_Session::get_instance();
		}

	}

	/**
	 * Check if server supports PHP sessions
	 *
	 * @since 3.2
	 * @return bool
	 */
	public function can_php_session() {

		// Check if the server supports PHP sessions
		if ( function_exists( 'session_start' ) && ! ini_get( 'safe_mode' ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Update the PHP session when internal session changes
	 *
	 * @since 3.2
	 * @return void
	 */
	protected function update_php_session() {

		$key = 'wpas' . $this->prefix;

		// Set the session if necessary
		if ( ! array_key_exists( $key, $_SESSION ) ) {
			$_SESSION[ $key ] = array();
		}

		$_SESSION[ $key ] = $this->session;

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

		if ( array_key_exists( $key, $this->session ) && true === $add ) {

			$old = $this->get( $key );

			if ( ! is_array( $old ) ) {
				$old = (array) $old;
			}

			$new                   = array_push( $old, $value );
			$this->session[ $key ] = serialize( $new );

		} else {
			$this->session[ $key ] = $value;
		}

		if ( $this->can_php_session() ) {
			$this->update_php_session();
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

		if ( array_key_exists( $key, $this->session ) ) {
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

		if ( array_key_exists( $key, $this->session ) ) {
			unset( $this->session[ $key ] );
			$cleaned = true;
		}

		if ( $this->can_php_session() ) {
			$this->update_php_session();
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

		if ( $this->can_php_session() ) {
			$this->update_php_session();
		}

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
	 * Maybe start the session
	 *
	 * @since 3.2
	 * @return void
	 */
	public function maybe_start_session() {
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}
	}

}