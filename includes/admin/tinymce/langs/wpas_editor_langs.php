<?php

if ( ! defined( 'ABSPATH' ) )
    exit;
 
if ( ! class_exists( '_WP_Editors' ) )
    require( ABSPATH . WPINC . '/class-wp-editor.php' );
 
function wpas_editor_langs() {
	
    $strings = array(
		
		'button_title' => __('Ticket Email Template Tags', 'awesome-support'),
		'window_title' => __('Awesome Support Email Template Tags', 'awesome-support'),
		'plugin_long_name' => __('Awesome Support Email Template Tags', 'awesome-support'),
		'table_header_tag' => __('Tag', 'awesome-support'),
		'table_header_desc' => __('Description', 'awesome-support'),
		'instructions' => __('Select one of the following tags to insert into the email template, at the current cursor location. <br /> <br /> <i>Note that these tags are only applicable on certain settings tabs and screens.  These include the <b>EMAIL</b>, <b>NOTIFICATIONS</b> and <b>SATISFACTION SURVEY</b> settings tabs as well as the <b>RULESET</b> screens. Some tags are applicable on the <b>FAQ</b> and <b>DOCUMENTATION</b> settings screens as well. Using them anywhere else will have no effect!</i>', 'awesome-support')
    );
 
    $locale = _WP_Editors::$mce_locale;
    $translated = 'tinyMCE.addI18n("' . $locale . '.wpas_editor_langs", ' . json_encode( $strings ) . ");\n";
 
    return $translated;
}
 
$strings = wpas_editor_langs();