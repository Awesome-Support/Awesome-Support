<?php

/**
 * Awesome Support File Uploader.
 *
 * @package   Awesome_Support
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */
class WPAS_File_Upload {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public    $post_id   = null;
	protected $parent_id = null;
	protected $index     = 'files';

	/**
	 * Store the potential error messages.
	 */
	protected $error_message;

	public function __construct() {

		/**
		 * Load the addon settings
		 */
		require_once( WPAS_PATH . 'includes/file-uploader/settings-file-upload.php' );

		if ( ! $this->can_attach_files() ) {
			return;
		}
		
		add_filter( 'upload_dir', array( $this, 'set_upload_dir' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'limit_upload' ), 10, 1 );
		add_filter( 'upload_mimes', array( $this, 'custom_mime_types' ), 10, 1 );
		add_action( 'pre_get_posts', array( $this, 'attachment_query_var' ), 10, 1 );
		add_action( 'init', array( $this, 'attachment_endpoint' ), 10, 1 );
		add_action( 'template_redirect', array( $this, 'view_attachment' ), 10, 0 );
		add_action( 'posts_clauses', array( $this, 'filter_attachments_out' ), 10, 2 );

		if ( ! is_admin() ) {

			/* Load media uploader related files. */
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/template.php' );

			add_action( 'wpas_submission_form_inside_before_submit', array( $this, 'upload_field' ) );                  // Load the dropzone after description textarea
			add_action( 'wpas_ticket_details_reply_textarea_after', array( $this, 'upload_field' ) );                  // Load dropzone after reply textarea

		}

		// We need those during Ajax requests and admin-ajax.php is considered to be part of the admin
		add_action( 'wpas_frontend_ticket_content_after', array( $this, 'show_attachments' ), 10, 1 );
		add_action( 'wpas_frontend_reply_content_after', array( $this, 'show_attachments' ), 10, 1 );
		add_action( 'wpas_process_ticket_attachments', array( $this, 'process_attachments' ), 10, 2 );

		if ( is_admin() ) {

			add_action( 'wpas_add_reply_admin_after', array( $this, 'new_reply_backend_attachment' ), 10, 2 );

			
			add_action( 'post_edit_form_tag', array( $this, 'add_form_enctype' ), 10, 1 );
			
			add_filter( 'wpas_admin_tabs_after_reply_wysiwyg', array( $this, 'upload_field_add_tab' ) , 11, 1 ); // Register attachments tab under reply wysiwyg
			add_filter( 'wpas_admin_tabs_after_reply_wysiwyg_attachments_content', array( $this, 'upload_field_tab_content' ) , 11, 1 ); // Return content for attachments tab
			
			add_action( 'before_delete_post', array( $this, 'delete_attachments' ), 10, 1 );
			add_action( 'wpas_backend_ticket_content_after', array( $this, 'show_attachments' ), 10, 1 );
			add_action( 'wpas_backend_reply_content_after', array( $this, 'show_attachments' ), 10, 1 );
			add_filter( 'wpas_cf_wrapper_class', array( $this, 'add_wrapper_class_admin' ), 10, 2 );

		}

		// If Ajax upload is enabled
		if ( boolval( wpas_get_option( 'ajax_upload', false ) ) || boolval( wpas_get_option( 'ajax_upload_all', false ) ) ) {

			// Cleanup action
			add_action( 'attachments_dir_cleanup_action', array( $this, 'attachments_dir_cleanup' ) );

			// Schedule cleanup of unused attachments directories
			add_action( 'wp', array( $this, 'attachments_dir_cleanup_schedule' ) );


			// After Add Reply action hook
			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'load_ajax_uploader_assets' ), 10 );
			} else {
				add_action( 'wp_enqueue_scripts',    array( $this, 'load_ajax_uploader_assets' ), 10 );
			}
			
			add_action( 'wpas_open_ticket_after', array( $this, 'new_ticket_ajax_attachments' ), 10, 2 ); // Check for ajax attachments after user opened a new ticket
			add_action( 'wpas_add_reply_after', array( $this, 'new_reply_ajax_attachments' ), 20, 2 );  // Check for ajax attachments after user submitted a new reply

			add_action( 'wp_ajax_wpas_upload_attachment',      array( $this, 'ajax_upload_attachment' ) );
			add_action( 'wp_ajax_wpas_delete_temp_attachment', array( $this, 'ajax_delete_temp_attachment' ) );
			add_action( 'wp_ajax_wpas_delete_temp_directory',  array( $this, 'ajax_delete_temp_directory' ) );

		}
		else
		{
			add_action( 'wpas_open_ticket_after', array( $this, 'new_ticket_attachment' ), 10, 2 ); // Save attachments after user opened a new ticket
			add_action( 'wpas_add_reply_public_after', array( $this, 'new_reply_attachment' ), 10, 2 );  // Save attachments after user submitted a new reply
		}
		
		add_action( 'wpas_submission_form_inside_before_submit', array( $this, 'add_auto_delete_button_fe_submission' ) );		
		add_action( 'wpas_ticket_details_reply_close_checkbox_after',		 array( $this, 'add_auto_delete_button_fe_ticket' ) );
		add_action( 'wpas_backend_ticket_status_before_actions', array( $this, 'admin_add_auto_delete_button'), 100 );
		
		add_action( 'wp_ajax_wpas_auto_delete_attachment_flag',  array( $this, 'auto_delete_attachment_flag' ) );
		
		add_action( 'wp_ajax_wpas_delete_attachment',			 array( $this, 'ajax_delete_attachment' ) );
		
		add_action( 'wpas_ticket_after_saved',					 array( $this, 'ticket_after_saved' ) );
		add_action( 'wpas_open_ticket_after',			array( $this, 'wpas_open_ticket_after' ), 11, 2 );
		
		add_action( 'wpas_after_close_ticket',			array( $this, 'wpas_maybe_delete_attachments_after_close_ticket' ), 11, 3 );
		
	}
	
	
	/**
	 * From backend tools add or remove auto delete attachments flag for open, closed or all tickets
	 * 
	 * @global object $wpdb
	 * 
	 * @param string $type
	 * @param boolean $auto_delete
	 */
	public static function mark_tickets_auto_delete_attachments( $type = 'all', $auto_delete = true ) {
		
		global $wpdb;
		
		$type_clause = "pm.meta_value IN ('open', 'closed')";
		
		if( 'all' !== $type ) {
			$type_clause = 'pm.meta_value = "' . $type . '"';
		}
		
		$meta_value = $auto_delete ? '1' : '';
		
		
		
		$select_q = "SELECT pm.post_id, 'auto_delete_attachments' as meta_key, '{$meta_value}' as meta_value from $wpdb->postmeta pm 
					LEFT JOIN $wpdb->postmeta pm2 ON pm2.post_id = pm.post_id AND pm2.meta_key = 'auto_delete_attachments'
					INNER JOIN $wpdb->posts p ON p.ID = pm.post_id AND p.post_type='ticket'
					WHERE pm.meta_key = '_wpas_status' AND $type_clause";
		
		$update_query = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_key = %s AND post_id IN( 
					select post_ids.post_id from ( $select_q AND !isnull( pm2.meta_id ) group by pm.post_id ) as post_ids
				)";
		
		
		
		
		$wpdb->query( $wpdb->prepare( $update_query, $meta_value, 'auto_delete_attachments' ));
		
		
		$q = "INSERT INTO $wpdb->postmeta( post_id, meta_key, meta_value ) ( $select_q AND isnull( pm2.meta_id ) group by pm.post_id )";
		$wpdb->query( $q );
	}
	
	
	/**
	 * Save auto delete attachments flag from backend after ticket is saved 
	 * 
	 * @param int $ticket_id
	 * 
	 * @return void
	 */
	function ticket_after_saved( $ticket_id ) {
		
		if( !is_admin() ) {
			return;
		}
		
		//$old_auto_save = get_post_meta( $ticket_id, 'auto_delete_attachments', true );
		$auto_delete = filter_input( INPUT_POST, 'wpas-auto-delete-attachments', FILTER_SANITIZE_NUMBER_INT );
		
		//if( $auto_delete !== $old_auto_save ) {
		//	$this->update_auto_delete_flag( $ticket_id, $auto_delete, 'agent' );
		//}
		
		if ( wpas_agent_can_set_auto_delete_attachments() || wpas_is_asadmin() ) {
			$this->update_auto_delete_flag( $ticket_id, $auto_delete, 'agent' );
		}
		
	}
	
	/**
	 * Save auto delete attachments flag from front-end
	 */
	function auto_delete_attachment_flag() {
		
		$ticket_id = filter_input( INPUT_POST, 'ticket_id', FILTER_SANITIZE_NUMBER_INT );
		$auto_delete = filter_input( INPUT_POST, 'auto_delete', FILTER_SANITIZE_NUMBER_INT );
		
		if( $ticket_id && ( 0 == $auto_delete || 1 == $auto_delete ) ) {
			$this->update_auto_delete_flag( $ticket_id, $auto_delete );
		}
	}
	
	/**
	 * update auto delete attachments flag
	 * 
	 * @param int $ticket_id
	 * @param boolean $auto_delete
	 * @param string $type
	 */
	function update_auto_delete_flag( $ticket_id, $auto_delete, $type = 'user' ) {
		
		$auto_delete = $auto_delete ? '1' : '';
		
		update_post_meta( $ticket_id, 'auto_delete_attachments', $auto_delete );
		update_post_meta( $ticket_id, 'auto_delete_attachments_type', $type );
	}
	
	/**
	 * Add field to mark auto delete attachments on ticket submission form
	 */
	function add_auto_delete_button_fe_submission() {
		global $post;
		
		$flag_on = '';
		
		
		$auto_delete = wpas_get_option( 'auto_delete_attachments' );
		
		$user_can_set_flag = wpas_user_can_set_auto_delete_attachments();
		
		if( !$auto_delete || !$user_can_set_flag ) {
			return;
		}
		
		
		if( $auto_delete ) {
			$flag_on = '1';
		} 
		
		
		$this->auto_delete_field( $flag_on );
		
	}
	
	
	/**
	 * Add field to mark auto delete attachments on ticket edit page front end
	 */
	function add_auto_delete_button_fe_ticket() {
		global $post;
		
		$auto_delete = boolval( wpas_get_option( 'auto_delete_attachments' ) );
		
		if( wpas_user_can_set_auto_delete_attachments()  && true == $auto_delete ) {
			$flag_on = get_post_meta( $post->ID, 'auto_delete_attachments', true );
			$this->auto_delete_field( $flag_on );
		}
		
	}
	
	
	/**
	 * Add field to mark auto delete attachments on ticket close
	 */
	function admin_add_auto_delete_button() {
		
		/* Exit if agents are not allowed to set auto-delete flag */
		if ( ! wpas_is_asadmin() &&  ! boolval( wpas_get_option( 'agent_can_set_auto_delete_attachments', false ) ) ) {
			return ;
		}

		/* Got here so ok to paint the field */
		global $post_id;
		
		$flag_on = get_post_meta( $post_id, 'auto_delete_attachments', true );
		
		echo '<p>';
		
		$this->auto_delete_field( $flag_on );
		echo '</p>';
		
	}
	
	function auto_delete_field( $flag_on = false ) {
		?>

		<div class="wpas-auto-delete-attachments-container">
			<label for="wpas-auto-delete-attachments">
				<input type="checkbox" id="wpas-auto-delete-attachments" name="wpas-auto-delete-attachments" value="1" <?php checked(1, $flag_on); ?>>
				<?php _e( 'Automatically delete attachments when a ticket is closed', 'wpas' ); ?>
			</label>
		</div>
		<?php
	}
	
	/**
	 * Check and auto delete attachment after ticket is closed
	 * 
	 * @param int $ticket_id
	 * @param boolean $update
	 * @param int $user_id
	 */
	public function wpas_maybe_delete_attachments_after_close_ticket( $ticket_id, $update, $user_id ) {
		
		
		$delete_attachments = get_post_meta( $ticket_id, 'auto_delete_attachments', true );
		
		if( $delete_attachments ) {
			
			// Get attachments on ticket
			$attachments = get_attached_media( '', $ticket_id );
			
			// Create array of attachments from replies..
			$replies = wpas_get_replies( $ticket_id );
			foreach( $replies as $reply ) {
				$attachments = array_merge( $attachments, get_attached_media( '', $reply->ID ) );
			}

			// Now delete them all
			$logs = array() ; // hold log messages to be written later to ticket
			
			$attachments = apply_filters( 'attachments_list_for_auto_delete', $attachments, $ticket_id );
			
			foreach ( $attachments as $attachment ) {
				
				$filename   = explode( '/', $attachment->guid );
				$name = $filename[ count( $filename ) - 1 ];
				
				wp_delete_attachment( $attachment->ID );
				
				$logs[] = '<li>' . sprintf( __( '%s attachment auto deleted', 'awesome-support' ), $name ) . '</li>';				
				
			}			
			
			// Write logs to ticket
			if( !empty( $logs ) ) {
				$log_content = '<ul>'. implode( '', $logs ).'</ul>';
				wpas_log( $ticket_id, $log_content );
			}							
			
		}
		

	}
	
	/**
	 * Add auto close mark after a new ticket is submitted
	 * 
	 * @param int $ticket_id
	 * @param array $data
	 */
	function wpas_open_ticket_after( $ticket_id, $data ) {
		
		
		$auto_delete = wpas_get_option( 'auto_delete_attachments' );
		
		$user_can_set_flag = wpas_user_can_set_auto_delete_attachments();
		
		if( !$auto_delete && !$user_can_set_flag ) {
			return;
		}

		$auto_delete_type = '';
		
		if( $user_can_set_flag ) {
			$auto_delete = filter_input( INPUT_POST, 'wpas-auto-delete-attachments', FILTER_SANITIZE_NUMBER_INT );
			$auto_delete_type = 'user';
		} elseif( $auto_delete ) {
			$auto_delete_type = 'auto';
		}
		
		$auto_delete = $auto_delete ? '1' : '';
		
		if( $auto_delete ) {
			update_post_meta( $ticket_id, 'auto_delete_attachments', $auto_delete );
			update_post_meta( $ticket_id, 'auto_delete_attachments_type', $auto_delete_type );
		}
	}
	
	
	/**
	 * Delete single attachment from front-end or backend
	 */
	function ajax_delete_attachment() {
		
		$parent_id = filter_input( INPUT_POST, 'parent_id', FILTER_SANITIZE_NUMBER_INT );
		$attachment_id = filter_input( INPUT_POST, 'att_id', FILTER_SANITIZE_NUMBER_INT );
		
		$user = wp_get_current_user();
		$deleted = false;
		
		if( $user && $parent_id && $attachment_id ) {
			
			$ticket_id = $parent_id;
			
			$can_delete = wpas_can_delete_attachments();
			
			if( $can_delete ) {
				
				$parent = get_post( $parent_id );
				if( 'ticket_reply' === $parent->post_type ) {
					$ticket_id = $parent->post_parent;
				}
			
				if( 'ticket' === $parent->post_type || 'ticket_reply' === $parent->post_type ) {
					
					$attachment = get_post( $attachment_id );
					$filename   = explode( '/', $attachment->guid );
					$name = $filename[ count( $filename ) - 1 ];
					
					wp_delete_attachment( $attachment_id, true );
					
					wpas_log( $ticket_id, sprintf( __( '%s attachment deleted by %s', 'awesome-support' ), $name, $user->display_name ) );
					$deleted = true;
				}
				
			}
			
		}
		
		if( $deleted ) {
			wp_send_json_success( array( 'msg' => __( 'Attachment deleted.', 'wpas' ) ) );
		} else {
			wp_send_json_error();
		}
		
		
		die();
	}
	

	/**
	 * Filter out tickets and ticket replies attachments
	 *
	 * Tickets attachments don't have their place in the media library. The library can quickly become a huge mess and
	 * it becomes impossible to work with actual post attachments.
	 *
	 * @since 3.3
	 *
	 * @param array    $clauses  SQL query clauses
	 * @param WP_Query $wp_query WordPress query object
	 *
	 * @return array
	 */
	public function filter_attachments_out( $clauses, $wp_query ) {

		global $pagenow, $wpdb;

		$action = isset( $_POST['action'] ) ? $_POST['action'] : '';

		// Make sure the query is for the media library
		if ( 'query-attachments' !== $action ) {
			return $clauses;
		}

		// We only want to alter queries in the admin
		if ( ! $wp_query->is_admin ) {
			return $clauses;
		}

		// Make sure this request is done through Ajax as this is how the media library does it
		if ( 'admin-ajax.php' !== $pagenow ) {
			return $clauses;
		}

		// Is this query for attachments?
		if ( 'attachment' !== $wp_query->query_vars['post_type'] ) {
			return $clauses;
		}
		
		$post_types = apply_filters( 'wpas_filter_out_media_attachment_post_types', array(
			'ticket', 'ticket_reply'
		) );
		
		if( !empty( $post_types ) ) {
			
			$post_types_list  = "'". implode( "', '", $post_types ) . "'";
			
			$clauses['join'] .= " LEFT OUTER JOIN $wpdb->posts daddy ON daddy.ID = $wpdb->posts.post_parent";
			$clauses['where'] .= " AND ( daddy.post_type NOT IN ( $post_types_list ) OR daddy.ID IS NULL )";
		}

		return $clauses;

	}

	/**
	 * Add a custom class to the upload field wrapper in the admin
	 *
	 * @since 3.2.10
	 *
	 * @param array $classes Field wrapper classes
	 * @param array $field   Field parameters
	 *
	 * @return array
	 */
	public function add_wrapper_class_admin( $classes, $field ) {

		if ( 'upload' === $field['args']['field_type'] ) {
			array_push( $classes, 'wpas-under-reply-box' );
		}

		return $classes;

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
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
	 * Add the attachment query var to the $query object.
	 * This is used as a fallback for when pretty permalinks
	 * are not enabled.
	 *
	 * @param WP_Query $query The WordPress main query
	 *
	 * @since 3.2.0
	 * @return void
	 */
	public function attachment_query_var( $query ) {
		if ( $query->is_main_query() && isset( $_GET['wpas-attachment'] ) ) {
			$query->set( 'wpas-attachment', filter_input( INPUT_GET, 'wpas-attachment', FILTER_SANITIZE_NUMBER_INT ) );
		}
	}

	/**
	 * Add a new rewrite endpoint.
	 *
	 * @since 3.2.0
	 * @return void
	 */
	public function attachment_endpoint() {
		add_rewrite_endpoint( 'wpas-attachment', EP_PERMALINK );
	}

	/**
	 * Display the attachment.
	 *
	 * Uses the new rewrite endpoint to get an attachment ID
	 * and display the attachment if the currently logged in user
	 * has the authorization to.
	 *
	 * @since 3.2.0
	 * @return void
	 */
	public function view_attachment() {

		$attachment_id = get_query_var( 'wpas-attachment' );

		if ( ! empty( $attachment_id ) ) {

			$attachment = get_post( $attachment_id );

			/**
			 * Return a 404 page if the attachment ID
			 * does not match any attachment in the database.
			 */
			if ( empty( $attachment ) ) {

				/**
				 * @var WP_Query $wp_query WordPress main query
				 */
				global $wp_query;

				$wp_query->set_404();

				status_header( 404 );
				include( get_query_template( '404' ) );

				die();
			}

			if ( 'attachment' !== $attachment->post_type ) {
				wp_die( __( 'The file you requested is not a valid attachment', 'awesome-support' ) );
			}

			if ( empty( $attachment->post_parent ) ) {
				wp_die( __( 'The attachment you requested is not attached to any ticket', 'awesome-support' ) );
			}

			$parent    = get_post( $attachment->post_parent ); // Get the parent. It can be a ticket or a ticket reply
			$parent_id = empty( $parent->post_parent ) ? $parent->ID : $parent->post_parent;

			if ( true !== wpas_can_view_ticket( $parent_id ) ) {
				wp_die( __( 'You are not allowed to view this attachment', 'awesome-support' ) );
			}

			$render_method = wpas_get_option( 'attachment_render_method', 'inline');  // returns 'inline' or 'attachment'.
			$filename = basename( $attachment->guid );

			ob_clean();
			ob_end_flush();

			ini_set( 'user_agent', 'Awesome Support/' . WPAS_VERSION . '; ' . get_bloginfo( 'url' ) );
			header( "Content-Type: $attachment->post_mime_type" );
			header( "Content-Disposition: $render_method; filename=\"$filename\"" );
			
			switch ($render_method) {
				case 'inline':
					readfile( $attachment->guid );
					break ;
					
				case 'attachment':
					echo readfile( $_SERVER['DOCUMENT_ROOT'] . parse_url($attachment->guid, PHP_URL_PATH) );
					break ;
					
				default:
					readfile( $attachment->guid );
					break ;				
			};

			die();

		}

	}

	/**
	 * Check if the current user can attach a file.
	 *
	 * @since  3.0.0
	 * @return boolean True if the user has the capability, false otherwise
	 */
	public function can_attach_files() {

		if ( false === boolval( wpas_get_option( 'enable_attachments' ) ) ) {
			return false;
		}

		$current_user = wp_get_current_user();

		if ( defined( 'DOING_CRON' ) && 0 === $current_user->ID ) {

		    $default_id = (int) wpas_get_option( 'assignee_default', 1 );

		    wp_set_current_user( $default_id );

		}

		if ( current_user_can( 'attach_files' ) ) {
			return true;
		}

		return false;
	}

	public function get_allowed_filetypes() {
		return apply_filters( 'wpas_attachments_filetypes', wpas_get_option( 'attachments_filetypes' ) );
	}

	/**
	 * Set upload directory.
	 *
	 * Set a custom upload directory in order to properly
	 * separate WordPress uploads and tickets attachments.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $upload Initial upload directory
	 *
	 * @return string Upload directory
	 */
	public function set_upload_dir( $upload ) {

		/* Get the ticket ID */
		$ticket_id = ! empty( $this->parent_id ) ? $this->parent_id : $this->post_id;

		if ( empty( $ticket_id ) ) {
			return $upload;
		}

		if ( ! $this->can_attach_files() ) {
			return $upload;
		}

		/* We sort the uploads in sub-folders per ticket. */
		$subdir = "/awesome-support/ticket_$ticket_id";

		/* Create final URL and dir */
		$dir = $upload['basedir'] . $subdir;
		$url = $upload['baseurl'] . $subdir;

		/* Update upload params */
		$upload['path']   = $dir;
		$upload['url']    = $url;
		$upload['subdir'] = $subdir;

		/* Create the directory if it doesn't exist yet, make sure it's protected otherwise */
		if ( ! is_dir( $dir ) ) {

		    if ( $_SERVER['REQUEST_METHOD'] == 'GET'
			    && isset( $_GET['action'] )
                && $_GET['action'] === 'delete'
            ) {
				return $upload;
			}

			$this->create_upload_dir( $dir );
		} else {
			$this->protect_upload_dir( $dir );
		}

		return $upload;

	}

	/**
	 * Create the upload directory for a ticket.
	 *
	 * @since 3.1.7
	 *
	 * @param string $dir Upload directory
	 *
	 * @return boolean Whether or not the directory was created
	 */
	public function create_upload_dir( $dir ) {

		$make = wp_mkdir_p ( $dir );

		if ( true === $make ) {
			$this->protect_upload_dir( $dir );
		}

		return $make;

	}

	/**
	 * Protects an upload directory by adding an .htaccess file
	 *
	 * @since 3.1.7
	 *
	 * @param string $dir Upload directory
	 *
	 * @return void
	 */
	protected function protect_upload_dir( $dir ) {

		if ( is_writable( $dir ) ) {
			
			$filename = $dir . '/.htaccess';
			
			$filecontents = wpas_get_option( 'htaccess_contents_for_attachment_folders', 'Options -Indexes' ) ;
			if ( empty( $filecontents ) ) {
				$filecontents = 'Options -Indexes' ;
			}

			if ( ! file_exists( $filename ) ) {
				$file = fopen( $filename, 'a+' );
				if ( false <> $file ) {
					fwrite( $file, $filecontents );
					fclose( $file );
				} else {
					// attempt to record failure...
					wpas_write_log('file-uploader','unable to write .htaccess file to folder ' . $dir ) ;
				}
			}
		} else {
			// folder isn't writable so no point in attempting to do it...
			// log the error in our log files instead...
			wpas_write_log('file-uploader','The folder ' . $dir . ' is not writable.  So we are unable to write a .htaccess file to this folder' ) ;			
		}

	}

	/**
	 * Add dropzone markup.
	 *
	 * @return void
	 */
	public function upload_field() {

		$filetypes = $this->get_allowed_filetypes();
		$filetypes = explode( ',', $filetypes );
		$accept    = array();

		foreach ( $filetypes as $key => $type ) {
			$filetypes[ $key ] = "<code>.$type</code>";
			array_push( $accept, ".$type" );
		}

		$filetypes = implode( ', ', $filetypes );
		$accept    = implode( ',', $accept );

		/**
		 * Output the upload field using a custom field
		 */
		$attachments_args = apply_filters( 'wpas_ticket_attachments_field_args', array(
			'name' => $this->index,
			'args' => array(
				'required'   => false,
				'capability' => 'edit_ticket',
				'field_type' => 'upload',
				'multiple'   => true,
				'use_ajax_uploader' => ( boolval( wpas_get_option( 'ajax_upload', false ) ) ),
				'enable_paste' => ( boolval( wpas_get_option( 'ajax_upload_paste_image', false ) ) ),
				'label'      => __( 'Attachments', 'awesome-support' ),
				'desc'       => sprintf( __( ' You can upload up to %d files (maximum %d MB each) of the following types: %s', 'awesome-support' ), (int) wpas_get_option( 'attachments_max' ), (int) wpas_get_option( 'filesize_max' ), apply_filters( 'wpas_attachments_filetypes_display', $filetypes ) ),
			),
		) );

		$attachments = new WPAS_Custom_Field( $this->index, $attachments_args );
		echo $attachments->get_output();
	
	}
	
	/**
	 * 
	 * Register attachments tab under reply wysiwyg
	 * 
	 * @param array $tabs
	 * 
	 * @return array
	 */
	public function upload_field_add_tab( $tabs ) {
		
		$tabs['attachments'] = __( 'Attachments' , 'awesome-support' );
		
		return $tabs;
	}
	
	/**
	 * 
	 * Return content for attachments tab
	 * 
	 * @param string $content
	 * 
	 * @return string
	 */
	public function upload_field_tab_content( $content ) {
		ob_start();
		$this->upload_field();
		return ob_get_clean();
	}

	/**
	 * Get post attachments.
	 *
	 * Get the attachments for a specific ticket or reply.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $post_id ID of the post to get attachment for
	 *
	 * @return array            Array of attachments or empty array if no attachments are found
	 */
	public function get_attachments( $post_id ) {

		$post = get_post( $post_id );

		if ( is_null( $post ) ) {
			return array();
		}

		$args = array(
			'post_parent'            => $post_id,
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			'posts_per_page'         => - 1,
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,

		);

		$attachments = new WP_Query( $args );
		$list        = array();

		if ( empty( $attachments->posts ) ) {
			return array();
		}

		foreach ( $attachments->posts as $key => $attachment ) {
			$list[ $attachment->ID ] = array( 'id' => $attachment->ID, 'name' => $attachment->post_title, 'url' => $attachment->guid );
		}

		return $list;

	}

	/**
	 * Check if post has attachments.
	 *
	 * Check if a specific ticket or reply has attachments.
	 * This method is based on get_attachments() and only returns
	 * a boolean value based on the returned array being empty or not.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $post_id ID of the post to get attachment for
	 *
	 * @return boolean          True if the ticket has attachments, false otherwise
	 */
	public function has_attachments( $post_id ) {

		$attachments = $this->get_attachments( $post_id );

		if ( empty( $attachments ) ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Show ticket attachments.
	 *
	 * Displays a ticket or reply attachments.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $post_id ID of the post to get attachment for
	 *
	 * @return void
	 */
	public function show_attachments( $post_id ) {

		$attachments = $this->get_attachments( $post_id );

		if ( ! empty( $attachments ) ): ?>

			<div class="wpas-reply-attachements">
				<strong><?php _e( 'Attachments:', 'awesome-support' ); ?></strong>
				<ul>
					<?php
					
					$can_delete = wpas_can_delete_attachments();
					
					foreach ( $attachments as $attachment_id => $attachment ):

						/**
						 * Get attachment metadata.
						 *
						 * @var array
						 */
						$metadata = wp_get_attachment_metadata( $attachment_id );

						/**
						 * This is the default case where an attachment was uploaded by the WordPress uploader.
						 * In this case we get the media from the ticket's attachments directory.
						 */
						if ( ! isset( $metadata['wpas_upload_source'] ) || 'wordpress' === $metadata['wpas_upload_source'] ) {

							/**
							 * Get filename.
							 */
							$filename   = explode( '/', $attachment['url'] );
							$filename   = $name = $filename[ count( $filename ) - 1 ];
							$upload_dir = wp_upload_dir();
							$filepath   = trailingslashit( $upload_dir['basedir'] ) . "awesome-support/ticket_$post_id/$filename";
							$filesize   = file_exists( $filepath ) ? $this->human_filesize( filesize( $filepath ), 0 ) : '';

							/**
							 * Prepare attachment link
							 */
							if ( false === boolval( wpas_get_option( 'unmask_attachment_links', false ) ) ) {
								// mask or obscure attachment links
								$link = add_query_arg( array( 'wpas-attachment' => $attachment['id'] ), home_url() );
							} else {
								// show full link
								$link = $attachment['url'];
							}

							?>
							<li>
									<?php 
									if( $can_delete ) {
										printf( '<a href="#" class="btn_delete_attachment" data-parent_id="%s" data-att_id="%s">%s</a>', $post_id,  $attachment['id'], __( 'X', 'awesome-support' ) );
									}
									
										
										
									?>
									
									<a href="<?php echo $link; ?>" target="_blank"><?php echo $name; ?></a> <?php echo $filesize; ?></li><?php

						} /**
						 * Now if we have a different upload source we delegate the computing
						 * to whatever will hook on wpas_attachment_display_$source
						 */
						else {

							$source = sanitize_text_field( $metadata['wpas_upload_source'] );

							/**
							 * wpas_attachment_display_$source fires if the current attachment
							 * was uploaded by an unknown source.
							 *
							 * @since  3.1.5
							 *
							 * @param  integer $attachment_id ID of this attachment
							 * @param  array   $attachment    The attachment array
							 * @param  integer $post_id       ID of the post we're displaying attachments for
							 */
							do_action( 'wpas_attachment_display_' . $source, $attachment_id, $attachment, $metadata, $post_id );

						}

					endforeach; ?>
				</ul>
			</div>
		<?php endif;
	}

	/**
	 * Human readable filesize.
	 *
	 * Transform the file size into a readable format including
	 * the size unit.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $bytes    Filesize in bytes
	 * @param  integer $decimals Number of decimals to show
	 *
	 * @return string             Human readable filesize
	 * @link   http://php.net/manual/en/function.filesize.php#106569
	 */
	public function human_filesize( $bytes, $decimals = 2 ) {
		$sz     = 'BKMGTP';
		$factor = (int) floor( ( strlen( $bytes ) - 1 ) / 3 );

		return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$sz[ $factor ];
	}

	public function add_form_enctype( $post ) {

		if ( 'ticket' !== $post->post_type ) {
			return;
		}

		echo ' enctype="multipart/form-data"';
	}

	/**
	 * Process the upload.
	 *
	 * We delegate the upload process to WordPress. Why reinvent the wheel?
	 * The only thing we do change is the upload path. For the rest it's all standard.
	 *
	 * @since  3.0.0
	 * @return bool Whether or not the upload has been processed
	 */
	public function process_upload() {

		$index = "wpas_$this->index"; // We need to prefix the index as the custom fields are always prefixed

		/* We have a submission with a $_FILES var set */
		if ( $_POST && $_FILES && isset( $_FILES[ $index ] ) ) {

			if ( empty( $_FILES[ $index ]['name'][0] ) ) {
				return false;
			}

			$max = wpas_get_option( 'attachments_max', 2 );
			$id  = false; // Declare a default value for $id

			if ( $this->individualize_files() ) {

				for ( $i = 0; isset( $_FILES["{$index}_$i"] ); ++ $i ) {

					/* Limit the number of uploaded files */
					if ( $i + 1 > $max ) {
						break;
					}

					$id = media_handle_upload( "{$index}_$i", $this->post_id );
				}

			} else {
				$id = media_handle_upload( $index, $this->post_id );
			}

			if ( is_wp_error( $id ) ) {

				$this->error_message = $id->get_error_message();
				add_filter( 'wpas_redirect_reply_added', array( $this, 'redirect_error' ), 10, 2 );

				return false;

			} else {
				return true;
			}

		} else {
			return false;
		}
	}

	/**
	 * Process files as ticket attachments.
	 *
	 * Call with post id and array of attachments (filename and raw file content in data)
	 *
	 * @since  3.3.4
	 *
	 * @param  int    $post_id     Ticket or Reply id to attach to
	 * @param  object $attachments Array of attachment file names and raw content
	 *
	 * @return void
	 */
	public function process_attachments( $post_id, $attachments ) {

		$max           = wpas_get_option( 'attachments_max', 2 );   // Core AS Max Files (File Upload settings)
		$cnt           = 0;                                         // Initialize count of current attachments
		$errors        = false;                                     // No errors/rejections yet
		$this->post_id = $post_id;                                  // Set post id for /ticket_nnnn folder creation

		$post = get_post($post_id);
        $this->parent_id = !empty($post->post_parent) ? $post->post_parent : false;

		foreach ( $attachments as $attachment ) {

			$filename = $attachment['filename'];                    // Base filename
			$data     = $attachment['data'];                        // Raw file contents

			/* Limit the number of uploaded files */
			if ( $cnt + 1 > $max ) {
				$errors[] = sprintf( __( '%s -> Max files (%d) exceeded.', 'awesome-support' ), $filename, $max );
				continue;
			}

			// Custom AS upload directory set in set_upload_dir() via upload_dir hook.
			$upload = wp_upload_bits( $filename, null, $data );

			if ( ! $upload['error'] ) {

				$attachment_data = array(
					'guid'           => $upload['url'],
					'post_mime_type' => $upload['type'],
					'post_parent'    => $post_id,
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);

				$attachment_id = wp_insert_attachment( $attachment_data, $upload['file'], $post_id );

				if ( is_wp_error( $attachment_id ) ) {

					$errors[] = sprintf( '%s -> %s', $filename, $attachment_id->get_error_message() );
					continue;

				} else {
					
					// Make sure a required function exists - for some reason
					// sometimes it does not, especially when called from our 
					// gravity forms add-on.
					if ( ! function_exists('wp_generate_attachment_metadata') ) {
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
					}					

					$attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );

					if ( ! empty( $attach_data ) ) {
						wp_update_attachment_metadata( $attachment_id, $attach_data );

					} else {
						$fileMeta = array(
							'file' => $upload['file'],
						);
						add_post_meta( $attachment_id, '_wp_attachment_metadata', $fileMeta );

					}
				}
			} else {
				$errors[] = sprintf( '%s -> %s', $filename, $upload['error'] );

			}

			$cnt ++;
		}

		// Log any errors
		if ( $errors ) {

			$log = __( 'Attachment Errors:', 'awesome-support' ) . '<br />';

			foreach ( $errors as $error ) {
				$log .= $error . '<br/>';
			}

			wpas_log_history( $this->parent_id ? $this->parent_id : $post_id, $log );

		}

	}

	/**
	 * Change the redirection URL.
	 *
	 * In case the upload fails we want to notify the user.
	 * We change the redirection URL and integrate a custom message
	 * encoded in base64 that will be interpreted by the notification class.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $location Original redirection URL
	 *
	 * @return string            New redirection URL
	 */
	public function redirect_error( $location ) {

		$url   = remove_query_arg( 'message', $location );
		$error = is_array( $this->error_message ) ? implode( ', ', $this->error_message ) : $this->error_message;

		wpas_add_error( 'files_not_uploaded', sprintf( __( 'Your reply has been correctly submitted but the attachment was not uploaded. %s', 'awesome-support' ), $error ) );

		$location = wp_sanitize_redirect( $url );

		return $location;
	}

	/**
	 * Limit upload filetypes.
	 *
	 * Gets the list of allowed file extensions from the plugin settings
	 * and compare the processed file. If the extension is not in the list we
	 * simply return an error message to prevent uploading it.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $file Currently processed file details
	 *
	 * @return array       File details with a possible error message
	 */
	public function limit_upload( $file ) {

		global $post;

		if ( empty( $post ) ) {
			$protocol = stripos( $_SERVER['SERVER_PROTOCOL'], 'https' ) === true ? 'https://' : 'http://';
			$post_id  = url_to_postid( $protocol . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'] );
			$post     = get_post( $post_id );
		}

		$submission = (int) wpas_get_option( 'ticket_submit' );
		$post_type  = filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING );

		/**
		 * On the front-end we only want to limit upload size
		 * on the submission page or on a ticket details page.
		 */
		if ( ! is_admin() ) {
			if ( ! empty( $post) && 'ticket' !== $post->post_type && $submission !== $post->ID ) {
				return $file;
			}
		}

		/**
		 * In the admin we only want to limit upload size on the ticket creation screen
		 * or on the ticket edit screen.
		 */
		if ( is_admin() ) {

			if ( ! isset( $post ) && empty( $post_type ) ) {
				return $file;
			}

			if ( isset( $post ) && 'ticket' !== $post->post_type ) {
				return $file;
			}

			if ( ! empty( $post_type ) && 'ticket' !== $post_type ) {
				return $file;
			}

		}

		$filetypes      = explode( ',', $this->get_allowed_filetypes() );
		$ext            = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$max_size       = wpas_get_option( 'filesize_max', 1 );
		$max_size_bytes = $max_size * 1024 * 1024;

		if ( ! in_array( $ext, $filetypes ) ) {
			$file['error'] = sprintf( __( 'You are not allowed to upload files of this type (%s)', 'awesome-support' ), $ext );
		}

		if ( $file['size'] <= 0 ) {
			$file['error'] = __( 'You cannot upload empty attachments. You attachments weights 0 bytes', 'awesome-support' );
		}

		if ( $file['size'] > $max_size_bytes ) {
			$file['error'] = sprintf( __( 'Your attachment is too big. You are allowed to attach files up to %s', 'awesome-support' ), "$max_size Mo" );
		}

		return $file;

	}

	/**
	 * Add the custom file types to the WordPress whitelist
	 *
	 * @since 3.2
	 *
	 * @param array $mimes Allowed mime types
	 *
	 * @return array Our custom mime types list
	 */
	public function custom_mime_types( $mimes ) {

		/* We don't want to allow those extra file types on other pages that the plugin ones */
		if ( ! wpas_is_plugin_page() ) {
			return $mimes;
		}

		$filetypes = explode( ',', $this->get_allowed_filetypes() );

		if ( ! empty( $filetypes ) ) {

			require_once( WPAS_PATH . 'includes/file-uploader/mime-types.php' );

			foreach ( $filetypes as $type ) {
				$mimes[ $type ] = wpas_get_mime_type( $type );
			}

		}

		return $mimes;

	}

	/**
	 * Individualize uploaded files.
	 *
	 * If multiple files were uploaded we need to separate each file
	 * in a separate array in the $_FILE array in order to let WordPress
	 * process them one by one.
	 *
	 * @since  3.0.0
	 * @return bool Whether or not files were individualized
	 */
	public function individualize_files() {

		$files_index = "wpas_$this->index"; // We need to prefix the index as the custom fields are always prefixed

		if ( ! is_array( $_FILES[ $files_index ]['name'] ) ) {
			return false;
		}

		foreach ( $_FILES[ $files_index ]['name'] as $id => $name ) {
			$index                    = $files_index . '_' . $id;
			$_FILES[ $index ]['name'] = $name;
		}

		foreach ( $_FILES[ $files_index ]['type'] as $id => $type ) {
			$index                    = $files_index . '_' . $id;
			$_FILES[ $index ]['type'] = $type;
		}

		foreach ( $_FILES[ $files_index ]['tmp_name'] as $id => $tmp_name ) {
			$index                        = $files_index . '_' . $id;
			$_FILES[ $index ]['tmp_name'] = $tmp_name;
		}

		foreach ( $_FILES[ $files_index ]['error'] as $id => $error ) {
			$index                     = $files_index . '_' . $id;
			$_FILES[ $index ]['error'] = $error;
		}

		foreach ( $_FILES[ $files_index ]['size'] as $id => $size ) {
			$index                    = $files_index . '_' . $id;
			$_FILES[ $index ]['size'] = $size;
		}

		return true;

	}

	/**
	 * Process upload on new ticket creation.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $ticket_id New ticket ID
	 *
	 * @return void
	 */
	public function new_ticket_attachment( $ticket_id ) {

		if ( isset( $_POST['wpas_title'] ) ) {
			$this->post_id = intval( $ticket_id );
			$this->process_upload();
		}
	}

	/**
	 * Process upload on new reply creation.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $reply_id New reply ID
	 *
	 * @return void
	 */
	public function new_reply_attachment( $reply_id ) {

		if ( ( isset( $_POST['wpas_nonce'] ) || isset( $_POST['client_reply'] ) ) || isset( $_POST['wpas_reply'] ) ) {
			$this->post_id   = intval( $reply_id );
			if( isset( $_POST['ticket_id'] ) ){
				$this->parent_id = intval( $_POST['ticket_id'] );
			}else{
				/**
				 * Ruleset bug fix on missing parent ID
				 * Get parent post ID from reply ID
				*/
				$this->parent_id = wp_get_post_parent_id( $reply_id );
			}
			$this->process_upload();
		}
	}

	/**
	 * Process upload on new reply creation.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $reply_id New reply ID
	 *
	 * @return void
	 */
	public function new_reply_backend_attachment( $reply_id ) {

		/* Are we in the right post type? */
		if ( ! isset( $_POST['post_type'] ) || 'ticket' !== $_POST['post_type'] ) {
			return;
		}

		if ( ! $this->can_attach_files() ) {
			return;
		}

		$this->post_id   = intval( $reply_id );
		$this->parent_id = intval( $_POST['wpas_post_parent'] );
		$this->process_upload();
	}

	/**
	 * Delete post attachments.
	 *
	 * Delete all post attachments if a ticket is deleted.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $post_id ID of the post to be deleted
	 *
	 * @return void
	 */
	public function delete_attachments( $post_id ) {

		$post = get_post( $post_id );
		if( empty( $post ) || 'ticket' !== $post->post_type ) {
		    return;
        }

		$this->post_id = $post_id;

		$attachments = $this->get_attachments( $post_id );

		if ( ! empty( $attachments ) ) {

			$args = array();

			// Remove attachment folder
			$upload = wp_get_upload_dir();

			if ( ! file_exists( $upload['path'] ) ) {
				return;
			}

			/**
			 * wpas_attachments_before_delete fires before deleting attachments
			 *
			 * @since  3.3.3
			 *
			 * @param  integer $post_id    ID of the post we're displaying attachments for
			 * @param  array   $attachment The attachment array
			 */
			do_action( 'wpas_attachments_before_delete', $post_id, $attachments, $args );

			foreach ( $attachments as $id => $attachment ) {
				wp_delete_attachment( $id, true );
			}

			$it    = new RecursiveDirectoryIterator( $upload['path'], RecursiveDirectoryIterator::SKIP_DOTS );
			$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

			foreach ( $files as $file ) {
				if ( $file->isDir() ) {
					rmdir( $file->getRealPath() );
				} else {
					unlink( $file->getRealPath() );
				}
			}
			rmdir( $upload['path'] );

			/**
			 * wpas_attachments_after_delete fires after deleting attachments
			 * to allow cleanup of attachment folders
			 *
			 * @since  3.3.3
			 *
			 * @param  integer $post_id    ID of the post we're displaying attachments for
			 * @param  array   $attachment The attachment array
			 */
			do_action( 'wpas_attachments_after_delete', $post_id, $attachments, $args );

		}

	}

	/**
	 * Load dropzone assets
	 */

	public function load_ajax_uploader_assets() {

		wp_register_style( 'wpas-dropzone', WPAS_URL . 'assets/admin/css/vendor/dropzone.css', null, WPAS_VERSION );
		wp_register_script( 'wpas-dropzone', WPAS_URL . 'assets/admin/js/vendor/dropzone.js', array( 'jquery' ), WPAS_VERSION );
		wp_register_script( 'wpas-ajax-upload', WPAS_URL . 'assets/admin/js/admin-ajax-upload.js', array( 'jquery' ), WPAS_VERSION, true );

		wp_enqueue_style( 'wpas-dropzone' );
		wp_enqueue_script( 'wpas-dropzone' );

		$filetypes = explode( ',', apply_filters( 'wpas_attachments_filetypes', wpas_get_option( 'attachments_filetypes' ) ) );
		$accept    = array();

		foreach ( $filetypes as $key => $type ) {
			array_push( $accept, ".$type" );
		}

		$accept = implode( ',', $accept );

		if ( ! $max_execution_time = ini_get('max_execution_time') ) {
			$max_execution_time = 30;
		}

		wp_localize_script( 'wpas-ajax-upload', 'WPAS_AJAX', array(
			'nonce'              => wp_create_nonce( 'wpas-gdpr-nonce' ),
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'accept'             => $accept,
			'max_execution_time' => ( $max_execution_time * 1000 ), // Convert to miliseconds
			'max_files'          => wpas_get_option( 'attachments_max' ),
			'max_size'           => wpas_get_option( 'filesize_max' ),
			'exceeded'           => sprintf( __( 'Max files (%s) exceeded.', 'awesome-support' ), wpas_get_option( 'attachments_max' ) )
		) );

		wp_enqueue_script( 'wpas-ajax-upload' );

	}


	/**
	 * Upload attachment using ajax
	 *
	 * @since  5.2.0
	 * 
	 * @return void
	 */
	public function ajax_upload_attachment() {

		if ( ! $this->can_attach_files() ) {
			return false;
		}

		$upload    = wp_upload_dir();
		$ticket_id = intval( $_POST[ 'ticket_id' ] );
		$user_id   = get_current_user_id();

		/**
		 * wpas_before_ajax_file_upload fires before uploading attachments
		 *
		 * @since 5.1.1
		 *
		 * @param int $ticket_id   ID of the ticket
		 * @param int $user_id     ID of the current logged in user
		 */
		do_action( 'wpas_before_ajax_file_upload', $ticket_id, $user_id );

		
		$dir = trailingslashit( $upload['basedir'] ) . 'awesome-support/temp_' . $ticket_id . '_' . $user_id;

		// Create temp directory if not exists
		if ( ! is_dir( $dir ) ) {
			$this->create_upload_dir( $dir );
		}

		// Check if file is set
		if ( ! empty( $file = $_FILES[ 'wpas_' . $this->index ] ) ) {
			// Get file extension
			$extension = pathinfo( $file[ 'name' ], PATHINFO_EXTENSION );
			// Get allowed file extensions
			$filetypes = explode( ',', apply_filters( 'wpas_attachments_filetypes', wpas_get_option( 'attachments_filetypes' ) ) );

			// Check file extension
			if ( in_array( $extension, $filetypes ) ) {
				// Upload file
				move_uploaded_file( $file[ 'tmp_name' ], trailingslashit( $dir ) . basename( $file[ 'name' ] ) );
			}

		}
		
		wp_die();

	}


	/**
	 * Delete temporary attachment using ajax
	 *
	 * @since  5.2.0
	 * 
	 * @return void
	 */
	public function ajax_delete_temp_attachment() {	
		
		if ( wpas_can_delete_attachments() ) {

			$ticket_id  = filter_input( INPUT_POST, 'ticket_id', FILTER_SANITIZE_NUMBER_INT );
			$attachment = filter_input( INPUT_POST, 'attachment', FILTER_SANITIZE_STRING );
			$upload     = wp_upload_dir();
			$user_id    = get_current_user_id();

			$file = sprintf( '%s/awesome-support/temp_%d_%d/%s', $upload['basedir'], $ticket_id, $user_id, $attachment );

			/**
			 * wpas_before_delete_temp_attachment fires before deleting temp attachment
			 *
			 * @since 5.1.1
			 *
			 * @param int $ticket_id     ID of the ticket
			 * @param int $user_id       ID of the current logged in user
			 * @param string $attachment Attachment filename
			 */
			do_action( 'wpas_before_delete_temp_attachment', $ticket_id, $user_id, $attachment );

			if ( file_exists( $file ) ) {
				unlink( $file );
			}
			
		}

		wp_die();

	}

	/**
	 * Delete temporary attachment folder
	 *
	 * @since  5.2.0
	 * 
	 * @return void
	 */
	public function ajax_delete_temp_directory() {	
	
		$upload     = wp_upload_dir();
		$temp_dir   = sprintf( '%s/awesome-support/temp_%d_%d', $upload['basedir'], intval( $_POST[ 'ticket_id' ] ), get_current_user_id() );

		if ( is_dir( $temp_dir ) ) {
			$this->remove_directory( $temp_dir );
		}

		wp_die();

	}

	/**
	 * Process attachments uploaded via ajax for new tickets
	 *
	 * @param int $ticket_id
	 * @param array $data
	 * 
	 * @since  5.2.0
	 * 
	 * @return void
	 */
	public function new_ticket_ajax_attachments( $ticket_id, $data ) {
		if( isset( $_POST['ticket_id'] ) ){
			$submission_ticket_id = intval( $_POST['ticket_id'] );
		} else {
			return;
		}
		$this->process_ajax_upload($submission_ticket_id, $ticket_id, $data);
	}
	
	/**
	 * Process attachments uploaded via ajax for new replies
	 *
	 * @param int $reply_id
	 * @param array $data
	 * 
	 * @since  5.2.0
	 * 
	 * @return void
	 */
	public function new_reply_ajax_attachments( $reply_id, $data ) {
		$this->process_ajax_upload($data[ 'post_parent' ], $reply_id, $data);
	}
	
	/**
	 * Process attachments uploaded via ajax
	 *
	 * @param int $ticket_id
	 * @param int $reply_id
	 * @param array $data
	 * 
	 * @since  5.2.0
	 * 
	 * @return void
	 */
	public function process_ajax_upload($ticket_id, $reply_id, $data ) {

		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . 'awesome-support/temp_' . $ticket_id . '_' . $data['post_author'] .'/';

		// If temp directory exists, it means that user is uploaded attachments
		if ( is_dir( $dir ) ) {

			$filetypes = explode( ',', apply_filters( 'wpas_attachments_filetypes', wpas_get_option( 'attachments_filetypes' ) ) );
			$accept    = array();
	
			foreach ( $filetypes as $key => $type ) {
				array_push( $accept, '*.' . $type );
			}
	
			$accept = implode( ',', $accept );

			foreach( glob( $dir . '{' . $accept . '}', GLOB_BRACE ) as $file ) {
				
				$new_file_relative_dir = 'awesome-support/ticket_' . $reply_id;
				$new_file_relative = $new_file_relative_dir . '/' . basename( $file );

				$new_file_url = trailingslashit( $upload['baseurl'] ) . $new_file_relative;
				
				// Prepare an array of post data for the attachment.
				$attachment = array(
					'guid'           => $new_file_url, 
					'post_mime_type' => mime_content_type( $file ),
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file ) ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
				
				// Insert the attachment.
				$attachment_id = wp_insert_attachment( $attachment, $file, $reply_id );
				
				if ( is_wp_error( $attachment_id ) ) {

					$errors[] = sprintf( '%s -> %s', $file, $attachment_id->get_error_message() );
					continue;

				} else {
					
					$new_file_upload_dir = trailingslashit( $upload['basedir'] ) . $new_file_relative_dir;
					$new_file_upload = $new_file_upload_dir . '/' . basename( $file );
				
					// Create ticket attachment directory if not exists
					if ( ! file_exists( $new_file_upload_dir ) ) {
						$this->create_upload_dir( $new_file_upload_dir );
					}

					// Move file from temp dir to ticket dir
					rename( $file,  $new_file_upload);

					// Update attached file post meta data
					update_attached_file($attachment_id, $new_file_relative);
					
					// Generate and update attachment metadata
					$attach_data = wp_generate_attachment_metadata( $attachment_id, $new_file_upload );

					if ( ! empty( $attach_data ) ) {
						
						wp_update_attachment_metadata( $attachment_id, $attach_data );

					} else {
						$fileMeta = array(
							'file' => $new_file_upload,
						);
						add_post_meta( $attachment_id, '_wp_attachment_metadata', $fileMeta );

					}
				}

			} 

			// Remove directory
			$this->remove_directory( $dir );

		}

	}

	/**
	 * Schedule cleanup of unused attachments dir 
	 * 
	 * @since  5.2.0
	 *
	 * @return void
	 */
	public function attachments_dir_cleanup_schedule() {

		if ( ! wp_next_scheduled( 'attachments_dir_cleanup_action' ) ) {
			wp_schedule_event( time(), 'daily', 'attachments_dir_cleanup_action');
		}

	}

	/**
	 * Attachments dir cleanup action.
	 * Removes temporary attachment folders
	 * 
	 * @since  5.1.1
	 *
	 * @return void
	 */
	public function attachments_dir_cleanup() {

		$upload  = wp_get_upload_dir();
		$folders = glob( trailingslashit( $upload['basedir'] ) . 'awesome-support/temp_*' );
	
		foreach ( $folders as $folder ) {

			$mtime = filemtime( $folder );

			if ( ( time() - $mtime ) > 60 * 60 * 24 ) { // Delete temp folder after 24 hours
				$this->remove_directory( $folder );
			}

		}

	}

	/**
	 * Remove directory
	 * 
	 * @since  5.2.0
	 *
	 * @return void
	 */
	public function remove_directory( $directory ) {

		if ( ! is_dir( $directory ) ) {
			return false;
		}

		$it    = new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS );
		$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				rmdir( $file->getRealPath() );
			} else {
				unlink( $file->getRealPath() );
			}
		}

		rmdir( $directory );

	}

}
