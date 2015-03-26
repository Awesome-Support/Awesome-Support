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
wp_nonce_field( Awesome_Support_Admin::$nonce_action, Awesome_Support_Admin::$nonce_name, false, true );

/* Issuer metadata */
$issuer = get_userdata( $post->post_author );

/* Issuer ID */
/* Issuer name */
if ($issuer !== false) {
    $issuer_id = $issuer->data->ID;
    $issuer_name = $issuer->data->display_name;
} else {
    $issuer_id = 0;
    $issuer_name = __( 'User was deleted', 'wpas' );
}

/* Issuer tickets link */
$issuer_tickets = admin_url( add_query_arg( array( 'post_type' => 'ticket', 'author' => $issuer_id ), 'edit.php' ) );

/* Prepare the empty users list */
$users = array();

/* Get fields values */
$ccs = wpas_get_cf_value( 'ccs', get_the_ID() );

/* Get ticket assignee */
$assignee = wpas_get_cf_value( 'assignee', get_the_ID() );

/* List available agents */
foreach( $wp_roles->roles as $role => $data ) {

	/* Check if current role can edit tickets */
	if( array_key_exists( 'edit_ticket', $data['capabilities'] ) ) {

		/* Get users with current role */
		$usrs = new WP_User_Query( array( 'role' => $role ) );

		/* Save users in global array */
		$users = array_merge( $users, $usrs->get_results() );
	}
}
?>
<div id="wpas-stakeholders">
	<label for="wpas-issuer"><strong><?php _e( 'Ticket Creator', 'wpas' ); ?></strong></label> 
	<p>

		<?php if ( current_user_can( 'create_ticket' ) ):

			$users_atts = array( 'agent_fallback' => true, 'select2' => true, 'name' => 'post_author_override', 'id' => 'wpas-issuer' );

			if ( isset( $post ) ) {
				$users_atts['selected'] = $post->post_author;
			}

			wpas_support_users_dropdown( $users_atts );

		else: ?>
			<a id="wpas-issuer" href="<?php echo $issuer_tickets; ?>"><?php echo $issuer_name; ?></a></p>
		<?php endif; ?>

	<?php if( WPAS_FIELDS_DESC ): ?><p class="description"><?php printf( __( 'This ticket has been raised by the user hereinabove.', 'wpas' ), '#' ); ?></p><?php endif; ?>
	<hr>

	<label for="wpas-assignee"><strong><?php _e( 'Support Staff', 'wpas' ); ?></strong></label>
	<p>
		<?php
		$staff_atts = array(
			'cap'      => 'edit_ticket',
			'name'     => 'wpas_assignee',
			'id'       => 'wpas-assignee',
			'disabled' => ! current_user_can( 'assign_ticket' ) ? true : false,
			'select2'  => true
		);

		if ( isset( $post ) ) {
			$staff_atts['selected'] = get_post_meta( $post->ID, '_wpas_assignee', true );
		}

		echo wpas_users_dropdown( $staff_atts );
		?>
	</p>
	<?php if( WPAS_FIELDS_DESC ): ?><p class="description"><?php printf( __( 'The above agent is currently responsible for this ticket.', 'wpas' ), '#' ); ?></p><?php endif; ?>
	
	<!-- <hr>

	<label for="wpas-ccs"><strong><?php _e( 'CCs', 'wpas' ); ?></strong></label>
	<p><input type="text" id="wpas-ccs" name="wpas_ccs" value="<?php echo $ccs; ?>" style="width:100%" /></p>
	<?php if( WPAS_FIELDS_DESC ): ?><p class="description"><?php printf( __( 'If you want to send a copy of the e-mails to another person, add the address(es) separated by a comma.', 'wpas' ), '#' ); ?></p><?php endif; ?> -->
</div>