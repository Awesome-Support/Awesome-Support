<?php

/**
 * Awesome Support Privacy Option.
 *
 * @package   Awesome_Support
 * @author    Naveen Giri <1naveengiri>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 */
class WPAS_Privacy_Option {
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
		add_filter( 'wpas_frontend_add_nav_buttons', array( $this, 'frontend_privacy_add_nav_buttons' ) );
		add_filter( 'wp_footer', array( $this, 'print_privacy_popup_temp' ), 101 );
		add_action( 'wp_ajax_wpas_gdpr_open_ticket', array( $this, 'wpas_gdpr_open_ticket' ) );
		add_action( 'wp_ajax_nopriv_wpas_gdpr_open_ticket', array( $this, 'wpas_gdpr_open_ticket' ) );

		/**
		 * Opt in processing
		 */
		add_action( 'wp_ajax_wpas_gdpr_user_opt_in', array( $this, 'wpas_gdpr_user_opt_in' ) );
		add_action( 'wp_ajax_nopriv_wpas_gdpr_user_opt_in', array( $this, 'wpas_gdpr_user_opt_in' ) );

		/**
		 * Opt out processing
		 */
		add_action( 'wp_ajax_wpas_gdpr_user_opt_out', array( $this, 'wpas_gdpr_user_opt_out' ) );
		add_action( 'wp_ajax_nopriv_wpas_gdpr_user_opt_out', array( $this, 'wpas_gdpr_user_opt_out' ) );
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
	 * Print Template file for privacy popup container.
	 *
	 * @return void
	 */
	public static function print_privacy_popup_temp() {
		?>
		<div class="privacy-container-template">
			<div class="entry entry-normal" id="privacy-option-content">
				<div class="wpas-gdpr-loader-background"></div><!-- .wpas-gdpr-loader-background -->
				<a href="#" class="hide-the-content"></a>
				<?php
				$entry_header = wpas_get_option( 'privacy_popup_header', 'Privacy' );
				if ( ! empty( $entry_header ) ) {
					echo '<div class="entry-header">' . $entry_header . '</div>';
				}
				?>
				<div class="entry-content">
					<div class="wpas-gdpr-tab">
						<button class="tablinks wpas-gdpr-tablinks" onclick="wpas_gdpr_open_tab( event, 'add-remove-consent' )" id="wpas-gdpr-tab-default" data-id="add-remove"><?php esc_html_e( 'Add/Remove Existing Consent', 'awesome-support' ); ?></button>
						<button class="tablinks wpas-gdpr-tablinks" onclick="wpas_gdpr_open_tab( event, 'delete-existing-data' )" data-id="delete-existing"><?php esc_html_e( 'Delete my existing data', 'awesome-support' ); ?></button>
						<button class="tablinks wpas-gdpr-tablinks" onclick="wpas_gdpr_open_tab( event, 'export-user-data' )" data-id="export"><?php esc_html_e( 'Export tickets and user data', 'awesome-support' ); ?></button>
					</div>

					<div id="add-remove-consent" class="entry-content-tabs wpas-gdpr-tab-content">
						<?php
							/**
							 * Include tab content for Add/Remove Content data
							 */
							include_once( WPAS_PATH . '/includes/gdpr-integration/tab-content/gdpr-add-remove-consent.php' );
						?>
					</div>
					<div id="delete-existing-data" class="entry-content-tabs wpas-gdpr-tab-content">
						<?php
							/**
							 * Include tab content for Delete my existing data
							 */
							include_once( WPAS_PATH . '/includes/gdpr-integration/tab-content/gdpr-delete-existing-data.php' );
						?>
					</div>
					<div id="export-user-data" class="entry-content-tabs wpas-gdpr-tab-content">
						<?php
							/**
							 * Include tab content for Export tickets and user data
							 */
							include_once( WPAS_PATH . '/includes/gdpr-integration/tab-content/gdpr-export-user-data.php' );
						?>
					</div>					
				</div>
				<?php
				$entry_footer = wpas_get_option( 'privacy_popup_footer', 'Privacy' );
				if ( ! empty( $entry_footer ) ) {
					echo '<div class="entry-footer">' . $entry_footer . '</div>';
				}
				?>
			</div> <!--  .entry entry-regular -->
		</div> <!--  .privacy-container-template -->
		<?php
	}

	/**
	 * Add GDPR privacy options to
	 * * Add/Remove Existing Consent
	 * * Export tickets and user data
	 * * Delete my existing data
	 *
	 * @return void
	 */
	public function frontend_privacy_add_nav_buttons() {
		$button_title = wpas_get_option( 'privacy_button_label', 'Privacy' );
		wpas_make_button(
			$button_title, array(
				'type'  => 'link',
				'link'  => '#',
				'class' => 'wpas-btn wpas-btn-default wpas-link-privacy',
			)
		);
	}

