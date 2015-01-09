<?php
/**
 * Open a new ticket.
 *
 * @since  3.0.0
 * @param  array $data Ticket data
 * @return boolean
 */
function wpas_open_ticket( $data ) {

	$title   = isset( $data['wpas_title'] ) ? wp_strip_all_tags( $data['wpas_title'] ) : false;
	$content = isset( $data['wpas_message'] ) ? wp_kses( $data['wpas_message'], wp_kses_allowed_html( 'post' ) ) : false;

	/**
	 * Prepare vars
	 */
	$errors  = array();                            // Error messages to display
	$notify  = array();                            // Notifications to trigger
	$missing = array();                            // Missing fields in the form
	$submit  = wpas_get_option( 'ticket_submit' ); // ID of the submission page

	// Verify the nonce first
	if ( !isset( $data['wpas_nonce'] ) || !wp_verify_nonce( $data['wpas_nonce'], 'new_ticket' ) ) {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wp_redirect( add_query_arg( array( 'message' => 4 ), get_permalink( $submit ) ) );

		// Break
		exit;
	}

	// Verify user capability
	if ( !current_user_can( 'create_ticket' ) ) {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wp_redirect( add_query_arg( array( 'message' => 11 ), get_permalink( $submit ) ) );

		// Break
		exit;
	}

	// Make sure we have at least a title and a message
	if ( false === $title || empty( $title ) ) {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wp_redirect( add_query_arg( array( 'message' => 3 ), get_permalink( $submit ) ) );

		// Break
		exit;
	}

	if ( true === ( $description_mandatory = apply_filters( 'wpas_ticket_submission_description_mandatory', true ) ) && ( false === $content || empty( $content ) ) ) {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wp_redirect( add_query_arg( array( 'message' => 10 ), get_permalink( $submit ) ) );

		// Break
		exit;

	}

	/**
	 * Allow the submission.
	 *
	 * This variable is used to add additional checks in the submission process.
	 * If the $go var is set to true, it gives a green light to this method
	 * and the ticket will be submitted. If the var is set to false, the process
	 * will be aborted.
	 *
	 * @since  3.0.0
	 */
	$go = apply_filters( 'wpas_before_submit_new_ticket_checks', true );

	/* Check for the green light */
	if ( is_wp_error( $go ) ) {

		/* Retrieve error messages. */
		$messages = $go->get_error_messages();

		/* Save the input */
		wpas_save_values();

		/* Redirect to submit page */
		wp_redirect( add_query_arg( array( 'message' => wpas_create_notification( $messages ), get_permalink( $submit ) ) ) );

		exit;

	}

	/**
	 * Gather current user info
	 */
	if( is_user_logged_in() ) {

		global $current_user;

		$user_id	= $current_user->ID;
		$user_name 	= $current_user->data->user_nicename;

	} else {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wp_redirect( add_query_arg( array( 'message' => 5 ), get_permalink( $submit ) ) );

		// Break
		exit;

	}

	/**
	 * Submit the ticket.
	 *
	 * Now that all the verifications are passed
	 * we can proceed to the actual ticket submission.
	 */
	$post = array(
		'post_content'   => $content,
		'post_name'      => $title,
		'post_title'     => $title,
		'post_status'    => 'queued',
		'post_type'      => 'ticket',
		'post_author'    => $user_id,
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
	);

	return wpas_insert_ticket( $post, false, false );
	
}

