<?php
/**
 * Open a new ticket.
 *
 * @since  3.0.0
 * @param  array $data Ticket data
 * @return boolean
 */
function wpas_open_ticket( $data ) {

	$title   = isset( $data['title'] ) ? wp_strip_all_tags( $data['title'] ) : false;
	$content = isset( $data['message'] ) ? wp_kses( $data['message'], wp_kses_allowed_html( 'post' ) ) : false;

	/**
	 * Prepare vars
	 */
	$submit = isset( $_POST['_wp_http_referer'] ) ? wpas_get_submission_page_url( url_to_postid( $_POST['_wp_http_referer'] ) ) : wpas_get_submission_page_url();

	// Fallback in case the referrer failed
	if ( empty( $submit ) ) {
		$submission_pages = wpas_get_option( 'ticket_submit' );
		$submit           = $submission_pages[0];
		$submit           = wp_sanitize_redirect( get_permalink( $submit ) );
	}

	// Verify user capability
	if ( !current_user_can( 'create_ticket' ) ) {

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
	$go = apply_filters( 'wpas_before_submit_new_ticket_checks', true );

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
	$post = apply_filters( 'wpas_open_ticket_data', array(
		'post_content'   => $content,
		'post_name'      => $title,
		'post_title'     => $title,
		'post_status'    => 'queued',
		'post_type'      => 'ticket',
		'post_author'    => $user_id,
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
	) );

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

		$ticket_id = wpas_open_ticket( array( 'title' => $data['wpas_title'], 'message' => $data['wpas_message'] ) );

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
			if ( ! empty( wpas_get_option( 'new_ticket_redirect_fe','' ) ) ) {
				wpas_redirect( 'ticket_added', wpas_get_option( 'new_ticket_redirect_fe','' ), $ticket_id );
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
 * @param array    $data     		Ticket (post) data
 * @param bool|int $post_id  		Post ID for an update
 * @param bool|int $agent_id		ID of the agent to assign ticket to
 * @param string   $channel_term	Source of the ticket
 *
 * @return bool|int|WP_Error
 */
function wpas_insert_ticket( $data = array(), $post_id = false, $agent_id = false, $channel_term = 'other' ) {
	
	// Save the original data array
	$incoming_data = $data ;

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
	if ( isset( $data['post_name'] ) && !empty( $data['post_name'] ) ) {
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
	If ( ! $update ) {
		wpas_set_ticket_slug($ticket_id);
	}
	
	/* Update the channel on the ticket so that hooks can access it - but only if the $update is false which means we've got a new ticket */
	/* It will need to be re-added to the ticket at the bottom of this routine because some hooks overwrite it with a blank. */
	If (! empty( $channel_term ) && ( ! $update ) ) {
		wpas_set_ticket_channel( $ticket_id , $channel_term, false );
	}		

	/* Set the ticket as open. */
	add_post_meta( $ticket_id, '_wpas_status', 'open', true );

	/* Next - update other some meta values. If you add or delete from this list you also */
	/* need to do the same thing in the /includes/admin/functions-post.php file */
	add_post_meta( $ticket_id, '_wpas_last_reply_date', null, true );
	add_post_meta( $ticket_id, '_wpas_last_reply_date_gmt', null, true );
	add_post_meta( $ticket_id, '_wpas_is_waiting_client_reply', ! user_can( $data['post_author'], 'edit_ticket' ), true  );

	if ( false === $agent_id ) {
		$agent_id = wpas_find_agent( $ticket_id );
	}
				
	
	/**
	 * Fire wpas_open_ticket_before_assigned after the post is successfully submitted but before it has been assigned to an agent.
	 *
	 * @since 3.2.6
	 */
	do_action( 'wpas_open_ticket_before_assigned', $ticket_id, $data, $incoming_data );	
	
	/* Assign an agent to the ticket */
	wpas_assign_ticket( $ticket_id, apply_filters( 'wpas_new_ticket_agent_id', $agent_id, $ticket_id, $agent_id ), false );

	/* Update the channel on the ticket - but only if the $update is false which means we've got a new ticket */
	/* Need to update it here again because some of the action hooks fired above will overwrite the term.			  */
	If (! empty( $channel_term ) && ( ! $update ) ) {
		wpas_set_ticket_channel( $ticket_id , $channel_term, false );
	}	
	
	/**
	 * Fire wpas_after_open_ticket just after the post is successfully submitted and assigned.
	 */
	do_action( 'wpas_open_ticket_after', $ticket_id, $data );

	do_action( 'wpas_tikcet_after_saved', $ticket_id );
	
	return $ticket_id;

}

/**
 * Set the channel (ticket source) term/field
 *
 * @since 3.4.0
 *
 * @param numeric		$ticket_id
 * @param string		$channel_term
 * @param string		$overwrite	whether or not to overwrite existing channel on the ticket - set to false by default 
 *
 * @return void
 */
 function wpas_set_ticket_channel( $ticket_id = -1, $channel_term = 'other', $overwrite = false ) {
	 
	 /* Does a term already exist on the ticket?  If so, do not overwrite it if $overwrite is false */
	 if ( false === $overwrite ) {
		 $existing_channel = wp_get_post_terms($ticket_id,'ticket_channel');
		 if ( ! empty( $existing_channel ) ) {
			 return ;
		 }
	 }	 

	/* Get the term id because wp_set_object_terms require an id instead of just a string */
	$arr_the_term_id = term_exists( $channel_term, 'ticket_channel' );

	If ( $arr_the_term_id ) {
			
		// Need to get array keys first so we can index and extract the first element in the wp_set_object_terms below.
		$arr_the_term_id_keys = array_keys($arr_the_term_id);  
		$int_the_term_id = (int) $arr_the_term_id[ $arr_the_term_id_keys[0] ];

		// Now add the terms (this function call doesn't work consistently for some reason!)
		$term_taxonomy_ids = wp_set_object_terms( $ticket_id, (int) $int_the_term_id , 'ticket_channel' );

	} 
	
	return;
 }

/**
 * Set ticket slug on new tickets if the admin chooses anything other than the default slug.
 *
 * @since 3.4.0
 *
 * @param numeric		$ticket_id
 *
 * @return void
 */
 function wpas_set_ticket_slug( $ticket_id = -1 ) {
 	$use_ticket_id_for_slug = wpas_get_option('ticket_topic_slug');  

	/* Set ticket slug to the post id / ticket id */
	If ( isset( $use_ticket_id_for_slug ) &&  ('ticketid' == $use_ticket_id_for_slug ) ) {
		
		/* Set the data to be updated - in this case just post_name (slug) with the key being the ID passed into this function */
		$newdata = array(
				'ID'			=> $ticket_id,
				'post_name'		=> (string) $ticket_id
		);
		
		/* Update the post with the new slug */
		wp_update_post ($newdata);
	} 	
	
	/* Set ticket slug to a random number  */
	If ( isset( $use_ticket_id_for_slug ) &&  ('randomnumber' == $use_ticket_id_for_slug ) ) {
		
		/*Calculate a random number */
		$randomslug = mt_rand();

		/* Set the data to be updated - in this case just post_name (slug) with the key being the ID passed into this function */
		$newdata = array(
				'ID'			=> $ticket_id,
				'post_name'		=> (string) $randomslug
		);
		
		/* Update the post with the new slug */
		wp_update_post ($newdata);
	} 		

	/* Set ticket slug to a GUID  */
	If ( isset( $use_ticket_id_for_slug ) &&  ('guid' == $use_ticket_id_for_slug ) ) {
		
		/*Calculate a guid */
		$randomguid = wpas_create_pseudo_guid();
		
		/* Set the data to be updated - in this case just post_name (slug) with the key being the ID passed into this function */
		$newdata = array(
				'ID'			=> $ticket_id,
				'post_name'		=> $randomguid
		);
		
		/* Update the post with the new slug */
		wp_update_post ($newdata);
	} 		
	
	return ;
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

	$args  = wp_parse_args( $args, $defaults );

	if ( 'any' !== $ticket_status ) {
		if ( in_array( $ticket_status, array( 'open', 'closed' ) ) ) {
			$args['meta_query'][] = array(
					'key'     => '_wpas_status',
					'value'   => $ticket_status,
					'compare' => '=',
					'type'    => 'CHAR'
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

	if ( ! empty( $data['wpas_user_reply'] ) && apply_filters( 'wpas_user_can_reply_ticket', true, $ticket_id ) ) {

		/* Sanitize the data */
		$data = array( 'post_content' => wp_kses( $data['wpas_user_reply'], wp_kses_allowed_html( 'post' ) ) );

		/* Add the reply */
		$reply_id = wpas_add_reply( $data, $parent_id );

	}

	/* Possibly close the ticket */
	if ( $close && apply_filters( 'wpas_user_can_close_ticket', true, $ticket_id ) ) {

		wpas_close_ticket( $parent_id );

		// Redirect now if no reply was posted
		if ( ! isset( $reply_id ) ) {
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

			if ( $close ) {
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

	if ( !current_user_can( 'reply_ticket' ) ) {
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
	update_post_meta( $data[ 'post_parent' ], '_wpas_last_reply_date', current_time( 'mysql' ) );
	update_post_meta( $data[ 'post_parent' ], '_wpas_last_reply_date_gmt', current_time( 'mysql', 1 ) );

	update_post_meta( $data[ 'post_parent' ], '_wpas_is_waiting_client_reply', ! current_user_can( 'edit_ticket' )  );

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
		'unread'
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
			$agent = array( 'tickets' => $count, 'user_id' => $user->ID );
		} else {

			if ( $count < $agent['tickets'] ) {
				$agent = array( 'tickets' => $count, 'user_id' => $user->ID );
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

		if ( !empty( $value ) ) {
			$fields[$key] = $value;
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
		wpas_log( $post_id, sprintf( __( 'Ticket state changed to %s', 'awesome-support' ), $custom_status[$status] ) );
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

		$update = update_post_meta( intval( $ticket_id ), '_wpas_status', 'closed' );

		// Save the date at which the ticket was last closed. The date is updated if the ticket is re-opened and then re-closed.
		update_post_meta( $ticket_id, '_ticket_closed_on', current_time( 'mysql' ) );
		update_post_meta( $ticket_id, '_ticket_closed_on_gmt', current_time( 'mysql', 1 ) );

		/* Decrement the number of tickets open for this agent */
		$agent_id = get_post_meta( $ticket_id, '_wpas_assignee', true );
		$agent    = new WPAS_Member_Agent( $agent_id );
		$agent->ticket_minus();

		/* Log the action */
		wpas_log( $ticket_id, __( 'The ticket was closed.', 'awesome-support' ) );

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
	wpas_log( $ticket_id, __( 'The ticket was re-opened.', 'awesome-support' ) );

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
		'teeny' 		=> true,
		'quicktags' 	=> false,
		'editor_class' 	=> 'wpas-edittextarea',
		'textarea_name' => 'wpas_edit_reply[' . $reply_id . ']',
		'textarea_rows' => 20
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
function wpas_get_ticket_count_by_status( $state = '', $status = 'open' ) {

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

	return count( wpas_get_tickets( $status, $args ) );

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
	$replies        = wpas_get_replies( $ticket_id, 'any', array(
		'posts_per_page' => $number_replies,
		'no_found_rows'  => false,
		'offset'         => $offset
	), 'wp_query' );

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

		wpas_get_template( 'partials/ticket-reply', array(
			'time_ago' => $time_ago,
			'user'     => $user,
			'post'     => $replies->post
		) );

		$reply = ob_get_contents();

		ob_end_clean();

		$html[] = $reply;

	}

	$output['html'] = implode( '', $html );

	echo json_encode( $output );
	die();

}