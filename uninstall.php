<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Awesome Support/Uninstallation
 * @author    Julien Liabeuf <julien@liabeuf.Fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
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
else {
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

	$options = maybe_unserialize( get_option( 'wpas_options' ) );

	/* Make sure that the user wants to remove all the data. */
	if ( isset( $options['delete_data'] ) && '1' === $options['delete_data'] ) {

		/* Remove all plugin options. */
		delete_option( 'wpas_options' );
		delete_option( 'wpas_db_version' );
		delete_option( 'wpas_version' );
		delete_option( 'wpas_dismiss_free_addon_page' );
		delete_option( 'wpas_plugin_setup' );
		delete_option( 'wpas_skip_wizard_setup' );

		/* Delete the plugin pages.	 */
		wp_delete_post( intval( $options['ticket_submit'] ), true );
		wp_delete_post( intval( $options['ticket_list'] ), true );

		/**
		 * Delete all posts from all custom post types
		 * that the plugin created.
		 */
		$args = array(
			'post_type'              => array( 'ticket', 'ticket_reply', 'ticket_history', 'ticket_log' ),
			'post_status'            => array( 'any', 'trash', 'auto-draft' ),
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			
		);

		$posts = new WP_Query( $args );

		/* Delete all post types and attachments */
		foreach ( $posts->posts as $post ) {

			wpas_delete_attachments( $post->ID );
			wp_delete_post( $post->ID, true );

			$upload_dir = wp_upload_dir();
			$dirpath    = trailingslashit( $upload_dir['basedir'] ) . "awesome-support/ticket_$post->ID";

			if ( $post->post_parent == 0 && is_dir( $dirpath ) ) {

				$it    = new RecursiveDirectoryIterator( $dirpath, RecursiveDirectoryIterator::SKIP_DOTS );
				$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

				/* Delete each file */
				foreach ( $files as $file ) {
					if ( $file->isDir() ) {
						rmdir( $file->getRealPath() );
					} else {
						unlink( $file->getRealPath() );
					}
				}

				/* Delete the uploads folder */
				rmdir( $dirpath );

				/* Remove transients */
				delete_transient( "wpas_activity_meta_post_$post->ID" );
			}
		}

		/* Delete all tag terms. */
		wpas_delete_taxonomy( 'ticket-tag' );

		/**
		 * Delete all products if the taxonomy
		 * was in use on this install.
		 */
		wpas_delete_taxonomy( 'product' );

		/**
		* Delete all deparments
		*/
		wpas_delete_taxonomy( 'department' );
		
		/**
		* Delete Priority taxonomy
		*/
		wpas_delete_taxonomy( 'ticket_priority' );		
		
		/**
		* Delete Channel taxonomy
		*/
		wpas_delete_taxonomy( 'ticket_channel' );				
		
	}

}

/**
 * Delete all terms of the given taxonomy.
 *
 * As the get_terms function is not available during uninstall
 * (because the taxonomies are not registered), we need to work
 * directly with the $wpdb class. The function gets all taxonomy terms
 * and deletes them one by one.
 *
 * @since  3.0.0
 * @param  string $taxonomy Name of the taxonomy to delete
 * @link   http://wordpress.stackexchange.com/a/119353
 * @return void
 */
function wpas_delete_taxonomy( $taxonomy ) {

	global $wpdb;

	$query = 'SELECT t.name, t.term_id
			FROM ' . $wpdb->terms . ' AS t
			INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt
			ON t.term_id = tt.term_id
			WHERE tt.taxonomy = "' . $taxonomy . '"';

	$terms = $wpdb->get_results($query);

	foreach ( $terms as $term ) {
		wp_delete_term( $term->term_id, $taxonomy );
	}

}

/**
 * Delete attachments.
 *
 * Delete all tickets and replies attachments.
 *
 * @since  3.0.0
 * @param  integer $post_id ID of the post to delete attachments from
 * @return void
 */
function wpas_delete_attachments( $post_id ) {

	$args = array(
		'post_type'              => 'attachment',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'post_parent'            => $post_id,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		
	);

	$posts = new WP_Query( $args );

	foreach ( $posts->posts as $post ) {
		wp_delete_attachment( $post->ID, true );
	}

}