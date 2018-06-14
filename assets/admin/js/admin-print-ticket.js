(function ($) {

    "use strict";

    function get_print_ticket_window(title) {

        var html = '<div id="wpas-print-ticket-box">';
        html += '<a href="#" id="wpas-print-ticket-box-close"><i class="dashicons dashicons-no"></i></a>';
        html += '<div id="wpas-print-ticket-box-content">';
        html += '<h2>' + title + '</h2>';
        html += '<div id="wpas-print-ticket-box-ticket-content"><img src="' + WPAS_Print.admin_url + 'images/loading.gif"></div>';
        html += '<div id="wpas-print-ticket-box-buttons">';
        html += '<a href="#" id="wpas-print-btn" class="button button-primary button-large">' + WPAS_Print.print + '</a>';
        html += '<a href="#" id="wpas-print-btn-cancel" class="button button-large">' + WPAS_Print.cancel + '</a>';
        html += '<label><input type="checkbox" id="wpas-print-toggle-replies" checked>' + WPAS_Print.include_replies + '</label>';
        html += '<label><input type="checkbox" id="wpas-print-toggle-history">' + WPAS_Print.include_history + '</label>';
        html += '<label><input type="checkbox" id="wpas-print-toggle-private-notes">' + WPAS_Print.include_private_notes + '</label>';
        html += '</div></div></div>';
        return html;
    }

    /**
     * Load print window
     */
    $(document).on('click', '.wpas-admin-quick-action-print', function (e) {

        e.preventDefault();

        var ticket_id = $(this).data('id');

        var html = get_print_ticket_window( WPAS_Print.print_ticket + ' #' + ticket_id );

        $('body').append(html);

        $.post(ajaxurl, {
            action: 'wpas_get_ticket_for_print',
            id: ticket_id,
            nonce: WPAS_Print.nonce,
            dataType: 'json'
        }).done(function (data) {

            $('#wpas-print-ticket-box-ticket-content').html(data);

        });

    });

    /**
     * Close print window function
     */
    function closeWindow() {

        $('#wpas-print-ticket-box').fadeOut(function () {
            $(this).remove();
        });
    }

    /**
     * Close print window
     */
    $(document).on('click', '#wpas-print-ticket-box-close, #wpas-print-btn-cancel', function (e) {
        e.preventDefault();
        closeWindow();
    });

    /**
     * Close print window on ESC
     */
    $(document).on('keyup', function (e) {
        if (e.keyCode == 27) {
            closeWindow();
        }
    });

    /**
     * Toggle history
     */
    $(document).on('click', '#wpas-print-toggle-history', function (e) {

        if ($(this).is(':checked')) {
            $('.wpas-print-ticket-history').show();
        } else {
            $('.wpas-print-ticket-history').hide();
        }

    });

    /**
     * Toggle replies
     */
    $(document).on('click', '#wpas-print-toggle-replies', function (e) {

        if ($(this).is(':checked')) {
            $('.wpas-print-ticket-reply').show();
        } else {
            $('.wpas-print-ticket-reply').hide();
        }

    });

    /**
     * Toggle notes
     */
    $(document).on('click', '#wpas-print-toggle-private-notes', function (e) {

        if ($(this).is(':checked')) {
            $('.wpas-print-ticket-notes').show();
        } else {
            $('.wpas-print-ticket-notes').hide();
        }

    });


    /**
     * Print Ticket
     */
    $(document).on('click', '#wpas-print-btn', function (e) {

        e.preventDefault();

        var frame = document.createElement('iframe');
        var content = $('#wpas-print-ticket-box-ticket-content')[0].outerHTML;

        frame.id = 'wpas-print-ticket-frame';
        document.body.appendChild(frame);

        frame.contentDocument.write(content);
        frame.focus();

        $('#' + frame.id).contents().find('head').append('<link rel="stylesheet" href="' + WPAS_Print.plugin_url + 'assets/admin/css/admin-print-ticket-iframe.css">');

        window.setTimeout(function () {
            frame.contentWindow.print();
            $('#' + frame.id).remove();
        }, 1000);

    });


    /**
     * Print Tickets bulk
     */

     $(document).on('submit', '#posts-filter', function(e){

        if ( $('select[name="action2"]', this).val() == 'wpas_print_tickets' || $('select[name="action"]', this).val() == 'wpas_print_tickets' ) {

            e.preventDefault();

            // get ids
            var ticket_ids = [];
            
            $('input[name="post[]"]:checked', this).each(function(i, el) {
                ticket_ids[i] = $(el).val();
            });

            var html = get_print_ticket_window( WPAS_Print.print_tickets );

            $('body').append(html);

            $.post(ajaxurl, {
                action: 'wpas_get_tickets_for_print',
                ids: ticket_ids,
                nonce: WPAS_Print.nonce,
                dataType: 'json'
            }).done(function (data) {

                $('#wpas-print-ticket-box-ticket-content').html(data);

            });
        }

     });

}(jQuery));