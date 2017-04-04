<?php
/**
 * @package   Awesome Support/Admin/Functions/Post
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'wp_insert_post_data', 'wpas_filter_ticket_data', 99, 2 );
/**
 * Filter ticket data before insertion.
 *
 * Before inserting a new ticket in the database,
 * we check the post status and possibly overwrite it
 * with one of the registered custom status.
 *
 * @since  3.0.0
 *
 * @param  array $data    Post data
 * @param  array $postarr Original post data
 *
 * @return array          Modified post data for insertion
 */
function wpas_filter_ticket_data( $data, $postarr ) {

	global $current_user;

	if ( ! isset( $data['post_type'] ) || 'ticket' !== $data['post_type'] ) {
		return $data;
	}

	/**
	 * If the ticket is being trashed we don't do anything.
	 */
	if ( 'trash' === $data['post_status'] ) {
		return $data;
	}

	/**
	 * Do not affect auto drafts
	 */
	if ( 'auto-draft' === $data['post_status'] ) {
		return $data;
	}

	/**
	 * Automatically set the ticket as processing if this is the first reply.
	 */
	if ( user_can( $current_user->ID, 'edit_ticket' ) && isset( $postarr['ID'] ) ) {

		$replies       = wpas_get_replies( intval( $postarr['ID'] ) );
		$agent_replied = false;

		if ( 0 !== count( $replies ) ) {

			foreach ( $replies as $reply ) {
				if ( user_can( $reply->post_author, 'edit_ticket' ) ) {
					$agent_replied = true;
					break;
				}
			}

		}

		if ( false === $agent_replied && ( ! isset( $_POST['post_status_override'] ) || 'queued' === $_POST['post_status_override'] ) ) {
			$_POST['post_status_override'] = 'processing';
		}

	}

	if ( isset( $_POST['post_status_override'] ) && ! empty( $_POST['post_status_override'] ) ) {

		$status = wpas_get_post_status();

		if ( array_key_exists( $_POST['post_status_override'], $status ) ) {

			$data['post_status'] = $_POST['post_status_override'];

			if ( $postarr['original_post_status'] !== $_POST['post_status_override'] && isset( $_POST['wpas_post_parent'] ) ) {
				wpas_log( intval( $_POST['wpas_post_parent'] ), sprintf( __( 'Ticket state changed to %s', 'awesome-support' ), '&laquo;' . $status[ $_POST['post_status_override'] ] . '&raquo;' ) );
			}
		}

	}

	return $data;
}

add_action( 'save_post_ticket', 'wpas_save_ticket' );
/**
 * Save ticket custom fields.
 *
 * This function will save all custom fields associated
 * to the ticket post type. Be it core custom fields
 * or user added custom fields.
 *
 * @param  (int) $post_id Current post ID
 *
 * @since  3.0.0
 */
