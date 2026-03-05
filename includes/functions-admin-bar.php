<?php
/**
 * @package   Ayuda – Help Desk/Admin Bar
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_bar_menu', 'mumei_ayuda_toolbar_tickets_link', 999, 1 );
/**
 * Add link to agent's tickets.
 *
 * @since  3.0.0
 *
 * @param  object $wp_admin_bar The WordPress toolbar object
 *
 * @return void
 */
function mumei_ayuda_toolbar_tickets_link( $wp_admin_bar ) {

	if ( ! current_user_can( 'edit_ticket' ) ) {
		return;
	}

	$hide          = (bool) mumei_ayuda_get_option( 'hide_closed' );
	$agent_see_all = (bool) mumei_ayuda_get_option( 'agent_see_all' );
	$admin_see_all = (bool) mumei_ayuda_get_option( 'admin_see_all' );
	$args          = array( 'post_type' => 'ticket' );

	// In case the current user can only see his own tickets
	if ( mumei_ayuda_is_asadmin() && false === $admin_see_all || ! mumei_ayuda_is_asadmin() && false === $agent_see_all ) {

		global $current_user;

		$agent         = new MUMEI_AYUDA_Member_Agent( $current_user->ID );
		$tickets_count = $agent->open_tickets();

	} else {
		$tickets_count = count( mumei_ayuda_get_tickets( 'open', $args ) );
	}

	if ( true === $hide ) {
		$args['mumei_ayuda_status'] = 'open';
	}

	$node = array(
		'id'     => 'mumei_ayuda_tickets',
		'parent' => null,
		'group'  => null,
		'title'  => '<span class="ab-icon"></span> ' . $tickets_count,
		'href'   => add_query_arg( $args, admin_url( 'edit.php' ) ),
		'meta'   => array(
			'target' => '_self',
			'title'  => esc_html__( 'Open tickets assigned to you', 'ayuda-help-desk' ),
			'class'  => 'wpas-my-tickets',
		),
	);

	$wp_admin_bar->add_node( $node );
}

add_action( 'wp_head', 'mumei_ayuda_load_admin_bar_style' );
add_action( 'admin_head', 'mumei_ayuda_load_admin_bar_style' );
/**
 * Load the one line style for the admin bar icon
 *
 * @since 3.2.6
 * @return void
 */
function mumei_ayuda_load_admin_bar_style() {

	if ( ! is_user_logged_in() || ! current_user_can( 'edit_ticket' ) ) {
		return;
	}

	echo '<style>#wpadminbar #wp-admin-bar-mumei_ayuda_tickets .ab-icon:before { content: \'\\f468\'; top: 2px; }</style>';

}