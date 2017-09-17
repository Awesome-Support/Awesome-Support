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
		 * The EDD API endpoint.
		 *
		 * @since 4.1
		 * @var string API endpoint URL
		 */
		public $api_endpoint = 'https://getawesomesupport.com/wp-json/as-client/';

		/**
		 * Addons_Installer constructor.
		 *
		 * @since 4.1
		 */
		public function __construct() {
			$this->load_api_credentials();
			$this->api_endpoint = apply_filters( 'wpas_addon_installer_api_endpoint', $this->api_endpoint );
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

			$this->user_api_key   = trim( wpas_get_option( 'edd_api_key', getenv( 'WPAS_EDD_API_KEY' ) ) );
			$this->user_api_token = trim( wpas_get_option( 'edd_api_token', getenv( 'WPAS_EDD_API_TOKEN' ) ) );
			$this->user_api_email = trim( wpas_get_option( 'edd_api_email', getenv( 'WPAS_EDD_API_EMAIL' ) ) );

			if ( empty( $this->user_api_key ) || empty( $this->user_api_token ) || empty( $this->user_api_email ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Get the list of user purchases.
		 *
		 * @since 4.1
		 * @return array
		 */
		public function get_purchases() {

			$response = $this->query_edd_server( 'addons' );

			if ( is_wp_error( $response ) ) {
				return array();
			}

			return $response;

		}

		/**
		 * Get all the downloads from the user purchases.
		 *
		 * @since 4.1
		 * @return array
		 */
		public function get_downloads() {

			$downloads = array();
			$purchases = $this->get_purchases();

			foreach ( $purchases as $purchase ) {
				if ( isset( $purchase->downloads ) ) {
					foreach ( $purchase->downloads as $download ) {
						$downloads[] = $download;
					}
				}
			}

			return $downloads;
		}

		/**
		 * Get all purchased addons for the sale.
		 *
		 * This method fetches all the addons purchased in the current sale, filters out the data we don't need, and adds the license key with each product (needed for addon activation).
		 *
		 * @since 4.1
		 *
		 * @param array $sale The sale array.
		 *
		 * @return array
		 */
		protected function get_purchased_addons_products( $sale ) {

			$products = array();

			foreach ( $sale['products'] as $key => $product ) {
				$products[] = array(
					'id'      => $product['id'],
					'name'    => $product['name'],
					'license' => $sale['licenses'][ $key ]['key'],
				);
			}

			return $products;

		}

		/**
		 * Query the EDD API server.
		 *
		 * @since 4.2
		 *
		 * @param string $route  The route to query.
		 * @param array  $params The query parameters.
		 *
		 * @return array|WP_Error
		 */
		protected function query_edd_server( $route, $params = array() ) {

			global $wp_version;

			$routes = array(
				'addons',
			);

			if ( ! in_array( $route, $routes, true ) ) {
				return new WP_Error( 'unknown_route', esc_attr( 'The API route you are trying to query is unknown.' ) );
			}

			$params   = $this->get_authenticated_params( $params );
			$query    = esc_url_raw( trailingslashit( $this->api_endpoint ) . $route . '?' . http_build_query( $params ) );
			$response = wp_remote_get( $query, array(
				'timeout'     => 30,
				'redirection' => 3,
				'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
			) );

			// Check the response code.
			$response_code    = wp_remote_retrieve_response_code( $response );
			$response_message = wp_remote_retrieve_response_message( $response );

			if ( 200 !== $response_code && ! empty( $response_message ) ) {
				return new WP_Error( $response_code, $response_message );
			} elseif ( 200 !== $response_code ) {
				return new WP_Error( $response_code, 'Unknown error occurred' );
			}

			return json_decode( wp_remote_retrieve_body( $response ) );

		}

		/**
		 * Add API credentials to the query parameters.
		 *
		 * @since 4.1
		 *
		 * @param array $params Query parameters.
		 *
		 * @return array
		 */
		protected function get_authenticated_params( $params ) {

			$params['email']     = $this->user_api_email;
			$params['api_key']   = $this->user_api_key;
			$params['api_token'] = $this->user_api_token;

			return $params;
		}

	}

}
