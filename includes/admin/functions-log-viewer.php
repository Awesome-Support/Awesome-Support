<?php
/**
 * Created by PhpStorm.
 * User: Robert
 * Date: 6/5/2017
 * Time: 8:34 PM
 */

/*
 * Actions
 * --------------------------------
 * wpas_tools_log_viewer_view
 * wpas_tools_log_viewer_download
 * wpas_tools_log_viewer_delete
 *
 */

function get_logs_path() {
	return WPAS_PATH  . 'logs/';
}

function get_logs_url() {
	return WPAS_URL  . 'logs/';
}

/*
 * AJAX handler function
 */
function wpas_tools_log_viewer_view() {

	if( ! isset( $_POST[ 'file' ] ) ) {
		wp_send_json_error( array( 'error' => esc_html__( 'No file given', 'awesome-support' ) ) );
	}

	// Default number of lines to return
	$lines = 100;

	// Get posted number of lines
	if( isset( $_POST[ 'lines' ] ) ) {
		$lines = $_POST[ 'lines' ];
	}

	wp_send_json_success( wpas_log_viewer_read_last_lines( $_POST[ 'file' ], $lines ) );

}
add_action( 'wp_ajax_wpas_tools_log_viewer_view', 'wpas_tools_log_viewer_view', 10, 0 );


/*
 * AJAX handler function
 */
function wpas_tools_log_viewer_download() {

	if( ! isset( $_POST[ 'file' ] ) ) {
		wp_send_json_error( array( 'error' => esc_html__( 'No file given', 'awesome-support' ) ) );
	}

	$file = $_POST[ 'file' ];

	$content = array(
		'status' => array(
			'code' => '200',
		    'message' => 'Downloading...'
		),
		'url' => get_logs_url() . $file
	);

	wp_send_json_success( $content );
}

add_action( 'wp_ajax_wpas_tools_log_viewer_download', 'wpas_tools_log_viewer_download', 10, 0 );


/*
 * AJAX handler function
 */
function wpas_tools_log_viewer_delete() {

	if( ! isset( $_POST[ 'file' ] ) ) {
		echo json_encode( array( 'error' => esc_html__( 'No file given', 'awesome-support' ) ) );
		wp_die();
	}

	$file = $_POST[ 'file' ];

	wp_send_json_success(	wpas_log_viewer_delete_file( $file ) );

}

add_action( 'wp_ajax_wpas_tools_log_viewer_delete', 'wpas_tools_log_viewer_delete', 10, 0 );


/**
 * @param $file
 */
function wpas_log_viewer_delete_file( $file ) {

	if( unlink( get_logs_path() . $file ) ) {
		$code = '200';
		$content = "Deleted " . $file . " successfully.";
	}
	else {
		$code = '404';
		$content = "Delete " . $file . " failed.";
	}

	$json = array(
		'status' => array(
			'code' => $code,
		    'message' => $content
		),
		'url' => get_logs_url() . $file
	);

	wp_send_json_success( $json );

}


/* Function read X last lines from file*/
function wpas_log_viewer_read_last_lines( $file, $lines ) {

	if( 'All' === $lines ) {
		return wpas_log_viewer_read_full_file( $file );
	}

	$result = [];
	$file_path   = get_logs_path() . $file;

	$handle = @fopen( $file_path, "r" );
	if( ! empty( $handle ) ) {
		$linecounter = $lines;
		$pos         = - 2;
		$beginning   = false;
		$text        = array();
		while ( $linecounter > 0 ) {
			$t = "";
			while ( $t != "\n" ) {
				if( fseek( $handle, $pos, SEEK_END ) == - 1 ) {
					$beginning = true;
					break;
				}
				$t = fgetc( $handle );
				$pos --;
			}
			$linecounter --;
			if( $beginning ) {
				//rewind( $handle );
			}
			$text[ $lines - $linecounter - 1 ] = fgets( $handle );
			if( $beginning ) {
				break;
			}
		}
		fclose( $handle );
		foreach( $text as $line ) {
			$result[] = $line;
		}

		return array(
			'status' => array(
				'code'    => '200',
				//'message' => '',
				'message' => sprintf(__( "Read %d lines from %s", 'awesome-support' ), count($result), esc_html( $file )),
			),
			'data'   => $result,
		);
	}
	else {
		//return printf( __( "Couldn't open the file %s. Make sure file is exists or is readable.", 'error-log-viewer' ), esc_html( $file ) );
		return array(
			'status' => array(
				'code'    => '404',
				'message' => sprintf(__( "Couldn't open the file %s. Make sure the file exists and is readable.", 'awesome-support' ), esc_html( $file )),
			),
			'data'   => [],
		);
	}

}


/* Function read full file */
function wpas_log_viewer_read_full_file( $file ) {

	$file_path = get_logs_path() . $file;

	$handle = @fopen( $file_path, 'r' );
	$result = [];
	if( ! empty( $handle ) ) {
		while ( ! feof( $handle ) ) {
			$line     = fgets( $handle );
			$result[] = $line;
		}
		fclose( $handle );

		return array(
			'status' => array(
				'code'    => '200',
				//'message' => '',
				'message' => sprintf(__( "Read %d lines from %s", 'awesome-support' ), count($result), esc_html( $file )),
			),
			'data'   => $result,
		);
	}
	else {
		//return printf( __( "Couldn't open the file %s. Make sure file is exists or is readable.", 'error-log-viewer' ), esc_html( $file ) );
		return array(
			'status' => array(
				'code'    => '404',
				'message' => sprintf(__( "Couldn't open the file %s. Make sure the file exists and is readable.", 'awesome-support' ), esc_html( $file )),
			),
			'data'   => [],
		);
	}
}

