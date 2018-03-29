<?php
/**
 * Custom Fields, Backend/Admin Only
 *
 * This metabox is used to display custom fields
 * that are tagged for dispaly only in the admin.
 *
 *
 * @since 3.3.5
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wpas-custom-fields">
	<?php
	
	printf('<h2>%s</h2>', __( 'Admin Only Custom Fields', 'awesome-support' ) );

	do_action( 'wpas_mb_details_before_custom_fields_admin_only' );

	WPAS()->custom_fields->show_backend_custom_form_fields();
	
	do_action( 'wpas_mb_details_after_custom_fields_admin_only' );
	
	echo '<div class="clear clearfix"></div>';
	
	?>
</div>