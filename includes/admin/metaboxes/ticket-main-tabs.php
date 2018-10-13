<?php

/**
 * Main tabs area on ticket edit page
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


add_filter( 'wpas_admin_tabs_ticket_main', 'wpas_ticket_main_tabs' ); // Register tabs in main tabs area

/**
 * Register tabs 
 * 
 * @param array $tabs
 * 
 * @return array
 */
function wpas_ticket_main_tabs( $tabs ) {
	
	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );
	
	$tabs['ticket']	= __( 'Ticket' , 'awesome-support' );
	
	if ( wpas_can_view_custom_field_tab() && WPAS()->custom_fields->have_custom_fields() ) {
		$tabs['custom_fields'] = __( 'Custom Fields' , 'awesome-support' );
	}
	
	if (  wpas_can_view_ai_tab() ) {
		$tabs['ai_parties'] = __( 'Additional Interested Parties', 'awesome-support' );
	}
	
	if ( isset( $options['show_basic_time_tracking_fields'] ) && true === boolval( $options['show_basic_time_tracking_fields'] ) ) {
		$tabs['time_tracking'] = __( 'Time Tracking', 'awesome-support' );
	}
	
	return $tabs;
}


add_filter( 'wpas_admin_tabs_ticket_main', 'wpas_ticket_main_tabs2', 16 ); //Register more tabs in main tabs area

/**
 * Register tabs
 * 
 * @param array $tabs
 * 
 * @return array
 */
function wpas_ticket_main_tabs2( $tabs ) {
	
	$tabs['statistics']	= __( 'Statistics' , 'awesome-support' );
	
	return $tabs;
}

add_filter( 'wpas_admin_tabs_ticket_main_ticket_content', 'wpas_ticket_main_tab_content' );

/**
 * Return content for ticket tab
 * 
 * @global object $post
 * 
 * @param string $content
 * 
 * @return string
 */
function wpas_ticket_main_tab_content( $content ) {
	global $post;
	
	ob_start();
	
	echo '<div class="wpas-post-body-content"></div><div class="clear clearfix"></div>';
	
	
	if( isset( $_GET['post'] ) ) {
		
		include WPAS_PATH . "includes/admin/metaboxes/message.php";
	}
	
	$content = ob_get_clean();
	return $content;
}


add_filter( 'wpas_admin_tabs_ticket_main_custom_fields_content', 'wpas_custom_fields_main_tab_content' );

/**
 * Return content for custom fields tab
 * 
 * @param string $content
 * 
 * @return string
 */
function wpas_custom_fields_main_tab_content( $content ) {
	ob_start();
	
	include WPAS_PATH . "includes/admin/metaboxes/custom-fields.php";
	
	include WPAS_PATH . "includes/admin/metaboxes/custom-fields-backend.php";
	
	$content = ob_get_clean();
	return $content;
}

add_filter( 'wpas_admin_tabs_ticket_main_ai_parties_content', 'wpas_ai_parties_main_tab_content' );

/**
 * Return content for additional interested parties
 * 
 * @param string $content
 * 
 * @return string
 */
function wpas_ai_parties_main_tab_content( $content ) {
	ob_start();
	
	include WPAS_PATH . "includes/admin/metaboxes/ticket-additional-parties.php";
	
	$content = ob_get_clean();
	return $content;
}

add_filter( 'wpas_admin_tabs_ticket_main_statistics_content', 'wpas_statistics_main_tab_content' );

/**
 * Return content for statistics tab
 * 
 * @param string $content
 * 
 * @return string
 */
function wpas_statistics_main_tab_content( $content ) {
	ob_start();
	include WPAS_PATH . "includes/admin/metaboxes/ticket-statistics.php";
	
	$content = ob_get_clean();
	return $content;
}


add_filter( 'wpas_admin_tabs_ticket_main_time_tracking_content', 'wpas_time_tracking_main_tab_content' );

/**
 * Return content for time tracking tab
 * 
 * @param string $content
 * 
 * @return string
 */
function wpas_time_tracking_main_tab_content( $content ) {
	ob_start();
	include WPAS_PATH . "includes/admin/metaboxes/time-tracking-statistics.php";
	
	$content = ob_get_clean();
	return $content;
}

/**
 * Inject the border color to the top of the ticket in wp-admin based on the priority of the ticket.
 * 
 * We are not actually coloring the ticket border but putting a tiny div above the ticket 
 * with a zero margin.
 *
 * @param none
 * 
 * @return void
 */
function wpas_color_ticket_header_by_priority() {
	
	if ( true === boolval( wpas_get_option( 'support_priority_color_code_ticket_header', false ) ) && true === boolval( wpas_get_option( 'support_priority', false ) )  ) {
	
		global $post_id;

		$terms = get_the_terms( $post_id, 'ticket_priority' );

		if ( $terms ) {
			$term = array_shift( $terms );
			$color = get_term_meta( $term->term_id, 'color', true );
			echo "<div style=\"margin:0 1px; border-top : 2px solid {$color}\"></div>";
		}	
	}

}

/**
 * Inject the border color to the bottom of the ticket in wp-admin based on the ticket type.
 *
 * We are not actually coloring the ticket border but putting a tiny div below the ticket 
 * with a zero margin.
 * 
 * @param none
 * 
 * @return void
 */
function wpas_color_ticket_header_by_ticket_type() {
	
	if ( true === boolval( wpas_get_option( 'support_ticket_type_color_code_ticket', false ) ) && true === boolval( wpas_get_option( 'support_ticket_type', false ) )  ) {
	
		global $post_id;

		$terms = get_the_terms( $post_id, 'ticket_type' );

		if ( $terms ) {
			$term = array_shift( $terms );
			$color = get_term_meta( $term->term_id, 'color', true );
			echo "<div style=\"margin:0 1px; border-top : 2px solid {$color}\"></div>";
		}	
	}

}


/**
 * Inject the color coding for priority (top of ticket is color-coded.)
 */
wpas_color_ticket_header_by_priority();


/**
 * Print main tabs in ticket edit page
 */
echo wpas_admin_tabs( 'ticket_main' );

/**
 * Inject the color coding for ticket_type (bottom of ticket is color coded).
 */
wpas_color_ticket_header_by_ticket_type();