
( function($) {
        
        $( function() {
                
                
                function ajaxContentAdded() {
                        $('.mfp-wrap').prepend( $('.mfp-close').get(0) );
                }
                
                
                function destroy_editors( content ) {
                        if( !content ) {
                                return;
                        }
                        content.find('.wp-editor-wrap').each( function() {
                                var editor_id = $(this).attr('id').slice( 3, -5 );
                                if( tinyMCE.get( editor_id ) ) {
                                        tinyMCE.get( editor_id ).destroy();
                                }
                        });
                }
                
                function open() {
                        
                        $('.mfp-wrap').prepend( $('.mfp-close').get(0) );
                        
                }
                
                function close() {
                        destroy_editors( this.content );
                }
                
                
                /**
                 * Trigger once close button is pressed from poopup
                 */
                $('body').delegate('.wpas_win_close_btn', 'click', function() {
                        $.magnificPopup.close();
                });
                
                /**
                 * Trigger once open window button is pressed
                 */
                $('body').delegate( '.mfp_window, .wpas_win_link ', 'click', function( e) {
                        e.preventDefault();
                        
                        
                        var type = $(this).data('win_type') || 'inline';
                        var src = $(this).data('win_src') || $(this).attr('href');
                        
                        if( !( type && src ) ) {
                                return;
                        }
                                
                        var settings = {
                                items : { type : type, src : src },
                                closeOnBgClick : false,
                                callbacks: {
                                        parseAjax: function(mfpResponse) {
                                                mfpResponse.data = $(mfpResponse.data).removeClass('mfp-hide')
                                        },

                                        ajaxContentAdded: ajaxContentAdded,
                                        open: open,
                                        close : close
                                }
                        };

                        if( 'ajax' === type ) {

                                settings.items.src = ajaxurl;

                                var ajax_data = $(this).data('ajax_params');

                                settings.ajax = {};
                                settings.ajax.settings = {
                                        method : 'POST',
                                        data : ajax_data
                                }
                        }

                        $.magnificPopup.open( settings );

                        $('.mfp-content .wpas_mfp_window_wrapper .wpas_msg').hide();
                        
                });
        })
        
        
})( jQuery );