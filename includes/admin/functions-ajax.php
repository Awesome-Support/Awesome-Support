<?php
/**
 * @package   Awesome Support/Admin/Functions/Ajax
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wp_ajax_wpas_dismiss_free_addon_page', 'wpas_dismiss_free_addon_page' );
/**
 * Hide the free addon page from the menu
 *
 * @since 3.3.3
 * @return bool
 */
function wpas_dismiss_free_addon_page() {
	return add_option( 'wpas_dismiss_free_addon_page', true );
}

add_action( 'wp_ajax_wpas_skip_wizard_setup', 'wpas_skip_wizard_setup' );
/**
 * Skip Setup Wizard
 *
 * @since 3.3.3
 * @return bool
 */
function wpas_skip_wizard_setup() {	
	add_option( 'wpas_skip_wizard_setup', true );
	wp_die();
}