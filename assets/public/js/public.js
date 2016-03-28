//@prepros-prepend ../vendor/featherlight/featherlight.min.js
//@prepros-prepend ../vendor/hideShowPassword/hideShowPassword.js

(function ($) {
	"use strict";

	/*
	Convert string to Boolean
	http://stackoverflow.com/a/21445227
	 */
	function stringToBool(val) {
		return (val + '').toLowerCase() === 'true';
	}

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

		/*
		Registration form: terms and conditions modal
		https://github.com/noelboss/featherlight/
		 */
		$('.wpas-modal-trigger').featherlight();

		/*
		Registration form: toggle password visibility
		https://github.com/cloudfour/hideShowPassword
		 */
		$('#wpas_form_registration').on('change', 'input[name="wpas_pwdshow[]"]', function (event) {
			event.preventDefault();
			$('#wpas_password').hideShowPassword($(this).prop('checked'));
		});

		/*
		Registration form: email validation by MailGun
		http://www.mailgun.com/email-validation
		http://documentation.mailgun.com/api-email-validation.html
		 */
		if (typeof wpas !== 'undefined' && stringToBool(wpas.emailCheck) && $('#wpas_form_registration').length) {

			var emailInput = $('#wpas_form_registration #wpas_email'),
				emailCheck = $('<div class="wpas-help-block" id="wpas_emailvalidation"></div>'),
				data;

			emailCheck.appendTo($('#wpas_email_wrapper')).hide();

			emailInput.on('change', function () {
				emailInput.addClass('wpas-form-control-loading');
				data = {
					'action': 'email_validation',
					'email': emailInput.val()
				};
				$.post(wpas.ajaxurl, data, function (response) {
					emailCheck.html(response).show();
					emailInput.removeClass('wpas-form-control-loading');
				});
			});

			emailCheck.on('click', 'strong', function () {
				emailInput.val($(this).html());
				emailCheck.hide();
			});
		}

		/*
		Clear the file input with jQuery
		http://stackoverflow.com/a/13351234
		 */
		function clearFileInput(input) {
			input.wrap('<form>').parent('form').trigger('reset');
			input.unwrap();
		}

		/*
		Limit maximum items on a multiple input
		http://stackoverflow.com/a/10105631		
		 */
		if (typeof wpas !== 'undefined' && wpas.fileUploadMax) {
			var $fileUpload = $('#wpas_files');
			$fileUpload.on('change', function (event) {
				event.preventDefault();

				// Check file input size with jQuery | http://stackoverflow.com/a/3937404
				var bigFiles = [];
				$.each($fileUpload.get(0).files, function (index, val) {
					if (val.size > wpas.fileUploadSize) {
						bigFiles.push(val.name);
					}
				});
				if (bigFiles.length !== 0) {
					alert(wpas.fileUploadMaxSizeError[0] + '\n\n' + bigFiles.join('\n') + '.\n\n' + wpas.fileUploadMaxSizeError[1]);
					clearFileInput($fileUpload);
				}

				// Check if not uploading too many files
				if (parseInt($fileUpload.get(0).files.length) > parseInt(wpas.fileUploadMax, 10)) {
					alert(wpas.fileUploadMaxError);
					clearFileInput($fileUpload);
				}
			});
		}

	});

}(jQuery));