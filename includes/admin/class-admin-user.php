<?php
/**
 * User.
 *
 * @package   Admin/User
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
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
//		add_action( 'profile_update',             array( $this, 'maybe_enable_assignment' ), 10, 2 );
		add_filter( 'manage_users_columns',       array( $this, 'auto_assignment_user_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'auto_assignment_user_column_content' ), 10, 3 );
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
	 * @return bool|void
	 */
	public function user_profile_custom_fields( $user ) {

		if ( ! user_can( $user->ID, 'edit_ticket' ) ) {
			return false;
		} ?>

		<h3><?php _e( 'Awesome Support Preferences', 'wpas' ); ?></h3>

		<table class="form-table">
			<tbody>

				<?php if ( current_user_can( 'administrator' ) ): ?>

					<tr class="wpas-after-reply-wrap">
						<th><label><?php _e( 'Can Be Assigned', 'wpas' ); ?></label></th>
						<td>
							<?php $can_assign = esc_attr( get_the_author_meta( 'wpas_can_be_assigned', $user->ID ) ); ?>
							<label for="wpas_can_be_assigned"><input type="checkbox" name="wpas_can_be_assigned" id="wpas_can_be_assigned" value="yes" <?php if ( ! empty( $can_assign ) ) { echo 'checked'; } ?>> <?php _e( 'Yes', 'wpas' ); ?></label>
							<p class="description"><?php _e( 'Can the system assign new tickets to this user?', 'wpas' ); ?></p>
						</td>
					</tr>

				<?php endif; ?>

				<tr class="wpas-after-reply-wrap">
					<th><label for="wpas_after_reply"><?php echo _x( 'After Reply', 'Action after replying to a ticket', 'wpas' ); ?></label></th>
					<td>
						<?php $after_reply = esc_attr( get_the_author_meta( 'wpas_after_reply', $user->ID ) ); ?>
						<select name="wpas_after_reply" id="wpas_after_reply">
							<option value=""><?php _e( 'Default', 'wpas' ); ?></option>
							<option value="stay" <?php if ( $after_reply === 'stay' ): ?>selected="selected"<?php endif; ?>><?php _e( 'Stay on screen', 'wpas' ); ?></option>
							<option value="back" <?php if ( $after_reply === 'back' ): ?>selected="selected"<?php endif; ?>><?php _e( 'Back to list', 'wpas' ); ?></option>
							<option value="ask" <?php if ( $after_reply === 'ask' ): ?>selected="selected"<?php endif; ?>><?php _e( 'Always ask', 'wpas' ); ?></option>
						</select>
						<p class="description"><?php _e( 'Where do you want to go after replying to a ticket?', 'wpas' ); ?></p>
					</td>
				</tr>

			</tbody>
		</table>
	<?php }

	/**
	 * Save the user preferences.
	 *
	 * @since  3.0.0
	 * @param  integer $user_id ID of the user to modify
	 * @return bool|void
	 */
	public function save_user_custom_fields( $user_id ) {

		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$wpas_after_reply = filter_input( INPUT_POST, 'wpas_after_reply' );
		$can_assign = filter_input( INPUT_POST, 'wpas_can_be_assigned' );

		if ( $wpas_after_reply ) {
			update_user_meta( $user_id, 'wpas_after_reply', $wpas_after_reply );
		}

		update_user_meta( $user_id, 'wpas_can_be_assigned', $can_assign );

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
			update_user_meta( $user_id, 'wpas_can_be_assigned', 'yes' );
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

		$columns['wpas_auto_assignment'] = __( 'Auto-Assign', 'wpas' );

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

		$agent = new WPAS_Agent( $user_id );

		if ( true !== $agent->is_agent() || false === $agent->can_be_assigned() ) {
			return '&#10005;';
		}

		return '&#10003;';

	}

}