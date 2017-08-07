jQuery(document).ready(function ($) {

	/**
	 * jQuery Datepicker
	 */
	if (jQuery().datepicker && $('input.wpas-date').length) {
		$('input.wpas-date').datepicker();
	}

});