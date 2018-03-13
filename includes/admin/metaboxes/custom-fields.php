<?php
/**
 * Custom Fields.
 *
 * This metabox is used to display custom fields.
 * Generally it displays custom fields that are only shown on the front end as well.
 *
 * @since 3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wpas-custom-fields">
	<?php

	printf('<h2>%s</h2>', __( 'Custom Fields', 'awesome-support' ) );
	
	do_action( 'wpas_mb_details_before_custom_fields' );

	WPAS()->custom_fields->submission_form_fields();

	do_action( 'wpas_mb_details_after_custom_fields' );
	
	echo '<div class="clear clearfix"></div>';
	?>
</div>