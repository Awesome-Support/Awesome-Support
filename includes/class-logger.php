<?php
/**
 * Allows log files to be written to for debugging purposes.
 *
 * @package		Awesome Support/WPAS_Logger
 */
class WPAS_Logger {

	/**
	 * List of registered handles.
	 * 
	 * @var array
	 */
	private $handles;

	/**
	 * Handle of the log to write.
	 * 
	 * @var string
	 */
	private $handle;

	/**
	 * Constructor for the logger.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $handle ) {

		$this->handles = array();
		$this->handle  = sanitize_file_name( $handle );

		if ( !in_array( $this->handle, $this->get_handles() ) ) {
			return false;
		}

	}


	/**
	 * Destructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		global $wp_filesystem;
	
		// Initialize WP_Filesystem if not already initialized
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		$file = $this->open();
	
		if ( $file && $wp_filesystem->exists( $file ) ) {
			// No need to close a file, as WP_Filesystem does not use file handles.
			// Just ensure the file exists and log an appropriate message if needed.
		} else {
			error_log( "Invalid file reference in __destruct(): " . esc_html( $file ) );
		}
	}
	

	public function get_handles() {
		return apply_filters( 'wpas_logs_handles', $this->handles );
	}
	
	/**
	 * Get the folder where log files are written.
	 *
	 * @since  3.0.2
	 * @return mixed Path if the log file exists, false otherwise
	 */	
	public function get_logs_path() {
		
		/* Figure out which base path to use */
		$base_path = $this->get_logs_base_path();

		$path = apply_filters( 'wpas_logs_path', $base_path, $this->handle );

		if ( !is_dir( $path ) ) {
			$dir = wp_mkdir_p( $path );
			if ( !$dir ) {
				return false;
			}
		}
		return $path;
	}
	
	/**
	 * Return the base path to use for all log files. 
	 * This is determined based on the option set by the user in TICKETS->SETTINGS->ADVANCED
	 *
	 * @since  4.3.6
	 * @return string Base path
	 */	
	public function get_logs_base_path() {

		$base_path = '' ;
		switch ( intval( wpas_get_option( 'log_file_location', 0 ) ) ) {
			case 0:
				// use default path
				$base_path = WPAS_PATH . 'logs' . $this->get_logs_base_path_postfix() ;
				break ;
				
			case 1:
				// use normal uploads folder
				$uploads = wp_upload_dir() ;

				if ( isset( $uploads['basedir'] ) ) {
					$base_path = $uploads['basedir'] . '/awesome-support/logs' ;
				}
				break ;
				
			case 2:
				// use absolute folder provided...
				// Do not set if running in SAAS mode though - just in case the admin of a sub-site
				// decides to do something stupid.  
				if ( ! is_saas() ) {
					$base_path = wpas_get_option ( 'log_file_location_absolute' , '' ) ;
				}
				break ;

		}
		
		if ( empty( $base_path ) ) {
			// for some reason WP didnt' return anything in the array above so go back to default...
			$base_path = WPAS_PATH . 'logs' . $this->get_logs_base_path_postfix() ;
		}

		return $base_path;
		
	}
	
	/**
	 * Return the string that will be appended to the end of the log files base path. 
	 * This is usually blank but will no tbe blank if running multisite.
	 *
	 * @since  4.3.6
	 * @return string Base path postfix
	 */		
	public function get_logs_base_path_postfix() {

		/* Set the last part of the default logs path name.  Will be blank if not on multi-site. */
		$path_postfix = '' ;  
		if ( true == is_multisite() ) {
			$path_postfix = '/site' . (string) get_current_blog_id();
		}		
		
		return $path_postfix ;
	}

	/**
	 * Get the file path for the current log file.
	 *
	 * @since  3.0.2
	 * @return mixed Path if the log file exists, false otherwise
	 */
	public function get_log_file_path() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		$path = $this->get_logs_path();
		if ( ! $path ) {
			return false;
		}
		$file = trailingslashit( $path ) . "log-$this->handle.txt";
	
		if ( ! $wp_filesystem->exists( $file ) ) {
			$wp_filesystem->put_contents( $file, '', FS_CHMOD_FILE ); // Create an empty file
		}
	
		return $file;

	}


	/**
	 * Open log file for writing.
	 *
	 * @since   3.0.2
	 * @return  mixed Resource on success
	 */
	private function open() {
		global $wp_filesystem;

		// Initialize WP_Filesystem if not already initialized
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		// Get the log file path
		$file = $this->get_log_file_path();
	
		// Ensure the file exists, and if not, create it
		if ( ! $wp_filesystem->exists( $file ) ) {
			$wp_filesystem->put_contents( $file, '', FS_CHMOD_FILE ); // Create an empty file if it doesn't exist
		}
	
		// Return the file path, as WP_Filesystem works with file contents, not file handles
		return $file;
	}


	/**
	 * Add a log entry to chosen file.
	 *
	 * @since  3.0.2
	 * @return void
	 */
	public function add( $message ) {
		global $wp_filesystem;
	
		// Initialize WP_Filesystem if not already initialized
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		
		// Get the log file path
		$file = $this->open();
	
		if ( $file ) {
			// Get the existing content of the log file, if any
			$existing_content = $wp_filesystem->exists( $file ) ? $wp_filesystem->get_contents( $file ) : '';
	
			// Format the new message with the current time
			$time = date_i18n( 'm-d-Y @ H:i:s -' ); // Grab Time
			$new_message = $time . " " . sanitize_text_field( $message ) . "\n";
	
			// Append the new message to the existing content
			$updated_content = $existing_content . $new_message;
	
			// Write the updated content back to the file
			$wp_filesystem->put_contents( $file, $updated_content, FS_CHMOD_FILE );
		}
	}


	/**
	 * Clear entries from chosen file.
	 *
	 * @since  3.0.2
	 * @return void
	 */
	public function clear( $handle ) {
		$file = $this->open();
		if ( $file && is_resource( $file ) ) {
			@ftruncate( $file, 0 );
		}
	}

}