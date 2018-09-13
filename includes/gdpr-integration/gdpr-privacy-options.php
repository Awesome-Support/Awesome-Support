<?php

/**
 * Awesome Support Privacy Option.
 *
 * @package   Awesome_Support
 * @author    Naveen Giri <1naveengiri>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 */
class WPAS_Privacy_Option {
	/**
	 * Instance of this class.
	 *
	 * @since     5.2.0
	 * @var      object
	 */
	protected static $instance = null;
	/**
	 * Store the potential error messages.
	 */
	protected $error_message;

	public function __construct() {
		add_filter( 'wpas_frontend_add_nav_buttons', array( $this, 'frontend_privacy_add_nav_buttons' ) );
		add_filter( 'wp_footer', array( $this, 'print_privacy_popup_temp' ), 101 );
		add_action( 'wp_ajax_wpas_gdpr_open_ticket', array( $this, 'wpas_gdpr_open_ticket' ) );
		add_action( 'wp_ajax_nopriv_wpas_gdpr_open_ticket', array( $this, 'wpas_gdpr_open_ticket' ) );

		/**
		 * Opt in processing
		 */
		add_action( 'wp_ajax_wpas_gdpr_user_opt_in', array( $this, 'wpas_gdpr_user_opt_in' ) );
		add_action( 'wp_ajax_nopriv_wpas_gdpr_user_opt_in', array( $this, 'wpas_gdpr_user_opt_in' ) );

		/**
		 * Opt out processing
		 */
		add_action( 'wp_ajax_wpas_gdpr_user_opt_out', array( $this, 'wpas_gdpr_user_opt_out' ) );
		add_action( 'wp_ajax_nopriv_wpas_gdpr_user_opt_out', array( $this, 'wpas_gdpr_user_opt_out' ) );
		
		add_action( 'wpas_system_tools_after', array( $this, 'wpas_system_tools_after_gdpr_callback' ) );
		
		add_filter( 'wpas_show_done_tool_message', array( $this, 'wpas_show_done_tool_message_gdpr_callback' ), 10, 2 );

		add_filter( 'execute_additional_tools', array( $this, 'execute_additional_tools_gdpr_callback' ), 10, 1 );
		
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'wp_register_asdata_personal_data_eraser' ) );

		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'wp_privacy_personal_asdata_exporters' ), 10, 1 );

		// Schedule cleanup of older tickets
		add_action( 'wp', array( $this, 'tickets_cleanup_schedule' ) );
		
		// Cleanup action
		add_action( 'wpas_tickets_cleanup_action', array( $this, 'as_tickets_cleanup_action_callback' ) );

		add_filter( 'cron_schedules', array( $this, 'gdpr_cron_job_schedule' ) ); 
	}

	/**
	 * Add or Remove User consent based on action call.
	 */
	function execute_additional_tools_gdpr_callback( $tool ){

		if ( ! isset( $tool ) || ! isset( $_GET['_nonce'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_GET['_nonce'], 'system_tool' ) ) {
			return false;
		}
		$authors = array();
		if( !empty( $tool ) ){
			// WP_User_Query arguments
			$args = array (
			    'order' => 'ASC',
			    'orderby' => 'display_name',
			);
			// Create the WP_User_Query object
			$wp_user_query = new WP_User_Query($args);

			// Get the results
			$authors = $wp_user_query->get_results();
		}
		switch ( sanitize_text_field( $tool ) ) {

			case 'remove_all_user_consent':

				// Check for results
				if (!empty($authors)) {

				    // loop through each author
				    foreach ($authors as $author) {

				        // get all the user's data
				        if( isset( $author->ID ) && !empty( $author->ID )){
				        	delete_user_option( $author->ID, 'wpas_consent_tracking' );
				        }
				    }
				}
				break;
				
			case 'add_user_consent':

				$_status = (isset( $_GET['_status'] ) && !empty( isset( $_GET['_status'] ) ))? sanitize_text_field( $_GET['_status'] ): '';
				$consent = ( isset( $_GET['_consent'] ) && !empty( isset( $_GET['_consent'] ) ) )? sanitize_text_field( $_GET['_consent'] ): '';
				if( empty( $_status ) || empty( $consent ) ){
					return false;
				}
				// Check for results
				if (!empty($authors)) {

				    // loop through each author
				    foreach ($authors as $author) {
				    	$opt_type = '';
				        // get all the user's data
				        if( isset( $author->ID ) && !empty( $author->ID )){

							$status 	= ( 'opt-in' === $_status )? true : false;
							$opt_in 	= ! empty ( $status ) ? strtotime( 'NOW' ) : "";
							$opt_out 	= empty ( $opt_in ) ? strtotime( 'NOW' ) : "";
							$opt_type = ( isset( $opt_in ) && !empty( $opt_in ))? 'in' : 'out';
							$args = array( 
								'item' 		=> wpas_get_option( $consent, false ),
								'status' 	=> $status,
								'opt_in' 	=> $opt_in,
								'opt_out' 	=> $opt_out,
								'is_tor'	=> false
							);

							if( 'terms_conditions' === $consent ){
								$args['is_tor'] = true;
							}

							$user_consent = get_user_option( 'wpas_consent_tracking', 
								$author->ID );
							if( !empty( $user_consent )){
								$found_key = array_search( $args['item'], array_column( $user_consent, 'item' ) );	
								// If GDPR option not already enabled, then add it.
								if( false === $found_key ){
									wpas_track_consent( $args , $author->ID, $opt_type );									
								}
								
							} else{

								wpas_track_consent( $args , $author->ID, $opt_type );

							}
				        }
				    }
				}
				break;

		}

	}

	/**
	 * GDPR Cron job schedule 
	 * 
	 * @param  array $schedules Cron schedules 
	 * 
	 * @return array $schedules Cron schedules with GDPR cron job schedule included.
	 */
	function gdpr_cron_job_schedule( $schedules ) {
		$trigger_time = wpas_get_option( 'anonymize_cronjob_trigger_time', '' );
		if( !empty( $trigger_time )){
			$trigger_time = intval($trigger_time);
			$schedules['min_'. $trigger_time ] = array(
				'interval' => ($trigger_time * 60),
				'display' => __('GDPR Ticket cleanup cron', 'awesome-support' )
			);
		}
		return $schedules;		
	}

	/**
	 * Schedule cleanup of older tickets
	 * 
	 * @since  5.2.0
	 *
	 * @return void
	 */
	function tickets_cleanup_schedule(){
		$anonymize_cron_job = wpas_get_option( 'anonymize_cron_job', '' );
		if( ! empty( $anonymize_cron_job ) ){
			if ( ! wp_next_scheduled( 'wpas_tickets_cleanup_action' ) ) {
				$trigger_time = wpas_get_option( 'anonymize_cronjob_trigger_time', '' );
				if( !empty( $trigger_time )){
					$trigger_time = intval($trigger_time);
					wp_schedule_event( time(), 'min_' . $trigger_time, 'wpas_tickets_cleanup_action');
				}
			}
		} else{
			wp_clear_scheduled_hook('wpas_tickets_cleanup_action'); 
		}
	}
	/**
	 * Anonymize ticket if delete ticket option is not checked.
	 * @return [type] [description]
	 */
	function as_tickets_cleanup_action_callback(){
		$ticket_age = wpas_get_option( 'anonymize_cronjob_max_age', '' );
		$ticket_data = array();
		if( !empty( $ticket_age )){
			$cronjob_max_age = intval($ticket_age);
			$args = array(
				'post_type'      => array( 'ticket' ),
				'post_status'    => array_keys( wpas_get_post_status() ),
				'posts_per_page' => 50,
				'meta_query' => array(
			        array(
			            'key'   => 'is_anonymize',
			            'compare' => 'NOT EXISTS',
			        )
			    ),
			    'date_query' => array(
					'before' => date('Y-m-d', strtotime('-' . $cronjob_max_age . ' days') )
				) 
			);

			$closed_tickets = boolval( wpas_get_option( 'closed_tickets_anonmyize', true ) );
			$open_tickets = boolval( wpas_get_option( 'open_tickets_anonmyize', false ) );
			
			// Closed tickets only?
			if( $closed_tickets && ! $open_tickets )  {
				$args['meta_query'][] = array(
					'key'   => '_wpas_status',
					'value' => 'closed',
					'compare' => '=',
				);
			}
			
			// Open tickets only?
			if( ! $closed_tickets && $open_tickets )  {
				$args['meta_query'][] = array(
					'key'   => '_wpas_status',
					'value' => 'open',
					'compare' => '=',
				);
			}
			
			$ticket_data = get_posts( $args );
			
		}
		
		if( !empty( $ticket_data ) ){
			$author_array = array();
			foreach ( $ticket_data as $key => $ticket_value ) {
				if( array_key_exists( $ticket_value->post_author ,$author_array ) ){
					$author_array[ $ticket_value->post_author ][] = $ticket_value->ID;
				} else{
					$author_array[ $ticket_value->post_author ] = array( $ticket_value->ID );
				}
			}

			if( !empty( $author_array )){
				foreach ( $author_array as $author_id => $author_tickets ) {
					/**
					 * 
					 ** 1. create an anonymous user if it is not created for author yet. This maintain a single and unique anonymous author per support user for this run only.
					 *
					 ** 2. Loop author tickets and set them anonymous and set meta key is_anonymous = true, to exclude already anonymized tickets.
					 *
					 */
					$related_author_id = $this->as_create_anonymous_user( $author_id );

					$delete_existing_data = wpas_get_option( 'anonymize_cronjob_delete_tickets', false );
					// Assign Author tickets to anonymous user. 
					// also set is_anonymize key in ticket meta.
					if( !empty( $author_tickets )){
						foreach ( $author_tickets as $key => $ticket_id ) {
							if( !$delete_existing_data && !empty( $related_author_id ) ){
								
								//2a. Update ticket data and set author as Anonymous user
								$arg = array(
								    'ID' => $ticket_id,
								    'post_author' => $related_author_id,
								);
								wp_update_post( $arg );
								update_post_meta( $ticket_id, 'is_anonymize', true );
								$messages = sprintf( __( 'Anonymize Awesome Support Ticket #: %s', 'awesome-support' ), (string) $ticket_id ) ;
								wpas_write_log( 'anonymize_ticket', $messages );

								//2b. Now handle the replies
								$args = array(
									'post_parent'           => $ticket_id,
									'author' 			 	=> $author_id,
									'post_type'             => apply_filters( 'wpas_replies_post_type', array(
										'ticket_history',
										'ticket_reply',
										'ticket_log'
									) ),
									'post_status'            => 'any',
									'posts_per_page'         => - 1,
									'no_found_rows'          => true,
									'cache_results'          => false,
									'update_post_term_cache' => false,
									'update_post_meta_cache' => false,
								);

								$posts = new WP_Query( $args );
								foreach ( $posts->posts as $id => $post ) {

									do_action( 'wpas_before_anonymize_dependency', $post->ID, $post );
									$arg = array(
									    'ID' => $post->ID,
									    'post_author' => $related_author_id,
									);
									wp_update_post( $arg );
									do_action( 'wpas_after_anonymize_dependency', $post->ID, $post );
									$messages = sprintf( __( 'Anonymize Reply on Awesome Support Ticket #: %s. The reply id is: %s', 'awesome-support' ), (string) $ticket_id, (string) $post->ID ) ;
									wpas_write_log( 'anonymize_ticket', $messages );
								}
							} else{
								if ( wp_delete_post( $ticket_id, true ) ) {
									$items_removed = true;
									$messages = sprintf( __( 'Removed Awesome Support Ticket #: %s', 'awesome-support' ), (string) $ticket_id ) ;
									wpas_write_log( 'anonymize_ticket_delete', $messages );
								} 
							}
						}
					}
				}
			}
		}

	}

	/**
	 * Update data on clean up tool click.
	 */
	function wpas_show_done_tool_message_gdpr_callback( $message, $status ){
		switch( $status ) {

			case 'remove_all_user_consent':
				$message = __( 'User Consents cleared', 'awesome-support' );
				break;

			case 'add_user_consent':
				$message = __( 'Added User Consents', 'awesome-support' );
				break;
		}
		return $message;
	}

	/**
	 * GDPR add consent html in cleanup section.
	 */
	function wpas_system_tools_after_gdpr_callback(){
		?>
		<p><h3><?php _e( 'GDPR/Privacy', 'awesome-support' ); ?></h3></p>
		<table class="widefat wpas-system-tools-table" id="wpas-system-tools-gdpr">
			<thead>
				<tr>
					<th data-override="key" class="row-title"><?php _e( 'GDPR Consent Bulk Action', 'awesome-support' ); ?></th>
					<th data-override="value"></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="row-title"><label for="tablecell"><?php _e( 'GDPR Consent', 'awesome-support' ); ?></label></td>
					<td>
						<a href="<?php echo wpas_tool_link( 'remove_all_user_consent' ); ?>" class="button-secondary"><?php _e( 'Remove', 'awesome-support' ); ?></a>
						<span class="wpas-system-tools-desc"><?php _e( 'Clear User Consent data for all Awesome support Users', 'awesome-support' ); ?></span>
					</td>
				</tr>
				<?php 
					$terms = wpas_get_option( 'terms_conditions', '' );
					$gdpr_short_desc_01 = wpas_get_option( 'gdpr_notice_short_desc_01', '' );
					$gdpr_short_desc_02 = wpas_get_option( 'gdpr_notice_short_desc_02', '' );
					$gdpr_short_desc_03 = wpas_get_option( 'gdpr_notice_short_desc_03', '' );

					$consent_array = array(
						'terms_conditions',
						'gdpr_notice_short_desc_01',
						'gdpr_notice_short_desc_02',
						'gdpr_notice_short_desc_03'
					);
					$consent_array = apply_filters( 'wpas_gdpr_consent_list_array',$consent_array );
					if( !empty( $consent_array ) ){
						foreach ( $consent_array as $key => $consent ) {
							$consent_name = wpas_get_option( $consent, '' );
							if( 'terms_conditions' === $consent ){
								$consent_name = 'Terms';
							}
							if( !empty( $consent_name ) ){
								?>
								<tr>
									<td class="row-title"><label for="tablecell"><?php _e( $consent_name , 'awesome-support' ); ?></label></td>
									<td>
										<?php 
											$opt_in = array(
												'_consent' => $consent,
												'_status' => 'opt-in'
											);
										?>
										<a href="<?php echo wpas_tool_link( 'add_user_consent', $opt_in ); ?>" class="button-secondary"><?php _e( 'OPT-IN', 'awesome-support' ); ?></a>
										<?php 
										$opt_out = array(
											'_consent' => $consent,
											'_status' => 'opt-out'
										);
										?>
										<a href="<?php echo wpas_tool_link( 'add_user_consent', $opt_out ); ?>" class="button-secondary"><?php _e( 'OPT-OUT', 'awesome-support' ); ?></a>
										<span class="wpas-system-tools-desc"><?php _e( 'Set ' . $consent_name . ' Consent status for all Awesome support Users', 'awesome-support' ); ?></span>
									</td>
								</tr>
								<?php 
							}
						}
					}
				?>
			</tbody>
		</table>
		<?php 
	}


	/**
	 * Registers the personal data eraser for Awesome Support data.
	 *
	 * @since  5.2.0
	 *
	 * @param  array $erasers An array of personal data erasers.
	 * @return array $erasers An array of personal data erasers.
	 */
	public function wp_register_asdata_personal_data_eraser( $erasers ){
		$erasers['awesome-support-data'] = array(
			'eraser_friendly_name' => __( 'Awesome Support Data' ),
			'callback'             => array( $this, 'wpas_users_personal_data_eraser' ),
		);

		return $erasers;
	}

	/**
	 * Erases Awesome Support related personal data associated with an email address.
	 *
	 * @since 5.2.0
	 *
	 * @param  string $email_address The As Users email address.
	 * @param  int    $page          Ticket page.
	 * @return array
	 */
	public function wpas_users_personal_data_eraser( $email_address, $page = 1 ){
		global $wpdb;

		// Evaluate whether conditions exist to allow deletion to proceed		
		$empty_return = array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
			
		if ( empty( $email_address ) ) {
			return $empty_return;
		}
		
		/**
		* Filter for other add-ons to hook into to prevent personal ticket data from being erased.
		*
		* For example, time tracking might have its DO NOT ALLOW option set to delete so if it 
		* hooks into this filter it can return FALSE to prevent further data deletion.
		*
		*/
		if ( ! apply_filters( 'wpas_allow_personal_data_eraser', true ) ) {
			return $empty_return;
		}

		/**
		* Make sure the email address is valid!
		*/
		$author = get_user_by( 'email', $email_address );		
		if ( empty( $author ) || true === is_wp_error( $author ) ) {
			return $empty_return;
		}
		
		/* All pre-conditions good, so ok to proceed */
		$number = apply_filters( 'wpas_personal_data_eraser_max_ticket_count', 500 ); // Limit us to 500 tickets at a time to avoid timing out.
		$page           = (int) $page;
		$items_removed  = false;
		$items_retained = false;
		$args = array(
			'post_type'      => array( 'ticket' ),
			'author'         => $author->ID,
			'post_status'    => array_keys( wpas_get_post_status() ),
			'posts_per_page' => $number,
			'paged'          => $page
		);

		$anonymize_existing_data = wpas_get_option( 'anonymize_existing_data' );
		if( $anonymize_existing_data ){
			$user_id = $this->as_create_anonymous_user( $author->ID );
		}
		/**
		 * Delete ticket data belongs to the mention email id.
		 */
		$ticket_data  = get_posts( $args );
		$messages  = array();
		if( !empty( $ticket_data )){
			foreach ( $ticket_data as $ticket ) {
				if( isset( $ticket->ID ) && !empty( $ticket->ID )){
					$ticket_id = (int) $ticket->ID;
					if ( $ticket_id ) {
						
						/* Apply a filter check, passing an array object so we can get messages back from the filter */
						$wpas_pe_msgs['ok_to_erase'] = true ;
						$wpas_pe_msgs['messages'] = array() ;						
						$wpas_pe_msgs = apply_filters( 'wpas_before_delete_ticket_via_personal_eraser', $wpas_pe_msgs, $ticket_id );
						
						/* Proceed with attempting to delete the ticket if filter returned ok */	
						if ( true === $wpas_pe_msgs['ok_to_erase'] ) {
							/**
							 * if anonymize data instead of delete is checked 
							 * 		dont delete 
							 * else 
							 * 		delete data
							 */
							
							if( $anonymize_existing_data ){
								//2. Update ticket data and set author as Anonymous user
								$arg = array(
								    'ID' => $ticket_id,
								    'post_author' => $user_id,
								);
								wp_update_post( $arg );
								$args = array(
									'post_parent'            => $ticket_id,
									'author'				 => $author->ID,
									'post_type'              => apply_filters( 'wpas_replies_post_type', array(
										'ticket_history',
										'ticket_reply',
										'ticket_log'
									) ),
									'post_status'            => 'any',
									'posts_per_page'         => - 1,
									'no_found_rows'          => true,
									'cache_results'          => false,
									'update_post_term_cache' => false,
									'update_post_meta_cache' => false,
								);

								$posts = new WP_Query( $args );
								foreach ( $posts->posts as $id => $post ) {

									do_action( 'wpas_before_anonymize_dependency', $post->ID, $post );
									$arg = array(
									    'ID' => $post->ID,
									    'post_author' => $user_id,
									);
									wp_update_post( $arg );

									do_action( 'wpas_after_anonymize_dependency', $post->ID, $post );
								}
								$messages[] = sprintf( __( 'Anonymize Awesome Support Ticket #: %s', 'awesome-support' ), (string) $ticket_id ) ;
							} else{
								if ( wp_delete_post( $ticket_id, true ) ) {
									$items_removed = true;
									$messages[] = sprintf( __( 'Removed Awesome Support Ticket #: %s', 'awesome-support' ), (string) $ticket_id ) ;
								} 
							}
						} else {
							$messages[] = sprintf( __( 'Awesome Support Ticket #: %s was NOT removed because the <i>wpas_before_delete_ticket_via_personal_eraser</i> filter check returned false. This means an Awesome Support add-on prevented this ticket from being deleted in order to preserve data integrity.', 'awesome-support' ), (string) $ticket_id ) ;
							$messages = array_merge( $messages, $wpas_pe_msgs['messages'] ) ;
						}
						
					}
				}
			}
		} else{
			$messages[] = __( 'No Awesome Support data was found.', 'awesome-support' );
		}

		$done = count( $ticket_data ) < $number;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}


	/**
	 * create anonymous user.
	 *
	 * @since  5.2.0
	 *
	 * @param  int $author_id The id of the author/user we're creating the anonymous user for.
	 *
	 * @return int
	 */
	public function as_create_anonymous_user( $author_id ){
		
		$uid_method = wpas_get_option( 'anonmyize_user_creation_method', '1');
		
		switch( $uid_method ) {
			
			case '2':
				// 2. Use a one-way hash;
				if ( $author_id ) {
					$hash = wp_hash( $author_id );
				} else {
					$hash = wp_hash( (string) wp_rand( 1, 10000000 ) );
				}
				$user_name = (string) $hash;
				break ;
			
			case '3':
				if ( ! empty( wpas_get_option( 'anonmyize_user_id' ) ) ) {
					$user_obj = get_user_by( 'ID', wpas_get_option( 'anonmyize_user_id' ) );
					if ( $user_obj ) {
						$user_name = $user_obj->user_login;
						break ;
					}
				}
				// note the lack of a break statement here - its deliberate because if nothing is processed here then we drop through to the default below!
			
			default:
				//1. create a anonymous user with username anno-xxxx 
				$random_number = wp_rand( 1, 10000000 );
				$user_name = 'anno-'.$random_number;
				break ;

		}

		return $this->as_create_anonymous_user_by_user_name( $user_name ) ;

	}
	
	/**
	 * create anonymous user by user name
	 *
	 * Accepts a user name and returns the id of the user if they exist or 
	 * the id of a new user that is created with that user name.
	 *
	 * @since  5.2.0
	 *
	 * @param  string $user_name The id of the author/user we're creating the anonymous user for.
	 *
	 * @return int
	 */
	public function as_create_anonymous_user_by_user_name( $user_name ){
		
		$user_id = username_exists( $user_name );
		$url = get_site_url();
		$urlobj = parse_url($url);
		$site_name = 'domain.com';
		$domain = ($urlobj['host'])? $urlobj['host']: '';
		if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
			$site_name = $regs['domain'];
		}

		$user_email = $user_name.'@'.$site_name;
		if ( !$user_id and email_exists($user_email) == false ) {
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$userdata = array(
			    'user_login'  => $user_name,
			    'user_email'  => $user_email,
			    'role'        => wpas_get_option( 'new_user_role', 'wpas_user' ),
			    'user_pass'   => $random_password,
			);
			$user_id = wp_insert_user( $userdata ) ;
			if( !empty( $user_id ) ){
				update_user_option( $user_id, 'is_anonymous', true );
			}
		}
		
		return $user_id;		
		
	}
	
	/**
	 * Registers a personal data exporter for Awesome Support
	 *
	 * @since  5.2.0
	 *
	 * @param  array $exporters An array of personal data exporters.
	 * @return array $exporters An array of personal data exporters.
	 */	
	public function wp_privacy_personal_asdata_exporters( $exporters ){
		$exporters['awesome-support-data-test'] = array(
			'exporter_friendly_name' => __( 'Awesome Support Data' ),
			'callback'               => array( $this, 'wpas_users_personal_data_exporter' ),
		);

		return $exporters;
	}


	/**
	 * Finds and exports personal Awesome Support data associated with an email address from the post table.
	 *
	 * @since 5.2.0
	 *
	 * @param string $email_address The comment author email address.
	 * @param int    $page          Comment page.
	 * @return array $return An array of personal data.
	 */
	public function wpas_users_personal_data_exporter( $email_address, $page = 1 ){
		
		$number = 500;
		$page   = (int) $page;
		$data_to_export = array();
		$user_data_to_export = array();
		$done = false;
		$author = get_user_by( 'email', $email_address );
		if ( ! $author ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}
		$instance = WPAS_GDPR_User_Profile::get_instance();
		if( isset( $author->ID ) && !empty( $author->ID )){
			$user_tickets_data = $instance->wpas_gdpr_ticket_data( $author->ID, $number, $page );
			$user_consent_data = $instance->wpas_gdpr_consent_data( $author->ID );

			if( !empty( $user_tickets_data )){
				$name = '';
				$value = '';
				$item_id = "as-{$user->ID}";
				$data_to_export[] = array(
					'group_id'    => 'awesome-support',
					'group_label' => __( 'Awesome Support', 'awesome-support' ),
					'item_id'     => $item_id,
					'data'        => array(),
				);
 				foreach ( $user_tickets_data as $key2 => $ticket ) {
 					
					foreach ( $ticket as $key => $value ) {
						switch ( $key ) {
							case 'ticket_id':
								$item_id = 'as-ticket-{' . $value . '}';
								$name = __( 'Ticket ID', 'awesome-support' );
							break;
							case 'subject':
								$name = __( 'Ticket Subject', 'awesome-support' );
							break;
							case 'description':
								$name = __( 'Ticket Description', 'awesome-support' );
							break;
							case 'replies':

								if( !empty( $value ) && is_array( $value ) ){
									$reply_count = 0;
									foreach ( $value as $reply_key => $reply_data ) {
										$reply_count ++;
										if( isset( $reply_data['content'] ) && !empty( $reply_data['content'] )){
											$name = __( 'Reply ' . $reply_count . ' Content', 'awesome-support' );
											if ( ! empty( $value ) ) {
												$user_data_to_export[] = array(
													'name'  => $name,
													'value' => $reply_data['content'],
												);
											}
										}
									}
								}
								$value = '';
							break;
							case 'ticket_status':
								$name = __( 'Ticket Status', 'awesome-support' );
							break;
							default:
								$value = '';
							break;

						}	
						if ( ! empty( $value ) ) {
							$user_data_to_export[] = array(
								'name'  => $name,
								'value' => $value,
							);
						}
					}

					$data_to_export[] = array(
						'group_id'    => 'ticket_' . $item_id,
						'group_label' => __( $ticket['subject'], 'awesome-support' ),
						'item_id'     => $item_id,
						'data'        => $user_data_to_export,
					);
					$user_data_to_export = array();
				}
			}
			if( !empty( $user_consent_data )){
				$consent_count = 0;
				foreach ( $user_consent_data as $consent_key => $consent_value ) {
					$consent_count ++;
					if( isset( $consent_value['item'] ) && !empty( $consent_value['item'] ) ){
						$user_data_to_export[] = array(
							'name'  => __( 'Item', 'awesome-support' ),
							'value' => $consent_value['item'],
						);
						if( isset( $consent_value['status'] ) && !empty( $consent_value['status'] ) ){
							$user_data_to_export[] = array(
								'name'  => __( 'Status', 'awesome-support' ),
								'value' => $consent_value['status'],
							);
						}
						if( isset( $consent_value['opt_in'] ) && !empty( $consent_value['opt_in'] ) ){
							$user_data_to_export[] = array(
								'name'  => __( 'Opt In', 'awesome-support' ),
								'value' => $consent_value['opt_in'],
							);
						}
						if( isset( $consent_value['opt_out'] ) && !empty( $consent_value['opt_out'] ) ){
							$user_data_to_export[] = array(
								'name'  => __( 'Opt Out', 'awesome-support' ),
								'value' => $consent_value['opt_out'],
							);
						}
					}
				}
				$data_to_export[] = array(
					'group_id'    => 'ticket_consent_' . $consent_count,
					'group_label' => __( 'Consent Data', 'awesome-support' ),
					'item_id'     => $item_id,
					'data'        => $user_data_to_export,
				);
			}
			$done = count( $user_tickets_data ) < $number;
		}


		$data = apply_filters( 'wpas_users_personal_data_export', $data_to_export, $author->ID );
		
		return array(
			'data' => $data,
			'done' => $done,
		);
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     5.2.0.
	 *
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
	 * Print Template file for privacy popup container.
	 *
	 * @return void
	 */
	public static function print_privacy_popup_temp() {
		if ( wpas_is_front_end_plugin_page() ) { ?>
			<div class="privacy-container-template">
				<div class="entry entry-normal" id="privacy-option-content">
					<div class="wpas-gdpr-loader-background"></div><!-- .wpas-gdpr-loader-background -->
					<a href="#" class="hide-the-content"></a>
					<?php
					$entry_header = wpas_get_option( 'privacy_popup_header', 'Privacy' );
					if ( ! empty( $entry_header ) ) {
						echo '<div class="entry-header">' . wpautop( stripslashes( $entry_header ) ) . '</div>';
					}
					?>
					<div class="entry-content">
						<div class="wpas-gdpr-tab">
							<?php 
								/**
								 * Include file to generate the tabs
								 */
								include_once( WPAS_PATH . '/includes/gdpr-integration/tab-content/gdpr-tabs.php' );
							?>
						</div>

						<div id="add-remove-consent" class="add-remove-consent entry-content-tabs wpas-gdpr-tab-content">
							<?php
								/**
								 * Include tab content for Add/Remove Content data
								 */
								 if ( true === boolval( wpas_get_option( 'privacy_show_consent_tab', true) ) ) {
									include_once( WPAS_PATH . '/includes/gdpr-integration/tab-content/gdpr-add-remove-consent.php' );
								 }
							?>
						</div>
						<div id="delete-existing-data" class="delete-existing-data entry-content-tabs wpas-gdpr-tab-content">
							<?php
								/**
								 * Include tab content for Delete my existing data
								 */
								if ( true === boolval( wpas_get_option( 'privacy_show_delete_data_tab', true) ) ) {								 
									include_once( WPAS_PATH . '/includes/gdpr-integration/tab-content/gdpr-delete-existing-data.php' );
								}
							?>
						</div>
						<div id="export-user-data" class="export-user-data entry-content-tabs wpas-gdpr-tab-content">
							<?php
								/**
								 * Include tab content for Export tickets and user data
								 */
								if ( true === boolval( wpas_get_option( 'privacy_show_export_tab', true) ) ) {								 
									include_once( WPAS_PATH . '/includes/gdpr-integration/tab-content/gdpr-export-user-data.php' );
								}
							?>
						</div>
						<div id="export-existing-data" class="entry-content-tabs wpas-gdpr-tab-content">
							<?php
								/**
								 * Include tab content for Export tickets and user data
								 */
								include_once( WPAS_PATH . '/includes/gdpr-integration/tab-content/gdpr-wpexport-user-data.php' );
							?>
						</div>
					</div>
					<?php
					$entry_footer = wpas_get_option( 'privacy_popup_footer', 'Privacy' );
					if ( ! empty( $entry_footer ) ) {
						echo '<div class="entry-footer">' . wpautop( stripslashes( $entry_footer ) )  . '</div>';
					}
					?>
				</div> <!--  .entry entry-regular -->
			</div> <!--  .privacy-container-template -->
		<?php
		}
	}
	
	/**
	 * Add GDPR privacy options to
	 * * Add/Remove Existing Consent
	 * * Export tickets and user data
	 * * Delete my existing data
	 *
	 * @return void
	 */
	public function frontend_privacy_add_nav_buttons() {
		
		/* Do not render button if option is turned off */
		if ( ! boolval( wpas_get_option( 'privacy_show_button', true) ) ) {
			return ;
		}
		
		/* Option is on so render the button */
		$button_title = wpas_get_option( 'privacy_button_label', 'Privacy' );
		wpas_make_button(
			stripslashes_deep( $button_title ), array(
				'type'  => 'link',
				'link'  => '#',
				'class' => 'wpas-btn wpas-btn-default wpas-link-privacy',
			)
		);
	}

	/**
	 * Ajax based ticket submission
	 * This is only good for 'Official Request: Please Delete My Existing Data ("Right To Be Forgotten")'
	 * ticket from the GDPR popup in 'Delete My Existing Data' tab
	 */
	public function wpas_gdpr_open_ticket() {
		/**
		 * Initialize custom reponse message
		 */
		$response = array(
			'code'    => 403,
			'message' => __( 'Sorry! Something failed', 'awesome-support' ),
		);

		/**
		 * Initiate nonce
		 */
		$nonce = isset( $_POST['data']['nonce'] ) ? $_POST['data']['nonce'] : '';

		/**
		 * Security checking
		 */
		if ( ! empty( $nonce ) && check_ajax_referer( 'wpas-gdpr-nonce', 'security' ) ) {

			/**
			 *  Initiate form data parsing
			 */
			$form_data = array();
			parse_str( $_POST['data']['form-data'], $form_data );

			$subject = isset( $form_data['wpas-gdpr-ded-subject'] ) ? $form_data['wpas-gdpr-ded-subject'] : '';
			$content = isset( $form_data['wpas-gdpr-ded-more-info'] ) && ! empty( $form_data['wpas-gdpr-ded-more-info'] ) ? $form_data['wpas-gdpr-ded-more-info'] : $subject; // Fallback to subject to avoid undefined!
			$request_type = ( isset( $_POST['data']['request_type'] ) && !empty( $_POST['data']['request_type'] ))? sanitize_text_field( $_POST['data']['request_type'] ): '';

			/**
			 * New ticket submission
			 * *
			 * * NOTE: data sanitization is happening on wpas_open_ticket()
			 * * We can skip doing it here
			 */
			$ticket_id = wpas_open_ticket(
				array(
					'title'   => $subject,
					'message' => $content,
					'bypass_pre_checks' => true,					
				)
			);

			wpas_log_consent( $form_data['wpas-user'], __( 'Right to be forgotten mail', 'awesome-support' ), __( 'requested', 'awesome-support' ) );
			
			if ( ! empty( $ticket_id ) ) {
				
				$response['code']    = 200;
				$response['message'] = __( 'We have received your "Right To Be Forgotten" request!', 'awesome-support' );				
				
				// send erase data request.
				if ( function_exists( 'wp_create_user_request' )  && function_exists( 'wp_send_user_request' ) ) {
					$current_user = wp_get_current_user();
					if( isset( $current_user->user_email ) && !empty( $current_user->user_email )){

						if( 'delete' === $request_type ){
							$request_id = wp_create_user_request( $current_user->user_email, 'remove_personal_data' );
							$response['message'] = __( 'We have received your "Right To Be Forgotten" request!', 'awesome-support' );
						}
						if( 'export' === $request_type ){
							$request_id = wp_create_user_request( $current_user->user_email, 'export_personal_data' );
							$response['message'] = __( 'We have received your Export data request!', 'awesome-support' );
						}

						if( isset( $request_id) && $request_id ) {
							wp_send_user_request( $request_id );
						} else {
							// if you've gotten here chances are the error is a duplicate request.
							if ( is_wp_error( $request_id ) ){
								$response['message'] = $request_id->get_error_message() ;
								unset( $response['code'] ) ;
							}
						}
					}
				}
				
				$response['code']    = 200;

			} else {
				$response['message'] = __( 'Something went wrong. Please try again!', 'awesome-support' );
			}
		} else {
			$response['message'] = __( 'Cheating huh?', 'awesome-support' );
		}
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Ajax based processing user opted in button
	 * The button can be found on GDPR popup in front-end
	 */
	public function wpas_gdpr_user_opt_in() {
		/**
		 * Initialize custom reponse message
		 */
		$response = array(
			'code'    => 403,
			'message' => array(),
		);

		/**
		 * Initiate nonce
		 */
		$nonce = isset( $_POST['data']['nonce'] ) ? $_POST['data']['nonce'] : '';

		/**
		 * Security checking
		 */
		if ( ! empty( $nonce ) && check_ajax_referer( 'wpas-gdpr-nonce', 'security' ) ) {

			$item   	= isset( $_POST['data']['gdpr-data'] ) ? sanitize_text_field( $_POST['data']['gdpr-data'] ) : '';
			$user   	= isset( $_POST['data']['gdpr-user'] ) ? sanitize_text_field( $_POST['data']['gdpr-user'] ) : '';
			$status 	= __( 'Opted-in', 'awesome-support' );
			$opt_in 	= strtotime( 'NOW' );
			$opt_out   	= isset( $_POST['data']['gdpr-optout'] ) ? strtotime( sanitize_text_field( $_POST['data']['gdpr-optout'] ) ) : '';
			$gdpr_id 	= wpas_get_gdpr_data( $item );

			/**
			 * Who is the current user right now?
			 */	
			$logged_user = wp_get_current_user();
			$current_user = isset( $logged_user->data->display_name ) ? $logged_user->data->display_name : __( 'user', 'awesome-support');

			wpas_track_consent(
				array(
					'item'    => $item,
					'status'  => $status,
					'opt_in'  => $opt_in,
					'opt_out' => '',
					'is_tor'  => false,
				), $user, 'in'
			);

			wpas_log_consent( $user, $item, __( 'opted-in', 'awesome-support' ), '', $current_user );
			$response['code']               = 200;
			$response['message']['success'] = __( 'You have successfully opted-in', 'awesome-support' );
			$response['message']['date']    = date( 'm/d/Y', $opt_in );
			$response['message']['status']    = $status;
			/**
			 * return buttons markup based on settings
			 * If can opt-out, then display the button
			 */
			if( wpas_get_option( 'gdpr_notice_opt_out_ok_0' . $gdpr_id, false ) ) {
				$response['message']['button']  = sprintf(
					'<a href="#" class="button button-secondary wpas-button wpas-gdpr-opt-out" data-gdpr="' . $item . '" data-user="' . get_current_user_id() . '">%s</a>',
					__( 'Opt-out', 'awesome-support' )
				);
			} else {
				$response['message']['button']  = '';
			}
		} else {
			$response['message']['error'] = __( 'Cheating huh?', 'awesome-support' );
		}
		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Ajax based processing user opted out button
	 * The button can be found on GDPR popup in front-end
	 */
	public function wpas_gdpr_user_opt_out() {
		/**
		 * Initialize custom reponse message
		 */
		$response = array(
			'code'    => 403,
			'message' => array(),
		);

		/**
		 * Initiate nonce
		 */
		$nonce = isset( $_POST['data']['nonce'] ) ? $_POST['data']['nonce'] : '';

		/**
		 * Security checking
		 */
		if ( ! empty( $nonce ) && check_ajax_referer( 'wpas-gdpr-nonce', 'security' ) ) {

			$item    	= isset( $_POST['data']['gdpr-data'] ) ? sanitize_text_field( $_POST['data']['gdpr-data'] ) : '';
			$user    	= isset( $_POST['data']['gdpr-user'] ) ? sanitize_text_field( $_POST['data']['gdpr-user'] ) : '';
			$status  	= __( 'Opted-Out', 'awesome-support' );
			$opt_out 	= strtotime( 'NOW' );
			$opt_in   	= isset( $_POST['data']['gdpr-optin'] ) ? strtotime( sanitize_text_field( $_POST['data']['gdpr-optin'] ) ) : '';

			/**
			 * Who is the current user right now?
			 */	
			$logged_user = wp_get_current_user();
			$current_user = isset( $logged_user->data->display_name ) ? $logged_user->data->display_name : __( 'user', 'awesome-support');

			wpas_track_consent(
				array(
					'item'    => $item,
					'status'  => $status,
					'opt_in'  => '',
					'opt_out' => $opt_out,
					'is_tor'  => false,
				), $user, 'out'
			);
			wpas_log_consent( $user, $item, __( 'opted-out', 'awesome-support' ), '', $current_user );

			$response['code']               = 200;
			$response['message']['success'] = __( 'You have successfully opted-out', 'awesome-support' );
			$response['message']['date']    = date( 'm/d/Y', $opt_out );
			$response['message']['status']    = $status;
			$response['message']['button']  = sprintf(
				'<a href="#" class="button button-secondary wpas-button wpas-gdpr-opt-in" data-gdpr="' . $item . '" data-user="' . get_current_user_id() . '">%s</a>',
				__( 'Opt-in', 'awesome-support' )
			);
		} else {
			$response['message']['error'] = __( 'Cheating huh?', 'awesome-support' );
		}
		wp_send_json( $response );
		wp_die();
	}

}
