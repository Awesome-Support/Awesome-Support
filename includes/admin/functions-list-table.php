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

	$post_type =  isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';

	/* Make sure we only alter our post type */
	if ( 'ticket' !== $post_type ) {
		return false;
	}

	global $current_user;

	/* Don't filter auto-draft, draft or trashed tickets - they don't need assignee meta filtering */
	$post_status = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '';
	if ( in_array( $post_status, array( 'auto-draft', 'draft', 'trash' ), true ) ) {
		return false;
	}	
	
	
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

	$post_type   = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
	$post_status = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '';

	/* Make sure we only alter our post type */
	if ( 'ticket' !== $post_type ) {
		return false;
	}

	/* Don't filter auto-draft, draft or trashed tickets - they don't need _wpas_status meta filtering */
	if ( in_array( $post_status, array( 'auto-draft', 'draft', 'trash' ), true ) ) {
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

		/* For auto-draft and draft tickets, only allow Edit and Trash */
		if ( in_array( get_post_status( $post->ID ), array( 'auto-draft', 'draft' ), true ) ) {
			return array_intersect_key( $actions, array_flip( array( 'edit', 'trash' ) ) );
		}

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
	$post_status   = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '';

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

	// Add Closed view to the list
	if ( ! isset( $views['closed'] ) ) {
		$views['closed'] = __( 'Closed', 'awesome-support' );
	}

	foreach ( $views as $view => $label ) {

		if ( array_key_exists( $view, $ticket_status ) || 'all' === $view || 'closed' === $view ) {

			if ( 'closed' === $view ) {
				$count = wpas_get_ticket_count_by_status( '', 'closed' );
			} else {
				$count = 'all' === $view ? wpas_get_ticket_count_by_status( '', $status ) : wpas_get_ticket_count_by_status( $view, $status );
			}

			$regex   = '.*?(\\(.*\\))';
			$replace = '';

			if ( preg_match_all( '/' . $regex . '/is', $label, $matches ) ) {
				$replace = $matches[1][0];
			}

			$label           = trim( wp_strip_all_tags( str_replace( $replace, '', $label ) ) );
			
			if ( 'closed' === $view ) {
				$class = isset( $_GET['status'] ) && 'closed' === $_GET['status'] ? ' class="current"' : '';
				$link_query_args = array( 'post_type' => 'ticket', 'status' => 'closed' );
			} else {
				$is_current = ( isset( $wp_query->query_vars['post_status'] ) && $wp_query->query_vars['post_status'] === $view ) || ( 'all' === $view && null === $wp_query->query_vars['post_status'] && ! isset( $_GET['status'] ) );
				$class = $is_current ? ' class="current"' : '';
				$link_query_args = 'all' === $view ? array( 'post_type' => 'ticket' ) : array( 'post_type' => 'ticket', 'post_status' => $view );
			}

			$link            = esc_url( add_query_arg( $link_query_args, admin_url( 'edit.php' ) ) );
			$views[ $view ]  = sprintf( '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>', $link, $class, $label, $count );

		}
	}

	/* Add Auto-draft view */
	global $wpdb;
	$auto_draft_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ticket' AND post_status = 'auto-draft'" );
	if ( $auto_draft_count > 0 ) {
		$ad_class = ( isset( $_GET['post_status'] ) && 'auto-draft' === $_GET['post_status'] ) ? ' class="current"' : '';
		$ad_link  = esc_url( add_query_arg( array( 'post_type' => 'ticket', 'post_status' => 'auto-draft' ), admin_url( 'edit.php' ) ) );
		$views['auto-draft'] = sprintf( '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>', $ad_link, $ad_class, __( 'Auto-draft', 'awesome-support' ), $auto_draft_count );
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

	$bulk_actions['wpas_bulk_close']  = __( 'Close', 'awesome-support' );
	$bulk_actions['wpas_bulk_reopen'] = __( 'Reopen', 'awesome-support' );

	/* Add Delete Permanently option when viewing auto-drafts */
	$current_status = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '';
	if ( 'auto-draft' === $current_status && current_user_can( 'delete_tickets' ) ) {
		$bulk_actions['wpas_bulk_delete'] = __( 'Delete Permanently', 'awesome-support' );
	}

	return $bulk_actions;
}


add_filter( 'post_row_actions', 'wpas_add_print_quick_action', 10, 2 );
/**
 * Add print quick action to tickets list table
 * 
 * @since 5.1.1
 * 
 * @param array $actions
 * @param object $post
 * 
 * @return array
 */
function wpas_add_print_quick_action( $actions, $post ) {

	if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'ticket' && ! in_array( get_post_status( $post->ID ), array( 'auto-draft', 'draft', 'trash' ), true ) ) {
		$actions['wpas_print'] = sprintf( '<a href="#" class="wpas-admin-quick-action-print" data-id="%s">%s</a>', $post->ID, __( 'Print', 'awesome-support' ) );
	}
	
	return $actions;
	
}


