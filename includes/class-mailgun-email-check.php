<?php
class WPAS_MailGun_EMail_Check {

	/**
	 * MailGun Public API Key
	 * 
	 * @var string
	 */
	protected $public_key;

	/**
	 * MailGun endpoint
	 *
	 * @var string
	 */
	protected $endpoint;
	
	public function __construct() {
		$this->endpoint   = 'https://api.mailgun.net/v2/address/validate';
		$this->public_key = $this->get_api_key();
	}

	protected function get_api_key() {
		return wpas_get_option( 'mailgun_api_key', '' );
	}
	
	/**
	 * Add MailGun settings.
	 * 
	 * @param  (array) $settings Array of existing settings
	 * @return (array)           Updated settings
	 */
	public static function settings( $settings ) {

		if ( !isset( $settings['general'] ) ) {
			return $settings;
		}

		array_push( $settings['general']['options'], array(
				'name' => __( 'E-Mail Checking', 'wpas' ),
				'type' => 'heading',
			)
		);

		array_push( $settings['general']['options'], array(
				'desc' => sprintf( __( 'You can enable e-mail checking on the registration page. When enabled, the plugin will make sure the e-mail address used is valid and can receive e-mails. The verification is done using <a href="%s">Email validation API</a> and requires a (free) MailGun account. This helps reducing typos in email addresses during sign ups.', 'wpas' ), esc_url( 'http://www.mailgun.com/email-validation' ) ),
				'type' => 'note',
			)
		);

		array_push( $settings['general']['options'], array(
				'name'    => __( 'Enable E-Mail Checking', 'wpas' ),
				'id'      => 'enable_mail_check',
				'type'    => 'checkbox',
				'default' => false,
				'desc'    => __( 'Do you want to check e-mail addresses on new registrations?', 'wpas' )
				)
		);

		array_push( $settings['general']['options'], array(
				'name'    => __( 'MailGun Public API Key', 'wpas' ),
				'id'      => 'mailgun_api_key',
				'type'    => 'text',
				'default' => '',
				'desc'    => sprintf( __( 'If you don&#39;t have a MailGun account you can <a href="%s" target="_blank">create one for free here</a>.', 'wpas' ), esc_url( 'https://mailgun.com/signup' ) )
				)
		);

		return $settings;

	}

	public function check_email( $data = '' ) {

		if ( empty( $this->public_key ) ) {
			return new WP_Error( 'no_api_key', __( 'No API key was provided', 'wpas' ) );
		}

		if ( empty( $data ) ) {
			if ( isset( $_POST ) ) {
				$data = $_POST;
			} else {
				return new WP_Error( 'no_data', __( 'No data to check', 'wpas' ) );
			}
		}

		if ( !isset( $data['email'] ) ) {
			return new WP_Error( 'no_email', __( 'No e-mail to check', 'wpas' ) );
		}

		global $wp_version;

		$args = array(
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
			'blocking'    => true,
			'headers'     => array( 'Authorization' => 'Basic ' . base64_encode( 'api:' . $this->public_key ) ),
			'cookies'     => array(),
			'body'        => array( 'address' => $data['email'] ),
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
			'filename'    => null
		);

		$response      = wp_remote_get( esc_url( $this->endpoint ), $args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 != $response_code ) {
			return new WP_Error( $response_code, wp_remote_retrieve_response_message( $response ) );
		}

		$body = wp_remote_retrieve_body( $response );

		return $body;

	}

}