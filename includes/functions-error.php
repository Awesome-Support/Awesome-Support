<?php
/**
 * AS Errors
 *
 * These are wrapper functions for the notification helper functions
 * with the error group and failure type predefined
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
function mumei_ayuda_set_errors() {
	mumei_ayuda_set_notifications( 'errors' );
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
function mumei_ayuda_add_error( $error_id, $error_message ) {
	mumei_ayuda_add_notification( $error_id, $error_message, 'errors' );
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
function mumei_ayuda_get_error( $error_id, $default = false ) {
	return mumei_ayuda_get_notification( $error_id, $default, 'errors' );
}

/**
 * Get all error messages
 *
 * @since 3.2
 * @return array
 */
function mumei_ayuda_get_errors() {
	return mumei_ayuda_get_notifications( 'errors' );
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
function mumei_ayuda_clean_error( $error_id ) {
	mumei_ayuda_clean_notification( $error_id, 'errors' );
}

/**
 * Clean all errors from session
 *
 * @since 3.2
 * @return void
 */
function mumei_ayuda_clean_errors() {
	mumei_ayuda_clean_notifications( 'errors' );
}

/**
 * Get all errors in a human readable format
 *
 * @since 3.2
 * @return string
 */
function mumei_ayuda_get_display_errors() {
	return mumei_ayuda_get_display_notifications( 'errors', 'failure' );
}

add_action( 'mumei_ayuda_before_template', 'mumei_ayuda_display_errors', 10, 3 );
/**
 * Display all error messages
 *
 * @since 3.2
 * @return string Readable errors
 */
function mumei_ayuda_display_errors() {
	echo wp_kses_post( mumei_ayuda_get_display_errors() );
	mumei_ayuda_clean_errors();
}