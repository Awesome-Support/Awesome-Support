<?php

add_filter( 'wpas_admin_tabs_wpas_about',							'wpas_about_register_tabs' ); // Register new tab area

add_filter( 'wpas_admin_tabs_wpas_about_welcome_content',			'wpas_admin_tabs_wpas_about_welcome_content' ); // add content in welcome log tab
add_filter( 'wpas_admin_tabs_wpas_about_change_log_content',		'wpas_admin_tabs_wpas_about_change_log_content' ); // add content in change log tab
add_filter( 'wpas_admin_tabs_wpas_about_getting_started_content',	'wpas_admin_tabs_wpas_about_getting_started_content' ); // add content in getting started tab
add_filter( 'wpas_admin_tabs_wpas_about_videos_content',			'wpas_admin_tabs_wpas_about_videos_content' ); // add content in videos tab
add_filter( 'wpas_admin_tabs_wpas_about_docs_content',				'wpas_admin_tabs_wpas_about_docs_content' ); // add content in documentation tab
add_filter( 'wpas_admin_tabs_wpas_about_credits_content',			'wpas_admin_tabs_wpas_about_credits_content' ); // add content in documentation tab


/**
 * Register tabs
 *
 * @param array $tabs
 *
 * @return array
 */
function wpas_about_register_tabs( $tabs ) {

		$tabs['welcome']		 = __( 'Welcome', 'awesome-support' );
		$tabs['getting_started'] = __( 'Getting Started', 'awesome-support' );
		$tabs['docs']			 = __( 'Documentation', 'awesome-support' );
		$tabs['videos']			 = __( 'Videos', 'awesome-support' );
		$tabs['change_log']		 = __( 'Change Log', 'awesome-support' );
		$tabs['credits']		 = __( 'Credits', 'awesome-support' );

		return $tabs;
}

/**
 * Add content in welcome tab
 *
 * @param string $content
 *
 * @return string
 */
function wpas_admin_tabs_wpas_about_welcome_content( $content ) {

	ob_start();

	include_once( WPAS_PATH . 'includes/admin/views/about-tab-welcome.php' );
	$content = ob_get_clean();

	return $content;
}

/**
 * Add content in change log tab
 *
 * @param string $content
 *
 * @return string
 */
function wpas_admin_tabs_wpas_about_change_log_content( $content ) {

	ob_start();

	include_once( WPAS_PATH . 'includes/admin/views/about-tab-change-log.php' );
	$content = ob_get_clean();

	return $content;
}

/**
 * Add content in getting started tab
 *
 * @param string $content
 *
 * @return string
 */
function wpas_admin_tabs_wpas_about_getting_started_content( $content ) {

	ob_start();

	include_once( WPAS_PATH . 'includes/admin/views/about-tab-getting-started.php' );
	$content = ob_get_clean();

	return $content;
}

/**
 * Add content in videos tab
 *
 * @param string $content
 *
 * @return string
 */
function wpas_admin_tabs_wpas_about_videos_content( $content ) {

	ob_start();

	include_once( WPAS_PATH . 'includes/admin/views/about-tab-videos.php' );
	$content = ob_get_clean();

	return $content;
}

/**
 * Add content in documentation tab
 *
 * @param string $content
 *
 * @return string
 */
function wpas_admin_tabs_wpas_about_docs_content( $content ) {

	ob_start();

	include_once( WPAS_PATH . 'includes/admin/views/about-tab-docs.php' );

	$content = ob_get_clean();

	return $content;
}

/**
 * Add content in credits tab
 *
 * @param string $content
 *
 * @return string
 */
function wpas_admin_tabs_wpas_about_credits_content( $content ) {

	ob_start();

	include_once( WPAS_PATH . 'includes/admin/views/about-tab-credits.php' );

	$content = ob_get_clean();

	return $content;
}

?>

<div class="wrap about-wrap">

	<h1><?php echo esc_html__( 'Welcome to Awesome Support ', 'awesome-support' );?><?php echo esc_html( WPAS_VERSION ); ?></h1>
	<div class="about-text"><?php echo esc_html__( 'Trusted by over 10,000+ Happy Users, Awesome Support is the most versatile WordPress support plugin.', 'awesome-support' );?></div>
	<hr />

	<?php echo wp_kses(wpas_admin_tabs( 'wpas_about' ), get_allowed_html_wp_notifications()); ?>

</div>
