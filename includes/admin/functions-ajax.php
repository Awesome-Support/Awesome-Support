<?php
/**
 * @package   Awesome Support/Admin/Functions/Ajax
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2015 ThemeAvenue
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