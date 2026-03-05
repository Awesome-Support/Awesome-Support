<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Ayuda – Help Desk/Uninstallation
 * @author    Mumei
 * @license   GPL-2.0+
 * @link      https://mumei.io
 * @copyright 2014-2017 AwesomeSupport
 * Modified by Mumei (2026)
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( is_multisite() ) {
	global $wpdb;
	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
		mumei_ayuda_uninstall();
	if ( $blogs ) {
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			mumei_ayuda_uninstall();
			restore_current_blog();
		}
	}
}
else {
	mumei_ayuda_uninstall();
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
function mumei_ayuda_uninstall() {

	$options = maybe_unserialize( get_option( 'mumei_ayuda_options' ) );
	global $wp_filesystem;

	// Initialize the filesystem 
	if (empty($wp_filesystem)) {
		require_once(ABSPATH . '/wp-admin/includes/file.php');
		WP_Filesystem();
	} 
	
	/* Make sure that the user wants to remove all the data. */
	if ( isset( $options['delete_data'] ) && '1' === $options['delete_data'] ) {

		/* Remove all plugin options. */
		delete_option( 'mumei_ayuda_options' );
		delete_option( 'mumei_ayuda_db_version' );
		delete_option( 'mumei_ayuda_version' );
		delete_option( 'mumei_ayuda_dismiss_free_addon_page' );
		delete_option( 'mumei_ayuda_plugin_setup' );
		delete_option( 'mumei_ayuda_skip_wizard_setup' );

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

			mumei_ayuda_delete_attachments( $post->ID );
			wp_delete_post( $post->ID, true );

			$upload_dir = wp_upload_dir();
			$ticket_id_encode = md5($post->ID . NONCE_SALT);	
			$dirpath    = trailingslashit( $upload_dir['basedir'] ) . "ayuda-help-desk/ticket_$ticket_id_encode";

			if ( $post->post_parent == 0 && is_dir( $dirpath ) ) {

				$it    = new RecursiveDirectoryIterator( $dirpath, RecursiveDirectoryIterator::SKIP_DOTS );
				$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

				/* Delete each file */
				foreach ( $files as $file ) {
					if ( $file->isDir() ) {
						$wp_filesystem->delete($file->getRealPath(), true);
					} else {
						unlink( $file->getRealPath() );
					}
				}

				/* Delete the uploads folder */
				$wp_filesystem->delete($dirpath, true);
				/* Remove transients */
				delete_transient( "mumei_ayuda_activity_meta_post_$post->ID" );
			}
		}

		/* Delete all tag terms. */
		mumei_ayuda_delete_taxonomy( 'ticket-tag' );

		/**
		 * Delete all products if the taxonomy
		 * was in use on this install.
		 */
		mumei_ayuda_delete_taxonomy( 'product' );

		/**
		* Delete all deparments
		*/
		mumei_ayuda_delete_taxonomy( 'department' );
		
		/**
		* Delete Priority taxonomy
		*/
		mumei_ayuda_delete_taxonomy( 'ticket_priority' );
		
		/**
		* Delete ticket type taxonomy
		*/
		mumei_ayuda_delete_taxonomy( 'ticket_type' );				
		
		/**
		* Delete Channel taxonomy
		*/
		mumei_ayuda_delete_taxonomy( 'ticket_channel' );				
		
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
function mumei_ayuda_delete_taxonomy( $taxonomy ) {

	global $wpdb;
	$sql = 'SELECT t.name, t.term_id
			FROM ' . $wpdb->terms . ' AS t
			INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt
			ON t.term_id = tt.term_id
			WHERE tt.taxonomy = "' . $taxonomy . '"';
			
	$terms = $wpdb->get_results("$sql");

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
function mumei_ayuda_delete_attachments( $post_id ) {

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