<?php
/**
 * @package   Awesome Support/Admin/Functions/Actions
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


add_action( 'admin_head-post.php', 'admin_head_post_editing' );
/**
 * Check to see if user can view a ticket.  If not, return them to the ticket list
 *
 * @since 3.3
 *
 * @return void
 */
function admin_head_post_editing() {

	global $post_type, $post_ID, $current_user;

	if( 'ticket' === $post_type ) {
		$can = wpas_can_view_ticket( $post_ID );

		if( $can ) {
			//can view ticket - do nothing and continue.
		}
		else {
			//Not allowed to view ticket - write to log file and bail out.
			wpas_write_log('security', 'A logged in user attempted to access a ticket without the necessary permissions. ' . 'Ticket id: ' . (string) $post_ID . ', Logged In user ID: ' . (string) $current_user->ID ) ;
			wp_redirect( add_query_arg( array( 'post_type' => 'ticket' ), admin_url( 'edit.php' ) ) );
			exit;
		}
	}

}

add_action( 'wpas_do_admin_close_ticket', 'wpas_admin_action_close_ticket' );
/**
 * Close a ticket
 *
 * @since 3.3
 *
 * @param obj $data
 *
 * @return void
 */
function wpas_admin_action_close_ticket( $data ) {

	global $pagenow;

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $data['post'] ) ) {
		return;
	}

	$post_id = (int) $data['post'];

	$closed = wpas_close_ticket( $post_id );

	
	if( $closed ) {
	
		// Read-only redirect
		if ( 'post.php' === $pagenow ) {
			$redirect_to = add_query_arg( array(
				'action'       => 'edit',
				'post'         => $post_id,
				'wpas-message' => 'closed'
			), admin_url( 'post.php' ) );
		} else {
			$redirect_to = add_query_arg( array(
				'post_type'    => 'ticket',
				'post'         => $post_id,
				'wpas-message' => 'closed'
			), admin_url( 'edit.php' ) );
		}
	} else {
		$redirect_to = WPAS()->session->get( 'redirect' );
	}

	wp_redirect( wp_sanitize_redirect( $redirect_to ) );
	exit;

}

add_action( 'wpas_do_admin_open_ticket', 'wpas_admin_action_open_ticket' );
/**
 * (Re)open a ticket
 *
 * @since 3.3
 *
 * @param obj $data
 *
 * @return void
 */
function wpas_admin_action_open_ticket( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $data['post'] ) ) {
		return;
	}

	$post_id = (int) $data['post'];

	wpas_reopen_ticket( $post_id );

	// Read-only redirect
	$redirect_to = add_query_arg( array(
		'action'       => 'edit',
		'post'         => $post_id,
		'wpas-message' => 'opened'
	), admin_url( 'post.php' ) );

	wp_redirect( wp_sanitize_redirect( $redirect_to ) );
	exit;

}

add_action( 'wpas_do_admin_trash_reply', 'wpas_admin_action_trash_reply' );
/**
 * Trash a reply
 *
 * @since 3.3
 *
 * @param obj $data
 *
 * @return void
 */
function wpas_admin_action_trash_reply( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $data['reply_id'] ) ) {
		return;
	}

	$reply_id = (int) $data['reply_id'];
	$ticket_id = ''; // will populate this below...
	$trashed_post = null ; // will populate this below
	
	/* Get the contents of the post being trashed */
	$trashed_contents = '';	
	if ( $reply_id > 0 ) {

		$trashed_post = get_post( $reply_id );

		if ( ! is_null( $reply_id ) ) {
			$trashed_contents = $trashed_post->post_content ;
			$ticket_id = $trashed_post->post_parent;
		} else {
			return false ;
		}
		
	}
	
	/* 
	 * Remove the attachments from the post before trashing the post 
	 * Note that even if we restore the post from the trash, 
	 * the attachments will not be restored - they are permanently deleted here. 
	 * There is no UI provision in Awesome Support to restore a reply from the trash 
	 * anyway and no anticipated future need where we would need to.  So no harm 
	 * deleting the attachments permanently here.
	 */
	wpas_delete_post_attachments( $reply_id ) ;
	
	/* Now trash/delete the post */
	if ( boolval( wpas_get_option( 'permanently_trash_replies', false ) ) ) {
		wp_delete_post( $reply_id, true ); // Permanentaly delete		
	} else {
		wp_trash_post( $reply_id );  // Move to trash - having it in trash allows the UI to post a "reply was deleted" alert in the ticket.
	}
	
	/* Add a flag to the TICKET that shows one of its replies was deleted */
	update_post_meta( $ticket_id, 'wpas_reply_was_deleted', '1' ) ;
	
	
	/* Fire the after-delete action hook */
	do_action( 'wpas_admin_reply_trashed', $reply_id, $trashed_post, $ticket_id  );

	// Read-only redirect
	$redirect_to = add_query_arg( array(
		'action'       => 'edit',
		'post'         => $data['post'],
	), admin_url( 'post.php' ) );

	wp_redirect( wp_sanitize_redirect( "$redirect_to#wpas-post-$reply_id" ) );
	exit;

}

add_action( 'wpas_admin_reply_trashed', 'wpas_log_reply_trashed', 10,3 );
/**
 * Log the original contents of a deleted reply.
 *
 * Action hook: wpas_admin_reply_trashed
 *
 * @since 5.2.0
 * @param int	$reply_id 		- the id of the reply being edited.
 * @param array $original_reply - the original post content before tit was deleted.
 * @param int	$ticket_id		- ticket id that the reply belongs to
 *
 * @return void
 */
function wpas_log_reply_trashed( $reply_id, $original_reply, $ticket_id ) {
	
	/* Do we log a summary or detail that includes the original content? */
	if ( 'low' === wpas_get_option( 'log_content_edit_level', 'low' ) ) {
		$reply_contents_to_log = __( 'Original data not available because detailed logging is not turned on or allowed', 'awesome-support' ) ;
	} else {
		$reply_contents_to_log = $original_reply->post_content ;
	}
	
	// Log it at the reply level
	wpas_log_edits( $reply_id, sprintf( __( 'Reply #%s was deleted from ticket #%s.', 'awesome-support' ), (string) $reply_id, (string) $original_reply->post_parent  ), $reply_contents_to_log );
	
	// Log it at the ticket level as well because if the reply gets permanently deleted, the only way to show it in the UI is at the ticket level
	wpas_log_edits( $original_reply->post_parent, sprintf( __( 'Reply #%s was deleted from ticket #%s.', 'awesome-support' ), (string) $reply_id, (string) $original_reply->post_parent  ), $reply_contents_to_log );
	
}