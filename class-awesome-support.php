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
class Awesome_Support {

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

		/**
		 * Ticket post type slug.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		$this->slug = 'ticket';

		add_action( 'plugins_loaded',                 array( 'WPAS_Ticket_Post_Type', 'get_instance' ), 11 );
		add_action( 'pre_user_query',                 'wpas_randomize_uers_query' );                  // Alter the user query to randomize the results
		add_action( 'wp',                             array( $this, 'get_replies_object' ) );         // Generate the object used for the custom loop for displaying ticket replies
		add_action( 'wpmu_new_blog',                  array( $this, 'activate_new_site' ) );          // Activate plugin when new blog is added
		add_action( 'init',                           array( $this, 'load_plugin_textdomain' ) );     // Load the plugin textdomain
		add_action( 'init',                           array( $this, 'init' ), 10, 0 );                // Register main post type
		add_action( 'admin_bar_menu',                 array( $this, 'toolbar_tickets_link' ), 999 );  // Add a link to agent's tickets in the toolbar
		add_action( 'wp_print_styles',                array( $this, 'enqueue_styles' ) );             // Load public-facing style sheets
		add_action( 'wp_print_scripts',               array( $this, 'enqueue_scripts' ) );            // Load public-facing JavaScripts
		add_action( 'template_redirect',              array( $this, 'redirect_archive' ) );
		add_action( 'wpas_after_registration_fields', array( $this, 'terms_and_conditions_checkbox' ), 10, 3 );// Load the terms and conditions in a hidden div in the footer
		add_action( 'wpas_after_template',            array( $this, 'terms_and_conditions_modal' ), 10, 3 );// Load the terms and conditions in a hidden div in the footer
		add_filter( 'template_include',               array( $this, 'template_include' ), 10, 1 );

		/* Hook all e-mail notifications */
		add_action( 'wpas_open_ticket_after',  array( $this, 'notify_confirmation' ), 10, 2 );
		add_action( 'wpas_add_reply_after',    array( $this, 'notify_reply' ), 10, 2 );
		add_action( 'wpas_after_close_ticket', array( $this, 'notify_close' ), 10, 1 );

		/**
		 * Modify the ticket single page content.
		 *
		 * wpas_single_ticket() is located in includes/functions-templating.php
		 *
		 * @since  3.0.0
		 */
		add_filter( 'the_content', 'wpas_single_ticket', 10, 1 );
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
		 * Log user in.
		 *
		 * If we have a login in the post data we try to log the user in.
		 * The login process relies on the WordPress core functions. If the login
		 * is successfull the user is redirected to the page he was requesting,
		 * otherwise the standard WordPress error messages are returned.
		 *
		 * @since 3.0.0
		 */
		if ( isset( $_POST['log'] ) ) {
			add_action( 'wp', 'wpas_try_login' );
		}

