<?php
global $post;

$status = get_post_meta( $post->ID, '_wpas_status', true );
?>

<table class="form-table wpas-table-replies">
	<tbody>

		<?php
		/* If the post hasn't been saved yet we do not display the metabox's content */
		if( '' == $status ): ?>

			<div class="updated below-h2" style="margin-top: 2em;">
				<h2 style="margin: 0.5em 0; padding: 0; line-height: 100%;"><?php _e( 'Create Ticket', 'wpas' ); ?></h2>
				<p><?php _e( 'Please save this ticket to reveal all options.', 'wpas' ); ?></p>
			</div>

		<?php
		/* Now let's display the real content */
		else:

			/* We're going to get all the posts part of the ticket history */
			$replies_args = array(
				'posts_per_page' =>	-1,
				'orderby'        =>	'post_date',
				'order'          =>	wpas_get_option( 'replies_order', 'ASC' ),
				'post_type'      =>	apply_filters( 'wpas_replies_post_type', array( 'ticket_history', 'ticket_reply' ) ),
				'post_parent'    =>	$post->ID,
				'post_status'    =>	apply_filters( 'wpas_replies_post_status', array( 'publish', 'inherit', 'private', 'trash', 'read', 'unread' ) )
			);

			$history = new WP_Query( $replies_args );

			if ( !empty( $history->posts ) ):

				foreach( $history->posts as $row ):

					/**
					 * Reply posted by registered member
					 */
					if( $row->post_author != 0 ) {

						$user_data 		= get_userdata( $row->post_author );
						$user_id 		= $user_data->data->ID;
						$user_name 		= $user_data->data->display_name;

					}

					/**
					 * Reply posted anonymously
					 */
					else {
						$user_name 		= __('Anonymous', 'wpas');
						$user_id 		= 0;
					}

					$user_avatar     = get_avatar( $user_id, '64', get_option( 'avatar_default' ) );
					$date            = human_time_diff( get_the_time( 'U', $row->ID ), current_time( 'timestamp' ) );
					$post_type       = $row->post_type;
					$post_type_class = ( 'ticket_reply' === $row->post_type && 'trash' === $row->post_status ) ? 'ticket_history' : $row->post_type;

					/**
					 * Layout for replies
					 */
					
					/**
					 * wpas_backend_replies_outside_row_before hook
					 */
					do_action( 'wpas_backend_replies_outside_row_before', $row );
					?>
					<tr valign="top" class="wpas-table-row wpas-<?php echo str_replace( '_', '-', $post_type_class ); ?> wpas-<?php echo str_replace( '_', '-', $row->post_status ); ?>" id="wpas-post-<?php echo $row->ID; ?>">
					
					<?php
					/**
					 * wpas_backend_replies_inside_row_before hook
					 */
					do_action( 'wpas_backend_replies_inside_row_before', $row );

					switch( $post_type ):

						/* Ticket Reply */
						case 'ticket_reply':

							if( 'trash' != $row->post_status ): ?>

								<td class="col1" style="width: 64px;">

									<?php
									/* Display avatar only for replies */
									if( 'ticket_reply' == $row->post_type ) {
										echo $user_avatar;
									}
									?>
									
								</td>
								<td class="col2">

									<?php if ( 'unread' === $row->post_status ): ?><div id="wpas-unread-<?php echo $row->ID; ?>" class="wpas-unread-badge"><?php _e( 'Unread', 'wpas' ); ?></div><?php endif; ?>
									<div class="wpas-reply-meta">
										<div class="wpas-reply-user">
											<strong class="wpas-profilename"><?php echo $user_name; ?></strong> <span class="wpas-profilerole">(<?php echo wpas_get_user_nice_role( $user_data->roles[0] ); ?>)</span>
										</div>
										<div class="wpas-reply-time">
											<time class="wpas-timestamp" datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . wpas_get_offset_html5(); ?>"><span class="wpas-human-date"><?php echo date( get_option( 'date_format' ), strtotime( $row->post_date ) ); ?> | </span><?php printf( __( '%s ago', 'wpas' ), $date ); ?></time>
										</div>
									</div>

									<div class="wpas-ticket-controls">
										<?php
										if( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) && get_current_user_id() == $row->post_author ) {

											$_GET['del_id'] = $row->ID;
											$url            = add_query_arg( $_GET, admin_url( 'post.php' ) );
											$url            = remove_query_arg( 'message', $url );
											$delete         = wpas_url_add_custom_action( $url, 'trash_reply' );
											$edit           = wp_nonce_url( add_query_arg( array( 'post' => $_GET['post'], 'rid' => $row->ID, 'action' => 'edit_reply' ), admin_url( 'post.php' ) ), 'delete_reply_' . $row->ID );

											echo '<a class="button-secondary wpas-delete" href="' . esc_url( $delete ) . '"title="' . __( 'Delete', 'wpas' ) . '">' . __( 'Delete', 'wpas' ) . '</a>';
											echo '<a class="button-secondary wpas-edit" href="#" data-origin="#wpas-reply-' . $row->ID . '" data-replyid="' . $row->ID . '" data-reply="wpas-editwrap-' . $row->ID . '" data-wysiwygid="wpas-editreply-' . $row->ID . '" title="' . __( 'Edit', 'wpas' ) . '">' . __( 'Edit', 'wpas' ) . '</a>';

										}

										if ( get_current_user_id() !== $row->post_author && 'unread' === $row->post_status ) {
											echo '<a class="button-secondary wpas-mark-read" href="#" data-replyid="' . $row->ID . '" title="' . __( 'Mark as Read', 'wpas' ) . '">' . __( 'Mark as Read', 'wpas' ) . '</a>';
										}
										?>
									</div>

									<?php
									/* Filter the content before we display it */
									$content = apply_filters( 'the_content', $row->post_content );

									/* The content displayed to agents */
									echo '<div class="wpas-reply-content" id="wpas-reply-' . $row->ID . '">';

									/**
									 * wpas_backend_reply_content_before hook
									 *
									 * @since  3.0.0
									 */
									do_action( 'wpas_backend_reply_content_before', $row->ID );

									echo $content;

									/**
									 * wpas_backend_reply_content_after hook
									 *
									 * @since  3.0.0
									 */
									do_action( 'wpas_backend_reply_content_after', $row->ID );

									echo '</div>';
									?>
								</td>

							<?php elseif( 'trash' == $row->post_status ): ?>
								<td colspan="3">
									<?php printf( __( 'This reply has been deleted by %s <em class="wpas-time">%s ago.</em>', 'wpas' ), "<strong>$user_name</strong>", human_time_diff( strtotime( $row->post_modified ), current_time( 'timestamp' ) ) ); ?>
								</td>
							<?php endif;

						break;

						case 'ticket_history':

							/**
							 * wpas_backend_history_content_before hook
							 *
							 * @since  3.0.0
							 */
							do_action( 'wpas_backend_history_content_before', $row->ID );

							/* Filter the content before we display it */
							$content = apply_filters( 'the_content', $row->post_content );

							/**
							 * wpas_backend_history_content_after hook
							 *
							 * @since  3.0.0
							 */
							do_action( 'wpas_backend_history_content_after', $row->ID ); ?>

							<td colspan="3">
								<span class="wpas-action-author"><?php echo $user_name; ?>, <em class='wpas-time'><?php printf( __( '%s ago', 'wpas' ), $date ); ?></em></span>
								<div class="wpas-action-details"><?php echo $content; ?></div>
							</td>

						<?php break;

					endswitch;

					/**
					 * wpas_backend_replies_inside_row_after hook
					 */
					do_action( 'wpas_backend_replies_inside_row_after', $row );
					?>

					</tr>

					<?php if ( 'ticket_reply' === $post_type && 'trash' !== $row->post_status ): ?>

						<tr class="wpas-editor wpas-editwrap-<?php echo $row->ID; ?>" style="display:none;">
							<td colspan="2">
								<div class="wpas-editwrap" id="wpas-editwrap-<?php echo $row->ID; ?>">
									<?php
									/* The edition textarea */
									wp_editor( $content, 'wpas-editreply-' . $row->ID, array(
										'media_buttons' => false,
										'teeny' 		=> true,
										'quicktags' 	=> false,
										'editor_class' 	=> 'wpas-edittextarea',
										'textarea_name' => 'wpas_edit_reply[' . $row->ID . ']',
										'textarea_rows' => 20
										)
									);
									?>

									<br>
									<input id="wpas-edited-reply-<?php echo $row->ID; ?>" type="hidden" name="edited_reply">
									<input type="submit" class="button-primary wpas-btn-save-edit" value="<?php _e( 'Save changes', 'wpas' ); ?>"> 
									<input type="button" class="wpas-editcancel button-secondary" data-origin="#wpas-reply-<?php echo $row->ID; ?>" data-replyid="<?php echo $row->ID; ?>" data-reply="wpas-editwrap-<?php echo $row->ID; ?>" data-wysiwygid="wpas-editreply-<?php echo $row->ID; ?>" value="<?php _e( 'Cancel', 'wpas' ); ?>">
								</div>
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

