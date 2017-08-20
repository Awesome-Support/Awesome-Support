<?php
/**
 * @package   Awesome Support
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2017 Awesome Support
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WPAS_Addons_Installer' ) ) {

	/**
	 * Class Addons_Installer
	 *
	 * This class handles interactions with the EDD API on our own server for retrieving the user purchased addons, downloading and installing them.
	 *
	 * @since 4.1
	 */
	class WPAS_Addons_Installer {

		/**
		 * The user API key.
		 *
		 * @since 4.1
		 * @var string User API key
		 */
		protected $user_api_key;

		/**
		 * The user API token.
		 *
		 * @since 4.1
		 * @var string
		 */
		protected $user_api_token;

		/**
		 * The user email as registered on getawesomesupport.com.
		 *
		 * @since 4.1
		 * @var string
		 */
		protected $user_api_email;

		/**
		 * Addons_Installer constructor.
		 *
		 * @since 4.1
		 */
		public function __construct() {
			$this->load_api_credentials();
		}

		/**
		 * Load the user API credentials.
		 *
		 * We need the credentials to authenticate with the EDD API on our server.
		 *
		 * @since 4.1
		 * @return bool
		 */
		public function load_api_credentials() {

			$this->user_api_key   = trim( wpas_get_option( 'edd_api_key', '' ) );
			$this->user_api_token = trim( wpas_get_option( 'edd_api_token', '' ) );
			$this->user_api_email = trim( wpas_get_option( 'edd_api_email', '' ) );

			if ( '' === ( $this->user_api_key || $this->user_api_token || $this->user_api_email ) ) {
				return false;
			}

			return true;
		}

	}

}
