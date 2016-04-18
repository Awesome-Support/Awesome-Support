(function ($) {
	"use strict";

	$(function () {

		/*
		Show more replies
		https://github.com/Awesome-Support/Awesome-Support/issues/255
		 */
		if (typeof wpas !== 'undefined' && $('.wpas-ticket-replies').length && $('.wpas-pagi').length) {

			// Cache selectors
			var container = $('.wpas-ticket-replies'),
				pagination = $('.wpas-pagi'),
				button = $('.wpas-pagi-loadmore'),
				current = $('.wpas-replies-current'),
				total = $('.wpas-replies-total'),
				totalCount = container.find('tbody tr.wpas-reply-single').length;

			// AJAX data
			var data = {
				'action': 'wpas_load_replies',
				'ticket_id': wpas.ticket_id,
				'ticket_replies_total': totalCount
			};

			// Size the loading spinner
			var loaderSize = $('.wpas-pagi-text').outerHeight();
			var loader = $('.wpas-pagi-loader').css({
				width: loaderSize,
				height: loaderSize
			});

			button.on('click', function (event) {
				event.preventDefault();

				// Show loading spinner
				pagination.addClass('wpas-pagi-loading');

				$.post(wpas.ajaxurl, data, function (response) {
					// Update pagination notice UI
					pagination.removeClass('wpas-pagi-loading');
					current.text(response.current);
					total.text(response.total);

					// Add newer replies HTML
					container.append(response.html);
				});
			});
		}

	});

}(jQuery));