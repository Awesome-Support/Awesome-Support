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