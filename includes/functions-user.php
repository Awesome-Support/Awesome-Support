<?php
/**
 * Register user account.
 *
 * @param array|bool $data User data
 *
 * @since  1.0.0
 * @return void
 */
function wpas_register_account( $data = false ) {

	global $post;

	/* Make sure registrations are open */
	$registration = boolval( wpas_get_option( 'allow_registrations', true ) );

	if ( true !== $registration ) {
		wp_redirect( add_query_arg( array(
			'message' => wpas_create_notification( __( 'Registrations are currently not allowed.', 'wpas' ) ),
			get_permalink( $post->ID )
		) ) );
		exit;
	}

	if ( false === $data ) {
		$data = $_POST;
	}

	$email      = isset( $data['email'] ) && ! empty( $data['email'] ) ? sanitize_email( $data['email'] ) : false;
	$first_name = isset( $data['first_name'] ) && ! empty( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : false;
	$last_name  = isset( $data['last_name'] ) && ! empty( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : false;
	$pwd        = isset( $data['pwd'] ) && ! empty( $data['pwd'] ) ? $data['pwd'] : false;

	/* Save the user information in session to pre populate the form in case of error. */
	$_SESSION['wpas_registration_form'] = array(
		'first_name' => $first_name,
		'last_name'  => $last_name,
		'email'      => $email,
	);

	/**
	 * wpas_pre_register_account hook
	 *
	 * This hook is triggered all the time
	 * even if the checks don't pass.
	 *
	 * @since  3.0.1
	 */
	do_action( 'wpas_pre_register_account', $data );

	if ( wpas_get_option( 'terms_conditions', false ) && ! isset( $data['terms'] ) ) {
		wp_redirect( add_query_arg( array(
			'message' => wpas_create_notification( __( 'You did not accept the terms and conditions.', 'wpas' ) ),
			get_permalink( $post->ID )
		) ) );
		exit;
	}

	/* Make sure we have all the necessary data. */
	if ( false === ( $email || $first_name || $last_name || $pwd ) ) {
		wp_redirect( add_query_arg( array(
			'message' => wpas_create_notification( __( 'You didn\'t correctly fill all the fields.', 'wpas' ) ),
			get_permalink( $post->ID )
		) ) );
		exit;
	}

	$username = sanitize_user( strtolower( $first_name ) . strtolower( $last_name ) );
	$user     = get_user_by( 'login', $username );

	/* Check for existing username */
	if ( is_a( $user, 'WP_User' ) ) {
		$suffix = 1;
		do {
			$alt_username = sanitize_user( $username . $suffix );
			$user         = get_user_by( 'login', $alt_username );
			$suffix ++;
		} while ( is_a( $user, 'WP_User' ) );
		$username = $alt_username;
	}

	/**
	 * wpas_insert_user_data filter
	 *
	 * @since  3.1.5
	 * @var    array User account arguments
	 */
	$args = apply_filters( 'wpas_insert_user_data', array(
		'user_login'   => $username,
		'user_email'   => $email,
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'display_name' => "$first_name $last_name",
		'user_pass'    => $pwd,
		'role'         => 'wpas_user'
	) );

	/**
	 * wpas_register_account_before hook
	 *
	 * Fired right before the user is added to the database.
	 */
	do_action( 'wpas_register_account_before', $args );

	$user_id = wp_insert_user( apply_filters( 'wpas_user_registration_data', $args ) );

	if ( is_wp_error( $user_id ) ) {

		/**
		 * wpas_register_account_before hook
		 *
		 * Fired right after a failed attempt to register a user.
		 *
		 * @since  3.0.1
		 */
		do_action( 'wpas_register_account_failed', $user_id, $args );

		$error = $user_id->get_error_message();
		wp_redirect( add_query_arg( array(
			'message' => wpas_create_notification( $error ),
			get_permalink( $post->ID )
		) ) );
		exit;

	} else {

		/**
		 * wpas_register_account_before hook
		 *
		 * Fired right after the user is successfully added to the database.
		 *
		 * @since  3.0.1
		 */
		do_action( 'wpas_register_account_after', $user_id, $args );

		/* Delete the user information data from session. */
		unset( $_SESSION['wpas_registration_form'] );

		wp_new_user_notification( $user_id, $pwd );

		if ( headers_sent() ) {
			wp_redirect( add_query_arg( array(
				'message' => wpas_create_notification( __( 'Your account has been created. Please log-in.', 'wpas' ) ),
				get_permalink( $post->ID )
			) ) );
			exit;
		}

		if ( ! is_user_logged_in() ) {

			/* Automatically log the user in */
			wp_set_current_user( $user_id, $email );
			wp_set_auth_cookie( $user_id );

			wp_redirect( get_permalink( $post->ID ) );
			exit;
		}

	}

}

/**
 * Get temporary user data.
 *
 * If the user registration fails some of the user data is saved
 * (all except the password) and can be used to pre-populate the registration
 * form after the page reloads. This function returns the desired field value
 * if any.
 *
 * @since  3.0.0
 *
 * @param  string $field Name of the field to get the value for
 *
 * @return string        The sanitized field value if any, an empty string otherwise
 */
function wpas_get_registration_field_value( $field ) {

	if ( isset( $_SESSION ) && isset( $_SESSION['wpas_registration_form'][ $field ] ) ) {
		return sanitize_text_field( $_SESSION['wpas_registration_form'][ $field ] );
	} else {
		return '';
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

		$login = wp_signon();

		if ( is_wp_error( $login ) ) {
			$error = $login->get_error_message();
			wp_redirect( add_query_arg( array( 'message' => urlencode( base64_encode( json_encode( $error ) ) ) ), get_permalink( $post->ID ) ) );
			exit;
		} elseif ( is_a( $login, 'WP_User' ) ) {
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
 *
 * @param  integer $post_id ID of the post to display
 *
 * @return boolean
 */
function wpas_can_view_ticket( $post_id ) {

	/* Only logged in users can view */
	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( ! current_user_can( 'view_ticket' ) ) {
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
 *
 * @param  boolean $admins_allowed Shall admins/agents be allowed to reply from the frontend
 * @param  integer $post_id        ID of the ticket to check
 *
 * @return boolean                 True if the user can reply
 */
function wpas_can_reply_ticket( $admins_allowed = false, $post_id = null ) {

	if ( is_null( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}

	$admins_allowed = apply_filters( 'wpas_can_agent_reply_frontend', $admins_allowed ); /* Allow admins to post through front-end. The filter overwrites the function parameter. */
	$post           = get_post( $post_id );
	$author_id      = $post->post_author;

	if ( is_user_logged_in() ) {

		global $current_user;

		if ( ! current_user_can( 'reply_ticket' ) ) {
			return false;
		}

		$user_id = $current_user->data->ID;

		/* If the current user is the author then yes */
		if ( $user_id == $author_id ) {
			return true;
		} else {

			if ( current_user_can( 'edit_ticket' ) && true === $admins_allowed ) {
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
 *
 * @param  string $role User role
 *
 * @return string       Nicely formatted user role
 */
function wpas_get_user_nice_role( $role ) {

	/* Remove the prefix on WPAS roles */
	if ( 'wpas_' === substr( $role, 0, 5 ) ) {
		$role = substr( $role, 5 );
	}

	/* Remove separators */
	$role = str_replace( array( '-', '_' ), ' ', $role );

	return ucwords( $role );

}

function wpas_can_submit_ticket() {

	$can = true;

	return apply_filters( 'wpas_can_submit_ticket', $can );

}

/**
 * Get a list of users that belong to the plugin.
 *
 * @param array $args Arguments used to filter the users
 *
 * @return array An array of users objects
 * @since 3.1.8
 */
function wpas_get_users( $args = array() ) {

	$defaults = array(
		'exclude'     => array(),
		'cap'         => '',
		'cap_exclude' => '',
	);

	/* The array where we save all users we want to keep. */
	$list = array();

	/* Merge arguments. */
	$args = wp_parse_args( $args, $defaults );

	/* Get the hash of the arguments that's used for caching the result. */
	$hash = substr( md5( serialize( $args ) ), 0, 10 ); // Limit the length of the hash in order to avoid issues with option_name being too long in the database (https://core.trac.wordpress.org/ticket/15058)

	/* Check if we have a result already cached. */
	$result = get_transient( "wpas_list_users_$hash" );

	/* If there is a cached result we return it and don't run the expensive query. */
	if ( false !== $result ) {
		if ( is_array( $result ) && isset( $result[0] ) && is_object( $result[0] ) && is_a( $result[0], 'WP_User' ) ) {
			delete_transient( "wpas_list_users_$hash" ); // Invalidate the previous cache
		} else {
			return apply_filters( 'wpas_get_users', get_users( array( 'include' => (array) $result ) ) );
		}
	}

	/* Get all WordPress users */
	$all_users = get_users();

	/**
	 * Store the selected user IDs for caching.
	 *
	 * On database with a lot of users, storing the entire WP_User
	 * object causes issues (eg. "Got a packet bigger than ‘max_allowed_packet’ bytes").
	 * In order to avoid that we only store the user IDs and then get the users list
	 * later on only including those IDs.
	 *
	 * @since 3.1.10
	 */
	$users_ids = array();

	/* Loop through the users list and filter them */
	foreach ( $all_users as $user ) {

		/* Check for required capability */
		if ( ! empty( $args['cap'] ) ) {
			if ( ! array_key_exists( $args['cap'], $user->allcaps ) ) {
				continue;
			}
		}

		/* Check for excluded capability */
		if ( ! empty( $args['cap_exclude'] ) ) {
			if ( array_key_exists( $args['cap_exclude'], $user->allcaps ) ) {
				continue;
			}
		}

		/* Maybe exclude this user from the list */
		if ( in_array( $user->ID, (array) $args['exclude'] ) ) {
			continue;
		}

		/* Now we add this user to our final list. */
		array_push( $list, $user );
		array_push( $users_ids, $user->ID );

	}

	/* Let's cache the result so that we can avoid running this query too many times. */
	set_transient( "wpas_list_users_$hash", $users_ids, apply_filters( 'wpas_list_users_cache_expiration', 60 * 60 * 24 ) );

	return apply_filters( 'wpas_get_users', $list );

}

/**
 * List users.
 *
 * Returns a list of users based on the required
 * capability. If the capability is "all", all site
 * users are returned.
 *
 * @param  string $cap Minimum capability the user must have to be added to the list
 *
 * @return array       A list of users
 * @since  3.0.0
 */
function wpas_list_users( $cap = 'all' ) {

	$list = array();

	/* List all users */
	$all_users = wpas_get_users( array( 'cap' => $cap ) );

	foreach ( $all_users as $user ) {
		$user_id          = $user->ID;
		$user_name        = $user->data->display_name;
		$list[ $user_id ] = $user_name;
	}

	return apply_filters( 'wpas_users_list', $list );

}

/**
 * Creates a dropdown list of users.
 *
 * @since  3.1.2
 * @param  array  $args Arguments
 * @return string       Users dropdown
 */
function wpas_users_dropdown( $args = array() ) {

	global $current_user, $post;

	$defaults = array(
		'name'           => 'wpas_user',
		'id'             => '',
		'class'          => '',
		'exclude'        => array(),
		'selected'       => '',
		'cap'            => '',
		'cap_exclude'    => '',
		'agent_fallback' => false,
		'please_select'  => false,
		'select2'        => false,
		'disabled'       => false,
	);

	$args = wp_parse_args( $args, $defaults );

	/* List all users */
	$all_users = wpas_get_users( array( 'cap' => $args['cap'], 'cap_exclude' => $args['cap_exclude'], 'exclude' => $args['exclude'] ) );

	/**
	 * We use a marker to keep track of when a user was selected.
	 * This allows for adding a fallback if nobody was selected.
	 * 
	 * @var boolean
	 */
	$marker = false;

	$options = '';

	/* The ticket is being created, use the current user by default */
	if ( ! empty( $args['selected'] ) ) {
		$user = get_user_by( 'id', intval( $args['selected'] ) );
		if ( false !== $user && ! is_wp_error( $user ) ) {
			$marker = true;
			$options .= "<option value='{$user->ID}' selected='selected'>{$user->data->display_name}</option>";
		}
	}

	foreach ( $all_users as $user ) {

		/* This user was already added, skip it */
		if ( ! empty( $args['selected'] ) && $user->ID === intval( $args['selected'] ) ) {
			continue;
		}

		$user_id       = $user->ID;
		$user_name     = $user->data->display_name;
		$selected_attr = '';

		if ( false === $marker ) {
			if ( false !== $args['selected'] ) {
				if ( ! empty( $args['selected'] ) ) {
					if ( $args['selected'] === $user_id ) {
						$selected_attr = 'selected="selected"';
					}
				} else {
					if ( isset( $post ) && $user_id == $post->post_author ) {
						$selected_attr = 'selected="selected"';
					}
				}
			}
		}

		/* Set the marker as true to avoid selecting more than one user */
		if ( ! empty( $selected_attr ) ) {
			$marker = true;
		}

		/* Output the option */
		$options .= "<option value='$user_id' $selected_attr>$user_name</option>";

	}

	/* In case there is no selected user yet we add the post author, or the currently logged user (most likely an admin) */
	if ( true === $args['agent_fallback'] && false === $marker ) {
		$fallback    = $current_user;
		$fb_selected = false === $marker ? 'selected="selected"' : '';
		$options .= "<option value='{$fallback->ID}' $fb_selected>{$fallback->data->display_name}</option>";
	}

	$contents = wpas_dropdown( wp_parse_args( $args, $defaults ), $options );

	return $contents;

}

/**
 * Display a dropdown of the support users.
 *
 * Wrapper function for wpas_users_dropdown where
 * the cap_exclude is set to exclude all users with
 * the capability to edit a ticket.
 *
 * @since  3.1.3
 * @param  array  $args Arguments
 * @return string       HTML dropdown
 */
function wpas_support_users_dropdown( $args = array() ) {
	$args['cap_exclude'] = 'edit_ticket';
	$args['cap']         = 'create_ticket';
	echo wpas_users_dropdown( $args );
}