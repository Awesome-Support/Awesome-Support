<?php
/**
 * @package   Awesome Support/Admin/Functions/Editor
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPAS_Editor_Email_Template_Tags_Button {
	
	public function __construct() {
		
		add_action( 'init', array( $this, 'init' ) );
	}
	
	public function init() {
		
		// Add button to editor
		// NOTE: THIS IS CURRENTLY HOOKED TO ALL EDITORS
		// Meaning... it will display on every editor throughout the site.
		// If the button is only used for template tags; herego only on the email template settings page; then it would be better to define the button when calling those wp_editor() instances.
		add_filter( 'mce_buttons', array( $this, 'add_editor_button' ) );
		
		// Add plugin to plugin array
		add_filter( 'mce_external_plugins', array( $this, 'add_plugin_array' ) );
		
		// Add language localizeation
		add_filter( 'mce_external_languages', array( $this, 'editor_tinymce_langs' ) );
		
		// Pass js variables to tinymce
		add_action( 'after_wp_tiny_mce', array( $this, 'editor_after_wp_tiny_mce' ) );
	}
	
	/**
	 * Add button to row 2 of editor
	 *
	 * @since 3.3.3
	 * @return bool
	 */
	public function add_editor_button( $buttons ) {
		
		$screen = get_current_screen();
			
		array_push( $buttons, 'wpas_editor_email_template_tags' );
		return $buttons;
	}
	
	/**
	 * Add button js to editor plugin array
	 *
	 * @since 3.3.3
	 * @return bool
	 */
	public function add_plugin_array( $plugin_array ) {
		
		$plugin_array['wpas_editor_email_template_tags'] = plugins_url( '/includes/admin/tinymce/wpas_editor_email_template_tags/editor_plugin.js', dirname( dirname( __FILE__ ) ) );
		return $plugin_array;
	}
	
	/**
	 * Localize tinymce langs
	 *
	 * @since 3.3.3
	 * @return bool
	 */
	public function editor_tinymce_langs( $locales ) {
		
		$locales['wpas_editor_langs'] = plugin_dir_path ( dirname( dirname( __FILE__ ) ) ) . 'includes/admin/tinymce/langs/wpas_editor_langs.php';
    	return $locales;
	}
	
	/**
	 * Localize available template tags into tinymce script
	 *
	 * @since 3.3.3
	 * @return bool
	 */
	public function editor_after_wp_tiny_mce() {
		
		// Get WPAS email template tags
		$list_tags = WPAS_Email_Notification::get_tags();
		$list_tags = json_encode( $list_tags, true );
		
		$script = 'var wpas_editor_js_vars = { "template_tags": ' . $list_tags . ' };' ;
		printf( '<script type="text/javascript">%s</script>', $script );
	}
}

$WPAS_Editor_Email_Template_Tags_Button = new WPAS_Editor_Email_Template_Tags_Button();