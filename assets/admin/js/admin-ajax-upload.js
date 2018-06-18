(function($){

    var uploaded = false;
    Dropzone.autoDiscover = false;

    // Attach dropzone to every upload field
    $('.wpas-uploader-dropzone').each(function(i, e){

        var id = $(e).attr('id');

        $('#' + id).dropzone({ 
            url: ajaxurl,
            paramName: 'wpas_files',
            acceptedFiles : WPAS_AJAX.accept,
            maxFiles : WPAS_AJAX.max_files,
            maxFilesize : WPAS_AJAX.max_size,
            addRemoveLinks: true,
            init: function() {
                
                this.on('sending', function(file, xhr, formData){
                    formData.append('action', 'wpas_upload_attachment');
                    formData.append('ticket_id', $('#post_ID').val() );
                    uploaded = true;
                });
        
                this.on('addedfile', function(e) {
                    while (this.files.length > this.options.maxFiles) {
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
                    url: ajaxurl,
                    data: {
                        action: 'wpas_delete_temp_attachment',
                        parent_id: $('#post_ID').val(),
                        attachment: file.name
                    },
                    done: function(data){
                        console.log('success: ' + data);
                    }
                });

                return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
            }

        });

    });


    // Reset uploaded var on reply submit
    $('button[name="wpas_do"]').on('click', function(e){
        uploaded = false;
    });

    // Delete temporary attachments directory if user didn't submit reply
    $(window).on('beforeunload', function( e ) {

        if (uploaded) {

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                async: false,
                data: {
                    action: 'wpas_delete_temp_directory',
                    parent_id: $('#post_ID').val(),
                }
            });
        }

    });

})(jQuery);
