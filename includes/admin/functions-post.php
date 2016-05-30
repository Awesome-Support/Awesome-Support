<?php
/**
 * @package   Awesome Support/Admin/Functions/Post
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2015 ThemeAvenue
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

	/* Now we can save the custom fields */
	WPAS()->custom_fields->save_custom_fields( $post_id, $_POST );

	/* Log the action */
	if ( ! empty( $log ) ) {
		wpas_log( $post_id, $log );
	}

	/* If this was a ticket update, we need to know where to go now... */
	if ( '' !== $original_status ) {

		/* Go back to the tickets list */
		if ( isset( $_POST['wpas_back_to_list'] ) && true === boolval( $_POST['wpas_back_to_list'] ) || isset( $_POST['where_after'] ) && 'back_to_list' === $_POST['where_after'] ) {
			$_SESSION['wpas_redirect'] = add_query_arg( array( 'post_type' => 'ticket' ), admin_url( 'edit.php' ) );
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