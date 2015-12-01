<?php
/**
 * @package   Awesome Support/Admin/Functions/Misc
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2015 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'plugin_action_links_' . WPAS_PLUGIN_BASENAME, 'wpas_settings_page_link' );
/**
 * Add a link to the settings page.
 *
 * @since  3.1.5
 *
 * @param  array $links Plugin links
 *
 * @return array        Links with the settings
 */
function wpas_settings_page_link( $links ) {

	$link    = wpas_get_settings_page_url();
	$links[] = "<a href='$link'>" . __( 'Settings', 'awesome-support' ) . "</a>";

	return $links;

}

add_filter( 'postbox_classes_ticket_wpas-mb-details', 'wpas_add_metabox_details_classes' );
/**
 * Add new class to the details metabox.
 *
 * @param array $classes Current metabox classes
 *
 * @return array The updated list of classes
 */
function wpas_add_metabox_details_classes( $classes ) {
	array_push( $classes, 'submitdiv' );

	return $classes;
}

add_action( 'admin_notices', 'wpas_admin_notices' );
/**
 * Display custom admin notices.
 *
 * Custom admin notices are usually triggered by custom actions.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_admin_notices() {

	if ( isset( $_GET['wpas-message'] ) ) {

		switch ( $_GET['wpas-message'] ) {

			case 'opened':
				?>
				<div class="updated">
					<p><?php printf( __( 'The ticket #%s has been (re)opened.', 'awesome-support' ), intval( $_GET['post'] ) ); ?></p>
				</div>
				<?php
				break;

			case 'closed':
				?>
				<div class="updated">
					<p><?php printf( __( 'The ticket #%s has been closed.', 'awesome-support' ), intval( $_GET['post'] ) ); ?></p>
				</div>
				<?php
				break;

		}

	}
}