<div id="mumei_ayuda_ticekt_main_toolbar">

<?php
global $post;

$status = get_post_meta( $post->ID, '_mumei_ayuda_status', true );
?>


<?php 
/**
 * Use this hook to insert items at the beginning of the toolbar.
 * Generally, just call the mumei_ayuda_add_ticket_detail_toolbar_item() 
 * function at the bottom of this file to add a new toolbar item.
 */
do_action( 'mumei_ayuda_ticket_detail_toolbar01_before', $post ); 

// Print main toolbar
mumei_ayuda_ticket_toolbar( $post->ID );

/**
 * Use this hook to insert items at the end of the toolbar.
 * Generally, just call the mumei_ayuda_add_ticket_detail_toolbar_item() 
 * function at the bottom of this file to add a new toolbar item. 
 */
do_action( 'mumei_ayuda_ticket_detail_toolbar01_after', $post ); 
?>

	<!-- Toolbar Message area -->
	<div class="mumei_ayuda_tb01_msg_area" id="wpas-tb01-msg-area">
		<div class="mumei_ayuda_btn_msg">
			<p></p>		
		</div>
	</div>
</div>