function wpas_save_ticket( $post_id ) {

	/* We should already being avoiding Ajax, but let's make sure */
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	/* Now we check the nonce */
	if ( ! isset( $_POST['wpas_cf'] ) || ! wp_verify_nonce( $_POST['wpas_cf'], 'wpas_update_cf' ) ) {
		return;
	}

	/* Does the current user has permission? */
	if ( ! current_user_can( 'edit_ticket', $post_id ) ) {
		return;
	}

	global $current_user;

	/**
	 * Store possible logs
	 */
	$log = array();
	
	/* Now we can save the custom fields */
	WPAS()->custom_fields->save_custom_fields( $post_id, $_POST );

	/**
	 * If no ticket status is found we are in the situation where
	 * the agent is creating a ticket on behalf of the user. There are
	 * a couple of things that we need to do then.
	 */
	if ( '' === $original_status = get_post_meta( $post_id, '_wpas_status', true ) ) {

		/**
		 * First of all, set the ticket as open. This is very important.
		 */
		add_post_meta( $post_id, '_wpas_status', 'open', true );

		/**
		 * Send the confirmation e-mail to the user.
		 *
		 * @since  3.1.5
		 */
		wpas_email_notify( $post_id, 'submission_confirmation' );

	}

	/* Save the possible ticket reply */
	if ( isset( $_POST['wpas_reply'] ) && isset( $_POST['wpas_reply_ticket'] ) && '' !== $_POST['wpas_reply'] ) {

		/* Check for the nonce */
		if ( wp_verify_nonce( $_POST['wpas_reply_ticket'], 'reply_ticket' ) ) {

			$user_id = $current_user->ID;
			$content = wp_kses_post( $_POST['wpas_reply'] );

			$data = apply_filters( 'wpas_post_reply_admin_args', array(
				'post_content'   => $content,
				'post_status'    => 'read',
				'post_type'      => 'ticket_reply',
				'post_author'    => $user_id,
				'post_parent'    => $post_id,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
			) );

			/**
			 * Remove the save_post hook now as we're going to trigger
			 * a new one by inserting the reply (and logging the history later).
			 */
			remove_action( 'save_post_ticket', 'wpas_save_ticket' );

			/**
			 * Fires right before a ticket reply is submitted
			 *
			 * @since 3.2.6
			 *
			 * @param int   $post_id Ticket ID
			 * @param array $data    Data to be inserted as the reply
			 */
			do_action( 'wpas_post_reply_admin_before', $post_id, $data );

			/* Insert the reply in DB */
			$reply = wpas_add_reply( $data, $post_id );

			/**
			 * Fires right after a ticket reply is submitted
			 *
			 * @since 3.2.6
			 *
			 * @param int      $post_id Ticket ID
			 * @param array    $data    Data to be inserted as the reply
			 * @param bool|int Reply    ID on success, false on failure
			 */
			do_action( 'wpas_post_reply_admin_after', $post_id, $data, $reply );

			/* In case the insertion failed... */
			if ( is_wp_error( $reply ) ) {

				/* Set the redirection */
				$_SESSION['wpas_redirect'] = add_query_arg( array( 'wpas-message' => 'wpas_reply_error' ), get_permalink( $post_id ) );

			} else {

				/* E-Mail the client */
				new WPAS_Email_Notification( $post_id, array(
					'reply_id' => $reply,
					'action'   => 'reply_agent'
				) );

				/* The agent wants to close the ticket */
				if ( isset( $_POST['wpas_do'] ) && 'reply_close' == $_POST['wpas_do'] ) {

					/* Confirm the post type and close */
					if ( 'ticket' == get_post_type( $post_id ) ) {

						/**
						 * wpas_ticket_before_close_by_agent hook
						 */
						do_action( 'wpas_ticket_before_close_by_agent', $post_id );

						/* Close */
						wpas_close_ticket( $post_id );

						/* E-Mail the client */
						new WPAS_Email_Notification( $post_id, array( 'action' => 'closed' ) );

						/**
						 * wpas_ticket_closed_by_agent hook
						 */
						do_action( 'wpas_ticket_closed_by_agent', $post_id );
					}

				}

			}

		}

	}


	/* Log the action */
	if ( ! empty( $log ) ) {
		wpas_log( $post_id, $log );
	}

	/* If this was a ticket update, we need to know where to go now... */
	if ( '' !== $original_status ) {

		$gt_post      = null;
		$where_after  = filter_input( INPUT_POST, 'where_after', FILTER_SANITIZE_STRING );
		$back_to_list = filter_input( INPUT_POST, 'wpas_back_to_list', FILTER_SANITIZE_NUMBER_INT );

		if ( true === (bool) $back_to_list ) {
			$where_after = 'back_to_list';
		}

		switch ( $where_after ) {

			/* Go back to the tickets list */
			case 'back_to_list':
				WPAS()->session->add( 'redirect', add_query_arg( array( 'post_type' => 'ticket' ), admin_url( 'edit.php' ) ) );
				break;

			case 'next_ticket':
				$gt_post = wpas_get_next_ticket( $post_id );
				break;

			case 'previous_ticket':
				$gt_post = wpas_get_previous_ticket( $post_id );
				break;

		}

		/* Go to next or previous ticket */
		if ( $gt_post ) {
			WPAS()->session->add( 'redirect', add_query_arg( array(
				'post'   => $gt_post,
				'action' => 'edit',
			), admin_url( 'post.php' ) ) );
		}
	}

}

