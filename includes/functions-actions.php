<?php
/**
 * @package   Awesome Support/Functions/Actions
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'init', 'wpas_process_actions', 50 );
/**
 * Process actions that can be triggered by $_GET or $_POST vars
 *
 * To trigger an action, a superglobal var must be passed with the key wpas-do.
 * The other superglobal vars will then be passed as arguments to the hook.
 *
 * @since 3.3
 * @return void
 */
function wpas_process_actions() {

	$nonce = false;
	$action = '';	
    if ( isset( $_POST['wpas-do-nonce'] ) ) {
        $nonce = sanitize_text_field( wp_unslash( $_POST['wpas-do-nonce'] ) );
        $action = isset( $_POST['wpas-do'] ) ? sanitize_text_field( wp_unslash( $_POST['wpas-do'] ) ) : '';
    } elseif ( isset( $_GET['wpas-do-nonce'] ) ) {
        $nonce = sanitize_text_field( wp_unslash( $_GET['wpas-do-nonce'] ) );
        $action = isset( $_GET['wpas-do'] ) ? sanitize_text_field( wp_unslash( $_GET['wpas-do'] ) ) : '';	
	}

	// FIX: Use action-specific nonce verification
    if ( ! $nonce || ! $action || ! wp_verify_nonce( $nonce, 'wpas_do_' . $action ) ) {
        return;
    }

	if ( isset( $_POST['wpas-do'] ) ) {		
		do_action( 'wpas_do_' . $action, $_POST );
	}

	if ( isset( $_GET['wpas-do'] ) ) {		
		do_action( 'wpas_do_' . $action, $_GET );
	}

}

/**
 * Generate a wpas-do field with a security nonce
 *
 * @since 3.3
 *
 * @param string $action      Action trigger
 * @param string $redirect_to Possible URL to redirect to after the action
 * @param bool   $echo        Whether to echo or return the fields
 *
 * @return string
 */
function wpas_do_field( $action, $redirect_to = '', $echo = true ) {

	$field = sprintf( '<input type="hidden" name="%1$s" value="%2$s">', 'wpas-do', $action );

	$field .= wp_nonce_field( 'wpas_do_' . $action, 'wpas-do-nonce', true, false );

	$field = str_replace( 'id="wpas-do-nonce"' , 'id="wpas-do-nonce-' . $action . '"' , $field );

	if ( ! empty( $redirect_to ) ) {
		$field .= sprintf( '<input type="hidden" name="%1$s" value="%2$s">', 'redirect_to', wp_sanitize_redirect( $redirect_to ) );
	}
	//This has been verify by html tags ted.
	$allow_html_tags_wpas_do_field = array(
			'input' => [
				'type' => true,
				'name' => true,
				'value' => true,
				'id' => true,			
			]
		);	
	if ( $echo ) {
		echo wp_kses($field, $allow_html_tags_wpas_do_field);	
	}
	return $field;
}

/**
 * Generate a wpas-do URL with a security nonce
 *
 * @since 3.3
 *
 * @param string $url    URL to action & nonce
 * @param string $action Action trigger
 * @param array  $args   Extra URL parameters to add
 *
 * @return string
 */
function wpas_do_url( $url, $action, $args = array() ) {

	$args['wpas-do']       = $action;
	$args['wpas-do-nonce'] = wp_create_nonce( 'wpas_do_' . $action );
	$url                   = esc_url( add_query_arg( $args, $url ) );

	return $url;

}