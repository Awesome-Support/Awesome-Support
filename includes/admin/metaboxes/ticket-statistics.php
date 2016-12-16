<?php
/**
 * Ticket statistics
 *
 * This metabox is used to display misc data about the ticket include
 * number of replies for agents etc.
 *
 *
 * @since 3.3.5
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wpas-ticket-statistics-mb">
	<?php

	do_action( 'wpas_mb_details_before_ticket_statistics' );


	// This code doesn't work - what the hell?  It shouldn't be this hard to echo out a custom field!
	$ticket_priority = wpas_get_cf_value( 'ticket_priority', get_the_ID(), 'NO VALUE RETURNED' );
	echo $ticket_priority;
	
	// Show time fields
	?> 
	<em>	
	<div class="wpas-ticket-statistics-mb-time-display-header">		
	<?php 
	echo __('Time Related Fields') ;
	?> 	
	</div>  
	</em>
	
	<?php
	//$ttl_calc_time_spent_on_ticket = wpas_get_cf_value( 'ttl_calculated_time_spent_on_ticket', get_the_ID() );
	//echo $ttl_calc_time_spent_on_ticket;
	
	WPAS()->custom_fields->display_single_field('ttl_calculated_time_spent_on_ticket');
	WPAS()->custom_fields->display_single_field('ttl_adjustments_to_time_spent_on_ticket');
	WPAS()->custom_fields->display_single_field('final_time_spent_on_ticket');
	

	do_action( 'wpas_mb_details_after_ticket_statistics' );
	?>
</div>