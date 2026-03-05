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

class MUMEI_AYUDA_User {

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
		add_action( 'mumei_ayuda_user_profile_fields', array( $this, 'profile_field_user_can_be_assigned' ), 10, 1 );
		add_action( 'mumei_ayuda_user_profile_fields', array( $this, 'profile_field_smart_tickets_order' ), 10, 1 );
		add_action( 'mumei_ayuda_user_profile_fields', array( $this, 'profile_field_after_reply' ), 10, 1 );
		add_action( 'mumei_ayuda_user_profile_fields', array( $this, 'profile_field_user_view_all_tickets' ), 10, 1 );
		add_action( 'mumei_ayuda_user_profile_fields', array( $this, 'profile_field_allow_assignment_to' ), 11, 1 );
		add_action( 'mumei_ayuda_all_user_profile_fields', array( $this, 'profile_phone_fields' ), 10, 1 );
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
	 * Add user phone fields to the profile page.
	 *
	 * @param WP_User $user
	 */
	public function profile_phone_fields( $user ) {

		$mobile_phone = esc_attr( get_user_option( 'mumei_ayuda_mobile_phone', $user->ID ) );
		$office_phone = esc_attr( get_user_option( 'mumei_ayuda_office_phone', $user->ID ) );
		$home_phone   = esc_attr( get_user_option( 'mumei_ayuda_home_phone',   $user->ID ) );
		$other_phone  = esc_attr( get_user_option( 'mumei_ayuda_other_phone',  $user->ID ) );
		?>

		<div id="mumei_ayuda_user_profile_segment">
			<h3><?php esc_html_e( 'Ayuda – Help Desk: Additional User Data', 'ayuda-help-desk') ?></h3>


			<table class="form-table">

				<tbody>
					<tr>
						<th><label><?php esc_html_e( 'Mobile Phone', 'ayuda-help-desk' ); ?></label></th>
						<td><input type="text" name="mumei_ayuda_mobile_phone" id="mumei_ayuda_mobile_phone" value="<?php echo esc_attr( $mobile_phone ); ?>" class="regular-text code"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Office Phone', 'ayuda-help-desk' ); ?></label></th>
						<td><input type="text" name="mumei_ayuda_office_phone" id="mumei_ayuda_office_phone" value="<?php echo esc_attr( $office_phone ); ?>" class="regular-text code"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Home Phone', 'ayuda-help-desk' ); ?></label></th>
						<td><input type="text" name="mumei_ayuda_home_phone" id="mumei_ayuda_home_phone" value="<?php echo esc_attr( $home_phone ); ?>" class="regular-text code"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Other Phone', 'ayuda-help-desk' ); ?></label></th>
						<td><input type="text" name="mumei_ayuda_other_phone" id="mumei_ayuda_other_phone" value="<?php echo esc_attr( $other_phone ); ?>" class="regular-text code"></td>
					</tr>
				</tbody>

			</table>
		</div>

		<?php
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

		do_action( 'mumei_ayuda_all_user_profile_fields', $user );

		if ( ! user_can( $user->ID, 'edit_ticket' ) ) {
			return false;
		} ?>

		<div id="mumei_ayuda_user_profile_segment">
			<h3><?php esc_html_e( 'Ayuda – Help Desk: Preferences', 'ayuda-help-desk' ); ?></h3>

			<table class="form-table">
				<tbody>
					<?php do_action( 'mumei_ayuda_user_profile_fields', $user ); ?>
				</tbody>
			</table>
		</div>
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

		/* If this user is not an agent, then don't allow this field to be set/shown */
		if ( ! mumei_ayuda_is_agent( $user->ID ) ) {
			return ;
		}

		?>

		<tr class="wpas-after-reply-wrap">
			<th><label><?php esc_attr_e( 'Smart Tickets Order', 'ayuda-help-desk' ); ?></label></th>
			<td>
				<?php $smart = esc_attr( get_user_option( 'mumei_ayuda_smart_tickets_order', $user->ID ) ); ?>
				<label for="mumei_ayuda_smart_tickets_order"><input type="checkbox" name="mumei_ayuda_smart_tickets_order" id="mumei_ayuda_smart_tickets_order" value="yes" <?php if ( ! empty( $smart ) ) { echo 'checked'; } ?>> <?php esc_html_e( 'Enable', 'ayuda-help-desk' ); ?></label>
				<p class="description"><?php esc_attr_e( 'If Smart Tickets Order is enabled, Ayuda – Help Desk will display tickets that need immediate attention at the top.', 'ayuda-help-desk' ); ?></p>
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

		/* If this user is not an agent, then don't allow this field to be set/shown */
		if ( ! mumei_ayuda_is_agent( $user->ID ) ) {
			return ;
		}

		?>

		<tr class="wpas-after-reply-wrap">
			<th><label for="mumei_ayuda_after_reply"><?php echo esc_html_x( 'After Reply', 'Action after replying to a ticket', 'ayuda-help-desk' ); ?></label></th>
			<td>
				<?php $after_reply = esc_attr( get_user_option( 'mumei_ayuda_after_reply', $user->ID ) ); ?>
				<select name="mumei_ayuda_after_reply" id="mumei_ayuda_after_reply">
					<option value=""><?php esc_html_e( 'Default', 'ayuda-help-desk' ); ?></option>
					<option value="stay" <?php if ( $after_reply === 'stay' ): ?>selected="selected"<?php endif; ?>><?php esc_html_e( 'Stay on screen', 'ayuda-help-desk' ); ?></option>
					<option value="back" <?php if ( $after_reply === 'back' ): ?>selected="selected"<?php endif; ?>><?php esc_html_e( 'Back to list', 'ayuda-help-desk' ); ?></option>
					<option value="ask" <?php if ( $after_reply === 'ask' ): ?>selected="selected"<?php endif; ?>><?php esc_html_e( 'Always ask', 'ayuda-help-desk' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Where do you want to go after replying to a ticket?', 'ayuda-help-desk' ); ?></p>
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

		/* Only admins can set this field for an agent */
		if ( ! mumei_ayuda_is_asadmin() ) {
			return;
		}

		/* If this user is not an agent, then don't allow this field to be set/shown */
		if ( ! mumei_ayuda_is_agent( $user->ID ) ) {
			return ;
		}

		?>

		<tr class="wpas-after-reply-wrap">
			<th><label><?php esc_html_e( 'Can Be Assigned', 'ayuda-help-desk' ); ?></label></th>
			<td>
				<?php $can_assign = esc_attr( get_user_option( 'mumei_ayuda_can_be_assigned', $user->ID ) ); ?>
				<label for="mumei_ayuda_can_be_assigned"><input type="checkbox" name="mumei_ayuda_can_be_assigned" id="mumei_ayuda_can_be_assigned" value="yes" <?php if ( ! empty( $can_assign ) ) { echo 'checked'; } ?>> <?php esc_html_e( 'Yes', 'ayuda-help-desk' ); ?></label>
				<p class="description"><?php esc_html_e( 'Can the system assign new tickets to this user?', 'ayuda-help-desk' ); ?></p>
			</td>
		</tr>

		<?php
	}


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
			<th><label><?php esc_html_e( 'View All Tickets', 'ayuda-help-desk' ); ?></label></th>
			<td>
				<?php $view_all_tickets = esc_attr( get_user_option( 'mumei_ayuda_view_all_tickets', $user->ID ) ); ?>
				<label for="mumei_ayuda_view_all_tickets"><input type="checkbox" name="mumei_ayuda_view_all_tickets" id="mumei_ayuda_view_all_tickets" value="yes" <?php if ( ! empty( $view_all_tickets ) ) { echo 'checked'; } ?>> <?php esc_html_e( 'Yes', 'ayuda-help-desk' ); ?></label>
				<p class="description"><?php esc_html_e( 'If agents role is allowed to view all tickets, turn on the option to do so?', 'ayuda-help-desk' ); ?></p>
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

		/* Only admins can set the dept field for an agent */
		if ( ! mumei_ayuda_is_asadmin() ) {
			return;
		}

		/* If this user is not an agent, then don't allow this field to be set/shown */
		if ( ! mumei_ayuda_is_agent( $user->ID ) ) {
			return ;
		}

		if ( false == mumei_ayuda_get_option( 'departments', false ) ) {
			return;
		}

		$departments = get_terms( array(
			'taxonomy'   => 'department',
			'hide_empty' => false,
		) );

		if ( empty( $departments ) ) {
			return;
		}

		$current = get_user_option( 'mumei_ayuda_department', $user->ID ); 
		
		$current = is_array( $current ) ? $current : array();		
		
		?>

		<tr class="wpas-after-reply-wrap">
			<th><label><?php esc_html_e( 'Department(s)', 'ayuda-help-desk' ); ?></label></th>
			<td>
				<?php
				foreach ( $departments as $department ) {
					$checked = in_array( $department->term_id, $current ) ? 'checked="checked"' : '';
					printf( '<label for="mumei_ayuda_department_%1$s"><input type="checkbox" name="%3$s" id="mumei_ayuda_department_%1$s" value="%2$d" %5$s> %4$s</label><br>', esc_attr( $department->slug ), esc_attr( $department->term_id ), 'mumei_ayuda_department[]', esc_attr( $department->name ), esc_attr( $checked ) );
				}
				?>
				<p class="description"><?php esc_html_e( 'Which department(s) does this agent belong to?', 'ayuda-help-desk' ); ?></p>
			</td>
		</tr>

	<?php }


