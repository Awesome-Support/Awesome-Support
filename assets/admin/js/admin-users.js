jQuery(document).ready(function ($) {

	/**
	 * Get User List via AJAX
	 * https://select2.github.io/examples.html#data-ajax
	 */
	$('.wpas-select2').each(function (index, el) {
		var capability = $(el).attr('data-capability');
		if (capability) {
			$(el).select2({
				ajax: {
					url: ajaxurl,
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							action: 'wpas_get_users',
							cap: capability,
							q: params.term
						};
					},
					processResults: function (data) {
						console.log(data);
						return {
							results: data.items
						};
					}
				}
			});
		}
	});

});