(function($){

    var uploaded  = false;
    var ticket_id = false;
    Dropzone.autoDiscover = false;

    var submitButtons = $('button[name="wpas_do"], button[name="wpas-submit"]');

    function attachDropZones() {

        // Attach dropzone to every upload field
        $('.wpas-uploader-dropzone').each(function(i, e){

            // Check if its already attached
            if ( $(e).hasClass('dz-clickable') ) {
                return false;
            }

            var id    = $(e).attr('id');
            var paste = $(this).data('enable-paste');
            ticket_id = (  $('#post_ID').length ) ? $('#post_ID').val() : $(this).data('ticket-id');
            var dropzone_id = $(this).attr('id').substr(9);

            $('#' + id).dropzone({ 
                url: WPAS_AJAX.ajax_url,
                paramName: 'wpas_files',
                acceptedFiles : WPAS_AJAX.accept,
                timeout : WPAS_AJAX.max_execution_time,
                maxFiles : WPAS_AJAX.max_files,
                maxFilesize : WPAS_AJAX.max_size,
                addRemoveLinks: true,
                init: function() {

                    var that = this;
                    
                    this.on('sending', function(file, xhr, formData){
                        formData.append('action', 'wpas_upload_attachment');
                        formData.append('ticket_id', ticket_id );
                        formData.append('dz_id', dropzone_id );
                        uploaded = true;
                        submitButtons.attr('disabled', 'disabled');
                    });

                    this.on('complete', function(e) {
                        if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                            submitButtons.removeAttr('disabled');
                        }
                    });
            
                    this.on('addedfile', function(e) {
                        if (this.files.length > this.options.maxFiles) {
                            this.removeFile(this.files[0]);
                            alert( WPAS_AJAX.exceeded );
                        }
                    });

                    // Check if paste is enabled
                    if( paste === 1 ) {

                        $(document).on('paste', function (e){

                            if ( $('#' + id).hasClass('wpas-mouse-hover') ) {

                                var items = (e.clipboardData || e.originalEvent.clipboardData).items;

                                for (var j in items) {
                                    if (items[j].kind === 'file') {
                                        that.addFile( items[j].getAsFile() ); 
                                    }
                                }
                            }

                        });
                        
                    } 
            
                },
                removedfile: function(file) {

                    var _ref;
                    var name = file.name; 

                    $.ajax({
                        type: 'POST',
                        url: WPAS_AJAX.ajax_url,
                        data: {
                            action: 'wpas_delete_temp_attachment',
                            ticket_id: ticket_id,
                            attachment: file.name
                        }
                    });

                    return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
                }

            });

            $(document).on({
                mouseenter: function () {
                    $(this).addClass('wpas-mouse-hover');
                },
                mouseleave: function () {
                    $(this).removeClass('wpas-mouse-hover');
                }
            }, '#' + id );

        });

    }

    // Attach dropzones
    attachDropZones();

    // Attach dropzones on dynamic elements
    $(document).ajaxComplete(function(){
        attachDropZones();
    });


    // Reset uploaded var on reply submit
    submitButtons.on('click', function(e){
        uploaded = false;
    });

    // Delete temporary attachments directory if user didn't submit reply
    $(window).on('beforeunload', function( e ) {

        if (uploaded) {

            $.ajax({
                type: 'POST',
                url: WPAS_AJAX.ajax_url,
                async: false,
                data: {
                    action: 'wpas_delete_temp_directory',
                    ticket_id: ticket_id,
                }
            });
        }

    });

})(jQuery);
