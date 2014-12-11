<?php
/**
 * Ticket Details Template.
 * 
 * This is a built-in template file. If you need to customize it, please,
 * DO NOT modify this file directly. Instead, copy it to your theme's directory
 * and then modify the code. If you modify this file directly, your changes
 * will be overwritten during next update of the plugin.
 */

/* Exit if accessed directly */
if( !defined( 'ABSPATH' ) ) {
	exit;
}

global $wpas_replies, $post;

/* Get author meta */
$author = get_user_by( 'id', $post->post_author );
?>
<div class="wpas wpas-ticket-details">

	<?php
	/**
	 * Display the table header containing the tickets details.
	 * By default, the header will contain ticket status, ID, priority, type and tags (if any).
	 */
	wpas_ticket_header();
	?>

	<table class="wpas-table wpas-ticket-replies">
		<tbody>
			<tr class="wpas-reply-single" valign="top">
				<td style="width: 64px;">
					<div class="wpas-user-profile">
						<?php echo get_avatar( $post->post_author, '64', get_option( 'avatar_default' ) ); ?>
					</div>
				</td>

				<td>
					<div class="wpas-reply-meta">
						<div class="wpas-reply-user">
							<strong class="wpas-profilename"><?php echo $author->data->user_nicename; ?></strong>
						</div>
						<div class="wpas-reply-time">
							<time class="wpas-timestamp" datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . wpas_get_offset_html5(); ?>">
								<span class="wpas-human-date"><?php echo date( get_option( 'date_format' ), strtotime( $post->post_date ) ) . ' ' . date( get_option( 'time_format' ) ); ?></span>
								<span class="wpas-date-ago"><?php printf( __( '%s ago', 'wpas' ), human_time_diff( get_the_time( 'U', $post->ID ), current_time( 'timestamp' ) ) ); ?></span>
							</time>
						</div>
					</div>

					<?php
					/**
					 * wpas_frontend_ticket_content_before hook
					 *
					 * @since  3.0.0
					 */
					do_action( 'wpas_frontend_ticket_content_before', $post->ID, $post );

					/**
					 * Display the original ticket's content
					 */
					echo '<div class="wpas-reply-content">' . apply_filters( 'the_content', $post->post_content ) . '</div>';

					/**
					 * wpas_frontend_ticket_content_after hook
					 *
					 * @since  3.0.0
					 */
					do_action( 'wpas_frontend_ticket_content_after', $post->ID, $post );
					?>

				</td>

			</tr>

			<?php
			/**
			 * Start the loop for the ticket replies.
			 */
			if ( $wpas_replies->have_posts() ):
				while ( $wpas_replies->have_posts() ):

					$wpas_replies->the_post();
					$user_role = get_the_author_meta( 'roles' );
					$user_role = $user_role[0];
					$time_ago  = human_time_diff( get_the_time( 'U', $post->ID ), current_time( 'timestamp' ) );  ?>

					<tr id="reply-<?php echo the_ID(); ?>" class="wpas-reply-single wpas-status-<?php echo get_post_status(); ?>" valign="top">

						<?php
						/**
						 * Make sure the reply hasn't been deleted.
						 */
						if ( 'trash' === get_post_status() ) { ?>

							<td colspan="2">
								<?php printf( __( 'This reply has been deleted %s ago.', 'wpas' ), $time_ago ); ?>
							</td>
						
						<?php continue; } ?>

						<td style="width: 64px;">
							<div class="wpas-user-profile">
								<?php echo get_avatar( get_the_author_meta( 'user_email' ), 64, get_option( 'avatar_default' ) ); ?>
							</div>
						</td>

						<td>
							<div class="wpas-reply-meta">
								<div class="wpas-reply-user">
									<strong class="wpas-profilename"><?php echo get_the_author_meta( 'user_nicename' ); ?></strong>
								</div>
								<div class="wpas-reply-time">
									<time class="wpas-timestamp" datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . wpas_get_offset_html5(); ?>">
										<span class="wpas-human-date"><?php echo date( get_option( 'date_format' ), strtotime( get_the_date() ) ) . ' ' . date( get_option( 'time_format' ) ); ?></span>
										<span class="wpas-date-ago"><?php printf( __( '%s ago', 'wpas' ), $time_ago ); ?></span>
									</time>
								</div>
							</div>

							<?php
							/**
							 * wpas_frontend_reply_content_before hook
							 *
							 * @since  3.0.0
							 */
							do_action( 'wpas_frontend_reply_content_before', get_the_ID() );
							?>

							<div class="wpas-reply-content"><?php the_content(); ?></div>

							<?php
							/**
							 * wpas_frontend_reply_content_after hook
							 *
							 * @since  3.0.0
							 */
							do_action( 'wpas_frontend_reply_content_after', get_the_ID() ); ?>
						</td>

					</tr>

				<?php endwhile;
			endif;

			wp_reset_query(); ?>
		</tbody>
	</table>

	<h3><?php _e( 'Write a reply', 'wpas' ); ?></h3>

	<?php
	/**
	 * Display the reply form.
	 *
	 * @since 3.0.0
	 */
	wpas_get_reply_form(); ?>

</div>