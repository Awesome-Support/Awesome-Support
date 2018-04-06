<?php
/**
 * @package   Awesome Support/Scripts
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wp_enqueue_scripts', 'wpas_register_assets_front_end', 5 );
/**
 * Register all front-end assets
 *
 * @since 3.3
 * @return void
 */
function wpas_register_assets_front_end() {
	
	// Optionally load bootstrap 4 or bootstrap 3 files from cdn.
	// These assets are also duplicated and loaded on the back-end
	$load_bs4 = wpas_get_option('load_bs4_files_fe', '0') ;
	if ( '1' === $load_bs4 ) {
		wpas_register_bs4_theme_styles() ;
		wp_register_script( 'wpas-bootstrap-4-popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js', array( 'jquery' ), '1.11.0', true );
		wp_register_script( 'wpas-bootstrap-4-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js', array( 'jquery' ), '4.0.0', true );
	}
	if ( '2' === $load_bs4 ) {
		// Boostrap 3 styles and scripts
		wp_register_style( 'wpas-bootstrap-3', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css', array(), '3.3.7' );
		wp_register_style( 'wpas-bootstrap-3-ss', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css', array(), '3.3.7' );
		wp_register_script( 'wpas-bootstrap-3-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array( 'jquery' ), '3.3.7', true );
	}
	
	// Our styles
	wp_register_style( 'wpas-plugin-styles', WPAS_URL . 'assets/public/css/public.css', array(), WPAS_VERSION );
	
	// Select2 styles are loaded based on a setting.  This asset is also duplicated on the back-end.
	// Note that we are hardcoding a version number into the wp_register_script call so that we can force caches to update when switching between options.	
	$which_select2_css = wpas_get_option('select2_css_file', 'min') ;
	switch ( $which_select2_css ) {
		case 'min': 
			wp_register_style( 'wpas-select2', WPAS_URL . 'assets/admin/css/vendor/select2/select2.min.css', null, '4.0.3.1', 'all' );
			break ;
		
		case 'full':
			wp_register_style( 'wpas-select2', WPAS_URL . 'assets/admin/css/vendor/select2/select2.css', null, '4.0.3.1', 'all' );
			break ;
	}	

	// Scripts
	wp_register_script( 'wpas-plugin-script', WPAS_URL . 'assets/public/js/public-dist.js', array( 'jquery' ), WPAS_VERSION, true );
	
	// Select2 scripts are loaded based on a setting.  This asset is also duplicated on the back-end.
	// Note that we are hardcoding a version number into the wp_register_script call so that we can force caches to update when switching between options.
	$which_select2_js = wpas_get_option('select2_js_file', 'full-min') ;
	switch ( $which_select2_js ) {
		case 'full': 
			wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2/select2.full.js', array( 'jquery' ), '4.0.3.1', 'all' );
			break ;
		
		case 'full-min':
			wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2/select2.full.min.js', array( 'jquery' ), '4.0.3.11', 'all' );
			break ;
			
		case 'partial': 
			wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2/select2.js', array( 'jquery' ), '4.0.3.11', 'all' );
			break ;

		case 'partial-min': 
			wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2/select2.min.js', array( 'jquery' ), '4.0.3.1111', 'all' );
			break ;						
	}
	
	// JS Objects
	wp_localize_script( 'wpas-plugin-script', 'wpas', wpas_get_javascript_object() );

}

add_action( 'admin_enqueue_scripts', 'wpas_register_assets_back_end', 5 );
/**
 * Register all back-end assets
 *
 * @since 3.3
 * @return void
 */
function wpas_register_assets_back_end() {
	
	// Optionally load bootstrap 4 or bootstrap 3 files from cdn.
	// These assets are also duplicated and loaded on the front-end
	$load_bs4  = wpas_get_option('load_bs4_files_be', '0') ;	
	if ( '1' === $load_bs4 ) {
		wpas_register_bs4_theme_styles();
		wp_register_script( 'wpas-bootstrap-4-popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js', array( 'jquery' ), '1.11.0', true );
		wp_register_script( 'wpas-bootstrap-4-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js', array( 'jquery' ), '4.0.0', true );
	}	
	if ( '2' === $load_bs4 ) {
		// Boostrap 3 styles and scripts		
		wp_register_style( 'wpas-bootstrap-3', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css', array(), '3.3.7' );
		wp_register_style( 'wpas-bootstrap-3-ss', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css', array(), '3.3.7' );
		wp_register_script( 'wpas-bootstrap-3-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array( 'jquery' ), '3.3.7', true );
	}
	
	// Other 3rd party styles
	wp_register_style( 'wpas-datepicker', WPAS_URL . 'assets/public/css/component_datepicker.css', null, WPAS_VERSION, 'all' ); // NOTE: This asset is duplicated in the back-end
	wp_register_style( 'wpas-simple-hint', 'https://cdn.jsdelivr.net/simple-hint/2.1.1/simple-hint.min.css', null, '2.1.1' );
	if ( intval( $load_bs4 ) <= 0 ) {	
		wp_register_style( 'wpas-flexboxgrid', WPAS_URL . 'assets/admin/css/vendor/flexboxgrid.min.css', null, '6.2.0', 'all' );	
	}
	
	// Our styles
	wp_register_style( 'wpas-admin-styles', WPAS_URL . 'assets/admin/css/admin.css', array( 'wpas-select2' ), WPAS_VERSION );
	
	// Select2 styles are loaded based on a setting.  This asset is also duplicated on the front-end.
	// Note that we are hardcoding a version number into the wp_register_script call so that we can force caches to update when switching between options.	
	$which_select2_css = wpas_get_option('select2_css_file', 'min') ;
	switch ( $which_select2_css ) {
		case 'min': 
			wp_register_style( 'wpas-select2', WPAS_URL . 'assets/admin/css/vendor/select2/select2.min.css', null, '4.0.3.1', 'all' );
			break ;
		
		case 'full':
			wp_register_style( 'wpas-select2', WPAS_URL . 'assets/admin/css/vendor/select2/select2.css', null, '4.0.3.1', 'all' );
			break ;
	}
	

	// Our Scripts
	wp_register_script( 'wpas-admin-about-linkify', WPAS_URL . 'assets/admin/js/vendor/linkify.min.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-about-linkify-jquery', WPAS_URL . 'assets/admin/js/vendor/linkify-jquery.min.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-about-moment', WPAS_URL . 'assets/admin/js/vendor/moment.min.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-about-script', WPAS_URL . 'assets/admin/js/admin-about.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-optin-script', WPAS_URL . 'assets/admin/js/admin-optin.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-script', WPAS_URL . 'assets/admin/js/admin.js', array( 'jquery', 'wpas-select2' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-toolbars-script', WPAS_URL . 'assets/admin/js/admin-toolbars.js', array( 'jquery', 'wpas-select2' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-tabletojson', WPAS_URL . 'assets/admin/js/vendor/jquery.tabletojson.min.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-reply', WPAS_URL . 'assets/admin/js/admin-reply.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-autolinker', WPAS_URL . 'assets/public/vendor/Autolinker/Autolinker.min.js', null, '0.19.0', true );
	wp_register_script( 'wpas-users', WPAS_URL . 'assets/admin/js/admin-users.js', null, WPAS_VERSION, true );
	wp_register_script( 'wpas-admin-helpers_functions', WPAS_URL . 'assets/public/js/helpers_functions.js', null, WPAS_VERSION );
	wp_register_script( 'wpas-admin-upload', WPAS_URL . 'assets/public/js/component_upload.js', array( 'jquery' ), WPAS_VERSION );

	// @TODO: Why is the version set to TIME() below instead of WPAS_VERSION?
	wp_register_script(
		'wpas-datepicker',
		WPAS_URL . 'assets/public/js/component_datepicker.js',
		array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
		time(),
		true
	);
	
	// Select2 scripts are loaded based on a setting.  This asset is also duplicated on the front-end.
	// Note that we are hardcoding a version number into the wp_register_script call so that we can force caches to update when switching between options.	
	$which_select2_js = wpas_get_option('select2_js_file', 'partial-min') ;
	switch ( $which_select2_js ) {
		case 'full': 
			wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2/select2.full.js', array( 'jquery' ), '4.0.3.1', 'all' );
			break ;
		
		case 'full-min':
			wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2/select2.full.min.js', array( 'jquery' ), '4.0.3.11', 'all' );
			break ;
			
		case 'partial': 
			wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2/select2.js', array( 'jquery' ), '4.0.3.111', 'all' );
			break ;

		case 'partial-min': 
			wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2/select2.min.js', array( 'jquery' ), '4.0.3.1111', 'all' );
			break ;						
	}	

	// JS Objects
	wp_localize_script( 'wpas-admin-script', 'wpas', wpas_get_javascript_object() );
	wp_localize_script( 'wpas-admin-reply', 'wpasL10n', array(
		'alertDelete'    => __( 'Are you sure you want to delete this reply?', 'awesome-support' ),
		'alertNoTinyMCE' => __( 'No instance of TinyMCE found. Please use wp_editor on this page at least once: http://codex.wordpress.org/Function_Reference/wp_editor', 'awesome-support' ),
		'alertNoContent' => __( "You can't submit an empty reply", 'awesome-support' )
	) );
	
	// Custom admin notice style and script
	wp_enqueue_style( 'wpas-admin-wizard-notice', WPAS_URL . 'assets/admin/css/wizard-notice.css', array(), WPAS_VERSION );
	wp_enqueue_script( 'wpas-admin-wizard-script', WPAS_URL . 'assets/admin/js/admin-wizard.js', array( 'jquery' ), WPAS_VERSION );
	wp_localize_script( 'wpas-admin-wizard-script', 'WPAS_Wizard', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'about_page' => admin_url( 'edit.php?post_type=ticket&page=wpas-about' )
	));

}

add_action( 'wp_enqueue_scripts', 'wpas_assets_front_end', 10 );
/**
 * Register and enqueue public-facing style sheet.
 *
 * @since    1.0.2
 */
function wpas_assets_front_end() {

	// Make sure we only enqueue on our plugin's pages
	if ( wpas_is_plugin_page() ) {
		
		// Optionally load bootstrap 4 or bootstrap 3 files from cdn.		
		$load_bs4 = wpas_get_option('load_bs4_files_fe', '0') ;
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

		$stylesheet = wpas_get_theme_stylesheet();

		if ( file_exists( $stylesheet ) && true === boolval( wpas_get_option( 'theme_stylesheet' ) ) ) {
			wp_register_style( 'wpas-theme-styles', wpas_get_theme_stylesheet_uri(), array(), WPAS_VERSION );
			wp_enqueue_style( 'wpas-theme-styles' );
		}

		// Our Custom Scripts
		wp_enqueue_script( 'wpas-plugin-script' );

	}

}

add_action( 'admin_enqueue_scripts', 'wpas_enqueue_assets_back_end', 10 );
/**
 * Register and enqueue admin-specific style sheet.
 *
 * @since     1.0.0
 * @return    null    Return early if no settings page is registered.
 */
function wpas_enqueue_assets_back_end() {

	// Make sure we only enqueue on our plugin's pages
	if ( wpas_is_plugin_page() ) {
		
		// Optionally load bootstrap 4 or bootstrap 3 files from cdn.		
		$load_bs4 = wpas_get_option('load_bs4_files_be', '0') ;
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

		if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			wp_enqueue_style( 'wpas-simple-hint' );
		}

		// Our Scripts
		if ( 'ticket' == get_post_type() ) {
			wp_dequeue_script( 'autosave' );
		}

		$page   = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

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
		wp_enqueue_script( 'wpas-admin-helpers_functions' );
		wp_enqueue_script( 'wpas-admin-upload' );

		if ( 'edit' === $action && 'ticket' == get_post_type() ) {
			wp_enqueue_script( 'wpas-admin-reply' );
			wp_enqueue_script( 'wpas-autolinker' );
		}

	}

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
function wpas_get_javascript_object() {

	global $post;

	if ( ! isset( $post ) || ! is_object( $post ) || ! is_a( $post, 'WP_Post' ) ) {
		return array();
	}

	$upload_max_files = (int) wpas_get_option( 'attachments_max' );
	$upload_max_size  = (int) wpas_get_option( 'filesize_max' );

	// Editors translations
	if ( in_array( $post->ID, wpas_get_submission_pages() ) ) {
		$empty_editor = _x( "You can't submit an empty ticket", 'JavaScript validation error message', 'awesome-support' );
	} else {
		$empty_editor = _x( "You can't submit an empty reply", 'JavaScript validation error message', 'awesome-support' );
	}

	$object = array(
		'ajaxurl'                => admin_url( 'admin-ajax.php' ),
		'emailCheck'             => true === boolval( wpas_get_option( 'enable_mail_check', false ) ) ? 'true' : 'false',
		'fileUploadMax'          => $upload_max_files,
		'fileUploadSize'         => $upload_max_size * 1048576, // We base our calculation on binary prefixes
		'fileUploadMaxError'     => __( sprintf( 'You can only upload a maximum of %d files', $upload_max_files ), 'awesome-support' ),
		'fileUploadMaxSizeError' => array(
			__( 'The following file(s) are too big to be uploaded:', 'awesome-support' ),
			sprintf( __( 'The maximum file size allowed for one file is %d MB', 'awesome-support' ), $upload_max_size )
		),
		'translations' => array(
			'emptyEditor' => $empty_editor,
			'onSubmit'    => _x( 'Submitting...', 'ticket submission button text while submitting', 'awesome-support' ),
		)
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
function wpas_register_bs4_theme_styles() {
	$bs4_theme = wpas_get_option('bs4_theme', 'default') ;
	switch ( $bs4_theme ) {
		
		case 'default':
			wp_register_style( 'wpas-bootstrap-4', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css', array(), '4.0.0' );		
			break ;
				
		case 'awesome':
			wp_register_style( 'wpas-bootstrap-4', WPAS_URL . 'assets/admin/css/vendor/bootstrap4themes/awesome-support/' . $bs4_theme . '/bootstrap.min.css', array(), WPAS_VERSION );
			break ;
			
		case 'custom':
			wp_register_style( 'wpas-bootstrap-4', WPAS_URL . 'assets/admin/css/vendor/bootstrap4themes/custom/style.css', array(), WPAS_VERSION );
			break ;			
			
		default: 
			wp_register_style( 'wpas-bootstrap-4', WPAS_URL . 'assets/admin/css/vendor/bootstrap4themes/bootswatch/' . $bs4_theme . '/bootstrap.min.css', array(), WPAS_VERSION );
			break ;
	}
}