<?php
/**
 * @package   Ayuda – Help Desk/Compatibility/WPML
 * @author    David Garcia Watkins <david.g@onthegosystems.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2017 Ayuda – Help Desk
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wpml_loaded', 'mumei_ayuda_load_wpml_compatibility' );
/**
 * Attach filters for WPML compatibility only when WPML is active.
 *
 * Using the 'wpml_loaded' action we can make sure the code only runs
 * when WPML is installed and active.
 *
 * @since 4.0.7
 * @return void
 */
function mumei_ayuda_load_wpml_compatibility() {

	// We only need this in the frontend.
	if ( ! is_admin() ) {
		add_filter( 'mumei_ayuda_plugin_frontend_pages', 'mumei_ayuda_translate_frontend_pages_ids' );
	}

}

/**
 * Add translated page ids to the current language.
 *
 * Using the 'mumei_ayuda_plugin_frontend_pages' filter we add translated page ids
 * for our pages to be detected correctly in all languages.
 *
 * Without this, translated pages are missing assets in the frontend.
 *
 * @param array $ids
 * @return array
 */
function mumei_ayuda_translate_frontend_pages_ids( $ids ) {

	// Add each translated page id to the resulting array.
	foreach ( (array) $ids as $id ) {
		$ids[] = apply_filters( 'wpml_object_id', $id, 'page' );
	}

	return $ids;
}