<?php

// add color meta only if support type is active
if( wpas_is_support_ticket_type_active() ) {

	add_action( "ticket_type_add_form_fields",  'wpas_ticket_type_add_form_color_field' );
	add_action( "ticket_type_edit_form_fields", 'wpas_ticket_type_edit_form_color_field', 10, 2 );

	add_action( 'created_ticket_type', 'wpas_ticket_type_save_color', 10, 2 );
	add_action( 'edited_ticket_type',   'wpas_ticket_type_save_color', 10, 2 );
	
	add_action( 'load-edit-tags.php', 'wpas_ticket_type_enqueue' );
	add_action( 'load-terms.php', 'wpas_ticket_type_enqueue' );

}

/**
 * Enqueue color picker for ticket type taxonomy
 * @global string $taxnow
 */
function wpas_ticket_type_enqueue() {
	global $taxnow;
	
	if( $taxnow == 'ticket_type' ) {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}
}

/**
 * Ticket type color add field
 * @param string $taxonomy
 */
function wpas_ticket_type_add_form_color_field( $taxonomy ) {
	?>

	<div class="form-field term-color-wrap">
		<label for="term-color"><?php echo _e( 'Color', 'awesome-support' ); ?></label>
		<input type="text" name="term-color" id="term-color" value="" />
		<p class="description"><?php echo _e( 'Set ticket type color.', 'awesome-support' ); ?></p>
	</div>

	<?php
}

/**
 * Ticket type color edit field
 * @param Object $term
 * @param string $taxonomy
 */
function wpas_ticket_type_edit_form_color_field( $term, $taxonomy ) {
	
	$color = get_term_meta( $term->term_id, 'color', true );
	?>

	<tr class="form-field term-color-wrap">
		<th scope="row" valign="top">
			<label for="term-color"><?php echo _e( 'Color', 'awesome-support' ); ?></label>
		</th>
		<td>
			<input type="text" name="term-color" id="term-color" value="<?php echo esc_attr( $color ); ?>" />
			<p class="description"><?php echo _e( 'Set priority color.', 'awesome-support' ); ?></p>
		</td>
	</tr>

	<?php
}

/**
 * Save ticket type color meta
 * @param int $term_id
 * @param int $tt_id
 */
function wpas_ticket_type_save_color( $term_id, $tt_id ) {
	$term_color = filter_input( INPUT_POST, 'term-color', FILTER_SANITIZE_STRING );
	
	$term_color = sanitize_hex_color( $term_color );
	
	update_term_meta( $term_id, 'color', $term_color );
	
}

/**
 * List of default ticket types
 * @return array
 */
function wpas_default_ticket_types() {
	$ticket_types = array(
		'Refund Request',
		'Service Request',
		'Get Personal Data Request',
		'Delete Personal Data Request',
		'Bug Report',
		'Sales Question',
		'Pre-sales Question',
		'Technical Issue',
		'Order Related Question',
		'Shipping Inquiry',
		'Delivery Inquiry',
		'Product Availability Question'
	);
	
	return apply_filters( 'wpas_default_ticket_types', $ticket_types );
}

/**
 * add ticket types
 * @param boolean $reset
 * @return boolean
 */
function wpas_add_default_ticket_types($reset = false) {
	
	if (!$reset) {
		
		$added_before = boolval( get_option( 'wpas_default_ticket_types_added', false ) );
		
		if ( true ===  $added_before) {
			return;
		}
	}
	
	if ( true === taxonomy_exists('ticket_type') ) {
		
		$ticket_types = wpas_default_ticket_types();
		
		foreach($ticket_types as $ticket_type) {
			wp_insert_term($ticket_type, 'ticket_type');
		}
		
		update_option('wpas_default_ticket_types_added', true);
		
	}
	return true;
	
}


add_action( 'tf_admin_options_saved_wpas', 'wpas_add_default_ticket_types' );