function wpas_insert_ticket( $data = array(), $post_id = false, $agent_id = false ) {

	if ( !current_user_can( 'create_ticket' ) ) {
		return false;
	}

	$update = false;

	/* If a post ID is passed we make sure the post actually exists before trying to update it. */
	if ( false !== $post_id ) {
		$post = get_post( intval( $post_id ) );

		if ( is_null( $post ) ) {
			return false;
		}

		$update = true;
	}

	$defaults = array(
		'post_content'   => '',
		'post_name'      => '',
		'post_title'     => '',
		'post_status'    => 'queued',
		'post_type'      => 'ticket',
		'post_author'    => '',
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
	);

	/* Add the post ID if this is an update. */
	if ( $update ) {
		$defaults['ID'] = $post_id;
	}

	/* Parse the input data. */
	$data = wp_parse_args( $data, $defaults );

	/* Sanitize the data */
	if ( isset( $data['post_title'] ) && !empty( $data['post_title'] ) ) {
		$data['post_title'] = wp_strip_all_tags( $data['post_title'] );
	}

	/**
	 * Filter the data right before inserting it in the post.
	 * 
	 * @var array
	 */
	$data = apply_filters( 'wpas_open_ticket_data', $data );

	if ( isset( $data['post_name'] ) && !empty( $data['post_name'] ) ) {
		$data['post_name'] = sanitize_text_field( $data['post_name'] );
	}

	/* Set the current user as author if the field is empty. */
	if ( empty( $data['post_author'] ) ) {
		global $current_user;
		$data['post_author'] = $current_user->ID;
	}

	/**
	 * Fire wpas_before_open_ticket just before the post is actually
	 * inserted in the database.
	 */
	do_action( 'wpas_open_ticket_before', $data, $post_id );

	/**
	 * Insert the post in database using the regular WordPress wp_insert_post
	 * function with default values corresponding to our post type structure.
	 * 
	 * @var boolean
	 */
	$ticket_id = wp_insert_post( $data, false );

	if ( false === $ticket_id ) {

		/**
		 * Fire wpas_open_ticket_failed if the ticket couldn't be inserted.
		 */
		do_action( 'wpas_open_ticket_failed', $data, $post_id );

		return false;

	}

	/* Set the ticket as open. */
	add_post_meta( $ticket_id, '_wpas_status', 'open', true );

	if ( false === $agent_id ) {
		$agent_id = wpas_find_agent( $ticket_id );
	}

	/* Assign an agent to the ticket */
	wpas_assign_ticket( $ticket_id, $agent_id, false );

	/**
	 * Fire wpas_after_open_ticket just after the post is successfully submitted.
	 */
	do_action( 'wpas_open_ticket_after', $ticket_id, $data );

	return $ticket_id;

}

/**
 * Get tickets.
 *
 * Get a list of tickets matching the arguments passed.
 * This function is basically a wrapper for WP_Query with
 * the addition of the ticket status.
 *
 * @since  3.0.0
 * @param  string $status Ticket status (open or closed)
 * @param  array  $args   Additional arguments (see WP_Query)
 * @return array          Array of tickets, empty array if no tickets found
 */
function get_tickets( $status = 'open', $args = array() ) {

	$defaults = array(
		'post_type'              => 'ticket',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'no_found_rows'          => false,
		'cache_results'          => true,
		'update_post_term_cache' => true,
		'update_post_meta_cache' => true,
	);

	$args  = wp_parse_args( $args, $defaults );

	if ( in_array( $status, array( 'open', 'closed' ) ) ) {
		$args['meta_query'][] = array(
			'key' => '_wpas_status',
			'value' => $status,
			'compare' => '='
		);
	}

	$query = new WP_Query( $args );

	if ( empty( $query->posts ) ) {
		return array();
	} else {
		return $query->posts;
	}
	

}

/**
 * Add a new reply to a ticket.
 * 
 * @return void
 */
function wpas_add_reply( $data, $parent_id = false, $author_id = false ) {

	global $current_user;

	if ( false === $parent_id ) {

		if ( isset( $data['parent_id'] ) ) {

			/* Get the parent ID from $data if not provided in the arguments. */
			$parent_id = intval( $data['parent_id'] );
			$parent    = get_post( $parent_id );

			/* Mare sure the parent exists. */
			if ( is_null( $parent ) ) {
				return false;
			}

		} else {
			return false;
		}

	}

	/**
	 * Submit the reply.
	 *
	 * Now that all the verifications are passed
	 * we can proceed to the actual ticket submission.
	 */
	$defaults = array(
		'post_content'   => '',
		'post_name'      => sprintf( __( 'Reply to ticket %s', 'wpas' ), "#$parent_id" ),
		'post_title'     => sprintf( __( 'Reply to ticket %s', 'wpas' ), "#$parent_id" ),
		'post_status'    => 'unread',
		'post_type'      => 'ticket_reply',
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
		'post_parent'    => $parent_id,
	);

	$data = wp_parse_args( $data, $defaults );

	if ( false !== $author_id ) {
		$defaults['post_author'] = $author_id;
	} else {
		global $current_user;
		$defaults['post_author'] = $current_user->ID;
	}

	$insert = wpas_insert_reply( $data, $parent_id );

	if ( $insert && user_can( $defaults['post_author'], 'edit_ticket' ) ) {
		$replies = wpas_get_replies( $parent_id );
		if ( 1 === count( $replies ) ) {
			wpas_update_ticket_status( $parent_id, 'processing' );
		}
	}

	return $insert;

}

