<?php
/**
 * @package   Awesome Support/Scripts
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2015 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wp_enqueue_scripts', 'wpas_enqueue_styles', 10, 0 );
/**
 * Register and enqueue public-facing style sheet.
 *
 * @since    1.0.2
 */
function wpas_enqueue_styles() {

	wp_register_style( 'wpas-plugin-styles', WPAS_URL . 'assets/public/css/public.css', array(), WPAS_VERSION );

	if ( ! is_admin() && wpas_is_plugin_page() ) {

		wp_enqueue_style( 'wpas-plugin-styles' );

		$stylesheet = wpas_get_theme_stylesheet();

		if ( file_exists( $stylesheet ) && true === boolval( wpas_get_option( 'theme_stylesheet' ) ) ) {
			wp_register_style( 'wpas-theme-styles', wpas_get_theme_stylesheet_uri(), array(), WPAS_VERSION );
			wp_enqueue_style( 'wpas-theme-styles' );
		}

	}

}


add_action( 'wp_enqueue_scripts', 'wpas_enqueue_scripts', 10, 0 );
/**
 * Register and enqueues public-facing JavaScript files.
 *
 * @since    1.0.2
 */
function wpas_enqueue_scripts() {

	wp_register_script( 'wpas-plugin-script', WPAS_URL . 'assets/public/js/public-dist.js', array( 'jquery' ), WPAS_VERSION, true );

	if ( ! is_admin() && wpas_is_plugin_page() ) {
		wp_enqueue_script( 'wpas-plugin-script' );
	}

	wp_localize_script( 'wpas-plugin-script', 'wpas', wpas_get_javascript_object() );

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
		return;
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

	return $object;

}

add_action( 'admin_enqueue_scripts', 'wpas_enqueue_admin_styles' );
/**
 * Register and enqueue admin-specific style sheet.
 *
 * @since     1.0.0
 * @return    null    Return early if no settings page is registered.
 */
function wpas_enqueue_admin_styles() {

	wp_register_style( 'wpas-select2', WPAS_URL . 'assets/admin/css/vendor/select2.min.css', null, '3.5.2', 'all' );
	wp_register_style( 'wpas-flexboxgrid', WPAS_URL . 'assets/admin/css/vendor/flexboxgrid.min.css', null, '6.2.0', 'all' );
	wp_register_style( 'wpas-admin-styles', WPAS_URL . 'assets/admin/css/admin.css', array( 'wpas-select2' ), WPAS_VERSION );

	if ( wpas_is_plugin_page() ) {
		wp_enqueue_style( 'wpas-select2' );
		wp_enqueue_style( 'wpas-flexboxgrid' );
		wp_enqueue_style( 'wpas-admin-styles' );
	}

}

add_action( 'admin_enqueue_scripts', 'wpas_enqueue_admin_scripts' );
/**
 * Register and enqueue admin-specific JavaScript.
 *
 * @since     1.0.0
 * @return    void
 */
function wpas_enqueue_admin_scripts() {

	wp_register_script( 'wpas-admin-about-linkify', WPAS_URL . 'assets/admin/js/vendor/linkify.min.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-about-linkify-jquery', WPAS_URL . 'assets/admin/js/vendor/linkify-jquery.min.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-about-moment', WPAS_URL . 'assets/admin/js/vendor/moment.min.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-about-script', WPAS_URL . 'assets/admin/js/admin-about.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-select2', WPAS_URL . 'assets/admin/js/vendor/select2.min.js', array( 'jquery' ), '4.0.0', true );
	wp_register_script( 'wpas-admin-script', WPAS_URL . 'assets/admin/js/admin.js', array( 'jquery', 'wpas-select2' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-tabletojson', WPAS_URL . 'assets/admin/js/vendor/jquery.tabletojson.min.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-admin-reply', WPAS_URL . 'assets/admin/js/admin-reply.js', array( 'jquery' ), WPAS_VERSION );
	wp_register_script( 'wpas-autolinker', WPAS_URL . 'assets/public/vendor/Autolinker/Autolinker.min.js', null, '0.19.0', true );

	if ( ! wpas_is_plugin_page() ) {
		return;
	}

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

	wp_enqueue_script( 'wpas-select2' );
	wp_enqueue_script( 'wpas-admin-script' );
	wp_enqueue_script( 'wpas-admin-tabletojson' );

	if ( 'edit' === $action && 'ticket' == get_post_type() ) {
		wp_enqueue_script( 'wpas-admin-reply' );
		wp_enqueue_script( 'wpas-autolinker' );
		wp_localize_script( 'wpas-admin-reply', 'wpasL10n', array(
				'alertDelete'    => __( 'Are you sure you want to delete this reply?', 'awesome-support' ),
				'alertNoTinyMCE' => __( 'No instance of TinyMCE found. Please use wp_editor on this page at least once: http://codex.wordpress.org/Function_Reference/wp_editor', 'awesome-support' ),
				'alertNoContent' => __( "You can't submit an empty reply", 'awesome-support' )
		) );
	}

}