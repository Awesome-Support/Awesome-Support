<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_settings_style', 5, 1 );
/**
 * Add plugin style settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function mumei_ayuda_core_settings_style( $def ) {

	$settings = array(
		'style' => array(
			'name'    => __( 'Style', 'ayuda-help-desk' ),
			'options' => array(
				array(
					'name'    => __( 'Theme', 'ayuda-help-desk' ),
					'id'      => 'theme',
					'type'    => 'select',
					'desc'    => __( 'Which theme to use for the front-end.', 'ayuda-help-desk' ),
					'options' => mumei_ayuda_list_themes(),
					'default' => 'default'
				),
				array(
					'name'    => __( 'Overlay', 'ayuda-help-desk' ),
					'id'      => 'theme_overlay',
					'type'    => 'select',
					'desc'    => __( 'An overlay is generally a pure css variation on the theme selected above that overides the colors of the selected theme.  The default overlay is the most widely compatible overlay - other overlays may or may not work with your theme.  There is limited technical support for overlays other than the default.', 'ayuda-help-desk' ),
					'options' => mumei_ayuda_list_overlays(),
					'default' => 'style.css'
				),				
				array(
					'name'    => __( 'Theme Stylesheet', 'ayuda-help-desk' ),
					'id'      => 'theme_stylesheet',
					'type'    => 'checkbox',
					'desc'    => __( 'Load the theme stylesheet. Don\'t uncheck if you don\'t know what this means', 'ayuda-help-desk' ),
					'default' => true
				),
				array(
					'name'    => __( 'Use editor in front-end', 'ayuda-help-desk' ),
					'id'      => 'frontend_wysiwyg_editor',
					'type'    => 'checkbox',
					'desc'    => __( 'Show an editor for the ticket description when user submits a ticket.', 'ayuda-help-desk' ),
					'default' => true
				),
				array(
					'name'    => __( 'Use automatic linker', 'ayuda-help-desk' ),
					'id'      => 'use_autolinker',
					'type'    => 'checkbox',
					'desc'    => __( 'Automatically link URLs, email addresses, phone numbers, twitter handles, and hashtags', 'ayuda-help-desk' ),
					'default' => true
				),
				array(
					'name' => __( 'Colors', 'ayuda-help-desk' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Open Status', 'ayuda-help-desk' ),
					'id'      => 'color_open',
					'type'    => 'color',
					'default' => '#81d742',
				),
				array(
					'name'    => __( 'Closed Status', 'ayuda-help-desk' ),
					'id'      => 'gas_color_closed',
					'type'    => 'color',
					'default' => '#dd3333',
				),
				array(
					'name'    => __( 'Old Status', 'ayuda-help-desk' ),
					'id'      => 'color_old',
					'type'    => 'color',
					'default' => '#dd9933',
				),
				array(
					'name'    => __( 'Awaiting Reply', 'ayuda-help-desk' ),
					'id'      => 'color_awaiting_reply',
					'type'    => 'color',
					'default' => '#0074a2',
				),
				array(
					'name'    => __( 'Ticket Template Type', 'ayuda-help-desk' ),
					'id'      => 'color_ticket_template_type',
					'type'    => 'color',
					'default' => '#1383D9',
				),				
			)
		),
	);

	$status   = mumei_ayuda_get_post_status();

	$defaults = apply_filters( 'mumei_ayuda_labels_default_colors', array(
		'queued'     => '#1e73be',
		'processing' => '#a01497',
		'hold'       => '#b56629',
		'unknown'    => '#169baa'
	) );
	
	foreach ( $status as $id => $label ) {
		
		$option = array(
			'name'    => $label,
			'id'      => 'color_' . $id,
			'type'    => 'color',
			'default' => isset( $defaults[$id] ) ? $defaults[$id] : mumei_ayuda_get_option( "color_$id", $defaults['unknown'] ),
		);

		array_push( $settings['style']['options'], $option );
	}
	
	return array_merge( $def, apply_filters('mumei_ayuda_settings_style', $settings )  );

}