function wpas_edit_reply( $reply_id = null, $content = '' ) {

	if ( is_null( $reply_id ) ) {
		if ( isset( $_POST['reply_id'] ) ) {
			$reply_id = intval( $_POST['reply_id'] );
		} else {
			return false;
		}
	}

	if ( empty( $content ) ) {
		if ( isset( $_POST['reply_content'] ) ) {
			$content = wp_kses( $_POST['reply_content'], wp_kses_allowed_html( 'post' ) );
		} else {
			return false;
		}
	}

	$reply = get_post( $reply_id );

	if ( is_null( $reply ) ) {
		return false;
	}

	$data = apply_filters( 'wpas_edit_reply_data', array(
		'ID'             => $reply_id,
		'post_content'   => $content,
		'post_status'    => 'read',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_date'      => $reply->post_date,
		'post_date_gmt'  => $reply->post_date_gmt,
		'post_name'      => $reply->post_name,
		'post_parent'    => $reply->post_parent,
		'post_type'      => $reply->post_type,
		'post_author'    => $reply->post_author,
		), $reply_id
	);

	$edited = wp_insert_post( $data, true );

	if ( is_wp_error( $edited ) ) {
		do_action( 'wpas_edit_reply_failed', $reply_id, $content, $edited );
		return $edited;
	}

	do_action( 'wpas_reply_edited', $reply_id );

	return $reply_id;

}

function wpas_mark_reply_read( $reply_id = null ) {

	if ( is_null( $reply_id ) ) {
		if ( isset( $_POST['reply_id'] ) ) {
			$reply_id = intval( $_POST['reply_id'] );
		} else {
			return false;
		}
	}

	$reply = get_post( $reply_id );

	if ( is_null( $reply ) ) {
		return false;
	}

	if ( 'read' === $reply->post_status ) {
		return $reply_id;
	}

	$data = apply_filters( 'wpas_mark_reply_read_data', array(
		'ID'             => $reply_id,
		'post_status'    => 'read',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_content'   => $reply->post_content,
		'post_date'      => $reply->post_date,
		'post_date_gmt'  => $reply->post_date_gmt,
		'post_name'      => $reply->post_name,
		'post_parent'    => $reply->post_parent,
		'post_type'      => $reply->post_type,
		'post_author'    => $reply->post_author,
		), $reply_id
	);

	$edited = wp_insert_post( $data, true );

	if ( is_wp_error( $edited ) ) {
		do_action( 'wpas_mark_reply_read_failed', $reply_id, $edited );
		return $edited;
	}

	do_action( 'wpas_marked_reply_read', $reply_id );

	return $edited;

}

function wpas_mark_reply_read_ajax() {
	
	$ID = wpas_mark_reply_read();

	if ( false === $ID || is_wp_error( $ID ) ) {
		$ID = $ID->get_error_message();
	}

	echo $ID;
	die();
}

function wpas_edit_reply_ajax() {
	
	$ID = wpas_edit_reply();

	if ( false === $ID || is_wp_error( $ID ) ) {
		$ID = $ID->get_error_message();
	}

	echo $ID;
	die();
}

/**
 * Insert a new reply.
 *
 * The function is basically a wrapper for wp_insert_post
 * with some additional checks and new default arguments
 * adapted to the needs of the ticket_reply post type.
 * If also gives some useful hooks at different steps of
 * the process.
 *
 * @since  3.0.0
 * @param  array  $data     Array of arguments for this reply
 * @param  boolean $post_id ID of the parent post
 * @return mixed            The reply ID on success or false on failure
 */
