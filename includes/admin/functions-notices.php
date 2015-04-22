<?php
/**
 * Get all dismissed notices.
 *
 * @since  3.1.5
 * @return array Array of dismissed notices
 */
function wpas_dismissed_notices() {

	global $current_user;

	$user_notices = (array) get_user_meta( $current_user->ID, 'wpas_dismissed_notices', true );

	return $user_notices;

}

/**
 * Check if a specific notice has been dismissed.
 *
 * @since  3.1.5
 * @param  string $notice Notice to check
 * @return boolean        Whether or not the notice has been dismissed
 */
function wpas_is_notice_dismissed( $notice ) {

	$dismissed = wpas_dismissed_notices();

	if ( array_key_exists( $notice, $dismissed ) ) {
		return true;
	} else {
		return false;
	}

}

/**
 * Dismiss a notice.
 *
 * @since  3.1.5
 * @param  string          $notice Notice to dismiss
 * @return boolean|integer         True on success, false on failure, meta ID if it didn't exist yet
 */
function wpas_dismiss_notice( $notice ) {

	global $current_user;

	$dismissed_notices = $new = (array) wpas_dismissed_notices();

	if ( ! array_key_exists( $notice, $dismissed_notices ) ) {
		$new[$notice] = 'true';
	}

	$update = update_user_meta( $current_user->ID, 'wpas_dismissed_notices', $new, $dismissed_notices );

	return $update;

}

/**
 * Restore a dismissed notice.
 *
 * @since  3.1.5
 * @param  string          $notice Notice to restore
 * @return boolean|integer         True on success, false on failure, meta ID if it didn't exist yet
 */
function wpas_restore_notice( $notice ) {

	global $current_user;

	$dismissed_notices = (array) wpas_dismissed_notices();

	if ( array_key_exists( $notice, $dismissed_notices ) ) {
		unset( $dismissed_notices[$notice] );
	}

	$update = update_user_meta( $current_user->ID, 'wpas_dismissed_notices', $dismissed_notices );

	return $update;

}

add_action( 'admin_init', 'wpas_grab_notice_dismiss', 10, 0 );
/**
 * Check if there is a notice to dismiss.
 *
 * @since  3.1.5
 * @return void
 */
function wpas_grab_notice_dismiss() {

	$dismiss = filter_input( INPUT_GET, 'wpas-dismiss', FILTER_SANITIZE_STRING );
	$nonce   = filter_input( INPUT_GET, 'wpas-nonce',   FILTER_SANITIZE_STRING );

	if ( ! empty( $dismiss ) && ! empty( $nonce ) ) {
		if ( wpas_check_nonce( $nonce ) ) {
			wpas_dismiss_notice( $dismiss );
		}
	}

}