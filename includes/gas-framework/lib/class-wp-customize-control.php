<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerGASFrameworkCustomizeControl', 1 );
function registerGASFrameworkCustomizeControl() {
	class GASFrameworkCustomizeControl extends WP_Customize_Control {
		public $description;

		public function render_content() {
			parent::render_content();
			// echo "<p class='description'>{$this->description}</p>";
		}
	}
}
