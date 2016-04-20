(function ($) {
	"use strict";

	$(function () {

		/*
		Show more replies
		https://github.com/Awesome-Support/Awesome-Support/issues/255
		 */
		if (typeof wpas !== 'undefined' && $('.wpas-ticket-replies').length && $('.wpas-pagi').length) {

			// Cache selectors
			var container = $('.wpas-ticket-replies tbody'),
				pagination = $('.wpas-pagi'),
				button = $('.wpas-pagi-loadmore'),
				current = $('.wpas-replies-current'),
				total = $('.wpas-replies-total');

			// AJAX data
			var data = {
				'action': 'wpas_load_replies',
				'ticket_id': wpas.ticket_id,
				'ticket_replies_total': 0
			};

			// Size the loading spinner
			var loaderSize = $('.wpas-pagi-text').outerHeight();
			var loader = $('.wpas-pagi-loader').css({
				width: loaderSize,
				height: loaderSize
			});

			button.on('click', function (event) {
				event.preventDefault();

				// Update variable
				data.ticket_replies_total = container.find('tr.wpas-reply-single').length - 1;

				// Show loading spinner
				pagination.addClass('wpas-pagi-loading');

				$.post(wpas.ajaxurl, data, function (response) {
					// Parse JSON
					response = $.parseJSON(response);

					// Update pagination notice UI
					pagination.removeClass('wpas-pagi-loading');
					current.text(response.current);
					total.text(response.total);

					// Hide link if all replies are shown
					if (response.current == response.total) {
						button.hide();
					}

					// Add newer replies HTML
					$(response.html)
						.appendTo(container)
						.addClass('wpas-reply-single-added').delay(900).queue(function () {
							$(this).removeClass('wpas-reply-single-added').dequeue();
						});
				});
			});
		}

	});

}(jQuery));