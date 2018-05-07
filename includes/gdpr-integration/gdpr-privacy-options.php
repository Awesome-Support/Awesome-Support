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
				<a href="#" class="hide-the-content">close</a>
				<?php
				$entry_header = wpas_get_option( 'privacy_popup_header', 'Privacy' );
				if ( ! empty( $entry_header ) ) {
					echo '<div class="entry-header">' . $entry_header . '</div>';
				}
				?>
				<div class="entry-content">
					<div class="wpas-gdpr-tab">
						<button class="tablinks" onclick="openGDPRTab(event, 'add-remove-consent')" id="wpas-gdpr-tab-default"><?php esc_html_e( 'Add/Remove Existing Consent', 'awesome-support' ); ?></button>
						<button class="tablinks" onclick="openGDPRTab(event, 'delete-existing-data')"><?php esc_html_e( 'Delete my existing data', 'awesome-support' ); ?></button>
						<button class="tablinks" onclick="openGDPRTab(event, 'export-user-data')"><?php esc_html_e( 'Export tickets and user data', 'awesome-support' ); ?></button>
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


}
