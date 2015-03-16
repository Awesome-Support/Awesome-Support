(function ($) {
	"use strict";

	$(function () {

		var data, btnEdit, btnDelete, btnCancel, btnSave, editorRow, replyId, editorId, reply;

		btnEdit = $('.wpas-edit');
		btnDelete = $('.wpas-delete');
		btnCancel = $('.wpas-editcancel');
		editorRow = $('.wpas-editor');

		////////////////////////////////////
		// Edit / Delete Ticket TinyMCE //
		////////////////////////////////////
		if (typeof tinyMCE == 'undefined' && typeof tinyMCEPreInit == 'undefined' && tinymce.editors.length) {

			alert('No instance of TinyMCE found. Please use wp_editor on this page at least once: http://codex.wordpress.org/Function_Reference/wp_editor');

		} else {

			btnEdit.on('click', function (event) {
				event.preventDefault();

				btnEdit = $(this);
				replyId = $(this).data('replyid');
				editorId = $(this).data('wysiwygid');
				reply = $($(this).data('origin'));
				btnSave = $('#wpas-edit-submit-' + replyId);

				// Update the UI
				btnEdit.text('Loading editor...').prop('disabled', true).blur();
				reply.hide();

				/*
				Check if wp_editor has already been created
				Only do AJAX if necessary
				 */
				if ($('.wpas-editwrap-' + replyId).hasClass('wp_editor_active')) {

					$('.wpas-editwrap-' + replyId).show();

				} else {

					// AJAX data
					data = {
						'action': 'wp_editor_ajax',
						'post_id': replyId,
						'editor_id': editorId
					};

					// AJAX request
					$.post(ajaxurl, data, function (response) {
						// Hide the Edit button
						btnEdit.text('Editor is active');

						// Append editor to DOM
						$('.wpas-editwrap-' + replyId).addClass('wp_editor_active').show();
						$('.wpas-editwrap-' + replyId + ' .wpas-wp-editor').html(response);

						// Init TinyMCE
						tinyMCE.init(tinyMCEPreInit.mceInit[data.editor_id]);

						// Init quicktags
						// Will not work because of https://core.trac.wordpress.org/ticket/26183
						try {
							quicktags(tinyMCEPreInit.qtInit[data.editor_id]);
						} catch (e) {}
					});

				}

				// Save the reply
				btnSave.on('click', function (e) {
					e.preventDefault();

					// Update the UI
					btnEdit.text('Edit');
					btnSave.prop('disabled', true).val('Saving...');

					var tinyMCEContent = tinyMCE.get(editorId).getContent();
					var data = {
						'action': 'wpas_edit_reply',
						'reply_id': replyId,
						'reply_content': tinyMCEContent
					};

					$.post(ajaxurl, data, function (response) {
						// check if the response is an integer
						if (Math.floor(response) == response && $.isNumeric(response)) {

							// Revert to save button
							btnSave.prop('disabled', false).val('Save changes');
							reply.html(tinyMCEContent).show();
							editorRow.hide();
						} else {
							alert(response);
						}
					});
				});

				// Cancel
				btnCancel.on('click', function (e) {
					e.preventDefault();

					// Restore the original wp_editor content					
					var data = {
						'action': 'wp_editor_content_ajax',
						'post_id': replyId
					};
					$.post(ajaxurl, data, function (response) {
						tinyMCE.get(editorId).setContent(response);
					});

					// Update the UI
					reply.show();
					editorRow.hide();
					btnEdit.text('Edit');
				});
			});

		}

		btnDelete.click(function (e) {
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