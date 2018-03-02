<?php
/**
 * @package   Awesome Support/Admin/Functions/Agent-Chat
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
 * Register teamviewer integration script
 *
 * @since 4.4.0
 *
 * @return void
 */
add_action( 'admin_enqueue_scripts', 'wpas_enqueue_team_viewer_scripts', 10 );
function wpas_enqueue_team_viewer_scripts(){
	
	if ( true === boolval( wpas_get_option( 'enable_teamviewer_chat', false )  && true == wpas_is_plugin_page() ) ) {
		
		wp_register_script( 'wpas-teamviewer-chat', 'https://integratedchat.teamviewer.com/widget', '', '4.4.0', true );
		wp_enqueue_script( 'wpas-teamviewer-chat' );		
		
	}
	
}