add_filter( 'bulk_actions-edit-ticket', 'wpas_add_print_bulk_action' );
/**
 * Add print tickets bulk action to tickets list table
 * 
 * @since 5.1.1
 * 
 * @param array $actions
 * 
 * @return array
 */
function wpas_add_print_bulk_action( $actions ) {

	if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'ticket' ) {
		$actions['wpas_print_tickets'] = __( 'Print Tickets', 'awesome-support' );
	}

	return $actions;
}

add_filter( 'handle_bulk_actions-edit-ticket', 'wpas_handle_ticket_bulk_actions', 10, 3 );
/**
 * Handle custom bulk actions for tickets list table.
 */
function wpas_handle_ticket_bulk_actions( $redirect_to, $action, $post_ids ) {
	if ( ! in_array( $action, array( 'wpas_bulk_close', 'wpas_bulk_reopen', 'wpas_bulk_delete' ), true ) ) {
		return $redirect_to;
	}

	$count = 0;

	foreach ( $post_ids as $post_id ) {
		if ( 'wpas_bulk_close' === $action ) {
			if ( 'closed' !== wpas_get_ticket_status( $post_id ) ) {
				wpas_close_ticket( $post_id, 0, true );
				$count++;
			}
		} elseif ( 'wpas_bulk_reopen' === $action ) {
			if ( 'closed' === wpas_get_ticket_status( $post_id ) ) {
				wpas_reopen_ticket( $post_id );
				$count++;
			}
		} elseif ( 'wpas_bulk_delete' === $action ) {
			if ( current_user_can( 'delete_tickets' ) && 'auto-draft' === get_post_status( $post_id ) ) {
				wp_delete_post( $post_id, true ); // true = force delete, skip trash
				$count++;
			}
		}
	}

	$redirect_to = add_query_arg( array(
		'wpas_bulk_action' => $action,
		'wpas_bulk_count'  => $count,
	), $redirect_to );

	return $redirect_to;
}

add_action( 'admin_notices', 'wpas_ticket_bulk_actions_admin_notice' );
/**
 * Display notices for custom bulk actions.
 */
function wpas_ticket_bulk_actions_admin_notice() {
	if ( ! empty( $_GET['wpas_bulk_action'] ) && isset( $_GET['wpas_bulk_count'] ) ) {
		$action = sanitize_key( $_GET['wpas_bulk_action'] );
		$count  = intval( $_GET['wpas_bulk_count'] );

		if ( 'wpas_bulk_close' === $action ) {
			$message = sprintf( _n( '%s ticket has been closed.', '%s tickets have been closed.', $count, 'awesome-support' ), $count );
		} elseif ( 'wpas_bulk_reopen' === $action ) {
			$message = sprintf( _n( '%s ticket has been reopened.', '%s tickets have been reopened.', $count, 'awesome-support' ), $count );
		} elseif ( 'wpas_bulk_delete' === $action ) {
			$message = sprintf( _n( '%s auto-draft ticket has been permanently deleted.', '%s auto-draft tickets have been permanently deleted.', $count, 'awesome-support' ), $count );
		}

		if ( isset( $message ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
	}
}