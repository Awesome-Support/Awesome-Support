<?php
/**
 * Ticket Status.
 *
 * This metabox is used to display the ticket current status
 * and change it in one click.
 *
 * For more details on how the ticket status is changed,
 *
 * @see   Awesome_Support_Admin::custom_actions()
 *
 * @since 3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wpas-custom-fields">
	<?php

	do_action( 'wpas_mb_details_before_custom_fields' );

	WPAS()->custom_fields->submission_form_fields();

	do_action( 'wpas_mb_details_after_custom_fields' );
	?>
</div>