<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wpas-custom-fields">
	<?php

	do_action( 'wpas_mb_details_before_custom_fields' );

	WPAS()->custom_fields->submission_form_fields();

	

	WPAS()->custom_fields->show_backend_custom_form_fields();

	do_action( 'wpas_mb_details_after_custom_fields' );
	?>
		
	<div class="clear clearfix"></div>
</div>