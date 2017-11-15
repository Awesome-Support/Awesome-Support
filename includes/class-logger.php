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
		$file = $this->open();
		
		if ( is_resource ( $file ) ) {
			
			@fclose( $file );
			
		} else {
			
			// If we get here it means we don't have an actual file handle/resource.
			// Take what we have and attempt to close anyway. Just in case.
			// An error will be thrown if parameters are not compatible!
			@fclose( escapeshellarg( $file ) );
			
		}
	}

	public function get_handles() {
		return apply_filters( 'wpas_logs_handles', $this->handles );
	}

	public function get_logs_path() {
		
		$path_postfix = '' ;  // the last part of the default logs path name.  Will be blank if not on multi-site.
		if ( true == is_multisite() ) {
			$path_postfix = '/site' . (string) get_current_blog_id();
		}

		$path = apply_filters( 'wpas_logs_path', WPAS_PATH . 'logs' . $path_postfix, $this->handle );

		if ( !is_dir( $path ) ) {
			$dir = mkdir( $path );
			if ( !$dir ) {
				return false;
			}
		}

		return $path;
	}

	/**
	 * Get the file path for the current log file.
	 *
	 * @since  3.0.2
	 * @return mixed Path if the log file exists, false otherwise
	 */
	public function get_log_file_path() {

		$path = $this->get_logs_path();
		if ( !$path ) {
			return false;
		}

		$file = trailingslashit( $path ) . "log-$this->handle.txt";

		if ( !file_exists( $file ) ) {
			
			$handle = fopen( $file, 'a' );
			
			if ( is_resource( $handle ) ) {
				fclose( $handle );
			}

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
		$file = fopen( $this->get_log_file_path(), 'a' );
		return $file;
	}


	/**
	 * Add a log entry to chosen file.
	 *
	 * @since  3.0.2
	 * @return void
	 */
	public function add( $message ) {
		$file = $this->open();
		if ( $file && is_resource( $file ) ) {
			$time = date_i18n( 'm-d-Y @ H:i:s -' ); // Grab Time
			@fwrite( $file, $time . " " . sanitize_text_field( $message ) . "\n" );
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