(function ($) {
	"use strict";

	$(function () {

		// Our condition to prevent error
		var selector = $('#wpas_ticketlist');
		var rows = $('#wpas_ticketlist > tbody > tr');
		var rowCount = rows.length;
		var controls = $('#wpas_ticketlist_filters');
		var condition = selector.length && rowCount >= 5 && $.fn.footable && typeof wpas !== 'undefined';
		condition ? drawTable() : controls.hide();

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

			// Attach status filter to table
			statusDropdown.change(function (e) {
				e.preventDefault();
				var status = statusDropdown.val();
				rows.show();
				$('tr.' + status).hide();
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