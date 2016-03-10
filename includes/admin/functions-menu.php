<?php
/**
 * @package   Awesome Support/Admin/Functions/Menu
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2015 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_menu', 'wpas_register_submenu_items' );
/**
 * Register all submenu items.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_register_submenu_items() {
	add_submenu_page( 'edit.php?post_type=ticket', __( 'Debugging Tools', 'awesome-support' ), __( 'Tools', 'awesome-support' ), 'administrator', 'wpas-status', 'wpas_display_status_page' );
	add_submenu_page( 'edit.php?post_type=ticket', __( 'Awesome Support Addons', 'awesome-support' ), '<span style="color:#f39c12;">' . __( 'Addons', 'awesome-support' ) . '</span>', 'edit_posts', 'wpas-addons', 'wpas_display_addons_page' );
	add_submenu_page( 'edit.php?post_type=ticket', __( 'About Awesome Support', 'awesome-support' ), __( 'About', 'awesome-support' ), 'edit_posts', 'wpas-about', 'wpas_display_about_page' );
	remove_submenu_page( 'edit.php?post_type=ticket', 'wpas-about' );
}

add_action( 'admin_menu', 'wpas_tickets_count' );
/**
 * Add ticket count in admin menu item.
 *
 * @return boolean True if the ticket count was added, false otherwise
 * @since  1.0.0
 */
function wpas_tickets_count() {

	if ( false === (bool) wpas_get_option( 'show_count' ) ) {
		return false;
	}

	global $menu, $current_user;

	if ( current_user_can( 'administrator' )
		 && false === boolval( wpas_get_option( 'admin_see_all' ) )
		 || ! current_user_can( 'administrator' )
			&& current_user_can( 'edit_ticket' )
			&& false === boolval( wpas_get_option( 'agent_see_all' ) )
	) {

		$agent = new WPAS_Agent( $current_user->ID );
		$count = $agent->open_tickets();

	} else {
		$count = count( wpas_get_tickets( 'open' ) );
	}

	if ( 0 === $count ) {
		return false;
	}

	foreach ( $menu as $key => $value ) {
		if ( $menu[ $key ][2] == 'edit.php?post_type=ticket' ) {
			$menu[ $key ][0] .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . $count . '</span></span>';
		}
	}

	return true;
}

/**
 * Render the about page for this plugin.
 *
 * @since    3.0.0
 */
function wpas_display_about_page() {
	include_once( WPAS_PATH . 'includes/admin/views/about.php' );
}

/**
 * Render the addons page for this plugin.
 *
 * @since    3.0.0
 */
function wpas_display_addons_page() {
	include_once( WPAS_PATH . 'includes/admin/views/addons.php' );
}

/**
 * Render the system status.
 *
 * @since    3.0.0
 */
function wpas_display_status_page() {
	include_once( WPAS_PATH . 'includes/admin/views/status.php' );
}