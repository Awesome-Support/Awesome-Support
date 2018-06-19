(function($){

    var uploaded  = false;
    var ticket_id = false;
    Dropzone.autoDiscover = false;

    // Attach dropzone to every upload field
    $('.wpas-uploader-dropzone').each(function(i, e){

        var id    = $(e).attr('id');
        ticket_id = (  $('#post_ID').length ) ? $('#post_ID').val() : $(this).data('ticket-id');

        $('#' + id).dropzone({ 
            url: WPAS_AJAX.ajax_url,
            paramName: 'wpas_files',
            acceptedFiles : WPAS_AJAX.accept,
            maxFiles : WPAS_AJAX.max_files,
            maxFilesize : WPAS_AJAX.max_size,
            addRemoveLinks: true,
            init: function() {
                
                this.on('sending', function(file, xhr, formData){
                    formData.append('action', 'wpas_upload_attachment');
                    formData.append('ticket_id', ticket_id );
                    uploaded = true;
                });
        
                this.on('addedfile', function(e) {
                    if (this.files.length > this.options.maxFiles) {
                        this.removeFile(this.files[0]);
                        alert( WPAS_AJAX.exceeded );
                    }
                });
        
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

    });


    // Reset uploaded var on reply submit
    $('button[name="wpas_do"], button[name="wpas-submit"]').on('click', function(e){
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