		/**
		 * Register a new account.
		 *
		 * If wpas_registration is passed we trigger the account registration function.
		 * The registration function will do a certain number of checks and if all of them
		 * are successful, a new user is created using the WordPress core functions.
		 *
		 * The reason why we are not using a simpler process is to keep full control over
		 * what's returned to the user and where the user is returned.
		 *
		 * @since 3.0.0
		 */
		if ( isset( $_POST['wpas_registration'] ) ) {
			add_action( 'wp', 'wpas_register_account' );
		}

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
		 * Open a new ticket.
		 *
		 * If a ticket title is passed in the post we trigger the function that adds
		 * new tickets. The function does a certain number of checks and has several
		 * action hooks and filters. Post-insertion actions like adding post metas
		 * and redirecting the user are run from here.
		 *
		 * @since  3.0.0
		 */
		if ( isset( $_POST['wpas_title'] ) ) {

			$ticket_id = wpas_open_ticket( $_POST );

			/* Submission failure */
			if( false === $ticket_id ) {

				/* Save the input */
				wpas_save_values();

				/**
				 * Redirect to the newly created ticket
				 */
				wpas_redirect( 'ticket_added_failed', add_query_arg( array( 'message' => 6 ), get_permalink( $submit ) ), $submit );
				exit;

			}

			/* Submission succeeded */
			else {

				/**
				 * Empty the temporary sessions
				 */
				unset( $_SESSION['wpas_submission_form'] );
				unset( $_SESSION['wpas_submission_error'] );
				
				/**
				 * Redirect to the newly created ticket
				 */
				wpas_redirect( 'ticket_added', get_permalink( $ticket_id ), $ticket_id );
				exit;
				
			}
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

			/**
			 * Define if the reply can be submitted empty or not.
			 *
			 * @since  3.0.0
			 * @var boolean
			 */
			$can_submit_empty = apply_filters( 'wpas_can_reply_be_empty', false );

			if ( empty( $_POST['wpas_user_reply'] ) && false === $can_submit_empty ) {
				wpas_redirect( 'reply_not_added', add_query_arg( array( 'message' => wpas_create_notification( __( 'You cannot submit an empty reply.', 'wpas' ) ) ), get_permalink( $parent_id ) ), $parent_id );
				exit;
			}

			$parent_id = intval( $_POST['ticket_id'] );

			/* Sanitize the data */
			$data = array( 'post_content' => wp_kses( $_POST['wpas_user_reply'], wp_kses_allowed_html( 'post' ) ) );

			/* Add the reply */
			$reply_id = wpas_add_reply( $data, $parent_id );

			/* Possibly close the ticket */
			if ( isset( $_POST['wpas_close_ticket'] ) && false !== $reply_id ) {
				update_post_meta( intval( $_POST['ticket_id'] ), '_wpas_status', 'closed', 'open' );
				wpas_log( intval( $_POST['ticket_id'] ), __( 'The ticket was closed.', 'wpas' ) );
				do_action( 'wpas_after_close_ticket', intval( $_POST['ticket_id'] ) );
			}

			if ( false === $reply_id ) {
				wpas_redirect( 'reply_added_failed', add_query_arg( array( 'message' => '7' ), get_permalink( $parent_id ) ) );
				exit;
			} else {

				/**
				 * Delete the activity transient.
				 */
				delete_transient( "wpas_activity_meta_post_$parent_id" );

				wpas_redirect( 'reply_added', add_query_arg( array( 'message' => '8' ), get_permalink( $parent_id ) ) . "#reply-$reply_id", $parent_id );
				exit;
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
					update_post_meta( intval( $_GET['ticket_id'] ), '_wpas_status', 'open', 'closed' );
					wpas_log( intval( $_GET['ticket_id'] ), __( 'The ticket was re-opened.', 'wpas' ) );
				}

				wpas_redirect( 'ticket_reopen', add_query_arg( array( 'message' => '9' ), get_permalink( intval( $_GET['ticket_id'] ) ) ), intval( $_GET['ticket_id'] ) );
				exit;

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

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		
		/**
		 * Full list of capabilities.
		 *
		 * This is the full list of capabilities
		 * that will be given to administrators.
		 * 
		 * @var array
		 */
		$full_cap = apply_filters( 'wpas_user_capabilities_full', array(
			'view_ticket',
			'view_private_ticket',
			'edit_ticket',
			'edit_other_ticket',
			'edit_private_ticket',
			'delete_ticket',
			'delete_reply',
			'delete_private_ticket',
			'delete_other_ticket',
			'assign_ticket',
			'close_ticket',
			'reply_ticket',
			'settings_tickets',
			'ticket_taxonomy',
			'create_ticket',
			'attach_files'
		) );

		/**
		 * Partial list of capabilities.
		 *
		 * A partial list of capabilities given to agents in addition to
		 * the author capabilities. Agents should be used if no other access
		 * than the tickets is required.
		 * 
		 * @var array
		 */
		$agent_cap = apply_filters( 'wpas_user_capabilities_agent', array(
			'view_ticket',
			'view_private_ticket',
			'edit_ticket',
			'edit_other_ticket',
			'edit_private_ticket',
			'assign_ticket',
			'close_ticket',
			'reply_ticket',
			'create_ticket',
			'delete_reply',
			'attach_files'
		) );

		/**
		 * Very limited list of capabilities for the clients.
		 */
		$client_cap = apply_filters( 'wpas_user_capabilities_client', array(
			'view_ticket',
			'create_ticket',
			'close_ticket',
			'reply_ticket',
			'attach_files'
		) );


		/* Get roles to copy capabilities from */
		$editor     = get_role( 'editor' );
		$author     = get_role( 'author' );
		$subscriber = get_role( 'subscriber' );
		$admin      = get_role( 'administrator' );

		/* Add the new roles */
		$manager = add_role( 'wpas_manager',         __( 'Support Superviser', 'wpas' ), $editor->capabilities );     // Has full capabilities for the plugin in addition to editor capabilities
		$tech    = add_role( 'wpas_support_manager', __( 'Support Manager', 'wpas' ),    $subscriber->capabilities ); // Has full capabilities for the plugin only
		$agent   = add_role( 'wpas_agent',           __( 'Support Agent', 'wpas' ),      $author->capabilities );     // Has limited capabilities for the plugin in addition to author's capabilities
		$client  = add_role( 'wpas_user',            __( 'Support User', 'wpas' ),       $subscriber->capabilities ); // Has posting & replying capapbilities for the plugin in addition to subscriber's capabilities

		/**
		 * Add full capacities to admin roles
		 */
		foreach ( $full_cap as $cap ) {

			// Add all the capacities to admin in addition to full WP capacities
			if ( null != $admin )
				$admin->add_cap( $cap );
			
			// Add full plugin capacities to manager in addition to the editor capacities
			if ( null != $manager )
				$manager->add_cap( $cap );

			// Add full plugin capacities only to technical manager
			if ( null != $tech )
				$tech->add_cap( $cap );

		}

		/**
		 * Add limited capacities ot agents
		 */
		foreach ( $agent_cap as $cap ) {
			if ( null != $agent ) {
				$agent->add_cap( $cap );
			}
		}

		/**
		 * Add limited capacities to users
		 */
		foreach ( $client_cap as $cap ) {
			if ( null != $client ) {
				$client->add_cap( $cap );
			}
		}

		add_option( 'wpas_setup', 'pending' );
		add_option( 'wpas_redirect_about', true );
		add_option( 'wpas_support_products', 'pending' );
		add_option( 'wpas_db_version', WPAS_DB_VERSION );
		update_option( 'wpas_version', WPAS_VERSION );

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wpas', FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		if ( !is_admin() && wpas_is_plugin_page() ) {

			/* Load the theme stylesheet */
			$theme = wpas_get_theme();

			if ( file_exists( WPAS_PATH . "themes/$theme/css/style.css" ) && true === boolval( wpas_get_option( 'theme_stylesheet' ) ) ) {
				wp_enqueue_style( 'wpas-theme-styles', WPAS_URL . "themes/$theme/css/style.css", array(), WPAS_VERSION );
			} else {
				wp_enqueue_style( 'wpas-plugin-styles', WPAS_URL . 'assets/public/css/public.css', array(), WPAS_VERSION );
			}
		}
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( !is_admin() && wpas_is_plugin_page() ) {
			wp_enqueue_script( 'wpas-plugin-script', WPAS_URL . 'assets/public/js/public-dist.js', array( 'jquery' ), WPAS_VERSION, true );
		}
	}

	/**
	 * Construct the replies query.
	 *
	 * The replies query is used as a custom loop to display
	 * a ticket's replies in a clean way. The resulting object
	 * is made global as $wpas_replies.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function get_replies_object() {

		global $wp_query, $wpas_replies;

		if ( isset( $wp_query->post ) && 'ticket' === $wp_query->post->post_type ) {

			$args = array(
				'post_parent'            => $wp_query->post->ID,
				'post_type'              => 'ticket_reply',
				'post_status'            => array( 'read', 'unread' ),
				'order'                  => wpas_get_option( 'replies_order', 'ASC' ),
				'orderby'                => 'date',
				'posts_per_page'         => -1,
				'no_found_rows'          => false,
				'cache_results'          => false,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				
			);

			$wpas_replies = new WP_Query( $args );

		}

	}

	/**
	 * Send e-mail confirmations.
	 *
	 * Sends an e-mail confirmation to the client
	 * and warns the agent that a new ticket has been assigned.
	 *
	 * @since  3.0.0
	 * @param  integer $ticket_id ID of the new ticket
	 * @param  integer $agent_id  ID of the agent who's assigned
	 * @param  array $data        Ticket data
	 * @return void
	 */
	public function notify_confirmation( $ticket_id, $data ) {
		new WPAS_Email_Notification( $ticket_id, 'submission_confirmation' );
		new WPAS_Email_Notification( $ticket_id, 'new_ticket_assigned' );
	}

	public function notify_reply( $reply_id, $data ) {

		/* If the ID is set it means we're updating a post and NOT creating. In this case no notification. */
		if ( isset( $data['ID'] ) ) {
			return;
		}

		$case = current_user_can( 'edit_ticket' ) ? 'agent_reply' : 'client_reply';
		new WPAS_Email_Notification( $reply_id, $case );
	}

	public function notify_close( $ticket_id ) {
		new WPAS_Email_Notification( $ticket_id, 'ticket_closed' );
	}

	/**
	 * Redirect ticket archive page.
	 *
	 * We don't use the archive page to display the ticket
	 * so let's redirect it to the user's tickets list instead.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function redirect_archive() {

		if ( is_post_type_archive( 'ticket' ) ) {
			wpas_redirect( 'archive_redirect', get_permalink( wpas_get_option( 'ticket_list' ) ) );
		}

	}

	/**
	 * Change ticket template.
	 *
	 * By default WordPress uses the single.php template
	 * to display the post type single page as a custom one doesn't exist.
	 * However we don't want all the meta that are usually displayed on a single.php
	 * template. For that reason we switch to the page.php template that usually
	 * doesn't contain all the post metas and author bio.
	 *
	 * @since  3.0.0
	 * @param  string $template Path to template
	 * @return string           Path to (possibly) new template
	 */
	public function template_include( $template ) {

		if ( !is_singular( 'ticket' ) ) {
			return $template;
		}

		$filename = explode( '/', $template );
		unset( $filename[count($filename)-1] ); // Remove the template name
		$filename = implode( '/', $filename );
		$filename = $filename . '/page.php';

		if ( file_exists( $filename ) ) {
			return $filename;
		} else {
			return $template;
		}

	}

	/**
	 * Load terms and conditions.
	 *
	 * Load the terms and conditions if any and if the user
	 * is on the submission page.
	 *
	 * @since  3.0.0
	 * @param  string  $name     Template name
	 * @param  string  $template Path to the template that was loaded
	 * @param  array   $args     Extra arguments passed when loading the template
	 * @return boolean           True if the modal is loaded, false otherwise
	 */
	public function terms_and_conditions_modal( $name, $template, $args ) {

		if ( 'registration' !== $name ) {
			return false;
		}

		global $post;

		$terms = wpas_get_option( 'terms_conditions', '' );

		if ( empty( $terms ) ) {
			return false;
		}

		echo '<div style="display: none;"><div id="wpas-modalterms">' . wp_kses_post( $terms ) . '</div></div>';

		return true;

	}

	/**
	 * Add the terms and conditions checkbox.
	 *
	 * Adds a checkbox to the registration form if there are
	 * terms and conditions set in the plugin settings.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function terms_and_conditions_checkbox() {
		if ( wpas_get_option( 'terms_conditions', false ) ): ?>
			<div class="wpas-checkbox">
				<label><input type="checkbox" name="terms" required> <?php printf( __( 'I accept the %sterms and conditions%s', 'wpas' ), '<a href="#wpas-modalterms" class="wpas-modal-trigger">', '</a>' ); ?></label>
			</div>
		<?php endif;
	}

	/**
	 * Add link to agent's tickets.
	 *
	 * @since  3.0.0
	 * @param  object $wp_admin_bar The WordPress toolbar object
	 * @return void
	 */
	public function toolbar_tickets_link( $wp_admin_bar ) {

		if ( !current_user_can( 'edit_ticket' ) ) {
			return;
		}

		$hide = boolval( wpas_get_option( 'hide_closed' ) );
		$args = array( 'post_type' => 'ticket' );

		if ( true === $hide ) {
			$args['wpas_status'] = 'open';
		} 

		$args = array(
			'id'    => 'wpas_tickets',
			'title' => __( 'My Tickets', 'wpas' ),
			'href'  => add_query_arg( $args, admin_url( 'edit.php' ) ),
			'meta'  => array( 'class' => 'wpas-my-tickets' )
		);
		
		$wp_admin_bar->add_node( $args );
	}

}