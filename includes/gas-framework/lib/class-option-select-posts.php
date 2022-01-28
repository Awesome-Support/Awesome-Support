<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class GASFrameworkOptionSelectPosts extends GASFrameworkOptionSelect {

	public $defaultSecondarySettings = array(
		'default' => '0', // show this when blank
		'post_type' => 'post',
		'num' => -1,
		'post_status' => 'any',
		'orderby' => 'post_date',
		'order' => 'DESC',
		'query_args' => null,
	);


	/**
	 * Creates the options for the select input. Puts the options in $this->settings['options']
	 *
	 * @since 1.11
	 *
	 * @return void
	 */
	public function create_select_options() {

		if ( ! empty( $this->settings['query_args'] ) ) {
			$args = $this->settings['query_args'];

		} else {
			$args = array(
				'post_type' => $this->settings['post_type'],
				'posts_per_page' => $this->settings['num'],
				'post_status' => $this->settings['post_status'],
				'orderby' => $this->settings['orderby'],
				'order' => $this->settings['order'],
			);
		}

		$posts = get_posts( $args );

		$this->settings['options'] = array(
			'' => '— ' . __( 'Select', GASF_I18NDOMAIN ) . ' —'
		);

		foreach ( $posts as $post ) {
			$title = esc_html( $post->post_title );
			if ( empty( $title ) ) {
				$title = sprintf( __( 'Untitled %s', GASF_I18NDOMAIN ), '(ID #' . $post->ID . ')' );
			}
			$this->settings['options'][ $post->ID ] = $title;
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
