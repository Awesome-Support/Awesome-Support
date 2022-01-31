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

	if ( 'allow' !== $registration && 'moderated' !== $registration ) {
		wpas_add_error( 'registration_not_allowed', __( 'Registrations are currently not allowed.', 'awesome-support' ) );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	// Prepare user data
	$user = array(
		'email'      => isset( $data['wpas_email'] ) ? $data['wpas_email'] : '',
		'first_name' => isset( $data['wpas_first_name'] ) ? $data['wpas_first_name'] : '',
		'last_name'  => isset( $data['wpas_last_name'] ) ? $data['wpas_last_name'] : '',
		'pwd'        => isset( $data['wpas_password'] ) ? $data['wpas_password'] : '',
	);

	/**
	 * wpas_pre_register_account hook
	 *
	 * This hook is triggered all the time
	 * even if the checks don't pass.
	 *
	 * @since  3.0.1
	 */
	do_action( 'wpas_pre_register_account', $user, $redirect_to, $data );

	if ( wpas_get_option( 'terms_conditions', false ) && ( ! isset( $data['wpas_terms'] ) || $data['wpas_terms'][0] != "1" ) ) {
		wpas_add_error( 'accept_terms_conditions', esc_html__( 'You did not accept the terms and conditions.', 'awesome-support' ) );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	if ( wpas_get_option( 'gdpr_notice_short_desc_01', false ) && wpas_get_option( 'gdpr_notice_mandatory_01', true) && ! isset( $data['wpas_gdpr01'] ) ) {
		wpas_add_error( 'accept_gdpr01_conditions', sprintf( __( 'You must check the <b>%s</b> box in order to register a support account on this site.', 'awesome-support' ), esc_html( wpas_get_option( 'gdpr_notice_short_desc_01', false ) ) ) );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	if ( wpas_get_option( 'gdpr_notice_short_desc_02', false ) && wpas_get_option( 'gdpr_notice_mandatory_02', true)  && ! isset( $data['wpas_gdpr02'] ) ) {
		wpas_add_error( 'accept_gdpr02_conditions', sprintf( __( 'You must check the <b>%s</b> box in order to register a support account on this site.', 'awesome-support' ), esc_html( wpas_get_option( 'gdpr_notice_short_desc_02', false ) ) ) );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	if ( wpas_get_option( 'gdpr_notice_short_desc_03', false ) && wpas_get_option( 'gdpr_notice_mandatory_03', true)  && ! isset( $data['wpas_gdpr03'] ) ) {
		wpas_add_error( 'accept_gdpr03_conditions', sprintf( __( 'You must check the <b>%s</b> box in order to register a support account on this site.', 'awesome-support' ), esc_html( wpas_get_option( 'gdpr_notice_short_desc_03', false ) ) ) );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * wpas_register_account_before hook
	 *
	 * Fired right before the user is added to the database.
	 */
	do_action( 'wpas_register_account_before', $user );

	// Try and insert the new user in the database
	$user_id = wpas_insert_user( $user );

	if ( is_wp_error( $user_id ) ) {

		/**
		 * wpas_register_account_before hook
		 *
		 * Fired right after a failed attempt to register a user.
		 *
		 * @since  3.0.1
		 */
		do_action( 'wpas_register_account_failed', $user_id, $user );

		$errors = implode( '<br>', $user_id->get_error_messages() );

		wpas_add_error( 'missing_fields', $errors );
		wp_safe_redirect( $redirect_to );

		exit;

	} else {

		// Mark new user if registration type is moderated
		if( 'moderated' === $registration ) {
			update_user_option( $user_id, 'mr_user_not_activated', 'yes' );
		}

		/**
		 * Record Term and Conditions consent
		 */
		if ( wpas_get_option( 'terms_conditions', false ) ) {
			$status = isset( $data['wpas_terms'] ) ? isset( $data['wpas_terms'] ) : "";
			$opt_in = ! empty ( $status ) ? strtotime( 'NOW' ) : "";

			wpas_track_consent( array(
				'item' 		=> wpas_get_option( 'terms_conditions', false ),
				'status' 	=> $status,
				'opt_in' 	=> $opt_in,
				'opt_out' 	=> "",
				'is_tor'	=> true
			), $user_id );
		}

		/**
		 * Record GDPR 1 consent
		 */
		if ( wpas_get_option( 'gdpr_notice_short_desc_01', false ) ) {
			$status 	= isset( $data['wpas_gdpr01'] ) ? isset( $data['wpas_gdpr01'] ) : "";
			$opt_in 	= ! empty ( $status ) ? strtotime( 'NOW' ) : "";
			$opt_out 	= empty ( $opt_in ) ? strtotime( 'NOW' ) : "";

			wpas_track_consent( array(
				'item' 		=> wpas_get_option( 'gdpr_notice_short_desc_01', false ),
				'status' 	=> $status,
				'opt_in' 	=> $opt_in,
				'opt_out' 	=> '',
				'is_tor'	=> false
			), $user_id );
		}

		/**
		 * Record GDPR 2 consent
		 */
		if ( wpas_get_option( 'gdpr_notice_short_desc_02', false ) ) {
			$status 	= isset( $data['wpas_gdpr02'] ) ? isset( $data['wpas_gdpr02'] ) : "";
			$opt_in 	= ! empty ( $status ) ? strtotime( 'NOW' ) : "";
			$opt_out 	= empty ( $opt_in ) ? strtotime( 'NOW' ) : "";

			wpas_track_consent( array(
				'item' 		=> wpas_get_option( 'gdpr_notice_short_desc_02', false ),
				'status' 	=> $status,
				'opt_in' 	=> $opt_in,
				'opt_out' 	=> '',
				'is_tor'	=> false
			), $user_id );
		}

		/**
		 * Record GDPR 3 consent
		 */
		if ( wpas_get_option( 'gdpr_notice_short_desc_03', false ) ) {
			$status 	= isset( $data['wpas_gdpr03'] ) ? isset( $data['wpas_gdpr03'] ) : "";
			$opt_in 	= ! empty ( $status ) ? strtotime( 'NOW' ) : "";
			$opt_out 	= empty ( $opt_in ) ? strtotime( 'NOW' ) : "";

			wpas_track_consent( array(
				'item' 		=> wpas_get_option( 'gdpr_notice_short_desc_03', false ),
				'status' 	=> $status,
				'opt_in' 	=> $opt_in,
				'opt_out' 	=> '',
				'is_tor'	=> false
			), $user_id );
		}

		/**
		 * wpas_register_account_before hook
		 *
		 * Fired right after the user is successfully added to the database.
		 *
		 * @since  3.0.1
		 */
		do_action( 'wpas_register_account_after', $user_id, $user, $data );

		// For moderated registration print message and redirect, so we don't auto login.
		if( 'moderated' === $registration ) {
			update_user_option( $user_id, 'mr_user_not_activated', 'yes' );

			wpas_add_notification( 'moderated_account_created', esc_html( wpas_get_option( 'mr_success_message' ) ) );
			wp_safe_redirect( $redirect_to );
			exit;
		}

		if ( headers_sent() ) {
			wpas_add_notification( 'account_created', esc_html__( 'Your account has been created. Please log-in.', 'awesome-support' ) );
			wp_safe_redirect( $redirect_to );
			exit;
		}

		if ( ! is_user_logged_in() ) {

			/* Automatically log the user in */
			wp_set_current_user( $user_id, get_user_by( 'ID', $user_id )->data->user_email );
			wp_set_auth_cookie( $user_id );

			wp_safe_redirect( $redirect_to );
			exit;
		}

	}

}

/**
 * Insert a new Awesome Support user in the WordPress users table
 *
 * @since 3.3.2
 *
 * @param array $data   The user data to insert
 * @param bool  $notify Whether or not to send a notification e-mail to the newly created user
 *
 * @return int|WP_Error The new user ID or an error object on failure
 */
function wpas_insert_user( $data = array(), $notify = true ) {

	// Set the default and required user info
	$defaults = apply_filters( 'wpas_insert_user_default_args', array(
		'email'      => '',
		'first_name' => '',
		'last_name'  => '',
		'pwd'        => '',
	) );

	// Set our user ID to false in the beginning
	$user_id = false;

	// Set our final user data array
	$user = apply_filters( 'wpas_insert_user_args', array_merge( $defaults, $data ) );

	// Now we need to make sure that all the required fields are filled before creating the user
	foreach ( $defaults as $field => $value ) {

		if ( empty( $user[ $field ] ) ) {

			// Create the WP_Error object if it doesn't exist yet
			if ( false === $user_id ) {
				$user_id = new WP_Error();
			}

			// Add a new error to the object
			$user_id->add( 'missing_field_' . $field, sprintf( esc_html__( 'The %s field is mandatory for registering an account', 'awesome-support' ), ucwords( str_replace( '_', ' ', $field ) ) ) );

		}

	}

	// Only proceed with the insertion process if there is no error so far
	if ( false === $user_id ) {

		// Now that we know we have all the minimum required information, let's sanitize the user input
		foreach ( $user as $field => $value ) {

			switch ( $field ) {

				case 'email':
					$user[ $field ] = sanitize_email( $value );
					break;

				case 'pwd':
					$user[ $field ] = $value; // No sanitization of the password
					break;

				default:
					$user[ $field ] = sanitize_text_field( $value );
					break;

			}

		}

		// Let's create the user username and make sure it's unique
		if ( isset( $data['user_login'] ) ) {
			$username = $data['user_login'];
			$username = wpas_check_duplicate_user_name( $username ) ;
		} else {
			$username = wpas_create_user_name( $user ) ;  // This function will create a user name AND automatically check and fix duplicates
		}

		$registration_type = wpas_get_option( 'allow_registrations', 'allow' );
		$new_user_role = 'moderated' === $registration_type ? wpas_get_option( 'moderated_pending_user_role' ) : wpas_get_option( 'new_user_role', 'wpas_user' );

		/**
		 * wpas_insert_user_data filter
		 *
		 * @since  3.1.5
		 * @var    array User account arguments
		 */
		$args = apply_filters( 'wpas_insert_user_data', array(
			'user_login'   => $username,
			'user_email'   => $user['email'],
			'first_name'   => $user['first_name'],
			'last_name'    => $user['last_name'],
			'display_name' => "{$user['first_name']} {$user['last_name']}",
			'user_pass'    => $user['pwd'],
			'role'         => $new_user_role,
		) );

		/**
		 * Give a chance to third-parties to add new checks to the account registration process
		 *
		 * @since 3.2.0
		 * @var false|WP_Error
		 */
		$user_id = apply_filters( 'wpas_register_account_errors', $user_id, $args['first_name'], $args['last_name'], $args['user_email'] );

		if ( ! is_wp_error( $user_id ) ) {

			/**
			 * wpas_register_account_before hook
			 *
			 * Fired right before the user is added to the database.
			 */
			do_action( 'wpas_insert_user_before', $args );

			$user_id = wp_insert_user( $args );

			/**
			 * Fire up another hook after the user has been inserted
			 *
			 * @since 3.3.2
			 *
			 * @param int|WP_Error $user_id The user ID or a WP_Error object
			 * @param array        $args    The user data
			 */
			do_action( 'wpas_insert_user_after', $user_id, $args );

			// Notify the new user if needed
			if ( ! is_wp_error( $user_id ) && true === apply_filters( 'wpas_new_user_notification', $notify ) ) {

				$receive_alert = wpas_get_option('reg_notify_users', 'both');  // Who should receive alerts?

				if ( 'none' <> $receive_alert ) {
					wp_new_user_notification( $user_id, null, $receive_alert );
				}

			}

		}

	}

	return $user_id;

}

/**
 * Create the user name for a user being added
  *
 * @since 4.4.0
 *
 * @param array $user_args An array that contains the current user information
 *
 * @return string username
 */
function wpas_create_user_name( $user_args ) {

	$name_ary = explode( '@', $user_args['email'] ); 	// extract whatever name we can from the email address...

	$user_name_construction = (int) wpas_get_option( 'reg_user_name_construction', 6 );	// get setting for how user name is to be constructed...

	$user_name = '' ; // initialize the user name variable...

	switch ( $user_name_construction ) {
		case 0 :
			// use the first part of the email address
			$user_name  = strtolower( $name_ary[0] );
			break;

		case 1:
			// use the full email address
			$user_name = strtolower( $user_args['email'] );
			break;

		case 2:
			// use a random number
			$user_name = mt_rand();
			break;

		case 3:
			// use a guid
			$user_name = wpas_create_pseudo_guid();
			break;

		case 4:
			// user the first name
			$user_name = strtolower( $user_args['first_name'] );
			break ;

		case 5:
			// user the last name
			$user_name = strtolower( $user_args['last_name'] );
			break ;

		case 6:
			// user the first and last name name
			$user_name = strtolower( $user_args['first_name'] . $user_args['last_name'] );
			break ;

		default:
			$user_name = $user_args['first_name'] . $user_args['last_name'] ;
			break;
	}

	// Now verify that the selected username is not already in use.
	// If it is, append a postfix and return it.
	return wpas_check_duplicate_user_name( $user_name );

}

/**
 * Check to see if a username is a duplicate
 *
 * If the user name is a duplicate, append a postfix and return it.
 *
 * @since 4.4.0
 *
 * @param string $user_name
 *
 * @return string username
 */
function wpas_check_duplicate_user_name( $user_name ) {

	$user_check = get_user_by( 'login', $user_name );

	if ( is_a( $user_check, 'WP_User' ) ) {
		$suffix = 1;
		do {
			$alt_username = sanitize_user( $user_name . $suffix );
			$user_check   = get_user_by( 'login', $alt_username );
			$suffix ++;
		} while ( is_a( $user_check, 'WP_User' ) );
		$user_name = $alt_username;
	}

	return $user_name ;

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

	/**
	 * Try to log the user if credentials are submitted.
	 */
	if ( isset( $data['wpas_log'] ) ) {

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
			wp_safe_redirect( $redirect_to );
			exit;
		}

		$login = wp_signon( $credentials );

		if ( is_wp_error( $login ) ) {

			$code = $login->get_error_code();
			$error = $login->get_error_message();

			// Pre-populate the user login if the problem is with the password
			if ( 'incorrect_password' === $code ) {
				$redirect_to = add_query_arg( 'wpas_log', $credentials['user_login'], $redirect_to );
			}

			wpas_add_error( 'login_failed', $error );
			wp_safe_redirect( $redirect_to );
			exit;

		} elseif ( $login instanceof WP_User ) {

			$user_not_activated = get_user_option( 'mr_user_not_activated', $login->ID );
			// Logout if user is not activated and print message
			if( 'yes' === $user_not_activated ) {
				wp_logout();
				wpas_add_error( 'login_not_activated', __( 'Your account is not activated yet. Try again later', 'awesome-support' ) );
				wp_safe_redirect( $redirect_to );
				exit;
			}

			// Filter to allow redirection of successful login
			$redirect_to = apply_filters( 'wpas_try_login_redirect', $redirect_to, $redirect_to, $login );

			wp_safe_redirect( $redirect_to );
			exit;

		} else {
			wpas_add_error( 'login_failed', __( 'We were unable to log you in for an unknown reason.', 'awesome-support' ) );
			wp_safe_redirect( $redirect_to );
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
	$post = get_post( $post_id );
	$author_id = null;

	if (!empty($post)) {

		/**
		 * Get author and agent ids on the ticket
		 */
		$author_id = intval( $post->post_author );

		if ( is_user_logged_in() ) {
			if (   ( get_current_user_id() === $author_id && current_user_can( 'view_ticket' ) )
				|| ( wpas_is_user_agent_on_ticket( $post_id ) && current_user_can( 'view_ticket' ) )
				|| wpas_can_user_see_all_tickets() ) {
				$can = true;
			}
		}

	}

	return apply_filters( 'wpas_can_view_ticket', $can, $post_id, $author_id );

}

/**
 * Check if user can see all tickets
 *
 * @global object $current_user
 * @return boolean
 */
function wpas_can_user_see_all_tickets() {

	$user_can_see_all = false;

	/* Check if admins can see all tickets */
	if ( wpas_is_asadmin() && true === (bool) wpas_get_option( 'admin_see_all' ) ) {
		$user_can_see_all = true;
	}

	/* Check if agents can see all tickets */
	if ( wpas_is_agent() && ! wpas_is_asadmin() && true === (bool) wpas_get_option( 'agent_see_all' ) ) {
		$user_can_see_all = true;
	}

	global $current_user;

	/* If current user can see all tickets */
	if ( current_user_can( 'view_all_tickets' ) || true === (bool) get_user_option( 'wpas_view_all_tickets', (int) $current_user->ID )  ) {
		$user_can_see_all = true;
	}

	return $user_can_see_all;
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
			// return false;
			return apply_filters( 'wpas_can_also_reply_ticket', false, $post_id, $author_id, 1 );
		}

		$user_id = $current_user->data->ID;

		/* If the current user is the author then yes */
		if ( $user_id == $author_id ) {
			// return true;
			return apply_filters( 'wpas_can_also_reply_ticket', true, $post_id, $author_id, 2 );
		} else {

			if ( current_user_can( 'edit_ticket' ) && true === $admins_allowed ) {
				// return true;
				return apply_filters( 'wpas_can_also_reply_ticket', true, $post_id, $author_id, 3 );
			} else {
				// return false;
				return apply_filters( 'wpas_can_also_reply_ticket', false, $post_id, $author_id, 4 );
			}

		}

	} else {
		// return false;
		return apply_filters( 'wpas_can_also_reply_ticket', false, $post_id, $author_id, 5 );
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

	/* Get first role if role is an array */
	if ( is_array($role) ) {
		// The two lines below have to be separate instead of embedded inside each other in order to avoid an "Only variables should be passed by reference" error
		$role = array_values( $role );
		$role = array_shift( $role ) ;
	}

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
 * @since 3.1.8
 *
 * @param array $args Arguments used to filter the users
 *
 * @return array An array of users objects
 */
function wpas_get_users( $args = array() ) {

	$defaults = array(
		'exclude'     => array(),
		'cap'         => '',
		'cap_exclude' => '',
		'orderby'	  => 'ID',
		'order'		  => 'ASC',
		'search'      => array(),
		'ids' 		  => array()
	);

	/* The array where we save all users we want to keep. */
	$list = array();

	/* Merge arguments. */
	$args  = wp_parse_args( $args, $defaults );
	$users = new WPAS_Member_Query( $args );

	return apply_filters( 'wpas_get_users', $users );

}

/**
 * Get all Awesome Support members
 *
 * @since 3.3
 * @return array
 */
function wpas_get_members() {

	global $wpdb;

	$query = $wpdb->get_results( "SELECT * FROM $wpdb->users WHERE 1 LIMIT 0, 2000" );

	if ( empty( $query ) ) {
		return $query;
	}

	return wpas_users_sql_result_to_wpas_member( $query );

}

/**
 * Get all Awesome Support members by their user ID
 *
 * @since 3.3
 *
 * @param $ids
 *
 * @return array
 */
function wpas_get_members_by_id( $ids ) {

	if ( ! is_array( $ids ) ) {
		$ids = (array) $ids;
	}

	// Prepare the IDs query var
	$ids = implode( ',', $ids );

	global $wpdb;

	$query = $wpdb->get_results( "SELECT * FROM $wpdb->users WHERE ID IN ('$ids')" );

	if ( empty( $query ) ) {
		return $query;
	}

	return wpas_users_sql_result_to_wpas_member( $query );

}

/**
 * Transform a users SQL query into WPAS_Member_User objects
 *
 * @param array  $results SQL results
 * @param string $class   The WPAS_Member subclass to use. Possible values are user and agent
 *
 * @return array
 */
function wpas_users_sql_result_to_wpas_member( $results, $class = 'user' ) {

	$users      = array();
	$class_name = '';

	switch ( $class ) {

		case 'user':
			$class_name = 'WPAS_Member_User';
			break;

		case 'agent':
			$class_name = 'WPAS_member_Agent';
			break;

	}

	if ( empty( $class_name ) ) {
		return array();
	}

	foreach ( $results as $user ) {

		$usr = new $class_name( $user );

		if ( true === $usr->is_member() ) {
			$users[] = $usr;
		}

	}

	return $users;

}

/**
 * Count the total number of users in the database
 *
 * @since 3.3
 * @return int
 */
function wpas_count_wp_users() {

	$count = get_transient( 'wpas_wp_users_count' );

	if ( false === $count ) {

		global $wpdb;

		$query = $wpdb->get_results( "SELECT ID FROM $wpdb->users WHERE 1" );
		$count = count( $query );

		set_transient( 'wpas_wp_users_count', $count, apply_filters( 'wpas_wp_users_count_transient_lifetime', 604800 ) ); // Default to 1 week

	}

	return $count;

}

/**
 * Check if the WP database has too many users or not
 *
 * @since 3.3
 * @return bool
 */
function wpas_has_too_many_users() {

	// We consider 3000 users to be too many to query at once
	$limit = apply_filters( 'wpas_has_too_many_users_limit', 3000 );

	if ( wpas_count_wp_users() > $limit ) {
		return true;
	}

	return false;

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

	foreach ( $all_users->members as $user ) {
		$user_id          = $user->ID;
		$user_name        = $user->display_name;
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
		'orderby'		 => 'ID',
		'order'			 => 'ASC',
		'agent_fallback' => false,
		'please_select'  => false,
		'select2'        => false,
		'disabled'       => false,
		'data_attr'      => array(),
		'ids'			 => array()
	);

	$args = wp_parse_args( $args, $defaults );

	/* List all users */
	$all_users = wpas_get_users( array( 'cap' => $args['cap'], 'cap_exclude' => $args['cap_exclude'], 'exclude' => $args['exclude'], 'ids' => $args['ids'], 'orderby' => $args['orderby'], 'order' => $args['order'] ) );

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

	foreach ( $all_users->members as $user ) {

		/* This user was already added, skip it */
		if ( ! empty( $args['selected'] ) && intval( $user->user_id ) === intval( $args['selected'] ) ) {
			continue;
		}

		$user_id       = $user->ID;
		$user_name     = $user->display_name;
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
			printf( wp_kses_post( __( 'Did you mean %s', 'awesome-support' ) ), "<strong>{$check->did_you_mean}</strong>?" );
			die();
		}

	}

	die();

}

add_action( 'wp_ajax_wpas_get_users', 'wpas_get_users_ajax',11,0 );
/**
 * Get AS users using Ajax
 *
 * @since 3.3
 *
 * @param array $args Query parameters
 *
 * @return void
 */
function wpas_get_users_ajax( $args = array() ) {

	$defaults = array(
		'cap'         => 'edit_ticket',
		'cap_exclude' => '',
		'exclude'     => '',
		'q'           => '', // The search query
	);

	if ( empty( $args ) ) {
		$args = array();
		foreach ( $defaults as $key => $value ) {
			if ( isset( $_POST[ $key ] ) ) {
				$args[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}
		}
	}

	$args = wp_parse_args( $args, $defaults );

	/**
	 * @var WPAS_Member_Query $users
	 */
	$users = wpas_get_users(
		array(
			'cap'         => array_map( 'sanitize_text_field', array_filter( (array) $args['cap'] ) ),
			'cap_exclude' => array_map( 'sanitize_text_field', array_filter( (array) $args['cap_exclude'] ) ),
			'exclude'     => array_map( 'intval', array_filter( (array) $args['exclude'] ) ),
			'search'      => array(
				'query'    => sanitize_text_field( $args['q'] ),
				'fields'   => array( 'user_nicename', 'display_name', 'id', 'user_email' ),
				'relation' => 'OR'
			)
		)
	);

	$result = array();

	foreach ( $users->members as $user ) {

		$result[] = array(
			'user_id'     => $user->ID,
			'user_name'   => $user->display_name,
			'user_email'  => $user->user_email,
			'user_avatar' => get_avatar_url( $user->ID, array( 'size' => 32, 'default' => 'mm' ) ),
		);

	}

	echo json_encode( $result );
	die();

}

/**
 * Check if a user has Smart Tickets Order
 *
 * Smart Tickets Order is a custom way to order tickets in the tickets list screen. This function checks if the current
 * agent has enabled this option. If not, tickets will be ordered the "WordPress way".
 *
 * @param int $user_id The user ID
 *
 * @return bool
 */
function wpas_has_smart_tickets_order( $user_id = 0 ) {

	// Set the value to false by default
	$value = false;

	if ( 0 === $user_id ) {
		$user_id = get_current_user_id();
	}

	// If the user is not an agent this is irrelevant. Just return false.
	if ( user_can( $user_id, 'edit_ticket' ) ) {

		$smart = esc_attr( get_user_option( 'wpas_smart_tickets_order', $user_id ) );

		if ( 'yes' === $smart ) {
			$value = true;
		}

	}

	return apply_filters( 'wpas_has_smart_tickets_order', $value, $user_id );

}

/**
 * return list of agents in a ticket
 * @param int $ticket_id
 * @param array $exclude
 * @return array
 */
function wpas_get_ticket_agents( $ticket_id = '' , $exclude = array() ) {

	$agent_ids = $agents = array();

	$primary_agent_id    = intval( get_post_meta( $ticket_id, '_wpas_assignee', true ) );
	if( $primary_agent_id && !in_array( $primary_agent_id, $exclude ) ) {
		$agent_ids[] = $primary_agent_id;
	}

	if( wpas_is_multi_agent_active() ) {
		$secondary_agent_id  = intval( get_post_meta( $ticket_id, '_wpas_secondary_assignee', true ) );
		$tertiary_agent_id   = intval( get_post_meta( $ticket_id, '_wpas_tertiary_assignee', true ) );
		if( $secondary_agent_id && !in_array( $secondary_agent_id, $exclude ) && !in_array( $secondary_agent_id, $agent_ids ) ) {
			$agent_ids[] = $secondary_agent_id;
		}

		if( $tertiary_agent_id && !in_array( $tertiary_agent_id, $exclude )  && !in_array( $tertiary_agent_id, $agent_ids ) ) {
			$agent_ids[] = $tertiary_agent_id;
		}
	}

	foreach ($agent_ids as $id) {
		$agents[] = get_user_by('id', $id);
	}

	return $agents;
}

/**
 * Log the user consent. Data saved in WP Option
 * We're not sure yet if custom table is needed but we can
 * extend in the future version when we see fit
 *
 * @param {*} label
 * @param {*} action
 * @param {*} date
 */
function wpas_log_consent( $user_id, $label, $action, $date = "", $user = "" ) {
	/**
	 * Label parameter is required, WP_Error if none given
	 */
	if( ! $label ) {
		return new WP_Error( 'consent_label_missing', __( 'Consent label is required!', 'awesome-support' ) );
	}
	if( ! $action ) {
		return new WP_Error( 'consent_action_missing', __( 'Consent action is required! Options are - "opted-in" or "opted-out"', 'awesome-support' ) );
	}

	/**
	 * If date is not given, set it today
	 */
	if( empty ( $date ) ) {
		$date = date( 'm/d/Y', strtotime( 'NOW' ) );
	}

	/**
	 * Determine user, we need to log when admin opt out as well
	 */
	if( empty ( $user ) ) {
		$user = __( 'user', 'awesome-support' );
	}

	/**
	 * Consent logs are stored in wpas_consent_log option
	 */
	$logged_consent = get_user_option( 'wpas_consent_log', $user_id );
	$consent = apply_filters( 'wpas_logged_consent_new', sprintf(
		'%s - %s %s %s %s',
		$label,
		$user,
		$action,
		__( 'on', 'awesome-support' ),
		$date
	) );

	if( ! empty ( $logged_consent ) && is_array( $logged_consent ) ) {
		update_user_option( $user_id, 'wpas_consent_log', array_merge( $logged_consent, array( $consent ) ) );
	}else{
		update_user_option( $user_id, 'wpas_consent_log', array( $consent ) );
	}

	/**
	 * After logging consent action hook
	 */
	do_action( 'wpas_log_consent_after', $user_id, $label, $action, $date , $user, $consent );

}

/**
 * Similar to wpas_log_consent()
 * This function tracks the consent instead of just
 * logging them. This is the primary function in consent
 * table information in both on user profile and on the
 * GDPR table in the front-end
 *
 * @param {*} data
 */
function wpas_track_consent( $data, $user_id, $opt_type = "" ){
	/**
	 * Consent logs are stored in wpas_consent_tracking option
	 */
	$tracked_consent = get_user_option( 'wpas_consent_tracking', $user_id );

	if( ! empty ( $tracked_consent ) && is_array( $tracked_consent ) ) {
		/**
		 * If same item exists, simply update the same row
		 * instead of merging them on new row
		 */
		$found_key = array_search( $data['item'], array_column( $tracked_consent, 'item' ) );
		if( $found_key !== false && ! empty ( $opt_type ) ) {
			/**
			 * We found something, update the row
			 */
			foreach( $tracked_consent as $key => $value ) {
				if( $found_key === $key ) {
					if( ! empty ( $data['opt_out'] ) ) {
						$tracked_consent[$found_key]['opt_out']= $data['opt_out'];
						$tracked_consent[$found_key]['opt_in'] = $data['opt_in'];
					}
					if( ! empty ( $data['opt_in'] ) ) {
						$tracked_consent[$found_key]['opt_in'] = $data['opt_in'];
						$tracked_consent[$found_key]['opt_out'] = $data['opt_out'];
					}
					$tracked_consent[$found_key]['status'] = $data['status'];
				}
			}
			update_user_option( $user_id, 'wpas_consent_tracking', $tracked_consent );

			/**
			 * After consent tracking update existing meta action hook
			 */
			do_action( 'wpas_track_consent_update_existing_after', $data, $user_id, $opt_type, $tracked_consent ) ;

		}else{
			update_user_option( $user_id, 'wpas_consent_tracking', array_merge( $tracked_consent, array( $data ) ) );
			/**
			 * After new consent tracking action hook
			 */
			do_action( 'wpas_track_consent_update_new_too', $data, $user_id, $opt_type, $tracked_consent ) ;
		}
	}else{
		update_user_option( $user_id, 'wpas_consent_tracking', array( $data ) );

		/**
		 * After new consent tracking action hook
		 */
		do_action( 'wpas_track_consent_update_new', $data, $user_id, $opt_type, $tracked_consent ) ;
	}
	/**
	 * After consent tracking action hook
	 */
	do_action( 'wpas_track_consent_after', $data, $user_id, $opt_type ) ;

}

add_action( 'wpas_register_account_after', 'wpas_moderated_registeration_notify', 11, 2 );

/**
 * Notify user and admin about moderated registration
 *
 * @param int $user_id
 * @param array $user
 */
function wpas_moderated_registeration_notify( $user_id, $user ) {


	$registration_type = wpas_get_option( 'allow_registrations', 'allow' );

	if( 'moderated' === $registration_type ) {

		$admin_email = get_bloginfo( 'admin_email' );

		$admin_notify = new WPAS_User_Email_Notification( $user_id, $admin_email );
		$admin_notify->notify( 'moderated_registration_admin' );


		$user_notify = new WPAS_User_Email_Notification( $user_id, $user['email'] );
		$user_notify->notify( 'moderated_registration_user' );
	}
}

/**
 * Return moderated registration notification cases
 *
 * @return array
 */
function wpas_mr_notification_cases() {

	return array(
		'moderated_registration_admin',
		'moderated_registration_user',
		'moderated_registration_approved_user',
		'moderated_registration_denied_user'
	);
}

add_filter( 'wpas__user_email_notifications_case_is_active', 'wpas_mr_enabled_email_notification_case', 11, 2 );

/**
 * Check if moderated registration notification is enabled
 *
 * @param boolean $enabled
 * @param string $case
 *
 * @return boolean
 */
function wpas_mr_enabled_email_notification_case( $enabled, $case ) {


	$cases = wpas_mr_notification_cases();

	if( in_array( $case, $cases ) ) {
		$enabled = wpas_get_option( "enable_{$case}_email", true );
	}

	return $enabled;
}



add_filter( 'wpas__user_email_notifications_pre_fetch_subject'  , 'wpas_registration_user_email_notifications_pre_fetch_subject' ,11, 3 );

/**
 * Set email subject for moderated registration notification
 *
 * @param string $subject
 * @param int $user_id
 * @param string $case
 *
 * @return string
 */
function wpas_registration_user_email_notifications_pre_fetch_subject( $subject, $user_id, $case ) {

	$subject = wpas_get_option( "{$case}_email__subject" );

	return $subject;
}

add_filter( 'wpas__user_email_notifications_pre_fetch_content'	, 'wpas_registration_user_email_notifications_pre_fetch_content' , 11, 3 );

/**
 * Set email content for moderated registration notification
 *
 * @param string $body
 * @param int $user_id
 * @param string $case
 *
 * @return string
 */
function wpas_registration_user_email_notifications_pre_fetch_content( $body, $user_id, $case ) {

	$body = wpas_get_option( "{$case}_email__content" );

	return $body;

}

add_action( 'edit_user_profile', 'wpas_add_activate_user_button' , 10, 1 ); // Display tickets on user profile page
add_action( 'show_user_profile', 'wpas_add_activate_user_button' , 9, 1 ); // Display tickets on user profile page

/**
 * Add activate user button on back-end edit user page
 *
 * @param object $user
 */
function wpas_add_activate_user_button( $user ) {

	$not_activated = get_user_option( 'mr_user_not_activated', $user->ID );
	$user_denied   = get_user_option( 'mr_user_denied', $user->ID );


	if( 'yes' === $not_activated && 'yes' !== $user_denied ) {

		$edit_user_link = add_query_arg( 'user_id', $user->ID, self_admin_url( 'user-edit.php' ) );
		$activate_url = wpas_do_url( $edit_user_link, 'mr_activate_user' );
		$deny_url = wpas_do_url( $edit_user_link, 'mr_deny_user' );

		printf( '<a href="%s" class="button button-primary">%s</a>', esc_url( $activate_url ), esc_html__( 'Activate User', 'awesome-support' ) );

		printf( '<a href="%s" class="button button-primary mr-deny-user-btn">%s</a>', esc_url( $deny_url ), esc_html__( 'Deny User', 'awesome-support' ) );

	} elseif( 'yes' === $user_denied ) {
		printf( '<div><p>%s</p></div>', esc_html__( 'User has been denied.', 'awesome-support' ) );
	}

}


add_action( 'wpas_do_mr_activate_user', 'wpas_do_mr_activate_user' );

/**
 * Activate moderated user
 *
 * @param array $data
 */
function wpas_do_mr_activate_user( $data ) {

	$user_id = $data['user_id'];

	if( $user_id ) {

		$role = wpas_get_option( 'moderated_activated_user_role' );

		$updated = wp_update_user( array( 'ID' => $user_id, 'role' => $role ) );

		if ( is_wp_error( $updated ) ) {
			$redirect_to = add_query_arg( array(
				'user_id'         => $user_id,
				'wpas-mr-message' => 'failed'
			), admin_url( 'user-edit.php' ) );
		} else {
			delete_user_option( $user_id, 'mr_user_not_activated' );

			// Notify to user
			$user = get_user_by( 'id', $user_id );
			$user_notify = new WPAS_User_Email_Notification( $user_id, $user->user_email );
			$user_notify->notify( 'moderated_registration_approved_user' );

			$redirect_to = add_query_arg( array(
				'user_id'         => $user_id,
				'wpas-mr-message' => 'success'
			), admin_url( 'user-edit.php' ) );
		}

		wpas_redirect( 'mr_activation', $redirect_to );
	}

}

add_action( 'wpas_do_mr_deny_user', 'wpas_do_mr_deny_user' );

/**
 * Deny moderated user registration
 *
 * @param array $data
 */
function wpas_do_mr_deny_user( $data ) {

	$user_id = $data['user_id'];

	if( $user_id ) {

		update_user_option( $user_id, 'mr_user_denied', 'yes' );

		// Notify to user
		$user = get_user_by( 'id', $user_id );
		$user_notify = new WPAS_User_Email_Notification( $user_id, $user->user_email );
		$user_notify->notify( 'moderated_registration_denied_user' );

		$redirect_to = add_query_arg( array(
			'user_id'         => $user_id,
			'wpas-mr-deny-message' => 'success'
		), admin_url( 'user-edit.php' ) );


		wpas_redirect( 'mr_activation', $redirect_to );
	}
}


add_action( 'admin_init', 'wpas_mr_activation_notices', 10, 0 );

/**
 * Register moderated user activation notices
 */
function wpas_mr_activation_notices() {

	if ( isset( $_GET['wpas-mr-message'] ) ) {

		$_SERVER['REQUEST_URI'] = remove_query_arg( 'wpas-mr-message' );

		if ( 'success' === $_GET['wpas-mr-message'] ) {
			add_action( 'admin_notices', 'wpas_mr_activation_success_notice' );
		} else {
			add_action( 'admin_notices', 'wpas_mr_activation_failed_notice' );
		}

	} elseif ( isset( $_GET['wpas-mr-deny-message'] ) ) {

		$_SERVER['REQUEST_URI'] = remove_query_arg( 'wpas-mr-deny-message' );

		if ( 'success' === $_GET['wpas-mr-deny-message'] ) {
			add_action( 'admin_notices', 'wpas_mr_deny_success_notice' );
		}

	}
}

/**
 * Print notice once a moderated user successfully activated
 */
function wpas_mr_activation_success_notice() {

	printf( '<div class="updated"><p>%s</p></div>', esc_html__( 'User successfully activated.', 'awesome-support' ) );

}

/**
 * Print notice once a moderated user activation failed
 */
function wpas_mr_activation_failed_notice() {

	printf( '<div class="updated error"><p>%s</p></div>', esc_html__( 'Error while activating user, try again later.', 'awesome-support' ) );

}

/**
 * Print notice once a moderated user registration denied
 */
function wpas_mr_deny_success_notice() {

	printf( '<div class="updated error"><p>%s</p></div>', esc_html__( 'User successfully denied.', 'awesome-support' ) );

}

/**
 * Takes a one dimensional array of user ids and returns an array of user objects
 */
function wpas_id_to_user_object( $user_ids ) {
	$user_objects = array();
	foreach ($user_ids as $id) {
		$user_objects[] = get_user_by('id', $id);
	}
	return $user_objects;
}
