<?php
/**
 * @package   Awesome Support/Admin/Functions/Metaboxes
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'add_meta_boxes', 'wpas_metaboxes' );
/**
 * Register the metaboxes.
 *
 * The function below registers all the metaboxes used
 * in the ticket edit screen.
 *
 * @since 3.0.0
 */
function wpas_metaboxes() {

	global $pagenow;

	/* Remove the publishing metabox */
	remove_meta_box( 'submitdiv', 'ticket', 'side' );

	/**
	 * Register the metaboxes.
	 */
	/* Issue details, only available for existing tickets */
	if( isset( $_GET['post'] ) ) {
		add_meta_box( 'wpas-mb-message', __( 'Ticket', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'normal', 'high', array( 'template' => 'message' ) );

		$status = get_post_meta( intval( $_GET['post'] ), '_wpas_status', true );

		if ( '' !== $status ) {
			add_meta_box( 'wpas-mb-replies', __( 'Ticket Replies', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'normal', 'high', array( 'template' => 'replies' ) );
		}
	}

	/* Ticket details */
	add_meta_box( 'wpas-mb-details', __( 'Ticket Details', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'high', array( 'template' => 'details' ) );
	
	
	/* Client profile */
	if ( 'post-new.php' !== $pagenow ) {
		add_meta_box( 'wpas-mb-user-profile', __( 'User Profile', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'high', array( 'template' => 'user-profile' ) );
	}

	/* Front End Custom Fields */
	if ( WPAS()->custom_fields->have_custom_fields() ) {
		add_meta_box( 'wpas-mb-cf', __( 'Custom Fields', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'default', array( 'template' => 'custom-fields' ) );
	}

	/* Back End Custom Fields */
	if ( WPAS()->custom_fields->have_custom_fields() ) {
		add_meta_box( 'wpas-mb-becf', __( 'Admin Only Custom Fields', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'default', array( 'template' => 'custom-fields-backend' ) );
	}	
	
	/* Ticket Statistics */
	add_meta_box( 'wpas-mb-ticket-statistics', __( 'Ticket Statistics', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'default', array( 'template' => 'ticket-statistics' ) );

	/* Time tracking statistics*/
	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

	if ( isset( $options['show_basic_time_tracking_fields'] ) && true === boolval( $options['show_basic_time_tracking_fields'] ) ) {	
		add_meta_box( 'wpas-mb-ticket-time-tracking', __( 'Time Tracking', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'default', array( 'template' => 'time-tracking-statistics' ) );
	}

	
	/* Additional Interested Parties */
	if (  ( isset( $options['multiple_agents_per_ticket'] ) && true === boolval( $options['multiple_agents_per_ticket'] ) ) or ( isset( $options['show_third_party_fields'] ) && true === boolval( $options['show_third_party_fields'] ) ) ) {
		add_meta_box( 'wpas-mb-ticket-addl-parties', __( 'Additional Interested Parties', 'awesome-support' ), 'wpas_metabox_callback', 'ticket', 'side', 'default', array( 'template' => 'ticket-additional-parties' ) );
	}
	
}

/**
 * Metabox callback function.
 *
 * The below function is used to call the metaboxes content.
 * A template name is given to the function. If the template
 * does exist, the metabox is loaded. If not, nothing happens.
 *
 * @since  3.0.0
 *
 * @param  int   $post Post ID
 * @param  array $args Arguments passed to the callback function
 *
 * @return void
 */
function wpas_metabox_callback( $post, $args ) {

	if ( ! is_array( $args ) || ! isset( $args['args']['template'] ) ) {
		_e( 'An error occurred while registering this metabox. Please contact support.', 'awesome-support' );
	}

	$template = $args['args']['template'];

	if ( ! file_exists( WPAS_PATH . "includes/admin/metaboxes/$template.php" ) ) {
		_e( 'An error occured while loading this metabox. Please contact support.', 'awesome-support' );
	}

	/* Include the metabox content */
	include_once( WPAS_PATH . "includes/admin/metaboxes/$template.php" );

}