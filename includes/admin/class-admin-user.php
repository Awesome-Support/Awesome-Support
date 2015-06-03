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
		add_action( 'show_user_profile',        array( $this, 'user_profile_custom_fields' ) ); // Add user preferences
		add_action( 'personal_options_update',  array( $this, 'save_user_custom_fields' ) );    // Save the user preferences
		add_action( 'edit_user_profile_update', array( $this, 'save_user_custom_fields' ) );    // Save the user preferences when modified by admins
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
	 * @return void
	 */
	public function user_profile_custom_fields( $user ) { ?>

		<h3><?php _e( 'Awesome Support Preferences', 'wpas' ); ?></h3>

		<table class="form-table">
			<tbody>
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
	 * @return void
	 */
	public function save_user_custom_fields( $user_id ) {

		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$wpas_after_reply = filter_input( INPUT_POST, 'wpas_after_reply' );

		if ( $wpas_after_reply ) {
			update_user_meta( $user_id, 'wpas_after_reply', $wpas_after_reply );
		}
	}

}