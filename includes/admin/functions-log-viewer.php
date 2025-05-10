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
	$file  = basename( sanitize_text_field( wp_unslash( $_POST[ 'file' ] )) );

	// Get posted number of lines
	if( isset( $_POST[ 'lines' ] ) ) {
		$lines = sanitize_text_field( wp_unslash( $_POST[ 'lines' ] ));
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

	$file = basename( sanitize_text_field( wp_unslash( $_POST[ 'file' ] )) );

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

	$file = basename( sanitize_text_field( wp_unslash( $_POST[ 'file' ] )) );

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

	global $wp_filesystem;
	// Initialize the filesystem 
	if (empty($wp_filesystem)) {
		require_once(ABSPATH . '/wp-admin/includes/file.php');
		WP_Filesystem();
	} 

	$handle = $wp_filesystem->get_contents($file_path);
	if ($handle !== false) {
		$lines_array = explode("\n", $handle); // Diviser le contenu du fichier en lignes
		$total_lines = count($lines_array);
	
		$linecounter = $lines;  // Le nombre de lignes à lire
		$text = array();
	
		while ($linecounter > 0 && $total_lines > 0) {
			if(isset($lines_array[$total_lines - $linecounter])){
				$text[] = $lines_array[$total_lines - $linecounter];
			}
			$linecounter--;
		}
	
		foreach ($text as $line) {
			if($line){
				$result[] = $line;
			}
		}

		// translators: %d is the number of lines, %s is the source.
		$x_content = __( 'Read %1$d lines from %2$s', 'awesome-support' );

		return array(
			'status' => array(
				'code'    => '200',
				'message' => sprintf( $x_content, count($result), esc_html( $file )),
			),
			'fileinfo' => array(
				'created' => gmdate ("F d Y H:i:s", filectime($file_path)),
				'lastmodified' => gmdate ("F d Y H:i:s", filemtime($file_path)),
				'filesize' => wpas_formatbytes(filesize($file_path)),
			),
			'data'   => $result,
		);
	}
	else {
		//return printf( __( "Couldn't open the file %s. Make sure file is exists or is readable.", 'error-log-viewer' ), esc_html( $file ) );

		// translators: %s is the file name that couldn't be opened.
		$x_content = __( "Couldn't open the file %s. Make sure the file exists and is readable.", 'awesome-support' );


		return array(
			'status' => array(
				'code'    => '404',
				'message' => sprintf($x_content, esc_html( $file )),
			),
			'data'   => [],
		);
	}

}


/* Function read full file */
function wpas_log_viewer_read_full_file( $file ) {

	$file_path = get_logs_path() . $file;

	global $wp_filesystem;
	// Initialize the filesystem 
	if (empty($wp_filesystem)) {
		require_once(ABSPATH . '/wp-admin/includes/file.php');
		WP_Filesystem();
	} 
	$handle = $wp_filesystem->get_contents($file_path);
	$result = [];
	if ($handle !== false) {
		$lines_array = explode("\n", $handle); // Diviser le contenu du fichier en lignes
		foreach ($lines_array as $line) {
			$result[] = $line;
		}
		// translators: %1$d is the number of lines, %2$s is the source.
		$x_content = __( 'Read %1$d lines from %2$s', 'awesome-support' );

		return array(
			'status' => array(
				'code'    => '200',
				//'message' => '',
				'message' => sprintf($x_content, count($result), esc_html( $file )),
			),
			'fileinfo' => array(
				'created' => gmdate ("F d Y H:i:s", filectime($file_path)),
				'lastmodified' => gmdate ("F d Y H:i:s", filemtime($file_path)),
			    'filesize' => wpas_formatbytes(filesize($file_path)),
			),
			'data'   => $result,
		);
	}
	else {
		//return printf( __( "Couldn't open the file %s. Make sure file is exists or is readable.", 'error-log-viewer' ), esc_html( $file ) );
		// translators: %s is the file name that couldn't be opened.
		$x_content = __( "Couldn't open the file %s. Make sure the file exists and is readable.", 'awesome-support' );
		return array(
			'status' => array(
				'code'    => '404',
				'message' => sprintf( $x_content, esc_html( $file )),
			),
			'data'   => [],
		);
	}
}

function wpas_formatbytes($val, $digits = 3, $mode = "SI", $bB = "B")
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
