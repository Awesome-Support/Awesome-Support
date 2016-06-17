<?php
/**
 * List all site pages.
 *
 * @return array List of pages in an array of the form page_id => page_title
 *
 * @param string $post_type The post type to query
 *
 * @since  3.0.0
 */
function wpas_list_pages( $post_type = 'page' ) {

	$list = array( '' => __( 'None', 'awesome-support' ) );

	$args = array(
		'post_type'              => $post_type,
		'post_status'            => 'publish',
		'order'                  => 'DESC',
		'orderby'                => 'page_title',
		'posts_per_page'         => - 1,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,

	);

	$pages = new WP_Query( $args );

	if ( ! empty( $pages->posts ) ) {

		foreach ( $pages->posts as $page ) {
			$list[ $page->ID ] = $page->post_title;
		}

	}

	return apply_filters( 'wpas_pages_list', $list );

}

/**
 * Get themes list.
 * 
 * @return array
 * @since  3.0.0
 */
function wpas_list_themes() {

	$dir    = WPAS_PATH . 'themes/';
	$themes = array();

	if ( is_dir( $dir ) ) {

		if ( $dh = opendir( $dir ) ) {

			while ( ( $file = readdir( $dh ) ) !== false ) {

				if ( '.' != $file && '..' != $file && is_dir( $dir . $file ) ) {

					if ( file_exists( "$dir$file/css/style.css" ) && file_exists( "$dir$file/registration.php" ) && file_exists( "$dir$file/submission.php" ) ) {
						$themes[$file] = ucwords( $file );
					}

				}

			}

			closedir( $dh );
		}

	}

	return $themes;


}

/**
 * Get plugin settings list
 *
 * Get all plugin settings filtered.
 *
 * @since 3.3
 * @return array
 */
function wpas_get_settings() {

	// Load the file uploader settings if not already done (those settings are loaded on plugins_loaded only)
	if ( ! function_exists( 'wpas_addon_settings_file_upload' ) ) {
		require_once( WPAS_PATH . 'includes/file-uploader/settings-file-upload.php' );
	}

	return apply_filters( 'wpas_plugin_settings', array() );

}

/**
 * Get the list of all options
 *
 * This function filters the settings to remove all the hierarchy and only returns
 * an array of options.
 *
 * @since 3.3
 * @return array
 */
function wpas_get_raw_settings() {

	$settings = wpas_get_settings();

	if ( empty( $settings ) ) {
		return array();
	}

	$just_options = array();

	foreach ( $settings as $tab => $contents ) {

		if ( ! isset( $contents['options'] ) ) {
			continue;
		}

		foreach ( $contents['options'] as $option ) {
			if ( isset( $option['id'] ) && ! array_key_exists( $option['id'], $just_options ) ) {
				$just_options[$option['id']] = $option;
			}
		}

	}

	return $just_options;

}

/**
 * Get all default options
 *
 * @since 3.3
 *
 * @param string $option Optional option ID to get the default value for
 *
 * @return array
 */
function get_settings_defaults( $option = '' ) {

	$options = wpas_get_raw_settings();

	if ( ! empty( $option ) && array_key_exists( $option, $options ) ) {
		return $options[ $option ]['default'];
	}

	$defaults = array();

	foreach ( $options as $key => $option ) {
		if ( isset( $options[ $key ]['default'] ) ) {
			$defaults[ $key ] = $options[ $key ]['default'];
		}
	}

	return $defaults;

}