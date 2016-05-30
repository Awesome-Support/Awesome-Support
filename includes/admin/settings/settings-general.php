<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_general', 5, 1 );
/**
 * Add plugin core settings.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_general( $def ) {

	$user_registration = boolval( get_option( 'users_can_register' ) );
	$registration_lbl  = ( true === $user_registration ) ? _x( 'allowed', 'User registration is allowed', 'awesome-support' ) : _x( 'not allowed', 'User registration is not allowed', 'awesome-support' );

	$settings = array(
		'general' => array(
			'name'    => __( 'General', 'awesome-support' ),
			'options' => array(
				array(
					'name' => __( 'Misc', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Default Assignee', 'awesome-support' ),
					'id'      => 'assignee_default',
					'type'    => 'select',
					'desc'    => __( 'Who to assign tickets to in the case that auto-assignment wouldn&#039;t work. This does NOT mean that all tickets will be assigned to this user. This is a fallback option. To enable/disable auto assignment for an agent, please do so in the user profile settings.', 'awesome-support' ),
					'options' => isset( $_GET['post_type'] ) && 'ticket' === $_GET['post_type'] && isset( $_GET['page'] ) && 'wpas-settings' === $_GET['page'] ? wpas_list_users( 'edit_ticket' ) : array(),
					'default' => ''
				),
				array(
					'name'    => __( 'Allow Registrations', 'awesome-support' ),
					'id'      => 'allow_registrations',
					'type'    => 'radio',
					'desc'    => sprintf( __( 'Allow users to register on the support page. This setting can be enabled even though the WordPress setting is disabled. Currently, registrations are %s by WordPress.', 'awesome-support' ),  "<strong>$registration_lbl</strong>" ),
					'default' => 'allow',
					'options' => array(
						'allow'           => __( 'Allow registrations', 'awesome-support' ),
						'disallow'        => __( 'Disallow registrations', 'awesome-support' ),
						'disallow_silent' => __( 'Disallow registrations without notice (just show the login form)', 'awesome-support' ),
					)
				),
				array(
					'name'    => __( 'Replies Order', 'awesome-support' ),
					'id'      => 'replies_order',
					'type'    => 'radio',
					'desc'    => __( 'In which order should the replies be displayed (for both client and admin side)?', 'awesome-support' ),
					'options' => array( 'ASC' => __( 'Old to New', 'awesome-support' ), 'DESC' => __( 'New to Old', 'awesome-support' ) ),
					'default' => 'ASC'
				),
				array(
					'name'    => __( 'Replies Per Page', 'awesome-support' ),
					'id'      => 'replies_per_page',
					'type'    => 'text',
					'default' => 10,
					'desc'    => __( 'How many replies should be displayed per page on a ticket details screen?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Hide Closed', 'awesome-support' ),
					'id'      => 'hide_closed',
					'type'    => 'checkbox',
					'desc'    => __( 'Only show open tickets when clicking the "All Tickets" link.', 'awesome-support' ),
					'default' => true
				),
				array(
					'name'    => __( 'Show Count', 'awesome-support' ),
					'id'      => 'show_count',
					'type'    => 'checkbox',
					'desc'    => __( 'Display the number of open tickets in the admin menu.', 'awesome-support' ),
					'default' => true
				),
				array(
					'name'    => __( 'Old Tickets', 'awesome-support' ),
					'id'      => 'old_ticket',
					'type'    => 'text',
					'default' => 10,
					'desc'    => __( 'After how many days should a ticket be considered &laquo;old&raquo;?', 'awesome-support' )
				),
				array(
					'name'    => __( 'Departments', 'awesome-support' ),
					'id'      => 'departments',
					'type'    => 'checkbox',
					'desc'    => __( 'Enable departments management.', 'awesome-support' ),
					'default' => false
				),
				array(
					'name' => __( 'Products Management', 'awesome-support' ),
					'type' => 'heading',
					'options' => wpas_get_products_options()
				),
				array(
					'name' => __( 'Plugin Pages', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'     => __( 'Ticket Submission', 'awesome-support' ),
					'id'       => 'ticket_submit',
					'type'     => 'select',
					'multiple' => true,
					'desc'     => sprintf( __( 'The page used for ticket submission. This page should contain the shortcode %s', 'awesome-support' ), '<code>[ticket-submit]</code>' ),
					'options'  => wpas_list_pages(),
					'default'  => ''
				),
				array(
					'name'     => __( 'Tickets List', 'awesome-support' ),
					'id'       => 'ticket_list',
					'type'     => 'select',
					'multiple' => false,
					'desc'     => sprintf( __( 'The page that will list all tickets for a client. This page should contain the shortcode %s', 'awesome-support' ), '<code>[tickets]</code>' ),
					'options'  => wpas_list_pages(),
					'default'  => ''
				),
				array(
					'name' => __( 'Terms & Conditions', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'     => __( 'Content', 'awesome-support' ),
					'id'       => 'terms_conditions',
					'type'     => 'editor',
					'default'  => '',
					'desc'     => __( 'Terms & conditions are not mandatory. If you add terms, a mandatory checkbox will be added in the registration form. Users won\'t be able to register if they don\'t accept your terms', 'awesome-support' ),
					'settings' => array( 'quicktags' => true, 'textarea_rows' => 7 )
				),
				array(
					'name' => __( 'Credit', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Show Credit', 'awesome-support' ),
					'id'      => 'credit_link',
					'type'    => 'checkbox',
					'desc'    => __( 'Do you like this plugin? Please help us spread the word by displaying a credit link at the bottom of your ticket submission page.', 'awesome-support' ),
					'default' => false
				),
			)
		),
	);

	return array_merge( $def, $settings );

}

/**
 * Prepare the available options for the products
 *
 * @since 3.3
 * @return array
 */
