<?php
/**
 * @package   Awesome Support/Admin/Functions/Toolbar
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
 * Generate toolbar item markup
 * 
 * @param array $args
 * 
 * @return string
 */
function wpas_toolbar_item( $args ) {
	
	$defaults = array(
		'id_param'		=> 'id',
		'id_css'		=> '',
		'type'			=> 'button',
		'link'			=> '#',
		'icon'			=> '',
		'tool_tip_text' => '',
		'attributes'    => '',
		'data'			=> array(),
		'classes'		=> ''
	);

	$args = wp_parse_args( $args, $defaults );
	
	
	$id   = $args['id'];
	$type = $args['type'];
	$link = $args['link'];
	$icon = $args['icon'];
	$classes = $args['classes'];
	
	$tool_tip_text = $args['tool_tip_text'];
	
	$attributes = $args['attributes'];
	
	if( 'id' === $args['id_param'] ) {
		$attributes = " id=\"{$id}\"";
	} else {
		$classes .= " wpas_toolbar_item_{$id}";
	}
	
	
	$data_params = is_array( $args['data'] ) ?  $args['data'] : array();
	
	foreach( $data_params as $dp_name => $dp_value ) {
		$attributes .= " data-{$dp_name}=\"{$dp_value}\"";
	}
	
	$item = "";
	
	$wrapper_classes = "hint-bottom hint-anim wpas_toolbar_item";
	
	if( 'link' === $type ) {
		
		$item = sprintf( '<li data-hint="%s" class="%s"><a href="%s" class="%s" %s><span class="%s"></span></a></li>', $tool_tip_text, $wrapper_classes, $link, $classes, $attributes, "wpas-icon {$icon}" );
	} else {
		$item = sprintf( '<li data-hint="%s" class="%s"><span class="%s" %s></span></li>', $tool_tip_text, $wrapper_classes, "wpas-icon {$icon} {$classes}", $attributes );
	}
	
	return $item;
}


/**
 * Generate toolbar markup
 * 
 * @param string $type unique toolbar name
 * @param array $fun_args
 * 
 * @return string
 */
function wpas_toolbar( $type, $fun_args = array() ) {
	
	$id = "wpas_toolbar_{$type}";
	
	$args = array();
	
	$args[0] = $id;
	$args[1] = array();
	
	foreach( $fun_args as $arg ) {
		$args[] = $arg;
	}
	
	$toolbar_items = call_user_func_array( 'apply_filters', $args );
	
	
	// Stop processing if no toolbar item exist
	if( empty( $toolbar_items ) ) {
		return;
	}
	
	
	$items = array();
	
	foreach ( $toolbar_items as $item_id => $item ) {
		
		$item['id'] = $item_id;
		
		$items[] = wpas_toolbar_item( $item );
	}
	
	
	$items[] = '<li class="clear clearfix"></li>';
	return '<ul class="wpas-toolbar" id="wpas-toolbar-'.$type.'">' . implode( "\n\r", $items ) . '</ul>';
	
}


add_filter( 'wpas_toolbar_ticket', 'wpas_toolbar_ticket_items', 11, 2 );


/**
 * Add items to main ticket toolbar
 * 
 * @param array $items
 * @param int $ticket_id
 * 
 * @return array
 */
function wpas_toolbar_ticket_items( $items, $ticket_id ) {
	
	$items['wpas-collapse-replies-top'] = array(
			'icon' => 'icon-hide-ticket-replies',
			'tool_tip_text' => __( 'Toggle Replies (Hide All Replies Except The Last 3)', 'awesome-support' )
		);
	
	
	$items['wpas-toggle-ticket-slug'] = array(
			'icon' => 'icon-hide-ticket-urls',
			'tool_tip_text' => __( 'Show/Hide The Ticket Slug', 'awesome-support' )
		);
	
	$items['wpas-edit-main-ticket-message'] = array(
			'icon' => 'icon-edit-ticket-replies',
			'tool_tip_text' => __( 'Edit Ticket', 'awesome-support' ),
			'data' => array(
				'ticketid' => $ticket_id
			)
		);
	
	
	$items['wpas-view-edit-main-ticket-message'] = array(
			'icon' => 'icon-due-date',
			'tool_tip_text' => __( 'View History', 'awesome-support' ),
			'data' => array(
				'ticketid' => $ticket_id
			)
		);
	
	
	return $items;
	
}


/**
 * Print main ticket toolbar
 */
function wpas_ticket_toolbar() {
	
	$tabs_content = wpas_toolbar( 'ticket', func_get_args() );
	echo $tabs_content;
	
}

/**
 * Print toolbar with each reply
 */
function wpas_ticket_reply_toolbar() {
	
	$tabs_content = wpas_toolbar( 'ticket_reply', func_get_args() );
	echo $tabs_content;
	
}