<?php
if( 'open' == $status ):

	if( current_user_can( 'reply_ticket' ) ): ?>

		<h2><?php printf( __( 'Write a reply to &laquo;%s&raquo;', 'wpas' ), get_the_title( $post->ID ) ); ?></h2>
		<div>
			<?php
			/**
			 * Load the WordPress WYSIWYG with minimal options
			 */
			/* The edition textarea */
			wp_editor( '', 'wpas_reply', array(
				'media_buttons' => false,
				'teeny' 		=> true,
				'quicktags' 	=> true,
				)
			);
			?>
		</div>
		<?php
		/**
		 * Add a hook after the WYSIWYG editor
		 * for tickets reply.
		 *
		 * @WPAS_Quick_Replies::echoMarkup()
		 */
		do_action( 'wpas_admin_after_wysiwyg' );

		/**
		 * Add a nonce for the reply
		 */
		wp_nonce_field( 'reply_ticket', 'wpas_reply_ticket', false, true );
		?>

		<div class="wpas-reply-actions">
			<?php
			/**
			 * Where should the user be redirected after submission.
			 * 
			 * @var string
			 */
			global $current_user;
			$where = get_user_meta( $current_user->ID, 'wpas_after_reply', true );

			switch ( $where ):

				case false:
				case '':
				case 'back': ?>
					<input type="hidden" name="wpas_back_to_list" value="1">
					<button type="submit" name="wpas_do" class="button-primary" value="reply"><?php _e( 'Reply', 'wpas' ); ?></button>
				<?php break;				

				break;

				case 'stay':
					?><button type="submit" name="wpas_do" class="button-primary" value="reply"><?php _e( 'Reply', 'wpas' ); ?></button><?php
				break;

				case 'ask': ?>
					<fieldset>
						<strong><?php _e( 'After Replying', 'wpas' ); ?></strong><br>
						<label for="back_to_list"><input type="radio" id="back_to_list" name="where_after" value="back_to_list" checked="checked"> <?php _e( 'Back to list', 'wpas' ); ?></label>
						<label for="stay_here"><input type="radio" id="stay_here" name="where_after" value="stay_here"> <?php _e( 'Stay on ticket screen', 'wpas' ); ?></label>
					</fieldset>
					<button type="submit" name="wpas_do" class="button-primary" value="reply"><?php _e( 'Reply', 'wpas' ); ?></button>
				<?php break;

			endswitch;
			?>
			
			<?php if ( current_user_can( 'close_ticket' ) ): ?>
				<button type="submit" name="wpas_do" class="button-secondary" value="reply_close"><?php _e( 'Reply & Close', 'wpas' ); ?></button>
			<?php endif; ?>
		</div>

	<?php else: ?>

		<p><?php _e( 'Sorry, you don\'t have sufficient permissions to reply to tickets.', 'wpas' ); ?></p>

	<?php endif;

/* The ticket was closed */
elseif( 'closed' == $status ): ?>

	<div class="updated below-h2" style="margin-top: 2em;">
		<h2 style="margin: 0.5em 0; padding: 0; line-height: 100%;"><?php _e('Ticket is closed', 'wpas'); ?></h2>
		<p><?php printf( __( 'This ticket has been closed. If you want to write a new reply to this ticket, you need to <a href="%s">re-open it first</a>.', 'wpas' ), wpas_get_open_ticket_url( $post->ID ) ); ?></p>
	</div>

<?php endif;