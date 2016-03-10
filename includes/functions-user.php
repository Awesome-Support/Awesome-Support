<?php
add_action( 'wpas_do_register', 'wpas_register_account' );
/**
 * Register user account.
 *
 * This function is hooked onto wpas_do_register so that the registration process can be triggered
 * when the registration form is submitted.
 *
 * @param array $data User data
 *
 * @since  1.0.0
 * @return void
 */
function wpas_register_account( $data ) {

	// Get the redirect URL
	$redirect_to = home_url();

	if ( isset( $data['redirect_to'] ) ) {
		$redirect_to = wp_sanitize_redirect( $data['redirect_to'] ); // If a redirect URL is specified we use it
	} else {

		global $post;

		// Otherwise we try to get the URL of the originating page
		if ( isset( $post ) && $post instanceof WP_Post ) {
			$redirect_to = wp_sanitize_redirect( get_permalink( $post->ID ) );
		}

	}

	/* Make sure registrations are open */
	$registration = wpas_get_option( 'allow_registrations', 'allow' );

	if ( 'allow' !== $registration ) {
		wpas_add_error( 'registration_not_allowed', __( 'Registrations are currently not allowed.', 'awesome-support' ) );
		wp_redirect( $redirect_to );
		exit;
	}

	$email      = isset( $data['wpas_email'] ) && ! empty( $data['wpas_email'] ) ? sanitize_email( $data['wpas_email'] ) : false;
	$first_name = isset( $data['wpas_first_name'] ) && ! empty( $data['wpas_first_name'] ) ? sanitize_text_field( $data['wpas_first_name'] ) : false;
	$last_name  = isset( $data['wpas_last_name'] ) && ! empty( $data['wpas_last_name'] ) ? sanitize_text_field( $data['wpas_last_name'] ) : false;
	$pwd        = isset( $data['wpas_password'] ) && ! empty( $data['wpas_password'] ) ? $data['wpas_password'] : false;

	/**
	 * Give a chance to third-parties to add new checks to the account registration process
	 *
	 * @since 3.2.0
	 * @var bool|WP_Error
	 */
	$errors = apply_filters( 'wpas_register_account_errors', false, $first_name, $last_name, $email );

	if ( false !== $errors ) {

		$notice = implode( '\n\r', $errors->get_error_messages() );

		wpas_add_error( 'registration_error', $notice );
		wp_redirect( $redirect_to );

		exit;

	}

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
		wpas_add_error( 'accept_terms_conditions', __( 'You did not accept the terms and conditions.', 'awesome-support' ) );
		wp_redirect( $redirect_to );
		exit;
	}

	/* Make sure we have all the necessary data. */
	if ( false === ( $email || $first_name || $last_name || $pwd ) ) {
		wpas_add_error( 'missing_fields', __( 'You didn\'t correctly fill all the fields.', 'awesome-support' ) );
		wp_redirect( $redirect_to );
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

		wpas_add_error( 'missing_fields', $error );
		wp_redirect( $redirect_to );

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

		if ( true === apply_filters( 'wpas_new_user_notification', true ) ) {
			wp_new_user_notification( $user_id );
		}

		if ( headers_sent() ) {
			wpas_add_notification( 'account_created', __( 'Your account has been created. Please log-in.', 'awesome-support' ) );
			wp_redirect( $redirect_to );
			exit;
		}

		if ( ! is_user_logged_in() ) {

			/* Automatically log the user in */
			wp_set_current_user( $user_id, $email );
			wp_set_auth_cookie( $user_id );

			wp_redirect( $redirect_to );
			exit;
		}

	}

}

add_action( 'wpas_do_login', 'wpas_try_login' );
/**
 * Try to log the user in.
 *
 * This function is hooked onto wpas_do_login so that the login process can be triggered
 * when the login form is submitted.
 *
 * @since 2.0
 *
 * @param array $data Function arguments (the superglobal vars if the function is triggered by wpas_do_login)
 *
 * @return void
 */
