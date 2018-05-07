<?php
/**
 * Awesome Support Privacy Option.
 *
 * @package   Awesome_Support
 * @author    DevriX
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 */

// If this file is called directly, abort!
if ( ! defined( 'WPINC' ) ) {
	die;
}
class WPAS_GDPR_User_Profile {

	/**
	 * Instance of this class.
	 *
	 * @since     5.1.1
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Store the potential error messages.
	 */
	protected $error_message;

	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'wpas_user_profile_fields' ), 10, 1 );
		add_action( 'edit_user_profile', array( $this, 'wpas_user_profile_fields' ), 10, 1 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     5.1.1
	 *
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
	 * Display OPT In information in User profile
	 * Only visible if the current role is WPAS User
	 */
	public function wpas_user_profile_fields( $profileuser ) {
		/**
		 * Visible to all WPAS user roles
		 */
		if ( current_user_can( 'create_ticket' ) ) {
	?>
		<h2><?php esc_html_e( 'Awesome Support Consent History', 'awesome-support' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Item', 'awesome-support' ); ?></th>
				<th><?php esc_html_e( 'Status', 'awesome-support' ); ?></th>
				<th><?php esc_html_e( 'Opt-in Date', 'awesome-support' ); ?></th>
				<th><?php esc_html_e( 'Opt-out Date', 'awesome-support' ); ?></th>
				<th><?php esc_html_e( 'Action', 'awesome-support' ); ?></th>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Terms and Conditions', 'awesome-support' ); ?></td>
				<td><?php esc_html_e( '', 'awesome-support' ); ?></td>
				<td><?php esc_html_e( '', 'awesome-support' ); ?></td>
				<td><?php esc_html_e( '', 'awesome-support' ); ?></td>
				<td></td>
			</tr>
			<?php
			 /**
			  * For the GDPR labels, this data are stored in
			  * wpas_gdpr_content option in form of array.
			  * Get the option and if not empty, loop them here
			  */
			?>
		</table>
	<?php
		}
	}
}
