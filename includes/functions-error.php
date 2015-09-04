<?php
/**
 * AS Errors
 *
 * A set of helper functions for handling errors
 *
 * @since 3.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set the errors session
 *
 * @since 3.2
 * @return void
 */
function wpas_set_errors() {
	global $wpas_session;
	$wpas_session->add( 'errors', array() );
}

/**
 * Add a new error
 *
 * @since 3.2
 *
 * @param string $error_id      ID of the error to add
 * @param string $error_message Error message
 *
 * @return void
 */
function wpas_add_error( $error_id, $error_message ) {

	global $wpas_session;

	$errors        = $wpas_session->get( 'errors' );
	$error_id      = sanitize_text_field( $error_id );
	$error_message = wp_kses_post( $error_message );

	if ( false === $errors ) {
		wpas_set_errors();
	}

	$errors[ $error_id ] = $error_message;

	$wpas_session->add( 'errors', $errors );

}

/**
 * Get error message by error ID
 *
 * @since 3.2
 *
 * @param  string $error_id ID of the error to get
 * @param mixed   $default  Default value to return if error doesn't exist
 *
 * @return mixed
 */
function wpas_get_error( $error_id, $default = false ) {

	global $wpas_session;

	$value    = $default;
	$errors   = $wpas_session->get( 'errors' );
	$error_id = sanitize_text_field( $error_id );

	if ( is_array( $errors ) && array_key_exists( $error_id, $errors ) ) {
		$value = $errors[ $error_id ];
	}

	return $value;

}

/**
 * Get all error messages
 *
 * @since 3.2
 * @return array
 */
function wpas_get_errors() {

	global $wpas_session;

	return $wpas_session->get( 'errors' );
}

/**
 * Clean one error from the list of errors
 *
 * @since 3.2
 *
 * @param string $error_id ID of the error to remove
 *
 * @return void
 */
function wpas_clean_error( $error_id ) {

	if ( false === wpas_get_error( $error_id ) ) {
		return;
	}

	global $wpas_session;

	$errors = wpas_get_errors();

	unset( $errors[ $error_id ] );

	$wpas_session->add( 'errors', $errors );

}

/**
 * Clean all errors from session
 *
 * @since 3.2
 * @return void
 */
function wpas_clean_errors() {
	wpas_set_errors();
}

/**
 * Get all errors in a human readable format
 *
 * @since 3.2
 * @return string
 */
function wpas_get_display_errors() {

	$errors = wpas_get_errors();
	$text   = '';

	if ( count( $errors ) >= 2 ) {
		$text = '<ul>';
		foreach ( $errors as $id => $message ) {
			$text .= "<li>$message</li>";
		}
		$text .= '</ul>';
	} else {
		foreach ( $errors as $id => $message ) {
			$text = $message;
		}
	}

	return wpas_get_notification_markup( 'failure', $text );

}

add_action( 'wpas_before_template', 'wpas_display_errors', 10, 3 );
/**
 * Display all error messages
 *
 * @since 3.2
 * @return string Readable errors
 */
function wpas_display_errors() {
	echo wpas_get_display_errors();
	wpas_clean_errors();
}