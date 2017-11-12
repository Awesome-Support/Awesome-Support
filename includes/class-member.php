<?php
/**
 * Awesome Support Member.
 *
 * @package   Awesome Support
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WPAS_Member
 *
 * @since 3.3
 */
class WPAS_Member {

	/**
	 * User ID
	 *
	 * @since 3.3
	 * @var int
	 */
	public $user_id;

	/**
	 * User profile data
	 *
	 * @since 3.3
	 * @var array
	 */
	public $data;

	/**
	 * User roles
	 *
	 * @since 3.3
	 * @var array
	 */
	public $roles;

	/**
	 * User capabilities
	 *
	 * @since 3.3
	 * @var array
	 */
	public $caps;

	/**
	 * Whether or not the user requested is a member of Awesome Support
	 *
	 * @since 3.3
	 * @var bool
	 */
	public $is_member;

	/**
	 * WPAS_Member constructor.
	 *
	 * @param int|stdClass $user The user ID or stdClass
	 * @throws Exception
	 */
	public function __construct( $user ) {

		if ( is_numeric( $user ) ) {
			$user = $this->get_user_data( (int) $user );
		}

		if ( is_object( $user ) && $user instanceof stdClass ) {
			$this->init( $user );
		}

	}

	/**
	 * Setup the user data
	 *
	 * @since 3.3
	 *
	 * @param $data
	 *
	 * @return void
	 */
	protected function init( $data ) {

		$defaults = $this->data_defaults();

		foreach ( $defaults as $field => $value ) {

			if ( isset ( $data->{$field} ) ) {
				$defaults[ $field ] = $data->{$field};
			}

		}

		// Set the user profile data
		$this->user_id = $data->ID;
		$this->data    = $defaults;

		// Setup user metadata
		$this->setup_roles();
		$this->setup_caps();
		$this->is_member();

	}

	/**
	 * Get user data by user ID
	 *
	 * @since 3.3
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	protected function get_user_data( $user_id ) {

		global $wpdb;

		$user = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE ID = '%d'", $user_id ) );

		if ( empty( $user ) ) {
			return array();
		}

		return $user[0];

	}

	/**
	 * Get the default profile data fields
	 *
	 * This is also used as a whitelist. Only fields listed in the defaults will be actually used as the user $data
	 *
	 * @since 3.3
	 * @return array
	 */
	protected function data_defaults() {

		return apply_filters( 'wpas_member_data_defaults', array(
			'ID'              => '',
			'user_login'      => '',
			'user_nicename'   => '',
			'user_email'      => '',
			'user_url'        => '',
			'user_registered' => '',
			'display_name'    => '',
		) );

	}

	/**
	 * Setup the user roles
	 *
	 * @since 3.3
	 * @return void
	 */
	protected function setup_roles() {

		global $wpdb;

		$cap_key = $wpdb->get_blog_prefix() . 'capabilities';
		$roles   = get_user_option( $cap_key, $this->user_id );

		if ( ! is_array( $roles ) ) {
			$roles = array();
		}

		$this->roles = array_keys( $roles );

	}

	/**
	 * Setup the user capabilities based on their roles
	 *
	 * This method is mostly based on WordPress' WP_User::get_role_caps()
	 *
	 * @since 3.3
	 * @return void
	 */
	protected function setup_caps() {

		$wp_roles = wp_roles();

		// Build $allcaps from role caps, overlay user's $caps
		$this->caps = array();

		foreach ( (array) $this->roles as $role ) {
			
			$the_role   = $wp_roles->get_role( $role );
			
			If ( ! empty ( $the_role->capabilities ) ) {
				$this->caps = array_merge( (array) $this->caps, (array) $the_role->capabilities );
			}
		}

	}

	/**
	 * Check if the current user actually is an Awesome Support member
	 *
	 * @since 3.3
	 * @return bool
	 */
	public function is_member() {

		// We assume that a user with the capability view_ticket is a member because this is one cap that all AS users have
		$cap = 'view_ticket';

		if ( is_null( $this->is_member ) ) {
			$this->is_member = apply_filters( 'wpas_member_is_member', array_key_exists( $cap, $this->caps ), $this->user_id, $cap, $this->roles, $this->caps );
		}

		// If the user is not a member we reset its profile data
		if ( false === $this->is_member ) {
			$this->data = $this->data_defaults();
		}

		return $this->is_member;

	}

	/**
	 * Check if the user has a specific capability
	 *
	 * @since 3.3
	 *
	 * @param string $cap Capability to check
	 *
	 * @return bool
	 */
	public function has_cap( $cap ) {
		return array_key_exists( $cap, $this->caps );
	}

}