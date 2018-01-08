<?php
global $post;

$status = get_post_meta( $post->ID, '_wpas_status', true );
?>

<?php do_action( 'wpas_backend_replies_top', $post ); ?>

<!-- Table of replies, notes and logs -->
<table class="form-table wpas-table-replies">
	<tbody>

		<?php
		/* If the post hasn't been saved yet we do not display the metabox's content */
		if( '' == $status ): ?>

			<div class="updated below-h2" style="margin-top: 2em;">
				<h2 style="margin: 0.5em 0; padding: 0; line-height: 100%;"><?php _e( 'Create Ticket', 'awesome-support' ); ?></h2>
				<p><?php _e( 'Please save this ticket to reveal all options.', 'awesome-support' ); ?></p>
			</div>

		<?php
		/* Now let's display the real content */
		else:

			/* We're going to get all the posts part of the ticket history */
			$replies_args = array(
				'posts_per_page' => - 1,
				'orderby'        => 'post_date',
				'order'          => wpas_get_option( 'replies_order', 'ASC' ),
				'post_type'      => apply_filters( 'wpas_replies_post_type', array(
					'ticket_history',
					'ticket_reply'
				) ),
				'post_parent'    => $post->ID,
				'post_status'    => apply_filters( 'wpas_replies_post_status', array(
					'publish',
					'inherit',
					'private',
					'trash',
					'read',
					'unread'
				) )
			);

			$history = new WP_Query( $replies_args );

			if ( ! empty( $history->posts ) ):

				foreach ( $history->posts as $row ):

					// Set the author data (if author is known)
					if ( $row->post_author != 0 ) {
						$user_data = get_userdata( $row->post_author );
						$user_id   = $user_data->data->ID;
						$user_name = $user_data->data->display_name;
					}

					// In case the post author is unknown, we set this as an anonymous post
					else {
						$user_name = __( 'Anonymous', 'awesome-support' );
						$user_id   = 0;
					}

					$user_avatar     = get_avatar( $user_id, '64', get_option( 'avatar_default' ) );
					$date            = human_time_diff( get_the_time( 'U', $row->ID ), current_time( 'timestamp' ) );
					$date_full		 = get_the_time('F j, Y g:i a', $row->ID);
					$days_since_open = wpas_get_date_diff_string( $post->post_date, $row->post_date) ;  // This is a string showing the number of dates/hours/mins that this reply arrived compared to the date the ticket was opened
					$post_type       = $row->post_type;
					$post_type_class = ( 'ticket_reply' === $row->post_type && 'trash' === $row->post_status ) ? 'ticket_history' : $row->post_type;

					/**
					 * This hook is fired just before we open the post row
					 *
					 * @param WP_Post $row Reply post object
					 */
					do_action( 'wpas_backend_replies_outside_row_before', $row );
					?>
					<tr valign="top" class="wpas-table-row wpas-<?php echo str_replace( '_', '-', $post_type_class ); ?> wpas-<?php echo str_replace( '_', '-', $row->post_status ); ?>" id="wpas-post-<?php echo $row->ID; ?>">
					
						<?php
						/**
						 * This hook is fired just after we opened the post row
						 *
						 * @param WP_Post $row Reply post object
						 */
						do_action( 'wpas_backend_replies_inside_row_before', $row );

						switch( $post_type ):

							/* Ticket Reply */
							case 'ticket_reply':

								if ( 'trash' != $row->post_status ) {
									require( WPAS_PATH . 'includes/admin/metaboxes/replies-published.php' );
								} elseif ( 'trash' == $row->post_status ) {
									require( WPAS_PATH . 'includes/admin/metaboxes/replies-trashed.php' );
								}

								break;

							case 'ticket_history':
								require( WPAS_PATH . 'includes/admin/metaboxes/replies-history.php' );
								break;

						endswitch;

						/**
						 * This hook is fired just before we close the post row
						 *
						 * @param WP_Post $row Reply post object
						 */
						do_action( 'wpas_backend_replies_inside_row_after', $row );
						?>

					</tr>

					<?php if ( 'ticket_reply' === $post_type && 'trash' !== $row->post_status ): ?>

						<tr class="wpas-editor wpas-editwrap-<?php echo $row->ID; ?>" style="display:none;">
							<td colspan="2">
								<div class="wpas-wp-editor" style="margin-bottom: 1em;"></div>
								<input id="wpas-edited-reply-<?php echo $row->ID; ?>" type="hidden" name="edited_reply">
								<input type="submit" id="wpas-edit-submit-<?php echo $row->ID; ?>" class="button-primary wpas-btn-save-edit" value="<?php _e( 'Save changes', 'awesome-support' ); ?>">
								<input type="button" class="wpas-editcancel button-secondary" data-origin="#wpas-reply-<?php echo $row->ID; ?>" data-replyid="<?php echo $row->ID; ?>" data-reply="wpas-editwrap-<?php echo $row->ID; ?>" data-wysiwygid="wpas-editreply-<?php echo $row->ID; ?>" value="<?php _e( 'Cancel', 'awesome-support' ); ?>">
							</td>
						</tr>

					<?php endif; ?>

					<?php
					/**
					 * wpas_backend_replies_outside_row_after hook
					 */
					do_action( 'wpas_backend_replies_outside_row_after', $row );
					?>

				<?php endforeach;
			endif;
		endif; ?>
	</tbody>
</table>

<hr />
<div>
	<?php do_action( 'wpas_backend_replies_bottom_before', $post ); ?>
	
	<!-- Link to collapse replies -->
	<span name="wpas_collapse_replies_bottom" id="wpas-collapse-replies-bottom" class="link-primary wpas-link-reply wpas-replies-links-bottom" value="collapse_replies"><?php _e( 'Toggle Replies', 'awesome-support' ); ?></span>	
	
	<?php do_action( 'wpas_backend_replies_bottom_after', $post ); ?>
</div>

<?php
if( 'open' == $status ):

	if( current_user_can( 'reply_ticket' ) ):
		require( WPAS_PATH . 'includes/admin/metaboxes/replies-form.php' );
	else: ?>

		<p><?php _e( 'Sorry, you don\'t have sufficient permissions to reply to tickets.', 'awesome-support' ); ?></p>

	<?php endif;

/* The ticket was closed */
elseif( 'closed' == $status ): ?>

	<div class="updated below-h2" style="margin-top: 2em;">
		<h2 style="margin: 0.5em 0; padding: 0; line-height: 100%;"><?php _e('Ticket is closed', 'wpas'); ?></h2>
		<p><?php printf( __( 'This ticket has been closed. If you want to write a new reply to this ticket, you need to <a href="%s">re-open it first</a>.', 'awesome-support' ), wpas_get_open_ticket_url( $post->ID ) ); ?></p>
	</div>

<?php endif;
