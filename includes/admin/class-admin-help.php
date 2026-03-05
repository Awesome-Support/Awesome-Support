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

class MUMEI_AYUDA_Help {

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
		
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		
		if( 'ticket' !== $post_type || 'general' !== $tab ) {
			return;
		}
		
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'default_assignee',
			'title'   => __( 'Default Assignee', 'ayuda-help-desk' ),
			'content' => __( '<h2>Default Assignee</h2><p>Even though the plugin will try to assign new tickets to the less busy agents, we need to know who to assign to in case we can\'t find a perfect fit for the new tickets.</p>', 'ayuda-help-desk' )
		) );
		
		$screen->add_help_tab( array(
			'id'      => 'assignee_use_select2',
			'title'   => __( 'Use Select2', 'ayuda-help-desk' ),
			'content' => __( '<h2>Use SELECT2 For Staff Drop-downs</h2><p>A SELECT2 drop-down is used when you have a large list of items which would take a long time to render on the screen.  Instead, the user gets to limit the list by searching and seeing the results show up in real-time.  Most sites will not hav a large number of agents so this option is usually turned off.</p>', 'ayuda-help-desk' )
		) );		

	}
	
	/**
	 * Registration settings contextual help.
	 *
	 * @since  5.2.0
	 * @return void
	 */
	public function settings_registration_help() {

		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		if( 'ticket' !== $post_type || 'registration' !== $tab ) {
			return;
		}
		
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'allow_registrations',
			'title'   => __( 'Allow Registrations', 'ayuda-help-desk' ),
			'content' => __( '<h2>Allow Registrations</h2><p>You WordPress site can be set to accept new registrations or not. By default, it doesn\'t. However, with closed registrations, this plugin becomes useless. This is why we added a separate setting to allow registrations. Users registering through Ayuda – Help Desk will be given a specific role (<code>Support User</code>) with very limited privileges.</p><p>If you allow registrations through the plugin but not through WordPress, users will only be able to register through our registration form.</p>', 'ayuda-help-desk' )
		) );
	}	
	
	/**
	 * Moderated registration contextual help.
	 * 
	 * @return void
	 */
	public function settings_moderated_registration_help() {
		
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		
		if( 'ticket' !== $post_type || 'modregistration' !== $tab ) {
			return;
		}
		
		/**
		 * Gather the list of e-mail template tags and their description
		 */
		$list_tags = MUMEI_AYUDA_User_Email_Notification::get_tags();

		$tags = '<table class="widefat"><thead><th class="row-title">' . __( 'Tag', 'ayuda-help-desk' ) . '</th><th>' . __( 'Description', 'ayuda-help-desk' ) . '</th></thead><tbody>';

		foreach ( $list_tags as $the_tag ) {
			$tags .= '<tr><td class="row-title"><strong>' . $the_tag['tag'] . '</strong></td><td>' . $the_tag['desc'] . '</td></tr>';
		}

		$tags .= '</tbody></table>';
		
		$screen = get_current_screen();

		// translators: %s is a list of available template tags for email setup.
		$content = sprintf( __( '<p>When setting up your e-mails templates, you can use a certain number of template tags allowing you to dynamically add user-related information at the moment the e-mail is sent. Here is the list of available tags:</p>%s', 'ayuda-help-desk' ), $tags );
		$screen->add_help_tab( array(
			'id'      => 'user-email-template-tags',
			'title'   => __( 'Email Template Tags', 'ayuda-help-desk' ),
			'content' => $content
		) );
		
	}
	
	/**
	 * Products management contextual help.
	 *
	 * @since  5.2.0
	 * @return void
	 */
	public function settings_products_management_help() {

		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		
		if( 'ticket' !== $post_type || 'products-management' !== $tab ) {
			return;
		}
		
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'multiple_products',
			'title'   => __( 'Multiple Products', 'ayuda-help-desk' ),
			'content' => __( '<h2>Multiple Products</h2><p>The plugin can handle single product and multiple products support. If you do need to provide support for multiple products it is very important that you do NOT use a custom field or taxonomy and use the &laquo;Multiple Products&raquo; option instead.</p><p>The reason why it is so important is that many addons for Ayuda – Help Desk are using the built-in products management system to work properly.</p>', 'ayuda-help-desk' )
		) );
		
		$screen->add_help_tab( array(
			'id'      => 'products_synchronize',
			'title'   => __( 'Synchronize Products', 'ayuda-help-desk' ),
			'content' => __( '<h2>Synchronize WooCommerce or EDD Products</h2><p>If you have WooCommerce or EDD installed, you will see an option allowing you to turn on synchronization between those plugins and the Ayuda – Help Desk product list.  But, you cannot synchronize your product list to both simultanously.  If you have both WC and EDD installed and active for some reason then you will be synchronizing with WooCommerce.</p>', 'ayuda-help-desk' )
		) );		
		
		$screen->add_help_tab( array(
			'id'      => 'products_slug',
			'title'   => __( 'Slug', 'ayuda-help-desk' ),
			'content' => __( '<h2>Slug</h2><p>Change this if you would like the URL for your products to include something other than PRODUCT.</p>', 'ayuda-help-desk' )
		) );

	}	

	/**
	 * Notifications settings contextual help.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_notifications_contextual_help() {

		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		if( 'ticket' !== $post_type || 'email' !== $tab ) {
			return;
		}

		/**
		 * Gather the list of e-mail template tags and their description
		 */
		$list_tags = MUMEI_AYUDA_Email_Notification::get_tags();

		$tags = '<table class="widefat"><thead><th class="row-title">' . __( 'Tag', 'ayuda-help-desk' ) . '</th><th>' . __( 'Description', 'ayuda-help-desk' ) . '</th></thead><tbody>';

		foreach ( $list_tags as $the_tag ) {
			$tags .= '<tr><td class="row-title"><strong>' . $the_tag['tag'] . '</strong></td><td>' . $the_tag['desc'] . '</td></tr>';
		}

		$tags .= '</tbody></table>';
		
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'email-html-template',
			'title'   => __( 'Use HTML Template', 'ayuda-help-desk' ),
			'content' => __( '<h2>Use HTML Template</h2><p>Wrap all outgoing emails in a set of pretty HTML forms and tags.  With this option turned on you can set a LOGO and create fancy header and footers around your outgoing email alerts.</p><p>However, if you have another plugin installed that already wraps all WordPress outgoing emails in a fancy HTML template then you should turn this option OFF.</p>', 'ayuda-help-desk' )
		) );
		
		// translators: %s is a list of available template tags for email setup.
		$content = sprintf( __( '<p>When setting up your e-mails templates, you can use a certain number of template tags allowing you to dynamically add ticket-related information at the moment the e-mail is sent. Here is the list of available tags:</p>%s', 'ayuda-help-desk' ), $tags );
		$screen->add_help_tab( array(
			'id'      => 'template-tags',
			'title'   => __( 'Email Template Tags', 'ayuda-help-desk' ),
			'content' => $content
		) );

	}

	/**
	 * Advanced settings contextual help.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_advanced_contextual_help() {

		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		
		if( 'ticket' !== $post_type || 'advanced' !== $tab ) {
			return;
		}

		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'custom_login',
			'title'   => __( 'Custom Login Page', 'ayuda-help-desk' ),
			'content' => __( '<h2>Custom Login Page</h2><p>This can be a dangerous setting. It is here to allow advanced users to create their own login / registration page. If you don\'t like our login form, you can replace it by your own.</p><p>To do so, create a new page containing the form (either with the use of a shortcode or a page template), then set this newly created page as the "Custom Login / Registration Page" in the setting below.</p><p><strong>Beware</strong>, setting a wrong page as the custom login page can either show a blank page or create an infinite loop.</p>', 'ayuda-help-desk' )
		) );

	}

}