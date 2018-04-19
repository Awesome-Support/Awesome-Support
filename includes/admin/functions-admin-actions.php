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
 * @param $data
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
		}
	}

}

add_action( 'wpas_do_admin_close_ticket', 'wpas_admin_action_close_ticket' );
/**
 * Close a ticket
 *
 * @since 3.3
 *
 * @param $data
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

	wpas_close_ticket( $post_id );

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

	wp_redirect( wp_sanitize_redirect( $redirect_to ) );
	exit;

}

add_action( 'wpas_do_admin_open_ticket', 'wpas_admin_action_open_ticket' );
/**
 * (Re)open a ticket
 *
 * @since 3.3
 *
 * @param $data
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
 * @param $data
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

	wp_trash_post( $reply_id, false );
	do_action( 'wpas_admin_reply_trashed', $reply_id );

	// Read-only redirect
	$redirect_to = add_query_arg( array(
		'action'       => 'edit',
		'post'         => $data['post'],
	), admin_url( 'post.php' ) );

	wp_redirect( wp_sanitize_redirect( "$redirect_to#wpas-post-$reply_id" ) );
	exit;

}