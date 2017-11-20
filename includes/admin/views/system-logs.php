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


/**
 * Scan AS logs folder for files.
 *
 * @return array
 */
function dirToArray() {

	$dir =  get_logs_path();

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

/**
 *  Enqueue Admin Scripts
 */
function enqueue_scripts() {
    wp_enqueue_script( 'jquery-ui-core' );
    wp_enqueue_script( 'jquery-ui-accordion' );
}
add_action( 'admin_enqueue_scripts', 'enqueue_scripts' );


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

        div.log-viewer-controls {
            width: 100%;
        }
        .log-viewer-controls table tr th,
        .log-viewer-controls table tr td {
            padding: 0px 10px;
        }

        textarea[disabled] {
            color: #000;
            background-color: #fff;
        }

        div:focus, span:focus, textarea:focus, a:focus, a:active {
            border-color: #5b9dd9;
            -webkit-box-shadow: 0 0 0 rgba(30,140,190,.8);
            box-shadow: 0 0 0 rgba(30,140,190,.8);
            outline: none;
        }

        *.fa {
            font-size: 16px;
            line-height: 26px;
            margin-right: 5px;
        }

        *.fa .disabled {
            color: lightgray;
        }

    </style>

    <script type="text/javascript">

        function makeSafeForCSS(name) {
            return name.replace(/[^a-z0-9]/g, function (s) {
                var c = s.charCodeAt(0);
                if (c == 32) return '-';
                if (c >= 65 && c <= 90) return '_' + s.toLowerCase();
                return '__' + ('000' + c.toString(16)).slice(-4);
            });
        }

        function setWrap(wrap) {

            var area = jQuery('textarea#content');
            //var wrap = jQuery('input#wrap').is(':checked') === true ? 'soft' : 'off';

            if (area.wrap) {
                area.attr('wrap', wrap);
                area.wrap = wrap;
            } else { // wrap attribute not supported - try Mozilla workaround
                area.setAttribute("wrap", wrap);
                area.style.overflow = "hidden";
                area.style.overflow = "auto";
            }
        }

        wpas_log_viewer_current_file = '';
        safeClassName = '';

        jQuery(document).ready(function ($) {
            $('button.wpas-tools-log-delete, a.wpas-tools-log-view, button.wpas-tools-log-download').click(function () {

                $action = $(this).data("action");
                $lines = $('#lines').val();
                $nonce = $(this).data("nonce");

                if ($action === 'wpas_tools_log_viewer_view') {
                    wpas_log_viewer_current_file = $(this).data("filename");
                    $('button#delete').data('filename', wpas_log_viewer_current_file);
                    $('button#download').data('filename', wpas_log_viewer_current_file);
                }

                disableInputs(false, wpas_log_viewer_current_file, 'Working ...');

                // Confirm Delete
                if ($action === 'wpas_tools_log_viewer_delete'
                    && !confirm("Deleting server log file '" + wpas_log_viewer_current_file + "' is permanent.\r\nAre you sure?")
                ) {
                    return false;
                }
                // View
                else if ($action === 'wpas_tools_log_viewer_view'
                ) {
                }
                // Download
                else if ($action === 'wpas_tools_log_viewer_download'
                ) {
                }


                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: $action,
                        file: wpas_log_viewer_current_file,
                        lines: $lines,
                        nonce: $nonce
                    },
                    success: function (data) {

                        // Download
                        if ($action === 'wpas_tools_log_viewer_download') {

                            var url = data.data.url;
                            var a = document.createElement('a'), ev = document.createEvent("MouseEvents");

                            a.href = url;
                            a.download = url.slice(url.lastIndexOf('/') + 1);
                            ev.initMouseEvent("click", true, false, self, 0, 0, 0, 0, 0,
                                false, false, false, false, 0, null);
                            a.dispatchEvent(ev);
                        }

                        if ($action === 'wpas_tools_log_viewer_delete') {

                            // Alert user of successful deletion.
                            disableInputs(true, '', wpas_log_viewer_current_file + ' successfully deleted.');

                            /*
                             Delete the log viewer controls associated with this file.
                             */
                            var parent = $('div.log-viewer-controls.' + safeClassName);
                            var head = parent.prev('h3');
                            parent.add(head).fadeOut('slow', function () {
                                $(parent).remove();
                            });

                        }

                        else if ($action === 'wpas_tools_log_viewer_download') {
                        }

                        // View
                        else if ($action === 'wpas_tools_log_viewer_view') {

                            disableInputs(false, wpas_log_viewer_current_file, data.data.status['message']);

                            $('.' + safeClassName + ' .lastmodified').html(data.data.fileinfo.lastmodified);
                            $('.' + safeClassName + ' .created').html(data.data.fileinfo.created);
                            $('.' + safeClassName + ' .filesize').html(data.data.fileinfo.filesize);

                            $('textarea#content').val(data.data.data);

                            console.log(data);
                        }
                    }
                })
                .done(function (data) {
                    $('body').css('cursor', 'auto');
                    //disableInputs(false);
                    //$("#overlay").fadeOut();
                })
                .fail(function (data) {
                    alert('Failed.');
                    console.log('Failed AJAX Call :( /// Return Data: ' + data);
                });
            });


            /*
             * Number of lines to display. Triggers View action.
             */
            $('select#lines').change(function () {
                $('a.wpas-tools-log-view.' + safeClassName).trigger('click');
            });

            /*
             * Clear display.
             */
            $('button#clear_content').click(function () {
                disableInputs(true, '', 'Ready.');
            });

            /*
             * Initialize log files accordion
             */
            $("#accordion").accordion({
                active: false,
                collapsible: true
            }).show();


            function accordion_expand_all() {
                var sections = $('#accordion').find("h3");
                sections.each(function (index, section) {
                    if ($(section).hasClass('ui-state-default') && !$(section).hasClass('accordion-header-active')) {
                        $(section).click();
                    }
                });

            }

            function accordion_collapse_all() {
                var sections = $('#accordion').find("h3");
                sections.each(function (index, section) {
                    if ($(section).hasClass('ui-state-active')) {
                        $(section).click();
                    }
                });
            }

            function statusMessage(message) {
                $('#log-viewer-status').html(message);
            }

            function disableInputs(disable, filename, statusmessage) {

                var download_color, delete_color, clear_color, wrap_on_color, wrap_off_color;

                if (!disable) {
                    $('body').css('cursor', 'wait');
                    statusMessage(statusmessage);
                    $('i.fa').removeClass('disabled');

                    download_color = "green";
                    delete_color = "red";
                    clear_color = "black";
                    wrap_off_color = "black";
                    wrap_on_color = "black";
                }
                else {

                    download_color = delete_color = clear_color = wrap_off_color = wrap_on_color = "lightgray";

                    statusMessage(statusmessage);
                    $('body').css('cursor', 'auto');
                    $('i.fa').addClass('disabled');
                    $('textarea#content').val('');
                }

                if (filename !== '') {
                    safeClassName = wpas_log_viewer_current_file.replace(/[!\"#$%&'\(\)\*\+,\.\/:;<=>\?\@\[\\\]\^`\{\|\}~]/g, '-');
                    safeClassName = safeClassName.replace(/ /g, '');
                    safeClassName = safeClassName.replace(/-{2,}/g, '-');
                    safeClassName = safeClassName.toLowerCase();
                }
                else {
                    accordion_collapse_all();
                    $('button#delete').data('filename', '');
                    $('button#download').data('filename', '');
                }


                $('button#clear_content i.fa').css('color', clear_color);
                $('button#download i.fa').css('color', download_color);
                $('button#delete i.fa').css('color', delete_color);

                $('button#wrap-off i.fa').css('color', wrap_off_color);
                $('button#wrap-on i.fa').css('color', wrap_off_color);

                $('button#clear_content').attr('disabled', disable);
                $('button#download').attr('disabled', disable);
                $('button#delete').attr('disabled', disable);

                $('button#wrap-off').attr('disabled', disable);
                $('button#wrap-on').attr('disabled', disable);
                $('select#lines').attr('disabled', disable);
                $('textarea#content').attr('disabled', disable);

                var parent = $('div.log-viewer-controls.' + safeClassName);
                var head = parent.prev('h3');
                parent.find('a').attr('disabled', disable);
            }

        });
    </script>
	<?php
}
add_action( 'admin_footer', 'wpas_tools_log_viewer_ajax_script' );
?>

