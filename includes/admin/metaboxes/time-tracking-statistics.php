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
	WPAS()->custom_fields->display_single_field('ttl_calculated_time_spent_on_ticket');
	WPAS()->custom_fields->display_single_field('ttl_adjustments_to_time_spent_on_ticket');
	WPAS()->custom_fields->display_single_field('final_time_spent_on_ticket');
	

	do_action( 'wpas_mb_details_after_time_tracking_statistics' );
	?>
</div>