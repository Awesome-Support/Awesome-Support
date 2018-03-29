<?php
/**
 * Awesome Support Members Query.
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
 * Class WPAS_Members_Query
 *
 * This class is intended to handle all users query for Awesome Support. Its main benefit compared to WP_User_Query is
 * its simplicity and its caching management. Especially for sites with lots of users, using WP_User_Query slows down
 * the site quite a lot mostly because of all the caching the WordPress does with user metas. This class has some level
 * of caching but does not go as far as WP_User_Query making its queries lightweight.
 *
 * @since 3.3
 */
class WPAS_Member_Query {

	/**
	 * Capabilities to query users by
	 *
	 * @since 3.3
	 * @var array
	 */
	protected $cap = array();

	/**
	 * Capabilities to exclude from the query
	 *
	 * @since 3.3
	 * @var array
	 */
	protected $cap_exclude = array();

	/**
	 * Array of user IDs to exclude from the query
	 *
	 * @since 3.3
	 * @var array
	 */
	protected $exclude = array();

	/**
	 * Array of user IDs to query
	 *
	 * @since 3.3
	 * @var array
	 */
	protected $ids = array();

	/**
	 * Order field name used to sort results, default is ID
	 * 
	 * @var string
	 */
	protected $orderby = 'ID';
	
	/**
	 * Order type to sort results either ASC or DESC
	 * 
	 * @var string 
	 */
	protected $order = 'ASC';
	
	/**
	 * Whether or not to convert the results into WPAS_Member (sub)objects
	 *
	 * @since 3.3
	 * @var string
	 */
	protected $output = 'stdClass';

	/**
	 * Array of WPAS_Member (or its sub-classes) objects, result of the SQL query
	 *
	 * @since 3.3
	 * @var array
	 */
	public $members = array();

	/**
	 * List of roles to include in the query
	 *
	 * @since 3.3
	 * @var array
	 */
	public $roles = array();

	/**
	 * User fields to return
	 *
	 * @since 3.3
	 * @var string
	 */
	public $fields = '*';

	/**
	 * The hash of this instance settings
	 *
	 * Used for caching the results.
	 *
	 * @since 3.3
	 * @var string
	 */
	public $hash;

	/**
	 * Search term
	 *
	 * @since 3.3
	 * @var array
	 */
	public $search = array();

	/**
	 * WPAS_Members_Query constructor.
	 *
	 * @since 3.3
	 *
	 * @param array $args Query args
	 */
	public function __construct( $args = array() ) {
		
		$this->cap         = isset( $args['cap'] ) ? (array) $args['cap'] : array();
		$this->cap_exclude = isset( $args['cap_exclude'] ) ? (array) $args['cap_exclude'] : array();
		$this->exclude     = isset( $args['exclude'] ) ? (array) $args['exclude'] : array();
		$this->ids         = isset( $args['ids'] ) ? (array) $args['ids'] : array();
		$this->fields      = isset( $args['fields'] ) ? $this->sanitize_fields( (array) $args['fields'] ) : '*';
		$this->output      = isset( $args['output'] ) ? $this->sanitize_output_format( $args['output'] ) : 'stdClass';
		$this->search      = isset( $args['search'] ) ? $args['search'] : array();
		$this->orderby	   = isset( $args['orderby'] ) ? $args['orderby'] : $this->orderby;
		$this->order	   = isset( $args['order'] ) ? $args['order'] : $this->order;
		$this->hash        = md5( serialize( $args ) );

		// Run the whole process
		$this->get_members();

	}

	/**
	 * Sanitize the requested output format
	 *
	 * @since 3.3
	 *
	 * @param string $format Output format
	 *
	 * @return string
	 */
	protected function sanitize_output_format( $format ) {

		if ( in_array( $format, array( 'wpas_member' ) ) ) {
			return $format;
		} else {
			return 'stdClass';
		}

	}

	/**
	 * Sanitize list of requested fields
	 *
	 * @since 3.3
	 *
	 * @param array $fields Requested fields
	 *
	 * @return array
	 */
	protected function sanitize_fields( $fields ) {

		$allowed = array(
			'*',
			'ID',
			'display_name'
		);

		foreach ( $fields as $key => $field ) {
			if ( ! in_array( $field, $allowed ) ) {
				unset( $fields[ $key ] );
			}
		}

		return ! empty( $fields ) ? implode( ',', $fields ) : '*';

	}

	/**
	 * Get all roles that have specific capabilities
	 *
	 * If multiple capabilities are given then only roles with all of the capabilities will be included.
	 *
	 * @since 3.3
	 *
	 * @return array
	 */
	protected function get_roles() {

		global $wp_roles;

		$roles = array();

		foreach ( $wp_roles->roles as $role_id => $role ) {

			$has = false; // Whether the current role has the capabilities

			foreach ( $this->cap as $capability ) {
				$has = array_key_exists( $capability, $role['capabilities'] ) ? true : false;
			}

			if ( true === $has ) {
				foreach ( $this->cap_exclude as $capability ) {
					if ( array_key_exists( $capability, $role['capabilities'] ) ) {
						$has = false;
					}
				}
			}

			if ( true === $has ) {
				array_push( $roles, $role_id );
			}

		}

		return $this->roles = $roles;

	}

