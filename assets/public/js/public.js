//@prepros-prepend ../vendor/featherlight/featherlight.min.js

(function ($) {
	"use strict";

	$(function () {

		/*
		Check if TinyMCE is empty
		http://codeblow.com/questions/method-to-check-whether-tinymce-is-active-in-wordpress/
		 */
		if (typeof tinyMCE != "undefined") {

			$('.wpas-form').submit(function (event) {
				var submitBtn = $('[type="submit"]', $(this));
				var editorContent = tinyMCE.activeEditor.getContent();
				if (editorContent === '' || editorContent === null) {

					/* Highlight the active editor */
					$(tinyMCE.activeEditor.getBody()).css('background-color', '#ffeeee');

					/* Alert the user */
					alert('You can\'t submit an empty ticket reply.');
					$(tinyMCE.activeEditor.getBody()).css('background-color', '');

					/* Focus on editor */
					tinyMCE.activeEditor.focus();

					return false;
				} else {
					submitBtn.prop('disabled', true).text(submitBtn.data('onsubmit'));
				}
			});

		} else {

			$('.wpas-form').submit(function (event) {
				var submitBtn = $('[type="submit"]', $(this));
				submitBtn.prop('disabled', true).text(submitBtn.data('onsubmit'));
			});

		}

		/*
		Modal used on registration form (terms and conditions)
		 */
		$('.wpas-modal-trigger').featherlight();

	});

}(jQuery));