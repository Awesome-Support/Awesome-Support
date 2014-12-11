<?php
/**
 * This is a built-in template file. If you need to customize it, please,
 * DO NOT modify this file directly. Instead, copy it to your theme's directory
 * and then modify the code. If you modify this file directly, your changes
 * will be overwritten during next update of the plugin.
 */

/**
 * Make the post data and the pre-form message global
 */
global $post, $wpas_notification;

$submit = get_permalink( wpas_get_option( 'ticket_list' ) );

/**
 * If there is a message to display we show a bootstrap info box
 */
if ( isset( $param ) && $param['msg'] && $param['type'] ):

	$wpas_notification->notification( $param['type'], $param['msg'] );

endif;

$registration  = boolval( wpas_get_option( 'allow_registrations', true ) ); // Make sure registrations are open
$redirect_to   = get_permalink( $post->ID );
$wrapper_class = true !== $registration ? 'wpas-login-only' : 'wpas-login-register';
?>

<div class="wpas <?php echo $wrapper_class; ?>">

	<form class="wpas-form" method="post" role="form" action="<?php echo wpas_get_login_url(); ?>">
		<h3><?php _e( 'Login' ); ?></h3>

		<?php
		/* Registrations are not allowed. */
		if ( false === $registration ) {
			wpas_notification( 'failure', __( 'Registrations are currently not allowed.', 'wpas' ) );
		}
		?>
		
		<div <?php wpas_get_field_container_class( 'log' ); ?>>			
			<label><?php _e( 'Username' ); ?></label>
			<input type="text" name="log" <?php wpas_get_field_class( 'log' ); ?> placeholder="<?php _e( 'Username' ); ?>" required>
		</div>
		<div <?php wpas_get_field_container_class( 'pwd' ); ?>>
			<label><?php _e( 'Password' ); ?></label>
			<input type="password" name="pwd" <?php wpas_get_field_class( 'pwd' ); ?> placeholder="<?php _e( 'Password' ); ?>" required>
		</div>

		<?php
		/**
		 * wpas_after_login_fields hook
		 */
		do_action( 'wpas_after_login_fields' );
		?>

		<div class="wpas-checkbox">
			<label><input type="checkbox" name="rememberme" class="wpas-form-control-checkbox"> <?php echo _x( 'Remember me', 'Login form', 'wpas' ); ?></label>
		</div>

		<input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">
		<?php wpas_make_button( __( 'Login', 'wpas' ), array( 'onsubmit' => __( 'Logging In...', 'wpas' ) ) ); ?>
	</form>
	<?php
	if ( true === $registration ): ?> 

		<form class="wpas-form" method="post" action="<?php echo get_permalink( $post->ID ); ?>">
			<h3><?php _e( 'Register' ); ?></h3>
			<div <?php wpas_get_field_container_class( 'email' ); ?>>
				<label><?php _e( 'Email' ); ?></label>
				<input <?php wpas_get_field_class( 'email' ); ?> type="email" placeholder="<?php _e( 'Email' ); ?>" name="email" value="<?php echo wpas_get_registration_field_value( 'email' ); ?>" required>
			</div>
			<div <?php wpas_get_field_container_class( 'first_name' ); ?>>
				<label><?php _e( 'First Name', 'wpas' ); ?></label>
				<input <?php wpas_get_field_class( 'first_name' ); ?> type="text" placeholder="<?php _e( 'First Name', 'wpas' ); ?>" name="first_name" value="<?php echo wpas_get_registration_field_value( 'first_name' ); ?>" required>
			</div>
			<div <?php wpas_get_field_container_class( 'last_name' ); ?>>
				<label><?php _e( 'Last Name', 'wpas' ); ?></label>
				<input <?php wpas_get_field_class( 'last_name' ); ?> type="text" placeholder="<?php _e( 'Last Name', 'wpas' ); ?>" name="last_name" value="<?php echo wpas_get_registration_field_value( 'last_name' ); ?>" required>
			</div>
			<div <?php wpas_get_field_container_class( 'pwd' ); ?>>
				<label><?php _e( 'Enter a password', 'wpas' ); ?></label>
				<input <?php wpas_get_field_class( 'pwd', 'wpas-pwd' ); ?> type="password" placeholder="<?php _e( 'Enter a password', 'wpas' ); ?>" id="password" name="pwd" required>
			</div>
			<div <?php wpas_get_field_container_class( 'pwd-validate' ); ?>>
				<label><?php _e( 'Repeat password', 'wpas' ); ?></label>
				<input <?php wpas_get_field_class( 'pwd-validate', 'wpas-checkpwd' ); ?> type="password" placeholder="<?php _e( 'Repeat password', 'wpas' ); ?>" id="passwordconf" name="pwd-validate" data-validation="<?php _e( 'The two passwords must match.', 'wpas' ); ?>" required>
			</div>

			<?php
			/**
			 * wpas_after_registration_fields hook
			 * 
			 * @Awesome_Support::terms_and_conditions_checkbox()
			 */
			do_action( 'wpas_after_registration_fields' );
			?>
			<input type="hidden" name="wpas_registration" value="true">
			<?php
			wp_nonce_field( 'register', 'user_registration', false, true );
			wpas_make_button( __( 'Create Account', 'wpas' ), array( 'onsubmit' => __( 'Creating Account...', 'wpas' ) ) );
			?>
		</form>
	<?php endif; ?>
</div>