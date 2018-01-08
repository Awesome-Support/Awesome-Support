<?php
/**
 * @package   Awesome Support/Admin/Functions/ticket-detail/toolbars
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2018 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/** 
 * Status action link.
 * This is used by the details.php metabox file as well as functions in this file.
 * 
 * @params none
 *
 * @see admin/class-awesome-support-admin.php
 */
function get_ticket_details_action_link( $post ) {

	/* Current status */
	$ticket_status = get_post_meta( get_the_ID(), '_wpas_status', true );

	$base_url = add_query_arg( array( 'action' => 'edit', 'post' => $post->ID ), admin_url( 'post.php' ) );
	
	$action = ( in_array( $ticket_status, array( 'closed', '' ) ) ) ? wpas_do_url( $base_url, 'admin_open_ticket' ) : wpas_do_url( $base_url, 'admin_close_ticket' );
	
	return $action ;
}

add_action( 'wpas_ticket_detail_toolbar01_before', 'wpas_add_close_ticket_item_to_ticket_detail_toolbar', 10, 1 );
/**
 * Add a CLOSE TICKET button to the ticket detail toolbar
 * 
 * Action Hook: wpas_ticket_detail_toolbar01_before
 *
 * @params post $post the current post/ticket being worked on
 */
function wpas_add_close_ticket_item_to_ticket_detail_toolbar( $post ) {
	
	/* Current status of ticket */
	$ticket_status = get_post_meta( get_the_ID(), '_wpas_status', true );
	
	/* Status action link close/reopen etc. */
	$action = get_ticket_details_action_link( $post );
	
	if ( 'closed' === $ticket_status ) {
		echo wpas_add_ticket_detail_toolbar_item( 'a', 'wpas-close-ticket-top', __( 'Re-open Ticket', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/re-open-ticket.png", $action );
	} elseif( '' === $ticket_status ) {
		// do nothing...
	} else {
		echo wpas_add_ticket_detail_toolbar_item( 'a', 'wpas-close-ticket-top', __( 'Close Ticket', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/close-ticket.png", $action );
	}	
}

/**
 * Adds an item to the tool-bar in the ticket detail in wp-admin or
 * returns a markup suitable for adding an item to the toolbar screen.
 * 
 * Note that if you choose to have the menu item echoed directly to the screen this
 * function should be called using a do_action hook tied into the menu (such as wpas_ticket_detail_toolbar01_before)
 *
 * @since 4.4.0
 *
 * @param 	string $html_element_type   img or a (anchor)
 *          string $item_css_id 		The CSS ID of the toolbar item 
 *                                      Any "-" in the name will be converted to underscores to create a class name; the "-" will remain for the CSS ID;
 *                                      For example: my-css-id will result in a markup with the css id = 'my-css-id' and the class name = 'my_css_id'.
 *		  	string $tool_tip_text 		The tool tip that will be displayed for the new toolbar item
 *			string $image_url			The URL for the toolbar item image.
 *			string $target_url			(optional) The URL target if creating an 'a' (anchor) element
 *			string $attributes			(optional) Any other attribute that needs to be added to the markup
 * 			bool   $return_markup		If set to true, it will return the button markup instead of echoing it to the screen.
 *
 * @return 	void (basically nothing is returned)
 */
function wpas_add_ticket_detail_toolbar_item( $html_element_type, $item_css_id, $tool_tip_text, $image_url, $target_url='', $attributes = '', $return_markup = true ) {
	
	$name = str_replace( '-', '_', $item_css_id );  // convert the passed ids into text to be used for classnames.  For convention we're using classnames with underscores (_) and ids with dashes (-)

	$echoout = '';
	$echoout = $echoout . '<span data-hint=' . '"' . $tool_tip_text . '"' . 'class="wpas-replies-middle-toolbar-item hint-bottom hint-anim">';
	$echoout = $echoout . ' ' . '<' . $html_element_type . ' ' ;  		// opening tag such as <a> or <img>
	$echoout = $echoout . ' ' . 'name = ' . '"' . $name . '"' ; 		// name attribute
	$echoout = $echoout . ' ' . 'id = ' . '"' . $item_css_id . '"' ;	// css ID
	$echoout = $echoout . ' ' . 'class="link-primary wpas-link-reply wpas-middle-toolbar-links ' . $name . '"' ; // css class names
	$echoout = $echoout . ' ' . 'value = ' . '"' . $name . '"' ;		// value attribute
	
	
	if ( ! empty( $attributes ) ) {
		// other attributes as passed in.
		$echoout = $echoout . ' ' . $attributes; 
	}
	
	if ( 'img' === $html_element_type ) {
		// img elements need the src attribute
		$echoout = $echoout . ' ' . 'src = ' . '"' . $image_url . '"' ;
	}
	
	if ( 'a' === $html_element_type && ! empty( $target_url ) ) {
		// add the href element
		$echoout = $echoout . ' ' . 'href = ' . '"' . $target_url . '"' ;
	}

	$echoout = $echoout. '>'; // closing bracket for the tag
	
	if ( 'a' === $html_element_type ) {
		// add the img element if we're using an "a" tag
		$echoout = $echoout . ' ' . '<img src = ' . $image_url . '>';
	}
	
	$echoout = $echoout. ' ' . '</' . $html_element_type . '>'; // closing tag such as </a> or </img>
	
	$echoout = $echoout. ' ' . '</span>' ; // closing tag of encompassing span
	
	if ( ! $return_markup ) {
		echo $echoout ;
	} else {
		return $echoout;
	}
}