function wpas_try_login( $data ) {

	// Get the redirect URL
	$redirect_to = home_url();

	if ( isset( $data['redirect_to'] ) ) {
		$redirect_to = wp_sanitize_redirect( $data['redirect_to'] ); // If a redirect URL is specified we use it
	} else {

		global $post;

		// Otherwise we try to get the URL of the originating page
		if ( isset( $post ) && $post instanceof WP_Post ) {
			$redirect_to = wp_sanitize_redirect( get_permalink( $post->ID ) );
		}

	}

	/**
	 * Try to log the user if credentials are submitted.
	 */
	if ( isset( $data['wpas_log'] ) ) {

		$credentials = array(
				'user_login' => $data['wpas_log'],
		);

		if ( isset( $data['rememberme'] ) ) {
			$credentials['remember'] = true;
		}

		$credentials['user_password'] = isset( $data['wpas_pwd'] ) ? $data['wpas_pwd'] : '';

		/**
		 * Give a chance to third-parties to add new checks to the login process
		 *
		 * @since 3.2.0
		 * @var bool|WP_Error
		 */
		$login = apply_filters( 'wpas_try_login', false );

		if ( is_wp_error( $login ) ) {
			$error = $login->get_error_message();
			wpas_add_error( 'login_failed', $error );
			wp_redirect( $redirect_to );
			exit;
		}

		$login = wp_signon( $credentials );

		if ( is_wp_error( $login ) ) {
			$error = $login->get_error_message();
			wpas_add_error( 'login_failed', $error );
			wp_redirect( $redirect_to );
			exit;
		} elseif ( $login instanceof WP_User ) {
			wp_redirect( $redirect_to );
			exit;
		} else {
			wpas_add_error( 'login_failed', __( 'We were unable to log you in for an unknown reason.', 'awesome-support' ) );
			wp_redirect( $redirect_to );
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

	/**
	 * Set the return value to false by default to avoid giving unwanted access.
	 */
	$can = false;

	/**
	 * Get the post data.
	 */
	$post      = get_post( $post_id );
	$author_id = intval( $post->post_author );

	if ( is_user_logged_in() ) {
		if ( get_current_user_id() === $author_id && current_user_can( 'view_ticket' ) || current_user_can( 'edit_ticket' ) ) {
			$can = true;
		}
	}

	return apply_filters( 'wpas_can_view_ticket', $can, $post_id, $author_id );

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

/**
 * Check if the current user has the permission to open a ticket
 *
 * If a ticket ID is given we make sure the ticket author is the current user.
 * This is used for checking if a user can re-open a ticket.
 *
 * @param int $ticket_id
 *
 * @return bool
 */
function wpas_can_submit_ticket( $ticket_id = 0 ) {

	$can = false;

	if ( is_user_logged_in() ) {

		if ( current_user_can( 'create_ticket' ) ) {
			$can = true;
		}

		if ( 0 !== $ticket_id ) {

			$ticket = get_post( $ticket_id );

			if ( is_object( $ticket ) && is_a( $ticket, 'WP_Post' ) && get_current_user_id() !== (int) $ticket->post_author ) {
				$can = false;
			}

		}

	}

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
		return apply_filters( 'wpas_get_users', get_users( array( 'include' => (array) $result ) ) );
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
			if ( ! user_can( $user, $args['cap'] ) ) {
				continue;
			}
		}

		/* Check for excluded capability */
		if ( ! empty( $args['cap_exclude'] ) ) {
			if ( user_can( $user, $args['cap_exclude'] ) ) {
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

add_action( 'user_register',  'wpas_clear_get_users_cache' );
add_action( 'delete_user',    'wpas_clear_get_users_cache' );
add_action( 'profile_update', 'wpas_clear_get_users_cache' );
/**
 * Clear all the users lists transients
 *
 * If a new admin / agent is added, deleted or edited while the users list transient
 * is still valid then the user won't appear / disappear from the users lists
 * until the transient expires. In order to avoid this issue we clear the transients
 * when one of the above actions is executed.
 *
 * @since 3.2.0
 * @return void
 */
function wpas_clear_get_users_cache() {

	global $wpdb;

	$wpdb->get_results( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE '%s'", '_transient_wpas_list_users_%' ) );

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

/**
 * Wrapper function to easily get a user tickets
 *
 * This function is a wrapper for wpas_get_user_tickets() with the user ID preset
 *
 * @since 3.2.2
 *
 * @param int    $user_id
 * @param string $ticket_status
 * @param string $post_status
 *
 * @return array
 */
function wpas_get_user_tickets( $user_id = 0, $ticket_status = 'open', $post_status = 'any' ) {

	if ( 0 === $user_id ) {
		$user_id = get_current_user_id();
	}

	$args = array(
		'author' => $user_id,
	);

	$tickets = wpas_get_tickets( $ticket_status, $args, $post_status );

	return $tickets;

}

add_filter( 'authenticate', 'wpas_email_signon', 20, 3 );
/**
 * Allow e-mail to be used as the login.
 *
 * @since  3.0.2
 *
 * @param  WP_User|WP_Error|null $user     User to authenticate.
 * @param  string                $username User login
 * @param  string                $password User password
 *
 * @return object                          WP_User if authentication succeed, WP_Error on failure
 */
function wpas_email_signon( $user, $username, $password ) {

	/* Authentication was successful, we don't touch it */
	if ( is_object( $user ) && is_a( $user, 'WP_User' ) ) {
		return $user;
	}

	/**
	 * If the $user isn't a WP_User object nor a WP_Error
	 * we don' touch it and let WordPress handle it.
	 */
	if ( ! is_wp_error( $user ) ) {
		return $user;
	}

	/**
	 * We only wanna alter the authentication process if the username was rejected.
	 * If the error is different, we let WordPress handle it.
	 */
	if ( 'invalid_username' !== $user->get_error_code() ) {
		return $user;
	}

	/**
	 * If the username is not an e-mail there is nothing else we can do,
	 * the error is probably legitimate.
	 */
	if ( ! is_email( $username ) ) {
		return $user;
	}

	/* Try to get the user with this e-mail address */
	$user_data = get_user_by( 'email', $username );

	/**
	 * If there is no user with this e-mail the error is legitimate
	 * so let's just return it.
	 */
	if ( false === $user_data || ! is_a( $user_data, 'WP_User' ) ) {
		return $user;
	}

	return wp_authenticate_username_password( null, $user_data->data->user_login, $password );

}

add_action( 'wp_ajax_nopriv_email_validation', 'wpas_mailgun_check' );
/**
 * Check if an e-mail is valid during registration using the MailGun API
 *
 * @param string $data
 */
function wpas_mailgun_check( $data = '' ) {

	if ( empty( $data ) ) {
		if ( isset( $_POST ) ) {
			$data = $_POST;
		} else {
			echo '';
			die();
		}
	}

	if ( ! isset( $data['email'] ) ) {
		echo '';
		die();
	}

	$mailgun = new WPAS_MailGun_EMail_Check();
	$check   = $mailgun->check_email( $data );

	if ( ! is_wp_error( $check ) ) {

		$check = json_decode( $check );

		if ( is_object( $check ) && isset( $check->did_you_mean ) && ! is_null( $check->did_you_mean ) ) {
			printf( __( 'Did you mean %s', 'awesome-support' ), "<strong>{$check->did_you_mean}</strong>?" );
			die();
		}

	}

	die();

}