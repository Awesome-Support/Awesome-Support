<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_products_management', 5, 1 );
/**
 * Add plugin core settings for managing products
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function mumei_ayuda_core_products_management( $def ) {

	$settings = array(
		'products-management' => array(
			'name'    => __( 'Products Management', 'ayuda-help-desk' ),
			'options' => array(
				array(
					'name' => __( 'Products Management', 'ayuda-help-desk' ),
					'type' => 'heading',
					'options' => mumei_ayuda_get_products_options()
				),
			)
		),
	);

	return array_merge( $def, apply_filters('mumei_ayuda_settings_products_mgt', $settings )  );

}

/**
 * Prepare the available options for the products
 *
 * @since 3.3
 * @return array
 */
function mumei_ayuda_get_products_options() {

	$products = array(
		array(
			'name'    => __( 'Multiple Products', 'ayuda-help-desk' ),
			'id'      => 'support_products',
			'type'    => 'checkbox',
			'desc'    => __( 'If you need to provide support for multiple products, please enable this option. You will then be able to add your products.', 'ayuda-help-desk' ),
			'default' => false
		),
		
		array(
			'name'    => __( 'Slug', 'ayuda-help-desk' ),
			'id'      => 'products_slug',
			'type'    => 'text',
			'desc'    => __( 'Enter the slug you would like to use for your product urls. If you change this, please go to the WordPress SETTINGS->PERMALINKS page and click the save button to force WP to update its configuration with your new value', 'ayuda-help-desk' ),
			'default' => 'product'
		),		
	);
	
	$ecommerce_synced = MUMEI_AYUDA_eCommerce_Integration::get_instance()->plugin;

	if ( ! is_null( $ecommerce_synced ) ) {

		$plugin_name = ucwords( str_replace( array( '-', '_' ), ' ', $ecommerce_synced ) );

		// translators: %1$s is the woocommerce plugin name.
		$desc = __( 'We have detected that you are using the e-commerce plugin %1$s. Would you like to automatically synchronize your e-commerce products with Ayuda – Help Desk?', 'ayuda-help-desk' );

		// translators: %1$s is URL to delete product.
		$desc1 = __( 'If you just disabled this option and want to remove the previously synchronized products, <a href="%1$s">please use the dedicated option &laquo;Delete Products&raquo;</a>', 'ayuda-help-desk' );

		// translators: %s is the product name.
		$name = __( 'Synchronize %s Products', 'ayuda-help-desk' );

		$products[] = array(
			'name'    => sprintf( esc_html($name), $plugin_name ),
			'id'      => 'support_products_' . $ecommerce_synced,
			'type'    => 'checkbox',
			'desc'    => sprintf( esc_html($desc), $plugin_name ),
			'default' => true
		);

		$products[] = array(
			'type' => 'note',
			'desc' => wp_kses( sprintf( $desc1, esc_url( add_query_arg( array(
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

		$registered = MUMEI_AYUDA_eCommerce_Integration::get_instance()->get_plugins();
		$post_type  = $registered[ $ecommerce_synced ]['post_type'];
		$options    = ( isset( $_GET['page'] ) && 'wpas-settings' === $_GET['page'] && mumei_ayuda_is_plugin_page() )
			? mumei_ayuda_list_pages( $post_type )
			: '';

		$products[] = array(
			'name'     => __( 'Include Products', 'ayuda-help-desk' ),
			'id'       => 'support_products_' . $ecommerce_synced . '_include',
			'type'     => 'select',
			'multiple' => true,
			'desc'     => esc_html__( 'Which products do you want to synchronize with Ayuda – Help Desk (leave blank for all products)', 'ayuda-help-desk' ),
			'options'  => $options,
			'default'  => ''
		);

		$products[] = array(
			'name'     => __( 'Exclude Products', 'ayuda-help-desk' ),
			'id'       => 'support_products_' . $ecommerce_synced . '_exclude',
			'type'     => 'select',
			'multiple' => true,
			'desc'     => esc_html__( 'Which products do you want to exclude from synchronization with Ayuda – Help Desk (leave blank for no exclusion)', 'ayuda-help-desk' ),
			'options'  => $options,
			'default'  => ''
		);

		$products[] = array(
			'type' => 'note',
			'desc' => esc_html__( 'You cannot use the include and exclude options at the same time. Please use one or the other. You should use the option where you need to select the least amount of products.', 'ayuda-help-desk' )
		);
		
		$products[] = array(
			'name'     => __( 'Product Statuses To Sync', 'ayuda-help-desk' ),
			'id'       => 'support_products_statuses',
			'type'     => 'text',
			'desc'     => esc_html__( 'Which statuses would you liked synced? By default only published products will be synced. For multiple statuses separate by commas with no spaces.', 'ayuda-help-desk' ),
			'default'  => 'publish'
		);		

	}

	return $products;

}