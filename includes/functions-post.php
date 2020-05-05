<?php
/**
 * Open a new ticket.
 *
 * @since  3.0.0
 * @param  array $data Ticket data
 * @return boolean
 */
function wpas_open_ticket( $data ) {

	$title   			= isset( $data['title'] ) ? wp_strip_all_tags( $data['title'] ) : false;
	$content 			= isset( $data['message'] ) ? wp_kses( $data['message'], wp_kses_allowed_html( 'post' ) ) : false;
	$bypass_pre_checks  = isset( $data['bypass_pre_checks'] ) ? boolval(wp_kses( $data['bypass_pre_checks'], false ) ) : false;  // Bypass pre-submission filter checks?

	/**
	 * Prepare vars
	 */
	$submit = isset( $_POST['_wp_http_referer'] ) ? wpas_get_submission_page_url( url_to_postid( $_POST['_wp_http_referer'] ) ) : wpas_get_submission_page_url();

	// Fallback in case the referrer failed
	if ( empty( $submit ) ) {
		$submission_pages = wpas_get_option( 'ticket_submit' );

		if( $submission_pages ) {
			$submit           = $submission_pages[0];
			$submit           = wp_sanitize_redirect( get_permalink( $submit ) );
		}
	}

	// Verify user capability
	if ( ! current_user_can( 'create_ticket' ) ) {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wpas_add_error( 'cannot_open_ticket', __( 'You do not have the capacity to open a new ticket.', 'awesome-support' ) );
		wp_redirect( $submit );

		// Break
		exit;
	}

	// Make sure we have at least a title and a message
	if ( false === $title || empty( $title ) ) {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wpas_add_error( 'missing_title', __( 'It is mandatory to provide a title for your issue.', 'awesome-support' ) );
		wp_redirect( $submit );

		// Break
		exit;
	}

	if ( true === ( $description_mandatory = apply_filters( 'wpas_ticket_submission_description_mandatory', true ) ) && ( false === $content || empty( $content ) ) ) {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wpas_add_error( 'missing_description', __( 'It is mandatory to provide a description for your issue.', 'awesome-support' ) );
		wp_redirect( $submit );

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
	$go = true ; 
	if ( ! $bypass_pre_checks ) {
		$go = apply_filters( 'wpas_before_submit_new_ticket_checks', true );
	}

	/* Check for the green light */
	if ( is_wp_error( $go ) ) {

		/* Retrieve error messages. */
		$messages = $go->get_error_messages();

		/* Save the input */
		wpas_save_values();

		/* Redirect to submit page */
		wpas_add_error( 'validation_issue', $messages );
		wp_redirect( $submit );

		exit;

	}

	/**
	 * Gather current user info
	 */
	if ( is_user_logged_in() ) {

		global $current_user;

		$user_id = $current_user->ID;

	} else {

		// Save the input
		wpas_save_values();

		// Redirect to submit page
		wpas_add_error( 'unknown_user', __( 'Only registered accounts can submit a ticket. Please register first.', 'awesome-support' ) );
		wp_redirect( $submit );

		exit;

	}

	/**
	 * Submit the ticket.
	 *
	 * Now that all the verifications are passed
	 * we can proceed to the actual ticket submission.
	 */
	$post = apply_filters(
		'wpas_open_ticket_data', array(
			'post_content'   => $content,
			'post_name'      => $title,
			'post_title'     => $title,
			'post_status'    => 'queued',
			'post_type'      => 'ticket',
			'post_author'    => $user_id,
			'ping_status'    => 'closed',
			'comment_status' => 'closed',
		)
	);

	return wpas_insert_ticket( $post, false, false, 'standard-ticket-form' );

}

add_action( 'wpas_do_submit_new_ticket', 'wpas_new_ticket_submission' );
/**
 * Instantiate a new ticket submission
 *
 * This helper function is used to trigger the creation of a new ticket
 * after the ticket submission form is posted on the front-end.
 *
 * @since 3.3
 *
 * @param array $data Ticket data required to open a new ticket
 *
 * @return void
 */
function wpas_new_ticket_submission( $data ) {

	if ( ! is_admin() && isset( $data['wpas_title'] ) ) {

		// Verify the nonce first
		if ( ! isset( $data['wpas_nonce'] ) || ! wp_verify_nonce( $data['wpas_nonce'], 'new_ticket' ) ) {

			/* Save the input */
			wpas_save_values();

			// Redirect to submit page
			wpas_add_error( 'nonce_verification_failed', __( 'The authenticity of your submission could not be validated. If this ticket is legitimate please try submitting again.', 'awesome-support' ) );
			wp_redirect( wp_sanitize_redirect( home_url( $_POST['_wp_http_referer'] ) ) );
			exit;
		}

		$ticket_id = wpas_open_ticket(
			array(
				'title'   => $data['wpas_title'],
				'message' => $data['wpas_message'],
			)
		);

		/* Submission failure */
		if ( false === $ticket_id ) {

			/* Save the input */
			wpas_save_values();

			/**
			 * Redirect to the referrer since ticket creation failed....
			 */
			wpas_add_error( 'submission_error', __( 'The ticket couldn\'t be submitted for an unknown reason.', 'awesome-support' ) );
			wp_redirect( wp_sanitize_redirect( home_url( $data['_wp_http_referer'] ) ) );
			exit;

		} /* Submission succeeded */
		else {

			/**
			 * Empty the temporary sessions
			 */
			WPAS()->session->clean( 'submission_form' );

			/**
			 * Redirect to the newly created ticket
			 */
			if ( ! empty( wpas_get_option( 'new_ticket_redirect_fe', '' ) ) ) {
				wpas_redirect( 'ticket_added', wpas_get_option( 'new_ticket_redirect_fe', '' ), $ticket_id );
			} else {
				wpas_redirect( 'ticket_added', get_permalink( $ticket_id ), $ticket_id );
			}

			exit;

		}
	}

}

/**
 * Insert a new ticket in the database
 *
 * This function is a wrapper function for wp_insert_post
 * with additional checks specific to the ticketing system
 *
 * @param array    $data            Ticket (post) data
 * @param bool|int $post_id         Post ID for an update
 * @param bool|int $agent_id        ID of the agent to assign ticket to
 * @param string   $channel_term    Source of the ticket
 *
 * @return bool|int|WP_Error
 */
function wpas_insert_ticket( $data = array(), $post_id = false, $agent_id = false, $channel_term = 'other' ) {

	// Save the original data array
	$incoming_data = $data;

	// First of all we want to set the ticket author so that we can check if (s)he is allowed to open a ticket or not.
	if ( empty( $data['post_author'] ) ) {
		global $current_user;
		$data['post_author'] = $current_user->ID;
	}

	if ( ! user_can( $data['post_author'], 'create_ticket' ) ) {
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
	if ( isset( $data['post_title'] ) && ! empty( $data['post_title'] ) ) {
		$data['post_title'] = wp_strip_all_tags( $data['post_title'] );
	}

	if ( ! empty( $data['post_content'] ) ) {
		$data['post_content'] = strip_shortcodes( $data['post_content'] );
	}

	/**
	* Sanitize the slug
	*/
	if ( isset( $data['post_name'] ) && ! empty( $data['post_name'] ) ) {
		$data['post_name'] = sanitize_text_field( $data['post_name'] );
	}

	/**
	 * Filter the data right before inserting it in the post.
	 *
	 * @var array
	 */
	$data = apply_filters( 'wpas_open_ticket_data', $data, $incoming_data );

	/**
	 * Fire wpas_before_open_ticket just before the post is actually
	 * inserted in the database.
	 */
	do_action( 'wpas_open_ticket_before', $data, $post_id, $incoming_data );

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
		do_action( 'wpas_open_ticket_failed', $data, $post_id, $incoming_data );

		return false;

	}

	/**
	* Change the slug to the postid if that's the option the admin set in the TICKETS->SETTINGS->Advanced tab.
	* Note that we only do this if $update is false signifying a new ticket!
	*/
	if ( ! $update ) {
		wpas_set_ticket_slug( $ticket_id );
	}

	/* Update the channel on the ticket so that hooks can access it - but only if the $update is false which means we've got a new ticket */
	/* It will need to be re-added to the ticket at the bottom of this routine because some hooks overwrite it with a blank. */
	if ( ! empty( $channel_term ) && ( ! $update ) ) {
		wpas_set_ticket_channel( $ticket_id, $channel_term, false );
	}

	/* Set the ticket as open. */
	add_post_meta( $ticket_id, '_wpas_status', 'open', true );

	/* Next - update other some meta values. If you add or delete from this list you also */
	/* need to do the same thing in the /includes/admin/functions-post.php file */
	add_post_meta( $ticket_id, '_wpas_last_reply_date', null, true );
	add_post_meta( $ticket_id, '_wpas_last_reply_date_gmt', null, true );
	add_post_meta( $ticket_id, '_wpas_is_waiting_client_reply', ! user_can( $data['post_author'], 'edit_ticket' ), true );

	if ( false === $agent_id ) {
		$agent_id = wpas_find_agent( $ticket_id );
	}

	/**
	 * Fire wpas_open_ticket_before_assigned after the post is successfully submitted but before it has been assigned to an agent.
	 *
	 * @since 3.2.6
	 */
	do_action( 'wpas_open_ticket_before_assigned', $ticket_id, $data, $incoming_data );
	
	/**
	 * We might want to assign agent manually
	 */
	if( apply_filters( 'wpas_open_ticket_should_agent_assign', true, $ticket_id ) ) {
		
		/* Assign an agent to the ticket */
		wpas_assign_ticket( $ticket_id, apply_filters( 'wpas_new_ticket_agent_id', $agent_id, $ticket_id, $agent_id ), false );
	
	}

	/* Update the channel on the ticket - but only if the $update is false which means we've got a new ticket */
	/* Need to update it here again because some of the action hooks fired above will overwrite the term.			  */
	if ( ! empty( $channel_term ) && ( ! $update ) ) {
		wpas_set_ticket_channel( $ticket_id, $channel_term, false );
	}

	/**
	 * Fire wpas_after_open_ticket just after the post is successfully submitted and assigned.
	 */
	do_action( 'wpas_open_ticket_after', $ticket_id, $data );

	do_action( 'wpas_ticket_after_saved', $ticket_id );

	return $ticket_id;

}

/**
 * Set the channel (ticket source) term/field
 *
 * @since 3.4.0
 *
 * @param numeric       $ticket_id
 * @param string        $channel_term
 * @param string        $overwrite  whether or not to overwrite existing channel on the ticket - set to false by default
 *
 * @return void
 */
function wpas_set_ticket_channel( $ticket_id = -1, $channel_term = 'other', $overwrite = false ) {

	/* Does a term already exist on the ticket?  If so, do not overwrite it if $overwrite is false */
	if ( false === $overwrite ) {
		$existing_channel = wp_get_post_terms( $ticket_id, 'ticket_channel' );
		if ( ! empty( $existing_channel ) ) {
			return;
		}
	}

	/* Get the term id because wp_set_object_terms require an id instead of just a string */
	$arr_the_term_id = term_exists( $channel_term, 'ticket_channel' );

	if ( $arr_the_term_id ) {

		// Need to get array keys first so we can index and extract the first element in the wp_set_object_terms below.
		$arr_the_term_id_keys = array_keys( $arr_the_term_id );
		$int_the_term_id      = (int) $arr_the_term_id[ $arr_the_term_id_keys[0] ];

		// Now add the terms (this function call doesn't work consistently for some reason!)
		$term_taxonomy_ids = wp_set_object_terms( $ticket_id, (int) $int_the_term_id, 'ticket_channel' );

	}

	return;
}

/**
 * Set ticket slug on new tickets if the admin chooses anything other than the default slug.
 *
 * @since 3.4.0
 *
 * @param numeric       $ticket_id
 *
 * @return void
 */
function wpas_set_ticket_slug( $ticket_id = -1 ) {
	$use_ticket_id_for_slug = wpas_get_option( 'ticket_topic_slug' );

	/* Set ticket slug to the post id / ticket id */
	if ( isset( $use_ticket_id_for_slug ) && ( 'ticketid' == $use_ticket_id_for_slug ) ) {

		/* Set the data to be updated - in this case just post_name (slug) with the key being the ID passed into this function */
		$newdata = array(
			'ID'        => $ticket_id,
			'post_name' => (string) $ticket_id,
		);

		/* Update the post with the new slug */
		wp_update_post( $newdata );
	}

	/* Set ticket slug to a random number  */
	if ( isset( $use_ticket_id_for_slug ) && ( 'randomnumber' == $use_ticket_id_for_slug ) ) {

		/*Calculate a random number */
		$randomslug = mt_rand();

		/* Set the data to be updated - in this case just post_name (slug) with the key being the ID passed into this function */
		$newdata = array(
			'ID'        => $ticket_id,
			'post_name' => (string) $randomslug,
		);

		/* Update the post with the new slug */
		wp_update_post( $newdata );
	}

	/* Set ticket slug to a GUID  */
	if ( isset( $use_ticket_id_for_slug ) && ( 'guid' == $use_ticket_id_for_slug ) ) {

		/*Calculate a guid */
		$randomguid = wpas_create_pseudo_guid();

		/* Set the data to be updated - in this case just post_name (slug) with the key being the ID passed into this function */
		$newdata = array(
			'ID'        => $ticket_id,
			'post_name' => $randomguid,
		);

		/* Update the post with the new slug */
		wp_update_post( $newdata );
	}

	return;
}

/**
 * Get tickets.
 *
 * Get a list of tickets matching the arguments passed.
 * This function is basically a wrapper for WP_Query with
 * the addition of the ticket status.
 *
 * @since  3.0.0
 *
 * @param string       $ticket_status Ticket status (open or closed)
 * @param array        $args          Additional arguments (see WP_Query)
 * @param string|array $post_status   Ticket state
 * @param bool         $cache         Whether or not to cache the results
 *
 * @return array               Array of tickets, empty array if no tickets found
 */
function wpas_get_tickets( $ticket_status = 'open', $args = array(), $post_status = 'any', $cache = false ) {

	$custom_post_status = wpas_get_post_status();
	$post_status_clean  = array();

	if ( empty( $post_status ) ) {
		$post_status = 'any';
	}

	if ( ! is_array( $post_status ) ) {
		if ( 'any' === $post_status ) {

			foreach ( $custom_post_status as $status_id => $status_label ) {
				$post_status_clean[] = $status_id;
			}

			$post_status = $post_status_clean;

		} else {
			if ( ! array_key_exists( $post_status, $custom_post_status ) ) {
				$post_status = ''; // This basically will return no result if the post status specified doesn't exist
			}
		}
	} else {
		foreach ( $post_status as $key => $status ) {
			if ( ! array_key_exists( $status, $custom_post_status ) ) {
				unset( $post_status[ $key ] );
			}
		}
	}

	$defaults = array(
		'post_type'              => 'ticket',
		'post_status'            => $post_status,
		'posts_per_page'         => - 1,
		'no_found_rows'          => ! (bool) $cache,
		'cache_results'          => (bool) $cache,
		'update_post_term_cache' => (bool) $cache,
		'update_post_meta_cache' => (bool) $cache,
		'wpas_query'             => true, // We use this parameter to identify our own queries so that we can remove the author parameter
	);

	$args = wp_parse_args( $args, $defaults );

	if ( 'any' !== $ticket_status ) {
		if ( in_array( $ticket_status, array( 'open', 'closed' ) ) ) {
			$args['meta_query'][] = array(
				'key'     => '_wpas_status',
				'value'   => $ticket_status,
				'compare' => '=',
				'type'    => 'CHAR',
			);
		}
	}

	$query = new WP_Query( $args );

	if ( empty( $query->posts ) ) {
		return array();
	} else {
		return $query->posts;
	}

}

/**
 * Get ticket by ticket id and user id.
 *
 * @since 5.1.1
 *
 * @param int       $id    Ticket ID
 * @param array     $args  Additional arguments (see WP_Query)
 * @param bool      $cache Whether or not to cache the results
 *
 * @return array  
 */
function wpas_get_ticket_by_id( $id, $args = array(), $cache = false ) {

	$defaults = [
		'p'                      => intval( $id ),
		'post_type'              => 'ticket',
		'no_found_rows'          => ! (bool) $cache,
		'cache_results'          => (bool) $cache,
		'update_post_term_cache' => (bool) $cache,
		'update_post_meta_cache' => (bool) $cache,
		'wpas_query'             => true, // We use this parameter to identify our own queries so that we can remove the author parameter
			
	];

	$args = wp_parse_args( $args, $defaults );

	$query = new WP_Query( $args );

	if ( empty( $query->posts ) ) {
		return array();
	} else {
		return $query->posts[0];
	}

}

/**
 * Add a new reply to a ticket.
 *
 * @param array           $data      The reply data to insert
 * @param boolean|integer $parent_id ID of the parent ticket (post)
 * @param boolean|integer $author_id The ID of the reply author (false if none)
 *
 * @return boolean|integer False on failure or reply ID on success
 */
function wpas_add_reply( $data, $parent_id = false, $author_id = false ) {

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
		'post_name'      => sprintf( __( 'Reply to ticket %s', 'awesome-support' ), "#$parent_id" ),
		'post_title'     => sprintf( __( 'Reply to ticket %s', 'awesome-support' ), "#$parent_id" ),
		'post_status'    => 'unread',
		'post_type'      => 'ticket_reply',
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
		'post_parent'    => $parent_id,
	);

	$data = wp_parse_args( $data, $defaults );

	if ( false !== $author_id ) {
		$data['post_author'] = $author_id;
	} else {
		global $current_user;
		$data['post_author'] = $current_user->ID;
	}

	$insert = wpas_insert_reply( $data, $parent_id );

	return $insert;

}

