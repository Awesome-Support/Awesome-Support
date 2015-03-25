<?php
/**
 * Display custom fields.
 *
 * @package   Awesome Support/Custom Fields
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 * @since     3.0.0
 */
class WPAS_Custom_Fields_Display extends WPAS_Custom_Fields {

	/**
	 * Get all the registered custom fields and display them
	 * on the ticket submission form on the front-end.
	 *
	 * @since  3.0.0
	 */
	public static function submission_form_fields() {

		/* Get all the registered fields from the $wpas_cf object */
		global $wpas_cf;

		$fields = $wpas_cf->get_custom_fields();

		if ( !empty( $fields ) ) {

			foreach ( $fields as $name => $field ) {

				/* Do not display core fields */
				if ( true === $field['args']['core'] ) {
					continue;
				}

				$title    = !empty( $field['args']['title'] ) ? $field['args']['title'] : wpas_get_title_from_id( $name );
				$callback = !empty( $field['args']['callback'] ) ? $field['args']['callback'] : 'text';

				/* Check for a custom function */
				if ( function_exists( $callback ) ) {
					call_user_func( $callback, $field );
				}

				/* Check for a matching method in the custom fields display class */
				elseif ( method_exists( 'WPAS_Custom_Fields_Display', $callback ) ) {
					call_user_func( array( 'WPAS_Custom_Fields_Display', $callback ), $field );
				}

				/* Fallback on a standard text field */
				else {
					WPAS_Custom_Fields_Display::text( $field );
				}
			}

		}

	}
	
