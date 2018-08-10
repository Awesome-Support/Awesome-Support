(function ($) {
	"use strict";

	$(function () {

		/*
		Limit maximum items on a multiple input
		http://stackoverflow.com/a/10105631
		 */
		if (typeof wpas !== 'undefined' && wpas.fileUploadMax) {
			var $fileUpload = $('#wpas_files');
			$fileUpload.on('change', function (event) {
				event.preventDefault();

				// Check file input size with jQuery | http://stackoverflow.com/a/3937404
				var bigFiles = [];
				$.each($fileUpload.get(0).files, function (index, val) {
					if (val.size > wpas.fileUploadSize) {
						bigFiles.push(val.name);
					}
				});
				if (bigFiles.length !== 0) {
					alert(wpas.fileUploadMaxSizeError[0] + '\n\n' + bigFiles.join('\n') + '.\n\n' + wpas.fileUploadMaxSizeError[1]);
					clearFileInput($fileUpload[0]);
				}

				// Check if not uploading too many files
				if (parseInt($fileUpload.get(0).files.length) > parseInt(wpas.fileUploadMax, 10)) {
					alert(wpas.fileUploadMaxError);
					clearFileInput($fileUpload[0]);
				}
			});
		}
                
                /* Delete single attachment from front-end or back-end  */
                $('body').delegate( '.btn_delete_attachment', 'click', function( e ) {
                    
                    e.preventDefault();
                    
                    var btn = $(this);
                    
                    var loader = $('<span class="spinner" style="visibility: visible;margin-left: 0;float: left;margin-top: 0;"></span>');
                        loader.insertAfter( btn );
                    
                    btn.hide();
                    
                    var parent_id = $(this).data('parent_id');
                    var att_id = $(this).data('att_id');
                    
                    var data = {
                        action   : 'wpas_delete_attachment',
                        parent_id : parent_id,
                        att_id : att_id
                    };

                    $.post( ajaxurl, data, function (response) {

                        btn.show();
                        loader.remove();
                        
                        if( response.success ) {
                                btn.closest('li').html(response.data.msg)
                        }
                        
                    });
            });
            
            /* front end update auto delete attachments flag */
            $('#wpas-new-reply .wpas-auto-delete-attachments-container input[type=checkbox]').change( function() {
                    var btn = $(this);
                    
                    var loader = $('<span class="spinner" style="visibility: visible;margin-left: 0;float: left;margin-top: 0;"></span>');
                        loader.insertAfter( btn );
                    
                    btn.hide();
                    
                    
                    var data = {
                        action   : 'wpas_auto_delete_attachment_flag',
                        ticket_id : wpas.ticket_id,
                        auto_delete : btn.is(':checked') ? '1' : '0'
                    };

                    $.post( ajaxurl, data, function (response) {
                        btn.show();
                        loader.remove();
                    });
            });

	});

}(jQuery));