<?php
/**
 * Get field container class.
 *
 * @since      3.0.0
 *
 * @param  string $field_name Name of the field we're getting the container class for
 * @param  string $extra      Extra classes to pass to the function
 *
 * @deprecated 3.2.0
 * @return string             The class tag with appropriate classes
 */
function wpas_get_field_container_class( $field_name = '', $extra = '' ) {

	$class = 'wpas-form-group';

	if ( isset( $_SESSION['wpas_submission_error'] ) && is_array( $_SESSION['wpas_submission_error'] ) && in_array( $field_name, $_SESSION['wpas_submission_error'] ) ) {
		$class .= ' has-error';
	}

	if ( '' != $extra ) {
		$class .= " $extra";
	}

	return $class;

}

/**
 * Get field class.
 *
 * @param  string $field_name Name of the field we're getting the class for
 * @param  string $extra      Extra classes to pass to the function
 * @param         $echo       bool Whether to echo the result or return it
 *
 * @since      3.0.0
 * @deprecated 3.2.0
 * @return string             The class tag with appropriate classes
 */
function wpas_get_field_class( $field_name = '', $extra = '', $echo = true ) {

	$class = 'wpas-form-control';

	if ( '' != $extra ) {
		$class .= " $extra";
	}

	if ( true === $echo ) {
		echo "class='$class'";
	} else {
		return $class;
	}

}

/**
 * Get temporary field value.
 *
 * Once a form is submitted, all values are kept
 * in session in case the ticket submission fails.
 * Once the submission form reloads we can pre-popupate fields
 * and avoid the pain of re-typing everything for the user.
 * When a submission is valid, the session is destroyed.
 *
 * @param  string $field_name The name of the field to get the value for
 * @return string             The temporary value for this field
 * @since  3.0.0
 * @deprecated 3.2.0
 */
function wpas_get_field_value( $field_name ) {

	$meta = get_post_meta( get_the_ID(), '_wpas_' . $field_name, true );

	if ( isset( $_SESSION['wpas_submission_form'] ) && is_array( $_SESSION['wpas_submission_form'] ) && array_key_exists( $field_name, $_SESSION['wpas_submission_form'] ) ) {
		$value = $_SESSION['wpas_submission_form'][$field_name];
	} elseif ( !empty( $meta ) ) {
		$value = $meta;
	} else {
		$value = '';
	}

	return apply_filters( 'wpas_get_field_value', esc_attr( wp_unslash( $value ) ), $field_name );

}