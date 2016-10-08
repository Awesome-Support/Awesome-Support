(function ($) {
    "use strict";

    $(function () {

        $('#wpas-mailchimp-signup-form').submit(function (e) {

            $.ajax({
                type: 'POST',
                url: '//getawesomesupport.us2.list-manage.com/subscribe/post-json?u=46b50a9678918ccf5eb64131a&id=3beeab4d90&c=?',
                data: $('#wpas-mailchimp-signup-form').serialize(),
                dataType: 'jsonp',
                contentType: "application/json; charset=utf-8",
                success: function (response) {
                    if ('success' != response.result) {
                        $('#wpas-mailchimp-signup-result-error').html(response.msg).show();
                    } else {
                        $('#wpas-mailchimp-signup-result-error').hide();
                        $('#wpas-mailchimp-signup-result-success').html(response.msg).show();

                        // Hide the free addon page now that the user subscribed
                        dismiss_free_addon_page();
                    }
                }
            });

            // Stop normal form submission
            e.preventDefault();

        });

    });

    function dismiss_free_addon_page() {

        var data = {
            action: 'wpas_dismiss_free_addon_page'
        };

        jQuery.ajax({
            type:'POST',
            url: ajaxurl,
            data: data
        });
    }

}(jQuery));