<table class="widefat wpas-tools-log-viewer" style="background-color:#f1f1f1;">

    <thead>
    <tr>
        <th data-override="key" class="row-title" width="289">
            <strong><?php _e( 'Server Logs', 'awesome-support' ); ?></strong></th>
        <th data-override="value">

            <div style="float: left;">

                <button id="clear_content"
                        data-action=""
                        data-filename=""
                        class="button-secondary wpas-tools-log-clear"
                        disabled="disabled"><i
                            class="fa fa-eraser fa-fw"
                            style="color:lightgray;"></i><?php _e( 'Clear', 'awesome-support' ); ?></button>

                <button id="download"
                        class="button-secondary wpas-tools-log-download"
                        data-action="wpas_tools_log_viewer_download"
                        data-nonce="<?php echo wp_create_nonce( 'wpas_tools_log_viewer_download' ); ?>"
                        data-filename=""
                        disabled="disabled"><i
                            class="fa fa-arrow-circle-down fa-fw"
                            style="color:lightgray;"></i><?php _e( 'Download', 'awesome-support' ); ?></button>

                <button id="delete"
                        class="button-secondary wpas-tools-log-delete"
                        data-action="wpas_tools_log_viewer_delete"
                        data-nonce="<?php echo wp_create_nonce( 'wpas_tools_log_viewer_delete' ); ?>"
                        data-filename=""
                        disabled="disabled"><i
                            class="fa fa-minus-circle fa-fw"
                            style="color: lightgray;"></i><?php _e( 'Delete', 'awesome-support' ); ?></button>

            </div>

            <div id="log-viewer-status"
                 style="float: left;height: 27px; min-width: 320px; line-height: 26px;border: 1px solid lightgray;margin-left: 20px;padding: 0 10px;">
                Ready.
            </div>

            <div style="float: right;">

                <label for="lines"><?php _e( 'Max Lines', 'awesome-support' ); ?></label>
                <select id="lines">
                    <option value="50">50</option>
                    <option value="500">500</option>
                    <option value="5000">5000</option>
                    <option value="All">All</option>
                </select>

                <button id="wrap-on"
                        data-wrap="soft"
                        class="button-secondary"
                        onclick="setWrap('soft');"
                        disabled="disabled"><i
                            class="fa fa-align-left fa-fw"
                            style="color: lightgray;"></i></button>
                <button id="wrap-off"
                        data-wrap="off"
                        class="button-secondary"
                        onclick="setWrap('off');"
                        disabled="disabled"><i
                            class="fa fa-align-justify fa-fw"
                            style="color: lightgray;"></i></button>
            </div>

        </th>
    </tr>
    </thead>

    <tbody>

    <tr>
        <td class="row-title" style="">

            <div style="max-height: 500px; overflow-y: scroll;">
                <div id="accordion" style="width: 100%; display: none;">

					<?php

					$ar = dirToArray();

					foreach( $ar as $file ) {

						$classfromfilename = sanitize_title( $file );
						?>

                        <h3 class="log-viewer-filename <?php echo $classfromfilename; ?>"
                            style="font-size: 14px;"><a href="#"
                                                        data-filename="<?php echo $file; ?>"
                                                        data-action="wpas_tools_log_viewer_view"
                                                        data-nonce="<?php echo wp_create_nonce( 'wpas_tools_log_viewer_view' ); ?>"
                                                        class="wpas-tools-log-view <?php echo $classfromfilename; ?>"><i
                                        class="fa fa-chevron-right fa-fw"
                                        style="color: dimgray; font-size: 12px;"></i><?php echo $file; ?></a>
                        </h3>

                        <div class="log-viewer-controls <?php echo $classfromfilename; ?>" style="100%">

                            <table width="100%">
                                <tr>
                                    <th>File Size:</th>
                                    <td><span class="filesize"></span></td>
                                </tr>
                                <tr>
                                    <th>Last Modified:</th>
                                    <td><span class="lastmodified"></span></td>
                                </tr>
                                <tr>
                                    <th>Created:</th>
                                    <td><span class="created"></span></td>
                                </tr>
                            </table>

                            <br/>

                        </div>

						<?php
					}
					?>
                </div>
            </div>

        </td>
        <td>

            <textarea id="content" cols="150" rows="25" style="width: 100%;"
                      wrap="soft"
                      readonly disabled="disabled"></textarea>
            <br/>
        </td>
    </tr>

    </tbody>

</table>
