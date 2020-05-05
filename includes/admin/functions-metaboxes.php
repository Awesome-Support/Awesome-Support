<?php
/**
 * @package   Awesome Support/Admin/Functions/Metaboxes
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'add_meta_boxes', 'wpas_metaboxes' );
/**
 * Register the metaboxes.
 *
 * The function below registers all the metaboxes used
 * in the ticket edit screen.
 *
 * @since 3.0.0
 */
function wpas_metaboxes() {

	global $pagenow;

	/* Remove the publishing metabox */
	remove_meta_box( 'submitdiv', 'ticket', 'side' );
	
	/* Possibly remove the TAGS metabox */
	if (  wpas_current_role_in_list( wpas_get_option( 'hide_tags_mb_roles' ) ) )  {
		remove_meta_box( 'tagsdiv-ticket-tag', 'ticket', 'side' );
	}
	
	$status = isset( $_GET['post'] ) ? get_post_meta( intval( $_GET['post'] ), '_wpas_status', true ) : '';

	/**
	 * Register the metaboxes.
	 */	
	
	/* Metabox to add main tabs */
	add_meta_box( 'wpas-mb-ticket-main-tabs', __( 'Main Tabs', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'normal', 'high', array( 'template' => 'ticket-main-tabs' ) );
		
	if ( '' !== $status ) {
		/* Ticket Replies */
		add_meta_box( 'wpas-mb-replies', __( 'Ticket Replies', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'normal', 'high', array( 'template' => 'replies' ) );
	}
	
	/* Ticket details */
	add_meta_box( 'wpas-mb-details', __( 'Ticket Details', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'high', array( 'template' => 'details' ) );
	
	
	/* Client profile */
	if ( 'post-new.php' !== $pagenow ) {
		add_meta_box( 'wpas-mb-user-profile', __( 'User Profile', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'high', array( 'template' => 'user-profile' ) );
	}
	
	/* Add a dummy metabox to force gutenberg to render in old-style mode... */
	// add_meta_box( 'wpas-mb-version', __( 'Misc and Debug', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'low', array( 'template' => 'version', '__block_editor_compatible_meta_box' => wpas_gutenberg_meta_box_compatible() ) );	
	add_meta_box( 'wpas-mb-version', __( 'Misc and Debug', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'low', array( 'template' => 'version' ) );
}

/**
 * Metabox callback function.
 *
 * The below function is used to call the metaboxes content.
 * A template name is given to the function. If the template
 * does exist, the metabox is loaded. If not, nothing happens.
 *
 * @since  3.0.0
 *
 * @param  int   $post Post ID
 * @param  array $args Arguments passed to the callback function
 *
 * @return void
 */
function wpas_metabox_callback( $post, $args ) {

	if ( ! is_array( $args ) || ! isset( $args['args']['template'] ) ) {
		_e( 'An error occurred while registering this metabox. Please contact support.', 'awesome-support' );
	}

	$template = $args['args']['template'];

	if ( ! file_exists( WPAS_PATH . "includes/admin/metaboxes/$template.php" ) ) {
		_e( 'An error occured while loading this metabox. Please contact support.', 'awesome-support' );
	}

	/* Include the metabox content */
	include_once( WPAS_PATH . "includes/admin/metaboxes/$template.php" );

}

// Add a filter to the_content to remove http and https
add_filter( 'the_content', 'wpas_the_content_remove_https', 7, 1 );
if ( ! function_exists( 'wpas_the_content_remove_https' ) ) {
	/**
	 * Remove http and https from the_content if content starts with it.
	 *
	 * @param  string $content String of post content
	 *
	 * @return string      Updated content
	 */
	function wpas_the_content_remove_https( $content )
	{
		// filter $content and replace http or https
		if ( strpos($content, 'http://') === 0 || strpos($content, 'https://') === 0 ) {
			$content = str_replace( array( 'http://', 'https://' ), '', $content );
		}
		
		return $content;
	}
}
