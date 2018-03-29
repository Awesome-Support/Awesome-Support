(function ($) {
	"use strict";

	$(function () {

		// Our condition to prevent error
		var selector = $('#wpas_ticketlist');
		var rows = $('#wpas_ticketlist > tbody > tr');
		var rowCount = rows.length;
		var controls = $('#wpas_ticketlist_filters');
		var condition = selector.length && rowCount >= 5 && $.fn.footable && typeof wpas !== 'undefined';
		var res = ( false !== condition ) ? drawTable() : controls.hide();

		function drawTable() {

			// Cache selectors
			var statusDropdown = $('.wpas-filter-status');

			// Initialize FooTable
			selector.footable();

			// Create the status filter
			selector.footable().bind('footable_filtering', function (e) {
				var selected = statusDropdown.find(':selected').val();
				if (selected && selected.length > 0) {
					e.filter += (e.filter && e.filter.length > 0) ? ' ' + selected : selected;
					e.clear = !e.filter;
				}
			});

			// Create the status dropdown
			var statusesArr = [];
			var statusesOptions = '';
			rows.each(function (index, el) {
				var status = $(el).find('.wpas-label-status').text();
				if (statusesArr.indexOf(status) == -1) {
					statusesArr.push(status);
					statusesOptions += '<option value="' + status + '">' + status + '</option>';
				}
			});

			// Show and populate the dropdown
			if (statusesArr.length > 1) {
				statusDropdown.append(statusesOptions);
			} else {
				statusDropdown.hide();
			}

			// Filter the table using footable_filter
			statusDropdown.change(function (e) {
				e.preventDefault();
				selector.trigger('footable_filter', {
					filter: $('#wpas_filter').val()
				});
			});

			// Clear status dropdown and search box
			$('.wpas-clear-filter').click(function (e) {
				e.preventDefault();
				statusDropdown.val('');
				selector.trigger('footable_clear_filter');
			});

		}

	});

}(jQuery));