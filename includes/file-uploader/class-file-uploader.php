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

			add_action( 'wpas_open_ticket_after', array( $this, 'new_ticket_attachment' ), 10, 2 ); // Save attachments after user opened a new ticket
			add_action( 'wpas_add_reply_public_after', array( $this, 'new_reply_attachment' ), 10, 2 );  // Save attachments after user submitted a new reply
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

		$clauses['join'] .= " LEFT OUTER JOIN $wpdb->posts daddy ON daddy.ID = $wpdb->posts.post_parent";
		$clauses['where'] .= " AND ( daddy.post_type NOT IN ( 'ticket', 'ticket_reply' ) OR daddy.ID IS NULL )";

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

			$filename = basename( $attachment->guid );

			ini_set( 'user_agent', 'Awesome Support/' . WPAS_VERSION . '; ' . get_bloginfo( 'url' ) );
			header( "Content-Type: $attachment->post_mime_type" );
			header( "Content-Disposition: inline; filename=\"$filename\"" );
			readfile( $attachment->guid );

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
							<li><a href="<?php echo $link; ?>" target="_blank"><?php echo $name; ?></a> <?php echo $filesize; ?></li><?php

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

			$max = wpas_get_option( 'attachments_max' );
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

		$max           = wpas_get_option( 'attachments_max' );      // Core AS Max Files (File Upload settings)
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

			wpas_log( $this->parent_id ? $this->parent_id : $post_id, $log );

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

}
