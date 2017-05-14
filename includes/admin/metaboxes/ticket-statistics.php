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

<div class="wpas-custom-fields wpas-ticket-statistics-mb">
	<?php
	do_action( 'wpas_mb_details_before_ticket_statistics' );
	?>
	
	<div class="wpas-row">
        <?php WPAS()->custom_fields->display_single_field( 'ttl_replies_by_agent' ); ?>
	</div>
	<div class="wpas-row">
        <?php WPAS()->custom_fields->display_single_field( 'ttl_replies_by_customer' ); ?>
	</div>
	<div class="wpas-row">
        <?php WPAS()->custom_fields->display_single_field( 'ttl_replies' ); ?>
	</div>
	
	<?php
	do_action( 'wpas_mb_details_after_ticket_statistics' );
	?>
</div>