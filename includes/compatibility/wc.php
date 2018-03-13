<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_enqueue_scripts', 'wpas_override_wc_select2_style', 12 );
/**
 * Fix compatibility issue with WooCommerce's select2
 *
 * This function will override WC select2 style on Awesome Support's pages.
 *
 * @return void
 */
function wpas_override_wc_select2_style() {

	// Only make changes on our pages. Don't want to mess up even more with other stuff
	if ( ! wpas_is_plugin_page() ) {
		return;
	}

	// Make sure that WooCommerce is installed and active
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
	if( is_plugin_active( 'woocommerce/woocommerce.php') ) {
		
		wp_enqueue_style( 'wpas-override-wc-select2-style', WPAS_URL . 'assets/admin/css/wc-select2.css', array(), WPAS_VERSION );
		
	}
	
}