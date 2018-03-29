(function ($) {
    "use strict";
    
        /* Place an element at specific index */
        $.fn.appendAtIndex = function( to,index ) {
                if(! to instanceof jQuery){
                    to=$(to);
                }
                if( index===0 ){
                    $(this).prependTo( to )
                }else{
                    $(this).insertAfter( to.children().eq(index-1) );
                }
        };

    $(function () {
		
		/* Hide the ticket slug on the ticket details page  */
		function hideTicketSlug() {
			var slug = $('.post-type-ticket #edit-slug-box');  // Get all the slug rows - should only be one though.
			slug.toggle(); // hide it.
		}
		hideTicketSlug(); // Hide the slug as soon as the page loads
		
		/* Show the ticket slug on the ticket details page */
		function toggleTicketSlug() {
			var slug = $('.post-type-ticket #edit-slug-box');  // Get all the slug rows - should only be one though.
			slug.toggle(); 
		}
		
		var btnToggleTicketSlug	= $('#wpas-toggle-ticket-slug');  			// Get a handle to the TOGGLE TICKET SLUG button in the ticket details toolbar
		btnToggleTicketSlug.click( function() { toggleTicketSlug(); } ) ;  	// When its clicked, call our toggleTicketSlug function above.			

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
                
                var editor = tinymce.get('wpas_reply');
                var is_tinymce_active = (typeof tinyMCE != "undefined") && editor && !editor.isHidden();

                // Visual Editor
                if (is_tinymce_active) {
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
         * jQuery DatePicker
         *
         */
        if (jQuery().datepicker && $('input.wpas-date').length) {
            // Check first element compatibility for HTML5 <input type="date" />
	    	if ( $('input.wpas-date:first').prop('type') != 'date' ) {
	    	    // Not supported. Fallback to jQuery DatePicker
		    	$('input.wpas-date').datepicker();
    		}
	    }

        /**
         * Make ticket title required
         * http://wordpress.stackexchange.com/a/101260
         */
        $('#publish').on('click', function () {
            $('#titlediv > #titlewrap > #title').prop('required', true);
        });

        // Adding color picker for priority taxonomy
        if (typeof $.wp.wpColorPicker === 'function') {
            $('#term-color').wpColorPicker();
        }


        /**
         * Admin tabs
         */


        // Tab change handler
        function admin_tabs_change_handler() {

            var container = $(this).closest('.wpas_admin_tabs');

            container.find('.wpas_admin_tab_content').each(function () {
                $(this).hide();
            });

            container.find('.wpas_tab_name').removeClass('active');
            $(this).addClass('active');

            var id = $(this).attr('rel');
            $('#' + id).show();

            $('#' + id + ' .wpas-select2').each(function () {
                if (typeof $(this).data('select2') != 'object') {
                    $(this).select2();
                }
            });
            
            /* Reset tinymce editors in tab so they size properly */
            if( typeof tinymce !== 'undefined') {
                $('#' + id + ' .wp-editor-wrap').each(function () {
                    
                    var editor_ele_id = $(this).attr('id');
                    var editor_id = editor_ele_id.substring( 3, editor_ele_id.length - 5 ) ;
                    
                    if (  $(this).hasClass( 'tmce-active' ) && tinyMCEPreInit.mceInit.hasOwnProperty(editor_id) ) {
                        if( null !== tinyMCE.get( editor_id ) ) {
                            tinyMCE.get( editor_id ).destroy();
                            tinymce.init( tinyMCEPreInit.mceInit[editor_id] )
                        }
                    }
                });
            }
            
            $('#' + id).trigger( 'tab_show' );
        }

        // making tabs smart responsive
        var processing_resize = false;
        var processing_resize_queue = false;

        function admin_tabs_responsive() {

            if (processing_resize) {
                processing_resize_queue = true;
                return;
            }

            processing_resize = true;


            var widgets = $('.wpas_admin_tabs');


            widgets.each(function () {
                var widget = $(this);

                var tabs_wrapper = widget.find('.wpas_admin_tabs_names_wrapper');
                var tabs = tabs_wrapper.find('> ul').children('li:not(.clear, .moreTab)');


                var wrapper_width = tabs_wrapper.innerWidth() - 60;


                var items_width = 0;
                var iw = 0;


                var limit_over = false;


                tabs.each(function () {
                    iw = $(this).innerWidth();
                    if ($(this).hasClass('active')) {
                        iw += 2;
                    }

                    if (!limit_over && wrapper_width > items_width + iw) {

                        items_width += iw;

                    } else {
                        limit_over = true;

                        $(this).appendTo(tabs_wrapper.find('.tabs_collapsed'));
                        $(this).data('inner_width', iw);

                    }


                });

                $(tabs_wrapper.find('.tabs_collapsed li').toArray().sort(sort_items)).appendTo($(tabs_wrapper.find('.tabs_collapsed')))
                limit_over = false;

                tabs_wrapper.find('.tabs_collapsed li').each(function () {
                    iw = parseInt($(this).data('inner_width'));

                    if (!limit_over && wrapper_width > items_width + iw) {

                        var tabs_wrapper = widget.find('.wpas_admin_tabs_names_wrapper');
                        var last_tab = tabs_wrapper.find('> ul').children('li:not(.clear, .moreTab):last');

                        if (last_tab.length === 1) {
                            $(this).insertAfter(last_tab);
                        } else {
                            $(this).prependTo(tabs_wrapper.find('> ul'));
                        }

                        items_width += iw;
                    } else {
                        limit_over = true;
                    }
                });


                if (tabs_wrapper.find('.tabs_collapsed li').length === 0) {
                    tabs_wrapper.find('.moreTab').hide();
                } else {
                    tabs_wrapper.find('.moreTab').show();
                }




            });




            processing_resize = false;

            if (processing_resize_queue) {
                meta_tabs_responsive();
                processing_resize_queue = false;
            }


        }

        function sort_items(a, b) {
            return parseInt($(a).data('tab-order')) - parseInt($(b).data('tab-order'));
        }


        if ($('.wpas_admin_tabs').length > 0) {

            // Listen tab change
            $('.wpas_admin_tabs .wpas_tab_name').on('click', admin_tabs_change_handler);


            // Default display first tab
            $('.wpas_admin_tabs').each(function () {
                $($(this).find('.wpas_tab_name').get(0)).trigger('click');
            });


            admin_tabs_responsive();

            $(window).on('resize', admin_tabs_responsive);
        }
        
        
        var tab_widget = $('#wpas_admin_tabs_tickets_tablenav.wpas_admin_tabs');
                if( 0 < tab_widget.length ) {
                        
                        // place filter button into filter tab
                        $('#post-query-submit').appendTo('.filter_btn_container');
                        
                        // place date filter dropdown into filter tab
                        $('.actions #filter-by-date').appendTo('.filter_by_date_container');
                        
                        // Place search box into search tab
                        if( $('#posts-filter .search-box').length > 0 ) {
                                $('#posts-filter .search-box').appendTo('#search_tab_content_placeholder');
                        } else {
                                // Search box does not exist so we should hide it.
                                $('#wpas_admin_tabs_tickets_tablenav .wpas_admin_tabs_names_wrapper li[rel=wpas_admin_tabs_tickets_tablenav_search]').hide();
                        }

                        // Place bulk actions dropdown and button into bulk actions tab
                        if( $('#bulk-action-selector-top').length > 0 ) {
                                $('#bulk-action-selector-top, .actions.bulkactions input#doaction[type=submit]').appendTo('#bulk_action_tab_content_placeholder');
                        } else {
                                // Bulk action dropdown does not exist so we should hide bulk actions tab
                                $('#wpas_admin_tabs_tickets_tablenav .wpas_admin_tabs_names_wrapper li[rel=wpas_admin_tabs_tickets_tablenav_bulk_actions]').hide();
                        }

                        
                        
                        
                }


        // Enable/disable filters if Ticket ID specified
        $('#wpas_admin_tabs_tickets_tablenav .wpas_admin_tab_content #id').on( 'input', function () {
            if( $(this).val() === '' ) {
                // Enable other filters
                $('#wpas_admin_tabs_tickets_tablenav .wpas_admin_tab_content select').removeAttr('disabled', false);
            }
            else {
                // Disable other filters
                $('#wpas_admin_tabs_tickets_tablenav .wpas_admin_tab_content select').attr('disabled', 'disabled');
            }
        });

        // Disable filters on Ready if filtering by Ticket ID
        if( $('#wpas_admin_tabs_tickets_tablenav .wpas_admin_tab_content #id').val() !== '' ) {
            // Disable other filters
            $('#wpas_admin_tabs_tickets_tablenav .wpas_admin_tab_content select').attr('disabled', 'disabled');
        }
        
        
        
        /**
         * Only run this if main tabs exist in ticket add|edit page
         */
        if( 0 < $('#wpas_admin_tabs_ticket_main_custom_fields').length ) {
                $('#postdivrich').prependTo('.wpas-post-body-content');
        }
        
        
        /* Arrange metaboxes in ticket edit page on small screens */
        
        /* Lets store original position of metaboxes so we can revert them back to original positions on large screens  */
        if( 0 < $('#wpas-mb-toolbar').length ) {
                var toolbar_index = $('#wpas-mb-toolbar').index();
                var main_tabs_index = $('#wpas-mb-ticket-main-tabs').index();
                var replies_mb_index = $('#wpas-mb-replies').index();

                var toolbar_mb_sortable = $('#wpas-mb-toolbar').closest('.meta-box-sortables');
                var main_tabs_mb_sortable = $('#wpas-mb-ticket-main-tabs').closest('.meta-box-sortables');
                var replies_mb_sortable = $('#wpas-mb-replies').closest('.meta-box-sortables');
                
                var previous_layout_type = 0 === parseInt( $('#postbox-container-1').css( 'marginRight' ) ) ? 2 : 1;
                var layout_type ;
                
                /* Arrange metaboxes based on screen size */
                function arrange_ticket_metaboxes() {
                        
                        layout_type = 0 === parseInt( $('#postbox-container-1').css( 'marginRight' ) ) ? 2 : 1;
                        
                        if( layout_type == previous_layout_type ) {
                                return;
                        }

                        
                        if( 0 === parseInt( $('#postbox-container-1').css( 'marginRight' ) ) ) {
                                $('#wpas-mb-toolbar').insertAfter('#post-body-content');
                                $('#wpas-mb-ticket-main-tabs').insertAfter('#wpas-mb-toolbar');
                                $('#wpas-mb-replies').insertAfter('#wpas-mb-ticket-main-tabs');
                        } else {
                                $('#wpas-mb-toolbar').appendAtIndex( toolbar_mb_sortable, toolbar_index );
                                $('#wpas-mb-ticket-main-tabs').appendAtIndex( main_tabs_mb_sortable, main_tabs_index );
                                $('#wpas-mb-replies').appendAtIndex( replies_mb_sortable, replies_mb_index );
                        }
                        
                        
                        previous_layout_type = layout_type;
                        
                        $('body').find('.wp-editor-wrap').each(function () {
                    
                                var editor_ele_id = $(this).attr('id');
                                var editor_id = editor_ele_id.substring( 3, editor_ele_id.length - 5 ) ;

                                if (  tinyMCEPreInit.mceInit.hasOwnProperty(editor_id) && null !== tinyMCE.get( editor_id ) ) {
                                        tinyMCE.get( editor_id ).destroy();
                                        tinymce.init( tinyMCEPreInit.mceInit[editor_id] )
                                }
                        });
                        
                }
                
                $(window).on( 'resize', arrange_ticket_metaboxes );

                arrange_ticket_metaboxes();
        }
        
        /* Make sure we activate error tab once ticket submit button is pressed, so agent can see error message */
        if( $( '#wpas-mb-ticket-main-tabs' ).length > 0 ) {
                        
                $('form[name=post] #publishing-action, .wpas-reply-actions .wpas_btn_reply').click( function(e) {
                        if( !$('form[name=post]').get(0).checkValidity() ) {

                                $('#wpas_admin_tabs_ticket_main .wpas_admin_tab_content').find('input, select, textarea').each( function() {
                                        if( !$(this).get(0).checkValidity() ) {
                                                var error_tab = $(this).closest('.wpas_admin_tab_content').attr('id');
                                                $('#wpas_admin_tabs_ticket_main li[rel='+error_tab+']').trigger('click');
                                        }
                                })
                        }
                        
                });
        }
        

    });

}(jQuery));