add_action( 'wpas_do_submit_new_reply', 'wpas_new_reply_submission' );
/**
 * Instantiate a new reply submission
 *
 * This helper function is used to trigger the creation of a new reply
 * after the reply submission form is posted on the front-end.
 *
 * @since 3.3
 *
 * @param array $data Reply data required to open a new ticket
 *
 * @return void
 */
function wpas_new_reply_submission( $data ) {

	// Get parent ticket ID
	$parent_id = (int) $data['ticket_id'];

	if ( 'ticket' !== get_post_type( $parent_id ) ) {
		wpas_add_error( 'reply_added_failed', __( 'Something went wrong. We couldn&#039;t identify your ticket. Please try again.', 'awesome-support' ) );
		wpas_redirect( 'reply_added_failed', get_permalink( $parent_id ) );
		exit;
	}

	// Define if the ticket must be closed
	$close = isset( $data['wpas_close_ticket'] ) ? true : false;

	if ( ! empty( $data['wpas_user_reply'] ) && apply_filters( 'wpas_user_can_reply_ticket', true, $parent_id ) ) {

		/* Sanitize the data */
		$data = array( 'post_content' => wp_kses( $data['wpas_user_reply'], wp_kses_allowed_html( 'post' ) ) );

		/* Add the reply */
		$reply_id = wpas_add_reply( $data, $parent_id );

	}
	
	$closed = false;

	/* Possibly close the ticket */
	if ( $close && apply_filters( 'wpas_user_can_close_ticket', true, $parent_id ) ) {

		$closed = wpas_close_ticket( $parent_id );

		// Redirect now if no reply was posted
		if ( ! isset( $reply_id ) && $closed ) {
			wpas_add_notification( 'ticket_closed', __( 'The ticket was successfully closed', 'awesome-support' ) );
			wpas_redirect( 'ticket_closed', get_permalink( $parent_id ) );
			exit;
		}
	}

	if ( isset( $reply_id ) ) {

		if ( false === $reply_id ) {
			wpas_add_error( 'reply_added_failed', __( 'Your reply could not be submitted for an unknown reason.', 'awesome-support' ) );
			wpas_redirect( 'reply_added_failed', get_permalink( $parent_id ) );
			exit;
		} else {

			if ( $closed ) {
				wpas_add_notification( 'reply_added_closed', __( 'Thanks for your reply. The ticket is now closed.', 'awesome-support' ) );
			} else {
				wpas_add_notification( 'reply_added', __( 'Your reply has been submitted. Your agent will reply ASAP.', 'awesome-support' ) );
			}

			if ( false !== $link = wpas_get_reply_link( $reply_id ) ) {
				wpas_redirect( 'reply_added', $link );
				exit;
			}
		}
	}

}

