<?php
/**
 * Awesome Support Admin.
 *
 * @package   Awesome_Support_Admin
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

class Awesome_Support_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Name of the nonce used to secure custom fields.
	 *
	 * @var      object
	 * @since 3.0.0
	 */
	public static $nonce_name = 'wpas_cf';

	/**
	 * Action of the custom nonce.
	 *
	 * @var      object
	 * @since 3.0.0
	 */
	public static $nonce_action = 'wpas_update_cf';

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		add_action( 'wp_ajax_wpas_edit_reply',      'wpas_edit_reply_ajax' ); // Edit a reply from the backend
		add_action( 'wp_ajax_wpas_mark_reply_read', 'wpas_mark_reply_read_ajax' ); // Edit a reply from the backend

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

			/* Load admin functions files */
			require_once( WPAS_PATH . 'includes/admin/functions-admin.php' );
			require_once( WPAS_PATH . 'includes/admin/functions-tools.php' );
			require_once( WPAS_PATH . 'includes/admin/functions-notices.php' );
			require_once( WPAS_PATH . 'includes/admin/class-admin-tickets-list.php' );
			require_once( WPAS_PATH . 'includes/admin/class-admin-user.php' );
			require_once( WPAS_PATH . 'includes/admin/class-admin-titan.php' );
			require_once( WPAS_PATH . 'includes/admin/class-admin-help.php' );

			if ( ! class_exists( 'TAV_Remote_Notification_Client' ) ) {
				require_once( WPAS_PATH . 'includes/class-remote-notification-client.php' );
			}

			/* Load settings files */
			require_once( WPAS_PATH . 'includes/admin/settings/functions-settings.php' );
			require_once( WPAS_PATH . 'includes/admin/settings/settings-general.php' );
			require_once( WPAS_PATH . 'includes/admin/settings/settings-style.php' );
			require_once( WPAS_PATH . 'includes/admin/settings/settings-notifications.php' );
			require_once( WPAS_PATH . 'includes/admin/settings/settings-advanced.php' );
			require_once( WPAS_PATH . 'includes/admin/settings/settings-licenses.php' );

			/* Handle possible redirections first of all. */
			if ( isset( $_SESSION['wpas_redirect'] ) ) {
				$redirect = esc_url( $_SESSION['wpas_redirect'] );
				unset( $_SESSION['wpas_redirect'] );
				wp_redirect( $redirect );
				exit;
			}

			/* Execute custom actions */
			if ( isset( $_GET['wpas-do'] ) ) {
				add_action( 'init', array( $this, 'custom_actions' ) );
			}

			/* Instantiate secondary classes */
			add_action( 'plugins_loaded',            array( 'WPAS_Tickets_List', 'get_instance' ), 11, 0 );
			add_action( 'plugins_loaded',            array( 'WPAS_User',         'get_instance' ), 11, 0 );
			add_action( 'plugins_loaded',            array( 'WPAS_Titan',        'get_instance' ), 11, 0 );
			add_action( 'plugins_loaded',            array( 'WPAS_Help',         'get_instance' ), 11, 0 );

			/* Do Actions. */
			add_action( 'pre_get_posts',             array( $this, 'hide_others_tickets' ), 10, 1 );
			add_action( 'pre_get_posts',             array( $this, 'limit_open' ), 10, 1 );
			add_action( 'admin_init',                array( $this, 'system_tools' ), 10, 0 );
			add_action( 'plugins_loaded',            array( $this, 'remote_notifications' ), 15, 0 );
			add_action( 'admin_enqueue_scripts',     array( $this, 'enqueue_admin_styles' ) );              // Load plugin styles
			add_action( 'admin_enqueue_scripts',     array( $this, 'enqueue_admin_scripts' ) );             // Load plugin scripts
			add_action( 'admin_menu',                array( $this, 'register_submenu_items' ) );            // Register all the submenus
			add_action( 'admin_menu',                array( $this, 'tickets_count' ) );                     // Add the tickets count
			add_action( 'admin_notices',             array( $this, 'admin_notices' ) );                     // Display custom admin notices
			add_action( 'add_meta_boxes',            array( $this, 'metaboxes' ) );                         // Register the metaboxes
			add_action( 'save_post_ticket',          array( $this, 'save_ticket' ) );                       // Save all custom fields
			add_action( 'wpas_add_reply_after',      array( $this, 'mark_replies_read' ), 10, 2 );          // Mark a ticket replies as read
			add_action( 'before_delete_post',        array( $this, 'delete_ticket_dependencies' ), 10, 1 ); // Delete all ticket dependencies (replies, history...)

			/* Apply Filters. */
			add_filter( 'plugin_action_links_' . plugin_basename( trailingslashit( plugin_dir_path( __DIR__ ) ) . 'awesome-support.php' ), array( $this, 'add_action_links' ) ); // Add link to settings in the plugins list
			add_filter( 'post_row_actions',                       array( $this, 'ticket_action_row' ), 10, 2 );    // Add custom actions in each ticket row
			add_filter( 'postbox_classes_ticket_wpas-mb-details', array( $this, 'add_metabox_details_classes' ) ); // Customizedetails metabox classes
			add_filter( 'wp_insert_post_data',                    array( $this, 'filter_ticket_data' ), 99, 2 );   // Filter ticket data before insertion in DB

			/**
			 * Plugin setup.
			 *
			 * If the plugin has just been installed we need to set a couple of things.
			 * We will automatically create the "special" pages: tickets list and 
			 * ticket submission.
			 */
			if ( 'pending' === get_option( 'wpas_setup', false ) ) {
				add_action( 'admin_init', array( $this, 'create_pages' ), 11, 0 );
				add_action( 'admin_init', array( $this, 'flush_rewrite_rules' ), 11, 0 );
			}

			/**
			 * Redirect to about page.
			 *
			 * We don't use the 'was_setup' option for the redirection as
			 * if the install fails the first time this will create a redirect loop
			 * on the about page.
			 */
			if ( true === boolval( get_option( 'wpas_redirect_about', false ) ) ) {
				add_action( 'admin_init', array( $this, 'redirect_to_about' ), 12, 0 );
			}

			/**
			 * Ask for products support.
			 *
			 * Still part of the installation process. Ask the user
			 * if he is going to support multiple products or only one.
			 * It is important to use the built-in taxonomy for multiple products
			 * support as it is used by multiple addons.
			 *
			 * However, if the products support is already enabled, it means that this is not
			 * the first activation of the plugin and products support was previously enabled
			 * (products support is disabled by default). In this case we don't ask again.
			 */
			if ( 'pending' === get_option( 'wpas_support_products', false ) && ( !isset( $_GET['page'] ) || 'wpas-about' !== $_GET['page'] ) ) {
				
				$products = boolval( wpas_get_option( 'support_products' ) );
				
				if ( true === $products ) {
					delete_option( 'wpas_support_products' );
				} else {
					add_action( 'admin_notices', array( $this, 'ask_support_products' ) );
				}
			}
		}

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
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
	 * Add a link to the settings page.
	 *
	 * @since  3.1.5
	 * @param  array $links Plugin links
	 * @return array        Links with the settings
	 */
	public static function settings_page_link( $links ) {

		$link    = add_query_arg( array( 'post_type' => 'ticket', 'page' => 'settings' ), admin_url( 'edit.php' ) );
		$links[] = "<a href='$link'>" . __( 'Settings', 'wpas' ) . "</a>";

		return $links;

	}

	/**
	 * Hide tickets not assigned to current user.
	 *
	 * Admins and agents can be set to only see their own tickets.
	 * In this case, we modify the main query to only get the tickets
	 * the current user is assigned to.
	 *
	 * @since  3.0.0
	 * @param  object $query WordPress main query
	 * @return boolean       True if the main query was modified, false otherwise
	 */
	public function hide_others_tickets( $query ) {

		/* Make sure this is the main query */
		if ( ! $query->is_main_query() ) {
			return false;
		}

		/* Make sure this is the admin screen */
		if ( ! is_admin() ) {
			return false;
		}

		/* Make sure we only alter our post type */
		if ( ! isset( $_GET['post_type'] ) || 'ticket' !== $_GET['post_type'] ) {
			return false;
		}

		/* If admins can see all tickets do nothing */
		if ( current_user_can( 'administrator' ) && true === (bool) wpas_get_option( 'admin_see_all' ) ) {
			return false;
		}

		/* If agents can see all tickets do nothing */
		if ( current_user_can( 'edit_ticket' ) && ! current_user_can( 'administrator' ) && true === (bool) wpas_get_option( 'agent_see_all' ) ) {
			return false;
		}

		global $current_user;

		$query->set( 'meta_key', '_wpas_assignee' );
		$query->set( 'meta_value', (int) $current_user->ID );

		return true;

	}

	/**
	 * Limit the list of tickets to open.
	 *
	 * When tickets are filtered by post status it makes no sense
	 * to display tickets that are already closed. We hereby limit
	 * the list to open tickets.
	 *
	 * @since  3.1.3
	 *
	 * @param object $query WordPress main query
	 *
	 * @return boolean True if the tickets were filtered, false otherwise
	 */
	public function limit_open( $query ) {

		/* Make sure this is the main query */
		if ( ! $query->is_main_query() ) {
			return false;
		}

		/* Make sure this is the admin screen */
		if ( ! is_admin() ) {
			return false;
		}

		/* Make sure we only alter our post type */
		if ( ! isset( $_GET['post_type'] ) || 'ticket' !== $_GET['post_type'] ) {
			return false;
		}

		if ( isset( $_GET['post_status'] ) && array_key_exists( $_GET['post_status'], wpas_get_post_status() ) || ! isset( $_GET['post_status'] ) && true === (bool) wpas_get_option( 'hide_closed', false ) ) {

			$query->set( 'meta_query', array(
					array(
						'key'     => '_wpas_status',
						'value'   => 'open',
						'compare' => '=',
					)
				)
			);

			return true;

		} else {
			return false;
		}

	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( wpas_is_plugin_page() ) {		

			wp_enqueue_style( 'wpas-select2', WPAS_URL . 'assets/admin/css/vendor/select2.min.css', null, '3.5.2', 'all' );
			wp_enqueue_style( 'wpas-admin-styles', WPAS_URL . 'assets/admin/css/admin.css', array( 'wpas-select2' ), WPAS_VERSION );

		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! wpas_is_plugin_page() ) {
			return;
		}

		if ( 'ticket' == get_post_type() ) {
			wp_dequeue_script( 'autosave' );
		}

		$page   = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

		if ( 'wpas-about' === $page ) {
			add_thickbox();
			wp_enqueue_script( 'wpas-admin-about-script', WPAS_URL . 'assets/admin/js/admin-about.js', array( 'jquery' ), WPAS_VERSION );
		}

		wp_enqueue_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2.min.js', array( 'jquery' ), '3.5.2', true );
		wp_enqueue_script( 'wpas-admin-script', WPAS_URL . 'assets/admin/js/admin.js', array( 'jquery', 'wpas-select2' ), WPAS_VERSION );
		wp_enqueue_script( 'wpas-admin-tabletojson', WPAS_URL . 'assets/admin/js/vendor/jquery.tabletojson.min.js', array( 'jquery' ), WPAS_VERSION );

		if ( 'edit' === $action && 'ticket' == get_post_type() ) {
			wp_enqueue_script( 'wpas-admin-reply', WPAS_URL . 'assets/admin/js/admin-reply.js', array( 'jquery' ), WPAS_VERSION );
			wp_localize_script( 'wpas-admin-reply', 'wpasL10n', array( 'alertDelete' => __( 'Are you sure you want to delete this reply?', 'wpas' ), 'alertNoTinyMCE' => __( 'No instance of TinyMCE found. Please use wp_editor on this page at least once: http://codex.wordpress.org/Function_Reference/wp_editor', 'wpas' ) ) );
		}

	}

	/**
	 * Create the mandatory pages.
	 *
	 * Create the mandatory for the user in order to avoid
	 * issues with people thinking the plugin isn't working.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function create_pages() {

		$options = maybe_unserialize( get_option( 'wpas_options', array() ) );
		$update = false;

		if ( empty( $options['ticket_list'] ) ) {

			$list_args = array(
				'post_content'   => '[tickets]',
				'post_title'     => wp_strip_all_tags( __( 'My Tickets', 'wpas' ) ),
				'post_name'      => sanitize_title( __( 'My Tickets', 'wpas' ) ),
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'ping_status'    => 'closed',
				'comment_status' => 'closed'
			);

			$list = wp_insert_post( $list_args, true );

			if ( !is_wp_error( $list ) && is_int( $list ) ) {
				$options['ticket_list'] = $list;
				$update                 = true;
			}
		}

		if ( empty( $options['ticket_submit'] ) ) {

			$submit_args = array(
				'post_content'   => '[ticket-submit]',
				'post_title'     => wp_strip_all_tags( __( 'Submit Ticket', 'wpas' ) ),
				'post_name'      => sanitize_title( __( 'Submit Ticket', 'wpas' ) ),
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'ping_status'    => 'closed',
				'comment_status' => 'closed'
			);
		
			$submit = wp_insert_post( $submit_args, true );

			if ( !is_wp_error( $submit ) && is_int( $submit ) ) {
				$options['ticket_submit'] = $submit;
				$update                   = true;
			}

		}

		if ( $update ) {
			update_option( 'wpas_options', serialize( $options ) );
		}

		if ( !empty( $options['ticket_submit'] ) && !empty( $options['ticket_list'] ) ) {
			delete_option( 'wpas_setup' );
		}
	}

	/**
	 * Flush rewrite rules.
	 *
	 * This is to avoid getting 404 errors
	 * when trying to view a ticket. We need to update
	 * the permalinks with our new custom post type.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Redirect to about page.
	 *
	 * Redirect the user to the about page after plugin activation.
	 * 
	 * @return void
	 */
	public function redirect_to_about() {
		delete_option( 'wpas_redirect_about' );
		wp_redirect( add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-about' ), admin_url( 'edit.php' ) ) );
		exit;
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @param array $links Plugin action links
	 *
	 * @return array Updated action links including the one to the settings page
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . add_query_arg( array( 'post_type' => 'ticket', 'page' => 'edit.php?post_type=ticket-settings' ), admin_url( 'edit.php' ) ) . '">' . __( 'Settings', 'wpas' ) . '</a>'
			),
			$links
		);

	}

	/**
	 * Add items in action row.
	 *
	 * Add a quick option to open or close a ticket
	 * directly from the tickets list.
	 *
	 * @since  3.0.0
	 * @param  array $actions  List of existing options
	 * @param  object $post    Current post object
	 * @return array           List of options with ours added
	 */
	public function ticket_action_row( $actions, $post ) {

		if ( 'ticket' === $post->post_type ) {

			$status = wpas_get_ticket_status( $post->ID );

			if ( 'open' === $status ) {
				$actions['close'] = '<a href="' . wpas_get_close_ticket_url( $post->ID ) . '">' . __( 'Close', 'wpas' ) . '</a>';
			} elseif( 'closed' === $status ) {
				$actions['open'] = '<a href="' . wpas_get_open_ticket_url( $post->ID ) . '">' . __( 'Open', 'wpas' ) . '</a>';
			}
			
		}

		return $actions;
	}

	/**
	 * Display custom admin notices.
	 *
	 * Custom admin notices are usually triggered by custom actions.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function admin_notices() {

		if ( isset( $_GET['wpas-message'] ) ) {

			switch( $_GET['wpas-message'] ) {

				case 'opened':
					?>
					<div class="updated">
						<p><?php printf( __( 'The ticket #%s has been (re)opened.', 'wpas' ), intval( $_GET['post'] ) ); ?></p>
					</div>
					<?php
				break;

				case 'closed':
					?>
					<div class="updated">
						<p><?php printf( __( 'The ticket #%s has been closed.', 'wpas' ), intval( $_GET['post'] ) ); ?></p>
					</div>
					<?php
				break;

			}

		}
	}

	/**
	 * Multiple products support.
	 *
	 * Ask the user to choose if the support site will manage
	 * multiple products or not.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function ask_support_products() {

		global $pagenow;

		$get = $_GET;

		if ( !isset( $get ) || !is_array( $get ) ) {
			$get = array();
		}

		$get['wpas-nonce']       = wp_create_nonce( 'wpas_custom_action' );
		$get_single              = $get_multiple = $get;
		$get_single['wpas-do']   = 'single-product';
		$get_multiple['wpas-do'] = 'multiple-products';

		$single_url   = add_query_arg( $get_single, admin_url( $pagenow ) );
		$multiple_url = add_query_arg( $get_multiple, admin_url( $pagenow ) );
		?>
		<div class="updated">
			<p><?php _e( 'Will you be supporting multiple products on this support site? You can activate multi-products support now. <small>(This setting can be modified later)</small>', 'wpas' ); ?></p>
			<p>
				<a href="<?php echo wp_sanitize_redirect( $single_url ); ?>" class="button-secondary"><?php _e( 'Single Product', 'wpas' ); ?></a> 
				<a href="<?php echo wp_sanitize_redirect( $multiple_url ); ?>" class="button-secondary"><?php _e( 'Multiple Products', 'wpas' ); ?></a>
			</p>
		</div>
	<?php }

	/**
	 * Filter ticket data before insertion.
	 *
	 * Before inserting a new ticket in the database,
	 * we check the post status and possibly overwrite it
	 * with one of the registered custom status.
	 *
	 * @since  3.0.0
	 * @param  array $data    Post data
	 * @param  array $postarr Original post data
	 * @return array          Modified post data for insertion
	 */
	public function filter_ticket_data( $data, $postarr ) {

		global $current_user;

		if ( !isset( $data['post_type'] ) || 'ticket' !== $data['post_type'] ) {
			return $data;
		}

		/**
		 * If the ticket is being trashed we don't do anything.
		 */
		if ( 'trash' === $data['post_status'] ) {
			return $data;
		}

		/**
		 * Do not affect auto drafts
		 */
		if ( 'auto-draft' === $data['post_status'] ) {
			return $data;
		}

		/**
		 * Automatically set the ticket as processing if this is the first reply.
		 */
		if ( user_can( $current_user->ID, 'edit_ticket' ) && isset( $postarr['ID'] ) ) {
			$replies = wpas_get_replies( intval( $postarr['ID'] ) );
			if ( 0 === count( $replies ) ) {
				if ( !isset( $_POST['post_status_override'] ) || 'queued' === $_POST['post_status_override'] ) {
					$_POST['post_status_override'] = 'processing';
				}
			}
		}

		if ( isset( $_POST['post_status_override'] ) && !empty( $_POST['post_status_override'] ) ) {

			$status = wpas_get_post_status();

			if ( array_key_exists( $_POST['post_status_override'], $status ) ) {

				$data['post_status'] = $_POST['post_status_override'];

				if ( $postarr['original_post_status'] !== $_POST['post_status_override'] && isset( $_POST['wpas_post_parent'] ) ) {
					wpas_log( intval( $_POST['wpas_post_parent'] ), sprintf( __( 'Ticket state changed to %s', 'wpas' ), '&laquo;' . $status[$_POST['post_status_override']] . '&raquo;' ) );
				}
			}

		}
		
		return $data;
	}

	/**
	 * Register all submenu items.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function register_submenu_items() {
		add_submenu_page( 'edit.php?post_type=ticket', __( 'Debugging Tools', 'wpas' ), __( 'Tools', 'wpas' ), 'administrator', 'wpas-status', array( $this, 'display_status_page' ) );
		add_submenu_page( 'edit.php?post_type=ticket', __( 'Awesome Support Addons', 'wpas' ), '<span style="color:#f39c12;">' . __( 'Addons', 'wpas' ) . '</span>', 'edit_posts', 'wpas-addons', array( $this, 'display_addons_page' ) );
		add_submenu_page( 'edit.php?post_type=ticket', __( 'About Awesome Support', 'wpas' ), __( 'About', 'wpas' ), 'edit_posts', 'wpas-about', array( $this, 'display_about_page' ) );
		remove_submenu_page( 'edit.php?post_type=ticket', 'wpas-about' );
	}

	/**
	 * Add ticket count in admin menu item.
	 *
	 * @return boolean True if the ticket count was added, false otherwise
	 * @since  1.0.0
	 */
	public function tickets_count() {

		if ( false === (bool) wpas_get_option( 'show_count' ) ) {
			return false;
		}

		global $menu, $current_user;

		$args = array();

		if ( current_user_can( 'administrator' )
			&& false === boolval( wpas_get_option( 'admin_see_all' ) )
			|| !current_user_can( 'administrator' )
			&& current_user_can( 'edit_ticket' )
			&& false === boolval( wpas_get_option( 'agent_see_all' ) ) ) {
			$args['meta_query'][] = array(
				'key'     => '_wpas_assignee',
				'value'   => $current_user->ID,
				'compare' => '=',
			);
		}

		$count = count( get_tickets( 'open', $args ) );

		if ( 0 === $count ) {
			return false;
		}

		foreach ( $menu as $key => $value ) {
			if ( $menu[$key][2] == 'edit.php?post_type=ticket' ) {
				$menu[$key][0] .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . $count . '</span></span>';
			}
		}

		return true;
	}

	/**
	 * Render the about page for this plugin.
	 *
	 * @since    3.0.0
	 */
	public function display_about_page() {
		include_once( WPAS_PATH . 'includes/admin/views/about.php' );
	}

	/**
	 * Render the addons page for this plugin.
	 *
	 * @since    3.0.0
	 */
	public function display_addons_page() {
		include_once( WPAS_PATH . 'includes/admin/views/addons.php' );
	}

	/**
	 * Render the system status.
	 *
	 * @since    3.0.0
	 */
	public function display_status_page() {
		include_once( WPAS_PATH . 'includes/admin/views/status.php' );
	}

	/**
	 * Execute plugin custom actions.
	 *
	 * Any custom actions the plugin can trigger through a URL variable
	 * will be executed here. It is all triggered by the var wpas-do.
	 *
	 * @since 3.0.0
	 */
	public function custom_actions() {

		/* Make sure we have a trigger */
		if ( ! isset( $_GET['wpas-do'] ) ) {
			return;
		}

		/* Validate the nonce */
		if ( ! isset( $_GET['wpas-nonce'] ) || ! wp_verify_nonce( $_GET['wpas-nonce'], 'wpas_custom_action' ) ) {
			return;
		}

		$log    = array();
		$action = sanitize_text_field( $_GET['wpas-do'] );

		switch ( $action ):

			case 'close':

				if( isset( $_GET['post'] ) && 'ticket' == get_post_type( intval( $_GET['post'] ) ) ) {

					$url = add_query_arg( array( 'post' => $_GET['post'], 'action' => 'edit', 'wpas-message' => 'closed' ), admin_url( 'post.php' ) );

					wpas_close_ticket( $_GET['post'] );

				}

			break;

			case 'open':

				if( isset( $_GET['post'] ) && 'ticket' == get_post_type( intval( $_GET['post'] ) ) ) {

					$url = add_query_arg( array( 'post' => $_GET['post'], 'action' => 'edit', 'wpas-message' => 'opened' ), admin_url( 'post.php' ) );

					wpas_reopen_ticket( $_GET['post'] );

				}

			break;

			case 'trash_reply':

				if( isset( $_GET['del_id'] ) && current_user_can( 'delete_reply' ) ) {

					$del_id = intval( $_GET['del_id'] );

					/* Trash the post */
					wp_trash_post( $del_id, false );

					/* Redirect with clean URL */
					$url = wp_sanitize_redirect( add_query_arg( array( 'post' => $_GET['post'], 'action' => 'edit' ), admin_url( 'post.php' ) . "#wpas-post-$del_id" ) );

					wpas_redirect( 'trashed_reply', $url );
					exit;

				}

			break;

			case 'multiple-products':

				$options = maybe_unserialize( get_option( 'wpas_options' ) );
				$options['support_products'] = '1';

				update_option( 'wpas_options', serialize( $options ) );
				delete_option( 'wpas_support_products' );

				wpas_redirect( 'enable_multiple_products', add_query_arg( array( 'taxonomy' => 'product', 'post_type' => 'ticket' ), admin_url( 'edit-tags.php' ) ) );
				exit;

			break;

			case 'single-product':
				delete_option( 'wpas_support_products' );
				wpas_redirect( 'enable_single_product', remove_query_arg( array( 'wpas-nonce', 'wpas-do' ), wpas_get_current_admin_url() ) );
				exit;
			break;

		endswitch;

		/**
		 * wpas_custom_actions hook
		 *
		 * Fired right after the action is executed. It is important to note that
		 * some of the action are triggering a redirect after they're done and
		 * that in this case this hook won't be triggered.
		 *
		 * @param string $action The action that's being executed
		 */
		do_action( 'wpas_execute_custom_action', $action );

		/* Log the action */
		if ( ! empty( $log ) ) {
			wpas_log( $_GET['post'], $log );
		}

		/* Get URL vars */
		$args = $_GET;

		/* Remove custom action and nonce */
		unset( $_GET['wpas-do'] );
		unset( $_GET['wpas-nonce'] );

		/* Read-only redirect */
		wpas_redirect( 'read_only', $url );
		exit;

	}

	/**
	 * Register the metaboxes.
	 *
	 * The function below registers all the metaboxes used
	 * in the ticket edit screen.
	 *
	 * @since 3.0.0
	 */
	public function metaboxes() {

		/* Remove the publishing metabox */
		remove_meta_box( 'submitdiv', 'ticket', 'side' );

		/**
		 * Register the metaboxes.
		 */
		/* Issue details, only available for existing tickets */
		if( isset( $_GET['post'] ) ) {
			add_meta_box( 'wpas-mb-message', __( 'Ticket', 'wpas' ), array( $this, 'metabox_callback' ), 'ticket', 'normal', 'high', array( 'template' => 'message' ) );

			$status = get_post_meta( intval( $_GET['post'] ), '_wpas_status', true );

			if ( '' !== $status ) {
				add_meta_box( 'wpas-mb-replies', __( 'Ticket Replies', 'wpas' ), array( $this, 'metabox_callback' ), 'ticket', 'normal', 'high', array( 'template' => 'replies' ) );
			}
		}

		/* Ticket details */
		add_meta_box( 'wpas-mb-details', __( 'Details', 'wpas' ), array( $this, 'metabox_callback' ), 'ticket', 'side', 'high', array( 'template' => 'details' ) );

		/* Contacts involved in the ticket */
		add_meta_box( 'wpas-mb-contacts', __( 'Stakeholders', 'wpas' ), array( $this, 'metabox_callback' ), 'ticket', 'side', 'high', array( 'template' => 'stakeholders' ) );

		/* Custom fields */
		global $wpas_cf;

		if ( $wpas_cf->have_custom_fields() ) {	
			add_meta_box( 'wpas-mb-cf', __( 'Custom Fields', 'wpas' ), array( $this, 'metabox_callback' ), 'ticket', 'side', 'default', array( 'template' => 'custom-fields' ) );
		}

	}

	/**
	 * Add new class to the details metabox.
	 *
	 * @param array $classes Current metabox classes
	 *
	 * @return array The updated list of classes
	 */
	public function add_metabox_details_classes( $classes ) {
		array_push( $classes, 'submitdiv' );
		return $classes;
	}

	/**
	 * Metabox callback function.
	 *
	 * The below function is used to call the metaboxes content.
	 * A template name is given to the function. If the template
	 * does exist, the metabox is loaded. If not, nothing happens.
	 *
	 * @param  (integer) $post     Post ID
	 * @param  (string)  $template Metabox content template
	 *
	 * @return void
	 * @since  3.0.0
	 */
	public function metabox_callback( $post, $args ) {

		if ( ! is_array( $args ) || ! isset( $args['args']['template'] ) ) {
			_e( 'An error occurred while registering this metabox. Please contact the support.', 'wpas' );
		}

		$template = $args['args']['template'];

		if ( ! file_exists( WPAS_PATH . "includes/admin/metaboxes/$template.php" ) ) {
			_e( 'An error occured while loading this metabox. Please contact the support.', 'wpas' );
		}

		/* Include the metabox content */
		include_once( WPAS_PATH . "includes/admin/metaboxes/$template.php" );

	}

	/**
	 * Save ticket custom fields.
	 *
	 * This function will save all custom fields associated
	 * to the ticket post type. Be it core custom fields
	 * or user added custom fields.
	 * 
	 * @param  (int) $post_id Current post ID
	 * @since  3.0.0
	 */
	public function save_ticket( $post_id ) {

		/* We should already being avoiding Ajax, but let's make sure */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		/* Now we check the nonce */
		if ( ! isset( $_POST[ Awesome_Support_Admin::$nonce_name ] ) || ! wp_verify_nonce( $_POST[ Awesome_Support_Admin::$nonce_name ], Awesome_Support_Admin::$nonce_action ) ) {
			return;
		}

		/* Does the current user has permission? */
		if ( !current_user_can( 'edit_ticket', $post_id ) ) {
			return;
		}

		global $current_user;

		/**
		 * Store possible logs
		 */
		$log = array();

		/**
		 * If no ticket status is found we are in the situation where
		 * the agent is creating a ticket on behalf of the user. There are
		 * a couple of things that we need to do then.
		 */
		if ( '' === $original_status = get_post_meta( $post_id, '_wpas_status', true ) ) {

			/**
			 * First of all, set the ticket as open. This is very important.
			 */
			add_post_meta( $post_id, '_wpas_status', 'open', true );

			/**
			 * Send the confirmation e-mail to the user.
			 *
			 * @since  3.1.5
			 */
			wpas_email_notify( $post_id, 'submission_confirmation' );

		}

		/* Save the possible ticket reply */
		if ( isset( $_POST['wpas_reply'] ) && isset( $_POST['wpas_reply_ticket'] ) && '' != $_POST['wpas_reply'] ) {

			/* Check for the nonce */
			if ( wp_verify_nonce( $_POST['wpas_reply_ticket'], 'reply_ticket' ) ) {

				$user_id = $current_user->ID;
				$content = wp_kses_post( $_POST['wpas_reply'] );

				$data = array(
					'post_content'   => $content,
					'post_status'    => 'read',
					'post_type'      => 'ticket_reply',
					'post_author'    => $user_id,
					'post_parent'    => $post_id,
					'ping_status'    => 'closed',
					'comment_status' => 'closed',
				);

				/**
				 * Remove the save_post hook now as we're going to trigger
				 * a new one by inserting the reply (and logging the history later).
				 */
				remove_action( 'save_post_ticket', array( $this, 'save_ticket' ) );

				/* Insert the reply in DB */
				$reply = wpas_add_reply( $data, $post_id );

				/* In case the insertion failed... */
				if ( is_wp_error( $reply ) ) {

					/* Set the redirection */
					$_SESSION['wpas_redirect'] = add_query_arg( array( 'wpas-message' => 'wpas_reply_error' ), get_permalink( $post_id ) );

				} else {

					/**
					 * Delete the activity transient.
					 */
					delete_transient( "wpas_activity_meta_post_$post_id" );

					/* E-Mail the client */
					$new_reply = new WPAS_Email_Notification( $post_id, array( 'reply_id' => $reply, 'action' => 'reply_agent' ) );

					/* The agent wants to close the ticket */
					if ( isset( $_POST['wpas_do'] ) &&  'reply_close' == $_POST['wpas_do'] ) {

						/* Confirm the post type and close */
						if( 'ticket' == get_post_type( $post_id ) ) {

							/**
							 * wpas_ticket_before_close_by_agent hook
							 */
							do_action( 'wpas_ticket_before_close_by_agent', $post_id );

							/* Close */
							$closed = wpas_close_ticket( $post_id );

							/* E-Mail the client */
							new WPAS_Email_Notification( $post_id, array( 'action' => 'closed' ) );

							/**
							 * wpas_ticket_closed_by_agent hook
							 */
							do_action( 'wpas_ticket_closed_by_agent', $post_id );
						}

					}

				}

			}

		}

		/**
		 * wpas_save_custom_fields_before hook
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_save_custom_fields_before', $post_id );

		/* Now we can instantiate the save class and save */
		$wpas_save = new WPAS_Save_Fields();
		$saved = $wpas_save->save( $post_id );

		/**
		 * wpas_save_custom_fields_before hook
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_save_custom_fields_after', $post_id );

		/* Log the action */
		if ( !empty( $log ) ) {
			wpas_log( $post_id, $log );
		}

		/* If this was a ticket update, we need to know where to go now... */
		if ( '' !== $original_status ) {

			/* Go back to the tickets list */
			if ( isset( $_POST['wpas_back_to_list'] ) && true === boolval( $_POST['wpas_back_to_list'] ) || isset( $_POST['where_after'] ) && 'back_to_list' === $_POST['where_after'] ) {
				$_SESSION['wpas_redirect'] = add_query_arg( array( 'post_type' => 'ticket' ), admin_url( 'edit.php' ) );
			}

		}

	}

	/**
	 * Mark replies as read.
	 *
	 * When an agent replies to a ticket, we mark all previous replies
	 * as read. We suppose it's all been read when the agent replies.
	 * This allows for keeping replies unread until an agent replies
	 * or manually marks the last reply as read.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function mark_replies_read( $reply_id, $data ) {

		$replies = wpas_get_replies( intval( $data['post_parent'] ), 'unread' );

		foreach ( $replies as $reply ) {
			wpas_mark_reply_read( $reply->ID );
		}

	}

	/**
	 * Delete ticket dependencies.
	 *
	 * Delete all ticket dependencies when a ticket is deleted. This includes
	 * ticket replies and ticket history. Ticket attachments are deleted by
	 * WPAS_File_Upload::delete_attachments()
	 * 
	 * @param  integer $post_id ID of the post to be deleted
	 * @return void
	 */
	public function delete_ticket_dependencies( $post_id ) {

		/* First of all we remove this action to avoid creating a loop */
		remove_action( 'before_delete_post', array( $this, 'delete_ticket_replies' ), 10, 1 );

		$args = array(
			'post_parent'            => $post_id,
			'post_type'              => apply_filters( 'wpas_replies_post_type', array( 'ticket_history', 'ticket_reply' ) ),
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);		
		
		$posts = new WP_Query( $args );

		foreach ( $posts->posts as $id => $post ) {

			do_action( 'wpas_before_delete_dependency', $post->ID, $post );

			wp_delete_post( $post->ID, true );

			do_action( 'wpas_after_delete_dependency', $post->ID, $post );
		}

	}

	public function system_tools() {

		if ( !isset( $_GET['tool'] ) || !isset( $_GET['_nonce'] ) ) {
			return false;
		}

		if ( !wp_verify_nonce( $_GET['_nonce'], 'system_tool' ) ) {
			return false;
		}

		switch( sanitize_text_field( $_GET['tool'] ) ) {

			/* Clear all tickets metas */
			case 'tickets_metas';
				wpas_clear_tickets_metas();
				break;

			case 'clear_taxonomies':
				wpas_clear_taxonomies();
				break;

			case 'resync_products':
				wpas_delete_synced_products( true );
				break;

			case 'delete_products':
				wpas_delete_synced_products();
				break;
		}

		/* Redirect in "read-only" mode */
		$url  = add_query_arg( array(
			'post_type' => 'ticket',
			'page'      => 'wpas-status',
			'tab'       => 'tools',
			'done'      => sanitize_text_field( $_GET['tool'] )
			), admin_url( 'edit.php' )
		);

		wp_redirect( wp_sanitize_redirect( $url ) );
		exit;

	}

	/**
	 * Check for remote notifications.
	 *
	 * Use the Remote Dashboard Notifications plugin
	 * to check for possible notifications from
	 * http://getawesomesupport.com
	 *
	 * @since  3.0.0
	 * @link   https://wordpress.org/plugins/remote-dashboard-notifications/
	 * @return void
	 */
	public function remote_notifications() {
		$notification = new TAV_Remote_Notification_Client( 76, '7f613a5dc7754971', 'http://getawesomesupport.com?post_type=notification' );
	}

}