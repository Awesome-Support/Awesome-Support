<?php
/**
 * @package   Awesome Support/Admin/Settings
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2017 Awesome Support
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'wpas_plugin_settings', 'wpas_core_settings_addons', 95, 1 );
/**
 * Addons settings.
 *
 * The settings below are related to paid addons that have been purchased by the client.
 *
 * @since 4.1
 *
 * @param  array $def Array of existing settings.
 *
 * @return array      Updated settings
 */
function wpas_core_settings_addons( $def ) {

	$settings = array(
		'addons' => array(
			'name'    => esc_attr__( 'Addons', 'awesome-support' ),
			'options' => array(
				array(
					'name' => esc_attr__( 'Connect Your Account', 'awesome-support' ),
					'type' => 'heading',
					'desc' => esc_html__( 'Set your API credentials to connect to your getawesomesupport.com account and install your purchased addons in one click.' ),
				),
				array(
					'name'    => esc_attr__( 'Server API Key', 'awesome-support' ),
					'id'      => 'edd_api_key',
					'type'    => 'text',
					'default' => '',
					'desc'    => esc_attr_x( 'Your API key can be found in your user account on', 'The sentence ends with a link to the user account on getawesomesupport.com', 'awesome-support' ) . ' <a href="' . esc_url( 'https://getawesomesupport.com' ) . '">getawesomesupport.com</a>',
				),
				array(
					'name'    => __( 'Server API Token', 'awesome-support' ),
					'id'      => 'edd_api_token',
					'type'    => 'text',
					'default' => '',
					'desc'    => esc_attr_x( 'Your API token can be found in your user account on', 'The sentence ends with a link to the user account on getawesomesupport.com', 'awesome-support' ) . ' <a href="' . esc_url( 'https://getawesomesupport.com' ) . '">getawesomesupport.com</a>',
				),
				array(
					'name'    => __( 'Server API Email', 'awesome-support' ),
					'id'      => 'edd_api_email',
					'type'    => 'text',
					'default' => '',
					'desc'    => esc_attr_x( 'Your API email can be found in your user account on', 'The sentence ends with a link to the user account on getawesomesupport.com', 'awesome-support' ) . ' <a href="' . esc_url( 'https://getawesomesupport.com' ) . '">getawesomesupport.com</a>',
				),
			),
		),
	);

	return array_merge( $def, $settings );

}