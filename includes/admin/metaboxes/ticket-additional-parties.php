<?php
/**
 * Ticket statistics
 *
 * This metabox is used to display additional interested parties
 *
 *
 * @since 3.3.5
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wpas-custom-fields wpas-ticket-addl-parties-mb">
	<?php

	do_action( 'wpas_mb_details_before_ticket_addl_parties' );

	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

	if ( isset( $options['multiple_agents_per_ticket'] ) && true === boolval( $options['multiple_agents_per_ticket'] ) ) {
		
		// Issue warning that these fields are notational only.
		echo _e( 'Note: Email notifications to these agents will be under the name of the PRIMARY agent.', 'awesome-support' );
	
		// get id for additional support agents if they are already on the ticket...
		$secondary_staff_id = wpas_get_cf_value( 'secondary_assignee', get_the_ID() );
		$tertiary_staff_id = wpas_get_cf_value( 'tertiary_assignee', get_the_ID() );

		// Translate ids to names
		$secondary_staff_name = '';
		$tertiary_staff_name = '';
		if ( ! empty( $secondary_staff_id ) ) {
			$secondary_staff = get_user_by( 'ID', $secondary_staff_id );

			if ( ! empty ( $secondary_staff ) )  {
				$secondary_staff_name = $secondary_staff->data->display_name;	
			}
		}
		If ( ! empty( $tertiary_staff_id) ) {
			$tertiary_staff = get_user_by( 'ID', $tertiary_staff_id );

			If ( ! empty( $tertiary_staff) ) {
				$tertiary_staff_name = $tertiary_staff->data->display_name;
			}
		}
	
			// Display dropdown for secondary staff
			?> <label for="wpas-secondary-assignee"><strong data-hint="<?php esc_html_e( 'First additional agent who has an interest this ticket', 'awesome-support' ); ?>" class="hint-left hint-anim"><?php _e( 'Additional Support Staff #1', 'awesome-support' ); ?></strong></label><?php	
			
			if ( wpas_get_option( 'support_staff_select2_enabled', false ) ) {
			
				$staff_atts = array(
					'name'      => 'wpas_secondary_assignee',
					'id'        => 'wpas-secondary-assignee',
					'disabled'  => ! current_user_can( 'assign_ticket' ) ? true : false,
					'select2'   => true,
					'data_attr' => array( 'capability' => 'edit_ticket' )
				);

				echo wpas_dropdown( $staff_atts, "<option value='$secondary_staff_id' selected='selected'>$secondary_staff_name</option>" );	
			} else {
				echo wpas_users_dropdown( array( 
					'cap'		=> 'edit_ticket',
					'orderby'	=> 'display_name',
					'order'		=> 'ASC',
					'name'      => 'wpas_secondary_assignee',
					'id'        => 'wpas-secondary-assignee',
					'class'		=> 'wpas-form-control',
					'please_select' => true,
					'selected' => $secondary_staff_id
				) );
			}
			// Display dropdown for tertiary staff
			?><label for="wpas-tertiary-assignee"><strong data-hint="<?php esc_html_e( 'Second additional agent who has an interest this ticket', 'awesome-support' ); ?>" class="hint-left hint-anim"><?php _e( 'Additional Support Staff #2', 'awesome-support' ); ?></strong></label><?php
			
			if ( wpas_get_option( 'support_staff_select2_enabled', false ) ) {
				$staff_atts = array(
					'name'      => 'wpas_tertiary_assignee',
					'id'        => 'wpas-tertiary-assignee',
					'disabled'  => ! current_user_can( 'assign_ticket' ) ? true : false,
					'select2'   => true,
					'data_attr' => array( 'capability' => 'edit_ticket' )
				);

				echo wpas_dropdown( $staff_atts, "<option value='$tertiary_staff_id' selected='selected'>$tertiary_staff_name</option>" );
			} else {
				echo wpas_users_dropdown( array( 
					'cap'		=> 'edit_ticket',
					'orderby'	=> 'display_name',
					'order'		=> 'ASC',
					'name'      => 'wpas_tertiary_assignee',
					'id'        => 'wpas-tertiary-assignee',
					'class'		=> 'wpas-form-control',
					'please_select' => true,
					'selected' => $tertiary_staff_id
				) );
			}
		
		// Create some space before showing free-form interested parties (name / email )
		?><br /><br /><?php
		?><hr /><?php
	}

	// Show free-form interested parties (name / email )
	?><strong><?php
	if ( isset( $options['show_third_party_fields'] ) && true === boolval( $options['show_third_party_fields'] ) ) {
		echo _e( 'Note: These fields are notational only. They do not participate in notifications!', 'awesome-support' );	
		?></strong><hr /><?php
		WPAS()->custom_fields->display_single_field('first_addl_interested_party_name');
		WPAS()->custom_fields->display_single_field('first_addl_interested_party_email');
		WPAS()->custom_fields->display_single_field('second_addl_interested_party_name');
		WPAS()->custom_fields->display_single_field('second_addl_interested_party_email');
	}
	do_action( 'wpas_mb_details_after_addl_parties' );
	?>
</div>