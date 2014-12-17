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

				if ( method_exists( 'WPAS_Custom_Fields_Display', $callback ) ) {
					call_user_func( array( 'WPAS_Custom_Fields_Display', $callback ), $field );
				} else {
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
				<input type="text" id="<?php echo $field_id; ?>" <?php wpas_get_field_class( $field_id, $field_class ); ?> name="<?php echo $field_id; ?>" value="<?php echo $value; ?>" <?php if ( true === $field['args']['required'] ): ?>required<?php endif; ?>>
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

		$field_id = 'wpas_' . $field['name'];
		$label    = wpas_get_field_title( $field );
		$current  = get_the_terms( $post->ID, sanitize_text_field( $field['name'] ) );
		$terms    = get_terms( sanitize_text_field( $field['name'] ), array( 'hide_empty' => 0 ) );
		$value    = '';

		if ( is_array( $current ) ) {
		
			foreach ( $current as $term ) {
				$value = $term->slug;
			}

		}

		/* In case the taxonomy does not exist */
		if ( is_wp_error( $terms ) ) {
			return;
		}
		?>

		<div id="<?php echo $field_id; ?>_container" <?php wpas_get_field_container_class( $field_id ); ?>>
			<label for="<?php echo $field_id; ?>"><strong><?php echo $label; ?></strong></label>

			<?php if ( !is_admin() || current_user_can( $field['args']['capability'] ) ): ?>

				<select name="<?php echo $field_id; ?>" id="<?php echo $field_id; ?>" <?php wpas_get_field_class( $field_id ); ?> style="width:100%">
					<option value=""><?php _e( 'Please select', 'wpas' ); ?></option>

					<?php
					foreach( $terms as $term ) { ?>
						<option value="<?php echo $term->slug; ?>" <?php if( $term->slug == $value ) { echo 'selected="selected"'; } ?>><?php echo $term->name; ?></option>
					<?php } ?>

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
		$label  = ucwords( $status );
		$color  = wpas_get_option( "color_$status", '#dd3333' );
		$tag    = "<span class='wpas-label' style='background-color:$color;'>$label</span>";
	} else {

		$post          = get_post( $post_id );
		$post_status   = $post->post_status;
		$custom_status = wpas_get_post_status();

		if ( !array_key_exists( $post_status, $custom_status ) ) {
			$label  = ucwords( $status );
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