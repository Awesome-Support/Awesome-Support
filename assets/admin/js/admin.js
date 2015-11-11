(function ($) {
	"use strict";

	$(function () {

		/**
		 * Automatically Link URLs, Email Addresses, Phone Numbers, etc.
		 * https://github.com/gregjacobs/Autolinker.js
		 */
		if ($('.wpas-ticket-content').length && $('.wpas-reply-content').length) {
			$('.wpas-ticket-content, .wpas-reply-content').each(function (index, el) {
				el.innerHTML = Autolinker.link(el.innerHTML);
			});
		}

		/**
		 * Mark as read
		 */
		$('.wpas-mark-read').on('click', function (event) {
			event.preventDefault();

			var btn = $(this),
				replyID = $(this).data('replyid'),
				data = {
					'action': 'wpas_mark_reply_read',
					'reply_id': replyID
				};

			$.post(ajaxurl, data, function (response) {

				/* check if response is an integer */
				if (Math.floor(response) == response && $.isNumeric(response)) {
					btn.fadeOut('fast');
					$('#wpas-unread-' + replyID).fadeOut('fast');
				} else {
					alert(response);
				}

			});

		});

		/**
		 * System Status
		 */
		var table,
			tableID,
			tableData,
			tables = [],
			output = $('#wpas-system-status-output');

		function tableToJSON(table) {

			$(table).each(function (index, el) {
				tableID = $(el).attr('id').replace('wpas-system-status-', '');
				tableData = $(el).tableToJSON();
				table = tableData;
				tables.push({
					label: tableID,
					data: tableData
				});
			});

		}

		$('#wpas-system-status-generate-json').click(function (event) {
			tableToJSON('.wpas-system-status-table');
			output.html(JSON.stringify(tables)).fadeIn('fast').focus().select();
		});

		$('#wpas-system-status-generate-wporg').click(function (event) {
			tableToJSON('.wpas-system-status-table');
			output.html('<pre>' + JSON.stringify(tables) + '</pre>').fadeIn('fast').focus().select();
		});

		/**
		 * Check if editor is empty
		 * http://stackoverflow.com/a/1180199
		 */
		$('.wpas-reply-actions').on('click', 'button', function (event) {

			var btn = $(event.target);

			// Detect which button is clicked
			if (btn.hasClass('wpas_btn_reply') || btn.hasClass('wpas_btn_reply_close')) {

				// Detect Visual and Text Mode in WordPress TinyMCE Editor
				var is_tinymce_active = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();

				// Visual Editor
				if (is_tinymce_active) {
					var editor = tinyMCE.activeEditor;
					var editorContent = editor.getContent();
					if (editorContent === '' || editorContent === null) {

						/* Alert the user */
						alert(wpasL10n.alertNoContent);

						/* Focus on editor */
						editor.focus();

						return false;
					}

				}

				// Text Editor
				else {
					var textarea = $('textarea[name="wpas_reply"]');
					if (!textarea.val()) {

						/* Alert the user */
						alert(wpasL10n.alertNoContent);

						/* Focus on editor */
						textarea.focus();

						return false;
					}
				}

			}
		});

		/**
		 * jQuery Select2
		 * http://select2.github.io/select2/
		 */
		if (jQuery().select2 && $('select.wpas-select2').length) {
			$('select.wpas-select2:visible').select2();
		}

		/**
		 * Make ticket title required
		 * http://wordpress.stackexchange.com/a/101260
		 */
		$('#publish').on('click', function () {
			$('#titlediv > #titlewrap > #title').prop('required', true);
		});

	});

}(jQuery));