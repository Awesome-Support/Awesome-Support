<?php
add_shortcode( 'ticket-submit', 'wpas_sc_submit_form' );
/**
 * Submission for shortcode.
 */
function wpas_sc_submit_form() {

	global $post;

	/* Start the buffer */
	ob_start();

	/* Open main container */
	?><div class="wpas"><?php

		/**
		 * wpas_before_ticket_submit hook
		 */
		do_action( 'wpas_before_ticket_submit' );

		/**
		 * wpas_frontend_plugin_page_top is executed at the top
		 * of every plugin page on the front end.
		 */
		do_action( 'wpas_frontend_plugin_page_top', $post->ID, $post );

		/* If user is not logged in we display the register form */
		if( !is_user_logged_in() ):

			$registration = wpas_get_option( 'login_page', false );

			if ( false !== $registration && !empty( $registration ) && !is_null( get_post( intval( $registration ) ) ) ) {
				/* As the headers are already sent we can't use wp_redirect. */
				echo '<meta http-equiv="refresh" content="0; url=' . get_permalink( $registration ) . '" />';
				echo wpas_get_notification_markup( 'info', __( 'You are being redirected...', 'awesome-support' ) );
				exit;
			}

			wpas_get_template( 'registration' );

		/**
		 * If user is logged in we display the ticket submission form
		 */
		else:

			/**
			 * wpas_before_ticket_submission_form hook
			 */
			do_action( 'wpas_before_ticket_submission_form_before_wrapper' );

			/* Namespace our content */
			echo '<div class="wpas">';

			/**
			 * wpas_before_all_templates hook.
			 *
			 * This hook is called at the top of every template
			 * used for the plugin front-end. This allows for adding actions
			 * (like notifications for instance) on all plugin related pages.
			 */
			do_action( 'wpas_before_all_templates' );

			/**
			 * wpas_before_ticket_submission_form hook
			 */
			do_action( 'wpas_before_ticket_submission_form' );

			/**
			 * Check if the current user is logged in
			 */
			if ( false === is_user_logged_in() ) {
				echo wpas_get_notification_markup( 'failure', sprintf( __( 'You need to <a href="%s">log-in</a> to submit a ticket.', 'awesome-support' ), esc_url( '' ) ) );
			} else {

				/**
				 * Make sure the current user can submit a ticket.
				 */
				if ( false === wpas_can_submit_ticket() ) {
					echo wpas_get_notification_markup( 'failure', __( 'You are not allowed to submit a ticket.', 'awesome-support' ) );
				}

				/**
				 * Show the actual submission form
				 */
				else {

					/**
					 * We check if the user is authorized to submit a ticket.
					 * User must be logged-in and can't have the capability. If the
					 * user isn't authorized to submit, we return the error message hereafter.
					 *
					 * Basically, admins and agents aren't allowed to submit a ticket as they
					 * need to do it in the back-end.
					 *
					 * If you want to allow admins and agents to submit tickets through the
					 * front-end, please use the filter wpas_agent_submit_front_end and set the value to (bool) true.
					 */
					if ( is_user_logged_in() && current_user_can( 'edit_ticket' ) && ( false === apply_filters( 'wpas_agent_submit_front_end', false ) ) ):

						/**
						 * Keep in mind that if you allow agents to open ticket through the front-end, actions
						 * will not be tracked.
						 */
						echo wpas_get_notification_markup( 'info', sprintf( __( 'Sorry, support team members cannot submit tickets from here. If you need to open a ticket, please go to your admin panel or <a href="%s">click here to open a new ticket</a>.', 'awesome-support' ), add_query_arg( array( 'post_type' => 'ticket' ), admin_url( 'post-new.php' ) ) ) );

					/**
					 * If the user is authorized to post a ticket, we display the submit form
					 */
					else:

						global $post;

						/**
						 * wpas_submission_form_before hook
						 *
						 * @since  3.0.0
						 */
						do_action( 'wpas_submission_form_before' );

						wpas_get_template( 'submission' );

						/**
						 * wpas_submission_form_after hook
						 *
						 * @since  3.0.0
						 */
						do_action( 'wpas_submission_form_after' );

					endif;
				}
			}

			/**
			 * wpas_after_ticket_submission_form hook
			 */
			do_action( 'wpas_after_ticket_submission_form' );

			echo '</div>';

		endif;

		/**
		 * wpas_after_ticket_submit hook
		 */
		do_action( 'wpas_after_ticket_submit' ); ?>

	</div>

	<?php
	/* Get buffer content */
	$sc = ob_get_contents();

	/* Clean the buffer */
	ob_end_clean();

	/* Return shortcode's content */
	return $sc;

}