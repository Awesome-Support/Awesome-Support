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
		<h3><?php _e( 'Log in', 'awesome-support' ); ?></h3>

		<?php
		/* Registrations are not allowed. */
		if ( 'disallow' === $registration ) {
			echo wpas_get_notification_markup( 'failure', __( 'Registrations are currently not allowed.', 'awesome-support' ) );
		}

		$username = new WPAS_Custom_Field( 'log', array(
			'name' => 'log',
			'args' => array(
				'required'    => true,
				'field_type'  => 'text',
				'label'       => __( 'E-mail or username', 'awesome-support' ),
				'placeholder' => __( 'E-mail or username', 'awesome-support' ),
				'sanitize'    => 'sanitize_user'
			)
		) );

		echo $username->get_output();

		$password = new WPAS_Custom_Field( 'pwd', array(
			'name' => 'pwd',
			'args' => array(
				'required'    => true,
				'field_type'  => 'password',
				'label'       => __( 'Password', 'awesome-support' ),
				'placeholder' => __( 'Password', 'awesome-support' ),
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
				'options'    => array( '1' => __( 'Remember Me', 'awesome-support' ) ),
			)
		) );

		echo $rememberme->get_output();

		wpas_do_field( 'login', $redirect_to );
		wpas_make_button( __( 'Log in', 'awesome-support' ), array( 'onsubmit' => __( 'Logging In...', 'awesome-support' ) ) );
		printf( '<a href="%1$s" class="wpas-forgot-password-link">%2$s</a>', wp_lostpassword_url( wpas_get_tickets_list_page_url() ), esc_html( __( 'Forgot password?', 'awesome-support' ) ) ); ?>
	</form>
	
	<?php
	if ( 'allow' === $registration ): ?>

		<form class="wpas-form" id="wpas_form_registration" method="post" action="<?php echo get_permalink( $post->ID ); ?>">
			<h3><?php _e( 'Register', 'awesome-support' ); ?></h3>

			<?php
			$first_name_desc = wpas_get_option( 'reg_first_name_desc', '' ) ;
			$first_name = new WPAS_Custom_Field( 'first_name', array(
				'name' => 'first_name',
				'args' => array(
					'required'    => true,
					'field_type'  => 'text',
					'label'       => __( 'First Name', 'awesome-support' ),
					'placeholder' => __( 'First Name', 'awesome-support' ),
					'sanitize'    => 'sanitize_text_field',
					'desc'		  => $first_name_desc,
				)
			) );

			echo $first_name->get_output();

			$last_name_desc = wpas_get_option( 'reg_last_name_desc', '' ) ;
			$last_name = new WPAS_Custom_Field( 'last_name', array(
				'name' => 'last_name',
				'args' => array(
					'required'    => true,
					'field_type'  => 'text',
					'label'       => __( 'Last Name', 'awesome-support' ),
					'placeholder' => __( 'Last Name', 'awesome-support' ),
					'sanitize'    => 'sanitize_text_field',
					'desc'		  => $last_name_desc,
				)
			) );

			echo $last_name->get_output();

			$email_desc = wpas_get_option( 'reg_email_desc', '' ) ;
			$email = new WPAS_Custom_Field( 'email', array(
				'name' => 'email',
				'args' => array(
					'required'    => true,
					'field_type'  => 'email',
					'label'       => __( 'Email', 'awesome-support' ),
					'placeholder' => __( 'Email', 'awesome-support' ),
					'sanitize'    => 'sanitize_text_field',
					'desc'		  => $email_desc,
				)
			) );

			echo $email->get_output();

			$pwd = new WPAS_Custom_Field( 'password', array(
				'name' => 'password',
				'args' => array(
					'required'    => true,
					'field_type'  => 'password',
					'label'       => __( 'Enter a password', 'awesome-support' ),
					'placeholder' => __( 'Password', 'awesome-support' ),
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
					'options'    => array( '1' => _x( 'Show Password', 'Login form', 'awesome-support' ) ),
				)
			) );

			echo $showpwd->get_output();

			/**
			 * wpas_after_registration_fields hook
			 * 
			 * @Awesome_Support::terms_and_conditions_checkbox()
			 */
			do_action( 'wpas_after_registration_fields' );
			wpas_do_field( 'register', $redirect_to );
			wp_nonce_field( 'register', 'user_registration', false, true );
			wpas_make_button( __( 'Create Account', 'awesome-support' ), array( 'onsubmit' => __( 'Creating Account...', 'awesome-support' ) ) );
			?>
		</form>
	<?php endif; ?>
</div>
