<div id="wpas-ticket-message" class="wpas-ticket-content">
	<?php
	/**
	 * wpas_frontend_ticket_content_before hook
	 *
	 * @since  3.0.0
	 */
	do_action( 'wpas_backend_ticket_content_before', $post->ID, $post );

	echo apply_filters( 'the_content', $post->post_content );

	/**
	 * wpas_backend_ticket_content_after hook
	 *
	 * @since  3.0.0
	 */
	do_action( 'wpas_backend_ticket_content_after', $post->ID, $post );

	?>
</div>