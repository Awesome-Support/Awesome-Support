<?php
/**
 * @package   Awesome Support/Admin/Functions/Post-Type
 * Implements some filters that will prevent gutenberg from firing on ticket related pages.
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Return a filtered list of post types that we would
 * want to block Gutenberg on.
 *
 * @since  5.8.1
 * 
 * @return array List of postypes that Gutenberg should not run on.
 */
function wpas_blocked_gutenberg_post_types() {
	
	$blocked[] = 'ticket';
	$blocked[] = 'ticket_reply';
	
	return apply_filters( 'wpas_blocked_gutenberg_post_types', $blocked ) ;
	
}

add_filter( 'allowed_block_types', 'wpas_filter_gutenberg_blocks_ticket' );
/**
 * Make sure that new tickets that use the GUTENBERG editor can only use the paragraph block type
 * We're going to disable GUTENBERG blocks on the tickets and reply post type completely by
 * hooking into another filter later anyway so this is just a prophylactic measure.
 *
 * @since  4.4.0
 * 
 * @return array List of allowed block types
 */
 function wpas_filter_gutenberg_blocks_ticket( $block_types ) {
	 
	$post             	= get_post();
	$post_type        	= get_post_type( $post );
	$blocked_post_types	= wpas_blocked_gutenberg_post_types() ;

	if ( ! in_array( $post_type, $blocked_post_types ) ) {
		return $block_types;
	}
	
	$allowed = [];
	$allowed[] = 'core/paragraph';
	$allowed[] = 'core/freeform';

	 
	 return apply_filters( 'wpas_editor_allowed_block_types', $allowed );
	 
}
 
 add_filter( 'use_block_editor_for_post_type', 'wpas_filter_use_gutenberg_for_ticket', 10, 2 );
 add_filter( 'gutenberg_can_edit_post_type', 'wpas_filter_use_gutenberg_for_ticket', 10, 2 );
 /**
 * Disable the use of Gutenberg completely in the tickets and ticket reply post types.
 *
 * @since  5.8.1
 * 
 * @return boolean
 */
 function wpas_filter_use_gutenberg_for_ticket( $use_block_editor, $post_type ) {
	 
	$blocked_post_types	= wpas_blocked_gutenberg_post_types() ; 
	 
	if ( ! in_array( $post_type, $blocked_post_types ) ) {
		return $use_block_editor;
	}	 
	 
	return false ;
	
}

