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

/* iThemes Exchange */
require_once( WPAS_PATH . 'includes/integrations/class-product-exchange.php' );
add_action( 'init', array( 'WPAS_Product_Exchange', 'get_instance' ), 11, 0 );

/* WP eCommerce */
require_once( WPAS_PATH . 'includes/integrations/class-product-wp-ecommerce.php' );
add_action( 'init', array( 'WPAS_Product_WP_Ecommerce', 'get_instance' ), 11, 0 );

/* Jigoshop */
require_once( WPAS_PATH . 'includes/integrations/class-product-jigoshop.php' );
add_action( 'init', array( 'WPAS_Product_Jigoshop', 'get_instance' ), 11, 0 );