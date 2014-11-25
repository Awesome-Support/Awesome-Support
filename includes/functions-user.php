<?php
/**
 * Register user account.
 *
 * @since  1.0.0
 * @return void
 */
function wpas_register_account() {

	global $post;

	/* Make sure registrations are open */
	$registration = boolval( wpas_get_option( 'allow_registrations', true ) );

	if ( true !== $registration ) {
		wp_redirect( add_query_arg( array( 'message' => wpas_create_notification( __( 'Registrations are currently not allowed.', 'wpas' ) ), get_permalink( $post->ID ) ) ) );
		exit;
	}

	$email    = isset( $_POST['email'] ) && !empty( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : false;
	$username = isset( $_POST['username'] ) && !empty( $_POST['username'] ) ? sanitize_text_field( $_POST['username'] ) : $email;
	$pwd      = isset( $_POST['pwd'] ) && !empty( $_POST['pwd'] ) ? $_POST['pwd'] : false;
	$pwd2     = isset( $_POST['pwd-validate'] ) && !empty( $_POST['pwd-validate'] ) ? $_POST['pwd-validate'] : false;

	/* Make sure we have all the necessary data. */
	if ( false === ( $email || $username || $pwd || $pwd2 ) ) {
		wp_redirect( add_query_arg( array( 'message' => wpas_create_notification( __( 'You didn\'t correctly fill all the fields.', 'wpas' ) ), get_permalink( $post->ID ) ) ) );
		exit;
	}

	/* Check passwords */
	if ( $pwd !== $pwd2 ) {
		wp_redirect( add_query_arg( array( 'message' => wpas_create_notification( __( 'The password confirmation does not match the password.', 'wpas' ) ), get_permalink( $post->ID ) ) ) );
		exit;
	}

	$args = array(
		'user_login'   => $username,
		'user_email'   => $email,
		'display_name' => $username,
		'user_pass'    => $pwd,
		'role'         => 'wpas_user'
	);

	$user_id = wp_insert_user( $args );

	if ( is_wp_error( $user_id ) ) {

		$error = $user_id->get_error_message();
		wp_redirect( add_query_arg( array( 'message' => wpas_create_notification( $error ), get_permalink( $post->ID ) ) ) );
		exit;

	} else {

		wp_new_user_notification( $user_id, $pwd );

		if ( headers_sent() ) {
			wp_redirect( add_query_arg( array( 'message' => wpas_create_notification( __( 'Your account has been created. Please log-in.', 'wpas' ) ), get_permalink( $post->ID ) ) ) );
			exit;
		}

		if ( !is_user_logged_in() ) {

			/* Automatically log the user in */
			wp_set_current_user( $user_id, $email );
			wp_set_auth_cookie( $user_id );
			
			wp_redirect( get_permalink( $post->ID ) );
			exit;
		}

	}

}

/**
 * Try to log the user in.
 *
 * If credentials are passed through the POST data
 * we try to log the user in.
 */
function wpas_try_login() {

	global $post;

	/**
	 * Try to log the user if credentials are submitted.
	 */
	if ( isset( $_POST['log'] ) ) {

		$login = $_POST['log'];
		$pwd   = isset( $_POST['pwd'] ) ? $_POST['pwd'] : '';
		$login = wp_signon();

		if ( is_wp_error( $login ) ) {
			$error = $login->get_error_message();
			wp_redirect( add_query_arg( array( 'message' => urlencode( base64_encode( json_encode( $error ) ) ) ), get_permalink( $post->ID ) ) );
			exit;
		} elseif( is_a( $login, 'WP_User' ) ) { var_dump( $post->ID );
			wp_redirect( get_permalink( $post->ID ) );
			exit;
		} else {
			wp_redirect( add_query_arg( array( 'message' => urlencode( base64_encode( json_encode( __( 'We were unable to log you in for an unknown reason.', 'wpas' ) ) ) ) ), get_permalink( $post->ID ) ) );
			exit;
		}

	}

}

/**
 * Checks if a user can view a ticket.
 *
 * @since  2.0.0
 * @param  integer $post_id ID of the post to display
 * @return boolean
 */
function wpas_can_view_ticket( $post_id ) {

	/* Only logged in users can view */
	if ( !is_user_logged_in() ) {
		return false;
	}

	if ( !current_user_can( 'view_ticket' ) ) {
		return false;
	}

	$post      = get_post( $post_id );
	$author_id = intval( $post->post_author );

	if ( get_current_user_id() === $author_id ) {
		return true;
	}

	if ( current_user_can( 'edit_ticket' ) ) {
		return true;
	}

	return false;

}

/**
 * Check if the current user can reply from the frontend.
 *
 * @since  2.0.0
 * @param  boolean $admins_allowed Shall admins/agents be allowed to reply from the frontend
 * @param  integer $post_id        ID of the ticket to check
 * @return boolean                 True if the user can reply
 */
function wpas_can_reply_ticket( $admins_allowed = false, $post_id = null ) {

	if ( is_null( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}

	$admins_allowed = apply_filters( 'wpas_can_agent_reply_frontend', false ); /* Allow admins to post through front-end. The filter overwrites the function parameter. */
	$post           = get_post( $post_id );
	$author_id      = $post->post_author;
	$reply          = wpas_get_option( 'ticket_can_reply', 'author' );

	if ( is_user_logged_in() ) {

		global $current_user;

		if ( !current_user_can( 'reply_ticket' ) ) {
			return false;
		}

		$usr_mail = $current_user->data->user_email;
		$user_id  = $current_user->data->ID;

		/* If the current user is the author then yes */
		if( $user_id == $author_id ) {
			return true;
		} else {

			if ( current_user_can( 'edit_ticket' ) && true === $admins_allowed  ) {
				return true;
			} else {
				return false;
			}

		}

	} else {
		return false;
	}

}

/**
 * Get user role nicely formatted.
 *
 * @since  3.0.0
 * @param  string $role User role
 * @return string       Nicely formatted user role
 */
function wpas_get_user_nice_role( $role ) {

	/* Remove the prefix on WPAS roles */
	if ( 'wpas_' === substr( $role, 0, 5 ) ) {
		$role = substr( $role, 5 );
	}

	/* Remove separators */
	$role = str_replace( array( '-', '_' ), ' ', $role );

	/* Uppercase each first letter */
	return ucwords( $role );

}

function wpas_can_submit_ticket() {

	$can = true;

	return apply_filters( 'wpas_can_submit_ticket', $can );

}