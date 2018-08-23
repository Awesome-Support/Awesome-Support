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
?>

<!-- Button to collapse replies -->
<?php echo wpas_add_ticket_detail_toolbar_item( 'img', 'wpas-collapse-replies-top', __( 'Toggle Replies (Hide All Replies Except The Last 3)', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/toggle-replies.png" ); ?>

<!-- Button to toggle ticket slug -->
<?php echo wpas_add_ticket_detail_toolbar_item( 'img', 'wpas-toggle-ticket-slug', __( 'Show/Hide The Ticket Slug', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/toggle-ticket-slug.png" ); ?>

<?php 

echo wpas_add_ticket_detail_toolbar_item( 'img', 'wpas-edit-main-ticket-message', __( 'Edit Ticket', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/edit_ticket.png", '',  "data-ticketid=\"$post->ID\"" );

echo wpas_add_ticket_detail_toolbar_item( 'img', 'wpas-view-edit-main-ticket-message', __( 'View History', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/view_history.png", '',  "data-ticketid=\"$post->ID\"" );

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