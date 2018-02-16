<?php
/**
 * @package   Awesome Support/Admin/Reply
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<td class="col1" style="width: 64px;">

	<?php
	/* Display avatar only for replies */
	if( 'ticket_reply' == $row->post_type ) {

		echo $user_avatar;

		/**
		 * Triggers an action right under the user avatar for ticket replies.
		 *
		 * @since 3.2.6
		 *
		 * @param int $row->ID The current reply ID
		 * @param int $user_id The reply author user ID
		 */
		do_action( 'wpas_mb_replies_under_avatar', $row->ID, $user_id );

	}
	?>

</td>
<td class="col2">

	<?php if ( 'unread' === $row->post_status ): ?><div id="wpas-unread-<?php echo $row->ID; ?>" class="wpas-unread-badge"><?php _e( 'Unread', 'awesome-support' ); ?></div><?php endif; ?>
	<?php $show_extended_date_in_replies = boolval( wpas_get_option( 'show_extended_date_in_replies', false ) ); ?>
	<div class="wpas-reply-meta">
		<div class="wpas-reply-user">
			<strong class="wpas-profilename"><?php echo $user_name; ?></strong><?php if ( $user_data ): ?> <span class="wpas-profilerole">(<?php echo wpas_get_user_nice_role( $user_data->roles[0] ); ?>)</span><?php endif; ?>
		</div>
		<div class="wpas-reply-time">
			<time class="wpas-timestamp" datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . wpas_get_offset_html5(); ?>"><span class="wpas-human-date"><?php echo date( get_option( 'date_format' ), strtotime( $row->post_date ) ); ?> <?php if ( true === $show_extended_date_in_replies ) { printf( __( '(%s - %s since ticket was opened.)', 'awesome-support' ), $date_full, $days_since_open ); } ?>  | </span><?php printf( __( '%s ago', 'awesome-support' ), $date ); ?></time>
		</div>
	</div>

	<div class="wpas-ticket-controls">
		<?php

		$ticket_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		/**
		 * Fires before the ticket reply controls (mark as read, delete, edit...) are displayed
		 *
		 * @since 3.2.6
		 *
		 * @param int     $ticket_id ID of the current ticket
		 * @param WP_Post $row       Current reply post object
		 */
		do_action( 'wpas_ticket_reply_controls_before', $ticket_id, $row );

		/**
		 * Ticket reply controls
		 *
		 * @since 3.2.6
		 */
		$controls = apply_filters( 'wpas_ticket_reply_controls', array(), $ticket_id, $row );

		if ( ! empty( $controls ) ) {

			$output = array();

			foreach ( $controls as $control_id => $control ) {
				array_push( $output, $control );
			}

			echo implode( ' ', $output );
		}

		/**
		 * Fires after the ticket reply controls (mark as read, delete, edit...) are displayed
		 *
		 * @since 3.2.6
		 *
		 * @param int     $ticket_id ID of the current ticket
		 * @param WP_Post $row       Current reply post object
		 */
		do_action( 'wpas_ticket_reply_controls_after', $ticket_id, $row );
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

	echo wp_kses( $content, wp_kses_allowed_html( 'post' ) );

	/**
	 * wpas_backend_reply_content_after hook
	 *
	 * @since  3.0.0
	 */
	do_action( 'wpas_backend_reply_content_after', $row->ID );

	echo '</div>';
	?>
</td>
