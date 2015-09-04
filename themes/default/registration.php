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
global $post;

$submit        = get_permalink( wpas_get_option( 'ticket_list' ) );
$registration  = wpas_get_option( 'allow_registrations', 'allow' ); // Make sure registrations are open
$redirect_to   = get_permalink( $post->ID );
$wrapper_class = 'allow' !== $registration ? 'wpas-login-only' : 'wpas-login-register';
?>

<div class="wpas <?php echo $wrapper_class; ?>">
	<?php do_action('wpas_before_login_form'); ?>

	<form class="wpas-form" id="wpas_form_login" method="post" role="form" action="<?php echo wpas_get_login_url(); ?>">
		<h3><?php _e( 'Log in', 'wpas' ); ?></h3>

		<?php
		/* Registrations are not allowed. */
		if ( 'disallow' === $registration ) {
			echo wpas_get_notification_markup( 'failure', __( 'Registrations are currently not allowed.', 'wpas' ) );
		}

		$username = new WPAS_Custom_Field( 'log', array(
			'name' => 'log',
			'args' => array(
				'required'    => true,
				'field_type'  => 'text',
				'label'       => __( 'E-mail or username', 'wpas' ),
				'placeholder' => __( 'E-mail or username', 'wpas' ),
				'sanitize'    => 'sanitize_text_field'
			)
		) );

		echo $username->get_output();

		$password = new WPAS_Custom_Field( 'pwd', array(
			'name' => 'pwd',
			'args' => array(
				'required'    => true,
				'field_type'  => 'password',
				'label'       => __( 'Password', 'wpas' ),
				'placeholder' => __( 'Password', 'wpas' ),
				'sanitize'    => 'sanitize_text_field'
			)
		) );

		echo $password->get_output();

		/**
		 * wpas_after_login_fields hook
		 */
		do_action( 'wpas_after_login_fields' );

		$rememberme = new WPAS_Custom_Field( 'rememberme', array(
			'name' => 'rememberme',
			'args' => array(
				'required'   => true,
				'field_type' => 'checkbox',
				'sanitize'   => 'sanitize_text_field',
				'options'    => array( '1' => __( 'Remember Me', 'wpas' ) ),
			)
		) );

		echo $rememberme->get_output();
		?>

		<input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">
		<input type="hidden" name="wpas_login" value="1">
		<?php wpas_make_button( __( 'Log in' ), array( 'onsubmit' => __( 'Logging In...', 'wpas' ) ) ); ?>
	</form>
	<?php
	if ( 'allow' === $registration ): ?>

		<form class="wpas-form" id="wpas_form_registration" method="post" action="<?php echo get_permalink( $post->ID ); ?>">
			<h3><?php _e( 'Register', 'wpas' ); ?></h3>

			<?php
			$first_name = new WPAS_Custom_Field( 'first_name', array(
				'name' => 'first_name',
				'args' => array(
					'required'    => true,
					'field_type'  => 'text',
					'label'       => __( 'First Name', 'wpas' ),
					'placeholder' => __( 'First Name', 'wpas' ),
					'sanitize'    => 'sanitize_text_field'
				)
			) );

			echo $first_name->get_output();

			$last_name = new WPAS_Custom_Field( 'last_name', array(
				'name' => 'last_name',
				'args' => array(
					'required'    => true,
					'field_type'  => 'text',
					'label'       => __( 'Last Name', 'wpas' ),
					'placeholder' => __( 'Last Name', 'wpas' ),
					'sanitize'    => 'sanitize_text_field'
				)
			) );

			echo $last_name->get_output();

			$email = new WPAS_Custom_Field( 'email', array(
				'name' => 'email',
				'args' => array(
					'required'    => true,
					'field_type'  => 'email',
					'label'       => __( 'Email', 'wpas' ),
					'placeholder' => __( 'Email', 'wpas' ),
					'sanitize'    => 'sanitize_text_field'
				)
			) );

			echo $email->get_output();

			$pwd = new WPAS_Custom_Field( 'password', array(
				'name' => 'password',
				'args' => array(
					'required'    => true,
					'field_type'  => 'password',
					'label'       => __( 'Enter a password', 'wpas' ),
					'placeholder' => __( 'Password', 'wpas' ),
					'sanitize'    => 'sanitize_text_field'
				)
			) );

			echo $pwd->get_output();

			$showpwd = new WPAS_Custom_Field( 'pwdshow', array(
				'name' => 'pwdshow',
				'args' => array(
					'required'   => false,
					'field_type' => 'checkbox',
					'sanitize'   => 'sanitize_text_field',
					'options'    => array( '1' => _x( 'Show Password', 'Login form', 'wpas' ) ),
				)
			) );

			echo $showpwd->get_output();

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
