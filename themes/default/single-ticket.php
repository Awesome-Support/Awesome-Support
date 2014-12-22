<?php
/**
 * Single Ticket Template.
 * 
 * This is a built-in template file. If you need to customize it, please,
 * DO NOT modify this file directly. Instead, copy it to your theme's directory
 * and then modify the code. If you modify this file directly, your changes
 * will be overwritten during next update of the plugin.
 */

/* Exit if accessed directly */
if( !defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

get_header();

/**
 * Display possible messages to the visitor.
 */
if ( isset( $_GET['message'] ) ) {
	wpas_notification( false, $_GET['message'] );
}

/**
 * wpas_frontend_plugin_page_top is executed at the top
 * of every plugin page on the front end.
 */
do_action( 'wpas_frontend_plugin_page_top', $post->ID, $post );

/**
 * Get the custom template.
 */
while ( have_posts() ) : the_post();

	wpas_get_template( 'details' );

endwhile;

get_footer();
