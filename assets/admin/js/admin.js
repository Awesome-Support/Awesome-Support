(function ($) {
	"use strict";

	$(function () {

		/**
		 * Automatically Link URLs, Email Addresses, Phone Numbers, etc.
		 * https://github.com/gregjacobs/Autolinker.js
		 */
		if ($('.wpas-ticket-content').length && $('.wpas-reply-content').length) {
			$('.wpas-ticket-content, .wpas-reply-content').each(function (index, el) {
				el.innerHTML = Autolinker.link(el.innerHTML);
			});
		}

		/**
		 * Mark as read
		 */
		$('.wpas-mark-read').on('click', function (event) {
			event.preventDefault();

			var btn = $(this),
				replyID = $(this).data('replyid'),
				data = {
					'action': 'wpas_mark_reply_read',
					'reply_id': replyID
				};

			$.post(ajaxurl, data, function (response) {

				/* check if response is an integer */
				if (Math.floor(response) == response && $.isNumeric(response)) {
					btn.fadeOut('fast');
					$('#wpas-unread-' + replyID).fadeOut('fast');
				} else {
					alert(response);
				}

			});

		});

		/**
		 * System Status
		 */
		var table,
			tableID,
			tableData,
			tables = [],
			output = $('#wpas-system-status-output');

		function tableToJSON(table) {

			$(table).each(function (index, el) {
				tableID = $(el).attr('id').replace('wpas-system-status-', '');
				tableData = $(el).tableToJSON();
				table = tableData;
				tables.push({
					label: tableID,
					data: tableData
				});
			});

		}

		$('#wpas-system-status-generate-json').click(function (event) {
			tableToJSON('.wpas-system-status-table');
			output.html(JSON.stringify(tables)).fadeIn('fast').focus().select();
		});

		$('#wpas-system-status-generate-wporg').click(function (event) {
			tableToJSON('.wpas-system-status-table');
			output.html('<pre>' + JSON.stringify(tables) + '</pre>').fadeIn('fast').focus().select();
		});

		/**
		 * Check if editor is empty
		 * http://stackoverflow.com/a/1180199
		 */
		$('.wpas-reply-actions').on('click', 'button', function (event) {

			var btn = $(event.target);

			// Detect which button is clicked
			if (btn.hasClass('wpas_btn_reply') || btn.hasClass('wpas_btn_reply_close')) {

				// Detect Visual and Text Mode in WordPress TinyMCE Editor
				var is_tinymce_active = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();

				// Visual Editor
				if (is_tinymce_active) {
					var editor = tinyMCE.activeEditor;
					var editorContent = editor.getContent();
					if (editorContent === '' || editorContent === null) {

						/* Alert the user */
						alert(wpasL10n.alertNoContent);

						/* Focus on editor */
						editor.focus();

						return false;
					}

				}

				// Text Editor
				else {
					var textarea = $('textarea[name="wpas_reply"]');
					if (!textarea.val()) {

						/* Alert the user */
						alert(wpasL10n.alertNoContent);

						/* Focus on editor */
						textarea.focus();

						return false;
					}
				}

			}
		});

		/**
		 * jQuery Select2
		 * http://select2.github.io/select2/
		 */
		if (jQuery().select2 && $('select.wpas-select2').length) {
			$('select.wpas-select2:visible').select2();
		}

		/**
		 * Make ticket title required
		 * http://wordpress.stackexchange.com/a/101260
		 */
		$('#publish').on('click', function () {
			$('#titlediv > #titlewrap > #title').prop('required', true);
		});
                
                // Adding color picker for priority taxonomy
                if ( typeof $.wp.wpColorPicker === 'function' ) {
                        $( '#term-color' ).wpColorPicker();
                } 
                
                
                /**
                 * Admin tabs
                 */
                
                
                // Tab change handler
                function admin_tabs_change_handler() {
                    
                    var container = $(this).closest('.wpas_admin_tabs');
                    
                    container.find('.wpas_admin_tab_content').each( function() {
                            $(this).hide();
                    });

                    container.find('.wpas_tab_name').removeClass('active');
                    $(this).addClass('active');

                    var id = $(this).attr('rel');
                    $('#'+id).show();
                    
                }
                
                
                // making tabs smart responsive
                var processing_resize = false;
                var processing_resize_queue = false;
        
                function admin_tabs_responsive() {

                        if( processing_resize ) {
                                processing_resize_queue = true;
                                return;
                        }

                        processing_resize = true;
                        
                        
                        var widgets = $('.wpas_admin_tabs');
                        
                        
                        widgets.each( function() {
                            var widget = $(this);
                            
                            var tabs_wrapper = widget.find('.wpas_admin_tabs_names_wrapper');
                            var tabs = tabs_wrapper.find('> ul').children('li:not(.clear, .moreTab)');
                            
                            
                            var wrapper_width = tabs_wrapper.innerWidth() - 60;
                            

                            var items_width = 0;
                            var iw = 0;


                            var limit_over = false;


                            tabs.each(function() {
                                    iw = $(this).innerWidth();
                                    if($(this).hasClass('active')) {
                                            iw += 2;
                                    }

                                    if( !limit_over && wrapper_width > items_width + iw ) {

                                            items_width += iw ;

                                    } else {
                                            limit_over = true;

                                            $(this).appendTo( tabs_wrapper.find('.tabs_collapsed') );
                                            $(this).data('inner_width', iw );

                                    }


                            });

                             $( tabs_wrapper.find('.tabs_collapsed li').toArray().sort(sort_items)).appendTo( $(tabs_wrapper.find('.tabs_collapsed')) )
                            limit_over = false;

                            tabs_wrapper.find('.tabs_collapsed li').each(function(){
                                    iw = parseInt($(this).data('inner_width'));

                                    if( !limit_over && wrapper_width > items_width + iw ) {
                                        
                                            var tabs_wrapper = widget.find('.wpas_admin_tabs_names_wrapper');
                                            var last_tab = tabs_wrapper.find('> ul').children('li:not(.clear, .moreTab):last');

                                            if( last_tab.length === 1 ) {
                                                    $(this).insertAfter(  last_tab );
                                            } else {
                                                    $(this).prependTo( tabs_wrapper.find('> ul') );
                                            }

                                            items_width += iw ;
                                    } else {
                                          limit_over = true;
                                    }
                            });


                            if( tabs_wrapper.find('.tabs_collapsed li').length === 0 ) {
                                    tabs_wrapper.find('.moreTab').hide();
                            } else {
                                    tabs_wrapper.find('.moreTab').show();
                            }
                            
                            
                            
                            
                        });
                        

                        

                        processing_resize = false;

                        if( processing_resize_queue ) {
                                meta_tabs_responsive();
                                processing_resize_queue = false;
                        }


                }

                function sort_items(a, b){
                        return parseInt($(a).data('tab-order')) - parseInt($(b).data('tab-order'));
                }
                
                
                if ( $('.wpas_admin_tabs').length > 0 ) {
                
                    // Listen tab change
                    $('.wpas_admin_tabs .wpas_tab_name').on( 'click', admin_tabs_change_handler );


                    // Default display first tab
                    $('.wpas_admin_tabs').each(function() {
                        $($(this).find('.wpas_tab_name').get(0)).trigger('click');
                    });


                    admin_tabs_responsive();

                    $(window).on('resize', admin_tabs_responsive );
                }
                
                
	});

}(jQuery));