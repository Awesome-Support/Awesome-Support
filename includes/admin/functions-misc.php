<?php
/**
 * @package   Awesome Support/Admin/Functions/Misc
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
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
		remove_query_arg( 'message', $url );
		$delete         = wpas_do_url( admin_url( 'post.php' ), 'admin_trash_reply', array( 'post' => $ticket_id, 'action' => 'edit', 'reply_id' => $reply->ID ) );
		wp_nonce_url( add_query_arg( array(
				'post'   => $ticket_id,
				'rid'    => $reply->ID,
				'action' => 'edit_reply'
		), admin_url( 'post.php' ) ), 'delete_reply_' . $reply->ID );

		/* Add delete reply icon */
		$controls['delete_reply'] = wpas_reply_control_item( 'delete_reply' ,array(
			'title' => esc_html_x( 'Delete', 'Link to delete a ticket reply', 'awesome-support' ),
			'link'	=> esc_url( $delete ),
			'icon'  => WPAS_URL . 'assets/admin/images/delete-ticket-reply.png',
			'classes' => 'wpas-delete'
		));
		
		/* Add edit reply icon */
		$controls['edit_reply'] = wpas_reply_control_item( 'edit_reply' ,array(
			'title' => esc_html_x( 'Edit', 'Link to edit a ticket reply', 'awesome-support' ),
			'link'	=> '#',
			'icon' => WPAS_URL . 'assets/admin/images/edit-ticket-reply.png',
			'classes' => 'wpas-edit',
			'data' => array( 
				'origin'=> "#wpas-reply-{$reply->ID}",
				'replyid' => $reply->ID,
				'reply' => "wpas-editwrap-{$reply->ID}",
				'wysiwygid'=> "wpas-editreply-{$reply->ID}"
			)
		));

	}

	if ( get_current_user_id() !== $reply->post_author && 'unread' === $reply->post_status ) {
		
		/* Add mark as read icon */
		$controls['mark_read'] = wpas_reply_control_item( 'mark_read' ,array(
			'title' => esc_html_x( 'Mark as Read', 'Mark a user reply as read', 'awesome-support' ),
			'link'	=> '#',
			'icon' => WPAS_URL . 'assets/admin/images/mark-as-read.png',
			'classes' => 'wpas-mark-read',
			'data' => array( 
				'replyid' => $reply->ID
			)
		));
		
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
 *
 * @param  integer       $post_id The ID of the ticket to check
 * @param  WP_Query|null $replies The object containing the ticket replies. If the object was previously generated we
 *                                pass it directly in order to avoid re-querying
 *
 * @return boolean          True if the ticket is old, false otherwise
 */
function wpas_is_ticket_old( $post_id, $replies = null ) {

	if ( 'closed' === wpas_get_ticket_status( $post_id ) ) {
		return false;
	}

	// Prepare the new object
	if ( is_null( $replies ) || is_object( $replies ) && ! is_a( $replies, 'WP_Query' ) ) {
		$replies = WPAS_Tickets_List::get_instance()->get_replies_query( $post_id );
	}

	/**
	 * We check when was the last reply (if there was a reply).
	 * Then, we compute the ticket age and if it is considered as
	 * old, we display an informational tag.
	 */
	if ( empty( $replies->posts ) ) {

		$post = get_post( $post_id );

		/* We get the post date */
		$date_created = $post->post_date;

	} else {

		$last = $replies->post_count - 1;

		/* We get the post date */
		$date_created = $replies->posts[ $last ]->post_date;

	}

	$old_after           = (int) wpas_get_option( 'old_ticket' );
	$post_date_timestamp = mysql2date( 'U', $date_created );

	if ( $post_date_timestamp + ( $old_after * 86400 ) < strtotime( 'now' ) ) {
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
 *
 * @param  integer       $post_id The ID of the ticket to check
 * @param  WP_Query|null $replies The object containing the ticket replies. If the object was previously generated we
 *                                pass it directly in order to avoid re-querying
 *
 * @return boolean          True if a reply is needed, false otherwise
 */
function wpas_is_reply_needed( $post_id, $replies = null ) {

	if ( 'closed' === wpas_get_ticket_status( $post_id ) ) {
		return false;
	}

	/* Prepare the new object */
	if ( is_null( $replies ) || is_object( $replies ) && ! is_a( $replies, 'WP_Query' ) ) {
		$replies = WPAS_Tickets_List::get_instance()->get_replies_query( $post_id );
	}

	/* No reply yet. */
	if ( empty( $replies->posts ) ) {

		$post = get_post( $post_id );

		/* Make sure the ticket wan not created by an agent on behalf of the client. */
		if ( ! user_can( $post->post_author, 'edit_ticket' ) ) {
			return true;
		}

	} else {

		$last = $replies->post_count - 1;

		// If the last reply was from an agent then return false - the ticket is waiting for the customer to reply.
		if ( user_can( $replies->posts[ $last ]->post_author, 'edit_ticket' ) ) {
			
			return false ;
		
		} else {
		
			// Or the ticket has just been transferred, in which case we want to show the awaiting reply tag			
			return true;
		
		}

		// If the last reply is not from an agent return true since ticket is waiting for a reply from an agent...
		if ( ! user_can( $replies->posts[ $last ]->post_author, 'edit_ticket' ) ) { 
			return true;
		}

	}

	return false;

}

/**
 * Returns the close date of the ticket based on the ticket/post id passed
 *
 * @since  4.0.4
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return date|string  Close date of ticket, an empty string otherwise
 */
function wpas_get_close_date( $post_id ) {
	
	$close_date = get_post_meta( $post_id, '_ticket_closed_on', true) ;
	
	if ( ! empty( $close_date ) ) {
		return $close_date ;
	} else {
		return '' ;
	}
	
}

/**
 * Returns the close date in GMT of the ticket based on the ticket/post id passed
 *
 * @since  4.0.5
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return date|string  Close date of ticket, an empty string otherwise
 */
function wpas_get_close_date_gmt( $post_id ) {
	
	$close_date = get_post_meta( $post_id, '_ticket_closed_on_gmt', true) ;
	
	if ( ! empty( $close_date ) ) {
		return $close_date ;
	} else {
		return '' ;
	}
	
}

/**
 * Returns the open date of the ticket based on the ticket/post id passed
 *
 * @since  4.0.5
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return date|string  open date of ticket, an empty string otherwise
 */
function wpas_get_open_date( $post_id ) {
	
	// Return if not a ticket...
	if ( 'ticket' <> get_post_type( $post_id ) ) {
		return '' ;
	}
		
	$the_ticket = get_post( $post_id ) ;

	$open_date = $the_ticket->post_date ;
	
	if ( ! empty( $open_date ) ) {
		return $open_date ;
	} else {
		return '' ;
	}
	
}

/**
 * Returns the open date in GMT of the ticket based on the ticket/post id passed
 *
 * @since  4.0.5
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return date|string  open date of ticket, an empty string otherwise
 */
function wpas_get_open_date_gmt( $post_id ) {
	
	// Return if not a ticket...
	if ( 'ticket' <> get_post_type( $post_id ) ) {
		return '' ;
	}
		
	$the_ticket = get_post( $post_id ) ;

	$open_date = $the_ticket->post_date_gmt ;
	
	if ( ! empty( $open_date ) ) {
		return $open_date ;
	} else {
		return '' ;
	}
	
}

/**
 * Returns difference between two dates in string format to help with debugging.
 * Formatted string will look like this sample : 0 day(s) 14 hour(s) 33 minute(s)
 * 
 * @since  4.0.5
 *
 * @param  date $firstdate 	First date in the format you get when using post->post_date to get a date from a post
 * @param  date $seconddate Second date in the format you get when using post->post_date to get a date from a post
 *
 * @return string  difference between two dates, an empty string otherwise
 */
function wpas_get_date_diff_string( $firstdate, $seconddate ) {
	
		// Calculate difference object...
		$date1 = new DateTime( $firstdate );
		$date2 = new DateTime( $seconddate );
		$diff_dates = $date2->diff($date1) ;	
		
		$date_string = '' ;
		$date_string .= ' ' . $diff_dates->format('%d') .  __(' day(s)', 'awesome-support') ;
		$date_string .=  ' ' . $diff_dates->format('%h') .  __(' hour(s)', 'awesome-support') ;								
		$date_string .=  ' ' . $diff_dates->format('%i') .  __(' minute(s)', 'awesome-support') ;
		
		return $date_string ;
	
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
	
	// Do not show message if installed in an SAAS environment
	if ( defined( 'WPAS_SAAS' ) && true === WPAS_SAAS ) {
		return ;
	}	

	if ( ! boolval( wpas_get_option( 'remove_admin_ratings_request', false) ) ) {
		return sprintf( __(  'If you like Awesome Support <a %s>please leave us a %s rating</a>. Many thanks from the Awesome Support team in advance :)', 'awesome-support' ), 'href="https://wordpress.org/support/view/plugin-reviews/awesome-support?rate=5#postform" target="_blank"', '&#9733&#9733&#9733&#9733&#9733 ' );
	}

}

/**
 * Check if the free addon page has been dismissed or not
 *
 * @since 3.3.3
 * @return bool
 */
function wpas_is_free_addon_page_dismissed() {
	return (bool) get_option( 'wpas_dismiss_free_addon_page', false );
}

add_action( 'plugins_loaded', 'wpas_free_addon_notice' );
/**
 * Add free addon notice
 *
 * After the plugin has been activated, we display a notice to admins telling them that they can get a free addon for
 * Awesome Support.
 *
 * @since 3.3.3
 * @return void
 */
function wpas_free_addon_notice() {
	
	// Do not show message if installed in an SAAS environment
	if ( defined( 'WPAS_SAAS' ) && true === WPAS_SAAS ) {
		return ;
	}			

	// Only show this message to admins
	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}

	// Don't show the notice if user already claimed the addon
	if ( wpas_is_free_addon_page_dismissed() ) {
		return;
	}

	// Only show the notice on the plugin pages
	if ( ! wpas_is_plugin_page() ) {
		return;
	}

	// No need to show the notice on the free addon page itself
	if ( isset( $_GET['page'] ) && 'wpas-optin' === $_GET['page'] ) {
		return;
	}

	WPAS()->admin_notices->add_notice( 'updated', 'wpas_get_free_addon', wp_kses( sprintf( __( 'Hey! Did you know you can get a <strong>free add-on for unlimited sites</strong> (a $61.00 USD value) for Awesome Support? <a href="%1$s">Click here to read more</a>.', 'awesome-support' ), add_query_arg( array(
		'post_type' => 'ticket',
		'page'      => 'wpas-optin',
	), admin_url( 'edit.php' ) ) ), array( 'strong' => array(), 'a' => array( 'href' => array() ) ) ) );

}

add_action( 'plugins_loaded', 'wpas_request_first_5star_rating' );
/**
 * Request 5 star rating after 25 closed tickets.
 *
 * After 25 closed tickets we ask the admin for a 5 star rating
 *
 * @since 4.0.0
 * @return void
 */
function wpas_request_first_5star_rating() {

	// Do not show message if installed in an SAAS environment
	if ( defined( 'WPAS_SAAS' ) && true === WPAS_SAAS ) {
		return ;
	}				

	// Only show this message to admins
	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}

	// Only show the notice on the plugin pages
	if ( ! wpas_is_plugin_page() ) {
		return;
	}
	
	// If notice has been dismissed, return since everything else after this is expensive operations!
	If ( wpas_is_notice_dismissed('wpas_request_first_5star_rating') ) {
		return ;
	}

	// How many tickets have been closed?
	$closed_tickets = wpas_get_tickets( 'closed', array( 'posts_per_page' => 25 ) );
	
	// Show notice if number of closed tickets greater than 25.
	If ( count ($closed_tickets) >= 25 ) {
	
		WPAS()->admin_notices->add_notice( 'updated', 'wpas_request_first_5star_rating', wp_kses( sprintf( __( 'Wow! It looks like you have closed a lot of tickets which is pretty awesome! We guess you must really like Awesome Support, huh? Could you please do us a favor and leave a 5 star rating on WordPress? It will only take a minute and helps to motivate our developers and volunteers. <a href="%1$s">Yes, you deserve it!</a>.', 'awesome-support' ), 'https://wordpress.org/support/plugin/awesome-support/reviews/' ) , 
		array( 'strong' => array(), 'a' => array( 'href' => array() ) ) ) );

	}
}

/**
 * Generate admin tabs html
 * 
 * @param string $type
 * @param array $tabs
 * 
 * @return string
 */
function wpas_admin_tabs( $type, $tabs = array() ) {
	
	// Unique tabs widget id
	$id = "wpas_admin_tabs_{$type}";
	
	
	$tabs = apply_filters( $id, $tabs );
	
	// Stop processing if no tab exist
	if( empty( $tabs ) ) {
		return;
	}
	
	
	$tab_order = 1;
	$tab_content_items_ar = array();
	$tab_content_ar = array();
	
	foreach( $tabs as $tab_id => $tab_name ) {
		$_id = "{$id}_{$tab_id}";
		
		$tab_content = apply_filters( "{$_id}_content", "" );
		
		if( $tab_content ) {
			$tab_content_items_ar[] = sprintf( '<li data-tab-order="%s" rel="%s" class="wpas_tab_name">%s</li>' , $tab_order, $_id, $tab_name );
			$tab_content_ar[] = '<div class="wpas_admin_tab_content" id="' . $_id . '">' . $tab_content . '</div>';
			$tab_order++;
		}
	}
	
	
	// Stop processing if no tab's data exist
	if( empty( $tab_content_items_ar ) ) {
		return;
	}
	
	ob_start();
	
	?>


	<div class="wpas_admin_tabs" id="<?php echo $id; ?>">
		<div class="wpas_admin_tabs_names_wrapper">
			<ul>
			    <?php echo implode( '', $tab_content_items_ar ); ?>
				<li class="moreTab">
					<ul class="dropdown-menu tabs_collapsed"></ul>
				</li>
				<li class="clear clearfix"></li>
	
			</ul>
		</div>
		<?php echo implode( '', $tab_content_ar ); ?>
	</div>
	<?php
	
	
	return ob_get_clean();
	
}


add_action( 'wpas_admin_after_wysiwyg', 'reply_tabs', 8, 0 );

/**
 * Add tabs under reply wysiwyg editor
 */
function reply_tabs() {
	
	$tabs_content = wpas_admin_tabs( 'after_reply_wysiwyg' );
	echo $tabs_content;
}