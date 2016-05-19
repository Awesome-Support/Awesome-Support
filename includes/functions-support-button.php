<?php

add_action( 'wp_footer', 'wpas_support_btn', 100 );
/**
 * Create a support button that points to the ticket submission page
 *
 * @since 3.3
 * @return void
 */
function wpas_support_btn() {
	$enable = (bool) wpas_get_option ( 'support_btn_enable' );
	$logged_in = (bool) wpas_get_option ( 'support_btn_logged_in' );

	if ( false === $enable ) {
		return;
	}

	if( true === $logged_in && false === is_user_logged_in() ) {
		return;    	
	}

	$label = wpas_get_option ( 'support_btn_label' );
	$color_text = wpas_get_option ( 'support_btn_color_text' );
	$color_background = wpas_get_option ( 'support_btn_color_background' );
	$position = wpas_get_option ( 'support_btn_position' );
	$ticket_submit_page = wpas_get_submission_page_url();

	echo "<a class='wpas_support_btn $position' href='$ticket_submit_page' style='color: $color_text; background-color: $color_background;'>$label</a>";
}