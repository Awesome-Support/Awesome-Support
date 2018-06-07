(function ($) {

    "use strict";

    $('.wpas-admin-quick-action-print').on('click', function (e) {

        e.preventDefault();

        var ticket_id = $(this).data('id');

        $('body').append('<div id="wpas-print-ticket-box"></div>');

    });

}(jQuery));