add_action( 'wpas_add_reply_after', 'wpas_mark_replies_read', 10, 2 );
/**
 * Mark replies as read.
 *
 * When an agent replies to a ticket, we mark all previous replies
 * as read. We suppose it's all been read when the agent replies.
 * This allows for keeping replies unread until an agent replies
 * or manually marks the last reply as read.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_mark_replies_read( $reply_id, $data ) {

	$replies = wpas_get_replies( intval( $data['post_parent'] ), 'unread' );

	foreach ( $replies as $reply ) {
		wpas_mark_reply_read( $reply->ID );
	}

}

add_action( 'before_delete_post', 'wpas_delete_ticket_dependencies', 10, 1 );
/**
 * Delete ticket dependencies.
 *
 * Delete all ticket dependencies when a ticket is deleted. This includes
 * ticket replies and ticket history. Ticket attachments are deleted by
 * WPAS_File_Upload::delete_attachments()
 *
 * @param  integer $post_id ID of the post to be deleted
 *
 * @return void
 */
function wpas_delete_ticket_dependencies( $post_id ) {

	global $post_type;

	if ( 'ticket' !== $post_type ) {
		return;
	}

	/* First of all we remove this action to avoid creating a loop */
	remove_action( 'before_delete_post', 'wpas_delete_ticket_dependencies', 10 );

	$args = array(
		'post_parent'            => $post_id,
		'post_type'              => apply_filters( 'wpas_replies_post_type', array(
			'ticket_history',
			'ticket_reply'
		) ),
		'post_status'            => 'any',
		'posts_per_page'         => - 1,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);

	$posts = new WP_Query( $args );

	foreach ( $posts->posts as $id => $post ) {

		do_action( 'wpas_before_delete_dependency', $post->ID, $post );

		wp_delete_post( $post->ID, true );

		do_action( 'wpas_after_delete_dependency', $post->ID, $post );
	}

	/* Decrement the number of tickets open for this agent */
	$agent_id = get_post_meta( $post_id, '_wpas_assignee', true );
	$agent    = new WPAS_Member_Agent( $agent_id );
	$agent->ticket_minus();

}


add_filter( 'redirect_post_location', 'wpas_redirect_ticket_after_save', 10, 2 );

/**
 * Redirect user after updating ticket
 *
 * @param string $location The redirect URL.
 * @param int    $post_id  ID of the post being saved.
 *
 * @return string
 */
function wpas_redirect_ticket_after_save( $location, $post_id ) {
	if ( is_admin() ) {

		$post = get_post( $post_id );

		if ( $post && 'ticket' === $post->post_type ) {

			// Get the redirect location.
			$redirect = WPAS()->session->get( 'redirect' );

			if ( false !== $redirect && filter_var( $redirect, FILTER_VALIDATE_URL ) !== false ) {
				$location = $redirect;
				WPAS()->session->clean( 'redirect' );
			}
		}
	}

	return $location;

}

/**
 * Get next id
 * @param int $current_ticket
 * 
 * @return int
 */
function wpas_get_next_ticket( $current_ticket ) {
	
	return wpas_get_adjacent_ticket( $current_ticket );
	
}

/**
 * Get previous id
 * @param int $current_ticket
 * 
 * @return int
 */
function wpas_get_previous_ticket( $current_ticket ) {
	
	return wpas_get_adjacent_ticket( $current_ticket, false );
	
}


/**
 * 
 * @global object $wpdb
 * @global object $current_user
 * @param int $ticket_id
 * @param boolean $next
 * 
 * @return int
 */
