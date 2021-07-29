<?php
/**
 * Ticket parent.
 *
 * This metabox is used to display all parties involved in the ticket resolution.
 *
 * @since 6.0.7
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Add nonce
wp_nonce_field( 'wpas_update_cf', 'wpas_cf', false, true );

?>
<div id="wpas-stakeholders">
	<label for="wpas-parent_id"><strong data-hint="<?php esc_html_e( 'The parent ticket for this ticket', 'awesome-support' ); ?>" class="hint-left hint-anim"><?php _e( 'Parent Ticket', 'awesome-support' ); ?></strong></label>
	<p>
		<?php

		$tickets_dropdown = "";
			
		$tickets_dropdown = wpas_tickets_dropdown( array(
			'name'  => 'parent_id',
			'id'    => 'wpas-parent_id',
			'class' => 'wpas-form-control',
			'please_select' => true,
			'selected' => wp_get_post_parent_id($post)
		) );
		
		echo $tickets_dropdown;
		?>
	</p>
</div>