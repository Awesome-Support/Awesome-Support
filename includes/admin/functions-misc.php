<?php
/**
 * @package   Awesome Support/Admin/Functions/Misc
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2015 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'plugin_action_links_' . WPAS_PLUGIN_BASENAME, 'wpas_settings_page_link' );
/**
 * Add a link to the settings page.
 *
 * @since  3.1.5
 *
 * @param  array $links Plugin links
 *
 * @return array        Links with the settings
 */
function wpas_settings_page_link( $links ) {

	$link    = wpas_get_settings_page_url();
	$links[] = "<a href='$link'>" . __( 'Settings', 'awesome-support' ) . "</a>";

	return $links;

}

add_filter( 'postbox_classes_ticket_wpas-mb-details', 'wpas_add_metabox_details_classes' );
/**
 * Add new class to the details metabox.
 *
 * @param array $classes Current metabox classes
 *
 * @return array The updated list of classes
 */
function wpas_add_metabox_details_classes( $classes ) {
	array_push( $classes, 'submitdiv' );

	return $classes;
}

add_action( 'admin_notices', 'wpas_admin_notices' );
/**
 * Display custom admin notices.
 *
 * Custom admin notices are usually triggered by custom actions.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_admin_notices() {

	if ( isset( $_GET['wpas-message'] ) ) {

		switch ( $_GET['wpas-message'] ) {

			case 'opened':
				?>
				<div class="updated">
					<p><?php printf( __( 'The ticket #%s has been (re)opened.', 'awesome-support' ), intval( $_GET['post'] ) ); ?></p>
				</div>
				<?php
				break;

			case 'closed':
				?>
				<div class="updated">
					<p><?php printf( __( 'The ticket #%s has been closed.', 'awesome-support' ), intval( $_GET['post'] ) ); ?></p>
				</div>
				<?php
				break;

		}

	}
}

add_filter( 'wpas_ticket_reply_controls', 'wpas_ticket_reply_controls', 10, 3 );
/**
 * Add ticket reply controls
 *
 * @since 3.2.6
 *
 * @param array   $controls  List of existing controls
 * @param int     $ticket_id ID of the ticket current reply belongs to
 * @param WP_Post $reply     Reply post object
 *
 * @return array
 */
function wpas_ticket_reply_controls( $controls, $ticket_id, $reply ) {

	if ( 0 !== $ticket_id && get_current_user_id() == $reply->post_author ) {

		$_GET['del_id'] = $reply->ID;
		$url            = add_query_arg( $_GET, admin_url( 'post.php' ) );
		$url            = remove_query_arg( 'message', $url );
		$delete         = wpas_do_url( admin_url( 'post.php' ), 'admin_trash_reply', array( 'post' => $ticket_id, 'action' => 'edit', 'reply_id' => $reply->ID ) );
		$edit           = wp_nonce_url( add_query_arg( array(
				'post'   => $ticket_id,
				'rid'    => $reply->ID,
				'action' => 'edit_reply'
		), admin_url( 'post.php' ) ), 'delete_reply_' . $reply->ID );

		$controls['delete_reply'] = sprintf( '<a class="%1$s" href="%2$s" title="%3$s">%3$s</a>', 'wpas-delete', esc_url( $delete ), esc_html_x( 'Delete', 'Link to delete a ticket reply', 'awesome-support' ) );
		$controls['edit_reply']   = sprintf( '<a class="%1$s" href="%2$s" data-origin="%3$s" data-replyid="%4$d" data-reply="%5$s" data-wysiwygid="%6$s" title="%7$s">%7$s</a>', 'wpas-edit', '#', "#wpas-reply-$reply->ID", $reply->ID, "wpas-editwrap-$reply->ID", "wpas-editreply-$reply->ID", esc_html_x( 'Edit', 'Link ot edit a ticket reply', 'awesome-support' ) );

	}

	if ( get_current_user_id() !== $reply->post_author && 'unread' === $reply->post_status ) {
		$controls['mark_read'] = sprintf( '<a class="%1$s" href="%2$s" data-replyid="%3$d" title="%4$s">%4$s</a>', 'wpas-mark-read', '#', $reply->ID, esc_html_x( 'Mark as Read', 'Mark a user reply as read', 'awesome-support' ) );
	}

	return $controls;

}

