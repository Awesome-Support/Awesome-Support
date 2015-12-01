<?php
/**
 * @package   Awesome Support/Functions/Actions
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2015 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'init', 'wpas_process_actions' );
/**
 * Process actions that can be triggered by $_GET or $_POST vars
 *
 * To trigger an action, a superglobal var must be passed with the key wpas-do.
 * The other superglobal vars will then be passed as arguments to the hook.
 *
 * @since 3.3
 * @return void
 */
function wpas_process_actions() {

	if ( isset( $_POST['wpas-do'] ) ) {
		do_action( 'wpas_do_' . $_POST['wpas-do'], $_POST );
	}

	if ( isset( $_GET['wpas-do'] ) ) {
		do_action( 'wpas_do_' . $_GET['wpas-do'], $_GET );
	}

}