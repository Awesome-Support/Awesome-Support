<?php
/**
 * @package   Awesome Support/Compatibility/Divi
 * @author    Awesome Support <contact@awesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://awesomesupport.com
 * @copyright 2017 Awesome Support
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/* DIVI somehow causes the autosave to activate on our ticket screen so deactivate it. */
function as_divi_add_query(){
	if ( function_exists( 'et_divi_fonts_url' ) ) {	
		if( 'ticket' === get_post_type() ){
			wp_dequeue_script('autosave');
		}
	}
}
add_action('admin_print_scripts','as_divi_add_query');

/* DIVI will pop up a message when the admin creates a new ticket asking to confirm leaving the new ticket screen. 	*/
/* This change unhooks all form exit scripts when leaving the ticket screen 										*/
/* Its not perfect since it could cause anything else that needs this hook to fail as well. 						*/
function as_divi_add_admin_scripts( $hook ) {
	if ( function_exists( 'et_divi_fonts_url' ) ) {	
		if ( 'ticket' === get_post_type() ) {     
			?>
			<script>
				jQuery('form#post').submit(function() {
					jQuery(window).unbind('beforeunload');
				});
			</script>
			<?php
		}
	}
}
add_action( 'in_admin_footer', 'as_divi_add_admin_scripts', 10, 1 );