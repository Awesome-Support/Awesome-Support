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
		if ( wpas_is_asadmin() ) {
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
			<?php
			 /**
			  * For the GDPR labels, this data are stored in
			  * wpas_consent_tracking user meta in form of array.
			  * Get the option and if not empty, loop them here
			  */
			  $user_consent = get_user_option( 'wpas_consent_tracking', $profileuser->ID );
			  if( ! empty ( $user_consent ) && is_array( $user_consent ) ) {
				foreach( $user_consent as $consent ) {
					/**
					 * Determine if current loop is TOR
					 * Display TOR as label instead of content
					 * There should be no Opt buttons
					 */
					$item = isset( $consent['item'] ) ? $consent['item'] : '';
					if( isset( $consent['is_tor'] ) && $consent['is_tor'] === true ) {
						$item = __( 'Terms and Conditions', 'awesome-support' );
					}

					/**
					 * Determine status
					 * Raw data is boolean, we convert it into string
					 */
					$status = isset( $consent['status'] ) && $consent['status'] === true ? __( 'Checked', 'awesome-support' ) : '';

					/**
					 * Convert Opt content into date
					 * We stored Opt data as strtotime value
					 */
					$opt_in = isset( $consent['opt_in'] ) && ! empty( $consent['opt_in'] ) ? date( 'm/d/Y', $consent['opt_in'] ) : '';
					$opt_out = isset( $consent['opt_out'] ) && ! empty( $consent['opt_out'] ) ? date( 'm/d/Y', $consent['opt_out'] ) : '';

					/**
					 * Determine 'Action' buttons
					 * If current loop is TOR, do not give Opt options
					 */
					$opt_button = "";
					if( isset( $consent['is_tor'] ) && $consent['is_tor'] == false ) {
						/**
						 * Determine what type of buttons we should render
						 * If opt_in is not empty, display Opt out button
						 * otherwise, just vice versa
						 */
						if( ! empty ( $opt_in ) ) {
							$opt_button = sprintf(
								'<a class="button button-secondary wpas-gdpr-opt-out" data-gdpr="' . $item . '" data-user="' . $profileuser->ID . '">%s</a>',
								__( 'Opt-out', 'awesome-support' )
							);
						}elseif( ! empty ( $opt_out ) ) {
							$opt_button = sprintf(
								'<a class="button button-secondary wpas-gdpr-opt-in" data-gdpr="' . $item . '" data-user="' . $profileuser->ID . '">%s</a>',
								__( 'Opt-in', 'awesome-support' )
							);
						}
					}

					/**
					 * Render data
					 */
					printf( 
						'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
						$item,
						$status,
						$opt_in,
						$opt_out,
						$opt_button
					);
				}
			  }
			?>
		</table>

		<!-- GDPR Consent logging -->
		<h2><?php esc_html_e( 'Log', 'awesome-support' ); ?></h2>
		<?php
			/**
			 * Get consent logs
			 */
			$consent_log = get_user_option( 'wpas_consent_log', $profileuser->ID );
			if( ! empty ( $consent_log ) && is_array( $consent_log ) ) {
				foreach( $consent_log as $log ) {
					echo '<p>' . $log . '</p>';
				}
			}
		?>
	<?php
		}
	}
}
