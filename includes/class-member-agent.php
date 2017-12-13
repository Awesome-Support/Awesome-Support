<?php
/**
 * Awesome Support Agent.
 *
 * @package   Awesome Support/Agent
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

add_action( 'wpas_ticket_assignee_changed', 'wpas_update_ticket_count_on_transfer', 10, 2 );
/**
 * Update the open agent tickets count when a ticket is transferred from one agent to another
 *
 * We do not need to add a new ticket to the new agent because it is automatically done in wpas_assign_ticket()
 *
 * @since 3.2.8
 *
 * @param int $agent_id          ID of the current ticket assignee
 * @param int $previous_agent_id ID of the previous assignee
 *
 * @return void
 */
function wpas_update_ticket_count_on_transfer( $agent_id, $previous_agent_id ) {

	$agent_prev = new WPAS_Member_Agent( $previous_agent_id );
	$agent_prev->ticket_minus();

}

class WPAS_Member_Agent extends WPAS_Member {

	/**
	 * Agent's departments
	 *
	 * @since 3.3
	 * @var array|bool
	 */
	protected $department;

	public function __construct( $user ) {
		parent::__construct( $user );
	}

	/**
	 * Check if the user had agent capability
	 *
	 * @since 3.2
	 * @return bool|WP_Error
	 */
	public function is_agent() {

		if ( false === $this->is_member() ) {
			return new WP_Error( 'user_not_exists', sprintf( __( 'The user with ID %d does not exist', 'awesome-support' ), $this->user_id ) );
		}

		if ( false === $this->has_cap( 'edit_ticket' ) ) {
			return new WP_Error( 'user_not_agent', __( 'The user exists but is not a support agent', 'awesome-support' ) );
		}

		return true;

	}

	/**
	 * Check if the agent can be assigned to new tickets
	 *
	 * @since 3.2
	 * @return bool
	 */
	public function can_be_assigned() {

		$can = esc_attr( get_user_option( 'wpas_can_be_assigned', $this->user_id ) );

		return empty( $can ) ? false : true;
	}

	/**
	 * Count the number of open tickets for this agent
	 *
	 * @since 3.2
	 * @return int
	 */
	public function open_tickets() {

		// Deactivate this for now as it is not reliable enough. Needs more work. Ticket count not correctly updated in certain situations, like when a ticket is transferred from an agent to another
//		$count = get_user_option( 'wpas_open_tickets', $this->user_id );
		$count = false;
		if ( false === $count ) {
			$count = count( $this->get_open_tickets() );
			update_user_option( $this->user_id, 'wpas_open_tickets', $count );
		}

		return $count;

	}

	/**
	 * Increment the number of open tickets
	 *
	 * @since 3.2
	 *
	 * @param int $num Number of tickets to increment
	 *
	 * @return int Number of open tickets
	 */
	public function ticket_plus( $num = 1 ) {

		$count = (int) $this->open_tickets();
		$count = $count + $num;

		update_user_option( $this->user_id, 'wpas_open_tickets', $count );

		return $count;

	}

	/**
	 * Decrement the number of open tickets
	 *
	 * @since 3.2
	 *
	 * @param int $num Number of tickets to decrement
	 *
	 * @return int Number of open tickets
	 */
	public function ticket_minus( $num = 1 ) {

		$count = (int) $this->open_tickets();
		$count = $count - $num;

		update_user_option( $this->user_id, 'wpas_open_tickets', $count );

		return $count;

	}

	/**
	 * Get all open tickets assigned to the agent
	 *
	 * @since 3.2
	 * @return array
	 */
	public function get_open_tickets() {

		$args                 = array();
		$args['meta_query'][] = array(
				'key'     => '_wpas_assignee',
				'value'   => $this->user_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
		);

		$open_tickets = wpas_get_tickets( 'open', $args );

		return $open_tickets;

	}

	/**
	 * Get the agent's departments
	 *
	 * @since 3.3
	 * @return bool|array
	 */
	public function in_department() {

		if ( false === wpas_get_option( 'departments', false ) ) {
			return false;
		}

		if ( is_null( $this->department ) ) {

			$this->department = get_user_option( 'wpas_department', $this->user_id );

			if ( empty( $this->department ) ) {
				$this->department = array();
			}

		}

		return apply_filters( 'wpas_agent_department', $this->department, $this->user_id );

	}

	/**
	 * Check if the agent belongs to a given department
	 *
	 * @since 3.3
	 *
	 * @param int $term_id ID of the department taxonomy term
	 *
	 * @return bool
	 */
	public function belongs_department( $term_id ) {

		if ( false === $this->in_department() ) {
			return false;
		}

		return in_array( $term_id, $this->in_department() ) ? true : false;

	}

}
