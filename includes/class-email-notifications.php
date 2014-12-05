<?php
/**
 * E-Mail Notifications.
 *
 * @package   Awesome_Support
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */
class WPAS_Email_Notification {

	public function __construct( $post_id, $case = false ) {

		/* If $case if false we don't know what e-mail to send. */
		if ( false === $case ) {
			return;
		}

		$this->post_id   = $post_id;
		$this->post      = get_post( $this->post_id );
		$this->ticket_id = ( 0 === intval( $this->post->post_parent ) ) ? $post_id : intval( $this->post->post_parent );
		$this->case      = $case;

		/* Prepare and send the message. */
		$this->init();

	}

	public function init() {

		switch( $this->case ) {

			case 'submission_confirmation':

				$enable         = boolval( wpas_get_option( 'enable_confirmation', true ) );
				$this->subject  = wpas_get_option( 'subject_confirmation' );
				$this->contents = wpas_get_option( 'content_confirmation' );
				$user           = get_user_by( 'id', $this->post->post_author );
				$this->to_name  = $user->user_nicename;
				$this->to_email = $user->user_email;

			break;

			case 'new_ticket_assigned':

				$enable         = boolval( wpas_get_option( 'enable_assignment', true ) );
				$this->subject  = wpas_get_option( 'subject_assignment' );
				$this->contents = wpas_get_option( 'content_assignment' );
				$user           = get_user_by( 'id', intval( get_post_meta( $this->ticket_id, '_assigned_agent' ) ) );
				$this->to_name  = $user->user_nicename;
				$this->to_email = $user->user_email;

			break;

			case 'agent_reply':

				$enable         = boolval( wpas_get_option( 'enable_reply_agent', true ) );
				$this->subject  = wpas_get_option( 'subject_reply_agent' );
				$this->contents = wpas_get_option( 'content_reply_agent' );
				$user           = get_user_by( 'id', $this->post->post_author );
				$this->to_name  = $user->data->user_nicename;
				$this->to_email = $user->data->user_email;

			break;

			case 'client_reply':

				$enable         = boolval( wpas_get_option( 'enable_reply_client', true ) );
				$this->subject  = wpas_get_option( 'subject_reply_client' );
				$this->contents = wpas_get_option( 'content_reply_client' );
				$user           = get_user_by( 'id', intval( get_post_meta( $this->ticket_id, '_assigned_agent' ) ) );
				$this->to_name  = $user->data->user_nicename;
				$this->to_email = $user->data->user_email;

			break;

			case 'ticket_closed':

				$enable         = boolval( wpas_get_option( 'enable_closed', true ) );
				$this->subject  = wpas_get_option( 'subject_closed' );
				$this->contents = wpas_get_option( 'content_closed' );
				$user           = get_user_by( 'id', $this->post->post_author );
				$this->to_name  = $user->data->user_nicename;
				$this->to_email = $user->data->user_email;

			break;

		}

		if ( !isset( $enable ) || false === $enable ) {
			return;
		}

		$this->prepare();

	}

	public function prepare() {

		$this->from_name   = apply_filters( 'wpas_email_from_name',      wpas_get_option( 'sender_name', get_bloginfo( 'name' ) ) );
		$this->from_email  = apply_filters( 'wpas_email_from_email',     wpas_get_option( 'sender_email', get_bloginfo( 'admin_email' ) ) );
		$this->reply_name  = apply_filters( 'wpas_email_reply_to_name',  $this->from_name );
		$this->reply_email = apply_filters( 'wpas_email_reply_to_email', $this->from_email );
		$this->subject     = $this->fetch( $this->subject );
		$this->body        = $this->fetch( $this->contents );

		$this->send();

	}

	public function fetch( $contents ) {

		$tags = $this->get_tags();

		foreach ( $tags as $tag ) {

			$id    = $tag['tag'];
			$value = $tag['value'];

			$contents = str_replace( $id, $value, $contents );

		}

		return $contents;

	}

	public function send() {

		$headers   = array();
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/html; charset=utf-8";
		$headers[] = "From: {$this->from_name} <{$this->from_email}>";
		// $headers[] = "Bcc: JJ Chong <bcc@domain2.com>";
		$headers[] = "Reply-To: {$this->reply_name} <{$this->reply_email}>";
		$headers[] = "Subject: {$this->subject}";
		$headers[] = "X-Mailer: PHP/" . phpversion();

		return wp_mail( $this->to_email, $this->subject, $this->body, implode( "\r\n", $headers ) );

	}

