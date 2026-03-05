<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_enqueue_scripts', 'mumei_ayuda_override_wc_select2_style', 12 );
/**
 * Fix compatibility issue with WooCommerce's select2
 *
 * This function will override WC select2 style on Ayuda – Help Desk's pages.
 *
 * @return void
 */
function mumei_ayuda_override_wc_select2_style() {

	// Only make changes on our pages. Don't want to mess up even more with other stuff
	if ( ! mumei_ayuda_is_plugin_page() ) {
		return;
	}

	// Make sure that WooCommerce is installed and active
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
	if( is_plugin_active( 'woocommerce/woocommerce.php') ) {
		
		wp_enqueue_style( 'wpas-override-wc-select2-style', MUMEI_AYUDA_URL . 'assets/admin/css/wc-select2.css', array(), MUMEI_AYUDA_VERSION );
		
	}
	
}