(function ($) {
	"use strict";

	$(function () {

		var data, wpasBtnEdit, wpasBtnDelete, wpasBtnCancel, wpasBtnSave, wpasEditorRow, wpasReplyID, wpasReply, wpasWisywigID, wpasOrigin, btnSave, originalContent;

		wpasBtnEdit = $('.wpas-edit');
		wpasBtnDelete = $('.wpas-delete');
		wpasBtnCancel = $('.wpas-editcancel');
		wpasBtnSave = $('.wpas-btn-save-reply');
		wpasEditorRow = $('.wpas-editor');

		////////////////////////////////////
		// Edit / Delete Ticket TinyMCE //
		////////////////////////////////////
		if (typeof tinyMCE == 'undefined' && typeof tinyMCEPreInit == 'undefined' && tinymce.editors.length) {

			alert('No instance of TinyMCE found. Please use wp_editor on this page at least once: http://codex.wordpress.org/Function_Reference/wp_editor');

		} else {

			wpasBtnEdit.on('click', function (event) {
				event.preventDefault();

				wpasReplyID = $(this).data('replyid');
				wpasReply = $(this).data('reply');
				wpasWisywigID = $(this).data('wysiwygid');
				wpasOrigin = $($(this).data('origin'));
				btnSave = $('#wpas-edit-submit-' + wpasReplyID);

				// Update the UI
				wpasBtnEdit.text('Loading editor...').prop('disabled', true).blur();
				wpasOrigin.hide();

				/*
				Check if wp_editor has already been created
				Only do AJAX if necessary
				 */
				if ($('.wpas-editwrap-' + wpasReplyID).hasClass('wp_editor_active')) {

					$('.wpas-editwrap-' + wpasReplyID).show();

				} else {

					// AJAX data
					data = {
						'action': 'wp_editor_ajax',
						'post_id': wpasReplyID,
						'editor_id': wpasWisywigID
					};

					// AJAX request
					$.post(ajaxurl, data, function (response) {
						// Hide the Edit button
						wpasBtnEdit.text('Editor is active');

						console.log(response);

						// Append editor to DOM
						$('.wpas-editwrap-' + wpasReplyID).addClass('wp_editor_active').show();
						$('.wpas-editwrap-' + wpasReplyID + ' .wpas-wp-editor').html(response);

						// Init TinyMCE
						tinyMCE.init(tinyMCEPreInit.mceInit[data.editor_id]);

						// Init quicktags
						// Will not work because of https://core.trac.wordpress.org/ticket/26183
						try {
							quicktags(tinyMCEPreInit.qtInit[data.editor_id]);
						} catch (e) {}

						// Get TinyMCE content
						setTimeout(function () {
							originalContent = tinyMCE.get(wpasWisywigID).getContent();
						}, 100);
					});

				}

				// Save the reply
				btnSave.on('click', function (e) {
					e.preventDefault();

					// Update the UI
					wpasBtnEdit.text('Edit');
					btnSave.prop('disabled', true).val('Saving...');

					var tinyMCEContent = tinyMCE.get(wpasWisywigID).getContent();
					var data = {
						'action': 'wpas_edit_reply',
						'reply_id': wpasReplyID,
						'reply_content': tinyMCEContent
					};

					$.post(ajaxurl, data, function (response) {
						// check if the response is an integer
						if (Math.floor(response) == response && $.isNumeric(response)) {

							// Revert to save button
							btnSave.prop('disabled', false).val('Save changes');
							wpasOrigin.html(tinyMCEContent).show();
							wpasEditorRow.hide();
						} else {
							alert(response);
						}
					});
				});

				// Cancel
				wpasBtnCancel.on('click', function (e) {
					e.preventDefault();

					// Restore the original wp_editor content
					tinyMCE.get(wpasWisywigID).setContent(originalContent);

					// Update the UI
					wpasOrigin.show();
					wpasEditorRow.hide();
					wpasBtnEdit.text('Edit');
				});
			});

		}

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
				placehoriginalContenter: 'Please Select'
			});
		}

	});

}(jQuery));