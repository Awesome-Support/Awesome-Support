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

$submit        = get_permalink( mumei_ayuda_get_option( 'ticket_list' ) );
$registration  = mumei_ayuda_get_option( 'allow_registrations', 'allow' ); // Make sure registrations are open
$redirect_to   = get_permalink( $post->ID );
$wrapper_class = 'allow' !== $registration && 'moderated' !== $registration ? 'wpas-login-only' : 'wpas-login-register';
?>

<div class="wpas <?php echo esc_attr( $wrapper_class ); ?>">
	<?php do_action('mumei_ayuda_before_login_form'); ?>

	<form class="wpas-form" id="mumei_ayuda_form_login" method="post" role="form" action="<?php echo esc_url( mumei_ayuda_get_login_url() ); ?>">
		<h3><?php esc_html_e( 'Log in', 'ayuda-help-desk' ); ?></h3>

		<?php
		/* Registrations are not allowed. */
		if ( 'disallow' === $registration ) {
			echo wp_kses(mumei_ayuda_get_notification_markup( 'failure', __( 'Registrations are currently not allowed.', 'ayuda-help-desk' ) ), get_allowed_html_wp_notifications());
		}

		$username = new MUMEI_AYUDA_Custom_Field( 'log', array(
			'name' => 'log',
			'args' => array(
				'spellcheck'    => false,
				'required'    => true,
				'field_type'  => 'text',
				'label'       => __( 'E-mail or username', 'ayuda-help-desk' ),
				'placeholder' => __( 'E-mail or username', 'ayuda-help-desk' ),
				'sanitize'    => 'sanitize_user'
			)
		) );

		$username = apply_filters( 'mumei_ayuda_login_form_user_name', $username ) ;

		echo wp_kses($username->get_output(), mumei_ayuda_registration_allowed_html_tags());

		$password = new MUMEI_AYUDA_Custom_Field( 'pwd', array(
			'name' => 'pwd',
			'args' => array(
				'spellcheck'    => false,
				'required'    => true,
				'field_type'  => 'password',
				'label'       => __( 'Password', 'ayuda-help-desk' ),
				'placeholder' => __( 'Password', 'ayuda-help-desk' ),
				'sanitize'    => 'sanitize_text_field'
			)
		) );

		$password = apply_filters( 'mumei_ayuda_login_form_password', $password ) ;

		echo wp_kses($password->get_output(), mumei_ayuda_registration_allowed_html_tags());

		/**
		 * mumei_ayuda_after_login_fields hook
		 */
		do_action( 'mumei_ayuda_after_login_fields' );

		$rememberme = new MUMEI_AYUDA_Custom_Field( 'rememberme', array(
			'name' => 'rememberme',
			'args' => array(
				'required'   => true,
				'field_type' => 'checkbox',
				'sanitize'   => 'sanitize_text_field',
				'options'    => array( '1' => __( 'Remember Me', 'ayuda-help-desk' ) ),
			)
		) );

		$rememberme = apply_filters( 'mumei_ayuda_login_form_rememberme', $rememberme ) ;		
		echo wp_kses($rememberme->get_output(), mumei_ayuda_registration_allowed_html_tags());

		mumei_ayuda_do_field( 'login', $redirect_to );
		mumei_ayuda_make_button( __( 'Log in', 'ayuda-help-desk' ), array( 'onsubmit' => __( 'Logging In...', 'ayuda-help-desk' ) ) );
		printf( '<a href="%1$s" class="wpas-forgot-password-link">%2$s</a>', esc_url( wp_lostpassword_url( mumei_ayuda_get_tickets_list_page_url() ) ), esc_html__( 'Forgot password?', 'ayuda-help-desk' ) ); ?>
	</form>

	<?php
	if ( 'allow' === $registration || 'moderated' === $registration ): ?>

		<form class="wpas-form" id="mumei_ayuda_form_registration" method="post" action="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
			<h3><?php esc_html_e( 'Register', 'ayuda-help-desk' ); ?></h3>

			<?php
			$first_name_desc = mumei_ayuda_get_option( 'reg_first_name_desc', '' ) ;
			$first_name = new MUMEI_AYUDA_Custom_Field( 'first_name', array(
				'name' => 'first_name',
				'args' => array(
					'required'    => true,
					'field_type'  => 'text',
					'label'       => __( 'First Name', 'ayuda-help-desk' ),
					'placeholder' => __( 'First Name', 'ayuda-help-desk' ),
					'sanitize'    => 'sanitize_text_field',
					'desc'		  => $first_name_desc,
					'default'	  => ( isset( $_SESSION["mumei_ayuda_registration_form"]["first_name"] ) && sanitize_text_field( $_SESSION["mumei_ayuda_registration_form"]["first_name"] ) ) ? sanitize_text_field( $_SESSION["mumei_ayuda_registration_form"]["first_name"] ) : ""
				)
			) );

			$first_name = apply_filters( 'mumei_ayuda_registration_form_first_name', $first_name ) ;

			echo wp_kses($first_name->get_output(), mumei_ayuda_registration_allowed_html_tags());

			$last_name_desc = mumei_ayuda_get_option( 'reg_last_name_desc', '' ) ;
			$last_name = new MUMEI_AYUDA_Custom_Field( 'last_name', array(
				'name' => 'last_name',
				'args' => array(
					'required'    => true,
					'field_type'  => 'text',
					'label'       => __( 'Last Name', 'ayuda-help-desk' ),
					'placeholder' => __( 'Last Name', 'ayuda-help-desk' ),
					'sanitize'    => 'sanitize_text_field',
					'desc'		  => $last_name_desc,
					'default'	  => ( isset( $_SESSION["mumei_ayuda_registration_form"]["last_name"] ) && sanitize_text_field( $_SESSION["mumei_ayuda_registration_form"]["last_name"] ) ) ? sanitize_text_field( $_SESSION["mumei_ayuda_registration_form"]["last_name"] ) : ""
				)
			) );

			$last_name = apply_filters( 'mumei_ayuda_registration_form_last_name', $last_name ) ;

			echo wp_kses($last_name->get_output(), mumei_ayuda_registration_allowed_html_tags());

			$email_desc = mumei_ayuda_get_option( 'reg_email_desc', '' ) ;
			$email = new MUMEI_AYUDA_Custom_Field( 'email', array(
				'name' => 'email',
				'args' => array(
					'required'    => true,
					'spellcheck'    => false,
					'field_type'  => 'email',
					'label'       => __( 'Email', 'ayuda-help-desk' ),
					'placeholder' => __( 'Email', 'ayuda-help-desk' ),
					'sanitize'    => 'sanitize_text_field',
					'desc'		  => $email_desc,
					'default'	  => ( isset( $_SESSION["mumei_ayuda_registration_form"]["email"] ) && sanitize_email( $_SESSION["mumei_ayuda_registration_form"]["email"] ) ) ? sanitize_email( $_SESSION["mumei_ayuda_registration_form"]["email"] ) : ""
				)
			) );

			$email = apply_filters( 'mumei_ayuda_registration_form_email', $email ) ;

			echo wp_kses($email->get_output(), mumei_ayuda_registration_allowed_html_tags());

			$pwd = new MUMEI_AYUDA_Custom_Field( 'password', array(
				'name' => 'password',
				'args' => array(
					'required'    => true,
					'spellcheck'    => false,
					'field_type'  => 'password',
					'label'       => __( 'Enter a password', 'ayuda-help-desk' ),
					'placeholder' => __( 'Password', 'ayuda-help-desk' ),
					'sanitize'    => 'sanitize_text_field'
				)
			) );

			$pwd = apply_filters( 'mumei_ayuda_registration_form_password', $pwd ) ;

			echo wp_kses($pwd->get_output(), mumei_ayuda_registration_allowed_html_tags());

			$showpwd = new MUMEI_AYUDA_Custom_Field( 'pwdshow', array(
				'name' => 'pwdshow',
				'args' => array(
					'required'   => false,
					'field_type' => 'checkbox',
					'sanitize'   => 'sanitize_text_field',
					'options'    => array( '1' => _x( 'Show Password', 'Login form', 'ayuda-help-desk' ) ),
				)
			) );

			echo wp_kses($showpwd->get_output(), mumei_ayuda_registration_allowed_html_tags());

			/**
			 * mumei_ayuda_after_registration_fields hook
			 *
			 * @Mumei_Ayuda_Support::terms_and_conditions_checkbox()
			 */
			do_action( 'mumei_ayuda_after_registration_fields' );
			mumei_ayuda_do_field( 'register', $redirect_to );
			wp_nonce_field( 'register', 'user_registration', false, true );
			mumei_ayuda_make_button( __( 'Create Account', 'ayuda-help-desk' ), array( 'onsubmit' => __( 'Creating Account...', 'ayuda-help-desk' ) ) );
			?>
		</form>
	<?php endif; ?>
</div>
