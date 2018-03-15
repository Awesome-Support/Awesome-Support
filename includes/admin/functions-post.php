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
		
		
		// @TODO:  Its possible that this entire section of code to set the $agent_replied flag might not be needed.
		// We'll keep it for now but its not used in this function at this time.
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

		// @TODO: Its possible this if statement below might need an additional qualifier to see if $agent_replied = true.
		// For now the ticket is going to IN PROCESS properly but if there is an issue later then using the additional 
		// qualifier might be warranted.
		if ( ! isset( $_POST['post_status_override'] ) || 'queued' === $_POST['post_status_override'] ) {
			$_POST['post_status_override'] = 'processing';
		}

	}

	if ( isset( $_POST['post_status_override'] ) && ! empty( $_POST['post_status_override'] ) ) {

		$status = wpas_get_post_status();

		if ( array_key_exists( $_POST['post_status_override'], $status ) ) {

			$data['post_status'] = $_POST['post_status_override'];

			if ( isset($postarr['original_post_status']) && $postarr['original_post_status'] !== $_POST['post_status_override'] && isset( $_POST['wpas_post_parent'] ) ) {
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

	/* Does the current user have permission? */
	if ( ! current_user_can( 'edit_ticket', $post_id ) ) {
		return;
	}

	global $current_user;

	/**
	 * Store possible logs
	 */
	$log = array();

	/**
	 * Save old assignee - will need to pass it to action hooks later
	 */ 
	 $old_assignee = get_post_meta( $post_id, '_wpas_assignee', true );
	
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
		
		/* Next - update other some meta values. If you add or delete from this list you also */
		/* need to do the same thing in the /includes/functions-post.php file */
		add_post_meta( $post_id, '_wpas_last_reply_date', null, true );
		add_post_meta( $post_id, '_wpas_last_reply_date_gmt', null, true );
		
		/* Set the slug */
		wpas_set_ticket_slug( $post_id );
		
		/**
		 * Fire hook when a new ticket is being added - works great for notifications
		 *
		 * @since 4.0.0
		 *
		 * @param int   $post_id Ticket ID
		 */
		do_action( 'wpas_post_new_ticket_admin', $post_id );

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
				
				// Fire action hook for failed reply inserted via admin
				do_action( 'wpas_insert_reply_admin_failed', $post_id, $data, $reply );

				/* Set the redirection */
				$_SESSION['wpas_redirect'] = add_query_arg( array( 'wpas-message' => 'wpas_reply_error' ), get_permalink( $post_id ) );

			} else {
				
				/**
				 * Fire action hook for reply inserted via admin - great place for notifications...
				 */								
				do_action( 'wpas_insert_reply_admin_success', $post_id, $data, $reply );

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

	/* If this was a ticket update, we need to fire some action hooks and then figure out where to go next... */
	if ( '' !== $original_status ) {
		
		/**
		 * Fire action hook for after ticket update...
		 *
		 * @since 4.0.0
		 */
		do_action( 'wpas_ticket_after_update_admin_success', $post_id, $old_assignee, $_POST);	

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
	
	do_action( 'wpas_tikcet_after_saved', $post_id );

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
 *
 * @param $reply_id
 * @param $data
 *
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
	
	$custom_post_status = wpas_get_post_status();
	$custom_post_status['open'] = 'Open';
	
	$meta_query = wpas_ticket_listing_assignee_meta_query_args();
	
	$args = array(
		'post_type' => 'ticket',
		'posts_per_page' => 1,
		'orderby' => 'ID',
		'order' => $order_type,
		'post_status' => array_keys( $custom_post_status ),
		'meta_query' => $meta_query,
		'next_previous_adjacent' => "{$adjacent} {$ticket_id}",
		'wpas_tickets_query' => 'listing'
	);
	
	$query = new WP_Query( $args );
	
	$adjacent_post_id = '';
	
	if ( !empty( $query->posts ) ) {
		$adjacent_post_id = $query->posts[0]->ID;
	} 
	
	return $adjacent_post_id;
}

add_filter( 'posts_clauses', 'wpas_get_adjacent_ticket_posts_clauses', 30, 2 );

/**
 * Modify get_adjacent_ticket query
 * 
 * @global object $wpdb
 * @param array $pieces
 * @param object $wp_query
 * 
 * @return array
 */
function wpas_get_adjacent_ticket_posts_clauses( $pieces , $wp_query ) {
	global $wpdb;
	
	if ( isset( $wp_query->query['next_previous_adjacent'] ) ) {
		$adjacent = $wp_query->query['next_previous_adjacent'];
		$pieces['where'] = "AND ({$wpdb->posts}.ID {$adjacent} ) " . $pieces['where'];
	}
	
	return $pieces;
}

/**
 *
 * @param array  $args
 * @param string $ticket_status
 *
 * @return array|int
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
	
	
	
	$meta_query = wpas_ticket_listing_assignee_meta_query_args();
		
	if( !empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}
	
	$args['wpas_tickets_query'] = 'listing';
	
	$query = new WP_Query( $args );
	if ( empty( $query->posts ) ) {
		return array();
	} else {
		return $query->posts;
	}
	
}

/**
 * Return meta query args for ticket listing query relative to assignee
 * 
 * @param type $use_id
 * @return type
 */
function wpas_ticket_listing_assignee_meta_query_args( $user_id = 0, $profile_filter = true ) {
	
	if( 0 ===  $user_id ) {
		$user_id = get_current_user_id();
	}
	
	$user_can_see_all = wpas_can_user_see_all_tickets();
	
	$meta_query = array();
	
	if( false === $user_can_see_all ) {
		
		$primary_agent_meta_query = array(
			'key'     => '_wpas_assignee',
			'value'   => (int) $user_id,
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
				'value'   => (int) $user_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);

			$multi_agents_meta_query[] = array(
				'key'     => '_wpas_tertiary_assignee',
				'value'   => (int) $user_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);

			$meta_query[] = $multi_agents_meta_query;

		} else {
			$meta_query[] = $primary_agent_meta_query;
		}
	}
	
	return apply_filters( 'wpas_assignee_meta_query', $meta_query, $user_id, $profile_filter );
	
}


/**
 * Generate a link with icon for a reply action
 * 
 * @param string $id
 * @param array $args
 * 
 * @return string
 */
function wpas_reply_control_item( $id , $args = array() ) {
	
	$link = isset( $args['link'] ) ? $args['link'] : '#';
	$title = isset( $args['title'] ) ? $args['title'] : '';
	
	$icon = isset( $args['icon'] ) && $args['icon'] ? $args['icon'] : false;
	
	$attr_id = isset( $args['id'] ) && $args['id'] ? $args['id'] : '';
	
	$classes = isset( $args['classes'] ) ? $args['classes'] : '';
	$classes .= " {$id}";
	$classes .= ( $icon ? ' reply_icon' : '' );
	$classes .= $title ? ' hint-bottom hint-anim' : '';
	
	$data_params = isset( $args['data'] ) && is_array( $args['data'] ) ?  $args['data'] : array();
	
	$markup = "<a href=\"{$link}\" data-hint=\"{$title}\" class=\"{$classes}\"";
	
	foreach( $data_params as $dp_name => $dp_value ) {
		$markup .= " data-{$dp_name}=\"{$dp_value}\"";
	}
	
	$markup .= $attr_id ? " id=\"{$attr_id}\"" : '';
	$markup .= '>';
	$markup .= $icon ? "<img src=\"{$icon}\" />" : '';
	$markup .= '</a>';
	
	
	return $markup;
}