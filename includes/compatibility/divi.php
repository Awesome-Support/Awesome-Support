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
function add_query(){
	if ( function_exists( 'et_divi_fonts_url' ) ) {	
		global $post, $wpdb;
		if(get_post_type($post->ID) === 'ticket'){
			wp_dequeue_script('autosave');
		}
	}
}
add_action('admin_print_scripts','add_query');

/* DIVI will pop up a message when the admin creates a new ticket asking to confirm leaving the new ticket screen. 	*/
/* This change unhooks all form exit scripts when leaving the ticket screen 										*/
/* Its not perfect since it could cause anything else that needs this hook to fail as well. 						*/
function add_admin_scripts( $hook ) {
	if ( function_exists( 'et_divi_fonts_url' ) ) {	
		global $post;
		if ( 'ticket' === $post->post_type ) {     
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
add_action( 'in_admin_footer', 'add_admin_scripts', 10, 1 );