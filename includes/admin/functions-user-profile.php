<?php
/**
 * @package   Awesome Support/Admin/Functions/User Profile
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Get the list of user profile data to display in the user profile metabox
 *
 * @since 3.3
 *
 * @param int $ticket_id Current ticket iD
 *
 * @return array
 */
function wpas_user_profile_get_contact_info( $ticket_id ) {

	$data = array(
		'name',
		'role',
		'email',
	);

	return apply_filters( 'wpas_user_profile_contact_info', $data, $ticket_id );

}

/**
 * Get the content of a user profile data field
 *
 * User profile data fields are declared in wpas_user_profile_get_contact_info()
 *
 * @since 3.3
 *
 * @param string  $info      ID of the information field being displayed
 * @param WP_User $user      The current user object (the creator of the ticket)
 * @param int     $ticket_id ID of the current ticket
 *
 * @return void
 */
function wpas_user_profile_contact_info_contents( $info, $user, $ticket_id ) {

	if ( !$user ) {
		return;
	}

	switch ( $info ) {

		case 'name':
			echo apply_filters( 'wpas_user_profile_contact_name', $user->data->display_name, $user, $ticket_id );
			break;

		case 'role':
			echo wp_kses_post( sprintf( __( 'Support User since %s', 'awesome-support' ), '<strong>' . date( get_option( 'date_format' ), strtotime( $user->data->user_registered ) ) . '</strong>' ) );
			break;

		case 'email':
			printf( '<a href="mailto:%1$s">%1$s</a>', $user->data->user_email );
			break;

		default:
			do_action( 'wpas_user_profile_info_' . $info, $user, $ticket_id );
			break;

	}

}