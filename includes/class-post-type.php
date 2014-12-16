<?php
/**
 * Post Type.
 *
 * @package   Admin/Post Type
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

class WPAS_Ticket_Post_Type {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {
		add_action( 'after_setup_theme',     array( $this, 'post_type' ),            10, 0 );
		add_action( 'init',                  array( $this, 'secondary_post_type' ),  10, 0 );
		add_action( 'init',                  array( $this, 'register_post_status' ), 10, 0 );
		add_action( 'post_updated_messages', array( $this, 'updated_messages' ),     10, 1 );    // Update the "post updated" messages for main post type
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     3.0.0
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
	 * Register the ticket post type.
	 *
	 * @since 1.0.0
	 */
	public function post_type() {

		$slug = defined( 'WPAS_SLUG' ) ? sanitize_title( WPAS_SLUG ) : 'ticket';

		/* Supported components */
		$supports = array( 'title' );

		/* If the post is being created we add the editor */
		if( !isset( $_GET['post'] ) ) {
			array_push( $supports, 'editor' );
		}

		/* Post type menu icon */
		$icon = version_compare( get_bloginfo( 'version' ), '3.8', '>=') ? 'dashicons-sos' : WPAS_ADMIN_ASSETS_URL . 'images/icon-tickets.png';

		/* Post type labels */
		$labels = array(
			'name'               => _x( 'Tickets', 'post type general name', 'wpas' ),
			'singular_name'      => _x( 'Ticket', 'post type singular name', 'wpas' ),
			'menu_name'          => _x( 'Tickets', 'admin menu', 'wpas' ),
			'name_admin_bar'     => _x( 'Ticket', 'add new on admin bar', 'wpas' ),
			'add_new'            => _x( 'Add New', 'book', 'wpas' ),
			'add_new_item'       => __( 'Add New Ticket', 'wpas' ),
			'new_item'           => __( 'New Ticket', 'wpas' ),
			'edit_item'          => __( 'Edit Ticket', 'wpas' ),
			'view_item'          => __( 'View Ticket', 'wpas' ),
			'all_items'          => __( 'All Tickets', 'wpas' ),
			'search_items'       => __( 'Search Tickets', 'wpas' ),
			'parent_item_colon'  => __( 'Parent Ticket:', 'wpas' ),
			'not_found'          => __( 'No tickets found.', 'wpas' ),
			'not_found_in_trash' => __( 'No tickets found in Trash.', 'wpas' ),
		);

		/* Post type capabilities */
		$cap = array(
			'read'					 => 'view_ticket',
			'read_post'				 => 'view_ticket',
			'read_private_posts' 	 => 'view_private_ticket',
			'edit_post'				 => 'edit_ticket',
			'edit_posts'			 => 'edit_ticket',
			'edit_others_posts' 	 => 'edit_other_ticket',
			'edit_private_posts' 	 => 'edit_private_ticket',
			'edit_published_posts' 	 => 'edit_ticket',
			'publish_posts'			 => 'create_ticket',
			'delete_post'			 => 'delete_ticket',
			'delete_posts'			 => 'delete_ticket',
			'delete_private_posts' 	 => 'delete_private_ticket',
			'delete_published_posts' => 'delete_ticket',
			'delete_others_posts' 	 => 'delete_other_ticket'
		);

		/* Post type arguments */
		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => apply_filters( 'wpas_rewrite_slug', $slug ), 'with_front' => false ),
			'capability_type'     => 'view_ticket',
			'capabilities'        => $cap,
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'menu_icon'           => $icon,
			'supports'            => $supports
		);

		register_post_type( 'ticket', $args );

	}

	/**
	 * Ticket update messages.
	 *
	 * @since  3.0.0
	 * @param  array $messages Existing post update messages.
	 * @return array           Amended post update messages with new CPT update messages.
	 */
	public function updated_messages( $messages ) {

		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		if ( 'ticket' !== $post_type ) {
			return $messages;
		}

		$messages[$post_type] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Ticket updated.', 'wpas' ),
			2  => __( 'Custom field updated.', 'wpas' ),
			3  => __( 'Custom field deleted.', 'wpas' ),
			4  => __( 'Ticket updated.', 'wpas' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Ticket restored to revision from %s', 'wpas' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Ticket published.', 'wpas' ),
			7  => __( 'Ticket saved.', 'wpas' ),
			8  => __( 'Ticket submitted.', 'wpas' ),
			9  => sprintf(
				__( 'Ticket scheduled for: <strong>%1$s</strong>.', 'wpas' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'wpas' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Ticket draft updated.', 'wpas' )
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View ticket', 'wpas' ) );
			$messages[ $post_type ][1] .= $view_link;
			$messages[ $post_type ][6] .= $view_link;
			$messages[ $post_type ][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview ticket', 'wpas' ) );
			$messages[ $post_type ][8]  .= $preview_link;
			$messages[ $post_type ][10] .= $preview_link;
		}

		return $messages;
	}

	/**
	 * Register secondary post types.
	 *
	 * These post types aren't used by the client
	 * but are used to store extra information about the tickets.
	 *
	 * @since  3.0.0
	 */
	public function secondary_post_type() {
		register_post_type( 'ticket_reply', array( 'public' => false, 'exclude_from_search' => true ) );
		register_post_type( 'ticket_history', array( 'public' => false, 'exclude_from_search' => true ) );
		register_post_type( 'ticket_log', array( 'public' => false, 'exclude_from_search' => true ) );
	}

	/**
	 * Register custom ticket status.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function register_post_status() {

		$status = self::get_post_status();

		foreach ( $status as $id => $custom_status ) {

			$args = array(
				'label'                     => $custom_status,
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( "$custom_status <span class='count'>(%s)</span>", "$custom_status <span class='count'>(%s)</span>", 'wpas' ),
			);

			register_post_status( $id, $args );
		}

		/**
		 * Hardcode the read and unread status used for replies.
		 */
		register_post_status( 'read',   array( 'label' => _x( 'Read', 'Reply status', 'wpas' ), 'public' => false ) );
		register_post_status( 'unread', array( 'label' => _x( 'Unread', 'Reply status', 'wpas' ), 'public' => false ) );
	}

	/**
	 * Get available ticket status.
	 *
	 * @since  3.0.0
	 * @return array List of filtered statuses
	 */
	public static function get_post_status() {

		$status = array(
			'queued'     => _x( 'New', 'Ticket status', 'wpas' ),
			'processing' => _x( 'In Progress', 'Ticket status', 'wpas' ),
			'hold'       => _x( 'On Hold', 'Ticket status', 'wpas' ),
		);

		return apply_filters( 'wpas_ticket_statuses', $status );

	}

}

/**
 * Get available ticket status wrapper function.
 *
 * @since  3.0.0
 * @return array List of filtered statuses
 */
function wpas_get_post_status() {
	return WPAS_Ticket_Post_Type::get_post_status();
}