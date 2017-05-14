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

add_action( 'wpas_do_admin_products_option', 'wpas_admin_action_set_products_option' );
/**
 * Trash a reply
 *
 * @since 3.3
 *
 * @param $data
 *
 * @return void
 */
function wpas_admin_action_set_products_option( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $data['products'] ) ) {
		return;
	}

	$products    = $data['products'];
	$redirect_to = remove_query_arg( array(
			'wpas-do',
			'wpas-do-nonce',
			'products'
	), wpas_get_current_admin_url() );

	// Delete the option that triggers the products setting notice
	delete_option( 'wpas_support_products' );

	// If the user needs multiple products we need to update the plugin options
	if ( 'multiple' === $products ) {

		$options                     = maybe_unserialize( get_option( 'wpas_options' ) );
		$options['support_products'] = '1';

		update_option( 'wpas_options', serialize( $options ) );

		// We redirect to the products taxonomy screen
		$redirect_to = add_query_arg( array(
				'taxonomy'  => 'product',
				'post_type' => 'ticket'
		), admin_url( 'edit-tags.php' ) );

	}

	wp_redirect( wp_sanitize_redirect( $redirect_to ) );
	exit;

}