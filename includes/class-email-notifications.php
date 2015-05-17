<?php
/**
 * E-Mail Notifications.
 *
 * This class handles all e-mail notifications. One instance of this class
 * relates to one and one only post, but can handle multiple notifications
 * for the same post.
 *
 * The available notifications can be extended with the use of a few filters
 * available throughout the class and the dispatch of e-mails is handled by
 * the pluggable function wp_mail(). It is recommended to use a proper SMTP
 * server for e-mail routing in order to ensure a safe delivery.
 *
 * @package   Awesome Support
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */
class WPAS_Email_Notification {

	/**
	 * ID of the post to notify about.
	 * 
	 * @var integer
	 */
	private $post_id;

	/**
	 * ID of the related ticket.
	 *
	 * The ticket ID can be the same as the post ID if the provided post ID
	 * is a new ticket. Otherwise $post_id is a reply.
	 *
	 * @var  integer
	 */
	private $ticket_id;

	/**
	 * Class constructor.
	 * 
	 * @param integer $post_id ID of the post to notify about
	 */
	public function __construct( $post_id ) {

		/* Make sure the given post belongs to our plugin. */
		if ( !in_array( get_post_type( $post_id ), array( 'ticket', 'ticket_reply' ) ) ) {
			return new WP_Error( 'incorrect_post_type', __( 'The post ID provided does not match any of the plugin post types', 'wpas' ) );
		}

		/* Set the e-mail content type to HTML */
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_mime_type' ) );

		/* Set the post ID */
		$this->post_id = $post_id;
		
		/**
		 * Define the ticket ID, be it $post_id or not.
		 */
		if ( 'ticket' === get_post_type( $post_id ) ) {
			$this->ticket_id = $post_id;
		} else {
			$reply           = $this->get_reply();
			$this->ticket_id = $reply->post_parent;
		}

	}

	public function __destruct() {

		/**
		 * Reset the e-mail content type to text as recommended by WordPress
		 *
		 * @since  3.1.1
		 * @link   http://codex.wordpress.org/Function_Reference/wp_mail
		 */
		add_filter( 'wp_mail_content_type', array( $this, 'set_text_mime_type' ) );

	}

	/**
	 * Ge the post object for the reply.
	 *
	 * @since  3.0.2
	 * @return boolean|object The reply object if there is a reply, false otherwise
	 */
	public function get_reply() {

		if ( isset( $this->reply ) ) {
			return $this->reply;
		}

		if ( 'ticket_reply' !== get_post_type( $this->post_id ) ) {
			return false;
		}

		$this->reply = get_post( $this->post_id );

		return $this->reply;

	}

	/**
	 * Ge the post object for the ticket.
	 *
	 * @since  3.0.2
	 * @return boolean|object The ticket object if there is a reply, false otherwise
	 */
	public function get_ticket() {

		if ( isset( $this->ticket ) ) {
			return $this->ticket;
		}

		if ( 'ticket' !== get_post_type( $this->ticket_id ) ) {
			return false;
		}

		$this->ticket = get_post( $this->ticket_id );

		return $this->ticket;

	}

	/**
	 * Check if the requested notification is active.
	 *
	 * E-mail notifications can be enabled or disabled on a
	 * per-case basis by the user in the plugin settings.
	 * We need to check that the requested notification hasn't been
	 * disabled by the user before sending it out.
	 *
	 * @since  3.0.2
	 * @param  string  $case The notification case requested
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

		return boolval( wpas_get_option( $option, false ) );

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
	 * @since  3.0.2
	 * @return array Array of the available cases
	 */
	public function get_cases() {

		$cases = array(
			'submission_confirmation',
			'new_ticket_assigned',
			'agent_reply',
			'client_reply',
			'ticket_closed',
		);

		return apply_filters( 'wpas_email_notifications_cases', $cases );

	}

	/**
	 * Get notification cases active option name.
	 *
	 * @since  3.0.2
	 * @return array Array of available cases with their activation option name
	 */
	private function cases_active_option() {

		$cases                            = $this->get_cases();
		$cases['submission_confirmation'] = 'enable_confirmation';
		$cases['new_ticket_assigned']     = 'enable_assignment';
		$cases['agent_reply']             = 'enable_reply_agent';
		$cases['client_reply']            = 'enable_reply_client';
		$cases['ticket_closed']           = 'enable_closed';

		return apply_filters( 'wpas email_notifications_cases_active_option', $cases );
	}

	/**
	 * Get sender data.
	 *
	 * @since  3.0.2
	 * @return array Array containing the sender name and e-mail as well as the reply address
	 */
	public function get_sender() {

		if ( isset( $this->data ) && !empty( $this->data ) ) {
			return $this->data;
		}

		$data = array(
			'from_name'   => wpas_get_option( 'sender_name', get_bloginfo( 'name' ) ),
			'from_email'  => wpas_get_option( 'sender_email', get_bloginfo( 'admin_email' ) ),
			'reply_email' => wpas_get_option( 'reply_email', get_bloginfo( 'admin_email' ) ),
		);

		$data['reply_name']  = $data['from_name'];

		$this->data = apply_filters( 'wpas_email_notifications_sender_data', $data );

		return $this->data;

	}

