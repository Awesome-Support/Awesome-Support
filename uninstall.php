<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Awesome_Support
 * @author    Julien Liabeuf <julien@liabeuf.Fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( is_multisite() ) {
	global $wpdb;
	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
		wpas_uninstall();
	if ( $blogs ) {
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			wpas_uninstall();
			restore_current_blog();
		}
	}
}
else
{
	wpas_uninstall();
}

/**
 * Uninstall function.
 *
 * The uninstall function will only proceed if
 * the user explicitly asks for all data to be removed.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_uninstall() {

	$options = get_option( 'wpas_options' );

	if ( !isset( $options['delete_data'] ) ) {
		return;
	}

	if ( '1' !== $options['delete_data'] ) {
		return;
	}

	delete_option( 'wpas_options' );
	delete_option( 'wpas_db_version' );
	delete_option( 'wpas_version' );

	/**
	 * Delete the plugin pages.
	 */
	wp_delete_post( intval( $options['ticket_submit'] ), true );
	wp_delete_post( intval( $options['ticket_list'] ), true );

	/**
	 * Delete all posts from all custom post types
	 * that the plugin created.
	 */
	$args = array(
		'post_type'              => array( 'ticket', 'ticket_reply', 'ticket_history', 'ticket_log' ),
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		
	);

	$posts = new WP_Query( $args );

	foreach ( $posts->posts as $post ) {
		if ( 'attachment' === $post->post_type ) {
			wp_delete_attachment( $post->ID, true );
		} else {
			wp_delete_post( $post->ID, true );
		}
	}

	/**
	 * Delete all products if the taxonomy
	 * was in use on this install.
	 */
	if ( '1' === $options['support_products'] ) {

		$products = get_terms( WPAS_PRODUCT_SLUG );

		foreach ( $products as $product ) {
			wp_delete_term( $product->term_id, WPAS_PRODUCT_SLUG );
		}

	}

	/**
	 * Delete all terms from custom taxonomies
	 * that might have been added.
	 */
	global $wpas_cf;

	$fields = $wpas_cf->get_custom_fields();

	foreach ( $fields as $field ) {

		if ( 'taxonomy' === $field['args']['callback'] ) {

			$custom_taxos = get_terms( $field['name'] );

			foreach ( $custom_taxos as $taxo ) {
				wp_delete_term( $taxo->term_id, $field['name'] );
			}

		}

	}

	/**
	 * Delete all tags terms.
	 */

}