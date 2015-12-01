<?php
/**
 * Awesome Support.
 *
 * @package   Awesome_Support
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

/**
 * Plugin public class.
 */
class Awesome_Support_Old {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			/**
			 * Load internal methods.
			 */
			add_action( 'init',                           array( $this, 'init' ),                            11, 0 ); // Register main post type
		}
	}

	/**
	 * Actions run on plugin initialization.
	 *
	 * A certain number of things can possibly run after
	 * the plugin initialized. Those actions are fired from here
	 * if the trigger is present.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function init() {

		/**
		 * Run custom actions.
		 *
		 * The plugin can run a number of custom actions triggered by a URL parameter.
		 * If the $action parameter is set in the URL we run this method.
		 *
		 * @since  3.0.0
		 */
		if ( isset( $_GET['action'] ) ) {
			add_action( 'wp', array( $this, 'custom_actions' ) );
		}

		/**
		 * Save a new reply.
		 *
		 * This adds a new reply to an existing ticket. The ticket
		 * can possibly be closed by the user in which case we update
		 * the post meta if the reply submission is successful.
		 *
		 * @since 3.0.0
		 */
		if ( isset( $_POST['wpas_user_reply'] ) ) {

			// Get parent ticket ID
			$parent_id = filter_input( INPUT_POST, 'ticket_id', FILTER_SANITIZE_NUMBER_INT );

			if ( 'ticket' !== get_post_type( $parent_id ) ) {
				wpas_add_error( 'reply_added_failed', __( 'Something went wrong. We couldn&#039;t identify your ticket. Please try again.', 'awesome-support' ) );
				wpas_redirect( 'reply_added_failed', get_permalink( $parent_id ) );
				exit;
			}

			// Define if the ticket must be closed
			$close = isset( $_POST['wpas_close_ticket'] ) ? true : false;

			if ( ! empty( $_POST['wpas_user_reply'] ) ) {

				/* Sanitize the data */
				$data = array( 'post_content' => wp_kses( $_POST['wpas_user_reply'], wp_kses_allowed_html( 'post' ) ) );

				/* Add the reply */
				$reply_id = wpas_add_reply( $data, $parent_id );

			}

			/* Possibly close the ticket */
			if ( $close ) {

				wpas_close_ticket( $parent_id );

				// Redirect now if no reply was posted
				if ( ! isset( $reply_id ) ) {
					wpas_add_notification( 'ticket_closed', __( 'The ticket was successfully closed', 'awesome-support' ) );
					wpas_redirect( 'ticket_closed', get_permalink( $parent_id ) );
					exit;
				}

			}

			if ( isset( $reply_id ) ) {

				if ( false === $reply_id ) {
					wpas_add_error( 'reply_added_failed', __( 'Your reply could not be submitted for an unknown reason.', 'awesome-support' ) );
					wpas_redirect( 'reply_added_failed', get_permalink( $parent_id ) );
					exit;
				} else {

					if ( $close ) {
						wpas_add_notification( 'reply_added_closed', __( 'Thanks for your reply. The ticket is now closed.', 'awesome-support' ) );
					} else {
						wpas_add_notification( 'reply_added', __( 'Your reply has been submitted. Your agent will reply ASAP.', 'awesome-support' ) );
					}

					if ( false !== $link = wpas_get_reply_link( $reply_id ) ) {
						wpas_redirect( 'reply_added', $link );
						exit;
					}
				}

			}
		}

	}

	/**
	 * Run pre-defined actions.
	 *
	 * Specific actions can be performed on page load.
	 * Those actions are triggered by a URL parameter ($action).
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function custom_actions() {

		if ( !isset( $_GET['action'] ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['action'] );

		switch( $action ) {

			case 'reopen':

				if ( isset( $_GET['ticket_id'] ) ) {

					$ticket_id = filter_input( INPUT_GET, 'ticket_id', FILTER_SANITIZE_NUMBER_INT );

					if ( ! wpas_can_submit_ticket( $ticket_id ) && ! current_user_can( 'edit_ticket' ) ) {
						wpas_add_error( 'cannot_reopen_ticket', __( 'You are not allowed to re-open this ticket', 'awesome-support' ) );
						wpas_redirect( 'ticket_reopen', wpas_get_tickets_list_page_url() );
						exit;
					}

					wpas_reopen_ticket( $ticket_id );
					wpas_add_notification( 'ticket_reopen', __( 'The ticket has been successfully re-opened.', 'awesome-support' ) );
					wpas_redirect( 'ticket_reopen', wp_sanitize_redirect( get_permalink( $ticket_id ) ) );
					exit;

				}

			break;

		}

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}
