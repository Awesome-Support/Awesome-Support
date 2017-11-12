<?php
/**
 * @package   Awesome Support/Admin/Functions/Menu
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
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
	
	if ( ! defined( 'WPAS_SAAS' ) || ( defined( 'WPAS_SAAS' ) && false === WPAS_SAAS ) ) {
		add_submenu_page( 'edit.php?post_type=ticket', __( 'Get a Free Addon', 'awesome-support' ), '<span style="color:#f39c12;">' . esc_html__( 'Get a Free Addon!', 'awesome-support' ) . '</span>', 'administrator', 'wpas-optin', 'wpas_display_optin_page' );
		add_submenu_page( 'edit.php?post_type=ticket', __( 'About Awesome Support', 'awesome-support' ), __( 'About', 'awesome-support' ), 'edit_posts', 'wpas-about', 'wpas_display_about_page' );	
	}				

	// Hide the free addon page if the user already claimed it
	if ( true === wpas_is_free_addon_page_dismissed() ) {
		remove_submenu_page( 'edit.php?post_type=ticket', 'wpas-optin' );
	}
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

	$is_agent = current_user_can( 'administrator' )
		&& false === boolval( wpas_get_option( 'admin_see_all' ) )
		|| ! current_user_can( 'administrator' )
			&& current_user_can( 'edit_ticket' )
			&& false === boolval( wpas_get_option( 'agent_see_all' ) );

	$count_cache = get_site_transient( 'wpas_tickets_counts' );

	if( !is_array($count_cache) )
		$count_cache = array();

	$agent_id = $is_agent ? $current_user->ID : 0;

	if( !isset( $count_cache[$agent_id] ) )  {

		if ( $is_agent ) {

			$agent = new WPAS_Member_Agent( $agent_id );
			$count = $agent->open_tickets();

		} else {
			$count = count( wpas_get_tickets( 'open' ) );
		}

		$count_cache[$agent_id] = $count;

		set_site_transient( 'wpas_tickets_counts', $count_cache, 24 * HOUR_IN_SECOND );
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
 * Render the free addon page
 *
 * @since    3.3.3
 */
function wpas_display_optin_page() {
	include_once( WPAS_PATH . 'includes/admin/views/opt-in.php' );
}

/**
 * Render the system status.
 *
 * @since    3.0.0
 */
function wpas_display_status_page() {
	include_once( WPAS_PATH . 'includes/admin/views/status.php' );
}