	/**
	 * User profile field "allow assignment to"
	 *
	 * @since 3.3
	 *
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function profile_field_allow_assignment_to( $user ) {

		/* Only admins can set the dept field for an agent */
		if ( ! mumei_ayuda_is_asadmin() ) {
			return;
		}

		if ( false == mumei_ayuda_get_option( 'departments', false ) ) {
			return;
		}

		$departments = get_terms( array(
			'taxonomy'   => 'department',
			'hide_empty' => false,
		) );

		if ( empty( $departments ) || is_wp_error( $departments ) ) {
			return;
		}
		if( !class_exists( 'Smart_Agent_Assignment' ) ) {
			return;
		}
		$current = get_user_option( 'mumei_ayuda_department_assignment', $user->ID ); 
		$current = is_array( $current ) ? $current : array();
		
		?>
		
		<tr class="wpas-after-reply-wrap">
			<th><label><?php esc_html_e( 'Allow assignment to', 'ayuda-help-desk' ); ?></label></th>
			<td>
				<?php
					$checked_all = in_array( 0, $current ) ? 'checked="checked"' : '';
					printf( '<label for="mumei_ayuda_department_assignment_%1$s"><input type="checkbox" name="%3$s" id="mumei_ayuda_department_assignment_%1$s" value="%2$d" %5$s> %4$s</label><br>', 'all', 0, 'mumei_ayuda_department_assignment[]', 'Users from all departments', wp_kses_post($checked_all) );
				?>
				<?php
				foreach ( $departments as $department ) {
					$checked = in_array( $department->term_id, $current ) ? 'checked="checked"' : '';
					printf( '<label for="mumei_ayuda_department_assignment_%1$s"><input type="checkbox" name="%3$s" id="mumei_ayuda_department_assignment_%1$s" value="%2$d" %5$s> %4$s</label><br>', wp_kses_post($department->slug), wp_kses_post($department->term_id), 'mumei_ayuda_department_assignment[]', wp_kses_post($department->name), wp_kses_post($checked) );
				}
				?>
				<p class="description"><?php esc_html_e( 'To agents from which departments is the user allowed to assign tickets', 'ayuda-help-desk' ); ?></p>
			</td>
		</tr>

