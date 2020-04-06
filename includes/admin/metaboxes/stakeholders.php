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

// Add nonce
wp_nonce_field( 'wpas_update_cf', 'wpas_cf', false, true );

// Set post-dependant values
if ( isset( $post ) && is_a( $post, 'WP_Post' ) && 'auto-draft' !== $post->post_status ) {

	// Client
	$client        = get_userdata( $post->post_author );
	$client_id     = !empty($client) ? $client->ID : 0;
	$client_name   = !empty($client) ? $client->data->display_name : '';
	$client_link   = '';
	$client_option = '';

	if ( $client_id !== 0 && $client_name !== '' ) {
		$client_option = "<option value='$client_id' selected='selected'>$client_name</option>";
		$client_link   = esc_url( admin_url( add_query_arg( array(
			'post_type' => 'ticket',
			'author'    => $client_id
		), 'edit.php' ) ) );
	}

	// Staff
	$staff_id = wpas_get_cf_value( 'assignee', get_the_ID() );

} else {

	// Staff
	$staff_id = get_current_user_id();

	// Client
	$client_id     = 0;
	$client_name   = '';
	$client_link   = '';
	$client_option = '';

}

// Set post-independent vars
$staff         = get_user_by( 'ID', $staff_id );
if (! empty( $staff ) ) {
	$staff_name    = $staff->data->display_name;
}
?>
<div id="wpas-stakeholders">
	<label for="wpas-issuer"><strong data-hint="<?php esc_html_e( 'This user who raised this ticket', 'awesome-support' ); ?>" class="hint-left hint-anim"><?php _e( 'Ticket Creator', 'awesome-support' ); ?></strong></label>
	<p>
		<?php if ( current_user_can( 'create_ticket' ) ):

			$users_atts = array( 
				'agent_fallback' => true, 
				'select2' => true, 
				'name' => 'post_author_override', 
				'id' => 'wpas-issuer', 
				'disabled'  => ! current_user_can( 'assign_ticket_creator' ) && ! wpas_is_asadmin() ? true : false, 
				'data_attr' => array( 'capability' => 'create_ticket' )
			);

			if ( isset( $post ) ) {
				$users_atts['selected'] = $post->post_author;
			}

			echo wpas_dropdown( $users_atts, $client_option );

		else: ?>
			<a id="wpas-issuer" href="<?php echo $client_link; ?>"><?php echo $client_name; ?></a>
		<?php endif; ?>
	</p>
	<label for="wpas-assignee"><strong data-hint="<?php esc_html_e( 'The agent currently responsible for this ticket', 'awesome-support' ); ?>" class="hint-left hint-anim"><?php _e( 'Support Staff', 'awesome-support' ); ?></strong></label>
	<p>
		<?php
		
		if ( wpas_get_option( 'support_staff_select2_enabled', false ) ) {
		
			$staff_atts = array(
				'name'      => 'wpas_assignee',
				'id'        => 'wpas-assignee',
				'disabled'  => ! current_user_can( 'assign_ticket' ) ? true : false,
				'select2'   => true,
				'data_attr' => array( 'capability' => 'edit_ticket' )
			);

			if (! empty( $staff ) ) {
				// We have a valid staff id
				echo wpas_dropdown( $staff_atts, "<option value='$staff_id' selected='selected'>$staff_name</option>" );		
			} else {
				// Oops - no valid staff id...
				echo wpas_dropdown( $staff_atts, "<option value='$staff_id'> " );					
			}
		} else {
			
			
			echo wpas_users_dropdown( array( 
				'cap'	=> 'edit_ticket',
				'orderby' => 'display_name',
				'order' => 'ASC',
				'name'  => 'wpas_assignee',
				'id'    => 'wpas-assignee',
				'class' => 'wpas-form-control',
				'please_select' => true,
				'selected' => $staff_id
			) );
		}
		?>
	</p>
</div>