<?php
/**
 * Post Type.
 *
 * @package   Admin/Post Type
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

add_action( 'init', 'mumei_ayuda_register_post_type', 10, 0 );
/**
 * Register the ticket post type.
 *
 * @since 1.0.2
 */
function mumei_ayuda_register_post_type() {

	$slug = defined( 'MUMEI_AYUDA_SLUG' ) ? sanitize_title( MUMEI_AYUDA_SLUG ) : 'ticket';

	/* Supported components */
	$supports = array( 'title' );
	
	/* Template components for Gutenberg */
	$gutenburg_new_template = array(
					array( 'core/paragraph', array(
							'placeholder' => _x('Enter the contents for your new ticket here', 'placeholder for main paragraph when adding a new ticket', 'ayuda-help-desk' )
						) ),
				);

	/* If the post is being created we add the editor */
	if( !isset( $_GET['post'] ) ) {
		array_push( $supports, 'editor' );
	}

	/* Post type menu icon */
	$icon = version_compare( get_bloginfo( 'version' ), '3.8', '>=') ? 'dashicons-forms' : MUMEI_AYUDA_ADMIN_ASSETS_URL . 'images/icon-tickets.png';

	/* Post type labels */
	$labels = apply_filters( 'mumei_ayuda_ticket_type_labels', array(
			'name'               => _x( 'Tickets', 'post type general name', 'ayuda-help-desk' ),
			'singular_name'      => _x( 'Ticket', 'post type singular name', 'ayuda-help-desk' ),
			'menu_name'          => _x( 'Tickets', 'admin menu', 'ayuda-help-desk' ),
			'name_admin_bar'     => _x( 'Ticket', 'add new on admin bar', 'ayuda-help-desk' ),
			'add_new'            => _x( 'Add New', 'ticket', 'ayuda-help-desk' ),
			'add_new_item'       => __( 'Add New Ticket', 'ayuda-help-desk' ),
			'new_item'           => __( 'New Ticket', 'ayuda-help-desk' ),
			'edit_item'          => __( 'Edit Ticket', 'ayuda-help-desk' ),
			'view_item'          => __( 'View Ticket', 'ayuda-help-desk' ),
			'all_items'          => __( 'All Tickets', 'ayuda-help-desk' ),
			'search_items'       => __( 'Search Tickets', 'ayuda-help-desk' ),
			'parent_item_colon'  => __( 'Parent Ticket:', 'ayuda-help-desk' ),
			'not_found'          => __( 'No tickets found.', 'ayuda-help-desk' ),
			'not_found_in_trash' => __( 'No tickets found in Trash.', 'ayuda-help-desk' ),
	) );

	/* Post type capabilities */
	$cap = apply_filters( 'mumei_ayuda_ticket_type_cap', array(
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
	$args = apply_filters( 'mumei_ayuda_ticket_type_args', array(
			'labels'              => $labels,
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => apply_filters( 'mumei_ayuda_rewrite_slug', $slug ), 'with_front' => false ),
			'capability_type'     => 'view_ticket',
			'capabilities'        => $cap,
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'menu_icon'           => $icon,
			'supports'            => $supports,
			'template' 			  => $gutenburg_new_template
	) );

	register_post_type( 'ticket', $args );

}

add_action( 'post_updated_messages', 'mumei_ayuda_post_type_updated_messages', 10, 1 );
/**
 * Ticket update messages.
 *
 * @since  3.0.0
 *
 * @param  array $messages Existing post update messages.
 *
 * @return array           Amended post update messages with new CPT update messages.
 */
function mumei_ayuda_post_type_updated_messages( $messages ) {

	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );

	if ( 'ticket' !== $post_type ) {
		return $messages;
	}

	// translators: %1$s is the scheduled ticket date.
	$x_content = __( 'Ticket scheduled for: <strong>%1$s</strong>.', 'ayuda-help-desk' );

	$messages[$post_type] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Ticket updated.', 'ayuda-help-desk' ),
			2  => __( 'Custom field updated.', 'ayuda-help-desk' ),
			3  => __( 'Custom field deleted.', 'ayuda-help-desk' ),
			4  => __( 'Ticket updated.', 'ayuda-help-desk' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Ticket restored to revision from %s', 'ayuda-help-desk' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Ticket published.', 'ayuda-help-desk' ),
			7  => __( 'Ticket saved.', 'ayuda-help-desk' ),
			8  => __( 'Ticket submitted.', 'ayuda-help-desk' ),
			9  => sprintf(
					$x_content,
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'ayuda-help-desk' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Ticket draft updated.', 'ayuda-help-desk' )
	);

	if ( $post_type_object->publicly_queryable ) {
		$permalink = get_permalink( $post->ID );

		$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View ticket', 'ayuda-help-desk' ) );
		$messages[ $post_type ][1] .= $view_link;
		$messages[ $post_type ][6] .= $view_link;
		$messages[ $post_type ][9] .= $view_link;

		$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
		$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview ticket', 'ayuda-help-desk' ) );
		$messages[ $post_type ][8]  .= $preview_link;
		$messages[ $post_type ][10] .= $preview_link;
	}

	return $messages;
}

