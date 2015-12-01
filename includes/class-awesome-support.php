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