	public function get_tags() {

		$ticket_id    = '';
		$ticket_title = '';
		$agent_name   = '';
		$agent_email  = '';
		$client_name  = '';
		$client_email = '';

		if ( isset( $this->post ) && is_object( $this->post ) ) {

			$ticket_id    = $this->ticket_id;
			$agent        = get_user_by( 'id', intval( get_post_meta( $ticket_id, '_wpas_assignee' ) ) );
			$agent_name   = $agent->user_nicename;
			$agent_email  = $agent->user_email;
			$client       = get_user_by( 'id', $this->post->post_author );
			$client_name  = $client->user_nicename;
			$client_email = $client->user_email;

			if ( isset( $this->post_id ) && $this->post_id === $this->ticket_id ) {
				$ticket_title = $this->post->post_title;
			} else {
				$post         = get_post( $this->ticket_id );
				$ticket_title = $post->post_title;
			}
		}

		$message           = isset( $this->post ) ? $this->post->post_content : '';
		$ticket_url        = !empty( $ticket_id ) ? esc_url( get_permalink( $ticket_id ) ) : '';
		$ticket_link       = !empty( $ticket_url ) ? "<a href='$ticket_url'>$ticket_url</a>" : '';
		$ticket_admin_url  = !empty( $ticket_id ) ? esc_url( add_query_arg( array( 'post' => $ticket_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) ) : '';
		$ticket_admin_link = !empty( $ticket_admin_url ) ? "<a href='$ticket_admin_url'>$ticket_admin_url</a>" : '';

		/* List tags */
		$tags = array(
			array(
				'tag' 	=> '{ticket_id}',
				'value' => $ticket_id,
				'desc' 	=> __( 'Convert into ticket ID', 'wpas' )
			),
			array(
				'tag' 	=> '{site_name}',
				'value' => get_bloginfo( 'name' ),
				'desc' 	=> __( 'Convert into website name', 'wpas' )
			),
			array(
				'tag' 	=> '{agent_name}',
				'value' => $agent_name,
				'desc' 	=> __( 'Convert into agent name', 'wpas' )
			),
			array(
				'tag' 	=> '{agent_email}',
				'value' => $agent_email,
				'desc' 	=> __( 'Convert into agent e-mail address', 'wpas' )
			),
			array(
				'tag' 	=> '{client_name}',
				'value' => $client_name,
				'desc' 	=> __( 'Convert into client name', 'wpas' )
			),
			array(
				'tag' 	=> '{client_email}',
				'value' => $client_email,
				'desc' 	=> __( 'Convert into client e-mail address', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_title}',
				'value' => $ticket_title,
				'desc' 	=> __( 'Convert into current ticket title', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_link}',
				'value' => $ticket_link,
				'desc' 	=> __( 'Displays a link to public ticket', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_url}',
				'value' => $ticket_url,
				'desc' 	=> __( 'Displays the URL <strong>only</strong> (not a link link) to public ticket', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_admin_link}',
				'value' => $ticket_admin_link,
				'desc' 	=> __( 'Displays a link to ticket details in admin (for agents)', 'wpas' )
			),
			array(
				'tag' 	=> '{ticket_admin_url}',
				'value' => $ticket_admin_url,
				'desc' 	=> __( 'Displays the URL <strong>only</strong> (not a link link) to ticket details in admin (for agents)', 'wpas' )
			),
			array(
				'tag' 	=> '{date}',
				'value' => date( get_option( 'date_format' ) ),
				'desc' 	=> __( 'Convert into current date', 'wpas' )
			),
			array(
				'tag' 	=> '{admin_email}',
				'value' => get_bloginfo( 'admin_email' ),
				'desc' 	=> sprintf( __( 'Convert into WordPress admin e-mail (<em>currently: %s</em>)', 'wpas' ), get_bloginfo( 'admin_email' ) )
			),
			array(
				'tag' 	=> '{message}',
				'value' => $message,
				'desc' 	=> __( 'Convert into ticket content or reply content', 'wpas' )
			)
		);

		return apply_filters( 'wpas_email_template_tags', $tags );

	}

}