	/**
	 * Convert a stdClass user object into a WPAS member object
	 *
	 * @since 3.3
	 *
	 * @param stdClass $user
	 *
	 * @return WPAS_Member|WPAS_Member_Agent|WPAS_Member_User|array
	 */
	protected function create_member_object( $user ) {

		global $wp_roles;

		$r     = unserialize( $user->meta_value );
		$roles = array();
		$class = '';

		foreach ( $r as $the_role => $val ) {
			$roles[] = $the_role;
		}

		foreach ( $roles as $role ) {

			if ( ! array_key_exists( $role, $wp_roles->roles ) ) {
				continue;
			}

			if ( array_key_exists( 'edit_ticket', $wp_roles->roles[ $role ]['capabilities'] ) ) {
				$class = 'WPAS_Member_Agent';
				break;
			} elseif ( array_key_exists( 'create_ticket', $wp_roles->roles[ $role ]['capabilities'] ) ) {
				$class = 'WPAS_Member_User';
				break;
			} else {
				continue;
			}

		}

		if ( empty( $class ) ) {
			return array();
		}

		$member = new $class( $user );

		return $member;

	}

	/**
	 * Get the SQL query result and convert each user into a WPAS_Member (sub)object
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	protected function convert_sql_result() {

		$users = array();

		foreach ( $this->members as $user ) {

			$usr = $this->create_member_object( $user );

			if ( ! empty( $usr ) ) {
				$users[] = $usr;
			}

		}

		$this->members = $users;

	}

	/**
	 * Get the members
	 *
	 * @since 3.3
	 * @return void
	 */
	public function get_members() {

		$this->members = wp_cache_get( 'users_' . $this->hash, 'wpas' );

		if ( false === $this->members ) {
			$this->query();
		}

		if ( 'wpas_member' === $this->output ) {
			$this->convert_sql_result();
		}

	}

	/**
	 * Run the SQL query
	 *
	 * @since 3.3
	 * @return array
	 */
	protected function query() {

		global $wpdb;

		$prefix = $wpdb->get_blog_prefix();
		$roles  = $this->get_roles();

		// Set the base SQL query
		$sql = "SELECT $this->fields FROM $wpdb->users";

		if ( ! empty( $roles ) ) {

			$like = array();

			foreach ( $roles as $role ) {
				$like[] = sprintf( 'CAST(%1$s AS CHAR) LIKE "%2$s"', "$wpdb->usermeta.meta_value", "%$role%" );
			}

			$like = implode( ' OR ', $like );

			$sql .= " INNER JOIN $wpdb->usermeta ON ( $wpdb->users.ID = $wpdb->usermeta.user_id ) 
			WHERE 1=1 
			AND ( ( ( $wpdb->usermeta.meta_key = '{$prefix}capabilities' 
			AND ( $like ) ) ) )";

		}

		// Exclude user IDs
		if ( ! empty( $this->exclude ) ) {

			// Prepare the IDs query var
			$ids = array_map( 'intval', implode( ',', $this->exclude ) );

			// Exclude users by ID
			$sql .= " AND ID NOT IN ($ids)";

		}

		// Include user IDs
		if ( ! empty( $this->ids ) ) {

			// Prepare the IDs query var
			$ids = array_map( 'intval', implode( ',', $this->ids ) );

			// Exclude users by ID
			$sql .= " AND ID IN ($ids)";

		}

		// Include search parameter
		if ( ! empty( $this->search ) && isset( $this->search['query'] ) && isset( $this->search['fields'] ) ) {

			if ( ! isset( $this->search['relation'] ) ) {
				$this->search['relation'] = 'OR';
			}

			$search_query = array();
			$operator     = empty( $search_query ) ? 'OR' : $this->search['relation'];

			foreach ( $this->search['fields'] as $field ) {
				if( 'ID' === $field ) {
					$search_query[] = "CAST({$wpdb->users}.{$field} AS CHAR) LIKE '%{$this->search['query']}%'";
				} else {
					$search_query[] = "{$wpdb->users}.{$field} LIKE '%{$this->search['query']}%'";
				}
			}

			$search_query = implode( " $operator ", $search_query );

			$sql .= " AND ($search_query)";

		}

		// Order users by provided args or default by login ID
		
		$order_field = $this->orderby ? $this->orderby : 'ID';
		$order_type = $this->order ? $this->order : 'ASC';
		
		$sql .= " ORDER BY {$wpdb->users}.{$order_field} {$order_type}";
		$this->members = $wpdb->get_results( $sql );

		// Cache the results
		wp_cache_add( 'users_' . $this->hash, $this->members, 'wpas' );

		return $this->members;

	}

}