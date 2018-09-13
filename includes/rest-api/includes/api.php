<?php

/**
 * Awesome Support API main plugin class.
 *
 * @since 1.0.0
 */
class WPAS_API {

	/**
	 * @var object WPAS_API\Auth\Init
	 */
	public $auth;

	/**
	 * Instance of this loader class.
	 *
	 * @since    0.1.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * WPAS_API constructor.
	 */
	private function __construct() {

		$this->declare_constants();
		$this->load();

	}
	
	/**
	 * Declare plugin constants
	 */
	protected function declare_constants() {
		define( 'AS_API_VERSION', '1.0.4' );
		define( 'AS_API_URL',     plugin_dir_url( __DIR__ ) );
		define( 'AS_API_PATH',    plugin_dir_path( __DIR__ ) );
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
	 * @return string
	 */
	public function get_api_namespace() {
		return apply_filters( 'wpas_api_get_api_namespace', 'wpas-api/v1' );
	}


	/**
	 * Load the addon.
	 *
	 * Include all necessary files and instanciate the addon.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	private function load() {

		$this->includes();
		$this->actions();
		$this->filters();

	}

	/**
	 * Handle Actions
	 */
	protected function actions() {
		add_action( 'init', array( $this, 'load_text_domain' ) );
		add_action( 'rest_api_init', array( $this, 'load_api_routes' ) );
		add_action( 'rest_api_init', array( $this, 'user_fields' ) );
	}

	/**
	 * Handle Filters
	 */
	protected function filters() {
		add_filter( 'register_post_type_args', array( $this, 'enable_rest_api_cpt' ), 10, 2 );
		add_filter( 'register_taxonomy_args',  array( $this, 'enable_rest_api_tax' ), 10, 3 );
		add_filter( 'rest_prepare_taxonomy',   array( $this, 'taxonomy_rest_response' ), 10, 3 );
		add_filter( 'rest_pre_dispatch',       array( $this, 'reroute_ticket_dispatch' ), 10, 3 );
	}

	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	protected function includes() {
		$this->auth = WPAS_API\Auth\Init::get_instance();
	}


	/**
	 * Load this plugins text domain
	 */
	public function load_text_domain() {

		// Set filter for plugin's languages directory
		$wpas_api_lang_dir = AS_API_PATH . '/languages/';
		$wpas_api_lang_dir = apply_filters( 'wpas_api_languages_directory', $wpas_api_lang_dir );


		// Traditional WordPress plugin locale filter

		$get_locale = get_locale();

		if ( function_exists( 'get_user_locale' ) ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Defines the plugin language locale used.
		 *
		 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'awesome-support' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'awesome-support', $locale );

		// Setup paths to current locale file
		$mofile_local  = $wpas_api_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/awesome-support-api/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/awesome-support-api folder
			load_textdomain( 'awesome-support', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/awesome-support-api/languages/ folder
			load_textdomain( 'awesome-support', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'awesome-support', false, $wpas_api_lang_dir );
		}

	}

	/**
	 * Load APIs that are not loaded automatically
	 */
	public function load_api_routes() {

		$controller = new WPAS_API\API\Settings();
		$controller->register_routes();

		$controller = new WPAS_API\API\Users();
		$controller->register_routes();

		$controller = new WPAS_API\API\UserData();
		$controller->register_routes();

		$controller = new WPAS_API\API\TicketStatus();
		$controller->register_routes();

		$controller = new WPAS_API\API\CustomFields();
		$controller->register_routes();

		$controller = new WPAS_API\API\Passwords();
		$controller->register_routes();

		$controller = new WPAS_API\API\Attachments();
        $controller->register_routes();
        
	}

	/**
	 * Register user field
	 */
	public function user_fields() {

		register_rest_field( 'users', 'wpas_can_be_assigned', array(
			'get_callback'    => function ( $comment_arr ) {
				$comment_obj = get_comment( $comment_arr['id'] );

				return (int) $comment_obj->comment_karma;
			},
			'update_callback' => function ( $karma, $comment_obj ) {
				$ret = wp_update_comment( array(
					'comment_ID'    => $comment_obj->comment_ID,
					'comment_karma' => $karma
				) );
				if ( false === $ret ) {
					return new WP_Error( 'rest_comment_karma_failed', __( 'Failed to update comment karma.' ),
						array( 'status' => 500 ) );
				}

				return true;
			},
			'schema'          => array(
				'description' => __( 'Comment karma.' ),
				'type'        => 'integer'
			),
        ) );
        
	}


	/**
	 * @param array  $args      Array of arguments for registering a post type.
	 * @param string $post_type Post type key.
	 *
	 * @return array $args
	 */
	public function enable_rest_api_cpt( $args, $post_type ) {

		switch( $post_type ) {
			case 'ticket' :
				$args['show_in_rest'] = true;
				$args['rest_base'] = 'tickets';
				$args['rest_controller_class'] = 'WPAS_API\API\Tickets';
				break;

			case 'ticket_reply' :
				$args['show_in_rest'] = true;
				$args['rest_base'] = 'replies';
				$args['rest_controller_class'] = 'WPAS_API\API\TicketReplies';
				break;

			case 'ticket_history' :
				$args['show_in_rest'] = true;
				$args['rest_base'] = 'history';
				$args['rest_controller_class'] = 'WPAS_API\API\TicketHistory';
				break;

		}
		return $args;
	}

	/**
	 * @param array  $args      Array of arguments for registering a post type.
	 * @param string $post_type Post type key.
	 *
	 * @return array $args
	 */
	public function enable_rest_api_tax( $args, $taxonomy, $post_type ) {

		if ( in_array( 'ticket', (array) $post_type ) ) {
			$args['show_in_rest'] = true;
			$args['rest_base'] = $taxonomy;
			$args['rest_controller_class'] = 'WPAS_API\API\TicketTaxonomy';
		}

		return $args;
	}

	/**
	 * Filter the response and update the term page to user the correct namespace.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param object           $taxonomy     The original taxonomy object.
	 * @param WP_REST_Request  $request  Request used to generate the response.
	 *
	 * @return WP_REST_Response $response
	 */
	public function taxonomy_rest_response( $response, $taxonomy, $request ) {
		$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

		if ( in_array( 'ticket', $taxonomy->object_type ) ) {
			$response->remove_link( 'https://api.w.org/items' );
			$response->add_link( 'https://api.w.org/items', rest_url( wpas_api()->get_api_namespace() . '/' . $base ) );
		}

		return $response;
	}

	/**
	 * Gutenberg uses the default /wp/v2/ namespace for all posts. This rewrites the route to support our namespace.
	 *
	 * @param $result
	 * @param WP_REST_Server $server
	 * @param WP_REST_Request $request
	 *
	 * @since  1.0.4
	 *
	 * @return WP_REST_Response
	 * @author Tanner Moushey
	 */
	public function reroute_ticket_dispatch( $result, $server, $request ) {

		if ( false === strpos( $request->get_route(), '/wp/v2/tickets' ) ) {
			return $result;
		}

		$request->set_route( str_replace( '/wp/v2/tickets', '/wpas-api/v1/tickets', $request->get_route() ) );
		return rest_do_request( $request );

	}

} 


/**
 * Returns the One True Instance of WPAS_API
 *
 * @since 1.0.0
 * @return object | WPAS_API
 */
function wpas_api() {
	return WPAS_API::get_instance();
}