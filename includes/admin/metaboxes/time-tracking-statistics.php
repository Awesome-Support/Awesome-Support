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

<div class="wpas-custom-fields wpas-time-tracking-statistics-mb">
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

        /*
         * Filter time fields - display minutes integer in hh:mm format
         */
		function wpas_cf_field_markup_time_display_hhmm( $field, $populate ) {

		    if( empty( $populate ) || ! is_numeric( $populate ) ) {
		        return $field;
            }

		    // Change minutes integer to hh:mm for display
            $minutes    = (int) $populate;
			$hhmm       = sprintf( "%02d:%02d", floor( $minutes / 60 ), ( $minutes ) % 60 );

            return str_replace( 'value="' . $populate . '"', 'value="' . $hhmm . '"', $field );

		}

		// Activate time display filter
		add_filter( 'wpas_cf_field_markup', 'wpas_cf_field_markup_time_display_hhmm', 10, 2 );
		WPAS()->custom_fields->display_single_field( 'ttl_calculated_time_spent_on_ticket' );
		WPAS()->custom_fields->display_single_field( 'ttl_adjustments_to_time_spent_on_ticket' );
		WPAS()->custom_fields->display_single_field( 'final_time_spent_on_ticket' );
        // Remove time display filter
		remove_filter('wpas_cf_field_markup', 'wpas_cf_field_markup_time_display_hhmm');

		WPAS()->custom_fields->display_single_field( 'time_adjustments_pos_or_neg' );
		WPAS()->custom_fields->display_single_field( 'time_notes' );

		do_action( 'wpas_mb_details_after_time_tracking_statistics' );
	?>
</div>
