jQuery(document).ready(function ($) {

	/**
	 * Condition to prevent JavaScript error. It checks if:
	 * 1) Selector exists
	 * 2) jQuery Select2 is loaded
	 * 3) WordPress AJAX is enabled
	 */
	var selector = $('.wpas-select2');
	var condition = selector.length && $.fn.select2 && typeof ajaxurl !== 'undefined';
	if (condition) getUserList( selector );

	/**
	 * Get User List via AJAX
	 * https://select2.github.io/examples.html#data-ajax
	 */
        
        
        
        
	function getUserList( selector ) {
		selector.each(function (index, el) {
			var capability = $(el).attr('data-capability');
			if (capability) {
				$(el).select2({
					ajax: {
						url: ajaxurl,
						dataType: 'json',
						type: 'POST',
						delay: 250,
						data: function (params) {
							return {
								action: 'wpas_get_users',
								cap: capability,
								q: params.term
							};
						},
						processResults: function (data, params) {
							return {
								results: $.map(data, function (obj) {
									return {
										id: obj.user_id,
										text: "#" +  obj.user_id + " " + obj.user_name + " (" +  obj.user_email + ")"
									};
								})
							};
						}
					},
					minimumInputLength: 3
				});
			}
		});
	}
        
        window.getUserListSelect2 = getUserList;

});
