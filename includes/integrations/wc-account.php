<?php
/**
 * Adds simple integration with the WC MyAccount page
 *
 * @author    Awesome Support <contact@awesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2018 AwesomeSupport
 *
 */

 class WPAS_WC_MyAccount {

	/**
	 * Custom endpoint name for the ticket list
	 *
	 * @var string
	 */
	public $endpoint_ticketlist ;
	
	/**
	 * Custom endpoint name for the open ticket page
	 *
	 * @var string
	 */
	public $endpoint_openticket ;
	
	
	/**
	 * Plugin actions.
	 */
	public function __construct() {	
	
		// Setup instance variables;
		$this->setup();
		
		// Actions used to insert a new endpoint in the WordPress.
		add_action( 'init', array( $this, 'add_endpoints' ) );

		// Insert new tab/page into the My Account page.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'new_menu_items' ) );	
	
		
	}
	
	/**
	 * Setup instance variables for the class.
	 *
	 */	
	public function setup() {
		
		$this->endpoint_ticketlist = $this->get_endpoint_name_for_ticket_list();
		$this->endpoint_openticket = $this->get_endpoint_name_for_openticket_page();
		
	}

	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * Filter hook: woocommerce_account_menu_items
	 *
	 * @param array $items
	 * @return array
	 */
	public function new_menu_items( $items ) {
		
		// Remove the logout menu item (we'll add it back in later!)
		$logout = isset($items['customer-logout']) ? $items['customer-logout'] : false;
		if ($logout) {
			unset( $items['customer-logout'] );
		}		

		// Get the labels for the new WooCommerce MY ACCOUNT menu items...
		// NOTE: The if-then-else format used below is very deliberate in order to support translation in a multi-language website!
		// If the admin deliberately left the fields blank then they can easily translate the default text into multiple langauges instead of being stuck with one language in settings.
		$my_tickets_label = empty( wpas_get_option( 'simple_wc_my_tickets_label' ) ) ? __( 'My tickets', 'awesome-support' ) :  wpas_get_option( 'simple_wc_my_tickets_label' ) ;
		$submit_ticket_label = empty( wpas_get_option( 'simple_wc_submit_ticket_label') ) ? __( 'Open a support ticket', 'awesome-support' ) : wpas_get_option( 'simple_wc_submit_ticket_label') ;
		
		// Insert the new menu items (endpoints)
		$items[ $this->endpoint_openticket ] = apply_filters( 'wpas_wc_account_tab_name_openticket', $submit_ticket_label );		
		$items[ $this->endpoint_ticketlist ] = apply_filters( 'wpas_wc_account_tab_name_ticketlist', $my_tickets_label );
		
		// Insert back the logout item.
		if ($logout) {
			$items['customer-logout'] = $logout;
		}

		return $items;
	}
	
	
	/**
	 * Get the slug that will be used to define the endpoint for the ticketlist
	 *
	 * @return string The endpoint
	 */	
	public function get_endpoint_name_for_ticket_list() {
		
		$slug = false ;  // return value
		
		$page_id = wpas_get_option('ticket_list') ;  // get the id of the ticket_list page from settings...
		
		if ( ! empty( $page_id ) ) {
			
			$page = get_post( $page_id ) ;
			
			if ( ! empty( $page ) ) {
				$slug = $page->post_name;
			}

		}
		
		return $slug ;
		
	}
	
	/**
	 * Get the slug that will be used to define the endpoint for the open ticket page
	 *
	 * @return string The endpoint
	 */	
	public function get_endpoint_name_for_openticket_page() {
		
		$slug = false ;  // return value
		
		$page_ids = wpas_get_option('ticket_submit') ;  // get the ids of the ticket pages from settings...
		
		if ( ! empty( $page_ids ) ) {
			
			$page_id = $page_ids[0];  
			
			$page = get_post( $page_id ) ;
			
			if ( ! empty( $page ) ) {
				$slug = $page->post_name;
			}

		}
		
		return $slug ;
		
	}	
	
	/**
	 * Register new endpoints to use inside My Account page.
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 *
	 * Filter Hook: init
	 *
	 */
	public function add_endpoints() {
		// We have this function here to be used later to add rewritable endpoints if necessary.
		// For now we don't have to do anything with it.
		
		//add_rewrite_endpoint( $this->endpoint_ticketlist, EP_ROOT | EP_PAGES );
		//add_rewrite_endpoint( $this->endpoint_openticket, EP_ROOT | EP_PAGES );		
	}
	 
 }
 
 // Instantiate the class here...
 if ( true === boolval( wpas_get_option( 'enable_simple_wc', false ) ) ) {	 
	$wcmyaccount = new WPAS_WC_MyAccount();	 
 }