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
	
	if( wpas_is_support_priority_active() ) {
		$terms = wp_get_post_terms( $post->ID, 'ticket_priority' );
		$priority_color = "";
		if( !empty( $terms ) && $terms[0] instanceof WP_Term) {
			$priority_color = get_term_meta( $terms[0]->term_id, 'color', true );
		}
		
		echo '<input type="hidden" id="ticket_priority_color" value="' . esc_attr( $priority_color ) . '" />';
	}
	?>
</div>