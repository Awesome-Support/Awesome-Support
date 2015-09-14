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

// Show only posts and media related to logged in author
add_action('pre_get_posts', 'query_set_only_author' );
function query_set_only_author( $wp_query ) {
	global $current_user;
	if( is_admin() && !current_user_can('edit_others_posts') ) {
		$wp_query->set( 'author', $current_user->ID );

		add_filter('views_upload', 'fix_media_counts');
	}
}

add_filter( 'views_edit-ticket', 'wpas_fix_tickets_count' );
/**
 * Fix the ticket count in the ticket list screen
 *
 * The ticket count is wrong because it doesn't includes
 * the possible restrictions on user roles.
 *
 * @since 3.2
 *
 * @param $views All available views in the ticket list screen
 *
 * @return array All views with accurate count
 */
function wpas_fix_tickets_count( $views ) {

	global $wp_query;

	$ticket_status = wpas_get_post_status(); // Our declared ticket status
	$status        = 'open';

	// Maybe apply filters
	if ( isset( $_GET['wpas_status'] ) ) {
		switch ( $_GET['wpas_status'] ) {
			case 'closed':
				$status = 'closed';
				break;
			case '':
				$status = 'any';
				break;
		}
	}

	foreach ( $views as $view => $label ) {

		if ( array_key_exists( $view, $ticket_status ) || 'all' === $view ) {

			$count   = 'all' === $view ? wpas_get_ticket_count_by_status( '', $status ) : wpas_get_ticket_count_by_status( $view, $status );
			$regex   = '.*?(\\(.*\\))';
			$replace = '';

			if ( preg_match_all( "/" . $regex . "/is", $label, $matches ) ) {
				$replace = $matches[1][0];
			}

			$label           = trim( strip_tags( str_replace( $replace, '', $label ) ) );
			$class           = isset( $wp_query->query_vars['post_status'] ) && $wp_query->query_vars['post_status'] === $view || isset( $wp_query->query_vars['post_status'] ) && 'all' === $view && $wp_query->query_vars['post_status'] == null ? ' class="current"' : '';
			$link_query_args = 'all' === $view ? array( 'post_type' => 'ticket' ) : array( 'post_type' => 'ticket', 'post_status' => $view );
			$link            = esc_url( add_query_arg( $link_query_args, admin_url( 'edit.php' ) ) );
			$views[ $view ]  = sprintf( '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>', $link, $class, $label, $count );

		}

	}

	return $views;

}