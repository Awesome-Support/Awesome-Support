<?php
/**
 * @package   Ayuda – Help Desk/Scripts
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wp_enqueue_scripts', 'mumei_ayuda_register_assets_front_end', 5 );
/**
 * Register all front-end assets
 *
 * @since 3.3
 * @return void
 */
function mumei_ayuda_register_assets_front_end() {

	// Optionally load bootstrap 4 or bootstrap 3 files from cdn.
	// These assets are also duplicated and loaded on the back-end
	$load_bs4 = mumei_ayuda_get_option('load_bs4_files_fe', '0') ;
	if ( '1' === $load_bs4 ) {
		mumei_ayuda_register_bs4_theme_styles() ;
		wp_register_script( 'wpas-bootstrap-4-popper', MUMEI_AYUDA_URL . 'assets/public/vendor/popper/js/popper.min.js', array( 'jquery' ), '1.11.0', true );
		wp_register_script( 'wpas-bootstrap-4-js', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/4.0.0/js/bootstrap.min.js', array( 'jquery' ), '4.0.0', true );
	}
	if ( '2' === $load_bs4 ) {
		// Boostrap 3 styles and scripts
		wp_register_style( 'wpas-bootstrap-3', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/3.3.7/css/bootstrap.min.css', array(), '3.3.7' );
		wp_register_style( 'wpas-bootstrap-3-ss', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/3.3.7/css/bootstrap-theme.min.css', array(), '3.3.7' );
		wp_register_script( 'wpas-bootstrap-3-js', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/3.3.7/js/bootstrap.min.js', array( 'jquery' ), '3.3.7', true );
	}

	// Our styles
	wp_register_style( 'wpas-plugin-styles', MUMEI_AYUDA_URL . 'assets/public/css/public.css', array(), MUMEI_AYUDA_VERSION );

	// Select2 styles are loaded based on a setting.  This asset is also duplicated on the back-end.
	// Note that we are hardcoding a version number into the wp_register_script call so that we can force caches to update when switching between options.
	$which_select2_css = mumei_ayuda_get_option('select2_css_file', 'min') ;
	$which_select2_version = mumei_ayuda_get_option('select2_version', 'select2-4-0-5') ;
	switch ( $which_select2_css ) {
		case 'min':
			wp_register_style( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/css/vendor/select2/$which_select2_version/select2.min.css", null, MUMEI_AYUDA_VERSION.'.1', 'all' );
			break ;

		case 'full':
			wp_register_style( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/css/vendor/select2/$which_select2_version/select2.css", null, MUMEI_AYUDA_VERSION.'.2', 'all' );
			break ;
	}

	// Scripts
	wp_register_script( 'wpas-plugin-script', MUMEI_AYUDA_URL . 'assets/public/js/public-dist.js', array( 'jquery' ), MUMEI_AYUDA_VERSION, true );

	// Select2 scripts are loaded based on a setting.  This asset is also duplicated on the back-end.
	// Note that we are hardcoding a version number into the wp_register_script call so that we can force caches to update when switching between options.
	$which_select2_js = mumei_ayuda_get_option('select2_js_file', 'full-min') ;
	$which_select2_version = mumei_ayuda_get_option('select2_version', 'select2-4-0-5') ;
	switch ( $which_select2_js ) {
		case 'full':
			wp_register_script( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/js/vendor/select2/$which_select2_version/select2.full.js", array( 'jquery' ), MUMEI_AYUDA_VERSION.'.1', 'all' );
			break ;

		case 'full-min':
			wp_register_script( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/js/vendor/select2/$which_select2_version/select2.full.min.js", array( 'jquery' ), MUMEI_AYUDA_VERSION.'.2', 'all' );
			break ;

		case 'partial':
			wp_register_script( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/js/vendor/select2/$which_select2_version/select2.js", array( 'jquery' ), MUMEI_AYUDA_VERSION.'.3', 'all' );
			break ;

		case 'partial-min':
			wp_register_script( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/js/vendor/select2/$which_select2_version/select2.min.js", array( 'jquery' ), MUMEI_AYUDA_VERSION.'.4', 'all' );
			break ;
	}

	// Include magnific popup
	mumei_ayuda_add_magnific();



	// JS Objects
	wp_localize_script( 'wpas-plugin-script', 'wpas', mumei_ayuda_get_javascript_object() );

}

add_action( 'admin_enqueue_scripts', 'mumei_ayuda_register_assets_back_end', 5 );
/**
 * Register all back-end assets
 *
 * @since 3.3
 * @return void
 *
 * @TODO: It is possible that most of the function below should be wrapped in a conditional similar to this:
 *		if ( true == mumei_ayuda_is_plugin_page() )
 */
function mumei_ayuda_register_assets_back_end() {

	// Optionally load bootstrap 4 or bootstrap 3 files from cdn.
	// These assets are also duplicated and loaded on the front-end
	$load_bs4  = mumei_ayuda_get_option('load_bs4_files_be', '0') ;
	if ( '1' === $load_bs4 ) {
		mumei_ayuda_register_bs4_theme_styles();
		wp_register_script( 'wpas-bootstrap-4-popper', MUMEI_AYUDA_URL . 'assets/public/vendor/popper/js/popper.min.js', array( 'jquery' ), '1.11.0', true );
		wp_register_script( 'wpas-bootstrap-4-js', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/4.0.0/js/bootstrap.min.js', array( 'jquery' ), '4.0.0', true );
	}
	if ( '2' === $load_bs4 ) {
		// Boostrap 3 styles and scripts
		wp_register_style( 'wpas-bootstrap-3', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/3.3.7/css/bootstrap.min.css', array(), '3.3.7' );
		wp_register_style( 'wpas-bootstrap-3-ss', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/3.3.7/css/bootstrap-theme.min.css', array(), '3.3.7' );
		wp_register_script( 'wpas-bootstrap-3-js', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/3.3.7/js/bootstrap.min.js', array( 'jquery' ), '3.3.7', true );
	}

	// Other 3rd party styles
	wp_register_style( 'wpas-datepicker', MUMEI_AYUDA_URL . 'assets/public/css/component_datepicker.css', null, MUMEI_AYUDA_VERSION, 'all' ); // NOTE: This asset is duplicated in the back-end
	wp_register_style( 'wpas-simple-hint', MUMEI_AYUDA_URL . 'assets/public/vendor/simple-hint/css/simple-hint.min.css', null, '2.1.1' );
	if ( intval( $load_bs4 ) <= 0 ) {
		wp_register_style( 'wpas-flexboxgrid', MUMEI_AYUDA_URL . 'assets/admin/css/vendor/flexboxgrid.min.css', null, '6.2.0', 'all' );
	}

	// Our styles
	wp_register_style( 'wpas-admin-styles', MUMEI_AYUDA_URL . 'assets/admin/css/admin.css', array( 'wpas-select2' ), MUMEI_AYUDA_VERSION );
	wp_register_style( 'wpas-admin-reply-history', MUMEI_AYUDA_URL . 'assets/admin/css/admin-reply-history.css', array(), MUMEI_AYUDA_VERSION );
	wp_register_style( 'wpas-admin-print-ticket', MUMEI_AYUDA_URL . 'assets/admin/css/admin-print-ticket.css', null, MUMEI_AYUDA_VERSION );
	wp_register_style( 'wpas-admin-icons', MUMEI_AYUDA_URL . 'assets/admin/css/admin-icons.css', array(), MUMEI_AYUDA_VERSION );

	// Select2 styles are loaded based on a setting.  This asset is also duplicated on the front-end.
	// Note that we are hardcoding a version number into the wp_register_script call so that we can force caches to update when switching between options.
	$which_select2_css = mumei_ayuda_get_option('select2_css_file', 'min') ;
	$which_select2_version = mumei_ayuda_get_option('select2_version', 'select2-4-0-5') ;
	switch ( $which_select2_css ) {
		case 'min':
			wp_register_style( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/css/vendor/select2/$which_select2_version/select2.min.css", null, MUMEI_AYUDA_VERSION.'.1', 'all' );
			break ;

		case 'full':
			wp_register_style( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/css/vendor/select2/$which_select2_version/select2.css", null, MUMEI_AYUDA_VERSION.'.2', 'all' );
			break ;
	}


	// Our Scripts
	wp_register_script( 'wpas-admin-about-linkify', MUMEI_AYUDA_URL . 'assets/admin/js/vendor/linkify.min.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-about-linkify-jquery', MUMEI_AYUDA_URL . 'assets/admin/js/vendor/linkify-jquery.min.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-about-moment', MUMEI_AYUDA_URL . 'assets/admin/js/vendor/moment.min.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-about-script', MUMEI_AYUDA_URL . 'assets/admin/js/admin-about.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-optin-script', MUMEI_AYUDA_URL . 'assets/admin/js/admin-optin.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_localize_script( 'wpas-admin-optin-script', 'MUMEI_AYUDA_Optin', array(
		'nonce' => wp_create_nonce('mumei_ayuda_admin_optin'), // Créez la nonce et transmettez-la au script
	));
	wp_register_script( 'wpas-admin-script', MUMEI_AYUDA_URL . 'assets/admin/js/admin.js', array( 'jquery', 'wpas-select2' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-toolbars-script', MUMEI_AYUDA_URL . 'assets/admin/js/admin-toolbars.js', array( 'jquery', 'wpas-select2' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-tabletojson', MUMEI_AYUDA_URL . 'assets/admin/js/vendor/jquery.tabletojson.min.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-reply', MUMEI_AYUDA_URL . 'assets/admin/js/admin-reply.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-reply-history', MUMEI_AYUDA_URL . 'assets/admin/js/admin-reply-history.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-autolinker', MUMEI_AYUDA_URL . 'assets/public/vendor/Autolinker/Autolinker.min.js', null, '0.19.0', true );
	wp_register_script( 'wpas-users', MUMEI_AYUDA_URL . 'assets/admin/js/admin-users.js', null, MUMEI_AYUDA_VERSION, true );
	wp_register_script( 'wpas-admin-helpers_functions', MUMEI_AYUDA_URL . 'assets/public/js/helpers_functions.js', null, MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-upload', MUMEI_AYUDA_URL . 'assets/public/js/component_upload.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-print-ticket', MUMEI_AYUDA_URL . 'assets/admin/js/admin-print-ticket.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_register_script( 'wpas-admin-edit-ticket-content-script', MUMEI_AYUDA_URL . 'assets/admin/js/admin-edit-ticket-content.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	// @TODO: Why is the version set to TIME() below instead of MUMEI_AYUDA_VERSION?
	wp_register_script(
		'wpas-datepicker',
		MUMEI_AYUDA_URL . 'assets/public/js/component_datepicker.js',
		array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
		time(),
		true
	);

	// Select2 scripts are loaded based on a setting.  This asset is also duplicated on the front-end.
	// Note that we are hardcoding a version number into the wp_register_script call so that we can force caches to update when switching between options.
	$which_select2_js = mumei_ayuda_get_option('select2_js_file', 'partial-min') ;
	$which_select2_version = mumei_ayuda_get_option('select2_version', 'select2-4-0-5') ;
	switch ( $which_select2_js ) {
		case 'full':
			wp_register_script( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/js/vendor/select2/$which_select2_version/select2.full.js", array( 'jquery' ), MUMEI_AYUDA_VERSION.'.1', 'all' );
			break ;

		case 'full-min':
			wp_register_script( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/js/vendor/select2/$which_select2_version/select2.full.min.js", array( 'jquery' ), MUMEI_AYUDA_VERSION.'.2', 'all' );
			break ;

		case 'partial':
			wp_register_script( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/js/vendor/select2/$which_select2_version/select2.js", array( 'jquery' ), MUMEI_AYUDA_VERSION.'.3', 'all' );
			break ;

		case 'partial-min':
			wp_register_script( 'wpas-select2', MUMEI_AYUDA_URL . "assets/admin/js/vendor/select2/$which_select2_version/select2.min.js", array( 'jquery' ), MUMEI_AYUDA_VERSION.'.4', 'all' );
			break ;
	}

	// JS Objects
	wp_localize_script( 'wpas-admin-script', 'wpas', mumei_ayuda_get_javascript_object() );
	wp_localize_script( 'wpas-admin-reply', 'wpasL10n', array(
		'alertDelete'    => __( 'Are you sure you want to delete this reply?', 'ayuda-help-desk' ),
		'alertNoTinyMCE' => __( 'No instance of TinyMCE found. Please use wp_editor on this page at least once: http://codex.wordpress.org/Function_Reference/wp_editor', 'ayuda-help-desk' ),
		'alertNoContent' => __( "You can't submit an empty reply", 'ayuda-help-desk' ),
		'reply_nonce' => wp_create_nonce( 'mumei_ayuda_edit_reply' ),
		'editor_content_nonce' => wp_create_nonce( 'wpas-editor-content-nonce' )
	) );
	wp_localize_script( 'wpas-admin-reply-history', 'MUMEI_AYUDA_Reply_History', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'date_label' => __( 'Edited on', 'ayuda-help-desk' ),
		'mumei_ayuda_history_reply_nonce' => wp_create_nonce( 'mumei_ayuda_history_reply_nonce' )
	));

	// Print ticket vars
	wp_localize_script( 'wpas-admin-print-ticket', 'MUMEI_AYUDA_Print', array(
		'admin_url'             => admin_url(),
		'plugin_url'            => MUMEI_AYUDA_URL,
		'nonce'                 => wp_create_nonce( 'mumei_ayuda_print_ticket' ),
		'print'                 => __( 'Print', 'ayuda-help-desk' ),
		'cancel'                => __( 'Cancel', 'ayuda-help-desk' ),
		'print_ticket'          => __( 'Print ticket', 'ayuda-help-desk' ),
		'print_tickets'         => __( 'Print tickets', 'ayuda-help-desk' ),
		'include_replies'       => __( 'Include replies', 'ayuda-help-desk' ),
		'include_history'       => __( 'Include history', 'ayuda-help-desk' ),
		'include_private_notes' => __( 'Include private notes', 'ayuda-help-desk' ),
	) );


	// Custom admin notice style and script
	wp_enqueue_style( 'wpas-admin-wizard-notice', MUMEI_AYUDA_URL . 'assets/admin/css/wizard-notice.css', array(), MUMEI_AYUDA_VERSION );
	wp_enqueue_style( 'wpas-admin-gdpr', MUMEI_AYUDA_URL . 'assets/admin/css/admin-gdpr.css', array(), MUMEI_AYUDA_VERSION );
	wp_enqueue_script( 'wpas-admin-wizard-script', MUMEI_AYUDA_URL . 'assets/admin/js/admin-wizard.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_localize_script( 'wpas-admin-wizard-script', 'MUMEI_AYUDA_Wizard', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'about_page' => admin_url( 'edit.php?post_type=ticket&page=wpas-about' ),
		'nonce' => wp_create_nonce('mumei_ayuda_admin_wizard'), // Create nonce and transmit it to the script
	));

	// Include magnific popup
	if ( true == mumei_ayuda_is_plugin_page() ) {
		mumei_ayuda_add_magnific();
	}

	// Edit ticket content!
	if ( true == mumei_ayuda_is_plugin_page() ) {
		wp_enqueue_editor();
		wp_enqueue_media();
	}
	
}

add_action( 'wp_enqueue_scripts', 'mumei_ayuda_assets_front_end', 10 );
/**
 * Register and enqueue public-facing style sheet.
 *
 * @since    1.0.2
 */
function mumei_ayuda_assets_front_end() {

	// Make sure we only enqueue on our plugin's pages
	if ( mumei_ayuda_is_plugin_page() ) {

		// Optionally load bootstrap 4 or bootstrap 3 files from cdn.
		$load_bs4 = mumei_ayuda_get_option('load_bs4_files_fe', '0') ;
		if ( '1' === $load_bs4 ) {
			// Boostrap 4 styles and scripts
			wp_enqueue_style( 'wpas-bootstrap-4' );
			wp_enqueue_script( 'wpas-bootstrap-4-popper' );
			wp_enqueue_script( 'wpas-bootstrap-4-js' );
		}
		if ( '2' === $load_bs4 ) {
			// Boostrap 3 styles and scripts
			wp_enqueue_style( 'wpas-bootstrap-3' );
			wp_enqueue_style( 'wpas-bootstrap-3-ss' );
			wp_enqueue_script( 'wpas-bootstrap-3-js' );
		}

		// @todo - where are the SELECT2 scripts being enqueued?
		// Feels like they shoudl be enqueued here - maybe controlled via a setting in TICKETS->SETTINGS->ADVANCED

		// Our Custom Styles
		wp_enqueue_style( 'wpas-plugin-styles' );

		$stylesheet = mumei_ayuda_get_theme_stylesheet();

		if ( file_exists( $stylesheet ) && true === boolval( mumei_ayuda_get_option( 'theme_stylesheet' ) ) ) {
			wp_register_style( 'wpas-theme-styles', mumei_ayuda_get_theme_stylesheet_uri(), array(), MUMEI_AYUDA_VERSION );
			wp_enqueue_style( 'wpas-theme-styles' );
		}

		// GDPR Privacy options script and style.
		wp_enqueue_editor();
		wp_register_script( 'wpas-gdpr-script', MUMEI_AYUDA_URL . 'assets/public/js/component-privacy-popup.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
		wp_localize_script( 'wpas-gdpr-script', 'MUMEI_AYUDA_GDPR', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wpas-gdpr-nonce' )
		) );
		wp_enqueue_script( 'wpas-gdpr-script' );

		// Our Custom Scripts
		wp_enqueue_script( 'wpas-plugin-script' );

	}

}

add_action( 'admin_enqueue_scripts', 'mumei_ayuda_enqueue_assets_back_end', 10 );
/**
 * Register and enqueue admin-specific style sheet.
 *
 * @since     1.0.0
 * @return    null    Return early if no settings page is registered.
 */
function mumei_ayuda_enqueue_assets_back_end() {

	// Make sure we only enqueue on our plugin's pages
	if ( mumei_ayuda_is_plugin_page() ) {

		// Optionally load bootstrap 4 or bootstrap 3 files from cdn.
		$load_bs4 = mumei_ayuda_get_option('load_bs4_files_be', '0') ;
		if ( '1' === $load_bs4 ) {
			// Boostrap 4 styles and scripts
			wp_enqueue_style( 'wpas-bootstrap-4' );
			wp_enqueue_script( 'wpas-bootstrap-4-popper' );
			wp_enqueue_script( 'wpas-bootstrap-4-js' );
		}
		if ( '2' === $load_bs4 ) {
			// Boostrap 3 styles and scripts
			wp_enqueue_style( 'wpas-bootstrap-3' );
			wp_enqueue_style( 'wpas-bootstrap-3-ss' );
			wp_enqueue_script( 'wpas-bootstrap-3-js' );
		}

		// Our Styles
		wp_enqueue_style( 'wpas-select2' );
		wp_enqueue_style( 'wpas-datepicker' );
		wp_enqueue_style( 'wpas-flexboxgrid' );
		wp_enqueue_style( 'wpas-admin-styles' );
		wp_enqueue_style( 'wpas-admin-icons' );

		if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			wp_enqueue_style( 'wpas-simple-hint' );
		}

		// Our Scripts
		if ( 'ticket' == get_post_type() ) {
			wp_dequeue_script( 'autosave' );
		}
		
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';		
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		
		if ( 'wpas-about' === $page ) {
			wp_enqueue_script( 'wpas-admin-about-linkify' );
			wp_enqueue_script( 'wpas-admin-about-linkify-jquery' );
			wp_enqueue_script( 'wpas-admin-about-moment' );
			wp_enqueue_script( 'wpas-admin-about-script' );
		}

		if ( 'wpas-optin' === $page ) {
			wp_enqueue_script( 'wpas-admin-optin-script' );
		}

		wp_enqueue_script( 'wpas-select2' );
		wp_enqueue_script( 'wpas-datepicker' );

		wp_enqueue_script( 'wpas-admin-script' );
		wp_enqueue_script( 'wpas-admin-toolbars-script' ) ;
		wp_enqueue_script( 'wpas-admin-tabletojson' );
		wp_enqueue_script( 'wpas-users' );
		wp_localize_script( 'wpas-users', 'MUMEI_AYUDA_get_users', array(			
			'get_users_nonce' => wp_create_nonce( 'wpas-get-users' )
		) );
		wp_enqueue_script( 'wpas-admin-helpers_functions' );
		wp_enqueue_script( 'wpas-admin-upload' );

		if ( 'edit' === $action && 'ticket' == get_post_type() ) {
			wp_enqueue_style( 'wpas-admin-reply-history' );
			wp_enqueue_script( 'wpas-admin-reply' );
			wp_enqueue_script( 'wpas-admin-reply-history' );
			wp_enqueue_script( 'wpas-autolinker' );
		}

		wp_enqueue_script( 'wpas-admin-edit-ticket-content-script');
		wp_localize_script( 'wpas-admin-edit-ticket-content-script', 'MUMEI_AYUDA_Edit_Ticket_Content', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'editor_content_nonce' => wp_create_nonce( 'wpas-editor-content-nonce' )
		));

	}

	wp_enqueue_style( 'wpas-admin-print-ticket' );
	wp_enqueue_script( 'wpas-admin-print-ticket' );

	wp_register_script( 'wpas-gdpr-admin-script', MUMEI_AYUDA_URL . 'assets/admin/js/admin-gdpr.js', array( 'jquery' ), MUMEI_AYUDA_VERSION );
	wp_localize_script( 'wpas-gdpr-admin-script', 'MUMEI_AYUDA_GDPR', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'wpas-gdpr-nonce' )
	) );
	wp_enqueue_script( 'wpas-gdpr-admin-script' );
}

/**
 * JavaScript object.
 *
 * The plugin uses a couple of JS variables that we pass
 * to the main script through a "wpas" object.
 *
 * @since  3.0.2
 * @return array The JavaScript object
 */
function mumei_ayuda_get_javascript_object() {

	global $post;

	if ( ! isset( $post ) || ! is_object( $post ) || ! is_a( $post, 'WP_Post' ) ) {
		return array();
	}

	$upload_max_files = (int) mumei_ayuda_get_option( 'attachments_max', 2 );
	$upload_max_size  = (int) mumei_ayuda_get_option( 'filesize_max' );

	// Editors translations
	if ( in_array( $post->ID, mumei_ayuda_get_submission_pages() ) ) {
		$empty_editor = _x( "You can't submit an empty ticket", 'JavaScript validation error message', 'ayuda-help-desk' );
	} else {
		$empty_editor = _x( "You can't submit an empty reply", 'JavaScript validation error message', 'ayuda-help-desk' );
	}
	
	// translators: %d is the maximum number of files.
	$fileUploadMaxError =  sprintf( __('You can only upload a maximum of %d files', 'ayuda-help-desk' ), $upload_max_files );

	// translators: %d is the maximum size of files.
	$x_content = __( 'The maximum file size allowed for one file is %d MB', 'ayuda-help-desk' );

	$object = array(
		'ajaxurl'                => admin_url( 'admin-ajax.php' ),
		'emailCheck'             => true === boolval( mumei_ayuda_get_option( 'enable_mail_check', false ) ) ? 'true' : 'false',
		'useAutolinker'          => true === boolval( mumei_ayuda_get_option( 'use_autolinker', true ) ) ? 'true' : 'false',
		'fileUploadMax'          => $upload_max_files,
		'fileUploadSize'         => $upload_max_size * 1048576, // We base our calculation on binary prefixes
		'fileUploadMaxError'     => $fileUploadMaxError,
		'fileUploadMaxSizeError' => array(
			__( 'The following file(s) are too big to be uploaded:', 'ayuda-help-desk' ),
			sprintf( $x_content, $upload_max_size )
		),
		'translations' => array(
			'emptyEditor' => $empty_editor,
			'onSubmit'    => _x( 'Submitting...', 'ticket submission button text while submitting', 'ayuda-help-desk' ),
		),
		'front_replies_nonce' => wp_create_nonce( 'mumei_ayuda_loads_replies' ),
		'front_delete_att_nonce' => wp_create_nonce( 'wpas-delete-attachs')
	);

	if ( 'ticket' === $post->post_type ) {
		$object['ticket_id'] = $post->ID;
	}

	return $object;

}

/**
 * Register bootstrap 4 theme theme stylesheets
 *
 * @since  4.1.0
 *
 * @return void
 */
function mumei_ayuda_register_bs4_theme_styles() {
	$bs4_theme = mumei_ayuda_get_option('bs4_theme', 'default') ;
	switch ( $bs4_theme ) {

		case 'default':
			wp_register_style( 'wpas-bootstrap-4', MUMEI_AYUDA_URL . 'assets/public/vendor/bootstrap/4.0.0/css/bootstrap.min.css', array(), '4.0.0' );
			break ;

		case 'awesome':
			wp_register_style( 'wpas-bootstrap-4', MUMEI_AYUDA_URL . 'assets/admin/css/vendor/bootstrap4themes/ayuda-help-desk/' . $bs4_theme . '/bootstrap.min.css', array(), MUMEI_AYUDA_VERSION );
			break ;

		case 'custom':
			wp_register_style( 'wpas-bootstrap-4', MUMEI_AYUDA_URL . 'assets/admin/css/vendor/bootstrap4themes/custom/style.css', array(), MUMEI_AYUDA_VERSION );
			break ;

		default:
			wp_register_style( 'wpas-bootstrap-4', MUMEI_AYUDA_URL . 'assets/admin/css/vendor/bootstrap4themes/bootswatch/' . $bs4_theme . '/bootstrap.min.css', array(), MUMEI_AYUDA_VERSION );
			break ;
	}
}