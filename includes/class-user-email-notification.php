<?php
/**
 * User E-Mail Notifications.
 *
 * This class handles all e-mail notifications related to users. One instance of this class
 * relates to one and one only user, but can handle multiple notifications
 * for the same user.
 *
 * The available notifications can be extended with the use of a few filters
 * available throughout the class and the dispatch of e-mails is handled by
 * the pluggable function wp_mail(). It is recommended to use a proper SMTP
 * server for e-mail routing in order to ensure a safe delivery.
 *
 * @package   Awesome Support
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */
class WPAS_User_Email_Notification {

	/**
	 * ID of the user to notify about.
	 * 
	 * @var integer
	 */
	protected $user_id;
	
	/**
	 * Email address of recipient
	 * 
	 * @var string
	 */
	protected $recipient_email;
	
	/**
	 * Recipient user id
	 * 
	 * @var int
	 */
	protected $recipient_id;


	/**
	 * User object
	 * 
	 * @var WP_User
	 */
	protected $user;
	
	/**
	 * Class constructor.
	 * 
	 * @param int $user_id
	 * @param string $recipient_email
	 * @param int $recipient_id
	 * 
	 * @return \WP_Error|void
	 */
	public function __construct( $user_id, $recipient_email, $recipient_id = 0 ) {
		
		
		$user = get_user_by( 'id', $user_id );
		
		/* Make sure the given user exists. */
		if ( !$user ) {
			return new WP_Error( 'user_does_not_exist', __( 'The user ID provided does not exists', 'awesome-support' ) );
		}
		
		/* Set the e-mail content type to HTML */
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_mime_type' ) );

		/* Set the user ID */
		$this->user_id = $user_id;
		$this->user = $user;
		
		$this->recipient_id = $recipient_id;
		$this->recipient_email = $recipient_email;
		
	}

	public function __destruct() {

		/**
		 * Reset the e-mail content type to text as recommended by WordPress
		 *
		 * @since  5.1.1
		 * @link   http://codex.wordpress.org/Function_Reference/wp_mail
		 */
		add_filter( 'wp_mail_content_type', array( $this, 'set_text_mime_type' ) );

	}


	/**
	 * Check if the requested notification is active.
	 *
	 * E-mail notifications can be enabled or disabled on a
	 * per-case basis by the user in the plugin settings.
	 * We need to check that the requested notification hasn't been
	 * disabled by the user before sending it out.
	 *
	 * @since  5.1.1
	 * @param  string  $case The notification case requested
	 * 
	 * @return boolean       True if the notification is enabled for this case, false otherwise
	 */
	public function is_active( $case ) {

		/* Make sure this case actually exists. */
		if ( !$this->notification_exists( $case ) ) {
			return false;
		}

		$options = $this->cases_active_option();

		/* Make sure we have the option name for this case, otherwise we abort */
		if ( !array_key_exists( $case, $options ) ) {
			return false;
		}

		$option = $options[$case];
		
		/* Replace the valueless tags array by the new one */
		return (bool) apply_filters( 'wpas__user_email_notifications_case_is_active', wpas_get_option( $option, false ), $case ); 
		
	}