	/**
	 * Ajax based ticket submission
	 * This is only good for 'Official Request: Please Delete My Existing Data ("Right To Be Forgotten")'
	 * ticket from the GDPR popup in 'Delete My Existing Data' tab
	 */
	public function wpas_gdpr_open_ticket() {
		/**
		 * Initialize custom reponse message
		 */
		$response = array(
			'code'    => 403,
			'message' => __( 'Sorry! Something failed', 'awesome-support' ),
		);

		/**
		 * Initiate nonce
		 */
		$nonce = isset( $_POST['data']['nonce'] ) ? $_POST['data']['nonce'] : '';

		/**
		 * Security checking
		 */
		if ( ! empty( $nonce ) && check_ajax_referer( 'wpas-gdpr-nonce', 'security' ) ) {

			/**
			 *  Initiate form data parsing
			 */
			$form_data = array();
			parse_str( $_POST['data']['form-data'], $form_data );

			$subject = isset( $form_data['wpas-gdpr-ded-subject'] ) ? $form_data['wpas-gdpr-ded-subject'] : '';
			$content = isset( $form_data['wpas-gdpr-ded-more-info'] ) && ! empty( $form_data['wpas-gdpr-ded-more-info'] ) ? $form_data['wpas-gdpr-ded-more-info'] : $subject; // Fallback to subject to avoid undefined!

			/**
			 * New ticket submission
			 * *
			 * * NOTE: data sanitization is happening on wpas_open_ticket()
			 * * We can skip doing it here
			 */
			$ticket_id = wpas_open_ticket(
				array(
					'title'   => $subject,
					'message' => $content,
				)
			);

			wpas_log_consent( $form_data['wpas-user'], __( 'Right to be forgotten mail', 'awesome-support' ), __( 'requested', 'awesome-support' ) );
			if ( ! empty( $ticket_id ) ) {
				$response['code']    = 200;
				$response['message'] = __( 'We have received your "Right To Be Forgotten" request!', 'awesome-support' );
			} else {
				$response['message'] = __( 'Something went wrong. Please try again!', 'awesome-support' );
			}
		} else {
			$response['message'] = __( 'Cheating huh?', 'awesome-support' );
		}
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Ajax based processing user opted in button
	 * The button can be found on GDPR popup in front-end
	 */
	public function wpas_gdpr_user_opt_in() {
		/**
		 * Initialize custom reponse message
		 */
		$response = array(
			'code'    => 403,
			'message' => array(),
		);

		/**
		 * Initiate nonce
		 */
		$nonce = isset( $_POST['data']['nonce'] ) ? $_POST['data']['nonce'] : '';

		/**
		 * Security checking
		 */
		if ( ! empty( $nonce ) && check_ajax_referer( 'wpas-gdpr-nonce', 'security' ) ) {

			$item   = isset( $_POST['data']['gdpr-data'] ) ? sanitize_text_field( $_POST['data']['gdpr-data'] ) : '';
			$user   = isset( $_POST['data']['gdpr-user'] ) ? sanitize_text_field( $_POST['data']['gdpr-user'] ) : '';
			$status = __( 'Checked', 'awesome-support' );
			$opt_in = strtotime( 'NOW' );

			wpas_track_consent(
				array(
					'item'    => $item,
					'status'  => $status,
					'opt_in'  => $opt_in,
					'opt_out' => '',
					'is_tor'  => false,
				), $user, 'in'
			);

			wpas_log_consent( $user, $item, __( 'opted-in', 'awesome-support' ) );
			$response['code']    = 200;
			$response['message']['success'] = __( 'You have successfully opted-in', 'awesome-support' );
			$response['message']['date'] = date( 'm/d/Y', $opt_in );
			$response['message']['button'] = sprintf(
				'<a href="#" class="button button-secondary wpas-button wpas-gdpr-opt-out" data-gdpr="' . $item . '" data-user="' . get_current_user_id() . '">%s</a>',
				__( 'Opt-out', 'awesome-support' )
			);
		} else {
			$response['message']['error'] = __( 'Cheating huh?', 'awesome-support' );
		}
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Ajax based processing user opted out button
	 * The button can be found on GDPR popup in front-end
	 */
	public function wpas_gdpr_user_opt_out() {
		/**
		 * Initialize custom reponse message
		 */
		$response = array(
			'code'    => 403,
			'message' => array(),
		);

		/**
		 * Initiate nonce
		 */
		$nonce = isset( $_POST['data']['nonce'] ) ? $_POST['data']['nonce'] : '';

		/**
		 * Security checking
		 */
		if ( ! empty( $nonce ) && check_ajax_referer( 'wpas-gdpr-nonce', 'security' ) ) {

			$item    = isset( $_POST['data']['gdpr-data'] ) ? sanitize_text_field( $_POST['data']['gdpr-data'] ) : '';
			$user    = isset( $_POST['data']['gdpr-user'] ) ? sanitize_text_field( $_POST['data']['gdpr-user'] ) : '';
			$status  = __( 'Checked', 'awesome-support' );
			$opt_out = strtotime( 'NOW' );
			wpas_track_consent(
				array(
					'item'    => $item,
					'status'  => $status,
					'opt_in'  => '',
					'opt_out' => $opt_out,
					'is_tor'  => false,
				), $user, 'out'
			);
			wpas_log_consent( $user, $item, __( 'opted-out', 'awesome-support' ) );
			$response['code']    = 200;
			$response['message']['success'] = __( 'You have successfully opted-out', 'awesome-support' );
			$response['message']['date'] = date( 'm/d/Y', $opt_out );
			$response['message']['button'] = sprintf(
				'<a href="#" class="button button-secondary wpas-button wpas-gdpr-opt-in" data-gdpr="' . $item . '" data-user="' . get_current_user_id() . '">%s</a>',
				__( 'Opt-in', 'awesome-support' )
			);
		} else {
			$response['message']['error'] = __( 'Cheating huh?', 'awesome-support' );
		}
		wp_send_json( $response );
		wp_die();
	}


}
