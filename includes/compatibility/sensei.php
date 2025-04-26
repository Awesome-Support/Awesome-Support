<?php
/**
 * @package   Awesome Support/Compatibility/Sensei
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2016 Awesome Support
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_enqueue_scripts', 'wpas_remove_sensei_select2_assets', 999 );
/**
 * Fix compatibility issue with Sensei's select2
 *
 * Sensei uses select2, just like Awesome Support. However, instead of loading select2's assets only where it is used
 * by Sensei, they load the assets everywhere, which messes us with our own instances of select2 if the versions of
 * select2 used by Awesome Support and Sensei don't match.
 *
 * This function will de-register Sensei's select2 on Awesome Support's pages.
 *
 * @since 3.3.2
 * @return void
 */
function wpas_remove_sensei_select2_assets() {

	// Only make changes on our pages. Don't want to mess up even more with other stuff
	if ( ! wpas_is_plugin_page() ) {
		return;
	}

	// Make sure that ACF Pro is installed and active
	if ( ! class_exists( 'Sensei_Main' ) ) {
		return;
	}

	wp_deregister_script( 'sensei-core-select2' );
	wp_deregister_style( 'sensei-core-select2' );

}


add_action( 'init', 'wpas_remove_sensei_actions', 999 );
/**
 * Fix compatibility issue with Sensei's action hooks
 *
 * Sensei outputs column data (post ID) indiscriminately on ANY custom post types
 * list page.
 *
 * This function will de-register Sensei's manage_posts_custom_column hook
 * from WordPress on Awesome Support's pages.
 *
 * @since 3.3.2
 * @return void
 */
function wpas_remove_sensei_actions() {

	// Only make changes on our pages. Don't want to mess up even more with other stuff
	if ( ! wpas_is_plugin_page() ) {
		return;
	}

	if ( class_exists( 'Sensei_Main' ) ) {
		$sensei_main = Sensei_Main::instance( null );
		remove_action( 'manage_posts_custom_column', array( $sensei_main->lesson, 'add_column_data' ) );
		remove_action( 'manage_posts_custom_column', array( $sensei_main->course, 'add_column_data' ) );
		remove_action( 'manage_posts_custom_column', array( $sensei_main->question, 'add_column_data' ) );
	}

}
