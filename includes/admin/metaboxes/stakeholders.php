<?php
/**
 * Ticket Stakeholders.
 *
 * This metabox is used to display all parties involved in the ticket resolution.
 *
 * @since 3.0.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/* Need access to the roles */
global $wp_roles;

/* Add nonce */
wp_nonce_field( 'wpas_update_cf', 'wpas_cf', false, true );

/* Issuer metadata */
$issuer = get_userdata( $post->post_author );

/* Issuer ID */
/* Issuer name */
if ($issuer !== false) {
    $issuer_id = $issuer->data->ID;
    $issuer_name = $issuer->data->display_name;
} else {
    $issuer_id = 0;
    $issuer_name = __( 'User was deleted', 'awesome-support' );
}

/* Issuer tickets link */
$issuer_tickets = admin_url( add_query_arg( array( 'post_type' => 'ticket', 'author' => $issuer_id ), 'edit.php' ) );

/* Get fields values */
$ccs = wpas_get_cf_value( 'ccs', get_the_ID() );

/* Get ticket assignee */
$assignee = wpas_get_cf_value( 'assignee', get_the_ID() );
?>
<div id="wpas-stakeholders">
	<label for="wpas-issuer"><strong><?php _e( 'Ticket Creator', 'awesome-support' ); ?></strong></label>
	<p>

		<?php if ( current_user_can( 'create_ticket' ) ):

			$users_atts = array( 'agent_fallback' => true, 'select2' => true, 'name' => 'post_author_override', 'id' => 'wpas-issuer', 'data_attr' => array( 'capability' => 'create_ticket' ) );

			if ( isset( $post ) ) {
				$users_atts['selected'] = $post->post_author;
			}

			echo wpas_dropdown( $users_atts, '' );

		else: ?>
			<a id="wpas-issuer" href="<?php echo $issuer_tickets; ?>"><?php echo $issuer_name; ?></a></p>
		<?php endif; ?>

	<p class="description"><?php printf( __( 'This ticket has been raised by the user hereinabove.', 'awesome-support' ), '#' ); ?></p>
	<hr>

	<label for="wpas-assignee"><strong><?php _e( 'Support Staff', 'awesome-support' ); ?></strong></label>
	<p>
		<?php
		$staff_atts = array(
			'name'      => 'wpas_assignee',
			'id'        => 'wpas-assignee',
			'disabled'  => ! current_user_can( 'assign_ticket' ) ? true : false,
			'select2'   => true,
			'data_attr' => array( 'capability' => 'edit_ticket' )
		);

		if ( isset( $post ) ) {
			$staff_atts['selected'] = get_post_meta( $post->ID, '_wpas_assignee', true );
		}

		echo wpas_dropdown( $staff_atts, '' );
		?>
	</p>
	<p class="description"><?php printf( __( 'The above agent is currently responsible for this ticket.', 'awesome-support' ), '#' ); ?></p>

</div>