add_action( 'init', 'mumei_ayuda_register_secondary_post_type', 10, 0 );
/**
 * Register secondary post types.
 *
 * These post types aren't used by the client
 * but are used to store extra information about the tickets.
 *
 * @since  3.0.0
 */
function mumei_ayuda_register_secondary_post_type() {

	$ticket_reply_labels = apply_filters( 'mumei_ayuda_ticket_replies_type_labels', array(
			'name'               => _x( 'Ticket Replies', 'post type general name', 'ayuda-help-desk' ),
			'singular_name'      => _x( 'Ticket Reply', 'post type singular name', 'ayuda-help-desk' ),
			'menu_name'          => _x( 'Ticket Reply', 'admin menu', 'ayuda-help-desk' ),
			'name_admin_bar'     => _x( 'Ticket Reply', 'add new on admin bar', 'ayuda-help-desk' ),
			'add_new'            => _x( 'Add New', 'Ticket Reply', 'ayuda-help-desk' ),
			'add_new_item'       => __( 'Add New Ticket Reply', 'ayuda-help-desk' ),
			'new_item'           => __( 'New Ticket Reply', 'ayuda-help-desk' ),
			'edit_item'          => __( 'Edit Ticket Reply', 'ayuda-help-desk' ),
			'view_item'          => __( 'View Ticket Reply', 'ayuda-help-desk' ),
			'all_items'          => __( 'All Ticket Replies', 'ayuda-help-desk' ),
			'search_items'       => __( 'Search Ticket Reply', 'ayuda-help-desk' ),
			'parent_item_colon'  => __( 'Parent Ticket Replies:', 'ayuda-help-desk' ),
			'not_found'          => __( 'No Ticket Replies found.', 'ayuda-help-desk' ),
			'not_found_in_trash' => __( 'No Ticket Replies found in Trash.', 'ayuda-help-desk' )
	)	);
	
	$ticket_history_labels = apply_filters( 'mumei_ayuda_ticket_history_type_labels', array(
			'name'               => _x( 'Ticket History', 'post type general name', 'ayuda-help-desk' ),
			'singular_name'      => _x( 'Ticket History', 'post type singular name', 'ayuda-help-desk' ),
			'menu_name'          => _x( 'Ticket History', 'admin menu', 'ayuda-help-desk' ),
			'name_admin_bar'     => _x( 'Ticket History', 'add new on admin bar', 'ayuda-help-desk' ),
			'add_new'            => _x( 'Add History', 'Ticket History', 'ayuda-help-desk' ),
			'add_new_item'       => __( 'Add New Ticket History', 'ayuda-help-desk' ),
			'new_item'           => __( 'New Ticket History', 'ayuda-help-desk' ),
			'edit_item'          => __( 'Edit Ticket History', 'ayuda-help-desk' ),
			'view_item'          => __( 'View Ticket History', 'ayuda-help-desk' ),
			'all_items'          => __( 'All Ticket History', 'ayuda-help-desk' ),
			'search_items'       => __( 'Search Ticket History', 'ayuda-help-desk' ),
			'parent_item_colon'  => __( 'Parent Ticket History:', 'ayuda-help-desk' ),
			'not_found'          => __( 'No Ticket History found.', 'ayuda-help-desk' ),
			'not_found_in_trash' => __( 'No Ticket History found in Trash.', 'ayuda-help-desk' )
	)	);	
	
	$ticket_log_labels = apply_filters( 'mumei_ayuda_ticket_log_type_labels', array(
			'name'               => _x( 'Ticket Log', 'post type general name', 'ayuda-help-desk' ),
			'singular_name'      => _x( 'Ticket Log', 'post type singular name', 'ayuda-help-desk' ),
			'menu_name'          => _x( 'Ticket Log', 'admin menu', 'ayuda-help-desk' ),
			'name_admin_bar'     => _x( 'Ticket Log', 'add new on admin bar', 'ayuda-help-desk' ),
			'add_new'            => _x( 'Add Ticket Log', 'Ticket Log', 'ayuda-help-desk' ),
			'add_new_item'       => __( 'Add New Ticket Log', 'ayuda-help-desk' ),
			'new_item'           => __( 'New Ticket Log', 'ayuda-help-desk' ),
			'edit_item'          => __( 'Edit Ticket Log', 'ayuda-help-desk' ),
			'view_item'          => __( 'View Ticket Log', 'ayuda-help-desk' ),
			'all_items'          => __( 'All Ticket Logs', 'ayuda-help-desk' ),
			'search_items'       => __( 'Search Ticket Logs', 'ayuda-help-desk' ),
			'parent_item_colon'  => __( 'Parent Ticket Log:', 'ayuda-help-desk' ),
			'not_found'          => __( 'No Ticket Logs found.', 'ayuda-help-desk' ),
			'not_found_in_trash' => __( 'No Ticket Logs found in Trash.', 'ayuda-help-desk' )
	)	);		
	
	register_post_type( 'ticket_reply', array( 'labels' => $ticket_reply_labels, 'public' => false, 'exclude_from_search' => true, 'supports' => array( 'editor' ) ) );
	register_post_type( 'ticket_history', array( 'labels' => $ticket_history_labels, 'public' => false, 'exclude_from_search' => true ) );
	register_post_type( 'ticket_log', array( 'labels' => $ticket_log_labels, 'public' => false, 'exclude_from_search' => true ) );
}

