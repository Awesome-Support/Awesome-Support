<?php
/**
 * Adds integration with the MYCRED plugin
 *
 * @author    Awesome Support <contact@awesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2018 AwesomeSupport
 *
 */

 class WPAS_MY_CRED {

	/**
	 * Plugin actions.
	 */
	public function __construct() {	
	
		// Add some action hooks here!
		add_action('wpas_after_close_ticket',	array($this, 'after_close_ticket'), 20, 3 );
		add_action('wpas_add_reply_after',		array($this, 'after_reply_ticket'), 20, 2 );
		add_action('wpas_open_ticket_after',	array($this, 'after_open_ticket'), 20, 2 );
		add_action('wpas_post_new_ticket_admin',array($this, 'after_open_ticket_admin'), 20, 1 );
	}
	
	/**
	 * Add points when a ticket is closed
	 *
	 * Action hook: wpas_after_close_ticket
	 *
	 * @param $ticket_id integer
	 * @param $update array
	 * @param $user_id integer
	 *
	 * @return void
	 */
	public function after_close_ticket($ticket_id, $update, $user_id) {

		if ( function_exists('mycred_add') ) {
			
			// Add points for agent closing ticket.
			if ( true == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_agent_point_type' ) ) ) {
				mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_agent_points_ticket_close'), __('Points for agent closing ticket #', 'awesome-support') . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_agent_point_type') );
			}
			
			// Add points for user closing a ticket
			if ( false == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_user_point_type' ) ) ) {
				mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_user_points_ticket_close'), __('Points for user closing ticket #', 'awesome-support') . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_user_point_type') );
			}
			
			// Add points for agent even if user closes a ticket...
			if ( false == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_agent_point_type' ) ) && true == wpas_get_option('myCRED_agent_gets_points_user_close' )  ) {
				
				// Who is the primary agent on the ticket?
				$agent_id = wpas_get_primary_agent_by_ticket_id( $ticket_id );
				
				if ( $agent_id ) {
					mycred_add( $ticket_id, $agent_id, wpas_get_option('myCRED_agent_points_ticket_close'), __('Agent gets points when user closed ticket #', 'awesome-support') . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_agent_point_type') );				
				}
				
			}
			
		}
	}
		
	/**
	 * Add points when a reply is added to a ticket
	 *
	 * Action hook: wpas_add_reply_after
	 *
	 * @param $reply_id integer
	 * @param $data array
	 *
	 * @return void
	 */
	public function after_reply_ticket($reply_id, $data) {

		if ( function_exists('mycred_add') ) {
			
			$ticket_id = wpas_get_ticket_id( $reply_id ) ;
			$user_id = get_post($reply_id)->post_author;
			
			if ( $user_id && ! is_wp_error( $user_id ) ) {
				// Add points for agent sending a reply ticket.
				if ( true == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_agent_point_type' ) ) ) {
					mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_agent_points_ticket_reply'), __('Points for agent replying to ticket #', 'awesome-support') . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_agent_point_type') );
				}
				
				// Add points for user replying to a ticket
				if ( false == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_user_point_type' ) ) ) {
					mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_user_points_ticket_reply'), __('Points for user replying to ticket #', 'awesome-support') . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_user_point_type') );
				}
			}
		}
		
	}
	
	/**
	 * Add points when a new ticket is opened by user
	 *
	 * Action hook: wpas_open_ticket_after
	 *
	 * @param $ticket_id integer
	 * @param $data array
	 *
	 * @return void
	 */
	public function after_open_ticket($ticket_id, $data) {

		if ( function_exists('mycred_add') ) {

			$user_id = get_post($ticket_id)->post_author;

			if ( $user_id && ! is_wp_error( $user_id ) ) {
				// Add points for agent opening ticket. Normally they open it on the back-end but if using the agent-front-end, might open ticket from there.
				if ( true == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_agent_point_type' ) ) ) {
					mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_agent_points_ticket_submit'), __('Points for agent opening ticket #', 'awesome-support') . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_agent_point_type') );
				}
				
				// Add points for user opening a ticket
				if ( false == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_user_point_type' ) ) ) {
					mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_user_points_ticket_submit'), __('Points for user opening a ticket #', 'awesome-support') . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_user_point_type') );
				}
			}
		}
		
	}
	
	/**
	 * Add points when a new ticket is opened in the admin area
	 *
	 * Action hook: wpas_post_new_ticket_admin
	 *
	 * @param $ticket_id integer
	 *
	 * @return void
	 */
	public function after_open_ticket_admin($ticket_id) {

		if ( function_exists('mycred_add') ) {

			//$user_id = get_post($ticket_id)->post_author;
			$user_id = wpas_get_primary_agent_by_ticket_id( $ticket_id ) ;
			
			if ( $user_id && ! is_wp_error( $user_id ) ) {
				// Add points for agent opening a ticket
				if ( true == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_agent_point_type' ) ) ) {
					mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_agent_points_ticket_submit'), __('Points for agent opening ticket #', 'awesome-support') . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_agent_point_type') );
				}
			}
		}
		
	}	
	 
 }
 
 // Instantiate the class here...
 if ( true === boolval( wpas_get_option( 'enable_my_cred', false ) ) ) {	 
	$my_cred = new WPAS_MY_CRED();	 
 }