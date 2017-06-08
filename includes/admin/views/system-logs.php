<?php

$log_viewer_action       =               //
	filter_input( INPUT_GET,
	              'wpas_tools_log_viewer_action',
	              FILTER_SANITIZE_STRING,
	              array(
		              "options" => array(
			              "default" => '',
		              ),
	              ) );
$log_viewer_current_file =               //
	filter_input( INPUT_GET,
	              'wpas_tools_log_viewer_current_file',
	              FILTER_SANITIZE_STRING,
	              array(
		              "options" => array(
			              "default" => '',
		              ),
	              ) );


function dirToArray() {

	$dir = WPAS_PATH . 'logs';

	$result = array();

	$cdir = scandir( $dir );
	foreach( $cdir as $key => $value ) {
		if( ! in_array( $value, array( ".", ".." ) ) ) {
			if( is_dir( $dir . DIRECTORY_SEPARATOR . $value ) ) {
				$result[ $value ] = dirToArray( $dir . DIRECTORY_SEPARATOR . $value );
			}
			else {
				$result[] = $value;
			}
		}
	}

	return $result;
}

/*
 * The JavaScript for our AJAX call
 */
function wpas_tools_log_viewer_ajax_script() {
	?>
    <style>
        #overlay {
            display: none;
            position: absolute;
            background: #fff;
        }

        #img-load {
            position: absolute;
        }

        .controls {
            display: none;
        }

    </style>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('a.wpas-tools-log-delete, a.wpas-tools-log-view, a.wpas-tools-log-download').click(function () {

                $action = $(this).data("action");
                $file = $(this).data("filename");
                $lines = $('#lines').val();

                if ($action === 'wpas_tools_log_viewer_delete'
                    && !confirm("Deleting server log file '" + $file + "' is permanent.\r\nAre you sure?")
                ) {
                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: $action,
                        file: $file,
                        lines: $lines,
                    },
                    success: function (data) {

                        $('#log-viewer-status').html(data.data.status['message']);
                        if ($action === 'wpas_tools_log_viewer_download') {

                            var url = data.data.url;
                            var a = document.createElement('a'), ev = document.createEvent("MouseEvents");

                            a.href = url;
                            a.download = url.slice(url.lastIndexOf('/') + 1);
                            ev.initMouseEvent("click", true, false, self, 0, 0, 0, 0, 0,
                                false, false, false, false, 0, null);
                            a.dispatchEvent(ev);
                        }
                        else {

                            $('textarea#content').val(data.data.data);
                            console.log(data);
                        }
                    }
                })
                .done(function (data) {
                    $("#overlay").fadeOut();
                })
                .fail(function (data) {
                    console.log('Failed AJAX Call :( /// Return Data: ' + data);
                });
            });

            $('select#lines').change(function () {
                $('button.wpas-tools-log-delete').trigger('click');
            });

            var $control_boxes = $('.log-viewer-controls'),
                $controlLinks = $('li.log-viewer-item').mouseover(function () {
                    $control_boxes.hide();  //.filter(this).show();
                    $('div.log-viewer-controls', this).show();  //fadeIn(500);
                });

            $('button#clear_content').click(function () {
                $('textarea#content').val("Nothing to display.");
            });

        });
    </script>
	<?php
}
add_action( 'admin_footer', 'wpas_tools_log_viewer_ajax_script' );
?>

<p><strong><?php _e( 'Log Viewer', 'awesome-support' ); ?></strong></p>

<table class="widefat wpas-tools-log-viewer">

    <thead>

    <tr>
        <th data-override="key" class="row-title">Logs Directory</th>
        <th data-override="value"><?php echo get_logs_path(); ?></th>
    </tr>

    <tr>
        <th data-override="key" class="row-title"></th>
        <th data-override="value"></th>
    </tr>

    <tr>
        <th data-override="key" class="row-title"></th>
        <th data-override="value">

            <div style="float: left;">
                <label for="lines"><?php _e( 'Max # Lines', 'awesome-support' ); ?></label>
                <select id="lines">
                    <option value="50">50</option>
                    <option value="500">500</option>
                    <option value="5000">5000</option>
                    <option value="All">All</option>
                </select>
            </div>

            <div style="float: right;">
                <button id="clear_content"
                        class="button-secondary wpas-tools-log-clear"><?php _e( 'Clear Content', 'awesome-support' ); ?></button>
            </div>

        </th>
    </tr>

    </thead>

    <tbody>

    <tr>
        <td class="row-title" style="min-width: 200px;"><?php

			$ar = dirToArray();
			echo '<br><ul style="100%;">';

			foreach( $ar as $file ) {

				$args[ 'tab' ]                                = 'logs';
				$args[ 'page' ]                               = 'wpas-status';
				$args[ 'post_type' ]                          = 'ticket';
				$args[ 'wpas_tools_log_viewer_action' ]       = 'view';
				$args[ 'wpas_tools_log_viewer_current_file' ] = $file;
				$args[ '_nonce' ]                             = wp_create_nonce( 'tool_log_viewer' );
				$url                                          = esc_url( add_query_arg( $args, admin_url( 'edit.php' ) ) );

				?>
                <li class="log-viewer-item" style="100%; height: auto; line-height: 32px; background-color: #dfdfdf;">
                    <div class="log-viewer-filename" style="clear:both; overflow: hidden; display: block;">
                        <a href="<?php echo $url; ?>"
                           data-filename="<?php echo $file; ?>"
                           data-action="wpas_tools_log_viewer_view"
                           class=" "><?php echo $file; ?></a>
                    </div>

                    <div class="log-viewer-controls" style="display: none;">
                        <a href="#"
                           data-filename="<?php echo $file; ?>"
                           data-action="wpas_tools_log_viewer_view"
                           class="wpas-tools-log-view"><?php _e( 'View', 'awesome-support' ); ?></a>

                        | <a href="#"
                             data-filename="<?php echo $file; ?>"
                             data-action="wpas_tools_log_viewer_download"
                             class="wpas-tools-log-download"><?php _e( 'Download', 'awesome-support' ); ?></a>

                        | <a href="#"
                             data-filename="<?php echo $file; ?>"
                             data-action="wpas_tools_log_viewer_delete"
                             class="wpas-tools-log-delete"><?php _e( 'Delete', 'awesome-support' ); ?></a>
                    </div>

                </li>
				<?php
			}
			echo '</ul>';

			?></td>
        <td>
            <textarea id="content" cols="150" rows="25" style="width: 100%;"
                      readonly><?php _e( 'Nothing to display.', 'awesome-support' ); ?></textarea>
        </td>
    </tr>

    <tr>
        <td></td>
        <td>
            <div id="log-viewer-status"></div>
        </td>
    </tr>

    </tbody>

</table>