add_action( 'init', 'mumei_ayuda_register_post_status', 10, 0 );
/**
 * Register custom ticket status.
 *
 * @since  3.0.0
 * @return void
 */
function mumei_ayuda_register_post_status() {

	$status = mumei_ayuda_get_post_status();

	foreach ( $status as $id => $custom_status ) {
		// translators: %s is the custom_status.
		$singular = " $custom_status <span class='count'>(%s)</span>";
		$plural   = " $custom_status <span class='count'>(%s)</span>";
		
		
		$args = array(
				'label'                     => $custom_status,
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => array(
					0          => $singular,
					1          => $plural,
					'singular' => $singular,
					'plural'   => $plural,
					'context'  => null,
					'domain'   => 'ayuda-help-desk',
				),
		);

		register_post_status( $id, $args );
	}

	/**
	 * Hardcode the read and unread status used for replies.
	 */
	register_post_status( 'read',   array( 'label' => _x( 'Read', 'Reply status', 'ayuda-help-desk' ), 'public' => false ) );
	register_post_status( 'unread', array( 'label' => _x( 'Unread', 'Reply status', 'ayuda-help-desk' ), 'public' => false ) );
}

/**
 * Get available ticket status.
 *
 * @since  3.0.0
 * @return array List of filtered statuses
 */
function mumei_ayuda_get_post_status() {

	$status = array(
			'queued'     => _x( 'New', 'Ticket status', 'ayuda-help-desk' ),
			'processing' => _x( 'In Progress', 'Ticket status', 'ayuda-help-desk' ),
			'hold'       => _x( 'On Hold', 'Ticket status', 'ayuda-help-desk' ),
	);

	return apply_filters( 'mumei_ayuda_ticket_statuses', $status );

}

add_action( 'template_redirect', 'mumei_ayuda_redirect_ticket_archive', 10, 0 );
/**
 * Redirect ticket archive page.
 *
 * We don't use the archive page to display the ticket
 * so let's redirect it to the user's tickets list instead.
 *
 * @since  1.0.0
 * @return void
 */
function mumei_ayuda_redirect_ticket_archive() {

	if ( is_post_type_archive( 'ticket' ) ) {

		// Redirect to the tickets list page
		$redirect_to = mumei_ayuda_get_tickets_list_page_url();

		// Fallback to the ticket submission page
		if ( empty( $redirect_to ) ) {
			$redirect_to = mumei_ayuda_get_submission_page_url();
		}

		// Fallback to the site homepage
		if ( empty( $redirect_to ) ) {
			$redirect_to = home_url();
		}

		mumei_ayuda_redirect( 'archive_redirect', $redirect_to );

	}

}
