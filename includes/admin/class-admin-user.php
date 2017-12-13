<?php
/**
 * User.
 *
 * @package   Admin/User
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

class WPAS_User {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {
		add_action( 'edit_user_profile',          array( $this, 'user_profile_custom_fields' ) ); // Add user preferences
		add_action( 'show_user_profile',          array( $this, 'user_profile_custom_fields' ) ); // Add user preferences
		add_action( 'personal_options_update',    array( $this, 'save_user_custom_fields' ) );    // Save the user preferences
		add_action( 'edit_user_profile_update',   array( $this, 'save_user_custom_fields' ) );    // Save the user preferences when modified by admins
		add_action( 'user_register',              array( $this, 'enable_assignment' ), 10, 1 );   // Enable auto-assignment for new users
		add_filter( 'manage_users_columns',       array( $this, 'auto_assignment_user_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'auto_assignment_user_column_content' ), 10, 3 );

		/**
		 * Custom profile fields
		 */
		add_action( 'wpas_user_profile_fields', array( $this, 'profile_field_user_can_be_assigned' ), 10, 1 );
		add_action( 'wpas_user_profile_fields', array( $this, 'profile_field_smart_tickets_order' ), 10, 1 );
		add_action( 'wpas_user_profile_fields', array( $this, 'profile_field_after_reply' ), 10, 1 );
		add_action( 'wpas_user_profile_fields', array( $this, 'profile_field_user_view_all_tickets' ), 10, 1 );
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
	 * Add user preferences to the profile page.
	 *
	 * @since  3.0.0
	 *
	 * @param WP_User $user
	 *
	 * @return bool|void
	 */
	public function user_profile_custom_fields( $user ) {

		if ( ! user_can( $user->ID, 'edit_ticket' ) ) {
			return false;
		} ?>

		<h3><?php _e( 'Awesome Support Preferences', 'awesome-support' ); ?></h3>

		<table class="form-table">
			<tbody>
				<?php do_action( 'wpas_user_profile_fields', $user ); ?>
			</tbody>
		</table>
	<?php }

	/**
	 * User profile field "tickets order"
	 *
	 * Let the user selects the order in which his tickets appear in the tickets list screen.
	 *
	 * @since 3.3.2
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function profile_field_smart_tickets_order( $user ) {

		if ( ! user_can( $user->ID, 'edit_ticket' ) ) {
			return;
		} ?>

		<tr class="wpas-after-reply-wrap">
			<th><label><?php esc_attr_e( 'Smart Tickets Order', 'awesome-support' ); ?></label></th>
			<td>
				<?php $smart = esc_attr( get_user_option( 'wpas_smart_tickets_order', $user->ID ) ); ?>
				<label for="wpas_smart_tickets_order"><input type="checkbox" name="wpas_smart_tickets_order" id="wpas_smart_tickets_order" value="yes" <?php if ( ! empty( $smart ) ) { echo 'checked'; } ?>> <?php esc_html_e( 'Enable', 'awesome-support' ); ?></label>
				<p class="description"><?php esc_attr_e( 'If Smart Tickets Order is enabled, Awesome Support will display tickets that need immediate attention at the top.', 'awesome-support' ); ?></p>
			</td>
		</tr>

	<?php }

	/**
	 * User profile field "after reply"
	 *
	 * @since 3.1.5
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function profile_field_after_reply( $user ) {

		if ( ! user_can( $user->ID, 'edit_ticket' ) ) {
			return;
		} ?>

		<tr class="wpas-after-reply-wrap">
			<th><label for="wpas_after_reply"><?php echo _x( 'After Reply', 'Action after replying to a ticket', 'awesome-support' ); ?></label></th>
			<td>
				<?php $after_reply = esc_attr( get_user_option( 'wpas_after_reply', $user->ID ) ); ?>
				<select name="wpas_after_reply" id="wpas_after_reply">
					<option value=""><?php _e( 'Default', 'awesome-support' ); ?></option>
					<option value="stay" <?php if ( $after_reply === 'stay' ): ?>selected="selected"<?php endif; ?>><?php _e( 'Stay on screen', 'awesome-support' ); ?></option>
					<option value="back" <?php if ( $after_reply === 'back' ): ?>selected="selected"<?php endif; ?>><?php _e( 'Back to list', 'awesome-support' ); ?></option>
					<option value="ask" <?php if ( $after_reply === 'ask' ): ?>selected="selected"<?php endif; ?>><?php _e( 'Always ask', 'awesome-support' ); ?></option>
				</select>
				<p class="description"><?php _e( 'Where do you want to go after replying to a ticket?', 'awesome-support' ); ?></p>
			</td>
		</tr>

	<?php }

	/**
	 * User profile field "can be assigned"
	 *
	 * @since 3.1.5
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function profile_field_user_can_be_assigned( $user ) {

		if ( ! user_can( $user->ID, 'edit_ticket' ) ) {
			return;
		}

		if ( ! current_user_can( 'administrator' ) ) {
			return;
		} ?>

		<tr class="wpas-after-reply-wrap">
			<th><label><?php _e( 'Can Be Assigned', 'awesome-support' ); ?></label></th>
			<td>
				<?php $can_assign = esc_attr( get_user_option( 'wpas_can_be_assigned', $user->ID ) ); ?>
				<label for="wpas_can_be_assigned"><input type="checkbox" name="wpas_can_be_assigned" id="wpas_can_be_assigned" value="yes" <?php if ( ! empty( $can_assign ) ) { echo 'checked'; } ?>> <?php _e( 'Yes', 'awesome-support' ); ?></label>
				<p class="description"><?php _e( 'Can the system assign new tickets to this user?', 'awesome-support' ); ?></p>
			</td>
		</tr>

	<?php }

	
	/**
	 * User profile field "View All Tickets"
	 *
	 * @since 3.3.5
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function profile_field_user_view_all_tickets( $user ) {

		if ( ! user_can( $user->ID, 'view_all_tickets' ) ) {
			return;
		} ?>

		<tr class="wpas-after-reply-wrap">
			<th><label><?php _e( 'View All Tickets', 'awesome-support' ); ?></label></th>
			<td>
				<?php $view_all_tickets = esc_attr( get_user_option( 'wpas_view_all_tickets', $user->ID ) ); ?>
				<label for="wpas_view_all_tickets"><input type="checkbox" name="wpas_view_all_tickets" id="wpas_view_all_tickets" value="yes" <?php if ( ! empty( $view_all_tickets ) ) { echo 'checked'; } ?>> <?php _e( 'Yes', 'awesome-support' ); ?></label>
				<p class="description"><?php _e( 'If agents role is allowed to view all tickets, turn on the option to do so?', 'awesome-support' ); ?></p>
			</td>
		</tr>

	<?php }	
	
	
	/**
	 * User profile field "departments"
	 *
	 * @since 3.3
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function profile_field_agent_department( $user ) {

		if ( ! user_can( $user->ID, 'edit_ticket' ) ) {
			return;
		}

		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		if ( false === wpas_get_option( 'departments', false ) ) {
			return;
		}

		$departments = get_terms( array(
			'taxonomy'   => 'department',
			'hide_empty' => false,
		) );

		if ( empty( $departments ) ) {
			return;
		}

		$current = get_user_option( 'wpas_department', $user->ID ); ?>

		<tr class="wpas-after-reply-wrap">
			<th><label><?php _e( 'Department(s)', 'awesome-support' ); ?></label></th>
			<td>
				<?php
				foreach ( $departments as $department ) {
					$checked = in_array( $department->term_id, $current ) ? 'checked="checked"' : '';
					printf( '<label for="wpas_department_%1$s"><input type="checkbox" name="%3$s" id="wpas_department_%1$s" value="%2$d" %5$s> %4$s</label><br>', $department->slug, $department->term_id, 'wpas_department[]', $department->name, $checked );
				}
				?>
				<p class="description"><?php esc_html_e( 'Which department(s) does this agent belong to?', 'awesome-support' ); ?></p>
			</td>
		</tr>

	<?php }

	/**
	 * Save the user preferences.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $user_id ID of the user to modify
	 *
	 * @return void
	 */
	public function save_user_custom_fields( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$wpas_after_reply = filter_input( INPUT_POST, 'wpas_after_reply' );
		$can_assign       = filter_input( INPUT_POST, 'wpas_can_be_assigned' );
		$smart            = filter_input( INPUT_POST, 'wpas_smart_tickets_order' );
		$view_all_tickets = filter_input( INPUT_POST, 'wpas_view_all_tickets' );
		$department       = isset( $_POST['wpas_department'] ) ? array_map( 'intval', $_POST['wpas_department'] ) : array();

		if ( $wpas_after_reply ) {
			update_user_option( $user_id, 'wpas_after_reply', $wpas_after_reply );
		}

		update_user_option( $user_id, 'wpas_can_be_assigned', $can_assign );
		update_user_option( $user_id, 'wpas_smart_tickets_order', $smart );
		update_user_option( $user_id, 'wpas_department', $department );
		update_user_option( $user_id, 'wpas_view_all_tickets', $view_all_tickets );

	}

	/**
	 * Enable auto-assignment for new agents
	 *
	 * @since 3.2
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function enable_assignment( $user_id ) {
		if ( user_can( $user_id, 'edit_ticket' ) && ! user_can( $user_id, 'administrator' ) ) {
			update_user_option( $user_id, 'wpas_can_be_assigned', 'yes' );
		}
	}

	/**
	 * Maybe enable auto assignment for this user
	 *
	 * Unfortunately there is no way to know what were the previous user capabilities
	 * which makes it impossible to safely enable auto-assignment.
	 * We are not able to differentiate a user being upgraded to support agent from a user
	 * who already was an agent but deactivated auto assignment and updated his profile.
	 *
	 * @since 3.2
	 *
	 * @param int   $user_id
	 * @param array $old_data
	 *
	 * @return void
	 */
	public function maybe_enable_assignment( $user_id, $old_data ) {
		if ( user_can( $user_id, 'edit_ticket' ) ) {
			$this->enable_assignment( $user_id );
		}
	}

	/**
	 * Add auto-assignment column in users table
	 *
	 * @since 3.2
	 *
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function auto_assignment_user_column( $columns ) {

		$columns['wpas_auto_assignment'] = __( 'Auto-Assign', 'awesome-support' );

		return $columns;
	}

	/**
	 * Add auto-assignment user column content
	 *
	 * @since 3.2
	 *
	 * @param mixed  $value       Column value
	 * @param string $column_name Column name
	 * @param int    $user_id     Current user ID
	 *
	 * @return string
	 */
	public function auto_assignment_user_column_content( $value, $column_name, $user_id ) {

		if ( 'wpas_auto_assignment' !== $column_name ) {
			return $value;
		}

		$agent = new WPAS_Member_Agent( $user_id );

		if ( true !== $agent->is_agent() ) {
			return 'N/A';
		}

		if ( false === $agent->can_be_assigned() ) {
			return '&#10005;';
		}

		return '&#10003;';

	}

}