	<?php
	}


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

		$mumei_ayuda_after_reply = filter_input( INPUT_POST, 'mumei_ayuda_after_reply' );
		$can_assign       = filter_input( INPUT_POST, 'mumei_ayuda_can_be_assigned' );
		$smart            = filter_input( INPUT_POST, 'mumei_ayuda_smart_tickets_order' );
		$view_all_tickets = filter_input( INPUT_POST, 'mumei_ayuda_view_all_tickets' );
		$department       = isset( $_POST['mumei_ayuda_department'] ) ? array_map( 'intval', $_POST['mumei_ayuda_department'] ) : array();
		$department_assignment = isset( $_POST['mumei_ayuda_department_assignment'] ) ? array_map( 'intval', $_POST['mumei_ayuda_department_assignment'] ) : array();
    
		$mobile_phone = filter_input( INPUT_POST, 'mumei_ayuda_mobile_phone' );
		$office_phone = filter_input( INPUT_POST, 'mumei_ayuda_office_phone' );
		$home_phone   = filter_input( INPUT_POST, 'mumei_ayuda_home_phone' );
		$other_phone  = filter_input( INPUT_POST, 'mumei_ayuda_other_phone' );


		if ( $mumei_ayuda_after_reply ) {
			update_user_option( $user_id, 'mumei_ayuda_after_reply', $mumei_ayuda_after_reply );
		}

		update_user_option( $user_id, 'mumei_ayuda_can_be_assigned', $can_assign );
		update_user_option( $user_id, 'mumei_ayuda_smart_tickets_order', $smart );
		update_user_option( $user_id, 'mumei_ayuda_department', $department );
		update_user_option( $user_id, 'mumei_ayuda_department_assignment', $department_assignment );
		update_user_option( $user_id, 'mumei_ayuda_view_all_tickets', $view_all_tickets );

		update_user_option( $user_id, 'mumei_ayuda_mobile_phone', $mobile_phone );
		update_user_option( $user_id, 'mumei_ayuda_office_phone', $office_phone );
		update_user_option( $user_id, 'mumei_ayuda_home_phone',   $home_phone );
		update_user_option( $user_id, 'mumei_ayuda_other_phone',  $other_phone );

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
			update_user_option( $user_id, 'mumei_ayuda_can_be_assigned', 'yes' );
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

		$columns['mumei_ayuda_auto_assignment'] = __( 'Auto-Assign', 'ayuda-help-desk' );

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

		if ( 'mumei_ayuda_auto_assignment' !== $column_name ) {
			return $value;
		}

		$agent = new MUMEI_AYUDA_Member_Agent( $user_id );

		if ( true !== $agent->is_agent() ) {
			return 'N/A';
		}

		if ( false === $agent->can_be_assigned() ) {
			return '&#10005;';
		}

		return '&#10003;';

	}

}
