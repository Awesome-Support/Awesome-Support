<?php
/**
 * @package   Awesome Support/Admin Bar
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_bar_menu', 'wpas_toolbar_tickets_link', 999, 1 );
/**
 * Add link to agent's tickets.
 *
 * @since  3.0.0
 *
 * @param  object $wp_admin_bar The WordPress toolbar object
 *
 * @return void
 */
function wpas_toolbar_tickets_link( $wp_admin_bar ) {

	if ( ! current_user_can( 'edit_ticket' ) ) {
		return;
	}

	$hide          = (bool) wpas_get_option( 'hide_closed' );
	$agent_see_all = (bool) wpas_get_option( 'agent_see_all' );
	$admin_see_all = (bool) wpas_get_option( 'admin_see_all' );
	$args          = array( 'post_type' => 'ticket' );

	// In case the current user can only see his own tickets
	if ( wpas_is_asadmin() && false === $admin_see_all || ! wpas_is_asadmin() && false === $agent_see_all ) {

		global $current_user;

		$agent         = new WPAS_Member_Agent( $current_user->ID );
		$tickets_count = $agent->open_tickets();

	} else {
		$tickets_count = count( wpas_get_tickets( 'open', $args ) );
	}

	if ( true === $hide ) {
		$args['wpas_status'] = 'open';
	}

	$node = array(
		'id'     => 'wpas_tickets',
		'parent' => null,
		'group'  => null,
		'title'  => '<span class="ab-icon"></span> ' . $tickets_count,
		'href'   => add_query_arg( $args, admin_url( 'edit.php' ) ),
		'meta'   => array(
			'target' => '_self',
			'title'  => esc_html__( 'Open tickets assigned to you', 'awesome-support' ),
			'class'  => 'wpas-my-tickets',
		),
	);

	$wp_admin_bar->add_node( $node );
}

add_action( 'wp_head', 'wpas_load_admin_bar_style' );
add_action( 'admin_head', 'wpas_load_admin_bar_style' );
/**
 * Load the one line style for the admin bar icon
 *
 * @since 3.2.6
 * @return void
 */
function wpas_load_admin_bar_style() {

	if ( ! is_user_logged_in() || ! current_user_can( 'edit_ticket' ) ) {
		return;
	}

	echo '<style>#wpadminbar #wp-admin-bar-wpas_tickets .ab-icon:before { content: \'\\f468\'; top: 2px; }</style>';

}