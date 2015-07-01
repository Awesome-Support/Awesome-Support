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