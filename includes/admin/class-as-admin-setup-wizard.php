<?php
/**
 * As Setup Wizard Class
 *
 * Takes new users through some basic steps to setup their support.
 * 
 * @package   Awesome Support/Admin/AS_Admin_Setup_Wizard
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2018 AwesomeSupport
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS_Admin_Setup_Wizard class.
 */
class AS_Admin_Setup_Wizard {

	/**
	 * Current step
	 *
	 * @var string
	 */
	private $step = '';

	/**
	 * Steps for the setup wizard
	 *
	 * @var array
	 */
	private $steps = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// check if current user can manage wizard.
		add_action( 'admin_menu', array( $this, 'admin_menus' ) );
		add_action( 'admin_init', array( $this, 'setup_wizard' ) );
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'as-setup', '' );
	}

	/**
	 * Show the setup wizard.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'as-setup' !== $_GET['page'] ) { // WPCS: CSRF ok, input var ok.
			return;
		}
		$default_steps = array(
			'product_setup' => array(
				'name'    => __( 'Product Setup', 'awesome-support' ),
				'view'    => array( $this, 'as_product_setup_setup' ),
				'handler' => array( $this, 'as_product_setup_setup_save' ),
			),
			'submit_ticket_page'     => array(
				'name'    => __( 'Submit ticket page', 'awesome-support' ),
				'view'    => array( $this, 'as_setup_submit_ticket_page' ),
				'handler' => array( $this, 'as_setup_submit_ticket_page_save' ),
			),
			'my_ticket_page'    => array(
				'name'    => __( 'My ticket Page', 'awesome-support' ),
				'view'    => array( $this, 'as_setup_my_ticket_page' ),
				'handler' => array( $this, 'as_setup_my_ticket_page_save' ),
			),
			'priorities'      => array(
				'name'    => __( 'Priorities', 'awesome-support' ),
				'view'    => array( $this, 'as_setup_priorities' ),
				'handler' => array( $this, 'as_setup_priorities_save' ),
			),
			'departments'    => array(
				'name'    => __( 'Departments', 'awesome-support' ),
				'view'    => array( $this, 'as_setup_departments' ),
				'handler' => array( $this, 'as_setup_departments_save' ),
			),
			'lets_go'    => array(
				'name'    => __( "Let's Go", 'awesome-support' ),
				'view'    => array( $this, 'as_setup_lets_go' ),
				'handler' => array( $this, 'as_setup_lets_go_save' ),
			),
		);
		
		// Admin styles
		wp_enqueue_style( 'as-admin-style', WPAS_URL . 'assets/admin/css/admin.css', WPAS_VERSION );
		wp_enqueue_style( 'admin-wizard-style', WPAS_URL . 'assets/admin/css/setup-wizard.css', WPAS_VERSION );
		wp_register_script( 'as-admin-script', WPAS_URL . 'assets/admin/js/as-setup.js', array( 'jquery' ), '1.0.0' );
		wp_register_script( 'as-setup', WPAS_URL . '/assets/admin/js/as-setup.js', array( 'jquery', 'wp-util' ), WPAS_VERSION );

		$this->steps = apply_filters( 'as_setup_wizard_steps', $default_steps );
		$this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) ); // WPCS: CSRF ok, input var ok.
	
		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}

		ob_start();
		// call setup view functions here.
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	/**
	 * Setup Wizard Header.
	 */
	public function setup_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'Awesome Support &rsaquo; Setup Wizard', 'awesome-support' ); ?></title>
			<?php wp_print_scripts( 'as-setup' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="as-setup wp-core-ui">
			<div class="as-setup-wizard">
			<h1 id="as-logo"><a href="https://getawesomesupport.com/">Awesome Support</a></h1>			
		<?php
	}


	/**
	 * Output the steps.
	 */
	public function setup_wizard_steps() {
		$output_steps = $this->steps;
		?>
		<ol class="as-setup-steps">
			<?php foreach ( $output_steps as $step_key => $step ) : ?>
				<?php
					/**
					 * Determine step_class
					 * On each steps done, add .done while .active 
					 * for the current step state.
					*/
					if ( $step_key === $this->step ) {
						$step_class = 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true ) ) {
						$step_class =  'done';
					} else {
						$step_class = '';
					}
				?>
				<li class="<?php echo $step_class; ?>"><div class="hint">
					<?php echo esc_html( $step['name'] ); ?>
				</div></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_content() {
		echo '<div class="as-setup-content">';
/*		
		printf(
			'<p class="sub-heading">%s</p>',
			__( 'Welcome to Awesome Support! This setup wizard will help you to quickly configure your new support system so that you can start processing customer requests right away.  So lets get started with our first question!', 'awesome-support' )
		);
*/		
		if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
			call_user_func( $this->steps[ $this->step ]['view'], $this );
			
		}
		echo '</div>';
	}


	/**
	 * Setup Wizard Footer.
	 */
	public function setup_wizard_footer() {
		$about_us_link = add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-about' ), admin_url( 'edit.php' ) )
		?>
		<?php if ( 'lets_go' !== $this->step ) : ?>
			<a class="not-now" href="<?php echo esc_url( $about_us_link ); ?>"><?php esc_html_e( 'Not right now', 'awesome-support' ); ?></a>
		<?php endif; ?>
				</div><!-- .setup-wizard -->
			</body>
		</html>
		<?php
	}

	/**
	 * Awesome Support Multiple or single Product setup
	 */
	public function as_product_setup_setup(){
		$support_products = wpas_get_option( 'support_products' );
		printf(
			'<p class="sub-heading">%s</p>',
			__( 'Welcome to Awesome Support! This setup wizard will help you to quickly configure your new support system so that you can start processing customer requests right away.  So lets get started with our first question!', 'awesome-support' )
		);		
		?>
		<form method="post">			
			<p><b><?php _e( 'Would you like to turn on support for multiple products?', 'awesome-support' );?> </b></p>
			<p><?php _e( 'If you only offer support for one product you do not need to turn on multi-product support. But if you offer support for multiple products then you should respond YES to this question.', 'awesome-support' );?></p>
			<p><?php _e( 'Note: You can change your mind later by going to the TICKETS->SETTINGS->PRODUCTS MANAGEMENT tab.', 'awesome-support' );?></p>			
			<label for="product_type_yes">Yes</label>
			<input type="radio" name="product_type" id='product_type_yes' value="yes" checked />
			<label for="product_type_no">No</label>
			<input type="radio" name="product_type" id='product_type_no' value="no"/>
			<input type="submit" name="save_step" value="Continue">
			<?php wp_nonce_field( 'as-setup' ); ?>
		</form>
		<?php
	}

	/**
	 * Awesome Support Multiple or single Product setup on save
	 */
	public function as_product_setup_setup_save(){
		check_admin_referer( 'as-setup' );
		$product_type = (isset( $_POST['product_type'] ) )? sanitize_text_field( $_POST['product_type'] ): '';

		// If the user needs multiple products we need to update the plugin options
		$options = maybe_unserialize( get_option( 'wpas_options' ) );
		// If multiple product is selected, make product selection multiple.
		if( !empty( $product_type ) && 'yes' === $product_type ){
			$options['support_products'] = '1';
		} else{
			$options['support_products'] = '0';
		}
		update_option( 'wpas_options', serialize( $options ) );
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
	}

	/**
	 * Awesome Support submit ticket page setup view. 
	 */
	public function as_setup_submit_ticket_page(){
		?>
		<form method="post">
			<p><b><?php _e( 'Which menu would you like to add the SUBMIT TICKET page to?', 'awesome-support' );?> </b></p>
			<p><?php _e( 'We have created a new page that users can access to submit tickets to your new support system.  However, the page first needs to be added to one of your menus so that the user can easily access it.', 'awesome-support' );?> </p>
			<p><?php _e( 'Note: If you change your mind later you can remove the page from your menu or add it to a new menu via APPEARANCE->MENUS.', 'awesome-support' );?></p>
			<?php 
			$menu_lists = wp_get_nav_menus();
			if( !empty( $menu_lists )){
				echo '<select name="wpas_ticket_submit_manu">';
				foreach ($menu_lists as $key => $menu ) {
					echo '<option value="'.$menu->term_id.'">' . $menu->name . '</option>';
				}
				echo '<select>';
				echo '<input type="submit" name="save_step" value="Continue">';
				wp_nonce_field( 'as-setup' );
			} else{
				echo __( 'It looks like you have a brand new install of WordPress without any menus.  So please setup at least one menu first. Click <a href="'. admin_url( 'nav-menus.php').'" class="contrast-link">here</a> to setup your first menu.', 'awesome-support' );
			}
			?>
		</form>
		<?php
	}

	/**
	 * Awesome Support submit ticket page setup on save.
	 */
	public function as_setup_submit_ticket_page_save(){
		check_admin_referer( 'as-setup' );
		$ticket_submit = wpas_get_option( 'ticket_submit' );
		$wpas_ticket_submit_manu = (isset( $_POST['wpas_ticket_submit_manu'] ) && !empty( $_POST['wpas_ticket_submit_manu'] ) )? intval( $_POST['wpas_ticket_submit_manu'] ): 0;
		if( !empty( $ticket_submit ) && !is_array( $ticket_submit ) ){
		    wp_update_nav_menu_item( $wpas_ticket_submit_manu , 0, array(
			    	'menu-item-db-id' => $ticket_submit,
			    	'menu-item-object-id' => $ticket_submit,
			    	'menu-item-object' => 'page',
			        'menu-item-title' =>  wp_strip_all_tags( __( 'Submit Ticket', 'awesome-support' ) ),
			        'menu-item-status' => 'publish',
			        'menu-item-type' => 'post_type'
		    	)
			);
		}
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
	}

	/**
	 * Awesome Support my tickets page setup view. 
	 */
	public function as_setup_my_ticket_page(){
		?>
		<form method="post">
			<p><b><?php _e( 'Which menu would you like to add the MY TICKETS page to?', 'awesome-support' );?> </b></p>
			<p><?php _e( 'We have created a new page that users can access to view their existing tickets.  This step allows you to add that page to one of your existing menus so users can easily access it.', 'awesome-support' );?></p>
			<p><?php _e( 'Note: If you change your mind later you can remove the page from your menu or add it to a new menu via APPEARANCE->MENUS.', 'awesome-support' );?></p>
			<?php 
			$menu_lists = wp_get_nav_menus();
			echo '<select name="wpas_ticket_list_menu">';
			foreach ($menu_lists as $key => $menu ) {
				echo '<option value="'.$menu->term_id.'">' . $menu->name . '</option>';
			}
			echo '<select>';
			?>
			<input type="submit" name="save_step" value="Continue">
			<?php wp_nonce_field( 'as-setup' ); ?>
		</form>
		<?php
	}

	/**
	 * Awesome Support my ticket page setup on save.
	 */
	public function as_setup_my_ticket_page_save(){
		check_admin_referer( 'as-setup' );
		$ticket_list = wpas_get_option( 'ticket_list' );
		$wpas_ticket_list_menu = (isset( $_POST['wpas_ticket_list_menu'] ) && !empty( $_POST['wpas_ticket_list_menu'] ) )? intval( $_POST['wpas_ticket_list_menu'] ): 0;
		if( !empty( $ticket_list ) && !is_array( $ticket_list ) ){
		    wp_update_nav_menu_item( $wpas_ticket_list_menu, 0, array(
			    	'menu-item-db-id' => $ticket_list,
			    	'menu-item-object-id' => $ticket_list,
			    	'menu-item-object' => 'page',
			        'menu-item-title' =>  wp_strip_all_tags( __( 'My Tickets', 'awesome-support' ) ),
			        'menu-item-status' => 'publish',
			        'menu-item-type' => 'post_type'
		    	)
			);
		}
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
	}

		/**
	 * Awesome Support priorities setup view. 
	 */
	public function as_setup_priorities(){
		$support_priority = wpas_get_option( 'support_priority' );
		?>
		<form method="post">
			<p><b><?php _e( 'Would you like to use the priority field in your tickets?', 'awesome-support' );?> </b></p>
			<p><?php _e( 'Turn this option on if you would like to assign priorities to your tickets.', 'awesome-support' );?> </p>
			<p><?php _e( 'After you have finished with the wizard you can configure your priority levels under TICKETS->PRIORITIES.', 'awesome-support' );?> </p>
			<p><?php _e( 'You can also tweak how priorities work by changing settings under the TICKETS->SETTINGS->GENERAL tab.', 'awesome-support' );?> </p>
			<label for='property_field_yes'>Yes</label>
			<input type="radio" name="property_field" id='property_field_yes' value="yes" checked />
			<label for='property_field_no'>No</label>
			<input type="radio" name="property_field" id='property_field_no' value="no"/>
			<input type="submit" name="save_step" value="Continue">
			<?php wp_nonce_field( 'as-setup' ); ?>
		</form>
		<?php
	}

	/**
	 * Awesome Support priorities setup on save.
	 */
	public function as_setup_priorities_save(){
		check_admin_referer( 'as-setup' );
		$property_field = (isset( $_POST['property_field'] ) )? sanitize_text_field( $_POST['property_field'] ): '';
		$options = unserialize( get_option( 'wpas_options', array() ) );
		if( !empty( $property_field ) && 'yes' === $property_field ){
			$options['support_priority'] = '1';
		} else{
			$options['support_priority'] = 0;
		}
		update_option( 'wpas_options', serialize( $options ) );
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
	}

		/**
	 * Awesome Support departments setup view. 
	 */
	public function as_setup_departments(){
		$departments = wpas_get_option( 'departments' );
		?>
		<form method="post">
			<p><b><?php _e( 'Do you want to enable Departments?', 'awesome-support' );?> </b></p>
			<p><?php _e( 'Turn this option on if you would like to assign departments to your tickets.', 'awesome-support' );?> </p>
			<p><?php _e( 'Once enabled, you can configure your list of departments by going to TICKETS->DEPARTMENTS.', 'awesome-support' );?> </p>
			<p><?php _e( 'You can turn this off later if you change your mind by going to the TICKETS->SETTINGS->GENERAL tab.', 'awesome-support' );?> </p>			
			<label for='departments_field_yes'>Yes</label>
			<input type="radio" name="departments_field" id='departments_field_yes' value="yes" checked />
			<label for='departments_field_no'>No</label>
			<input type="radio" name="departments_field" id='departments_field_no' value="no"/>
			<input type="submit" name="save_step" value="Continue">
			<?php wp_nonce_field( 'as-setup' ); ?>
		</form>
		<?php
	}

	/**
	 * Awesome Support departments setup on save.
	 */
	public function as_setup_departments_save(){
		check_admin_referer( 'as-setup' );
		$departments_field = (isset( $_POST['departments_field'] ) )? sanitize_text_field( $_POST['departments_field'] ): '';
		$options = unserialize( get_option( 'wpas_options', array() ) );
		if( !empty( $departments_field ) && 'yes' === $departments_field ){
			$options['departments'] = '1';
		} else{
			$options['departments'] = '0';
		}
		update_option( 'wpas_options', serialize( $options ) );
		// Don't show setup wizard link on plug-in activation.
		update_option('wpas_plugin_setup', 'done');
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
	}

	/**
	 * Lets Go page view for think you message and all.
	 */
	public function as_setup_lets_go(){?>
		<form method="post">
			<p><b><?php _e( "Your new support system is all set up and ready to go!", "awesome-support" ); ?></b></p>
			<p><?php _e( "If your menus are active in your theme your users will now able to register for an account and submit tickets.", "awesome-support" ); ?></p>
			<p><b><?php _e( "Do you have existing users in your WordPress System?", "awesome-support" ); ?></b></p>
			<p><?php
			echo sprintf( __( 'If so, you will want to read <b><u><a %s>this article</a></b></u> on our website.', 'awesome-support' ), 'href="https://getawesomesupport.com/documentation/awesome-support/admin-handling-existing-users-after-installation/" target="_blank" ' );
			?></p>
			<p><b><?php _e( "Where are my support tickets?", "awesome-support" ); ?></b></p>
			<p><?php _e( "You can now access your support tickets and other support options under the new TICKETS menu option.", "awesome-support" ); ?></p>
			<input type="submit" name="save_step" value="Let's Go">
			<?php wp_nonce_field( 'as-setup' ); ?>
		</form>
		<?php
	}

	/**
	 * Lets Go button click
	 */
	public function as_setup_lets_go_save(){
		wp_redirect( add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-about' ), admin_url( 'edit.php' ) ) );
		exit;
	}

	/**
	 * Get the URL for the next step's screen.
	 *
	 * @param string $step  slug (default: current step).
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys, true );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ], remove_query_arg( 'activate_error' ) );
	}

}
new AS_Admin_Setup_Wizard();
