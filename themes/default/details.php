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

/**
 * @var $post WP_Post
 */
global $post;

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
							<strong class="wpas-profilename"><?php echo $author->data->display_name; ?></strong>
						</div>
						<div class="wpas-reply-time">
							<time class="wpas-timestamp" datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . wpas_get_offset_html5(); ?>">
								<span class="wpas-human-date"><?php echo get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post->ID ); ?></span>
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
					echo '<div class="wpas-reply-content">' .  make_clickable( apply_filters( 'the_content', $post->post_content ) ) . '</div>';

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
			$current_page     = isset( $_GET['as-page'] ) ? filter_input( INPUT_GET, 'as-page', FILTER_SANITIZE_NUMBER_INT ) : 1;
			$replies_per_page = wpas_get_option( 'replies_per_page', 10 );

			$args = array(
				'posts_per_page' => $replies_per_page,
				'paged'          => $current_page,
				'no_found_rows'  => false,
			);

			$replies = wpas_get_replies( $post->ID, array( 'read', 'unread' ), $args, 'wp_query' );

			if ( $replies->have_posts() ):

				while ( $replies->have_posts() ):

					$replies->the_post();
					$user      = get_userdata( $post->post_author );
					$user_role = get_the_author_meta( 'roles' );
					$user_role = $user_role[0];
					$time_ago  = human_time_diff( get_the_time( 'U', $post->ID ), current_time( 'timestamp' ) ); ?>

					<tr id="reply-<?php echo the_ID(); ?>"
					    class="wpas-reply-single wpas-status-<?php echo get_post_status(); ?>" valign="top">

						<?php
						/**
						 * Make sure the reply hasn't been deleted.
						 */
						if ( 'trash' === get_post_status() ) { ?>

							<td colspan="2">
								<?php printf( __( 'This reply has been deleted %s ago.', 'wpas' ), $time_ago ); ?>
							</td>

							<?php continue;
						} ?>

						<td style="width: 64px;">
							<div class="wpas-user-profile">
								<?php echo get_avatar( get_the_author_meta( 'user_email' ), 64, get_option( 'avatar_default' ) ); ?>
							</div>
						</td>

						<td>
							<div class="wpas-reply-meta">
								<div class="wpas-reply-user">
									<strong class="wpas-profilename"><?php echo $user->data->display_name; ?></strong>
								</div>
								<div class="wpas-reply-time">
									<time class="wpas-timestamp"
									      datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . wpas_get_offset_html5(); ?>">
										<span
											class="wpas-human-date"><?php echo get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post->ID ); ?></span>
										<span
											class="wpas-date-ago"><?php printf( __( '%s ago', 'wpas' ), $time_ago ); ?></span>
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

	<div class="wpas-pagi">
		<span class="wpas-pagi-prev"><?php wpas_prev_page_link( '< ' . __( 'Older Replies', 'wpas' ) ); ?></span>
		<span class="wpas-pagi-next"><?php wpas_next_page_link( __( 'Newer Replies', 'wpas' ) . ' >', $replies->found_posts ); ?></span>
	</div>

	<h3><?php _e( 'Write a reply', 'wpas' ); ?></h3>

	<?php
	/**
	 * Display the reply form.
	 *
	 * @since 3.0.0
	 */
	wpas_get_reply_form(); ?>

</div>