function wpas_insert_reply( $data, $post_id = false ) {

	if ( false === $post_id ) {
		return false;
	}

	if ( !current_user_can( 'reply_ticket' ) ) {
		return false;
	}

	$defaults = array(
		'post_name'      => sprintf( __( 'Reply to ticket %s', 'wpas' ), "#$post_id" ),
		'post_title'     => sprintf( __( 'Reply to ticket %s', 'wpas' ), "#$post_id" ),
		'post_content'   => '',
		'post_status'    => 'unread',
		'post_type'      => 'ticket_reply',
		'post_author'    => '',
		'post_parent'    => $post_id,
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
	);

	$data = wp_parse_args( $data, $defaults );

	/* Set the current user as author if the field is empty. */
	if ( empty( $data['post_author'] ) ) {
		global $current_user;
		$data['post_author'] = $current_user->ID;
	}

	$data = apply_filters( 'wpas_add_reply_data', $data, $post_id );

	/* Sanitize the data */
	if ( isset( $data['post_title'] ) && !empty( $data['post_title'] ) ) {
		$data['post_title'] = wp_strip_all_tags( $data['post_title'] );
	}

	if ( isset( $data['post_name'] ) && !empty( $data['post_name'] ) ) {
		$data['post_name'] = sanitize_title( $data['post_name'] );
	}

	/**
	 * Fire wpas_add_reply_before before the reply is added to the database.
	 */
	do_action( 'wpas_add_reply_before', $data, $post_id );

	$reply_id = wp_insert_post( $data, false );

	if ( false === $reply_id ) {

		/**
		 * Fire wpas_add_reply_failed if the reply couldn't be inserted.
		 */
		do_action( 'wpas_add_reply_failed', $data, $post_id );

		return false;

	}

	/**
	 * Fire wpas_add_reply_after after the reply was successfully added.
	 */
	do_action( 'wpas_add_reply_after', $reply_id, $data );

	return $reply_id;

}

function wpas_get_replies( $post_id, $status = 'any', $args = array() ) {

	$allowed_status = array(
		'any',
		'read',
		'unread'
	);

	if ( !in_array( $status, $allowed_status ) ) {
		$status = 'any';
	}

	$defaults = array(
		'post_parent'            => $post_id,
		'post_type'              => 'ticket_reply',
		'post_status'            => $status,
		'order'                  => 'DESC',
		'orderby'                => 'date',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);	

	$args = wp_parse_args( $args, $defaults );	
	
	$replies = new WP_Query( $args );

	if ( is_wp_error( $replies ) ) {
		return $replies;
	}
	
	return $replies->posts;

}

/**
 * Find an available agent to assign a ticket to.
 *
 * This is a super basic attribution system. It just finds the agent
 * with the less tickets currently open.
 *
 * @since  3.0.0
 * @param  integer $ticket The ticket that needs an agent
 * @return integer         ID of the best agent for the job
 */
function wpas_find_agent( $ticket_id = false ) {

	$users = get_users( array( 'orderby' => 'wpas_random' ) ); // We use a unique and non-existing orderby parameter so that we can identify the query in pre_user_query
	$agent = array();

	foreach ( $users as $user ) {

		if ( array_key_exists( 'edit_ticket', $user->allcaps ) ) {

			$posts_args = array(
				'post_type'              => 'ticket',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'cache_results'          => false,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => '_wpas_status',
						'value'   => 'open',
						'type'    => 'CHAR',
						'compare' => '='
					),
					array(
						'key'     => '_assigned_agent',
						'value'   => $user->ID,
						'type'    => 'NUMERIC',
						'compare' => '='
					),
				)
			);

			$open_tickets = new WP_Query( $posts_args );
			$count        = count( $open_tickets->posts ); // Total number of open tickets for this agent

			if ( empty( $agent ) ) {
				$agent = array( 'tickets' => $count, 'user_id' => $user->ID );
			} else {

				if ( $count < $agent['tickets'] ) {
					$agent = array( 'tickets' => $count, 'user_id' => $user->ID );
				}

			}
			
		}

	}

	return apply_filters( 'wpas_find_available_agent', $agent['user_id'], $ticket_id );

}

/**
 * Assign an agent to a ticket.
 *
 * Assign the given agent to a ticket or find an available
 * agent if no agent ID is given.
 *
 * @since  3.0.2
 * @param  integer  $ticket_id    ID of the post in need of a new agent
 * @param  integer  $agent_id     ID of the agent to assign the ticket to
 * @param  boolean  $log          Shall the assignment be logged or not
 * @return object|boolean|integer WP_Error in case of problem, true if no change is required or the post meta ID if the agent was changed
 */
function wpas_assign_ticket( $ticket_id, $agent_id = null, $log = true ) {

	if ( 'ticket' !== get_post_type( $ticket_id ) ) {
		return new WP_Error( 'incorrect_post_type', __( 'The given post ID is not a ticket', 'wpas' ) );
	}

	if ( is_null( $agent_id ) ) {
		$agent_id = wpas_find_agent( $ticket_id );
	}

	if ( !user_can( $agent_id, 'edit_ticket' ) ) {
		return new WP_Error( 'incorrect_agent', __( 'The chosen agent does not have the sufficient capapbilities to be assigned a ticket', 'wpas' ) );
	}

	/* Get the current agent if any */
	$current = get_post_meta( $ticket_id, '_wpas_assignee', true );

	if ( $current === $agent_id ) {
		return true;
	}

	$update = update_post_meta( $ticket_id, '_wpas_assignee', $agent_id, $current );

	/* Log the action */
	if ( true === $log ) {
		$log = array();
		$log[] = array(
			'action'   => 'updated',
			'label'    => __( 'Support staff', 'wpas' ),
			'value'    => $agent_id,
			'field_id' => 'assignee'
		);
	}

	wpas_log( $ticket_id, $log );

	/**
	 * wpas_ticket_assigned hook
	 *
	 * since 3.0.2
	 */
	do_action( 'wpas_ticket_assigned', $ticket_id, $agent_id );

	return $update;

}

