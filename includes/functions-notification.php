<?php
/**
 * AS Notifications
 *
 * A set of helper functions for handling notifications
 *
 * @since 3.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set the notifications session
 *
 * @since 3.2
 *
 * @param string $group Session key
 *
 * @return void
 */
function wpas_set_notifications( $group = 'notifications' ) {
	WPAS()->session->add( $group, array() );
}

/**
 * Add a new notification
 *
 * @since 3.2
 *
 * @param string $id      ID of the notification to add
 * @param string $message notification message
 * @param string $group   Notification group to add the message into
 * @return void
 */
function wpas_add_notification( $id, $message, $group = 'notifications' ) {

	$notifications = WPAS()->session->get( $group );
	$id            = sanitize_text_field( $id );
	$message       = wp_kses_post( $message );

	if ( false === $notifications ) {
		wpas_set_notifications();
	}

	$notifications[ $id ] = $message;

	WPAS()->session->add( $group, $notifications );

}

/**
 * Get notification message by notification ID
 *
 * @since 3.2
 *
 * @param  string $id      ID of the notification to get
 * @param mixed   $default Default value to return if notification doesn't exist
 * @param string  $group   Notification group to look into
 *
 * @return mixed
 */
function wpas_get_notification( $id, $default = false, $group = 'notifications' ) {

	$value         = $default;
	$notifications = WPAS()->session->get( $group );
	$id            = sanitize_text_field( $id );

	if ( is_array( $notifications ) && array_key_exists( $id, $notifications ) ) {
		$value = $notifications[ $id ];
	}

	return $value;

}

/**
 * Get all notification messages
 *
 * @since 3.2
 *
 * @param string $group Notification group to look into
 *
 * @return array
 */
function wpas_get_notifications( $group = 'notifications' ) {
	return WPAS()->session->get( $group );
}

/**
 * Clean one notification from the list of notifications
 *
 * @since 3.2
 *
 * @param string $id    ID of the notification to remove
 * @param string $group Notification group to look into
 *
 * @return void
 */
function wpas_clean_notification( $id, $group ) {

	if ( false === wpas_get_notification( $id ) ) {
		return;
	}

	$notifications = wpas_get_notifications();

	unset( $notifications[ $id ] );

	WPAS()->session->add( $group, $notifications );

}

/**
 * Clean all notifications from session
 *
 * @since 3.2
 *
 * @param string $group Group of notifications to remove
 *
 * @return void
 */
function wpas_clean_notifications( $group = 'notifications' ) {
	WPAS()->session->clean( $group );
}

/**
 * Get all notifications in a human readable format
 *
 * @since 3.2
 *
 * @param string $group Group of notifications to lookup
 * @param string $type  Type of markup to use
 *
 * @return string
 */
function wpas_get_display_notifications( $group = 'notifications', $type = 'success' ) {

	$notifications = wpas_get_notifications( $group );
	$text          = '';

	if ( ! is_array( $notifications ) ) {
		$text = $notifications;
	} else {
		if ( count( $notifications ) >= 2 ) {

			$messages = array();

			foreach ( $notifications as $id => $message ) {
				array_push( $messages, wpas_readable_notification_message( $message ) );
			}

			$text = implode( '<br>', $messages );

		} else {
			foreach ( $notifications as $id => $message ) {
				$text = wpas_readable_notification_message( $message );
			}
		}
	}

	return wpas_get_notification_markup( $type, $text );

}

/**
 * Maybe transform array message into a readable list
 *
 * @since 3.2
 *
 * @param array|string $message Message to display
 *
 * @return string Readable message
 */
function wpas_readable_notification_message( $message ) {

	if ( ! is_array( $message ) ) {
		return $message;
	}

	$messages = array();

	foreach ( $message as $key => $value ) {
		array_push( $messages, wpas_readable_notification_message( $value ) );
	}

	return implode( '<br>', $messages );

}

add_action( 'wpas_before_template', 'wpas_display_notifications', 10, 3 );
/**
 * Display all notification messages
 *
 * @since 3.2
 * @return string Readable notifications
 */
function wpas_display_notifications() {
	echo wpas_get_display_notifications();
	wpas_clean_notifications();
}