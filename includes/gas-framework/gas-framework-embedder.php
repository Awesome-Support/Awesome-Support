<?php
/**
 * This script is not used within Gas Framework itself.
 *
 * This script is meant to be used when embedding Gas Framework into your
 * theme or plugin.
 *
 * To embed Gas Framework into your project, copy the whole Gas Framework folder
 * into your project, then in your functions.php or main plugin script, do a
 * require_once( 'gas-framework/gas-framework-embedder.php' );
 *
 * When done, your project will use the embedded copy of Gas Framework. When the plugin
 * version is activated, that one will be used instead.
 *
 *
 * @package Gas Framework
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

if ( ! class_exists( 'GASFrameworkEmbedder' ) ) {


	/**
	 * GAS Framework Embedder
	 *
	 * @since 1.6
	 */
	class GASFrameworkEmbedder {


		/**
		 * Constructor, add hooks for embedding for Gas Framework
		 *
		 * @since 1.6
		 */
		function __construct() {
			// Don't do anything when we're activating a plugin to prevent errors
			// on redeclaring Gas classes.
			if ( is_admin() ) {
				if ( ! empty( $_GET['action'] ) && ! empty( $_GET['plugin'] ) ) { // Input var: okay.
				    if ( 'activate' === $_GET['action'] ) { // Input var: okay.
				        return;
				    }
				}
			}
			add_action( 'after_setup_theme', array( $this, 'perform_check' ), 1 );
		}


		/**
		 * Uses GAS Framework
		 *
		 * @since 1.6
		 */
		public function perform_check() {
			if ( class_exists( 'GASFramework' ) ) {
				return;
			}
			require_once( 'gas-framework.php' );
		}
	}

	new GASFrameworkEmbedder();
}
