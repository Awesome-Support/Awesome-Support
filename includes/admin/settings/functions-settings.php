<?php
/**
 * List all site pages.
 * 
 * @return array List of pages in an array of the form page_id => page_title
 * @since  3.0.0
 */
function wpas_list_pages() {

	$list = array( '' => __( 'None', 'wpas' ) );

	$args = array(
		'post_type'              => 'page',
		'post_status'            => 'publish',
		'order'                  => 'DESC',
		'orderby'                => 'page_title',
		'posts_per_page'         => -1,
		'no_found_rows'          => false,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		
	);

	$pages = new WP_Query( $args );
	
	if( !empty( $pages->posts ) ) {

		foreach( $pages->posts as $page ) {
			$list[$page->ID] = apply_filters( 'the_title', $page->post_title );
		}

	}

	return apply_filters( 'wpas_pages_list', $list );

}

/**
 * List users.
 *
 * Returns a list of users based on the required
 * capability. If the capability is "all", all site
 * users are returned.
 * 
 * @param  string $cap Minimum capability the user must have to be added to the list
 * @return array       A list of users
 * @since  3.0.0
 */
function wpas_list_users( $cap = 'all' ) {

	$list = array();

	/* List all users */
	$all_users = get_users();

	foreach( $all_users as $user ) {

		/* Exclude agents */
		if( 'all' == $cap || ( 'all' != $cap && $user->has_cap( $cap ) ) ) {

			$user_id        = $user->ID;
			$user_name      = $user->data->user_nicename;
			$list[$user_id] = $user_name;

		}

	}

	return apply_filters( 'wpas_users_list', $list );

}

/**
 * Get themes list.
 * 
 * @return [type] [description]
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