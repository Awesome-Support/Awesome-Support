<?php
/**
 * @package   Awesome Support/Admin/Functions/List Table
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'pre_get_posts', 'wpas_hide_others_tickets', 10, 1 );
/**
 * Hide tickets not assigned to current user.
 *
 * Admins and agents can be set to only see their own tickets.
 * In this case, we modify the main query to only get the tickets
 * the current user is assigned to.
 *
 * @since  3.0.0
 *
 * @param  object $query WordPress main query.
 *
 * @return boolean       True if the main query was modified, false otherwise
 */
function wpas_hide_others_tickets( $query ) {

	/* Make sure this is the main query */
	if ( ! $query->is_main_query() ) {
		return false;
	}

	/* Make sure this is the admin screen */
	if ( ! is_admin() ) {
		return false;
	}

	$post_type = filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING );

	/* Make sure we only alter our post type */
	if ( 'ticket' !== $post_type ) {
		return false;
	}

	global $current_user;	
	
	
	// We need to update the original meta_query and not replace it to avoid filtering issues.
	$meta_query = $query->get( 'meta_query' );

	if ( ! is_array( $meta_query ) ) {
		$meta_query = array_filter( (array) $meta_query );
	}
	
	$agents_meta_query = wpas_ticket_listing_assignee_meta_query_args( $current_user->ID );
	
	if( !empty( $agents_meta_query ) ) {
		$meta_query[] = $agents_meta_query;
		$query->set( 'meta_query', $meta_query );
	}
	
	return true;

}

/**
 * Limit the list of tickets to open.
 *
 * When tickets are filtered by post status it makes no sense
 * to display tickets that are already closed. We hereby limit
 * the list to open tickets.
 *
 * @since  3.1.3
 *
 * @param object $query WordPress main query.
 *
 * @return boolean True if the tickets were filtered, false otherwise
 */
function wpas_limit_open( $query ) {

	/* Make sure this is the main query */
	if ( ! $query->is_main_query() ) {
		return false;
	}

	/* Make sure this is the admin screen */
	if ( ! is_admin() ) {
		return false;
	}

	$post_type   = filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING );
	$post_status = filter_input( INPUT_GET, 'post_status', FILTER_SANITIZE_STRING );

	/* Make sure we only alter our post type */
	if ( 'ticket' !== $post_type ) {
		return false;
	}

	if ( array_key_exists( $post_status, wpas_get_post_status() ) || empty( $post_status ) && true === (bool) wpas_get_option( 'hide_closed', false ) ) {

		// We need to update the original meta_query and not replace it to avoid filtering issues.
		$meta_query = $query->get( 'meta_query' );

		if ( ! is_array( $meta_query ) ) {
			$meta_query = array_filter( (array) $meta_query );
		}

		$meta_query[] = array(
				'key'     => '_wpas_status',
				'value'   => 'open',
				'compare' => '=',
				'type'    => 'CHAR',
		);

		$query->set( 'meta_query', $meta_query );

		return true;

	} else {
		return false;
	}

}

add_filter( 'post_row_actions', 'wpas_ticket_action_row', 10, 2 );
/**
 * Add items in action row.
 *
 * Add a quick option to open or close a ticket
 * directly from the tickets list.
 *
 * @since  3.0.0
 *
 * @param  array  $actions List of existing options.
 * @param  object $post    Current post object.
 *
 * @return array           List of options with ours added
 */
function wpas_ticket_action_row( $actions, $post ) {

	if ( 'ticket' === $post->post_type ) {

		$status = wpas_get_ticket_status( $post->ID );

		if ( 'open' === $status ) {
			$actions['closeticket'] = '<a href="' . wpas_get_close_ticket_url( $post->ID ) . '">' . __( 'Close', 'awesome-support' ) . '</a>';
		} elseif ( 'closed' === $status ) {
			$actions['openticket'] = '<a href="' . wpas_get_open_ticket_url( $post->ID ) . '">' . __( 'Open', 'awesome-support' ) . '</a>';
		}
	}

	return $actions;
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
 * @param array $views All available views in the ticket list screen.
 *
 * @return array All views with accurate count
 */
function wpas_fix_tickets_count( $views ) {

	global $wp_query;

	$ticket_status = wpas_get_post_status(); // Our declared ticket status.
	$status        = 'open';
	$post_status   = filter_input( INPUT_GET, 'post_status', FILTER_SANITIZE_STRING );

	// Maybe apply filters.
	if ( ! empty( $post_status ) ) {
		switch ( $post_status ) {
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

			if ( preg_match_all( '/' . $regex . '/is', $label, $matches ) ) {
				$replace = $matches[1][0];
			}

			$label           = trim( strip_tags( str_replace( $replace, '', $label ) ) );
			$class           = isset( $wp_query->query_vars['post_status'] ) && $wp_query->query_vars['post_status'] === $view || isset( $wp_query->query_vars['post_status'] ) && 'all' === $view && null === $wp_query->query_vars['post_status'] ? ' class="current"' : '';
			$link_query_args = 'all' === $view ? array( 'post_type' => 'ticket' ) : array( 'post_type' => 'ticket', 'post_status' => $view );
			$link            = esc_url( add_query_arg( $link_query_args, admin_url( 'edit.php' ) ) );
			$views[ $view ]  = sprintf( '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>', $link, $class, $label, $count );

		}
	}

	return $views;

}


add_filter( 'bulk_actions-edit-ticket', 'wpas_manage_ticket_bulk_actions', 11, 1 );

/**
 * Remove bulk edit action from ticket listing page
 * 
 * @param array $bulk_actions
 * @return array
 */
function wpas_manage_ticket_bulk_actions( $bulk_actions ) {
	
	if( isset( $bulk_actions['edit'] ) ) {
		unset( $bulk_actions['edit'] );
	}
	
	return $bulk_actions;
}