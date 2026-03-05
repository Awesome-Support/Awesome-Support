<?php
/**
 * @package   Ayuda – Help Desk/Admin/Functions/Gutenberg
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'enqueue_block_editor_assets', 'mumei_ayuda_gutenberg_enqueue_block_editor_assets' );
/**
 * Register the Gutenberg blocks
 *
 * The function below registers all the javascript files used
 * to render Gutenberg blocks
 *
 * @since 4.4.0
 */
function mumei_ayuda_gutenberg_enqueue_block_editor_assets() {

	if ( function_exists( 'gutenberg_get_jed_locale_data' ) ) {

		$locale_data = gutenberg_get_jed_locale_data( 'ayuda-help-desk' );

		wp_add_inline_script(
			'wp-i18n',
			'wp.i18n.setLocaleData( ' . json_encode( $locale_data ) . ' );'
		);	

	}
	
    wp_enqueue_script( 'wpas-gutenberg-block-submit-ticket',  MUMEI_AYUDA_URL . 'includes/admin/gutenberg/blocks/submit-ticket/submit-ticket-block.js', array( 'wp-blocks', 'wp-element' ) );
	wp_enqueue_script( 'wpas-gutenberg-block-my-tickets',     MUMEI_AYUDA_URL . 'includes/admin/gutenberg/blocks/my-tickets/my-tickets-block.js',       array( 'wp-blocks', 'wp-element' ) );	
}
