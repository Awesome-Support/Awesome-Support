<?php
/*----------------------------------------------------------------------------*
 * eCommerce
 *----------------------------------------------------------------------------*/

/* WooCommerce */
require_once( WPAS_PATH . 'includes/integrations/class-product-woocommerce.php' );
add_action( 'init', array( 'WPAS_Product_WooCommerce', 'get_instance' ), 11, 0 );

/* Easy Digital Downloads */
require_once( WPAS_PATH . 'includes/integrations/class-product-edd.php' );
add_action( 'init', array( 'WPAS_Product_EDD', 'get_instance' ), 11, 0 );

/* Easy Digital Downloads */
require_once( WPAS_PATH . 'includes/integrations/class-product-exchange.php' );
add_action( 'init', array( 'WPAS_Product_Exchange', 'get_instance' ), 11, 0 );