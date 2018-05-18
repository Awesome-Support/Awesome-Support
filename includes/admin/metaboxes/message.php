<div id="wpas-ticket-message" class="wpas-ticket-content">
	<?php
	/**
	 * wpas_frontend_ticket_content_before hook
	 *
	 * @since  3.0.0
	 */
	do_action( 'wpas_backend_ticket_content_before', $post->ID, $post );

	printf(
		'<div class="wpas-main-ticket-message" id="wpas-main-ticket-message">%s</div>',
		apply_filters( 'the_content', $post->post_content ) 
	);

	/**
	 * wpas_backend_ticket_content_after hook
	 *
	 * @since  3.0.0
	 */
	do_action( 'wpas_backend_ticket_content_after', $post->ID, $post );

	/**
	 * Allows certain user roles from Settings -> General -> History
	 *
	 * Administrator should be always on. Both site admin and Super Admin
	 */
	if( is_admin() || is_super_admin() ) {
		printf( 
			'<div class="wpas-edit-ticket-actions"><a href="#" class="button button-primary wpas-edit-main-ticket-message" id="wpas-edit-main-ticket-message" data-ticketid="%s">%s</a>' .
			'<a href="#" class="button button-primary wpas-save-edit-main-ticket-message" id="wpas-save-edit-main-ticket-message" data-ticketid="%s">%s</a> ' .
			'<a href="#" class="button button-secondary wpas-cancel-edit-main-ticket-message" id="wpas-cancel-edit-main-ticket-message" data-ticketid="%s">%s</a></div>', 
			$post->ID,
			__( 'Edit', 'awesome-support' ),
			$post->ID,
			__( 'Save', 'awesome-support' ),
			$post->ID,
			__( 'Cancel', 'awesome-support' )
		);
	}
	?>
</div>