<?php
/**
 * Contextual Help.
 *
 * @package   Admin/Help
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

class WPAS_Help {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;
	
	public function __construct() {
		add_filter( 'contextual_help', array( $this, 'settings_general_contextual_help' ), 10, 3 );
		add_filter( 'contextual_help', array( $this, 'settings_notifications_contextual_help' ), 10, 3 );
		add_filter( 'contextual_help', array( $this, 'settings_advanced_contextual_help' ), 10, 3 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     3.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * General settings contextual help.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_general_contextual_help() {

		if( 'ticket' !== filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING ) ||
		    'general' !== filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) ) {
			return;
		}
		
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'multiple_products',
			'title'   => __( 'Multiple Products', 'awesome-support' ),
			'content' => __( '<h2>Multiple Products</h2><p>The plugin can handle single product and multiple products support. If you do need to provide support for multiple products it is very important that you do NOT use a custom field or taxonomy and use the &laquo;Multiple Products&raquo; option instead.</p><p>The reason why it is so important is that many addons for Awesome Support are using the built-in products management system to work properly.</p>', 'awesome-support' )
		) );

		$screen->add_help_tab( array(
			'id'      => 'default_assignee',
			'title'   => __( 'Default Assignee', 'awesome-support' ),
			'content' => __( '<h2>Default Assignee</h2><p>Even though the plugin will try to assign new tickets to the less busy agent, we need to know who to assign to in case we can\'t find a perfect fit for the new tickets.</p>', 'awesome-support' )
		) );

		$screen->add_help_tab( array(
			'id'      => 'allow_registrations',
			'title'   => __( 'Allow Registrations', 'awesome-support' ),
			'content' => __( '<h2>Allow Registrations</h2><p>You WordPress site can be set to accept new registrations or not. By default, it doesn\'t. However, with closed registrations, this plugin becomes useless. This is why we added a separate setting to allow registrations. Users registering through Awesome Support will be given a specific role (<code>Support User</code>) with very limited privileges.</p><p>If you allow registrations through the plugin but not through WordPress, users will only be able to register through our registration form.</p>', 'awesome-support' )
		) );
	}

	/**
	 * Notifications settings contextual help.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_notifications_contextual_help() {

		if( 'ticket' !== filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING ) ||
		    'email' !== filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) ) {
			return;
		}

		/**
		 * Gather the list of e-mail template tags and their description
		 */
		$list_tags = WPAS_Email_Notification::get_tags();

		$tags = '<table class="widefat"><thead><th class="row-title">' . __( 'Tag', 'awesome-support' ) . '</th><th>' . __( 'Description', 'awesome-support' ) . '</th></thead><tbody>';

		foreach ( $list_tags as $the_tag ) {
			$tags .= '<tr><td class="row-title"><strong>' . $the_tag['tag'] . '</strong></td><td>' . $the_tag['desc'] . '</td></tr>';
		}

		$tags .= '</tbody></table>';
		
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'general',
			'title'   => __( 'General Settings', 'awesome-support' ),
			'content' => __( '<h2>Multiple Products</h2><p>The plugin can handle single product and multiple products support. If you do need to provide support for multiple products it is very important that you do NOT use a custom field or taxonomy and use the &laquo;Multiple Products&raquo; option instead.</p><p>The reason why it is so important is that many addons for Awesome Support are using the built-in products management system to work properly.</p>', 'awesome-support' )
		) );
		
		$screen->add_help_tab( array(
			'id'      => 'template-tags',
			'title'   => __( 'Email Template Tags', 'awesome-support' ),
			'content' => sprintf( __( '<p>When setting up your e-mails templates, you can use a certain number of template tags allowing you to dynamically add ticket-related information at the moment the e-mail is sent. Here is the list of available tags:</p>%s', 'awesome-support' ), $tags )
		) );
	}

	/**
	 * Advanced settings contextual help.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_advanced_contextual_help() {

		if( 'ticket' !== filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING ) ||
		    'advanced' !== filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) ) {
			return;
		}

		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'custom_login',
			'title'   => __( 'Custom Login Page', 'awesome-support' ),
			'content' => __( '<h2>Multiple Products</h2><p>This can be a dangerous setting. It is here to allow advanced users to create their own login / registration page. If you don\'t like our login form, you can replace it by your own.</p><p>To do so, create a new page containing the form (either with the use of a shortcode or a page template), then set this newly created page as the "Custom Login / Registration Page" in the setting below.</p><p><strong>Beware</strong>, setting a wrong page as the custom login page can either show a blank page or create an infinite loop.</p>', 'awesome-support' )
		) );

	}

}