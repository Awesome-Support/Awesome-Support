(function ($) {
    "use strict";

    $(function () {

        $('#wpas-mailchimp-signup-form').submit(function (e) {

            $.ajax({
                type: 'POST',
                url: '//liabeuf.us2.list-manage.com/subscribe/post-json?u=17afd044fbacf43351ab7f56f&id=5cc0ccb414&c=?',
                data: $('#wpas-mailchimp-signup-form').serialize(),
                dataType: 'jsonp',
                contentType: "application/json; charset=utf-8",
                success: function (response) {
                    if ('success' != response.result) {
                        $('#wpas-mailchimp-signup-result-error').html(response.msg).show();
                    } else {
                        $('#wpas-mailchimp-signup-result-error').hide();
                        $('#wpas-mailchimp-signup-result-success').html(response.msg).show();
                    }
                }
            });

            // Stop normal form submission
            e.preventDefault();

        });

    });

}(jQuery));