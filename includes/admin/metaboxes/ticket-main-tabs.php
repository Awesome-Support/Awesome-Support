<?php

/**
 * Main tabs area on ticket edit page
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


add_filter( 'mumei_ayuda_admin_tabs_ticket_main', 'mumei_ayuda_ticket_main_tabs' ); // Register tabs in main tabs area

/**
 * Register tabs
 *
 * @param array $tabs
 *
 * @return array
 */
function mumei_ayuda_ticket_main_tabs( $tabs ) {

	$options = maybe_unserialize( get_option( 'mumei_ayuda_options', array() ) );

	$tabs['ticket']	= __( 'Ticket' , 'ayuda-help-desk' );

	if ( mumei_ayuda_can_view_custom_field_tab() && WPAS()->custom_fields->have_custom_fields() ) {
		$tabs['custom_fields'] = __( 'Custom Fields' , 'ayuda-help-desk' );
	}

	if (  mumei_ayuda_can_view_ai_tab() ) {
		$tabs['ai_parties'] = __( 'Additional Interested Parties', 'ayuda-help-desk' );
	}

	if ( isset( $options['show_basic_time_tracking_fields'] ) && true === boolval( $options['show_basic_time_tracking_fields'] ) ) {
		$tabs['time_tracking'] = __( 'Time Tracking', 'ayuda-help-desk' );
	}

	return $tabs;
}


add_filter( 'mumei_ayuda_admin_tabs_ticket_main', 'mumei_ayuda_ticket_main_tabs2', 16 ); //Register more tabs in main tabs area

/**
 * Register tabs
 *
 * @param array $tabs
 *
 * @return array
 */
function mumei_ayuda_ticket_main_tabs2( $tabs ) {

	$tabs['statistics']	= __( 'Statistics' , 'ayuda-help-desk' );

	return $tabs;
}

add_filter( 'mumei_ayuda_admin_tabs_ticket_main_ticket_content', 'mumei_ayuda_ticket_main_tab_content' );

/**
 * Return content for ticket tab
 *
 * @global object $post
 *
 * @param string $content
 *
 * @return string
 */
function mumei_ayuda_ticket_main_tab_content( $content ) {
	global $post;

	ob_start();

	echo '<div class="wpas-post-body-content"></div><div class="clear clearfix"></div>';


	if( isset( $_GET['post'] ) ) {

		include MUMEI_AYUDA_PATH . "includes/admin/metaboxes/message.php";
	}

	$content = ob_get_clean();
	return $content;
}


add_filter( 'mumei_ayuda_admin_tabs_ticket_main_custom_fields_content', 'mumei_ayuda_custom_fields_main_tab_content' );

/**
 * Return content for custom fields tab
 *
 * @param string $content
 *
 * @return string
 */
function mumei_ayuda_custom_fields_main_tab_content( $content ) {
	ob_start();

	include MUMEI_AYUDA_PATH . "includes/admin/metaboxes/custom-fields.php";

	include MUMEI_AYUDA_PATH . "includes/admin/metaboxes/custom-fields-backend.php";

	$content = ob_get_clean();
	return $content;
}

add_filter( 'mumei_ayuda_admin_tabs_ticket_main_ai_parties_content', 'mumei_ayuda_ai_parties_main_tab_content' );

/**
 * Return content for additional interested parties
 *
 * @param string $content
 *
 * @return string
 */
function mumei_ayuda_ai_parties_main_tab_content( $content ) {
	ob_start();

	include MUMEI_AYUDA_PATH . "includes/admin/metaboxes/ticket-additional-parties.php";

	$content = ob_get_clean();
	return $content;
}

add_filter( 'mumei_ayuda_admin_tabs_ticket_main_statistics_content', 'mumei_ayuda_statistics_main_tab_content' );

/**
 * Return content for statistics tab
 *
 * @param string $content
 *
 * @return string
 */
function mumei_ayuda_statistics_main_tab_content( $content ) {
	ob_start();
	include MUMEI_AYUDA_PATH . "includes/admin/metaboxes/ticket-statistics.php";

	$content = ob_get_clean();
	return $content;
}


add_filter( 'mumei_ayuda_admin_tabs_ticket_main_time_tracking_content', 'mumei_ayuda_time_tracking_main_tab_content' );

/**
 * Return content for time tracking tab
 *
 * @param string $content
 *
 * @return string
 */
function mumei_ayuda_time_tracking_main_tab_content( $content ) {
	ob_start();
	include MUMEI_AYUDA_PATH . "includes/admin/metaboxes/time-tracking-statistics.php";

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
function mumei_ayuda_color_ticket_header_by_priority() {

	if ( true === boolval( mumei_ayuda_get_option( 'support_priority_color_code_ticket_header', false ) ) && true === boolval( mumei_ayuda_get_option( 'support_priority', false ) )  ) {

		global $post_id;

		$terms = get_the_terms( $post_id, 'ticket_priority' );

		if ( $terms ) {
			$term = array_shift( $terms );
			$color = get_term_meta( $term->term_id, 'color', true );
			echo '<div style="margin:0 1px; border-top : 2px solid ' . esc_attr( $color ) . '"></div>';
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
function mumei_ayuda_color_ticket_header_by_ticket_type() {

	if ( true === boolval( mumei_ayuda_get_option( 'support_ticket_type_color_code_ticket', false ) ) && true === boolval( mumei_ayuda_get_option( 'support_ticket_type', false ) )  ) {

		global $post_id;

		$terms = get_the_terms( $post_id, 'ticket_type' );

		if ( $terms ) {
			$term = array_shift( $terms );
			$color = get_term_meta( $term->term_id, 'color', true );
			echo '<div style="margin:0 1px; border-top : 2px solid ' . esc_attr( $color ) . '"></div>';
		}
	}

}


/**
 * Inject the color coding for priority (top of ticket is color-coded.)
 */
mumei_ayuda_color_ticket_header_by_priority();


/**
 * Print main tabs in ticket edit page
 */
echo wp_kses(mumei_ayuda_admin_tabs( 'ticket_main' ), get_allowed_html_wp_notifications());

/**
 * Inject the color coding for ticket_type (bottom of ticket is color coded).
 */
mumei_ayuda_color_ticket_header_by_ticket_type();