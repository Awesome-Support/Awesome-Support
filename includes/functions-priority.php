<?php

// add color meta only if support priority active is active 
if( wpas_is_support_priority_active() ) {

	add_action( "ticket_priority_add_form_fields",  'wpas_ticket_priority_add_form_color_field' );
	add_action( "ticket_priority_edit_form_fields", 'wpas_ticket_priority_edit_form_color_field', 10, 2 );

	add_action( 'created_ticket_priority', 'wpas_ticket_priority_save_color', 10, 2 );
	add_action( 'edited_ticket_priority',   'wpas_ticket_priority_save_color', 10, 2 );
	
	add_action( 'load-edit-tags.php', 'wpas_ticket_priority_enqueue' );
	add_action( 'load-terms.php', 'wpas_ticket_priority_enqueue' );

}

/**
 * Enqueue color picker for support priority taxonomy
 * @global string $taxnow
 */
function wpas_ticket_priority_enqueue() {
	global $taxnow;
	
	if( $taxnow == 'ticket_priority' ) {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}
}

/**
 * Priority color add field
 * @param string $taxonomy
 */
function wpas_ticket_priority_add_form_color_field( $taxonomy ) {
	?>

	<div class="form-field term-color-wrap">
		<label for="term-color"><?php echo _e( 'Color', 'awesome-support' ); ?></label>
		<input type="text" name="term-color" id="term-color" value="" />
		<p class="description"><?php echo _e( 'Set priority color.', 'awesome-support' ); ?></p>
	</div>

	<?php
}

/**
 * Priority color edit field
 * @param Object $term
 * @param string $taxonomy
 */
function wpas_ticket_priority_edit_form_color_field( $term, $taxonomy ) {
	
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
 * Save priority color meta
 * @param int $term_id
 * @param int $tt_id
 */
function wpas_ticket_priority_save_color( $term_id, $tt_id ) {
	$term_color = filter_input( INPUT_POST, 'term-color', FILTER_SANITIZE_STRING );
	
	$term_color = sanitize_hex_color( $term_color );
	
	update_term_meta( $term_id, 'color', $term_color );
	
}