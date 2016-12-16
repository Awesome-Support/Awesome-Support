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

<div class="wpas-ticket-addl-parties-mb">
	<?php

	do_action( 'wpas_mb_details_before_ticket_addl_parties' );


	// WPAS()->custom_fields->display_single_field('secondary_assignee');
	// WPAS()->custom_fields->display_single_field('tertiary_assignee');

	// Issue warning that these fields are notational only.
	echo _e( 'Note: These fields are notational only. They do not participate in notifications nor do these agents see this ticket in their ticket lists.', 'awesome-support' );
	
	// get id for additional support agents if they are already on the ticket...
	$secondary_staff_id = wpas_get_cf_value( 'secondary_assignee', get_the_ID() );
	$tertiary_staff_id = wpas_get_cf_value( 'tertiary_assignee', get_the_ID() );
	
	// Translate ids to names
	$secondary_staff		= get_user_by( 'ID', $secondary_staff_id );
	$secondary_staff_name	= $secondary_staff->data->display_name;	
	$tertiary_staff         = get_user_by( 'ID', $tertiary_staff_id );
	$tertiary_staff_name    = $tertiary_staff->data->display_name;
	
	// Display dropdown for secondary staff
	?> <label for="wpas-secondary-assignee"><strong data-hint="<?php esc_html_e( 'First additional agent who has an interest this ticket', 'awesome-support' ); ?>" class="hint-left hint-anim"><?php _e( 'Additional Support Staff #1', 'awesome-support' ); ?></strong></label><?php	
	$staff_atts = array(
		'name'      => 'secondary_assignee',
		'id'        => 'wpas-secondary-assignee',
		'disabled'  => ! current_user_can( 'assign_ticket' ) ? true : false,
		'select2'   => true,
		'data_attr' => array( 'capability' => 'edit_ticket' )
	);

	echo wpas_dropdown( $staff_atts, "<option value='$secondary_staff_id' selected='selected'>$secondary_staff_name</option>" );	

	// Display dropdown for tertiary staff
	?><label for="wpas-tertiary-assignee"><strong data-hint="<?php esc_html_e( 'Second additional agent who has an interest this ticket', 'awesome-support' ); ?>" class="hint-left hint-anim"><?php _e( 'Additional Support Staff #2', 'awesome-support' ); ?></strong></label><?php
	$staff_atts = array(
		'name'      => 'tertiary_assignee',
		'id'        => 'wpas-tertiary-assignee',
		'disabled'  => ! current_user_can( 'assign_ticket' ) ? true : false,
		'select2'   => true,
		'data_attr' => array( 'capability' => 'edit_ticket' )
	);

	echo wpas_dropdown( $staff_atts, "<option value='$tertiary_staff_id' selected='selected'>$tertiary_staff_name</option>" );	
	
	
	// Show free-form interested parties (name / email )
	?><br /><br /><?php
	?><hr /><strong><?php
	echo _e( 'Note: These fields are notational only. They do not participate in notifications!', 'awesome-support' );	
	?></strong><hr /><?php
	WPAS()->custom_fields->display_single_field('first_addl_interested_party_name');
	WPAS()->custom_fields->display_single_field('first_addl_interested_party_email');
	WPAS()->custom_fields->display_single_field('second_addl_interested_party_name');
	WPAS()->custom_fields->display_single_field('second_addl_interested_party_email');

	do_action( 'wpas_mb_details_after_addl_parties' );
	?>
</div>