	/**
	 * Text field.
	 */
	public static function text( $field ) {

		if ( isset( $post ) ) {
			$post_id = $post->ID;
		} elseif ( isset( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
		} else {
			$post_id = false;
		}

		$field_id    = 'wpas_' . $field['name'];
		$value       = wpas_get_cf_value( $field_id, $post_id );
		$label       = wpas_get_field_title( $field );
		$field_class = isset( $field['args']['field_class'] ) ? $field['args']['field_class'] : ''; ?>

		<div <?php wpas_get_field_container_class( $field_id ); ?> id="<?php echo $field_id; ?>_container">
			
			<label for="<?php echo $field_id; ?>"><strong><?php echo $label; ?></strong></label>

			<?php if ( !is_admin() || current_user_can( $field['args']['capability'] ) ): ?>
				<input type="text" id="<?php echo $field_id; ?>" <?php wpas_get_field_class( $field_id, $field_class ); ?> name="<?php echo $field_id; ?>" value="<?php echo $value; ?>" <?php if ( $field['args']['placeholder'] !== '' ): ?>placeholder="<?php echo $field['args']['placeholder'];?>"<?php endif; ?> <?php if ( true === $field['args']['required'] ): ?>required<?php endif; ?>>
			<?php else: ?>
				<p id="<?php echo $field_id; ?>"><?php echo $value; ?></p>
			<?php endif;

			if( isset( $field['args']['desc'] ) && '' != $field['args']['desc'] && WPAS_FIELDS_DESC ): ?><p class="<?php echo is_admin() ? 'description' : 'wpas-help-block'; ?>"><?php echo wp_kses_post( $field['args']['desc'] ); ?></p><?php endif; ?>
		</div>

	<?php }

	/**
	 * URL field.
	 */
	public static function url( $field ) {

		if ( isset( $post ) ) {
			$post_id = $post->ID;
		} elseif ( isset( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
		} else {
			$post_id = false;
		}

		$field_id    = 'wpas_' . $field['name'];
		$value       = wpas_get_cf_value( $field_id, $post_id );
		$label       = wpas_get_field_title( $field );
		$field_class = isset( $field['args']['field_class'] ) ? $field['args']['field_class'] : ''; ?>

		<div <?php wpas_get_field_container_class( $field_id ); ?> id="<?php echo $field_id; ?>_container">
			
			<label for="<?php echo $field_id; ?>"><strong><?php echo $label; ?></strong></label>

			<?php if ( !is_admin() || current_user_can( $field['args']['capability'] ) ): ?>
				<input type="url" id="<?php echo $field_id; ?>" <?php wpas_get_field_class( $field_id, $field_class ); ?> name="<?php echo $field_id; ?>" value="<?php echo $value; ?>" <?php if ( $field['args']['placeholder'] !== '' ): ?>placeholder="<?php echo $field['args']['placeholder'];?>"<?php endif; ?> <?php if ( true === $field['args']['required'] ): ?>required<?php endif; ?>>
			<?php else: ?>
				<p id="<?php echo $field_id; ?>"><?php echo $value; ?></p>
			<?php endif;

			if( isset( $field['args']['desc'] ) && '' != $field['args']['desc'] && WPAS_FIELDS_DESC ): ?><p class="<?php echo is_admin() ? 'description' : 'wpas-help-block'; ?>"><?php echo wp_kses_post( $field['args']['desc'] ); ?></p><?php endif; ?>
		</div>

	<?php }

	/**
	 * Textarea field.
	 */
	public static function textarea( $field ) {

		if ( isset( $post ) ) {
			$post_id = $post->ID;
		} elseif ( isset( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
		} else {
			$post_id = false;
		}

		$field_id    = 'wpas_' . $field['name'];
		$value       = wpas_get_cf_value( $field_id, $post_id );
		$label       = wpas_get_field_title( $field );
		$field_class = isset( $field['args']['field_class'] ) ? $field['args']['field_class'] : ''; ?>

		<div <?php wpas_get_field_container_class( $field_id ); ?> id="<?php echo $field_id; ?>_container">
			
			<label for="<?php echo $field_id; ?>"><strong><?php echo $label; ?></strong></label>

			<?php if ( !is_admin() || current_user_can( $field['args']['capability'] ) ): ?>
				<textarea id="<?php echo $field_id; ?>" <?php wpas_get_field_class( $field_id, $field_class ); ?> name="<?php echo $field_id; ?>" <?php if ( $field['args']['placeholder'] !== '' ): ?>placeholder="<?php echo $field['args']['placeholder'];?>"<?php endif; ?> <?php if ( true === $field['args']['required'] ): ?>required<?php endif; ?>><?php echo $value; ?></textarea>
			<?php else: ?>
				<p id="<?php echo $field_id; ?>"><?php echo $value; ?></p>
			<?php endif;

			if( isset( $field['args']['desc'] ) && '' != $field['args']['desc'] && WPAS_FIELDS_DESC ): ?><p class="<?php echo is_admin() ? 'description' : 'wpas-help-block'; ?>"><?php echo wp_kses_post( $field['args']['desc'] ); ?></p><?php endif; ?>
		</div>

	<?php }

	/**
	 * "Fake" taxonomy select.
	 * 
	 * @param  array $field Field options
	 * @since  3.0.0
	 */
	public static function taxonomy( $field ) {

		global $post;

		$field_id      = 'wpas_' . $field['name'];
		$label         = wpas_get_field_title( $field );
		$current       = get_the_terms( $post->ID, sanitize_text_field( $field['name'] ) );
		$terms         = get_terms( sanitize_text_field( $field['name'] ), array( 'hide_empty' => 0 ) );
		$value         = '';
		$ordered_terms = array();

		if ( is_array( $current ) ) {
		
			foreach ( $current as $term ) {
				$value = $term->slug;
			}

		}

		/* In case the taxonomy does not exist */
		if ( is_wp_error( $terms ) ) {
			return;
		}

		/**
		 * Re-order the terms hierarchically.
		 */
		wpas_sort_terms_hierarchicaly( $terms, $ordered_terms );
		?>

		<div <?php wpas_get_field_container_class( $field_id ); ?> id="<?php echo $field_id; ?>_container">
			<label for="<?php echo $field_id; ?>"><strong><?php echo $label; ?></strong></label>

			<?php if ( !is_admin() || current_user_can( $field['args']['capability'] ) ): ?>

				<select name="<?php echo $field_id; ?>" id="<?php echo $field_id; ?>" <?php wpas_get_field_class( $field_id ); ?>>
					<option value=""><?php _e( 'Please select', 'wpas' ); ?></option>

					<?php
					foreach ( $ordered_terms as $term ) {
						wpas_hierarchical_taxonomy_dropdown_options( $term, $value );
					} ?>

				</select>

			<?php else: ?>
				<p id="<?php echo $field_id; ?>"><?php echo $value; ?></p>
			<?php endif;

			if( isset( $field['args']['desc'] ) && '' != $field['args']['desc'] && WPAS_FIELDS_DESC ): ?><p class="<?php echo is_admin() ? 'description' : 'wpas-help-block'; ?>"><?php echo wp_kses( $field['args']['desc'] ); ?></p><?php endif; ?>
		</div>

	<?php }

}

/**
 * Display the post status.
 *
 * Gets the ticket status and formats it according to the plugin settings.
 *
 * @since  3.0.0
 * @param  string $name     Field name
 * @param  integer $post_id ID of the post being processed
 * @return string           Formatted ticket status
 */
function wpas_cf_display_status( $name, $post_id ) {

	$status = wpas_get_ticket_status( $post_id );

	if ( 'closed' === $status ) {
		$label  = __( 'Closed', 'wpas' );
		$color  = wpas_get_option( "color_$status", '#dd3333' );
		$tag    = "<span class='wpas-label' style='background-color:$color;'>$label</span>";
	} else {

		$post          = get_post( $post_id );
		$post_status   = $post->post_status;
		$custom_status = wpas_get_post_status();

		if ( !array_key_exists( $post_status, $custom_status ) ) {
			$label  = __( 'Open', 'wpas' );
			$color  = wpas_get_option( "color_$status", '#169baa' );
			$tag    = "<span class='wpas-label' style='background-color:$color;'>$label</span>";
		} else {
			$defaults = array(
				'queued'     => '#1e73be',
				'processing' => '#a01497',
				'hold'       => '#b56629'
			);
			$label = $custom_status[$post_status];
			$color = wpas_get_option( "color_$post_status", false );

			if ( false === $color ) {
				if ( isset( $defaults[$post_status] ) ) {
					$color = $defaults[$post_status];
				} else {
					$color = '#169baa';
				}
			}

			$tag = "<span class='wpas-label' style='background-color:$color;'>$label</span>";
		}
	}

	echo $tag;

}

/**
 * Recursively sort an array of taxonomy terms hierarchically. Child categories will be
 * placed under a 'children' member of their parent term.
 *
 * @since  3.0.1
 * @param Array   $cats     taxonomy term objects to sort
 * @param Array   $into     result array to put them in
 * @param integer $parentId the current parent ID to put them in
 * @link  http://wordpress.stackexchange.com/a/99516/16176
 */
function wpas_sort_terms_hierarchicaly( Array &$cats, Array &$into, $parentId = 0 ) {

	foreach ($cats as $i => $cat) {
		if ($cat->parent == $parentId) {
			$into[$cat->term_id] = $cat;
			unset($cats[$i]);
		}
	}

	foreach ($into as $topCat) {
		$topCat->children = array();
		wpas_sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
	}
}

/**
 * Recursively displays hierarchical options into a select dropdown.
 *
 * @since  3.0.1
 * @param  object $term  The term to display
 * @param  string $value The value to compare against
 * @return void
 */
function wpas_hierarchical_taxonomy_dropdown_options( $term, $value, $level = 1 ) {

	$option = '';

	/* Add a visual indication that this is a child term */
	if ( 1 !== $level ) {
		for ( $i = 1; $i < ( $level - 1 ); $i++ ) {
			$option .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$option .= '&angrt; ';
	}

	$option .= $term->name;
	?>

	<option value="<?php echo $term->term_id; ?>" <?php if( (int) $value === $term->term_id || $value === $term->slug  ) { echo 'selected="selected"'; } ?>><?php echo $option; ?></option>

	<?php if ( isset( $term->children ) && !empty( $term->children ) ) {
		++$level;
		foreach ( $term->children as $child ) {
			wpas_hierarchical_taxonomy_dropdown_options( $child, $value, $level );
		}
	}

}
