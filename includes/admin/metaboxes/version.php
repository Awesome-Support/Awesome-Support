<?php
/**
 * Show the Awesome Support Version Number
 *
 * This metabox is used to display the awesome support
 * version number.  It will be used later to show
 * additional debugging information in real time.
 *
 * @since 4.4.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

echo( esc_html__('Awesome Support Version: ', 'awesome_support') . esc_attr( WPAS_VERSION ) ) ;

