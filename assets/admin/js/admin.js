(function ($) {
	"use strict";

	$(function () {

		////////////////////
		// Mark as read //
		////////////////////
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

		/////////////////////
		// System Status //
		/////////////////////

		var table, tableID, tableData, tables = [];

		$('.wpas-system-status-table').each(function (index, el) {
			tableID = $(el).attr('id').replace('wpas-system-status-', '');
			tableData = $(el).tableToJSON();
			table = tableData;
			tables.push({
				label: tableID,
				data: tableData
			});
		});

		$('#wpas-system-status-generate-json').click(function (event) {
			/* Populate the textarea and select all its content */
			/* http://stackoverflow.com/a/5797700 */
			$('#wpas-system-status-output').html(JSON.stringify(tables)).fadeIn('fast').focus().select();
		});

		$('#wpas-system-status-generate-wporg').click(function (event) {
			/* Populate the textarea and select all its content */
			/* http://stackoverflow.com/a/5797700 */
			$('#wpas-system-status-output').html('`' + JSON.stringify(tables) + '`').fadeIn('fast').focus().select();
		});

		////////////////////////////////
		// Check if editor is empty //
		////////////////////////////////
		$('.wpas-reply-actions').on('click', 'button', function () {
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
			}
		});

		////////////////////////////////
		// jQuery Select2 //
		// http://select2.github.io/select2/
		////////////////////////////////
		if (jQuery().select2 && $('select.wpas-select2').length) {
			var select = $('select.wpas-select2');

			select.find('option[value=""]').remove();
			select.prepend('<option></option>');
			select.select2({
				placeholder: 'Please Select'
			});
		}

	});

}(jQuery));