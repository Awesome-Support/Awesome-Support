<?php
/**
 * Show the Awesome Support Version Number
 *
 * This metabox is used to display the awesome support
 * version number.  It will be used later to show
 * additional debugging information in real time.
 *
 * @since 4.4.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

echo esc_html__( 'Awesome Support Version: ', 'awesome-support' ) . esc_attr( WPAS_VERSION );

// Display Ticket Source only if ticket was created from email piping
if ( isset( $post->ID ) ) {
	$source = get_post_meta( $post->ID, '_wpas_source', true );

	if ( 'email' === $source ) {
		$mailbox_name = get_post_meta( $post->ID, '_wpas_mailboxname', true );

		echo '<br/>';
		echo '<strong>' . esc_html__( 'Ticket Source: ', 'awesome-support' ) . '</strong>';

		if ( ! empty( $mailbox_name ) ) {
			echo '<span style="color:#0073aa;">&#9993; ' . esc_html( $mailbox_name ) . '</span>';
		} else {
			echo '<span style="color:#0073aa;">&#9993; ' . esc_html__( 'default mailbox', 'awesome-support' ) . '</span>';
		}
	}
}
