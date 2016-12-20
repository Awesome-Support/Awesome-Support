<?php
/**
 * Ticket statistics (Misc)
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

	

	do_action( 'wpas_mb_details_after_ticket_statistics' );
	?>
</div>