/**
 * Save form values.
 *
 * If the submission fails we save the form values in order to
 * pre-populate the form on page reload. This will avoid asking the user
 * to fill all the fields again.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_save_values() {

	if ( isset( $_SESSION['wpas_submission_form'] ) ) {
		unset( $_SESSION['wpas_submission_form'] );
	}

	foreach ( $_POST as $key => $value ) {

		if ( !empty( $value ) ) {
			$_SESSION['wpas_submission_form'][$key] = $value;
		}

	}

}

/**
 * Randomize user query.
 *
 * In order to correctly balance the tickets attribution
 * we need ot randomize the order in which they are returned.
 * Otherwise, when multiple agents have the same amount of open tickets
 * it is always the first one in the results who will be assigned
 * to new tickets.
 *
 * @since  3.0.0
 * @param  object $query User query
 * @return void
 */
function wpas_randomize_uers_query( $query ) {

	/* Make sure we only alter our own user query */
	if ( 'wpas_random' == $query->query_vars['orderby'] ) {
		$query->query_orderby = 'ORDER BY RAND()';
    }

}

/**
 * Update ticket status.
 *
 * Update the post_status of a ticket
 * using one of the custom status registered by the plugin
 * or its addons.
 *
 * @since  3.0.0
 * @param  integer $post_id ID of the ticket being updated
 * @param  string  $status  New status to attribute
 * @return boolean          True if the query was successfully executed
 */
function wpas_update_ticket_status( $post_id, $status ) {

	$custom_status = wpas_get_post_status();

	if ( !array_key_exists( $status, $custom_status ) ) {
		return false;
	}

	$post = get_post( $post_id );

	if( !$post || $post->post_status === $status ) {
		return false;
	}

	$my_post = array(
		'ID'          => $post_id,
		'post_status' => $status
	);

	$updated = wp_update_post( $my_post );

	if ( 0 !== intval( $updated ) ) {
		wpas_log( $post_id, sprintf( __( 'Ticket state changed to &laquo;%s&raquo;', 'wpas' ), $custom_status[$status] ) );
	}

	/**
	 * wpas_ticket_status_updated hook
	 *
	 * @since  3.0.2
	 */
	do_action( 'wpas_ticket_status_updated', $post_id, $status, $updated );

	return $updated;

}

/**
 * Change a ticket status to closed.
 *
 * @since  3.0.2
 * @param  integer         $ticket_id ID of the ticket to close
 * @return integer|boolean            ID of the post meta if exists, true on success or false on failure
 */
function wpas_close_ticket( $ticket_id ) {

	if ( 'ticket' == get_post_type( $ticket_id ) ) {

		$update = update_post_meta( intval( $ticket_id ), '_wpas_status', 'closed' );

		/* Log the action */
		wpas_log( $ticket_id, __( 'The ticket was closed.', 'wpas' ) );

		/**
		 * wpas_after_close_ticket hook
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_after_close_ticket', intval( $ticket_id ), $update );

		return $update;

	} else {
		return false;
	}

}

/**
 * Change a ticket status to open.
 *
 * @since  3.0.2
 * @param  integer         $ticket_id ID of the ticket to re-open
 * @return integer|boolean            ID of the post meta if exists, true on success or false on failure
 */
function wpas_reopen_ticket( $ticket_id ) {

	if ( 'ticket' == get_post_type( $ticket_id ) ) {

		$update = update_post_meta( intval( $ticket_id ), '_wpas_status', 'open' );

		/* Log the action */
		wpas_log( $ticket_id, __( 'The ticket was re-opened.', 'wpas' ) );

		/**
		 * wpas_after_reopen_ticket hook
		 *
		 * @since  3.0.2
		 */
		do_action( 'wpas_after_reopen_ticket', intval( $ticket_id ), $update );

		return $update;

	} else {
		return false;
	}

}