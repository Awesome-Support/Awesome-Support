<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_style', 5, 1 );
/**
 * Add plugin style settings.
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_core_settings_style( $def ) {

	$settings = array(
		'style' => array(
			'name'    => __( 'Style', 'awesome-support' ),
			'options' => array(
				array(
					'name'    => __( 'Theme', 'awesome-support' ),
					'id'      => 'theme',
					'type'    => 'select',
					'desc'    => __( 'Which theme to use for the front-end.', 'awesome-support' ),
					'options' => wpas_list_themes(),
					'default' => 'default'
				),
				array(
					'name'    => __( 'Theme Stylesheet', 'awesome-support' ),
					'id'      => 'theme_stylesheet',
					'type'    => 'checkbox',
					'desc'    => __( 'Load the theme stylesheet. Don\'t uncheck if you don\'t know what this means', 'awesome-support' ),
					'default' => true
				),
				array(
					'name'    => __( 'Use editor in front-end', 'awesome-support' ),
					'id'      => 'frontend_wysiwyg_editor',
					'type'    => 'checkbox',
					'desc'    => __( 'Show a editor editor for the ticket description when user submits a ticket.', 'awesome-support' ),
					'default' => true
				),
				array(
					'name' => __( 'Support Button', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Show button', 'awesome-support' ),
					'id'      => 'support_btn_enable',
					'type' => 'enable',
					'default' => false,
				),
				array(
					'name'    => __( 'Show button to logged-in users only', 'awesome-support' ),
					'id'      => 'support_btn_logged_in',
					'type' => 'enable',
					'default' => false,
				),
				array(
					'name'    => __( 'Button Label', 'awesome-support' ),
					'id'      => 'support_btn_label',
					'type'    => 'text',
					'default' => 'Get Help →',
				),
				array(
					'name'    => __( 'Button Text Color', 'awesome-support' ),
					'id'      => 'support_btn_color_text',
					'type'    => 'color',
					'default' => '#ffffff',
				),
				array(
					'name'    => __( 'Button Background Color', 'awesome-support' ),
					'id'      => 'support_btn_color_background',
					'type'    => 'color',
					'default' => '#d35400',
				),
				array(
					'name'    => __( 'Button Position', 'awesome-support' ),
					'id'      => 'support_btn_position',
					'type' => 'select',
					'desc' => __( 'Where do you want the button to appear?', 'awesome-support' ),
					'options' => array(
						'wpas_support_btn_top_left' => 'Top Left',
						'wpas_support_btn_top_right' => 'Top Right',
						'wpas_support_btn_bottom_left' => 'Bottom Left',
						'wpas_support_btn_bottom_right' => 'Bottom Right',
					),
					'default' => 'wpas_support_btn_middle_right',
				),
				array(
					'name' => __( 'Colors', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Open Status', 'awesome-support' ),
					'id'      => 'color_open',
					'type'    => 'color',
					'default' => '#81d742',
				),
				array(
					'name'    => __( 'Closed Status', 'awesome-support' ),
					'id'      => 'color_closed',
					'type'    => 'color',
					'default' => '#dd3333',
				),
				array(
					'name'    => __( 'Old Status', 'awesome-support' ),
					'id'      => 'color_old',
					'type'    => 'color',
					'default' => '#dd9933',
				),
				array(
					'name'    => __( 'Awaiting Reply', 'awesome-support' ),
					'id'      => 'color_awaiting_reply',
					'type'    => 'color',
					'default' => '#0074a2',
				),
			)
		),
	);

	$status   = wpas_get_post_status();

	$defaults = apply_filters( 'wpas_labels_default_colors', array(
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
			'default' => isset( $defaults[$id] ) ? $defaults[$id] : $defaults['unknown'],
		);

		array_push( $settings['style']['options'], $option );
	}

	return array_merge( $def, $settings );

}