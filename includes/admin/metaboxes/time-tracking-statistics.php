<?php
/**
 * Time Tracking Statistics
 *
 * This metabox is used to display misc data about time tracking. *
 *
 * @since 3.3.5
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wpas-time-tracking-statistics-mb">
	<?php

	do_action( 'wpas_mb_details_before_time_tracking_statistics' );
	
	// Get option settings related to these fields
	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );
	
	$allow_agents_to_enter_time = false;

	$allow_agents_to_enter_time = ( isset( $options['allow_agents_to_enter_time'] ) && true === boolval( $options['show_basic_time_tracking_fields'] ) ) ;
	

	// Show time fields
	?> 
	<em>	
	<div class="wpas-time-tracking-statistics-mb-time-display-header">		
	<?php 
	echo __('Time Summary') ;
	?> 	
	</div>  
	</em>	
	
	<?php
	If ( true === $allow_agents_to_enter_time ) {
		// Show fields and allow them to be edited
		WPAS()->custom_fields->display_single_field('ttl_calculated_time_spent_on_ticket');
		WPAS()->custom_fields->display_single_field('ttl_adjustments_to_time_spent_on_ticket');
		WPAS()->custom_fields->display_single_field('time_adjustments_pos_or_neg');		
		WPAS()->custom_fields->display_single_field('final_time_spent_on_ticket');
	}
	
	If ( false === $allow_agents_to_enter_time ) {	
		// Show fields read-only!
		echo __('Time Summary Read Only') ;
	}
	
	
	

	do_action( 'wpas_mb_details_after_time_tracking_statistics' );
	?>
</div>