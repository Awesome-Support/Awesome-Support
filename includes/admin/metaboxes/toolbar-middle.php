<div id="wpas_ticekt_main_toolbar">

<?php
global $post;

$status = get_post_meta( $post->ID, '_wpas_status', true );
?>


<?php 
/**
 * Use this hook to insert items at the beginning of the toolbar.
 * Generally, just call the wpas_add_ticket_detail_toolbar_item() 
 * function at the bottom of this file to add a new toolbar item.
 */
do_action( 'wpas_ticket_detail_toolbar01_before', $post ); 

// Print main toolbar
wpas_ticket_toolbar( $post->ID );

/**
 * Use this hook to insert items at the end of the toolbar.
 * Generally, just call the wpas_add_ticket_detail_toolbar_item() 
 * function at the bottom of this file to add a new toolbar item. 
 */
do_action( 'wpas_ticket_detail_toolbar01_after', $post ); 
?>

	<!-- Toolbar Message area -->
	<div class="wpas_tb01_msg_area" id="wpas-tb01-msg-area">
		<div class="wpas_btn_msg">
			<p></p>		
		</div>
	</div>
</div>