/**
 * Check if the ticket is old.
 *
 * A simple check based on the value of the "Ticket old" option.
 * If the last reply (or the ticket itself if no reply) is older
 * than the post date + the allowed delay, then it is considered old.
 *
 * @since  3.0.0
 * @param  integer $post_id The ID of the ticket to check
 * @param  object  $latest  The object containing the ticket replies. If the object was previously generated we pass it directly in order to avoid re-querying
 * @return boolean          True if the ticket is old, false otherwise
 */
function wpas_is_ticket_old( $post_id, $latest = null ) {

	if ( 'closed' === wpas_get_ticket_status( $post_id ) ) {
		return false;
	}

	/* Prepare the new object */
	if ( is_null( $latest ) ) {
		$latest = new WP_Query(  array(
						'posts_per_page'         =>	1,
						'orderby'                =>	'post_date',
						'order'                  =>	'DESC',
						'post_type'              =>	'ticket_reply',
						'post_parent'            =>	$post_id,
						'post_status'            =>	array( 'unread', 'read' ),
						'no_found_rows'          => true,
						'cache_results'          => false,
						'update_post_term_cache' => false,
						'update_post_meta_cache' => false,
				)
		);
	}

	/**
	 * We check when was the last reply (if there was a reply).
	 * Then, we compute the ticket age and if it is considered as
	 * old, we display an informational tag.
	 */
	if ( empty( $latest->posts ) ) {

		$post = get_post( $post_id );

		/* We get the post date */
		$date_created = $post->post_date;

	} else {

		/* We get the post date */
		$date_created = $latest->post->post_date;

	}

	$old_after = wpas_get_option( 'old_ticket' );

	if ( strtotime( "$date_created +$old_after days" ) < strtotime( 'now' ) ) {
		return true;
	}

	return false;

}

/**
 * Check if a reply is needed.
 *
 * Takes a ticket ID and checks if a reply is needed. The check is based
 * on who replied last. If a client was the last to reply, or if the ticket
 * was just transferred from one agent to another, then it is considered
 * as "awaiting reply".
 *
 * @since  3.0.0
 * @param  integer $post_id The ID of the ticket to check
 * @param  object  $latest  The object containing the ticket replies. If the object was previously generated we pass it directly in order to avoid re-querying
 * @return boolean          True if a reply is needed, false otherwise
 */
function wpas_is_reply_needed( $post_id, $latest = null ) {

	if ( 'closed' === wpas_get_ticket_status( $post_id ) ) {
		return false;
	}

	/* Prepare the new object */
	if ( is_null( $latest ) ) {
		$latest = new WP_Query(  array(
						'posts_per_page'         =>	1,
						'orderby'                =>	'post_date',
						'order'                  =>	'DESC',
						'post_type'              =>	'ticket_reply',
						'post_parent'            =>	$post_id,
						'post_status'            =>	array( 'unread', 'read' ),
						'no_found_rows'          => true,
						'cache_results'          => false,
						'update_post_term_cache' => false,
						'update_post_meta_cache' => false,
				)
		);
	}

	/* No reply yet. */
	if ( empty( $latest->posts ) ) {

		$post = get_post( $post_id );

		/* Make sure the ticket wan not created by an agent on behalf of the client. */
		if( !user_can( $post->post_author, 'edit_ticket' ) ) {
			return true;
		}

	} else {

		$last = $latest->post_count-1;

		/* Check if the last user who replied is an agent. */
		if( !user_can( $latest->posts[$last]->post_author, 'edit_ticket' ) && 'unread' === $latest->posts[$last]->post_status ) {
			return true;
		}

	}

	return false;

}

add_filter( 'admin_footer_text', 'wpas_admin_footer_text', 999, 1 );
/**
 * Add a custom admin footer text
 *
 * @since 3.2.8
 *
 * @param string $text Footer text
 *
 * @return string
 */
function wpas_admin_footer_text( $text ) {

	if ( ! is_admin() || ! wpas_is_plugin_page() ) {
		return $text;
	}

	return sprintf( __(  'If you like Awesome Support <a %s>please leave us a %s rating</a>. Many thanks from ThemeAvenue in advance :)', 'awesome-support' ), 'href="https://wordpress.org/support/view/plugin-reviews/awesome-support?rate=5#postform" target="_blank"', '&#9733&#9733&#9733&#9733&#9733' );

}