/**
 * Update a reply with its edited version
 *
 * @since 3.3.0
 *
 * @param $int  $reply_id       - the id of the reply being edited.
 * @param array $content        - the new content.  If blank, the function will attempt to pull the new content from $_POST.
 *
 * @return void
 */
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

	$original_reply = get_post( $reply_id );

	if ( is_null( $original_reply ) ) {
		return false;
	}

	$data = apply_filters(
		'wpas_edit_reply_data', array(
			'ID'             => $reply_id,
			'post_content'   => $content,
			'post_status'    => 'read',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_date'      => $original_reply->post_date,
			'post_date_gmt'  => $original_reply->post_date_gmt,
			'post_name'      => $original_reply->post_name,
			'post_parent'    => $original_reply->post_parent,
			'post_type'      => $original_reply->post_type,
			'post_author'    => $original_reply->post_author,
		), $reply_id
	);

	$edited = wp_insert_post( $data, true );

	if ( is_wp_error( $edited ) ) {
		do_action( 'wpas_edit_reply_failed', $reply_id, $content, $edited );
		return $edited;
	}

	/* Add a flag to the reply that shows it was edited */
	update_post_meta( $edited, 'wpas_reply_was_edited', '1' );

	/* Fire the after-edit action hook */
	do_action( 'wpas_reply_edited', $reply_id, $original_reply );

	return $reply_id;

}

add_action( 'wpas_reply_edited', 'wpas_log_reply_edits', 10, 2 );
/**
 * Log the original contents of a reply after it is edited.
 *
 * Action hook: wpas_reply_edited
 *
 * @since 5.2.0
 *
 * @param $int  $reply_id       - the id of the reply being edited.
 * @param array $original_reply - the original post before the edit reply was added to the database
 *
 * @TODO: Somehow this hook is getting called three times for every edit when the logging level is LOW.  3 entries end up in the log for every single edit.
 *
 * @return void
 */
function wpas_log_reply_edits( $reply_id, $original_reply ) {

	/* Do we log a summary or detail that includes the original content? */
	if ( 'low' === wpas_get_option( 'log_content_edit_level', 'low' ) ) {
		$reply_contents_to_log = __( 'Original data not available because detailed logging is not turned on or allowed', 'awesome-support' );
	} else {
		$reply_contents_to_log = $original_reply->post_content;
	}

	wpas_log_edits( $reply_id, sprintf( __( 'Reply #%1$s located on ticket #%2$s was edited.', 'awesome-support' ), (string) $reply_id, (string) $original_reply->post_parent ), $reply_contents_to_log );

}

