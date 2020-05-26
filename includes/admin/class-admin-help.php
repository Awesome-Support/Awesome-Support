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
	
	public function __construct()
	{
		add_filter( 'admin_head', array( $this, 'settings_general_contextual_help' ), 10, 3 );
		add_filter( 'admin_head', array( $this, 'settings_registration_help' ), 10, 3 );
		add_filter( 'admin_head', array( $this, 'settings_products_management_help' ), 10, 3 );
		add_filter( 'admin_head', array( $this, 'settings_notifications_contextual_help' ), 10, 3 );
		add_filter( 'admin_head', array( $this, 'settings_advanced_contextual_help' ), 10, 3 );
		
		add_filter( 'admin_head', array( $this, 'settings_moderated_registration_help' ), 10, 3 );
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
			'id'      => 'default_assignee',
			'title'   => __( 'Default Assignee', 'awesome-support' ),
			'content' => __( '<h2>Default Assignee</h2><p>Even though the plugin will try to assign new tickets to the less busy agents, we need to know who to assign to in case we can\'t find a perfect fit for the new tickets.</p>', 'awesome-support' )
		) );
		
		$screen->add_help_tab( array(
			'id'      => 'assignee_use_select2',
			'title'   => __( 'Use Select2', 'awesome-support' ),
			'content' => __( '<h2>Use SELECT2 For Staff Drop-downs</h2><p>A SELECT2 drop-down is used when you have a large list of items which would take a long time to render on the screen.  Instead, the user gets to limit the list by searching and seeing the results show up in real-time.  Most sites will not hav a large number of agents so this option is usually turned off.</p>', 'awesome-support' )
		) );		

	}
	
	/**
	 * Registration settings contextual help.
	 *
	 * @since  5.2.0
	 * @return void
	 */
	public function settings_registration_help() {

		if( 'ticket' !== filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING ) ||
		    'registration' !== filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) ) {
			return;
		}
		
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'allow_registrations',
			'title'   => __( 'Allow Registrations', 'awesome-support' ),
			'content' => __( '<h2>Allow Registrations</h2><p>You WordPress site can be set to accept new registrations or not. By default, it doesn\'t. However, with closed registrations, this plugin becomes useless. This is why we added a separate setting to allow registrations. Users registering through Awesome Support will be given a specific role (<code>Support User</code>) with very limited privileges.</p><p>If you allow registrations through the plugin but not through WordPress, users will only be able to register through our registration form.</p>', 'awesome-support' )
		) );
	}	
	
	/**
	 * Moderated registration contextual help.
	 * 
	 * @return void
	 */
	public function settings_moderated_registration_help() {
		if( 'ticket' !== filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING ) ||
		    'modregistration' !== filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) ) {
			return;
		}
		
		/**
		 * Gather the list of e-mail template tags and their description
		 */
		$list_tags = WPAS_User_Email_Notification::get_tags();

		$tags = '<table class="widefat"><thead><th class="row-title">' . __( 'Tag', 'awesome-support' ) . '</th><th>' . __( 'Description', 'awesome-support' ) . '</th></thead><tbody>';

		foreach ( $list_tags as $the_tag ) {
			$tags .= '<tr><td class="row-title"><strong>' . $the_tag['tag'] . '</strong></td><td>' . $the_tag['desc'] . '</td></tr>';
		}

		$tags .= '</tbody></table>';
		
		$screen = get_current_screen();

		
		$screen->add_help_tab( array(
			'id'      => 'user-email-template-tags',
			'title'   => __( 'Email Template Tags', 'awesome-support' ),
			'content' => sprintf( __( '<p>When setting up your e-mails templates, you can use a certain number of template tags allowing you to dynamically add user-related information at the moment the e-mail is sent. Here is the list of available tags:</p>%s', 'awesome-support' ), $tags )
		) );
		
	}
	
	/**
	 * Products management contextual help.
	 *
	 * @since  5.2.0
	 * @return void
	 */
	public function settings_products_management_help() {

		if( 'ticket' !== filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING ) ||
		    'products-management' !== filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) ) {
			return;
		}
		
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'multiple_products',
			'title'   => __( 'Multiple Products', 'awesome-support' ),
			'content' => __( '<h2>Multiple Products</h2><p>The plugin can handle single product and multiple products support. If you do need to provide support for multiple products it is very important that you do NOT use a custom field or taxonomy and use the &laquo;Multiple Products&raquo; option instead.</p><p>The reason why it is so important is that many addons for Awesome Support are using the built-in products management system to work properly.</p>', 'awesome-support' )
		) );
		
		$screen->add_help_tab( array(
			'id'      => 'products_synchronize',
			'title'   => __( 'Synchronize Products', 'awesome-support' ),
			'content' => __( '<h2>Synchronize WooCommerce or EDD Products</h2><p>If you have WooCommerce or EDD installed, you will see an option allowing you to turn on synchronization between those plugins and the Awesome Support product list.  But, you cannot synchronize your product list to both simultanously.  If you have both WC and EDD installed and active for some reason then you will be synchronizing with WooCommerce.</p>', 'awesome-support' )
		) );		
		
		$screen->add_help_tab( array(
			'id'      => 'products_slug',
			'title'   => __( 'Slug', 'awesome-support' ),
			'content' => __( '<h2>Slug</h2><p>Change this if you would like the URL for your products to include something other than PRODUCT.</p>', 'awesome-support' )
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
			'id'      => 'email-html-template',
			'title'   => __( 'Use HTML Template', 'awesome-support' ),
			'content' => __( '<h2>Use HTML Template</h2><p>Wrap all outgoing emails in a set of pretty HTML forms and tags.  With this option turned on you can set a LOGO and create fancy header and footers around your outgoing email alerts.</p><p>However, if you have another plugin installed that already wraps all WordPress outgoing emails in a fancy HTML template then you should turn this option OFF.</p>', 'awesome-support' )
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
			'content' => __( '<h2>Custom Login Page</h2><p>This can be a dangerous setting. It is here to allow advanced users to create their own login / registration page. If you don\'t like our login form, you can replace it by your own.</p><p>To do so, create a new page containing the form (either with the use of a shortcode or a page template), then set this newly created page as the "Custom Login / Registration Page" in the setting below.</p><p><strong>Beware</strong>, setting a wrong page as the custom login page can either show a blank page or create an infinite loop.</p>', 'awesome-support' )
		) );

	}

}