	/**
	 * Check if the requested notification exists.
	 * 
	 * @param  string  $case The notification case requested
	 * @return boolean       True if such a case exists, false otherwise
	 */
	public function notification_exists( $case ) {

		$cases = $this->get_cases();

		if ( !in_array( $case, $cases ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Get available notification cases.
	 *
	 * @since  5.1.1
	 * @return array Array of the available cases
	 */
	public function get_cases() {

		$cases = array(
			'moderated_registration_admin',
			'moderated_registration_user',
			'moderated_registration_approved_user',
			'moderated_registration_denied_user'
		);

		return apply_filters( 'wpas__user_email_notifications_cases', $cases );

	}

	/**
	 * Get notification cases active option name.
	 *
	 * @since  5.1.1
	 * @return array Array of available cases with their activation option name
	 */
	private function cases_active_option() {

		$cases					= $this->get_cases();
		$cases['moderated_registration_admin']	= 'enable_moderated_registration_admin_email';
		$cases['moderated_registration_user']	= 'enable_moderated_registration_user_email';
		$cases['moderated_registration_approved_user'] = 'enable_moderated_registration_approved_user_email';
		$cases['moderated_registration_denied_user']   = 'enable_moderated_registration_denied_user_email';
		
		
		return apply_filters( 'wpas__user_email_notifications_cases_active_option', $cases );
	}

	/**
	 * Get sender data.
	 *
	 * @since  5.1.1
	 * @return array Array containing the sender name and e-mail as well as the reply address
	 */
	public function get_sender() {

		if ( isset( $this->data ) && !empty( $this->data ) ) {
			return $this->data;
		}

		$data = array(
			'from_name'   => stripslashes( wpas_get_option( 'sender_name', get_bloginfo( 'name' ) ) ),
			'from_email'  => wpas_get_option( 'sender_email', get_bloginfo( 'admin_email' ) ),
			'reply_email' => wpas_get_option( 'reply_email', get_bloginfo( 'admin_email' ) ),
		);

		$data['reply_name']  = $data['from_name'];

		$this->data = apply_filters( 'wpas__user_email_notifications_sender_data', $data, $this );

		return $this->data;

	}

	/**
	 * Convert tags within a string.
	 *
	 * Takes a string (subject or body) and replace the tags
	 * with their current value if any.
	 *
	 * @since  5.1.1
	 * @param  string $contents String to convert tags from
	 * @return string           String with tags converted into their corresponding value
	 */
	public function fetch( $contents ) {

		$tags = $this->get_tags_values();
		
		foreach ( $tags as $tag ) {

			$id       = $tag['tag'];
			$value    = isset( $tag['value'] ) ? $tag['value'] : '';
			$contents = str_replace( $id, $value, $contents );
			
		}

		return $contents;

	}

	/**
	 * Get the available template tags.
	 *
	 * This is just a list of available tags, no value is attached to those tags.
	 * This list is used both for value attribution and in the contextual help
	 * in the plugin settings page.
	 *
	 * @since  5.1.1
	 * @return array Array of tags with their description
	 */
	public static function get_tags() {

		$tags = array(
			array(
				'tag' 	=> '{user_id}',
				'desc' 	=> __( 'Converts into user ID', 'awesome-support' )
			),
			array(
				'tag' 	=> '{first_name}',
				'desc' 	=> __( 'Converts into user\'s first name', 'awesome-support' )
			),
			array(
				'tag' 	=> '{last_name}',
				'desc' 	=> __( 'Converts into user\'s last name', 'awesome-support' )
			),
			array(
				'tag' 	=> '{display_name}',
				'desc' 	=> __( 'Converts into user name (WordPress Display Name)', 'awesome-support' )
			),
			array(
				'tag' 	=> '{user_profile_link}',
				'desc' 	=> __( 'Displays a link to user profile page', 'awesome-support' )
			),
			array(
				'tag' 	=> '{email}',
				'desc' 	=> __( 'Converts into the user\'s email address', 'awesome-support' )
			),
			array(
				'tag' 	=> '{site_name}',
				'desc' 	=> __( 'Converts into website name', 'awesome-support' )
			),
			array(
				'tag' 	=> '{date}',
				'desc' 	=> __( 'Converts into current date', 'awesome-support' )
			),
			array(
				'tag' 	=> '{admin_email}',
				'desc' 	=> sprintf( __( 'Converts into WordPress admin e-mail (<em>currently: %s</em>)', 'awesome-support' ), get_bloginfo( 'admin_email' ) )
			),

		);

		return apply_filters( 'wpas__user_email_notifications_template_tags', $tags );

	}

	/**
	 * Get tags and their value in the current context.
	 *
	 * @since  5.1.1
	 * @return array Array of tag / value pairs
	 */
	public function get_tags_values() {

		/* Get all available tags */
		$tags = $this->get_tags();

		/* This is where we save the tags with their new value */
		$new = array();

		
		/* Add the tag value in the current context */
		foreach ( $tags as $key => $tag ) {

			$name = trim( $tag['tag'], '{}' );

			switch ( $name ) {

				/* User ID */
				case 'user_id';
					$tag['value'] = $this->user->ID;
					break;

				/* Name of the website */
				case 'site_name':
					$tag['value'] = get_bloginfo( 'name' );
					break;

				case 'first_name':
					$tag['value'] = $this->user->first_name;
					break;

				case 'last_name':
					$tag['value'] = $this->user->last_name;
					break;

				case 'display_name':
					$tag['value'] = $this->user->display_name;
					break;
			
				case 'user_profile_link':
					
					$tag['value'] = add_query_arg( array(
						'user_id'         => $this->user->ID
						), admin_url( 'user-edit.php' ) );
					
					break;
			
				case 'email':
					$tag['value'] = $this->user->user_email;
					break;

				case 'date':
					$tag['value'] = date( get_option( 'date_format' ) );
					break;

				case 'admin_email':
					$tag['value'] = get_bloginfo( 'admin_email' );
					break;

			}

			array_push( $new, $tag );

		}

		/* Replace the valueless tags array by the new one */
		$tags = apply_filters( 'wpas__user_email_notifications_tags_values', $new, $this->user_id );

		return $tags;

	}

	/**
	 * Get e-mail subject.
	 *
	 * @param $case string The type of e-mail notification that's being sent
	 *
	 * @since  5.1.1
	 * @return string E-mail subject
	 */
	private function get_subject( $case ) {
		
		return apply_filters( 'wpas__user_email_notifications_subject', $this->get_content( 'subject', $case ), $this->user_id, $case );
	}

	/**
	 * Get e-mail body.
	 *
	 * @param $case string The type of e-mail notification that's being sent
	 *
	 * @since  5.1.1
	 * @return string E-mail body
	 */
	private function get_body( $case ) {
		return apply_filters( 'wpas__user_email_notifications_body', stripcslashes ( $this->get_content( 'content', $case ) ), $this->user_id, $case );
	}

	/**
	 * Get e-mail content.
	 *
	 * Get the content for the given part.
	 *
	 * @since  5.1.1
	 *
	 * @param  string $part Part of the e-mail to retrieve
	 * @param  string $case Which notification is requested
	 *
	 * @return string       The content with tags converted into their values
	 */
	private function get_content( $part, $case ) {

		
		if ( ! in_array( $part, array( 'subject', 'content' ) ) ) {
			return false;
		}

		/* Set the output value */
		$value = '';
		
		
		$pre_fetch_content = apply_filters( 'wpas__user_email_notifications_pre_fetch_' . $part, $value, $this->user_id, $case );
		
		
		return $this->fetch( $pre_fetch_content );
		
	}

	/**
	 * Retrieve the e-mail template to use and input the content
	 *
	 * @since 5.1.1
	 *
	 * @param string $content The e-mail contents to inject into the template
	 *
	 * @return string
	 */
	public function get_formatted_email( $content = '' ) {

		if ( false === (bool) wpas_get_option( 'use_email_template', true ) ) {
			return $content;
		}

		ob_start();

		// Get the e-mail notification template. This template can be customized by the user.
		// See https://getawesomesupport.com/documentation-new/documentation-awesome-support-core-customization/
		wpas_get_template( 'email-notification' );

		$template = ob_get_contents();

		// Clean buffer
		ob_end_clean();

		$template = str_replace( '{content}', wpautop( $content ), $template ); // Inject content
		$template = str_replace( '{footer}', stripslashes( wpas_get_option( 'email_template_footer', '' ) ), $template ); // Inject footer
		$template = str_replace( '{header}', stripslashes( wpas_get_option( 'email_template_header', '' ) ), $template ); // Inject header

		if ( '' !== $logo = wpas_get_option( 'email_template_logo', '' ) ) {
			$logo = wp_get_attachment_image_src( $logo, 'full' );
			$logo = '<img src="' . $logo[0] . '">';
		}

		$template = str_replace( '{logo}', $logo, $template ); // Inject logo

		return $template;

	}

	/**
	 * Set the e-mail content type to HTML.
	 *
	 * @since  5.1.1
	 *
	 * @return string               HTML content type
	 */
	public function set_html_mime_type() {
		return 'text/html';
	}

	/**
	 * Set the e-mail content type to plain text.
	 *
	 * @since  5.1.1
	 *
	 * @return string               Text content type
	 */
	public function set_text_mime_type() {
		return 'text/plain';
	}

	/**
	 * Send out the e-mail notification.
	 *
	 * @since  5.1.1
	 * @param  string         $case The notification case
	 * @return boolean|object       True if the notification was sent, WP_Error otherwise
	 */
	public function notify( $case ) {

		if ( !$this->notification_exists( $case ) ) {
			return new WP_Error( 'unknown_notification', __( 'The requested notification does not exist', 'awesome-support' ) );
		}

		if ( !$this->is_active( $case ) ) {
			return new WP_Error( 'disabled_notification', __( 'The requested notification is disabled', 'awesome-support' ) );
		}
		
		
		/**
		 * Find out who's the user to notify
		 */
		$recipient_email = $this->recipient_email;
		
		
		/**
		 * Get the sender information
		 */
		$sender      = $this->get_sender();
		$from_name   = $sender['from_name'];
		$from_email  = $sender['from_email'];
		$reply_name  = $sender['reply_name'];
		$reply_email = $sender['reply_email'];

		/**
		 * Get e-mail subject
		 *
		 * @var  string
		 */
		
		$subject = stripslashes( $this->get_subject( $case ) );
		
		
		

		/**
		 * Get the e-mail body and filter it before the template is being applied
		 *
		 * @var  string
		 */
		$body = apply_filters( 'wpas__user_email_notification_body_before_template', $this->get_body( $case ), $case, $this->user_id );

		/**
		 * Filter the e-mail body after the template has been applied
		 *
		 * @since 5.1.1
		 * @var string
		 */
		$body = apply_filters( 'wpas__user_email_notification_body_after_template', $this->get_formatted_email( $body ), $case, $this->user_id );

		/**
		 * Prepare e-mail headers
		 * 
		 * @var array
		 */
		$headers = array(
			"MIME-Version: 1.0",
			"Content-type: text/html; charset=utf-8",
			"From: $from_name <$from_email>",
			"Reply-To: $reply_name <$reply_email>",
			// "Subject: $subject",
			"X-Mailer: Awesome Support/" . WPAS_VERSION,
		);

		/**
		 * Merge all the e-mail variables and apply the wpas__user_email_notifications_email filter.
		 */
		$email = apply_filters( 'wpas__user_email_notifications_email', array(
			'recipient_email' => $recipient_email,
			'subject'         => $subject,
			'body'            => $body,
			'headers'         => $headers,
			'attachments'     => ''
			),
			$case,
			$this->user_id
		);
		
		
		
		$mail = false;
		
			
		$email_headers = $email['headers'];
		
		$to_email = $email['recipient_email'];
		
		if( wp_mail( $to_email, $email['subject'], $email['body'], $email_headers ) ) {
			$mail = true;
		}
		
		
		return $mail;

	}

}