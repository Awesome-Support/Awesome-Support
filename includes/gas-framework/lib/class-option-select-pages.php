<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class GASFrameworkOptionSelectPages extends GASFrameworkOptionSelect {

	public $defaultSecondarySettings = array(
		'default' => '0', // show this when blank
	);

	private static $allPages;

	/**
	 * Creates the options for the select input. Puts the options in $this->settings['options']
	 *
	 * @since 1.11
	 *
	 * @return void
	 */
	public function create_select_options() {
		// Remember the pages so as not to perform any more lookups
		if ( ! isset( self::$allPages ) ) {
			self::$allPages = get_pages();
		}

		$this->settings['options'] = array(
			'' => '— ' . __( 'Select',  'awesome-support' ) . ' —'
		);

		// Print all the other pages
		foreach ( self::$allPages as $page ) {
			$title = $page->post_title;
			if ( empty( $title ) ) {
				// translators: %s is the title.
				$x_content = __( 'Untitled %s',  'awesome-support' );
				$title = sprintf( $x_content, '(ID #' . $page->ID . ')' );
			}
			$this->settings['options'][ $page->ID ] = $title;
		}
	}

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->create_select_options();
		parent::display();
	}


	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$this->create_select_options();
		parent::registerCustomizerControl( $wp_customize, $section, $priority );
	}

}
