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
	?>
	
	<div class="wpas-row">
		<div class="wpas-col">Replies by agent</div>
		<div class="wpas-col"><?php echo wpas_num_agent_replies( get_the_ID() ); ?></div>
	</div>
	<div class="wpas-row">
		<div class="wpas-col">Replies by customer</div>
		<div class="wpas-col"><?php echo wpas_num_customer_replies( get_the_ID() ); ?></div>
	</div>
	<div class="wpas-row">
		<div class="wpas-col">Total replies</div>
		<div class="wpas-col"><?php echo wpas_num_total_replies( get_the_ID() ); ?></div>
	</div>
	
	<?php
	do_action( 'wpas_mb_details_after_ticket_statistics' );
	?>
</div>