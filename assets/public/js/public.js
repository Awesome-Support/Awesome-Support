(function ($) {
	"use strict";

	$(function () {

		$('.wpas-form').submit(function (event) {
			var submitBtn = $('[type="submit"]', $(this));
			submitBtn.prop('disabled', true).text(submitBtn.data('onsubmit'));
		});

	});

}(jQuery));