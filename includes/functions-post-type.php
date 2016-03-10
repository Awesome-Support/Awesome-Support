<?php
/**
 * Post Type.
 *
 * @package   Admin/Post Type
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

add_action( 'init', 'wpas_register_post_type', 10, 0 );
/**
 * Register the ticket post type.
 *
 * @since 1.0.2
 */
function wpas_register_post_type() {

	$slug = defined( 'WPAS_SLUG' ) ? sanitize_title( WPAS_SLUG ) : 'ticket';

	/* Supported components */
	$supports = array( 'title' );

	/* If the post is being created we add the editor */
	if( !isset( $_GET['post'] ) ) {
		array_push( $supports, 'editor' );
	}

	/* Post type menu icon */
	$icon = version_compare( get_bloginfo( 'version' ), '3.8', '>=') ? 'dashicons-sos' : WPAS_ADMIN_ASSETS_URL . 'images/icon-tickets.png';

	/* Post type labels */
	$labels = apply_filters( 'wpas_ticket_type_labels', array(
			'name'               => _x( 'Tickets', 'post type general name', 'awesome-support' ),
			'singular_name'      => _x( 'Ticket', 'post type singular name', 'awesome-support' ),
			'menu_name'          => _x( 'Tickets', 'admin menu', 'awesome-support' ),
			'name_admin_bar'     => _x( 'Ticket', 'add new on admin bar', 'awesome-support' ),
			'add_new'            => _x( 'Add New', 'book', 'awesome-support' ),
			'add_new_item'       => __( 'Add New Ticket', 'awesome-support' ),
			'new_item'           => __( 'New Ticket', 'awesome-support' ),
			'edit_item'          => __( 'Edit Ticket', 'awesome-support' ),
			'view_item'          => __( 'View Ticket', 'awesome-support' ),
			'all_items'          => __( 'All Tickets', 'awesome-support' ),
			'search_items'       => __( 'Search Tickets', 'awesome-support' ),
			'parent_item_colon'  => __( 'Parent Ticket:', 'awesome-support' ),
			'not_found'          => __( 'No tickets found.', 'awesome-support' ),
			'not_found_in_trash' => __( 'No tickets found in Trash.', 'awesome-support' ),
	) );

	/* Post type capabilities */
	$cap = apply_filters( 'wpas_ticket_type_cap', array(
			'read'					 => 'view_ticket',
			'read_post'				 => 'view_ticket',
			'read_private_posts' 	 => 'view_private_ticket',
			'edit_post'				 => 'edit_ticket',
			'edit_posts'			 => 'edit_ticket',
			'edit_others_posts' 	 => 'edit_other_ticket',
			'edit_private_posts' 	 => 'edit_private_ticket',
			'edit_published_posts' 	 => 'edit_ticket',
			'publish_posts'			 => 'create_ticket',
			'delete_post'			 => 'delete_ticket',
			'delete_posts'			 => 'delete_ticket',
			'delete_private_posts' 	 => 'delete_private_ticket',
			'delete_published_posts' => 'delete_ticket',
			'delete_others_posts' 	 => 'delete_other_ticket'
	) );

	/* Post type arguments */
	$args = apply_filters( 'wpas_ticket_type_args', array(
			'labels'              => $labels,
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => apply_filters( 'wpas_rewrite_slug', $slug ), 'with_front' => false ),
			'capability_type'     => 'view_ticket',
			'capabilities'        => $cap,
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'menu_icon'           => $icon,
			'supports'            => $supports
	) );

	register_post_type( 'ticket', $args );

}

add_action( 'post_updated_messages', 'wpas_post_type_updated_messages', 10, 1 );
/**
 * Ticket update messages.
 *
 * @since  3.0.0
 *
 * @param  array $messages Existing post update messages.
 *
 * @return array           Amended post update messages with new CPT update messages.
 */
function wpas_post_type_updated_messages( $messages ) {

	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );

	if ( 'ticket' !== $post_type ) {
		return $messages;
	}

	$messages[$post_type] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Ticket updated.', 'awesome-support' ),
			2  => __( 'Custom field updated.', 'awesome-support' ),
			3  => __( 'Custom field deleted.', 'awesome-support' ),
			4  => __( 'Ticket updated.', 'awesome-support' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Ticket restored to revision from %s', 'awesome-support' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Ticket published.', 'awesome-support' ),
			7  => __( 'Ticket saved.', 'awesome-support' ),
			8  => __( 'Ticket submitted.', 'awesome-support' ),
			9  => sprintf(
					__( 'Ticket scheduled for: <strong>%1$s</strong>.', 'awesome-support' ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'awesome-support' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Ticket draft updated.', 'awesome-support' )
	);

	if ( $post_type_object->publicly_queryable ) {
		$permalink = get_permalink( $post->ID );

		$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View ticket', 'awesome-support' ) );
		$messages[ $post_type ][1] .= $view_link;
		$messages[ $post_type ][6] .= $view_link;
		$messages[ $post_type ][9] .= $view_link;

		$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
		$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview ticket', 'awesome-support' ) );
		$messages[ $post_type ][8]  .= $preview_link;
		$messages[ $post_type ][10] .= $preview_link;
	}

	return $messages;
}

add_action( 'init', 'wpas_register_secondary_post_type', 10, 0 );
/**
 * Register secondary post types.
 *
 * These post types aren't used by the client
 * but are used to store extra information about the tickets.
 *
 * @since  3.0.0
 */
function wpas_register_secondary_post_type() {
	register_post_type( 'ticket_reply', array( 'public' => false, 'exclude_from_search' => true, 'supports' => array( 'editor' ) ) );
	register_post_type( 'ticket_history', array( 'public' => false, 'exclude_from_search' => true ) );
	register_post_type( 'ticket_log', array( 'public' => false, 'exclude_from_search' => true ) );
}

add_action( 'init', 'wpas_register_post_status', 10, 0 );
/**
 * Register custom ticket status.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_register_post_status() {

	$status = wpas_get_post_status();

	foreach ( $status as $id => $custom_status ) {

		$args = array(
				'label'                     => $custom_status,
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( "$custom_status <span class='count'>(%s)</span>", "$custom_status <span class='count'>(%s)</span>", 'awesome-support' ),
		);

		register_post_status( $id, $args );
	}

	/**
	 * Hardcode the read and unread status used for replies.
	 */
	register_post_status( 'read',   array( 'label' => _x( 'Read', 'Reply status', 'awesome-support' ), 'public' => false ) );
	register_post_status( 'unread', array( 'label' => _x( 'Unread', 'Reply status', 'awesome-support' ), 'public' => false ) );
}

/**
 * Get available ticket status.
 *
 * @since  3.0.0
 * @return array List of filtered statuses
 */
function wpas_get_post_status() {

	$status = array(
			'queued'     => _x( 'New', 'Ticket status', 'awesome-support' ),
			'processing' => _x( 'In Progress', 'Ticket status', 'awesome-support' ),
			'hold'       => _x( 'On Hold', 'Ticket status', 'awesome-support' ),
	);

	return apply_filters( 'wpas_ticket_statuses', $status );

}

add_action( 'template_redirect', 'wpas_redirect_ticket_archive', 10, 0 );
/**
 * Redirect ticket archive page.
 *
 * We don't use the archive page to display the ticket
 * so let's redirect it to the user's tickets list instead.
 *
 * @since  1.0.0
 * @return void
 */
function wpas_redirect_ticket_archive() {

	if ( is_post_type_archive( 'ticket' ) ) {
		wpas_redirect( 'archive_redirect', wpas_get_tickets_list_page_url() );
	}

}