/**
 * Mark a reply as read
 *
 * @since 3.3.0
 *
 * @param $int  $reply_id       - the id of the reply being marked as read.
 *
 * @return void
 */
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

	$data = apply_filters(
		'wpas_mark_reply_read_data', array(
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

add_action( 'wp_ajax_wpas_mark_reply_read', 'wpas_mark_reply_read_ajax' );
/**
 * Mark a ticket reply as read with Ajax
 *
 * @return void
 */
function wpas_mark_reply_read_ajax() {

	$ID = wpas_mark_reply_read();

	if ( false === $ID || is_wp_error( $ID ) ) {
		$ID = $ID->get_error_message();
	}

	echo $ID;
	die();
}

add_action( 'wp_ajax_wpas_edit_reply', 'wpas_edit_reply_ajax' );
/**
 * Edit a reply with Ajax
 *
 * @return void
 */
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
 * @param  array            $data     Array of arguments for this reply
 * @param  boolean          $post_id  ID of the parent post
 * @return integer|WP_Error           The reply ID on success or WP_Error on failure
 */
function wpas_insert_reply( $data, $post_id = false ) {

	if ( false === $post_id ) {
		return false;
	}

	if ( ! current_user_can( 'reply_ticket' ) ) {
		return false;
	}

	$defaults = array(
		'post_name'      => sprintf( __( 'Reply to ticket %s', 'awesome-support' ), "#$post_id" ),
		'post_title'     => sprintf( __( 'Reply to ticket %s', 'awesome-support' ), "#$post_id" ),
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
	if ( isset( $data['post_title'] ) && ! empty( $data['post_title'] ) ) {
		$data['post_title'] = wp_strip_all_tags( $data['post_title'] );
	}

	if ( ! empty( $data['post_content'] ) ) {
		$data['post_content'] = strip_shortcodes( $data['post_content'] );
	}

	if ( isset( $data['post_name'] ) && ! empty( $data['post_name'] ) ) {
		$data['post_name'] = sanitize_title( $data['post_name'] );
	}

	/**
	 * Fire wpas_add_reply_before before the reply is added to the database.
	 * This hook is fired both on the back-end and the front-end.
	 *
	 * @param  array   $data    The data to be inserted to the database
	 * @param  integer $post_id ID of the parent post
	 */
	do_action( 'wpas_add_reply_before', $data, $post_id );

	if ( is_admin() ) {

		/**
		 * Fired right before the data is added to the database on the back-end only.
		 *
		 * @since  3.1.2
		 * @param  array   $data    The data to be inserted to the database
		 * @param  integer $post_id ID of the parent post
		 */
		do_action( 'wpas_add_reply_admin_before', $data, $post_id );

		/**
		 * wpas_save_reply_before
		 *
		 * This hook is now deprecated but stays in the code for backward compatibility.
		 * Instead of wpas_save_reply_before you should now use wpas_add_reply_admin_before
		 *
		 * @deprecated 3.1.2
		 */
		do_action( 'wpas_save_reply_before' );

	} else {

		/**
		 * Fired right before the data is added to the database on the front-end only.
		 *
		 * @since  3.1.2
		 * @param  array   $data    The data to be inserted to the database
		 * @param  integer $post_id ID of the parent post
		 */
		do_action( 'wpas_add_reply_public_before', $data, $post_id );

	}

	/* This is where we actually insert the post */
	$reply_id = wp_insert_post( $data, true );

	if ( is_wp_error( $reply_id ) ) {

		/**
		 * Fire wpas_add_reply_failed if the reply couldn't be inserted.
		 * This hook will be fired both in the admin and in the front-end.
		 *
		 * @param  array   $data     The data we tried to add to the database
		 * @param  integer $post_id  ID of the parent post
		 * @param  object  $reply_id WP_Error object
		 */
		do_action( 'wpas_add_reply_failed', $data, $post_id, $reply_id );

		if ( is_admin() ) {

			/**
			 * Fired if the reply instertion failed.
			 * This hook will only be fired in the admin.
			 *
			 * @since  3.1.2
			 * @param  array   $data     The data we tried to add to the database
			 * @param  integer $post_id  ID of the parent post
			 * @param  object  $reply_id WP_Error object
			 */
			do_action( 'wpas_add_reply_admin_failed', $data, $post_id, $reply_id );

			/**
			 * wpas_save_reply_after_error hook
			 *
			 * This hook is deprecated but stays in the code for backward compatibility.
			 * You should now use wpas_add_reply_admin_failed instead.
			 *
			 * @deprecated  3.1.2
			 * @param      $reply WP_Error object
			 */
			do_action( 'wpas_save_reply_after_error', $reply_id );

		} else {

			/**
			 * Fired if the reply instertion failed.
			 * This hook will only be fired in the frontèend.
			 *
			 * @since  3.1.2
			 * @param  array   $data     The data we tried to add to the database
			 * @param  integer $post_id  ID of the parent post
			 * @param  object  $reply_id WP_Error object
			 */
			do_action( 'wpas_add_reply_public_failed', $data, $post_id, $reply_id );

		}

		return $reply_id;

	}

	/**
	 * Delete the activity transient.
	 */
	delete_transient( "wpas_activity_meta_post_$post_id" );

	/**
	 * Fire wpas_add_reply_after after the reply was successfully added.
	 */
	do_action( 'wpas_add_reply_after', $reply_id, $data );

	if ( is_admin() ) {

		/**
		 * Fired right after the data is added to the database on the back-end only.
		 *
		 * @since  3.1.2
		 * @param  integer $reply_id ID of the reply added to the database
		 * @param  array   $data     Data inserted to the database
		 */
		do_action( 'wpas_add_reply_admin_after', $reply_id, $data );

		/**
		 * wpas_save_reply_after hook
		 *
		 * This hook is deprecated but stays in the code for backward compatibility.
		 * You should now use wpas_add_reply_admin_after instead.
		 *
		 * @deprecated  3.1.2
		 * @param  integer $reply Reply ID
		 * @param  array   $data  Data used to add the reply
		 */
		do_action( 'wpas_save_reply_after', $reply_id, $data );

	} else {

		/**
		 * Fired right after the data is added to the database on the front-end only.
		 *
		 * @since  3.1.2
		 * @param  integer $reply_id ID of the reply added to the database
		 * @param  array   $data     Data inserted to the database
		 */
		do_action( 'wpas_add_reply_public_after', $reply_id, $data );

	}

	/**
	 * Fire wpas_add_reply_complete after the reply and attachments was successfully added.
	 */
	do_action( 'wpas_add_reply_complete', $reply_id, $data );

	/* . */
	update_post_meta( $data['post_parent'], '_wpas_last_reply_date', current_time( 'mysql' ) );
	update_post_meta( $data['post_parent'], '_wpas_last_reply_date_gmt', current_time( 'mysql', 1 ) );

	update_post_meta( $data['post_parent'], '_wpas_is_waiting_client_reply', ! current_user_can( 'edit_ticket' ) );

	return $reply_id;

}

/**
 * Get replies for a specific ticket
 *
 * @param integer      $post_id ID of the post (ticket) to get the replies from
 * @param string|array $status  Status of the replies to get
 * @param array        $args    Additional arguments (see WP_Query)
 * @param string       $output  Type of data to return. wp_query for the WP_Query object, replies for the WP_Query posts
 *
 * @return array|WP_Query
 */
function wpas_get_replies( $post_id, $status = 'any', $args = array(), $output = 'replies' ) {

	$allowed_status = array(
		'any',
		'read',
		'unread',
	);

	if ( ! is_array( $status ) ) {
		$status = (array) $status;
	}

	foreach ( $status as $key => $reply_status ) {
		if ( ! in_array( $reply_status, $allowed_status ) ) {
			unset( $status[ $key ] );
		}
	}

	if ( empty( $status ) ) {
		$status = 'any';
	}

	$defaults = array(
		'post_parent'            => $post_id,
		'post_type'              => 'ticket_reply',
		'post_status'            => $status,
		'order'                  => wpas_get_option( 'replies_order', 'ASC' ),
		'orderby'                => 'date',
		'posts_per_page'         => - 1,
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

	return 'wp_query' === $output ? $replies : $replies->posts;

}

/**
 * Find an available agent to assign a ticket to.
 *
 * This is a super basic attribution system. It just finds the agent
 * with the less tickets currently open.
 *
 * @since  3.0.0
 *
 * @param  boolean|integer $ticket_id The ticket that needs an agent
 *
 * @return integer         ID of the best agent for the job
 */
function wpas_find_agent( $ticket_id = false ) {

	if ( defined( 'WPAS_DISABLE_AUTO_ASSIGN' ) && true === WPAS_DISABLE_AUTO_ASSIGN ) {
		return apply_filters( 'wpas_find_available_agent', wpas_get_option( 'assignee_default' ), $ticket_id );
	}

	$users = shuffle_assoc( wpas_get_users( apply_filters( 'wpas_find_agent_get_users_args', array( 'cap' => 'edit_ticket' ) ) ) );
	$agent = array();

	foreach ( $users->members as $user ) {

		$wpas_agent = new WPAS_Member_Agent( $user );

		/**
		 * Make sure the user really is an agent and that he can currently be assigned
		 */
		if ( true !== $wpas_agent->is_agent() || false === $wpas_agent->can_be_assigned() ) {
			continue;
		}

		$count = $wpas_agent->open_tickets(); // Total number of open tickets for this agent

		if ( empty( $agent ) ) {
			$agent = array(
				'tickets' => $count,
				'user_id' => $user->ID,
			);
		} else {

			if ( $count < $agent['tickets'] ) {
				$agent = array(
					'tickets' => $count,
					'user_id' => $user->ID,
				);
			}
		}
	}

	if ( is_array( $agent ) && isset( $agent['user_id'] ) ) {
		$agent_id = $agent['user_id'];
	} else {

		$default_id = wpas_get_option( 'assignee_default', 1 );

		if ( empty( $default_id ) ) {
			$default_id = 1;
		}

		$agent_id = $default_id;

	}

	return apply_filters( 'wpas_find_available_agent', (int) $agent_id, $ticket_id );

}

/**
 * Assign an agent to a ticket.
 *
 * Assign the given agent to a ticket or find an available
 * agent if no agent ID is given.
 *
 * @since  3.0.2
 *
 * @param  integer $ticket_id ID of the post in need of a new agent
 * @param  integer $agent_id  ID of the agent to assign the ticket to
 * @param  boolean $log       Shall the assignment be logged or not
 *
 * @return object|boolean|integer WP_Error in case of problem, true if no change is required or the post meta ID if the
 *                                agent was changed
 */
function wpas_assign_ticket( $ticket_id, $agent_id = null, $log = true ) {

	if ( 'ticket' !== get_post_type( $ticket_id ) ) {
		return new WP_Error( 'incorrect_post_type', __( 'The given post ID is not a ticket', 'awesome-support' ) );
	}

	if ( is_null( $agent_id ) ) {
		$agent_id = wpas_find_agent( $ticket_id );
	}

	if ( ! user_can( $agent_id, 'edit_ticket' ) ) {
		return new WP_Error( 'incorrect_agent', __( 'The chosen agent does not have the sufficient capabilities to be assigned a ticket', 'awesome-support' ) );
	}

	/* Get the current agent if any */
	$current = get_post_meta( $ticket_id, '_wpas_assignee', true );

	if ( $current === $agent_id ) {
		return true;
	}

	$update = update_post_meta( $ticket_id, '_wpas_assignee', $agent_id, $current );

	/* Increment the number of tickets open for this agent */
	$agent = new WPAS_Member_Agent( $agent_id );
	$agent->ticket_plus();

	/* Log the action */
	if ( true === $log ) {
		$log   = array();
		$log[] = array(
			'action'   => 'updated',
			'label'    => __( 'Support Staff', 'awesome-support' ),
			'value'    => $agent_id,
			'field_id' => 'assignee',
		);
	}

	wpas_log_history( $ticket_id, $log );

	/**
	 * wpas_ticket_assigned hook
	 *
	 * since 3.0.2
	 */
	do_action( 'wpas_ticket_assigned', $ticket_id, $agent_id );

	// In case this is a ticket transfer from one agent to another, we fire a dedicated action
	if ( ! empty( $current ) && user_can( (int) $current, 'edit_ticket' ) ) {

		/**
		 * Fired only if the current assignment is a ticket transfer
		 *
		 * @since 3.2.8
		 *
		 * @param int $agent_id ID of the new assignee
		 * @param int $current  ID of the previous assignee
		 */
		do_action( 'wpas_ticket_assignee_changed', $agent_id, (int) $current );

	}

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

	$fields = array();

	foreach ( $_POST as $key => $value ) {

		if ( ! empty( $value ) ) {
			$fields[ $key ] = $value;
		}
	}

	WPAS()->session->add( 'submission_form', $fields );

}

add_action( 'pre_user_query', 'wpas_randomize_uers_query', 10, 1 );
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
 *
 * @param  object $query User query
 *
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

	if ( ! array_key_exists( $status, $custom_status ) ) {
		return false;
	}

	$post = get_post( $post_id );

	if ( ! $post || $post->post_status === $status ) {
		return false;
	}

	$my_post = array(
		'ID'          => $post_id,
		'post_status' => $status,
	);

	$updated = wp_update_post( $my_post );

	if ( 0 !== intval( $updated ) ) {
		wpas_log_history( $post_id, sprintf( __( 'Ticket state changed to %s', 'awesome-support' ), $custom_status[ $status ] ) );
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
 *
 * @param  integer $ticket_id ID of the ticket to close
 * @param int      $user_id   ID of the user who closed the ticket
 *
 * @return integer|boolean            ID of the post meta if exists, true on success or false on failure
 */
function wpas_close_ticket( $ticket_id, $user_id = 0, $skip_user_validation = false ) {

	global $current_user;

	// Set the user who closed the ticket to the current user if nothing is specified
	if ( 0 === $user_id ) {
		$user_id = $current_user->ID;
	}

	if ( ! $skip_user_validation ) {
		if ( ! current_user_can( 'close_ticket' ) ) {
			wp_die( __( 'You do not have the capacity to close this ticket', 'awesome-support' ), __( 'Can’t close ticket', 'awesome-support' ), array( 'back_link' => true ) );
		}
	}

	$ticket_id = intval( $ticket_id );

	if ( 'ticket' == get_post_type( $ticket_id ) ) {
		
		$close_ticket = true;
		
		if ( is_admin() ) {
			$close_ticket = apply_filters( 'wpas_before_close_ticket_admin', $close_ticket, $ticket_id );
		} else {
			$close_ticket = apply_filters( 'wpas_before_close_ticket_public', $close_ticket, $ticket_id );
		}
		
		if( !$close_ticket ) {
			return false;
		}
		

		$update = update_post_meta( intval( $ticket_id ), '_wpas_status', 'closed' );

		// Save the date at which the ticket was last closed. The date is updated if the ticket is re-opened and then re-closed.
		update_post_meta( $ticket_id, '_ticket_closed_on', current_time( 'mysql' ) );
		update_post_meta( $ticket_id, '_ticket_closed_on_gmt', current_time( 'mysql', 1 ) );

		/* Decrement the number of tickets open for this agent */
		$agent_id = get_post_meta( $ticket_id, '_wpas_assignee', true );
		$agent    = new WPAS_Member_Agent( $agent_id );
		$agent->ticket_minus();

		/* Log the action */
		wpas_log_history( $ticket_id, __( 'The ticket was closed.', 'awesome-support' ) );

		/**
		 * wpas_after_close_ticket hook
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_after_close_ticket', $ticket_id, $update, $user_id );

		if ( is_admin() ) {

			/**
			 * Fires after the ticket was closed in the admin only.
			 *
			 * @since  3.1.2
			 *
			 * @param integer $ticket_id ID of the ticket we just closed
			 * @param integer $user_id   ID of the user who did the action
			 * @param boolean $update    True on success, false on fialure
			 */
			do_action( 'wpas_after_close_ticket_admin', $ticket_id, $user_id, $update );

		} else {

			/**
			 * Fires after the ticket was closed in the front-end only.
			 *
			 * @since  3.1.2
			 *
			 * @param integer $ticket_id ID of the ticket we just closed
			 * @param integer $user_id   ID of the user who did the action
			 * @param boolean $update    True on success, false on failure
			 */
			do_action( 'wpas_after_close_ticket_public', $ticket_id, $user_id, $update );

		}

		return $update;

	} else {
		return false;
	}

}

/**
 * Change a ticket status to open.
 *
 * @since  3.0.2
 *
 * @param  integer $ticket_id ID of the ticket to re-open
 *
 * @return integer|boolean            ID of the post meta if exists, true on success or false on failure
 */
function wpas_reopen_ticket( $ticket_id ) {

	if ( 'ticket' !== get_post_type( $ticket_id ) ) {
		return false;
	}

	if ( ! current_user_can( 'edit_ticket' ) && ! wpas_can_submit_ticket( $ticket_id ) ) {
		return false;
	}

	$update = update_post_meta( intval( $ticket_id ), '_wpas_status', 'open' );

	/* Log the action */
	wpas_log_history( $ticket_id, __( 'The ticket was re-opened.', 'awesome-support' ) );

	/**
	 * wpas_after_reopen_ticket hook
	 *
	 * @since  3.0.2
	 */
	do_action( 'wpas_after_reopen_ticket', intval( $ticket_id ), $update );

	return $update;

}

add_action( 'wpas_do_reopen_ticket', 'wpas_reopen_ticket_trigger' );
/**
 * Trigger the re-open ticket function
 *
 * This is triggered by the wpas_do custom actions.
 *
 * @since 3.3
 *
 * @param array $data Superglobal data
 *
 * @return void
 */
function wpas_reopen_ticket_trigger( $data ) {

	if ( isset( $data['ticket_id'] ) ) {

		$ticket_id = (int) $data['ticket_id'];

		if ( ! wpas_can_submit_ticket( $ticket_id ) && ! current_user_can( 'edit_ticket' ) ) {
			wpas_add_error( 'cannot_reopen_ticket', __( 'You are not allowed to re-open this ticket', 'awesome-support' ) );
			wpas_redirect( 'ticket_reopen', wpas_get_tickets_list_page_url() );
			exit;
		}

		do_action( 'wpas_before_customer_reopen_ticket', $ticket_id );

		wpas_reopen_ticket( $ticket_id );
		wpas_add_notification( 'ticket_reopen', __( 'The ticket has been successfully re-opened.', 'awesome-support' ) );
		wpas_redirect( 'ticket_reopen', wp_sanitize_redirect( get_permalink( $ticket_id ) ) );
		exit;

	}

}

add_action( 'wp_ajax_wpas_edit_reply_editor', 'wpas_edit_reply_editor_ajax' );
/**
 * Load TinyMCE via Ajax request to edit a reply.
 *
 * @since  3.1.5
 * @return string Editor markup
 */
function wpas_edit_reply_editor_ajax() {

	$reply_id = filter_input( INPUT_POST, 'reply_id', FILTER_SANITIZE_NUMBER_INT );

	if ( empty( $reply_id ) ) {
		echo '';
		die();
	}

	$post = get_post( $reply_id );

	if ( 'ticket_reply' !== $post->post_type ) {
		echo '';
		die();
	}

	$editor_id      = "wpas-editreply-$reply_id";
	$editor_content = apply_filters( 'the_content', $post->post_content );

	$settings = array(
		'media_buttons' => false,
		'teeny'         => true,
		'quicktags'     => false,
		'editor_class'  => 'wpas-edittextarea',
		'textarea_name' => 'wpas_edit_reply[' . $reply_id . ']',
		'textarea_rows' => 20,
	);

	wp_editor( $editor_content, $editor_id, $settings );

	die();

}

/**
 * Get the tickets count by ticket status
 *
 * @since 3.2
 *
 * @param string $state
 * @param string $status
 *
 * @return int Tickets count
 */
function wpas_get_ticket_count_by_status( $state = '', $status = 'open', $query = array() ) {

	$args        = array();
	$post_status = wpas_get_post_status();

	// Make the state an array
	if ( ! is_array( $state ) ) {
		$state = array_filter( (array) $state );
	}

	// Sanitize the status
	if ( ! in_array( $status, array( 'open', 'closed', 'any' ) ) ) {
		$status = 'open';
	}

	// Restrict tickets to the specified status
	if ( ! empty( $state ) ) {

		// Force open status if a state is defined. Who cares about counting closed "In Progress" tickets.
		$status = 'open';

		// Make sure the requested ticket state is declared
		foreach ( $state as $key => $s ) {
			if ( ! array_key_exists( $s, $post_status ) ) {
				unset( $state[ $key ] );
			}
		}

		$args['post_status'] = $state;

	}

	// Maybe restrict the count to the current user only
	if (
		( wpas_is_asadmin() && false === (bool) wpas_get_option( 'admin_see_all' ) )
		|| ( ! wpas_is_asadmin() && wpas_is_agent() && false === (bool) wpas_get_option( 'agent_see_all' ) )
	) {

		global $current_user;

		$args['meta_query'][] = array(
			'key'     => '_wpas_assignee',
			'value'   => $current_user->ID,
			'compare' => '=',
		);

	}
	// if query arguments are set then combine query argument with $args varible
	if( is_array( $query ) &&  count( $query ) > 0 ) {
		$args = array_merge( $args, $query );
	}
	return count( wpas_get_tickets( $status, apply_filters( 'wpas_get_ticket_count_by_status_args',$args ) ) );

}

add_action( 'wp_ajax_wpas_load_replies', 'wpas_get_ticket_replies_ajax' );
add_action( 'wp_ajax_nopriv_wpas_load_replies', 'wpas_get_ticket_replies_ajax' );
/**
 * Ajax function that returns a number of ticket replies
 *
 * @since 3.3
 * @return void
 */
function wpas_get_ticket_replies_ajax() {

	// Make sure we have a ticket ID to work with
	if ( ! isset( $_POST['ticket_id'] ) ) {
		echo json_encode( array( 'error' => esc_html__( 'No ticket ID given', 'awesome-support' ) ) );
		die();
	}

	$ticket_id = (int) $_POST['ticket_id'];
	$offset    = isset( $_POST['ticket_replies_total'] ) ? (int) $_POST['ticket_replies_total'] : 0;
	$ticket    = get_post( $ticket_id );

	// Make sure the ID exists
	if ( ! is_object( $ticket ) || ! is_a( $ticket, 'WP_Post' ) ) {
		echo json_encode( array( 'error' => esc_html__( 'Invalid ticket ID', 'awesome-support' ) ) );
		die();
	}

	// Make sure the post is actually a ticket
	if ( 'ticket' !== $ticket->post_type ) {
		echo json_encode( array( 'error' => esc_html__( 'Given ID is not a ticket', 'awesome-support' ) ) );
		die();
	}

	$number_replies = apply_filters( 'wpas_get_ticket_replies_ajax_replies', wpas_get_option( 'replies_per_page', 10 ) );
	$replies        = wpas_get_replies(
		$ticket_id, 'any', array(
			'posts_per_page' => $number_replies,
			'no_found_rows'  => false,
			'offset'         => $offset,
		), 'wp_query'
	);

	if ( empty( $replies->posts ) ) {
		echo json_encode( array() );
		die();
	}

	$output = array(
		'total'   => (int) $replies->found_posts,
		'current' => $offset + (int) $replies->post_count,
		'html'    => '',
	);

	$html = array();

	while ( $replies->have_posts() ) {

		$replies->the_post();
		$user     = get_userdata( $replies->post->post_author );
		$time_ago = human_time_diff( get_the_time( 'U', $replies->post->ID ), current_time( 'timestamp' ) );

		ob_start();

		wpas_get_template(
			'partials/ticket-reply', array(
				'time_ago' => $time_ago,
				'user'     => $user,
				'post'     => $replies->post,
			)
		);

		$reply = ob_get_contents();

		ob_end_clean();

		$html[] = $reply;

	}

	$output['html'] = implode( '', $html );

	echo json_encode( $output );
	die();

}


add_action( 'wpas_backend_reply_content_after', 'wpas_show_reply_edited_msg', 10, 1 );
/**
 * Show whether a ticket reply has been edited or not.
 *
 * Action hook: wpas_backend_reply_content_after
 *              Hook located in metaboxes/replies-published.php.
 *
 * @since 5.2.0
 *
 * @param string $reply_id - postid of reply being processed.
 *
 * @return void
 */
function wpas_show_reply_edited_msg( $reply_id ) {

	$edited = get_post_meta( $reply_id, 'wpas_reply_was_edited' );

	if ( (int) $edited > 0 ) {
		echo '<br />' . '<div class="wpas_footer_note">' . __( '* This reply has been edited.  See the logs for a full history of edits.', 'awesome-support' ) . '</div>';
	}

}

add_action( 'wpas_backend_ticket_content_after', 'wpas_show_reply_deleted_msg', 10, 2 );
/**
 * Show whether a ticket reply has been deleted.
 *
 * Because the reply is deleted, we have to show the message on the opening ticket post.
 *
 * Action hook: wpas_backend_ticket_content_after
 *              Hook located in metaboxes/message.php.
 *
 * @since 5.2.0
 *
 * @param string $ticket_id - id of ticket being processed.
 * @param array  $ticket    - post object of ticket being processed.
 *
 * @return void
 */
function wpas_show_reply_deleted_msg( $ticket_id, $ticket ) {

	$post = get_post_meta( $ticket_id, 'wpas_reply_was_deleted' );

	if ( (int) $post > 0 ) {
		echo '<br />' . '<div class="wpas_footer_note">' . __( '* This ticket has had replies deleted from it.  Depending on your settings at the time of deletion, the logs might have a full history of these edits.', 'awesome-support' ) . '</div>';
	}

}

add_action( 'wp_ajax_wpas_edit_ticket_content', 'wpas_edit_ticket_content' );
add_action( 'wp_ajax_nopriv_wpas_edit_ticket_content', 'wpas_edit_ticket_content' );
/**
 * Save the ticket content from editing
 *
 * @return void
 */
function wpas_edit_ticket_content() {

	/**
	 * The default response
	 */
	$response = array(
		'code'    => 404,
		'message' => __( 'Nothing found!', 'awesome-support' ),
	);

	/**
	 * Variables!
	 */
	$ticket_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
	$content   = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '';

	/**
	 * Make sure we have ticket ID
	 */
	if ( ! $ticket_id ) {
		$response['message'] = __( 'Ticket ID missing. Invalid request!', 'awesome-support' );
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * The updated ticket content is missing, exit
	 */
	if ( ! $content ) {
		$response['message'] = __( 'No ticket message found. Invalid request!', 'awesome-support' );
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Make sure we are on the correct post type
	 */
	$is_ticket = get_post_type( $ticket_id );
	if ( $is_ticket !== 'ticket' ) {
		$response['message'] = __( 'Id provided is not a valid ticket. Invalid request!', 'awesome-support' );
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Make sure that this is valid ticket and it exists
	 */
	$original_content = get_post( $ticket_id );
	if ( is_null( $original_content ) ) {
		$response['message'] = __( 'No ticket found. Invalid request!', 'awesome-support' );
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Compare the original content vs the updated content
	 * If they are not same and have differences, log it
	 * then we update the post content
	 */
	if ( $original_content->post_content !== $content ) {

		/**
		 * Update the content
		 */
		$updated_post_id = wp_update_post(
			array(
				'ID'           => $ticket_id,
				'post_content' => $content,
			)
		);

		if ( is_wp_error( $updated_post_id ) ) {
			$response['message'] = $updated_post_id->get_error_messages();
			$response['content'] = $original_content->post_content;
		} else {
			$response['code']    = 200;
			$response['message'] = __( 'You have successfully edited content!', 'awesome-support' );
			$response['content'] = $content;
			/**
			 * Log the edits to ticket
			 */
			wpas_log_ticket_edits( $ticket_id, $original_content );
		}
	} else {
		$response['code']    = 404;
		$response['message'] = __( 'Nothing has been updated. You have same content as before..', 'awesome-support' );
		$response['content'] = $original_content->post_content;
	}

	wp_send_json( $response );
	wp_die();

}

/**
 * Log the original contents of a ticket after it is edited.
 *
 * @since 5.7.1
 *
 * @param $int  $ticket_id       - the id of the ticket being edited.
 * @param array $original_ticket - the original post before the edited ticket was added to the database
 *
 * @return void
 */
function wpas_log_ticket_edits( $ticket_id, $original_ticket ) {
	
	if ( 'low' === wpas_get_option( 'log_content_edit_level', 'low' ) ) {
		$contents_to_log = __( 'Original data not available because detailed logging is not turned on or allowed', 'awesome-support' );
	} else {
		$contents_to_log = $original_ticket->post_content;
	}
	
	wpas_log_edits( $ticket_id, sprintf( __( 'Ticket content located on ticket #%1$s was edited.', 'awesome-support' ), (string) $ticket_id ), $contents_to_log );	
	
}

add_action( 'wp_ajax_wpas_load_reply_history', 'wpas_load_reply_history' );
add_action( 'wp_ajax_nopriv_wpas_load_reply_history', 'wpas_load_reply_history' );
/**
 * Ajax function that returns a the history of replies
 *
 * @since 3.3
 * @return void
 */
function wpas_load_reply_history() {
	/**
	 * Default response messages
	 */
	$response = array(
		'code'    => 404,
		'message' => __( 'Invalid request!', 'awesome-support' ),
		'data'    => array(),
	);

	/**
	 * Reply ID is required
	 */
	if ( ! isset( $_POST['reply_id'] ) ) {
		wp_send_json( $response );
	}

	/**
	 * Get all reply history
	 */
	$reply_history = get_posts(
		array(
			'post_parent' 		=> sanitize_text_field( $_POST['reply_id'] ),
			'post_type'   		=> 'ticket_log',
			'posts_per_page'	=> 10,  //Maybe this should an option?!
			'orderby'			=> 'ID',
			'order'				=> 'DESC'
		)
	);

	if ( ! empty( $reply_history ) ) {
		/**
		 * Update response
		 */
		$response = array(
			'code'    => 200,
			'message' => __( 'Edit history', 'awesome-support' ),
			'data'    => $reply_history,
		);
		wp_send_json( $response );
	} else {
		$response['code']    = 404;
		$response['message'] = __( 'No edit history found!', 'awesome-support' );
		$response['data']    = '';
		wp_send_json( $response );
	}
	wp_die();
}

/**
 * There an issue with the WPAS Options data
 * in which we cannot determine the GDPRs hierarchy IDs
 * This function will attempt to have workaround.
 * 
 * Returns GDPR Id's
 * NOTE: If the short description is identical, this
 * function will return the first ID
 */
function wpas_get_gdpr_data( $short_description ) {
	$return_id = false;
	if( $short_description === wpas_get_option( 'gdpr_notice_short_desc_01', false ) ) {
		$return_id = 1;
	}elseif( $short_description === wpas_get_option( 'gdpr_notice_short_desc_02', false ) ) {
		$return_id = 2;
	}elseif( $short_description === wpas_get_option( 'gdpr_notice_short_desc_03', false ) ) {
		$return_id = 3;
	}
	
	$return_id = apply_filters('gdpr_consent_data_id', $return_id, $short_description );

	return $return_id;
}

/**
 * Delete post attachments
 * 
 * @param int $post_id
 */
function wpas_delete_post_attachments( $post_id ) {
	
	$attachments = get_attached_media( '', $post_id );
	
	foreach ( $attachments as $attachment ) {
	  wp_delete_attachment( $attachment->ID, true );
	}
}

/**
 * Add custom fields data in cloned ticket
 * 
 * @param int $ticket_id
 * @param array $data
 * @param array $incoming_data
 */
function wpas_clone_ticket_before_assigned( $new_ticket_id, $data, $incoming_data ) {
	
	// Clone custom fields
	$clone_custom_fields_list = is_array( $incoming_data['custom_fields'] ) ? $incoming_data['custom_fields'] : array();
	
	$ticket_id = isset( $incoming_data['clone_ticket_id'] ) ? $incoming_data['clone_ticket_id'] : '';
	
	
	if( !$ticket_id || empty( $clone_custom_fields_list ) ) {
		return;
	}
	
	
	$custom_fields =  WPAS()->custom_fields->get_custom_fields();
	
	foreach( $clone_custom_fields_list as $cf_name )  {
		
		if( !array_key_exists( $cf_name, $custom_fields ) ) {
			continue;
		}
		
		$cf_field = new WPAS_Custom_Field( $cf_name, $custom_fields[ $cf_name ] );
		$cf_value = $cf_field->get_field_value( false, $ticket_id );
		
		
		if( 'taxonomy' ===  $cf_field->field_type ) {
			
			$tax_terms = get_the_terms( $ticket_id, $cf_field->field_id );
		
			if ( is_array( $tax_terms ) ) {
				foreach ( $tax_terms as $term ) {
					$cf_value = $term->term_id;
				}
			}
			
		}
		
		$cf_field->update_value( $cf_value, $new_ticket_id );
	}
	
}



/**
 * Clone a ticket 
 * 
 * @param int $ticket_id			  Ticket ID to clone
 * @param array $args				  Setting to clone ticket
 * @return integer|WP_Error           New ticket ID on success or WP_Error on failure
 */
function wpas_clone_ticket( $ticket_id, $args = array() ) {
	
	
	$defaults = array(
		'clone_replies'				=> true,
		'clone_custom_fields_list'	=> array( "product", "department", "ticket_priority" ),
		'clone_agent'				=> true,
		'cloned_ticket_status'		=> 'queued',
		'suppress_notifications'	=> false,
	);
	
	
	
	$args = wp_parse_args( $args, $defaults );
	
	$args = apply_filters( 'wpas_clone_ticket_args', $args, $ticket_id );
	
	
	
	$ticket = get_post( $ticket_id );
	
	// Check if source ticket id is valid
	if( !$ticket || 'ticket' !== get_post_type( $ticket ) ) {
		return new WP_Error( 'invalid_source_ticket_id', __( 'Source ticket id is not valid.', 'awesome-support' ) );
	}
	
	
	$title = $ticket->post_title;
	
	// Process tags in ticket content
	$emails = new WPAS_Email_Notification( $ticket_id );
	$content = wpautop( str_replace( '\'', '&apos;', $emails->fetch( $ticket->post_content ) ) );
	
	$customer = $ticket->post_author;
	
	$ticket_status = $args['cloned_ticket_status'];
	
	$ticket_data = apply_filters( 'wpas_clone_ticket_data', array(
		'post_content'   => $content,
		'post_name'      => $title,
		'post_title'     => $title,
		'post_status'    => $ticket_status,
		'post_type'      => 'ticket',
		'post_author'    => $customer,
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
	), $ticket_id, $args );
	
	
	$clone_agent   = $args['clone_agent'];
	$agent_id = $clone_agent ?  get_post_meta( $ticket_id, '_wpas_assignee', true ) : false;
	
	
	$agent_id = apply_filters( 'wpas_clone_ticket_agent_id', $agent_id, $ticket, $args );
	
	
	$ticket_data['custom_fields'] = is_array( $args['clone_custom_fields_list'] ) ? $args['clone_custom_fields_list'] : array();
	$ticket_data['clone_ticket_id'] = $ticket_id;
	
	// Prevent notification while cloning ticket
	if( $args['suppress_notifications'] ) {
		remove_action( 'wpas_open_ticket_after', 'wpas_notify_confirmation', 11 );
		remove_action( 'wpas_open_ticket_after', 'wpas_notify_assignment', 12 );
	}
	
	
	add_action( 'wpas_open_ticket_before_assigned', 'wpas_clone_ticket_before_assigned', 11, 3 );
	
	$new_ticket_id = wpas_insert_ticket( $ticket_data, false, $agent_id );
	
	remove_action( 'wpas_open_ticket_before_assigned', 'wpas_clone_ticket_before_assigned', 11 );
	
	// Add removed notification hooks back
	if( $args['suppress_notifications'] ) {
		add_action( 'wpas_open_ticket_after', 'wpas_notify_confirmation', 11, 2 );
		add_action( 'wpas_open_ticket_after', 'wpas_notify_assignment', 12, 2 );
	}
	
	if( !$new_ticket_id ) {
		return new WP_Error( 'ticket_clone_failed', __( 'Ticket cloning failed', 'awesome-support' ) );
	}
	
	do_action( 'wpas_clone_ticket_added_after', $new_ticket_id, $ticket, $args );
	
	
	// Clone replies
	$clone_replies = $args['clone_replies'];
	if( $clone_replies ) {
		
		$replies = wpas_get_replies( $ticket_id );
		
		if( $args['suppress_notifications'] ) {
			remove_action( 'wpas_add_reply_complete', 'wpas_notify_reply', 10 );
		}
		
		foreach( $replies as $reply ) {
			
			$reply_data = array(
				'post_content'   => $reply->post_content,
				'post_name'      => sprintf( __( 'Reply to ticket %s', 'awesome-support' ), "#$new_ticket_id" ),
				'post_title'     => sprintf( __( 'Reply to ticket %s', 'awesome-support' ), "#$new_ticket_id" ),
				'post_status'    => 'unread',
				'post_type'      => 'ticket_reply',
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_parent'    => $new_ticket_id,
				'post_author'	 => $reply->post_author
			);

			$reply_id = wpas_insert_reply( $reply_data, $new_ticket_id );
			
			do_action( 'wpas_clone_ticket_reply_added_after', $reply_id, $new_ticket_id, $ticket, $args );
		}
		
		if( $args['suppress_notifications'] ) {
			add_action( 'wpas_add_reply_complete', 'wpas_notify_reply', 10, 2 );
		}
		
	}
	
	
	do_action( 'wpas_clone_ticket_completed_after', $new_ticket_id, $ticket, $args );
	
	return $new_ticket_id;
	
}