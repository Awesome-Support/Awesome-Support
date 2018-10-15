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
		add_action('wpas_after_close_ticket', array($this, 'after_close_ticket'), 20, 3 );
		
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
				mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_agent_points_ticket_close'), 'Points for agent closing ticket #' . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_agent_point_type') );
			}
			
			// Add points for user closing a ticket
			if ( false == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_user_point_type' ) ) ) {
				mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_user_points_ticket_close'), 'Points for user closing ticket #' . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_user_point_type') );
			}
			
			// Add points for agent even if user closes a ticket...
			if ( false == wpas_is_agent( $user_id ) && !empty( wpas_get_option('myCRED_agent_point_type' ) ) && true == wpas_get_option('myCRED_agent_gets_points_user_close' )  ) {
				mycred_add( $ticket_id, $user_id, wpas_get_option('myCRED_agent_points_ticket_close'), 'Agent gets points when user closed ticket #' . (string) $ticket_id, $ticket_id, '', wpas_get_option('myCRED_agent_point_type') );				
			}				
			
		}
		
	}
	 
 }
 
 // Instantiate the class here...
 if ( true === boolval( wpas_get_option( 'enable_my_cred', false ) ) ) {	 
	$my_cred = new WPAS_MY_CRED();	 
 }