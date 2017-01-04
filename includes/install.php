<?php
/**
 * @package   Awesome Support/Install
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2015 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

register_activation_hook( WPAS_PLUGIN_FILE, 'wpas_install' );
/**
 * Fired when the plugin is activated.
 *
 * @since    1.0.0
 *
 * @param    boolean $network_wide       True if WPMU superadmin uses
 *                                       "Network Activate" action, false if
 *                                       WPMU is disabled or plugin is
 *                                       activated on an individual blog.
 */
function wpas_install( $network_wide ) {

	if ( function_exists( 'is_multisite' ) && is_multisite() ) {

		if ( $network_wide ) {

			// Get all blog ids
			$blog_ids = wpas_get_blog_ids();

			foreach ( $blog_ids as $blog_id ) {

				switch_to_blog( $blog_id );
				wpas_single_activate();
			}

			restore_current_blog();

		} else {
			wpas_single_activate();
		}

	} else {
		wpas_single_activate();
	}

}

add_action( 'wpmu_new_blog', 'wpas_activate_new_site', 10, 6 );
/**
 * Fired when a new site is activated with a WPMU environment.
 *
 * @since    1.0.0
 *
 * @param    int $blog_id ID of the new blog.
 */
function wpas_activate_new_site( $blog_id ) {

	if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
		return;
	}

	switch_to_blog( $blog_id );
	wpas_single_activate();
	restore_current_blog();

}

/**
 * Fired for each blog when the plugin is activated.
 *
 * @since    1.0.0
 * @return void
 */
function wpas_single_activate() {

	// Get user capabilities.
	$caps = wpas_get_user_capabilities();

	// Get base roles for all custom roles.
	$subscriber = get_role( 'subscriber' );
	$admin      = get_role( 'administrator' );

	// Create the new, custom roles.
	$supervisor = add_role( 'wpas_manager', esc_attr__( 'Support Supervisor', 'awesome-support' ), get_role( 'editor' )->capabilities );
	$manager    = add_role( 'wpas_support_manager', esc_attr__( 'Support Manager', 'awesome-support' ), $subscriber->capabilities );
	$agent      = add_role( 'wpas_agent', esc_attr__( 'Support Agent', 'awesome-support' ), get_role( 'author' )->capabilities );
	$user       = add_role( 'wpas_user', esc_attr__( 'Support User', 'awesome-support' ), $subscriber->capabilities );

	foreach ( $caps as $cap => $roles ) {

		// Add admin capabilities.
		if ( in_array( 0, $roles, true ) ) {
			$admin->add_cap( $cap );
		}

		// Add supervisor capabilities.
		if ( in_array( 1, $roles, true ) ) {
			$supervisor->add_cap( $cap );
		}

		// Add manager capabilities.
		if ( in_array( 2, $roles, true ) ) {
			$manager->add_cap( $cap );
		}

		// Add agent capabilities.
		if ( in_array( 3, $roles, true ) ) {
			$agent->add_cap( $cap );
		}

		// Add user capabilities.
		if ( in_array( 4, $roles, true ) ) {
			$user->add_cap( $cap );
		}
	}

	add_option( 'wpas_options', serialize( get_settings_defaults() ) );
	add_option( 'wpas_setup', 'pending' );
	add_option( 'wpas_redirect_about', true );
	add_option( 'wpas_support_products', 'pending' );
	add_option( 'wpas_db_version', WPAS_DB_VERSION );
	add_option( 'wpas_version', WPAS_VERSION );

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
function wpas_get_blog_ids() {

	global $wpdb;

	// get an array of blog ids
	$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

	return $wpdb->get_col( $sql );

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
function wpas_create_pages() {

	$options = unserialize( get_option( 'wpas_options', array() ) );
	$update  = false;

	if ( empty( $options['ticket_list'] ) ) {

		$list_args = array(
				'post_content'   => '[tickets]',
				'post_title'     => wp_strip_all_tags( __( 'My Tickets', 'awesome-support' ) ),
				'post_name'      => sanitize_title( __( 'My Tickets', 'awesome-support' ) ),
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'ping_status'    => 'closed',
				'comment_status' => 'closed'
		);

		$list = wp_insert_post( $list_args, true );

		if ( ! is_wp_error( $list ) && is_int( $list ) ) {
			$options['ticket_list'] = $list;
			$update                 = true;
		}
	}

	if ( empty( $options['ticket_submit'] ) ) {

		$submit_args = array(
				'post_content'   => '[ticket-submit]',
				'post_title'     => wp_strip_all_tags( __( 'Submit Ticket', 'awesome-support' ) ),
				'post_name'      => sanitize_title( __( 'Submit Ticket', 'awesome-support' ) ),
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'ping_status'    => 'closed',
				'comment_status' => 'closed'
		);

		$submit = wp_insert_post( $submit_args, true );

		if ( ! is_wp_error( $submit ) && is_int( $submit ) ) {
			$options['ticket_submit'] = $submit;
			$update                   = true;
		}

	}

	if ( $update ) {
		update_option( 'wpas_options', serialize( $options ) );
	}

	if ( ! empty( $options['ticket_submit'] ) && ! empty( $options['ticket_list'] ) ) {
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
function wpas_flush_rewrite_rules() {
	flush_rewrite_rules();
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
function wpas_ask_support_products() {

	global $pagenow;

	$args_single = $args_multiple = $_GET;

	if ( ! isset( $get ) || ! is_array( $get ) ) {
		$get = array();
	}

	$args_single['products']   = 'single';
	$args_multiple['products'] = 'multiple';
	?>
	<div class="updated">
		<p><?php _e( 'Will you be supporting multiple products on this support site? You can activate multi-products support now. <small>(This setting can be modified later)</small>', 'awesome-support' ); ?></p>

		<p>
			<a href="<?php echo wp_sanitize_redirect( wpas_do_url( admin_url( $pagenow ), 'admin_products_option', $args_single ) ); ?>"
			   class="button-secondary"><?php _e( 'Single Product', 'awesome-support' ); ?></a>
			<a href="<?php echo wp_sanitize_redirect( wpas_do_url( admin_url( $pagenow ), 'admin_products_option', $args_multiple ) ); ?>"
			   class="button-secondary"><?php _e( 'Multiple Products', 'awesome-support' ); ?></a>
		</p>
	</div>
<?php }