	/**
	 * Convert tags within a string.
	 *
	 * Takes a string (subject or body) and replace the tags
	 * with their current value if any.
	 *
	 * @since  3.0.0
	 * @param  string $contents String to convert tags from
	 * @return string           String with tags converted into their corresponding value
	 */
	public function fetch( $contents ) {

		$tags = $this->get_tags_values();

		foreach ( $tags as $tag ) {

			$id       = $tag['tag'];
			$value    = $tag['value'];
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
	 * @since  3.0.2
	 * @return array Array of tags with their description
	 */
	public static function get_tags() {

		$tags = array(
			array(
				'tag' 	=> '{ticket_id}',
				'desc' 	=> __( 'Convert into ticket ID', 'wpas' )
			),
			array(
				'tag' 	=> '{site_name}',
				'desc' 	=> __( 'Convert into website name', 'wpas' )
			),
			array(
				'tag' 	=> '{agent_name}',
				'desc' 	=> __( 'Convert into agent name', 'wpas' )
			),
			array(
				'tag' 	=> '{agent_email}',
				'desc' 	=> __( 'Convert into agent e-mail address', 'wpas' )
			),
			array(
				'tag' 	=> '{client_name}',
				'desc' 	=> __( 'Convert into client name', 'wpas' )
			),
			array(
				'tag' 	=> '{client_email}',
				'desc' 	=> __( 'Convert into client e-mail address', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_title}',
				'desc' 	=> __( 'Convert into current ticket title', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_link}',
				'desc' 	=> __( 'Displays a link to public ticket', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_url}',
				'desc' 	=> __( 'Displays the URL <strong>only</strong> (not a link link) to public ticket', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_admin_link}',
				'desc' 	=> __( 'Displays a link to ticket details in admin (for agents)', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_admin_url}',
				'desc' 	=> __( 'Displays the URL <strong>only</strong> (not a link link) to ticket details in admin (for agents)', 'wpas' )
			),
			array(
				'tag' 	=> '{date}',
				'desc' 	=> __( 'Convert into current date', 'wpas' )
			),
			array(
				'tag' 	=> '{admin_email}',
				'desc' 	=> sprintf( __( 'Convert into WordPress admin e-mail (<em>currently: %s</em>)', 'wpas' ), get_bloginfo( 'admin_email' ) )
			),
			array(
				'tag' 	=> '{message}',
				'desc' 	=> __( 'Convert into ticket content or reply content', 'wpas' )
			)
		);

		return apply_filters( 'wpas_email_notifications_template_tags', $tags );

	}

	/**
	 * Get tags and their value in the current context.
	 *
	 * @since  3.0.0
	 * @return array Array of tag / value pairs
	 */
	public function get_tags_values() {

		/* Get all available tags */
		$tags = self::get_tags();

		/* This is where we save the tags with their new value */
		$new = array();

		/* Get the involved users' information */
		$agent  = get_user_by( 'id', intval( get_post_meta( $this->ticket_id, '_wpas_assignee', true ) ) );
		$client = get_user_by( 'id', $this->get_ticket()->post_author );

		/* Get the ticket links */
		$url_public  = get_permalink( $this->get_ticket()->ID );
		$url_private = add_query_arg( array( 'post' => $this->ticket_id, 'action' => 'edit' ), admin_url( 'post.php' ) );

		/* Add the tag value in the current context */
		foreach ( $tags as $key => $tag ) {

			$name = trim( $tag['tag'], '{}' );

			switch ( $name ) {

				/* Ticket ID */
				case 'ticket_id';
					$tag['value'] = $this->ticket_id;
					break;

				/* Name of the website */
				case 'site_name':
					$tag['value'] = get_bloginfo( 'name' );
					break;

				/* Name of the agent assigned to this ticket */
				case 'agent_name':
					$tag['value'] = $agent->display_name;
					break;

				/* E-mail of the agent assigned to this ticket */
				case 'agent_email':
					$tag['value'] = $agent->user_email;
					break;

				case 'client_name':
					$tag['value'] = $client->display_name;
					break;

				case 'client_email':
					$tag['value'] = $client->user_email;
					break;

				case 'ticket_title':
					$tag['value'] = wp_strip_all_tags( $this->get_ticket()->post_title );
					break;

				case 'ticket_link':
					$tag['value'] = '<a href="' . $url_public . '">' . $url_public . '</a>';
					break;

				case 'ticket_url':
					$tag['value'] = $url_public;
					break;

				case 'ticket_admin_link':
					$tag['value'] = '<a href="' . $url_private . '">' . $url_private . '</a>';
					break;

				case 'ticket_admin_url':
					$tag['value'] = $url_private;
					break;

				case 'date':
					$tag['value'] = date( get_option( 'date_format' ) );
					break;

				case 'admin_email':
					$tag['value'] = get_bloginfo( 'admin_email' );
					break;

				case 'message':
					$tag['value'] = $this->ticket_id === $this->post_id ? $this->get_ticket()->post_content : $this->get_reply()->post_content;
					break;


			}

			array_push( $new, $tag );

		}

		/* Replace the valueless tags array by the new one */
		$tags = apply_filters( 'wpas_email_notifications_tags_values', $new, $this->post_id );

		return $tags;

	}

	/**
	 * Get e-mail subject.
	 *
	 * @since  3.0.2
	 * @return string E-mail subject
	 */
	private function get_subject( $case ) {
		return apply_filters( 'wpas_email_notifications_subject', $this->get_content( 'subject', $case ), $this->post_id );
	}

	/**
	 * Get e-mail body.
	 *
	 * @since  3.0.2
	 * @return string E-mail body
	 */
	private function get_body( $case ) {
		return apply_filters( 'wpas_email_notifications_body', $this->get_content( 'content', $case ), $this->post_id );
	}

	/**
	 * Get e-mail content.
	 *
	 * Get the content for the given part.
	 *
	 * @since  3.0.2
	 * @param  string $part Part of the e-mail to retrieve
	 * @param  string $case Which notification is requested
	 * @return string       The content with tags converted into their values
	 */
	private function get_content( $part, $case ) {

		if ( !in_array( $part, array( 'subject', 'content' ) ) ) {
			return false;
		}

		/* Set the output value */
		$value = '';

		switch ( $case ) {

			case 'submission_confirmation':
				$value = wpas_get_option( "{$part}_confirmation", "" );
				break;

			case 'new_ticket_assigned':
				$value = wpas_get_option( "{$part}_assignment", "" );
				break;

			case 'agent_reply':
				$value = wpas_get_option( "{$part}_reply_agent", "" );
				break;

			case 'client_reply':
				$value = wpas_get_option( "{$part}_reply_client", "" );
				break;

			case 'ticket_closed':
				$value = wpas_get_option( "{$part}_closed", "" );
				break;

		}

		return $this->fetch( apply_filters( 'wpas_email_notifications_pre_fetch_' . $part, $value, $this->post_id ) );

	}

	/**
	 * Set the e-mail content type to HTML.
	 *
	 * @since  3.1.1
	 *
	 * @param  string $content_type Current e-mail content type
	 *
	 * @return string               HTML content type
	 */
	public function set_html_mime_type( $content_type ) {
		return 'text/html';
	}

	/**
	 * Set the e-mail content type to plain text.
	 *
	 * @since  3.1.1
	 *
	 * @param  string $content_type Current e-mail content type
	 *
	 * @return string               Text content type
	 */
	public function set_text_mime_type( $content_type ) {
		return 'text/plain';
	}

	/**
	 * Send out the e-mail notification.
	 *
	 * @since  3.0.2
	 * @param  string         $case The notification case
	 * @return boolean|object       True if the notification was sent, WP_Error otherwise
	 */
	public function notify( $case ) {

		if ( !$this->notification_exists( $case ) ) {
			return new WP_Error( 'unknown_notification', __( 'The requested notification does not exist', 'wpas' ) );
		}

		if ( !$this->is_active( $case ) ) {
			return new WP_Error( 'disabled_notification', __( 'The requested notification is disabled', 'wpas' ) );
		}

		/**
		 * Find out who's the user to notify
		 */
		switch ( $case ) {
			case 'submission_confirmation':
			case 'agent_reply':
			case 'ticket_closed':
				$user = get_user_by( 'id', $this->get_ticket()->post_author );
				break;

			case 'new_ticket_assigned':
			case 'client_reply':
				$user = get_user_by( 'id', intval( get_post_meta( $this->ticket_id, '_wpas_assignee', true ) ) );
				break;
		}

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
		 * @var  stirng
		 */
		$subject = $this->get_subject( $case );

		/**
		 * Get the e-mail body
		 *
		 * @var  string
		 */
		$body = $this->get_body( $case );

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
		 * Merge all the e-mail variables and apply the wpas_email_notifications_email filter.
		 */
		$email = apply_filters( 'wpas_email_notifications_email', array(
			'recipient_email' => $user->user_email,
			'subject'         => $subject,
			'body'            => $body,
			'headers'         => $headers,
			'attachments'     => ''
			)
		);

		$mail = wp_mail( $email['recipient_email'], $email['subject'], $email['body'], $email['headers'] );

		return $mail;

	}

}

/**
 * Wrapper function to trigger an e-mail notification.
 *
 * @since  3.0.2
 * @param  integer         $post_id ID of the post to notify about
 * @param  string|array    $cases   The case(s) to notify for
 * @return boolean|object           True if the notification was sent, WP_Error or false otherwise
 */
function wpas_email_notify( $post_id, $cases ) {

	$notify = new WPAS_Email_Notification( $post_id );
	$error  = false;

	if ( is_wp_error( $notify ) ) {
		return $notify;
	}

	if ( is_array( $cases ) ) {

		foreach ( $cases as $case ) {
			if ( !$notify->notify( $case ) ) {
				$error = true;
			}
		}

		return true === $error ? false : true;

	} else {
		return $notify->notify( $cases );
	}

}