(function ($) {
	"use strict";

	$(function () {

		var wpasBtnEdit, wpasBtnDelete, wpasBtnCancel, wpasBtnSave, wpasEditorRow, wpasReplyID, wpasReply, wpasWisywigID, wpasOrigin;

		wpasBtnEdit = $('.wpas-edit');
		wpasBtnDelete = $('.wpas-delete');
		wpasBtnCancel = $('.wpas-editcancel');
		wpasBtnSave = $('.wpas-btn-save-reply');
		wpasEditorRow = $('.wpas-editor');

		////////////////////////////////////
		// Edit / Delete Ticket TinyMCE //
		////////////////////////////////////
		wpasBtnEdit.click(function (e) {
			e.preventDefault();

			wpasReplyID = $(this).data('replyid');
			wpasReply = $(this).data('reply');
			wpasWisywigID = $(this).data('wysiwygid');
			wpasOrigin = $(this).data('origin');

			// Handle Layout changes
			wpasEditorRow.hide();
			$(wpasOrigin).hide();
			$('.' + wpasReply).show();

			// Save the 
			$('.' + wpasReply).find('[type="submit"]').click(function (e) {
				e.preventDefault();

				$(this).prop('disabled', true).val('Saving...');

				var tinyMCEContent = tinyMCE.get(wpasWisywigID).getContent();

				console.log(tinyMCEContent);

				var data = {
					'action': 'wpas_edit_reply',
					'reply_id': wpasReplyID,
					'reply_content': tinyMCEContent
				};
				$.post(ajaxurl, data, function (response) {

					console.log(response);

					/* check if the response is an integer */
					if (Math.floor(response) == response && $.isNumeric(response)) {
						$(wpasOrigin).html(tinyMCEContent).show();
						wpasEditorRow.hide();
					} else {
						alert(response);
					}
				});
			});
		});

		wpasBtnCancel.click(function (e) {
			e.preventDefault();

			wpasReply = $(this).data('reply');
			wpasOrigin = $(this).data('origin');

			// Handle Layout changes
			wpasEditorRow.hide();
			$(wpasOrigin).show();
			$('.' + wpasReply).hide();
		});

		wpasBtnDelete.click(function (e) {
			if (confirm(wpasL10n.alertDelete)) {
				return true;
			} else {
				return false;
			}
		});

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

				console.log(response);

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

		$('#wpas-system-status-generate').click(function (event) {
			console.log(tables);
			/* Populate the textarea and select all its content */
			/* http://stackoverflow.com/a/5797700 */
			$('#wpas-system-status-output').html(JSON.stringify(tables)).fadeIn('fast').focus().select();
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

	});

}(jQuery));