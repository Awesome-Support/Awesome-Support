(function ($) {
	"use strict";

	$(function () {

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

	});

}(jQuery));