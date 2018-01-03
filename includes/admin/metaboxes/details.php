<?php
/**
 * Ticket Status.
 *
 * This metabox is used to display the ticket current status
 * and change it in one click.
 *
 * For more details on how the ticket status is changed,
 * @see Awesome_Support_Admin::custom_actions()
 *
 * @since 3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $pagenow, $post;

/* Current status */
$ticket_status = get_post_meta( get_the_ID(), '_wpas_status', true );

/* Status action link - @see admin/class-awesome-support-admin.php */
$action = get_ticket_details_action_link( $post );

/**
 * Get available statuses.
 */
$statuses = wpas_get_post_status();

/* Get post status */
$post_status = isset( $post ) ? $post->post_status : '';

/* Get the date */
$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
$date        = get_the_date( $date_format );

/* Get time */
if ( isset( $post ) ) {
	$dateago = human_time_diff( get_the_time( 'U', $post->ID ), current_time( 'timestamp' ) );
}
?>
<div class="wpas-ticket-status submitbox">
	
	<?php do_action( 'wpas_backend_ticket_status_content_before', $post->ID ); ?>
	
	<div class="wpas-row" id="wpas-statusdate">
		<div class="wpas-col">
			<strong><?php _e( 'Status', 'awesome-support' ); ?></strong>
			<?php if ( 'post-new.php' != $pagenow ):
				wpas_cf_display_status( '', $post->ID );
			?>
			<?php else: ?>
				<span><?php _x( 'Creating...', 'Ticket creation', 'awesome-support' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="wpas-col">
			<?php if ( isset( $post ) ): ?>
				<strong><?php echo $date; ?></strong>
				<em><?php printf( __( '%s ago', 'awesome-support' ), $dateago ); ?></em>
			<?php endif; ?>
		</div>
	</div>
	<?php do_action( 'wpas_backend_ticket_stakeholders_before', $post->ID ); ?>
	<?php require( WPAS_PATH . 'includes/admin/metaboxes/stakeholders.php' ); ?>
	<?php if ( 'open' === get_post_meta( $post->ID, '_wpas_status', true ) ): ?>
		<label for="wpas-post-status"><strong><?php _e( 'Current Status', 'awesome-support' ); ?></strong></label>
		<p>
			<select id="wpas-post-status" name="post_status_override" style="width: 100%">
				<?php foreach ( $statuses as $status => $label ):
					$selected = ( $post_status === $status ) ? 'selected="selected"' : '';
					if ( 'auto-draft' === $post_status && 'processing' === $status ) { $selected = 'selected="selected"'; } ?>
					<option value="<?php echo $status; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( isset( $_GET['post'] ) ): ?>
				<input type="hidden" name="wpas_post_parent" value="<?php echo filter_input(INPUT_GET, 'post', FILTER_SANITIZE_STRING); ?>">
			<?php endif; ?>
		</p>
	<?php endif; ?>
		
	<?php do_action( 'wpas_backend_ticket_status_before_actions', $post->ID ); ?>
	<div id="major-publishing-actions">
		<?php if ( current_user_can( "delete_ticket", $post->ID ) ): ?>
			<div id="delete-action">
				<a class="submitdelete deletion" href="<?php echo $action; ?>">
					<?php
					if ( 'closed' === $ticket_status ) {
						_e( 'Re-open', 'awesome-support' );
					} elseif( '' === $ticket_status ) {
						_e( 'Open', 'awesome-support' );
					} else {
						_e( 'Close', 'awesome-support' );
					}
					?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ( current_user_can( 'edit_ticket' ) ): ?>
			<div id="publishing-action">
				<span class="spinner"></span>
				<?php if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) : ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Updating', 'awesome-support' ) ?>" />
					<?php submit_button( __( 'Update Ticket', 'awesome-support' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'u' ) ); ?>
				<?php else:
					if ( current_user_can( 'create_ticket' ) ): ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Creating', 'awesome-support' ) ?>" />
						<?php submit_button( __( 'Open Ticket', 'awesome-support' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'o' ) ); ?>
						<?php endif;
				endif; ?>
			</div>
		<?php endif; ?>
		<div class="clear"></div>
	</div>
</div>