function wpas_get_products_options() {

	$products = array(
		array(
			'name'    => __( 'Multiple Products', 'awesome-support' ),
			'id'      => 'support_products',
			'type'    => 'checkbox',
			'desc'    => __( 'If you need to provide support for multiple products, please enable this option. You will then be able to add your products.', 'awesome-support' ),
			'default' => false
		),
	);

	$ecommerce_synced = WPAS_eCommerce_Integration::get_instance()->plugin;

	if ( ! is_null( $ecommerce_synced ) ) {

		$plugin_name = ucwords( str_replace( array( '-', '_' ), ' ', $ecommerce_synced ) );

		$products[] = array(
			'name'    => sprintf( esc_html__( 'Synchronize %s Products', 'awesome-support' ), $plugin_name ),
			'id'      => 'support_products_' . $ecommerce_synced,
			'type'    => 'checkbox',
			'desc'    => sprintf( esc_html__( 'We have detected that you are using the e-commerce plugin %1$s. Would you like to automatically synchronize your e-commerce products with Awesome Support?', 'awesome-support' ), $plugin_name ),
			'default' => true
		);

		$products[] = array(
			'type' => 'note',
			'desc' => wp_kses( sprintf( __( 'If you just disabled this option and want to remove the previously synchronized products, <a href="%1$s">please use the dedicated option &laquo;Delete Products&raquo;</a>', 'awesome-support' ), esc_url( add_query_arg( array(
					'post_type' => 'ticket',
					'page'      => 'wpas-status',
					'tab'       => 'tools'
				), admin_url( 'edit.php' ) )
			) ), array(
				'a' => array(
					'href'  => array(),
					'title' => array()
				)
			) )
		);

		$registered = WPAS_eCommerce_Integration::get_instance()->get_plugins();
		$post_type  = $registered[ $ecommerce_synced ]['post_type'];

		$products[] = array(
			'name'     => __( 'Include Products', 'awesome-support' ),
			'id'       => 'support_products_' . $ecommerce_synced . '_include',
			'type'     => 'select',
			'multiple' => true,
			'desc'     => esc_html__( 'Which products do you want to synchronize with Awesome Support (leave blank for all products)', 'awesome-support' ),
			'options'  => wpas_list_pages( $post_type ),
			'default'  => ''
		);

		$products[] = array(
			'name'     => __( 'Exclude Products', 'awesome-support' ),
			'id'       => 'support_products_' . $ecommerce_synced . '_exclude',
			'type'     => 'select',
			'multiple' => true,
			'desc'     => esc_html__( 'Which products do you want to exclude from synchronization with Awesome Support (leave blank for no exclusion)', 'awesome-support' ),
			'options'  => wpas_list_pages( $post_type ),
			'default'  => ''
		);

		$products[] = array(
			'type' => 'note',
			'desc' => esc_html__( 'You cannot use the include and exclude options at the same time. Please use one or the other. You should use the option where you need to select the least amount of products.', 'awesome-support' )
		);

	}

	return $products;

}