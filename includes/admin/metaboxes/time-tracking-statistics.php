<?php
	/**
	 * Time Tracking Statistics
	 *
	 * This metabox is used to display misc data about time tracking. *
	 *
	 * @since 3.3.5
	 */

	// If this file is called directly, abort.
	if ( !defined( 'WPINC' ) ) {
		die;
	} ?>

<div class="wpas-time-tracking-statistics-mb">
	<?php

		do_action( 'wpas_mb_details_before_time_tracking_statistics' );

		// Show time fields
	?>
    <b>
        <div class="wpas-time-tracking-statistics-mb-time-display-header">
			<?php
			if ( false === boolval( wpas_get_option( 'allow_agents_to_enter_time', false ) ) ){
				echo __( 'Note: Read-only Configuration - You Are Not Permitted To Edit Time Data' ); 
			 }
			 ?>
        </div>
    </b>

	<?php

		WPAS()->custom_fields->display_single_field( 'ttl_calculated_time_spent_on_ticket' );
		WPAS()->custom_fields->display_single_field( 'ttl_adjustments_to_time_spent_on_ticket' );
		WPAS()->custom_fields->display_single_field( 'time_adjustments_pos_or_neg' );
		WPAS()->custom_fields->display_single_field( 'final_time_spent_on_ticket' );
		WPAS()->custom_fields->display_single_field( 'time_notes' );

		do_action( 'wpas_mb_details_after_time_tracking_statistics' );
	?>
</div>
