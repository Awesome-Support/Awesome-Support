<?php
/**
 * Created by PhpStorm.
 * User: Robert
 * Date: 6/5/2017
 * Time: 8:34 PM
 */

/*
log viewer - just a couple of minor changes:
1. Don't show the logs folder or show it at the bottom of the screen.
2. Make the main background color gray and the viewer itself black text on white background.
3. Remove the horizontal lines across the top - looks too busy with them there. This is a simple interface so lets make it less busy by doing that.
4. If possible, add an option to turn off wordwrap.

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

	$log = new WPAS_Logger( '' );
	$base_path = $log->get_logs_base_path() . '/';

	return $base_path;

}

function get_logs_url() {
	return WPAS_URL  . 'logs/';
}

/*
 * AJAX handler function
 */
function wpas_tools_log_viewer_view() {

	if ( ! current_user_can( 'administrator' ) ) {
		wp_send_json_error( array( 'error' => esc_html__( 'Not found', 'awesome-support' ) ) );
	}

	check_ajax_referer( 'wpas_tools_log_viewer_view', 'nonce' );

	if( ! isset( $_POST[ 'file' ] ) ) {
		wp_send_json_error( array( 'error' => esc_html__( 'No file given', 'awesome-support' ) ) );
	}

	// Default number of lines to return
	$lines = 100;
	$file  = basename( $_POST[ 'file' ] );

	// Get posted number of lines
	if( isset( $_POST[ 'lines' ] ) ) {
		$lines = $_POST[ 'lines' ];
	}

	wp_send_json_success( wpas_log_viewer_read_last_lines( $file, $lines ) );

}
add_action( 'wp_ajax_wpas_tools_log_viewer_view', 'wpas_tools_log_viewer_view', 10, 0 );


/*
 * AJAX handler function
 */
function wpas_tools_log_viewer_download() {

	if ( ! current_user_can( 'administrator' ) ) {
		wp_send_json_error( array( 'error' => esc_html__( 'Not found', 'awesome-support' ) ) );
	}

	check_ajax_referer( 'wpas_tools_log_viewer_download', 'nonce' );

	if( ! isset( $_POST[ 'file' ] ) ) {
		wp_send_json_error( array( 'error' => esc_html__( 'No file given', 'awesome-support' ) ) );
	}

	$file = basename( $_POST[ 'file' ] );

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

	if ( ! current_user_can( 'administrator' ) ) {
		wp_send_json_error( array( 'error' => esc_html__( 'Not found', 'awesome-support' ) ) );
	}

	check_ajax_referer( 'wpas_tools_log_viewer_delete', 'nonce' );

	if( ! isset( $_POST[ 'file' ] ) ) {
		echo json_encode( array( 'error' => esc_html__( 'No file given', 'awesome-support' ) ) );
		wp_die();
	}

	$file = basename( $_POST[ 'file' ] );

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
				'message' => sprintf(__( "Read %d lines from %s", 'awesome-support' ), count($result), esc_html( $file )),
			),
			'fileinfo' => array(
				'created' => date ("F d Y H:i:s", filectime($file_path)),
				'lastmodified' => date ("F d Y H:i:s", filemtime($file_path)),
			    'filesize' => formatbytes(filesize($file_path)),
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
			'fileinfo' => array(
				'created' => date ("F d Y H:i:s", filectime($file_path)),
				'lastmodified' => date ("F d Y H:i:s", filemtime($file_path)),
			    'filesize' => formatbytes(filesize($file_path)),
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

function formatbytes($val, $digits = 3, $mode = "SI", $bB = "B")
{
  $si = array("", "k", "M", "G", "T", "P", "E", "Z", "Y");
  $iec = array("", "Ki", "Mi", "Gi", "Ti", "Pi", "Ei", "Zi", "Yi");

  switch(strtoupper($mode))
  {
      case "SI" : $factor = 1000; $symbols = $si; break;
      case "IEC" : $factor = 1024; $symbols = $iec; break;
      default : $factor = 1000; $symbols = $si; break;
  }

  switch($bB)
  {
      case "b" : $val *= 8; break;
      default : $bB = "B"; break;
  }

  for($i=0;$i<count($symbols)-1 && $val>=$factor;$i++)
      $val /= $factor;

  $p = strpos($val, ".");
  if($p !== false && $p > $digits)
    $val = round($val);
  elseif($p !== false)
    $val = round($val, $digits-$p);

  return round($val, $digits) . " " . $symbols[$i] . $bB;
}