<div class="wpas-ticket-buttons-top">
	<?php mumei_ayuda_make_button( __( 'My Tickets', 'ayuda-help-desk' ), array( 'type' => 'link', 'link' => mumei_ayuda_get_tickets_list_page_url(), 'class' => 'wpas-btn wpas-btn-default wpas-link-ticketlist' ) ); ?>
	<?php mumei_ayuda_make_button( __( 'Open a ticket', 'ayuda-help-desk' ), array( 'type' => 'link', 'link' => mumei_ayuda_get_submission_page_url(), 'class' => 'wpas-btn wpas-btn-default wpas-link-ticketnew' ) ); ?>
    <?php apply_filters( 'mumei_ayuda_frontend_add_nav_buttons', null ); ?>
	<?php mumei_ayuda_make_button( __( 'Logout', 'ayuda-help-desk' ), array( 'type' => 'link', 'link' => mumei_ayuda_get_logout_redirect(), 'class' => 'wpas-btn wpas-btn-default wpas-link-logout' ) ); ?>
</div>