function wpas_get_adjacent_ticket( $ticket_id , $next = true ) {
	
	global $wpdb, $current_user;

	/* Make sure this is the admin screen */
	if ( ! is_admin() ) {
		return false;
	}
	
	if ( true === $next ) {
		$adjacent = '>';
		$order_type = 'ASC';
	} else {
		$adjacent = '<';
		$order_type = 'DESC';
	}
	
	
	$query_args = array();
	$current_user_can_see_all = false;
	
	
	$query = "SELECT ID FROM {$wpdb->posts} p";
	
	
	/* If admins can see all tickets do nothing */
	if ( current_user_can( 'administrator' ) && true === (bool) wpas_get_option( 'admin_see_all' ) ) {
		$current_user_can_see_all = true;
	}

	/* If agents can see all tickets do nothing */
	if ( current_user_can( 'edit_ticket' ) && ! current_user_can( 'administrator' ) && true === (bool) wpas_get_option( 'agent_see_all' ) ) {
		$current_user_can_see_all = true;
	}

	
	/* If current user can see all tickets do nothing */
	if ( current_user_can( 'view_all_tickets' ) && ! current_user_can( 'administrator' ) && true === (bool) get_user_meta( (int) $current_user->ID, 'wpas_view_all_tickets', true )  ) {
		$current_user_can_see_all = true;
	}
	
	
	if ( false === $current_user_can_see_all ) {
		$query .= " INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN(%s, %s, %s) AND meta_value=%s";
		$query_args = array( '_wpas_assignee', '_wpas_secondary_assignee', '_wpas_tertiary_assignee', $current_user->ID );
	}
	
	$query .= " WHERE p.post_type = %s AND p.ID {$adjacent} %d";
	$query_args[] = 'ticket';
	$query_args[] = $ticket_id;
	
	
	$custom_post_status = wpas_get_post_status();
	$custom_post_status['open'] = 'Open';
	
	$post_status_query_ar = array_fill(0, count($custom_post_status), "p.post_status=%s");
	$query .= " AND (" . implode(' OR ', $post_status_query_ar) . ")";
	
	
	foreach($custom_post_status as $status => $label) {
		$query_args[] = $status;
	}
	$query .= " GROUP BY p.ID ORDER BY p.ID {$order_type} LIMIT 1";
	
	$query = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $query ), $query_args ) );
	$adjacent_post_id = $wpdb->get_var( $query );
	
	return $adjacent_post_id;
	
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
	if ( current_user_can( 'administrator' ) && true === (bool) wpas_get_option( 'admin_see_all' ) ) {
		$user_can_see_all = true;
	}

	/* Check if agents can see all tickets */
	if ( current_user_can( 'edit_ticket' ) && ! current_user_can( 'administrator' ) && true === (bool) wpas_get_option( 'agent_see_all' ) ) {
		$user_can_see_all = true;
	}

	global $current_user;
	
	/* If current user can see all tickets */
	if ( current_user_can( 'view_all_tickets' ) && ! current_user_can( 'administrator' ) && true === (bool) get_user_meta( (int) $current_user->ID, 'wpas_view_all_tickets', true )  ) {
		$user_can_see_all = true;
	}
	
	return $user_can_see_all;
}

/**
 * 
 * @global object $wpdb
 * @global object $current_user
 * @param int $ticket_id
 * @param boolean $next
 * 
 * @return int
 */
function wpas_get_agent_tickets( $args = array(), $ticket_status = 'any' ) {
	
	global $current_user;
	
	$custom_post_status = wpas_get_post_status();
	$custom_post_status['open'] = 'Open';
	
	foreach($custom_post_status as $status => $label) {
		$post_status[] = $status;
	}
	
	
	$defaults = array(
		'post_type'              => 'ticket',
		'post_status'            => $post_status,
		'posts_per_page'         => - 1
	);

	$args  = wp_parse_args( $args, $defaults );
	
	$meta_query = array();
	
	if ( 'any' !== $ticket_status ) {
		if ( in_array( $ticket_status, array( 'open', 'closed' ) ) ) {
			$meta_query[] = array(
					'key'     => '_wpas_status',
					'value'   => $ticket_status,
					'compare' => '=',
					'type'    => 'CHAR'
			);
		}
	}
	
	
	
	$user_can_see_all = wpas_can_user_see_all_tickets();
	
	
	if( false === $user_can_see_all ) {
		
		$primary_agent_meta_query = array(
		'key'     => '_wpas_assignee',
		'value'   => (int) $current_user->ID,
		'compare' => '=',
		'type'    => 'NUMERIC',
		);
	
		if( wpas_is_multi_agent_active() ) {
			// Check if agent is set as secondary or tertiary agent
			$multi_agents_meta_query = array();
			$multi_agents_meta_query['relation'] = 'OR';
			$multi_agents_meta_query[] = $primary_agent_meta_query;

			$multi_agents_meta_query[] = array(
				'key'     => '_wpas_secondary_assignee',
				'value'   => (int) $current_user->ID,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);

			$multi_agents_meta_query[] = array(
				'key'     => '_wpas_tertiary_assignee',
				'value'   => (int) $current_user->ID,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);

			$meta_query[] = $multi_agents_meta_query;

		} else {
			$meta_query[] = $primary_agent_meta_query;
		}
	}
		
	if( !empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}
	
	$query = new WP_Query( $args );
	if ( empty( $query->posts ) ) {
		return array();
	} else {
		return $query->posts;
	}
	
}