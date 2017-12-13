<?php
/**
 * Single Ticket Reply.
 *
 * This is a built-in template file. If you need to customize it, please,
 * DO NOT modify this file directly. Instead, copy it to your theme's directory
 * and then modify the code. If you modify this file directly, your changes
 * will be overwritten during next update of the plugin.
 *
 * @package   Awesome Support/Templates/Reply
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2016-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/* Get the user role */
$user_role = $user->roles[0];
?>

<tr id="reply-<?php echo the_ID(); ?>" class="wpas-reply-single wpas-status-<?php echo get_post_status(); ?> wpas_user_<?php echo $user_role; ?>" valign="top">

	<?php
	/**
	 * If the reply has been deleted we display a warning message with the deletion date.
	 */
	if ( 'trash' === get_post_status() ): ?>

		<td colspan="2"><?php printf( esc_html__( 'This reply has been deleted %s ago.', 'awesome-support' ), $time_ago ); ?></td>

	<?php else: ?>

		<td style="width: 64px;">
			<div class="wpas-user-profile">
				<?php echo apply_filters('wpas_fe_template_detail_reply_author_avatar', get_avatar( get_userdata( $user->ID )->user_email, 64, get_option( 'avatar_default' ) ), $post ); ?>
			</div>
		</td>

		<td>
			<div class="wpas-reply-meta">
				<div class="wpas-reply-user">
					<strong class="wpas-profilename"><?php echo apply_filters('wpas_fe_template_detail_reply_display_name', $user->data->display_name, $post ); ?></strong>
				</div>
				<div class="wpas-reply-time">
					<time class="wpas-timestamp" datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . wpas_get_offset_html5(); ?>">
						<span class="wpas-human-date"><?php echo get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post->ID ); ?></span>
						<span class="wpas-date-ago"><?php printf( esc_html_x( '%s ago', 'Time ago (eg. 5 minutes ago)', 'awesome-support' ), $time_ago ); ?></span>
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

	<?php endif; ?>
</tr>