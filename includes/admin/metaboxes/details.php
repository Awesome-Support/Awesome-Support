<?php
/**
 * Ticket Status.
 *
 * This metabox is used to display the ticket current status
 * and change it in one click.
 *
 * For more details on how the ticket status is changed,
 * @see Mumei_Ayuda_Support_Admin::custom_actions()
 *
 * @since 3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $pagenow, $post;

/* Current status */
$ticket_status = get_post_meta( get_the_ID(), '_mumei_ayuda_status', true );

/* Status action link - @see admin/class-awesome-support-admin.php */
$action = get_ticket_details_action_link( $post );

/**
 * Get available statuses.
 */
$statuses = mumei_ayuda_get_post_status();

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

	<?php do_action( 'mumei_ayuda_backend_ticket_status_content_before', $post->ID ); ?>

	<div class="wpas-row" id="wpas-statusdate">
		<div class="wpas-col">
			<strong><?php esc_html_e( 'Status', 'ayuda-help-desk' ); ?></strong>
			<?php if ( 'post-new.php' != $pagenow ):
				mumei_ayuda_cf_display_status( '', $post->ID );
			?>
			<?php else: ?>				
				<span><?php echo _x( 'Creating...', 'Ticket creation', 'ayuda-help-desk' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="wpas-col">
			<?php if ( isset( $post ) ): ?>
				<strong><?php echo esc_html( $date ); ?></strong>
				<?php  
					// translators: %sis the date ago.
					$x_content = __( '%s ago', 'ayuda-help-desk' ); 
				?>
				<em><?php printf( esc_html($x_content), esc_html( $dateago ) ); ?></em>
			<?php endif; ?>
		</div>

	</div>
	<?php do_action( 'mumei_ayuda_backend_ticket_stakeholders_before', $post->ID ); ?>
	<?php require( MUMEI_AYUDA_PATH . 'includes/admin/metaboxes/stakeholders.php' ); ?>
	<?php if ( 'open' === get_post_meta( $post->ID, '_mumei_ayuda_status', true ) ): ?>
		<label for="wpas-post-status"><strong><?php esc_html_e( 'Current Status', 'ayuda-help-desk' ); ?></strong></label>
		<p>
			<select id="wpas-post-status" name="post_status_override" style="width: 100%">
				<?php foreach ( $statuses as $status => $label ):
					$selected = ( $post_status === $status ) ? 'selected="selected"' : '';
					if ( 'auto-draft' === $post_status && 'processing' === $status ) { $selected = 'selected="selected"'; } ?>
					<option value="<?php echo esc_attr( $status ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( isset( $_GET['post'] ) ): ?>
				<input type="hidden" name="mumei_ayuda_post_parent" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ); ?>">
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php do_action( 'mumei_ayuda_backend_ticket_status_before_actions', $post->ID ); ?>
	<div id="major-publishing-actions">
		<?php if ( current_user_can( "close_ticket", $post->ID ) ): ?>
			<div id="delete-action">
				<a class="submitdelete deletion" href="<?php echo esc_attr( $action ); ?>">
					<?php
					if ( 'closed' === $ticket_status ) {
						esc_html_e( 'Re-open', 'ayuda-help-desk' );
					} elseif( '' === $ticket_status ) {
						esc_html_e( 'Open', 'ayuda-help-desk' );
					} else {
						esc_html_e( 'Close', 'ayuda-help-desk' );
					}
					?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ( current_user_can( 'edit_ticket' ) ): ?>
			<div id="publishing-action">
				<span class="spinner"></span>
				<?php if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) : ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Updating', 'ayuda-help-desk' ) ?>" />
					<?php submit_button( __( 'Update Ticket', 'ayuda-help-desk' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'u' ) ); ?>
				<?php else:
					if ( current_user_can( 'create_ticket' ) ): ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Creating', 'ayuda-help-desk' ) ?>" />
						<?php submit_button( __( 'Open Ticket', 'ayuda-help-desk' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'o' ) ); ?>
						<?php endif;
				endif; ?>
			</div>
		<?php endif; ?>
		<div class="clear"></div>
	</div>

	<?php do_action( 'mumei_ayuda_backend_ticket_status_after_actions', $post->ID ); ?>

</div>

