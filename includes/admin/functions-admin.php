<?php
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

/**
 * Checks for templates overrides.
 *
 * Check if any of the plugin templates is being
 * overwritten by the child theme or the theme.
 *
 * @since  3.0.0
 * @param  string $dir Directory to check
 * @return array       Array of overridden templates
 */
function wpas_check_templates_override( $dir ) {

	$templates = array(
		'details.php',
		'list.php',
		'registration.php',
		'submission.php'
	);

	$overrides = array();

	if ( is_dir( $dir ) ) {

		$files = scandir( $dir );

		if ( empty( $files ) ) {
			return array();
		}

		foreach ( $files as $key => $file ) {
			if ( !in_array( $file, $templates ) ) {
				continue;
			}

			array_push( $overrides, $file );
		}

	}

	return $overrides;

}

class WPAS_Replies_Filter extends WP_Query {

	public function __construct() {
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 1 );
	}

	public function posts_join( $sql ) {

		if ( !is_admin() ) {
			return $sql;
		}

		global $wpdb;
		// $sql .= "AS ticket_id LEFT JOIN $wpdb->posts AS reply ON ticket_id.post_parent = reply.ID AND post_status = 'unread'";
		// $sql .= "LEFT JOIN ( SELECT $wpdb->posts.* WHERE  as reply_id, ticket_id FROM $wpdb->posts WHERE post_type = 'ticket_reply' AND post_status = 'unread' ) ON $wpdb->posts.ID = ticket_id";
		// return $sql;

		 $sql .= "LEFT JOIN (
                SELECT MAX(ID) as child_ID, post_parent FROM $wpdb->posts
                WHERE post_type = 'ticket_reply' AND post_status = 'unread'
                GROUP BY post_parent
                ) sl ON $wpdb->posts.ID = sl.post_parent
                LEFT JOIN $wpdb->posts wp1 on sl.child_ID = wp1.ID";

		return $sql;

	}

}

//new WPAS_Replies_Filter();