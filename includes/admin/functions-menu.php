<?php
/**
 * @package   Ayuda – Help Desk/Admin/Functions/Menu
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_menu', 'mumei_ayuda_register_submenu_items' );
/**
 * Register all submenu items.
 *
 * @since  3.0.0
 * @return void
 */
function mumei_ayuda_register_submenu_items() {

	add_submenu_page( 'edit.php?post_type=ticket', __( 'Debugging Tools', 'ayuda-help-desk' ), __( 'Tools', 'ayuda-help-desk' ), 'administrator', 'wpas-status', 'mumei_ayuda_display_status_page' );
	
	add_submenu_page( 'edit.php?post_type=ticket', __( 'Ayuda – Help Desk Addons', 'ayuda-help-desk' ), '<span style="color:#f39c12;">' . __( 'Addons', 'ayuda-help-desk' ) . '</span>', 'edit_posts', 'wpas-addons', 'mumei_ayuda_display_addons_page' );
	
	if ( ! defined( 'MUMEI_AYUDA_SAAS' ) || ( defined( 'MUMEI_AYUDA_SAAS' ) && false === MUMEI_AYUDA_SAAS ) ) {
		
		add_submenu_page( 'edit.php?post_type=ticket', __( 'Get a Free Addon', 'ayuda-help-desk' ), '<span style="color:#f39c12;">' . esc_html__( 'Get a Free Addon!', 'ayuda-help-desk' ) . '</span>', 'administrator', 'wpas-optin', 'mumei_ayuda_display_optin_page' );
		
		add_submenu_page( 'edit.php?post_type=ticket', __( 'Help & Support', 'ayuda-help-desk' ), '<span style="color:#4CBBA7;">' . esc_html__( 'Help & Support', 'ayuda-help-desk' ) . '</span>', 'administrator', 'wpas-help-and-support', 'mumei_ayuda_display_help_and_support_page' );		
		
		// Premium-only Get Help button (visible when at least one addon is active)
		if ( ! empty( WPAS()->addons ) ) {
			add_submenu_page( 'edit.php?post_type=ticket', __( 'Get Help', 'ayuda-help-desk' ), '<span style="display:inline-block; background:#4CBBA7; font-weight:600; border-radius:3px; padding:2px 8px; color:#fff;">' . esc_html__( 'Get Help', 'ayuda-help-desk' ) . '</span>', 'edit_posts', 'wpas-get-help', 'mumei_ayuda_display_get_help_page' );
		}

		/**
		 * Provides a hook for Addons to add their menu item in the right location, i.e. above the "About" page.
		 *
		 * @since 4.3.3
		 *
		 * @param string Passes the Ayuda – Help Desk parent slug to the addon.
		 */
		do_action( 'mumei_ayuda_addon_submenu_page',  'edit.php?post_type=ticket' );

		add_submenu_page( 'edit.php?post_type=ticket', __( 'About Ayuda – Help Desk', 'ayuda-help-desk' ), __( 'About', 'ayuda-help-desk' ), 'edit_posts', 'wpas-about', 'mumei_ayuda_display_about_page' );	

	}				

	// Hide the free addon page if the user already claimed it
	if ( true === mumei_ayuda_is_free_addon_page_dismissed() ) {
		remove_submenu_page( 'edit.php?post_type=ticket', 'wpas-optin' );
	}
}

add_action( 'admin_menu', 'mumei_ayuda_tickets_count' );
/**
 * Add ticket count in admin menu item.
 *
 * @return boolean True if the ticket count was added, false otherwise
 * @since  1.0.0
 */
function mumei_ayuda_tickets_count() {

	if ( false === (bool) mumei_ayuda_get_option( 'show_count' ) ) {
		return false;
	}

	global $menu, $current_user;

	$count_cache = get_site_transient( 'mumei_ayuda_tickets_counts' );
	
	if( !is_array($count_cache) )
	{
		$count_cache = array();
	}
	if ( mumei_ayuda_is_asadmin() && false === boolval( mumei_ayuda_get_option( 'admin_see_all' ) )
		 || ! mumei_ayuda_is_asadmin() && mumei_ayuda_is_agent() && false === boolval( mumei_ayuda_get_option( 'agent_see_all' ) )
	) {		
		// Display tickets was assign to current user
		if( is_array( $count_cache ) && isset( $count_cache[ $current_user->ID ] ) )
		{
			$count = $count_cache[ $current_user->ID ];			
		}
		else
		{
			$agent = new MUMEI_AYUDA_Member_Agent( $current_user->ID );
			$count = $agent->open_tickets();
			$count_cache[$current_user->ID] = $count;
			set_site_transient( 'mumei_ayuda_tickets_counts', $count_cache, DAY_IN_SECONDS  );			
		}		

	} else {	
		
		// Display all Opened tickets
		if( is_array( $count_cache ) && isset( $count_cache['all'] ) )
		{
			$count = $count_cache['all'];			
		}
		else
		{
			$count = mumei_ayuda_get_tickets( 'open', [], 'any', true, true );
			$count_cache['all'] = $count;
			set_site_transient( 'mumei_ayuda_tickets_counts', $count_cache, DAY_IN_SECONDS  );			
		}		
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
function mumei_ayuda_display_about_page() {
	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/about.php' );
}

/**
 * Render the addons page for this plugin.
 *
 * @since    3.0.0
 */
function mumei_ayuda_display_addons_page() {
	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/addons.php' );
}

/**
 * Render the free addon page
 *
 * @since    3.3.3
 */
function mumei_ayuda_display_optin_page() {
	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/opt-in.php' );
}

/**
 * Render the help & support options page
 *
 * @since    5.2.0
 */
function mumei_ayuda_display_help_and_support_page() {
	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/wpas-help-and-support.php' );
}

/**
 * Render the system status.
 *
 * @since    3.0.0
 */
function mumei_ayuda_display_status_page() {
	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/status.php' );
}

/**
 * Redirect to the Get Help page.
 *
 * @since    3.0.0
 */
function mumei_ayuda_display_get_help_page() {
	$link = 'https://getawesomesupport.com/submit-ticket/';
    if ( ! headers_sent() ) {
        wp_redirect( $link );
        exit;
    } else {
		include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/wpas-help-and-support.php' );
        echo '<script type="text/javascript">';
        echo 'window.location.href="'.$link.'";';
        echo '</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url='.$link.'" /></noscript>';
        exit;
    }
}