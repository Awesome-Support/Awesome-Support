(function ($) {
	"use strict";

	$(function () {

		/**
		 * Automatically Link URLs, Email Addresses, Phone Numbers, etc.
		 * https://github.com/gregjacobs/Autolinker.js
		 */
		if ($('.wpas-reply-content').length) {
			$('.wpas-reply-content').each(function (index, el) {
				el.innerHTML = Autolinker.link(el.innerHTML);
			});
		}

		/*
		Closing Ticket Function
		 */
		var replyForm = $('#wpas-new-reply');
		var replyInput = $('textarea[name="wpas_user_reply"]');
		var replyClose = $('input[name="wpas_close_ticket"]');

		replyForm.on('change', replyClose, function () {
			// Check if textarea is focusable
			if (replyInput.is(':visible')) {
				// If "Close this ticket" checkbox is checked, add required attribute to textarea 
				replyInput.prop('required', replyClose.is(':checked'));
			}
		});

		/*
		Check if TinyMCE is empty
		http://codeblow.com/questions/method-to-check-whether-tinymce-is-active-in-wordpress/
		http://stackoverflow.com/a/8749616
		 */
		if (typeof tinyMCE != "undefined") {

			$('.wpas-form').submit(function (event) {
				var submitBtn = $('[type="submit"]', $(this));
				var editorContent = tinyMCE.activeEditor.getContent();
				if (!replyClose.is(':checked') && (editorContent === '' || editorContent === null)) {

					/* Highlight the active editor */
					$(tinyMCE.activeEditor.getBody()).css('background-color', '#ffeeee');

					/* Alert the user */
					alert(wpas.translations.emptyEditor);

					/* Restore the editor background color */
					$(tinyMCE.activeEditor.getBody()).css('background-color', '');

					/* Focus on editor */
					tinyMCE.activeEditor.focus();

					return false;
				} else {
					submitBtn.prop('disabled', true).text(wpas.translations.onSubmit);
				}
			});

		} else {

			$('.wpas-form').submit(function (event) {
				var submitBtn = $('[type="submit"]', $(this));
				var submitText = submitBtn.attr('data-onsubmit') ? submitBtn.attr('data-onsubmit') : wpas.translations.onSubmit;
				submitBtn.prop('disabled', true).text(submitText);
			});

		}

	});

}(jQuery));