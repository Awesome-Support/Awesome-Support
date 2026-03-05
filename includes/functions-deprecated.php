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
function mumei_ayuda_get_field_container_class( $field_name = '', $extra = '' ) {

	$class = 'wpas-form-group';

	if ( isset( $_SESSION['mumei_ayuda_submission_error'] ) && is_array( $_SESSION['mumei_ayuda_submission_error'] ) && in_array( $field_name, $_SESSION['mumei_ayuda_submission_error'] ) ) {
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
function mumei_ayuda_get_field_class( $field_name = '', $extra = '', $echo = true ) {

	$class = 'wpas-form-control';

	if ( '' != $extra ) {
		$class .= " $extra";
	}

	if ( true === $echo ) {
		echo "class='" . wp_kses_post($class). "'";
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
function mumei_ayuda_get_field_value( $field_name ) {

	$meta = get_post_meta( get_the_ID(), '_mumei_ayuda_' . $field_name, true );

	if ( isset( $_SESSION['mumei_ayuda_submission_form'] ) && is_array( $_SESSION['mumei_ayuda_submission_form'] ) && array_key_exists( $field_name, $_SESSION['mumei_ayuda_submission_form'] ) ) {
		$value = sanitize_text_field( $_SESSION['mumei_ayuda_submission_form'][$field_name] );
	} elseif ( !empty( $meta ) ) {
		$value = $meta;
	} else {
		$value = '';
	}

	return apply_filters( 'mumei_ayuda_get_field_value', esc_attr( wp_unslash( $value ) ), $field_name );

}

/**
 * Shows the message field.
 *
 * The function echoes the textarea where the user
 * may input the ticket description. The field can be
 * either a textarea or a WYSIWYG depending on the plugin settings.
 * The WYSIWYG editor uses TinyMCE with a minimal configuration.
 *
 * @since      3.0.0
 * @deprecated 3.2.0
 *
 * @param  array $editor_args Arguments used for TinyMCE
 *
 * @return void
 */
function mumei_ayuda_get_message_textarea( $editor_args = array() ) {

	/**
	 * Check if the description field should use the WYSIWYG editor
	 *
	 * @var string
	 */
	$textarea_class = ( true === ( $wysiwyg = boolval( mumei_ayuda_get_option( 'frontend_wysiwyg_editor' ) ) ) ) ? 'wpas-wysiwyg' : 'wpas-textarea';

	if ( true === $wysiwyg ) {

		$editor_defaults = apply_filters( 'mumei_ayuda_ticket_editor_args', array(
			'media_buttons' => false,
			'textarea_name' => 'mumei_ayuda_message',
			'textarea_rows' => 10,
			'tabindex'      => 2,
			'editor_class'  => mumei_ayuda_get_field_class( 'mumei_ayuda_message', $textarea_class, false ),
			'quicktags'     => false,
			'tinymce'       => array(
				'toolbar1' => 'bold,italic,underline,strikethrough,hr,|,bullist,numlist,|,link,unlink',
				'toolbar2' => ''
			),
		) );

		?><div class="wpas-submit-ticket-wysiwyg"><?php
		wp_editor( mumei_ayuda_get_field_value( 'mumei_ayuda_message' ), 'wpas-ticket-message', apply_filters( 'mumei_ayuda_reply_wysiwyg_args', $editor_defaults ) );
		?></div><?php

	} else {

		/**
		 * Define if the body can be submitted empty or not.
		 *
		 * @since  3.0.0
		 * @var boolean
		 */
		$can_submit_empty = apply_filters( 'mumei_ayuda_can_message_be_empty', false );
		?>
		<div class="wpas-submit-ticket-wysiwyg">
			<textarea <?php mumei_ayuda_get_field_class( 'mumei_ayuda_message', wp_kses_post($textarea_class) ); ?> id="wpas-ticket-message" name="mumei_ayuda_message" placeholder="<?php echo wp_kses_post(apply_filters( 'mumei_ayuda_form_field_placeholder_mumei_ayuda_message', __( 'Describe your problem as accurately as possible', 'ayuda-help-desk' ) )); ?>" rows="10" <?php if ( false === $can_submit_empty ): ?>required="required"<?php endif; ?>><?php echo wp_kses_post(mumei_ayuda_get_field_value( 'mumei_ayuda_message' )); ?></textarea>
		</div>
	<?php }

}

/**
 * Get temporary user data.
 *
 * If the user registration fails some of the user data is saved
 * (all except the password) and can be used to pre-populate the registration
 * form after the page reloads. This function returns the desired field value
 * if any.
 *
 * @since      3.0.0
 * @deprecated 3.2.0
 *
 * @param  string $field Name of the field to get the value for
 *
 * @return string        The sanitized field value if any, an empty string otherwise
 */
function mumei_ayuda_get_registration_field_value( $field ) {

	if ( isset( $_SESSION ) && isset( $_SESSION['mumei_ayuda_registration_form'][ $field ] ) ) {
		return sanitize_text_field( $_SESSION['mumei_ayuda_registration_form'][ $field ] );
	} else {
		return '';
	}

}

/**
 * Display notification.
 *
 * This function returns a notification either
 * predefined or customized by the user.
 *
 * @since      3.0.0
 * @deprecated 3.2
 *
 * @param  string         $case    Type of notification
 * @param  boolean|string $message Message to display
 * @param  boolean        $echo    Whether to echo or return the notification
 *
 * @return string           Notification (with markup)
 * @see        MUMEI_AYUDA_Notification
 */
function mumei_ayuda_notification( $case, $message = '', $echo = true ) {
	_deprecated_function( __FUNCTION__, '3.2', 'mumei_ayuda_get_notification_markup()' );
}

/**
 * Create custom notification.
 *
 * Takes a custom message and encodes it so that it can be
 * passed safely as a URL parameter.
 *
 * @since      3.0.0
 * @deprecated 3.2
 *
 * @param  string $message Custom message
 *
 * @return string          Encoded message
 */
function mumei_ayuda_create_notification( $message ) {
	_deprecated_function( __FUNCTION__, '3.2' );
}

/**
 * Add custom action and nonce to URL.
 *
 * The function adds a custom action trigger using the wpas-do
 * URL parameter and adds a security nonce for plugin custom actions.
 *
 * @param  string $url    URL to customize
 * @param  string $action Custom action to add
 *
 * @return string         Customized URL
 * @since      3.0.0
 * @deprecated 3.3
 */
function mumei_ayuda_url_add_custom_action( $url, $action ) {

	_deprecated_function( 'mumei_ayuda_url_add_custom_action', '3.3', 'mumei_ayuda_do_url' );

	return mumei_ayuda_do_url( $url, sanitize_text_field( $action ) );
}

/**
 * Check a custom action nonce.
 *
 * @since      3.1.5
 *
 * @param  string $nonce Nonce to be checked
 *
 * @return boolean        Nonce validity
 * @deprecated 3.3
 */
function mumei_ayuda_check_nonce( $nonce ) {
	_deprecated_function( 'mumei_ayuda_check_nonce', '3.3', 'mumei_ayuda_do_url' );
	return wp_verify_nonce( $nonce, 'mumei_ayuda_custom_action' );
}

/**
 * Add a security nonce.
 *
 * The function adds a security nonce to URLs
 * with a trigger for plugin custom action.
 *
 * @param  string $url URL to nonce
 *
 * @return string      Nonced URL
 * @since      3.0.0
 * @deprecated 3.3
 */
function mumei_ayuda_nonce_url( $url ) {

	_deprecated_function( 'mumei_ayuda_nonce_url', '3.3', 'mumei_ayuda_do_url' );

	return add_query_arg( array( 'wpas-nonce' => wp_create_nonce( 'mumei_ayuda_custom_action' ) ), $url );
}