<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_products_management', 5, 1 );
/**
 * Add plugin core settings for managing products
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_products_management( $def ) {

	$settings = array(
		'products-management' => array(
			'name'    => __( 'Products Management', 'awesome-support' ),
			'options' => array(
				array(
					'name' => __( 'Products Management', 'awesome-support' ),
					'type' => 'heading',
					'options' => wpas_get_products_options()
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