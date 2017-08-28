<?php
/**
 * @package   Awesome Support/Compatibility/WPML
 * @author    David Garcia Watkins <david.g@onthegosystems.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2017 Awesome Support
 */

if ( ! is_admin() ) {
	add_filter( 'wpas_plugin_frontend_pages', 'wpas_translate_frontend_pages_ids' );
}

function wpas_translate_frontend_pages_ids( $ids ) {
	foreach ( (array) $ids as $id ) {
		$ids[] = apply_filters( 'wpml_object_id', $id, 'page' );
	}

	return $ids;
}