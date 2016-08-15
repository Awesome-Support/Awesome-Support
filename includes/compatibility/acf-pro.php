<?php
/**
 * @package   Awesome Support/Compatibility/ACF Pro
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://etawesomesupport.com
 * @copyright 2016 Awesome Support
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wp_print_scripts', 'wpas_remove_acf_pro_select2_assets' );
/**
 * Fix compatibility issue with ACF Pro's select2
 *
 * ACF Pro uses select2, just like Awesome Support. However, instead of loading select2's assets only where it is used
 * by ACF, they load the assets everywhere, which messes us with our own instances of select2 if the versions of
 * select2 used by Awesome Support and ACF Pro don't match.
 *
 * @since 3.3.2
 * @return void
 */
function wpas_remove_acf_pro_select2_assets() {

	// Only make changes on our pages. Don't want to mess up even more with other stuff
	if ( ! wpas_is_plugin_page() ) {
		return;
	}

	// Make sure that ACF Pro is installed and active
	if ( ! class_exists( 'acf' ) ) {
		return;
	}

	wp_deregister_script( 'select2-l10n' );
	wp_deregister_script( 'select2' );
	wp_deregister_style( 'select2' );

}