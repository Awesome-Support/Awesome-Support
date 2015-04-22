<?php
/**
 * Awesome Support File Uploader.
 *
 * @package   Awesome_Support
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

/**
 * Instantiate the file upload class.
 */
add_action( 'plugins_loaded', array( 'WPAS_File_Upload', 'get_instance' ) );

class WPAS_File_Upload {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	protected $post_id = null;
	protected $parent_id = null;
	protected $index = 'wpas_files';

	public function __construct() {

		/**
		 * Load the addon settings
		 */
		require_once( WPAS_PATH . 'includes/addons/file-uploader/settings-file-upload.php' );

		if ( !$this->can_attach_files() ) {
			return;
		}

		add_filter( 'upload_dir',                 array( $this, 'set_upload_dir' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'limit_upload' ), 10, 1 );

		if ( !is_admin() ) {

			/* Load media uploader related files. */
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/template.php' );

			add_action( 'wpas_open_ticket_after',                    array( $this, 'new_ticket_attachment' ), 10, 2 ); // Save attachments after user opened a new ticket
			add_action( 'wpas_add_reply_public_after',               array( $this, 'new_reply_attachment' ), 10, 2 );  // Save attachments after user submitted a new reply
			add_action( 'wpas_submission_form_inside_before_submit', array( $this, 'upload_field' ) );                  // Load the dropzone after description textarea
			add_action( 'wpas_ticket_details_reply_textarea_after',  array( $this, 'upload_field' ) );                  // Load dropzone after reply textarea
			add_action( 'wpas_frontend_ticket_content_after',        array( $this, 'show_attachments' ), 10, 1 );
			add_action( 'wpas_frontend_reply_content_after',         array( $this, 'show_attachments' ), 10, 1 );

		}

		if ( is_admin() ) {
			add_action( 'wpas_add_reply_admin_after',        array( $this, 'new_reply_backend_attachment' ), 10, 2 );
			add_action( 'post_edit_form_tag',                array( $this, 'add_form_enctype' ), 10, 1 );
			add_action( 'wpas_admin_after_wysiwyg',          array( $this, 'upload_field' ), 10, 0 );
			add_action( 'before_delete_post',                array( $this, 'delete_attachments' ), 10, 1 );
			add_action( 'wpas_backend_ticket_content_after', array( $this, 'show_attachments' ), 10, 1 );
			add_action( 'wpas_backend_reply_content_after',  array( $this, 'show_attachments' ), 10, 1 );
		}
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
	 * Check if the current user can attach a file.
	 *
	 * @since  3.0.0
	 * @return boolean True if the user has the capability, false otherwise
	 */
	public function can_attach_files() {

		if ( false === boolval( wpas_get_option( 'enable_attachments' ) ) ) {
			return false;
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

		/* On the front-end, we make sure that a new ticket or a reply is being submitted */
		if ( !is_admin() ) {
			if ( !isset( $_POST['wpas_title'] ) && !isset( $_POST['wpas_user_reply'] ) ) {
				return $upload;
			}
		}

		if ( is_admin() ) {

			/* Are we in the right post type? */
			if ( !isset( $_POST['post_type'] ) || 'ticket' !== $_POST['post_type'] )
				return $upload;

			if ( !isset( $_POST['wpas_reply'] ) )
				return $upload;

		}

		if ( ! $this->can_attach_files() ) {
			return $upload;
		}

		/* Get the ticket ID */
		$ticket_id = !is_null( $this->parent_id ) ? $this->parent_id : $this->post_id;

		/* We sort the uploads in sub-folders per ticket. */
		$subdir = "/awesome-support/ticket_$ticket_id";

		/* Create final URL and dir */
		$dir = $upload['basedir'] . $subdir;
		$url = $upload['baseurl'] . $subdir;

		/* Update upload params */
		$upload['path']    = $dir;
		$upload['url']     = $url;
		$upload['subdir']  = $subdir;

		/* Create the directory if it doesn't exist yet, make sure it's protected otherwise */
		if ( ! is_dir( $dir ) ) {
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

		$make = mkdir( $dir );

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

		$filename = $dir . '/.htaccess';

		if ( ! file_exists( $filename ) ) {
			$file = fopen( $filename, 'a+' );
			fwrite( $file, 'Options -Indexes' );
			fclose( $file );
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
		foreach ( $filetypes as $key => $type ) { $filetypes[$key] = "<code>.$type</code>"; }
		$filetypes = implode( ', ', $filetypes );
		?>

		<div class="wpas-form-group wpas-attachment-container">
			<label for="wpas-file-upload"><?php _e( 'Attachments', 'wpas' ); ?></label>
			<input type="file" name="<?php echo $this->index; ?>[]" id="wpas-file-upload" class="wpas-form-control" multiple>
			<p class="wpas-help-block"><?php printf( __( ' You can upload up to %s files of the following types: %s', 'wpas' ), wpas_get_option( 'attachments_max' ), $filetypes ); ?></p>
		</div>

	<?php }

	/**
	 * Get post attachments.
	 *
	 * Get the attachments for a specific ticket or reply.
	 *
	 * @since  3.0.0
	 * @param  integer $post_id ID of the post to get attachment for
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
			'posts_per_page'         => -1,
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
			$list[$attachment->ID] = array( 'name' => $attachment->post_title, 'url' => $attachment->guid );
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
	 * @param  integer $post_id ID of the post to get attachment for
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
	 * @param  integer $post_id ID of the post to get attachment for
	 * @return void
	 */
	public function show_attachments( $post_id ) {

		$attachments = $this->get_attachments( $post_id );

		if ( empty( $attachments ) ) {
			return false;
		} ?>

		<div class="wpas-reply-attachements">
			<strong><?php _e( 'Attachments:', 'wpas' ); ?></strong>
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
						$filename   = $name = $filename[count($filename)-1];
						$upload_dir = wp_upload_dir();
						$filepath   = trailingslashit( $upload_dir['basedir'] ) . "awesome-support/ticket_$post_id/$filename";
						$filesize   = file_exists( $filepath ) ? $this->human_filesize( filesize( $filepath ), 0 ) : '';

						?><li><a href="<?php echo $attachment['url']; ?>" target="_blank"><?php echo $name; ?></a> <?php echo $filesize; ?></li><?php

					}

					/**
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
						 * @param  integer $attachment_id ID of this attachment
						 * @param  array   $attachment    The attachment array
						 * @param  integer $post_id       ID of the post we're displaying attachments for
						 */
						do_action( 'wpas_attachment_display_' . $source, $attachment_id, $attachment, $metadata, $post_id );

					}
					
				endforeach; ?>
			</ul>
		</div>
	<?php }

	/**
	 * Human readable filesize.
	 *
	 * Transform the file size into a readable format including
	 * the size unit.
	 *
	 * @since  3.0.0
	 * @param  integer  $bytes    Filesize in bytes
	 * @param  integer  $decimals Number of decimals to show
	 * @return string             Human readable filesize
	 * @link   http://php.net/manual/en/function.filesize.php#106569
	 */
	public function human_filesize( $bytes, $decimals = 2 ) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
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
	 * @return void
	 */
	public function process_upload() {
		
		/* We have a submission with a $_FILES var set */
		if ( $_POST && $_FILES && isset( $_FILES[$this->index] ) ) {

			if ( empty( $_FILES[$this->index]['name'][0] ) ) {
				return false;
			}

			$max = wpas_get_option( 'attachments_max' );
			
			if ( $this->individualize_files() ) {

				for ( $i = 0; isset( $_FILES["{$this->index}_$i"] ); ++$i ) {

					/* Limit the number of uploaded files */
					if ( $i+1 > $max ) {
						break;
					}

					$id = media_handle_upload( "{$this->index}_$i", $this->post_id );
				}

			} else {
				$id = media_handle_upload( $this->index, $this->post_id );
			}

			if ( is_wp_error( $id ) ) {

				$this->error_message = $id->get_error_message();
				add_filter( 'wpas_redirect_reply_added', array( $this, 'redirect_error' ), 10, 2 );

				return false;

			} else {
				return true;
			}

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
	 * @param  string  $location Original redirection URL
	 * @param  integer $post_id  ID of the post to redirect to
	 * @return string            New redirection URL
	 */
	public function redirect_error( $location, $post_id ) {

		$url      = remove_query_arg( 'message', $location );
		$message  = wpas_create_notification( sprintf( __( 'Your reply has been correctly submitted but the attachment was not uploaded. %s', 'wpas' ), $this->error_message ) );
		$location = add_query_arg( array( 'message' => $message ), $url );

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
	 * @param  array $file Currently processed file details
	 * @return array       File details with a possible error message
	 */
	public function limit_upload( $file ) {

		global $post;

		$submission = (int) wpas_get_option( 'ticket_submit' );
		$post_type  = filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING );

		/**
		 * On the front-end we only want to limit upload size
		 * on the submission page or on a ticket details page.
		 */
		if ( ! is_admin() ) {
			if ( 'ticket' !== $post->post_type && $submission !== $post->ID ) {
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

		if ( !in_array( $ext, $filetypes ) ) {
			$file['error'] = sprintf( __( 'You are not allowed to upload files of this type (%s)', 'wpas' ), $ext );
		}

		if ( $file['size'] <= 0 ) {
			$file['error'] = __( 'You cannot upload empty attachments. You attachments weights 0 bytes', 'wpas' );
		}

		if ( $file['size'] > $max_size_bytes ) {
			$file['error'] = sprintf( __( 'Your attachment is too big. You are allowed to attach files up to %s', 'wpas' ), "$max_size Mo" );
		}
		
		return $file;

	}

	/**
	 * Individualize uploaded files.
	 *
	 * If multiple files were uploaded we need to separate each file
	 * in a separate array in the $_FILE array in order to let WordPress
	 * process them one by one.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function individualize_files() {

		if ( !is_array( $_FILES[$this->index]['name'] ) ) {
			return false;
		}

		foreach ( $_FILES[$this->index]['name'] as $id => $name ) {
			$index = $this->index . '_' . $id;
			$_FILES[$index]['name'] = $name;
		}

		foreach ( $_FILES[$this->index]['type'] as $id => $type ) {
			$index = $this->index . '_' . $id;
			$_FILES[$index]['type'] = $type;
		}

		foreach ( $_FILES[$this->index]['tmp_name'] as $id => $tmp_name ) {
			$index = $this->index . '_' . $id;
			$_FILES[$index]['tmp_name'] = $tmp_name;
		}

		foreach ( $_FILES[$this->index]['error'] as $id => $error ) {
			$index = $this->index . '_' . $id;
			$_FILES[$index]['error'] = $error;
		}

		foreach ( $_FILES[$this->index]['size'] as $id => $size ) {
			$index = $this->index . '_' . $id;
			$_FILES[$index]['size'] = $size;
		}

		// print_r( $_POST ); print_r( $_FILES ); exit;

		return true;

	}

	/**
	 * Process upload on new ticket creation.
	 *
	 * @since  3.0.0
	 * @param  integer $ticket_id New ticket ID
	 * @param  array   $data      The newly created ticket's data
	 * @return void
	 */
	public function new_ticket_attachment( $ticket_id, $data ) {

		if ( isset( $_POST['wpas_title'] ) ) {
			$this->post_id = intval( $ticket_id );
			$this->process_upload();
		}
	}

	/**
	 * Process upload on new reply creation.
	 *
	 * @since  3.0.0
	 * @param  integer $ticket_id New reply ID
	 * @param  array   $data      The newly created reply's data
	 * @return void
	 */
	public function new_reply_attachment( $reply_id, $data ) {

		if ( ( isset( $_POST['wpas_nonce'] ) || isset( $_POST['client_reply'] ) ) || isset( $_POST['wpas_reply'] ) ) {
			$this->post_id   = intval( $reply_id );
			$this->parent_id = intval( $_POST['ticket_id'] );
			$this->process_upload();
		}
	}

	/**
	 * Process upload on new reply creation.
	 *
	 * @since  3.0.0
	 * @param  integer $ticket_id New reply ID
	 * @param  array   $data      The newly created reply's data
	 * @return void
	 */
	public function new_reply_backend_attachment( $reply_id, $data ) {

		/* Are we in the right post type? */
		if ( !isset( $_POST['post_type'] ) || 'ticket' !== $_POST['post_type'] )
			return;

		if ( !$this->can_attach_files() )
			return;

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
	 * @param  integer $post_id ID of the post to be deleted
	 * @return void
	 */
	public function delete_attachments( $post_id ) {

		global $post_type;

		if ( 'ticket' !== $post_type ) {
			return;
		}

		$attachments = $this->get_attachments( $post_id );

		if ( !empty( $attachments ) ) {
			foreach ( $attachments as $id => $attachment ) {
				wp_delete_attachment( $id, true );
			}
		}

	}

}