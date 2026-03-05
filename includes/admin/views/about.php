<?php

add_filter( 'mumei_ayuda_admin_tabs_mumei_ayuda_about',							'mumei_ayuda_about_register_tabs' ); // Register new tab area

add_filter( 'mumei_ayuda_admin_tabs_mumei_ayuda_about_welcome_content',			'mumei_ayuda_admin_tabs_mumei_ayuda_about_welcome_content' ); // add content in welcome log tab
add_filter( 'mumei_ayuda_admin_tabs_mumei_ayuda_about_change_log_content',		'mumei_ayuda_admin_tabs_mumei_ayuda_about_change_log_content' ); // add content in change log tab
add_filter( 'mumei_ayuda_admin_tabs_mumei_ayuda_about_getting_started_content',	'mumei_ayuda_admin_tabs_mumei_ayuda_about_getting_started_content' ); // add content in getting started tab
add_filter( 'mumei_ayuda_admin_tabs_mumei_ayuda_about_videos_content',			'mumei_ayuda_admin_tabs_mumei_ayuda_about_videos_content' ); // add content in videos tab
add_filter( 'mumei_ayuda_admin_tabs_mumei_ayuda_about_docs_content',				'mumei_ayuda_admin_tabs_mumei_ayuda_about_docs_content' ); // add content in documentation tab
add_filter( 'mumei_ayuda_admin_tabs_mumei_ayuda_about_credits_content',			'mumei_ayuda_admin_tabs_mumei_ayuda_about_credits_content' ); // add content in documentation tab


/**
 * Register tabs
 *
 * @param array $tabs
 *
 * @return array
 */
function mumei_ayuda_about_register_tabs( $tabs ) {

		$tabs['welcome']		 = __( 'Welcome', 'ayuda-help-desk' );
		$tabs['getting_started'] = __( 'Getting Started', 'ayuda-help-desk' );
		$tabs['docs']			 = __( 'Documentation', 'ayuda-help-desk' );
		$tabs['videos']			 = __( 'Videos', 'ayuda-help-desk' );
		$tabs['change_log']		 = __( 'Change Log', 'ayuda-help-desk' );
		$tabs['credits']		 = __( 'Credits', 'ayuda-help-desk' );

		return $tabs;
}

/**
 * Add content in welcome tab
 *
 * @param string $content
 *
 * @return string
 */
function mumei_ayuda_admin_tabs_mumei_ayuda_about_welcome_content( $content ) {

	ob_start();

	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/about-tab-welcome.php' );
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
function mumei_ayuda_admin_tabs_mumei_ayuda_about_change_log_content( $content ) {

	ob_start();

	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/about-tab-change-log.php' );
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
function mumei_ayuda_admin_tabs_mumei_ayuda_about_getting_started_content( $content ) {

	ob_start();

	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/about-tab-getting-started.php' );
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
function mumei_ayuda_admin_tabs_mumei_ayuda_about_videos_content( $content ) {

	ob_start();

	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/about-tab-videos.php' );
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
function mumei_ayuda_admin_tabs_mumei_ayuda_about_docs_content( $content ) {

	ob_start();

	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/about-tab-docs.php' );

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
function mumei_ayuda_admin_tabs_mumei_ayuda_about_credits_content( $content ) {

	ob_start();

	include_once( MUMEI_AYUDA_PATH . 'includes/admin/views/about-tab-credits.php' );

	$content = ob_get_clean();

	return $content;
}

?>

<div class="wrap about-wrap">

	<h1><?php echo esc_html__( 'Welcome to Ayuda – Help Desk ', 'ayuda-help-desk' );?><?php echo esc_html( MUMEI_AYUDA_VERSION ); ?></h1>
	<div class="about-text"><?php echo esc_html__( 'Trusted by over 10,000+ Happy Users, Ayuda – Help Desk is the most versatile WordPress support plugin.', 'ayuda-help-desk' );?></div>
	<hr />

	<?php echo wp_kses(mumei_ayuda_admin_tabs( 'mumei_ayuda_about' ), get_allowed_html_wp_notifications()); ?>

</div>
